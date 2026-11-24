import { createHash, randomUUID } from "node:crypto";
import { ApiError, mapStatusToError } from "./errors.js";
import type { ApiListMeta, ApiListResponse } from "./types.js";

export type FetchLike = (input: string, init?: RequestInit) => Promise<Response>;

export interface ApiEnvelope {
  data?: unknown;
  message?: string;
}

export interface RequestOptions {
  /** Forwarded as X-Idempotency-Key. Enables safe retries for writes. */
  idempotencyKey?: string;
  /** Retry network failures and 5xx even for non-GET requests. */
  retryableWrite?: boolean;
}

interface ClientDeps {
  baseUrl: string;
  token: string;
  timeoutMs: number;
  maxRetries: number;
  minIntervalMs: number;
  fetchFn?: FetchLike;
  now?: () => number;
  sleep?: (ms: number) => Promise<void>;
}

const RETRYABLE_STATUSES = new Set([408, 425, 500, 502, 503, 504]);

export class RumuApiClient {
  private readonly fetchFn: FetchLike;
  private readonly now: () => number;
  private readonly sleep: (ms: number) => Promise<void>;
  private queue: Promise<unknown> = Promise.resolve();
  private lastRequestAt = 0;

  constructor(private readonly deps: ClientDeps) {
    this.fetchFn = deps.fetchFn ?? ((input, init) => fetch(input, init));
    this.now = deps.now ?? Date.now;
    this.sleep = deps.sleep ?? ((ms) => new Promise((resolve) => setTimeout(resolve, ms)));
  }

  get baseUrl(): string {
    return this.deps.baseUrl;
  }

  async get<T = unknown>(path: string, params?: Record<string, unknown>): Promise<T> {
    return this.request<T>("GET", path, undefined, params);
  }

  async getList<T = unknown>(
    path: string,
    params?: Record<string, unknown>,
  ): Promise<{ items: T[]; meta?: ApiListMeta; message?: string | null }> {
    const res = await this.request<ApiListResponse<T>>("GET", path, undefined, params);
    const items = Array.isArray(res.data) ? res.data : res.data != null ? [res.data as T] : [];
    return { items, meta: res.meta, message: res.message };
  }

  async post<T = ApiEnvelope>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return this.request<T>("POST", path, body ?? {}, undefined, options);
  }

  async put<T = ApiEnvelope>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return this.request<T>("PUT", path, body ?? {}, undefined, options);
  }

  async patch<T = ApiEnvelope>(path: string, body?: unknown, options?: RequestOptions): Promise<T> {
    return this.request<T>("PATCH", path, body ?? {}, undefined, options);
  }

  async delete<T = ApiEnvelope>(path: string, options?: RequestOptions): Promise<T> {
    return this.request<T>("DELETE", path, undefined, undefined, options);
  }

  /** Raw text fetch (e.g. CSV export). Never parses JSON. */
  async getText(path: string, params?: Record<string, unknown>): Promise<{ text: string; headers: Headers }> {
    const res = await this.perform("GET", path, undefined, params, undefined);
    const text = await res.text();
    return { text, headers: res.headers };
  }

  private async request<T>(
    method: string,
    path: string,
    body?: unknown,
    params?: Record<string, unknown>,
    options?: RequestOptions,
  ): Promise<T> {
    const isRead = method === "GET" || method === "HEAD";
    const maxAttempts = 1 + (isRead || options?.retryableWrite ? this.deps.maxRetries : 0);

    let attempt = 0;
    for (;;) {
      attempt += 1;
      try {
        const res = await this.perform(method, path, body, params, options);
        if (res.status === 204) return null as T;
        const text = await res.text();
        const json: unknown = text ? safeJsonParse(text) : null;
        if (!res.ok) {
          throw mapStatusToError(res.status, (json ?? null) as { message?: unknown; errors?: unknown } | null, res.headers);
        }
        return json as T;
      } catch (err) {
        if (err instanceof ApiError) {
          const canRetry =
            attempt < maxAttempts &&
            ((err.retryable && (err.code === "rate_limited" || err.code === "server")) ||
              (err.code === "unknown" && RETRYABLE_STATUSES.has(err.status)));
          if (!canRetry) throw err;
          const delay = err.retryAfterSeconds != null
            ? err.retryAfterSeconds * 1000
            : backoffMs(attempt);
          await this.sleep(delay);
          continue;
        }
        const networkError = toNetworkError(err);
        if (attempt >= maxAttempts) throw networkError;
        await this.sleep(backoffMs(attempt));
      }
    }
  }

  private async perform(
    method: string,
    path: string,
    body?: unknown,
    params?: Record<string, unknown>,
    options?: RequestOptions,
  ): Promise<Response> {
    // Serialize requests and enforce a minimum interval between API calls.
    const run = this.queue.then(async () => {
      const elapsed = this.now() - this.lastRequestAt;
      if (elapsed < this.deps.minIntervalMs) {
        await this.sleep(this.deps.minIntervalMs - elapsed);
      }
      this.lastRequestAt = this.now();

      const url = new URL(`${this.deps.baseUrl}${path.startsWith("/") ? path : `/${path}`}`);
      if (params) {
        for (const [key, value] of Object.entries(params)) {
          if (value === undefined || value === null || value === "") continue;
          url.searchParams.set(key, String(value));
        }
      }

      const headers: Record<string, string> = {
        Accept: "application/json",
        Authorization: `Bearer ${this.deps.token}`,
        "X-Request-Id": randomUUID(),
      };
      const init: RequestInit = { method, headers, signal: AbortSignal.timeout(this.deps.timeoutMs) };
      if (body !== undefined) {
        headers["Content-Type"] = "application/json";
        init.body = JSON.stringify(body);
      }
      if (options?.idempotencyKey) {
        headers["X-Idempotency-Key"] = options.idempotencyKey;
      }
      return this.fetchFn(url.toString(), init);
    });
    this.queue = run.catch(() => undefined);
    return run;
  }
}

export function createClient(config: {
  apiBase: string;
  token: string;
  timeoutMs: number;
  maxRetries: number;
  minIntervalMs: number;
}, fetchFn?: FetchLike): RumuApiClient {
  return new RumuApiClient({
    baseUrl: config.apiBase,
    token: config.token,
    timeoutMs: config.timeoutMs,
    maxRetries: config.maxRetries,
    minIntervalMs: config.minIntervalMs,
    fetchFn,
  });
}

function safeJsonParse(text: string): unknown {
  try {
    return JSON.parse(text);
  } catch {
    return null;
  }
}

function backoffMs(attempt: number): number {
  const base = 500 * 2 ** (attempt - 1);
  return base + Math.floor(Math.random() * 250);
}

function toNetworkError(err: unknown): ApiError {
  const name = err instanceof Error ? err.name : "";
  if (name === "TimeoutError" || name === "AbortError") {
    return new ApiError(0, "timeout", "The Rumu API did not respond in time. Try again shortly.", undefined, true);
  }
  return new ApiError(0, "network", "Could not reach the Rumu API. Check connectivity and RUMU_API_BASE.", undefined, true);
}

export function hashArguments(input: unknown): string {
  return createHash("sha256").update(canonicalJson(input)).digest("hex");
}

export function canonicalJson(value: unknown): string {
  if (value === null || typeof value !== "object") return JSON.stringify(value ?? null);
  if (Array.isArray(value)) return `[${value.map(canonicalJson).join(",")}]`;
  const entries = Object.entries(value as Record<string, unknown>)
    .filter(([, v]) => v !== undefined)
    .sort(([a], [b]) => (a < b ? -1 : a > b ? 1 : 0))
    .map(([k, v]) => `${JSON.stringify(k)}:${canonicalJson(v)}`);
  return `{${entries.join(",")}}`;
}
