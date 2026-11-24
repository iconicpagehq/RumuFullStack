import { describe, expect, it, vi } from "vitest";
import { RumuApiClient, canonicalJson, hashArguments, type FetchLike } from "../src/client.js";
import { ApiError } from "../src/errors.js";

function jsonResponse(status: number, body: unknown, headers: Record<string, string> = {}): Response {
  return new Response(JSON.stringify(body), {
    status,
    headers: { "Content-Type": "application/json", ...headers },
  });
}

function makeClient(fetchFn: FetchLike, overrides: Partial<ConstructorParameters<typeof RumuApiClient>[0]> = {}) {
  return new RumuApiClient({
    baseUrl: "https://api.test/api/v1",
    token: "test-token",
    timeoutMs: 5000,
    maxRetries: 2,
    minIntervalMs: 0,
    fetchFn,
    sleep: () => Promise.resolve(),
    ...overrides,
  });
}

describe("RumuApiClient", () => {
  it("builds URLs with query params and auth headers", async () => {
    const calls: { url: string; init?: RequestInit }[] = [];
    const client = makeClient(async (url, init) => {
      calls.push({ url, init });
      return jsonResponse(200, { success: true, data: [] });
    });

    await client.get("/realtor/leads", { status: "new", empty: "", skip: undefined, page: 2 });

    expect(calls).toHaveLength(1);
    const url = new URL(calls[0]!.url);
    expect(url.origin + url.pathname).toBe("https://api.test/api/v1/realtor/leads");
    expect(url.searchParams.get("status")).toBe("new");
    expect(url.searchParams.get("page")).toBe("2");
    expect(url.searchParams.has("empty")).toBe(false);
    expect(url.searchParams.has("skip")).toBe(false);

    const headers = calls[0]!.init!.headers as Record<string, string>;
    expect(headers.Authorization).toBe("Bearer test-token");
    expect(headers.Accept).toBe("application/json");
  });

  it("sends JSON bodies and idempotency keys on writes", async () => {
    const calls: { init?: RequestInit }[] = [];
    const client = makeClient(async (_url, init) => {
      calls.push({ init });
      return jsonResponse(201, { success: true, data: { id: 7 } });
    });

    await client.post("/realtor/tasks", { title: "Call Riya" }, { idempotencyKey: "key-12345678" });

    const init = calls[0]!.init!;
    expect(init.method).toBe("POST");
    expect((init.headers as Record<string, string>)["Content-Type"]).toBe("application/json");
    expect((init.headers as Record<string, string>)["X-Idempotency-Key"]).toBe("key-12345678");
    expect(JSON.parse(init.body as string)).toEqual({ title: "Call Riya" });
  });

  it.each([
    [401, "unauthorized"],
    [403, "forbidden"],
    [404, "not_found"],
    [422, "validation"],
    [429, "rate_limited"],
    [500, "server"],
  ])("maps HTTP %i to error code %s", async (status, code) => {
    const client = makeClient(async () => jsonResponse(status, { message: "boom" }, status === 429 ? { "retry-after": "3" } : {}));
    const err = await client.get("/x").catch((e: unknown) => e);
    expect(err).toBeInstanceOf(ApiError);
    expect((err as ApiError).code).toBe(code);
    if (status === 429) expect((err as ApiError).retryAfterSeconds).toBe(3);
    if (status === 422) expect((err as ApiError).retryable).toBe(false);
  });

  it("returns field errors from 422 responses", async () => {
    const client = makeClient(async () =>
      jsonResponse(422, { message: "Validation failed", errors: { due_at: ["The due_at field is required."] } }),
    );
    const err = (await client.post("/realtor/tasks", {}).catch((e: unknown) => e)) as ApiError;
    expect(err.fieldErrors).toEqual({ due_at: ["The due_at field is required."] });
  });

  it("retries 429 with retry-after then succeeds", async () => {
    let attempts = 0;
    const sleeps: number[] = [];
    const client = makeClient(
      async () => {
        attempts += 1;
        return attempts < 3
          ? jsonResponse(429, { message: "slow down" }, { "retry-after": "1" })
          : jsonResponse(200, { success: true, data: { ok: true } });
      },
      { sleep: (ms) => { sleeps.push(ms); return Promise.resolve(); } },
    );
    const res = await client.get("/realtor/leads");
    expect(attempts).toBe(3);
    expect(sleeps).toEqual([1000, 1000]);
    expect((res as { data: { ok: boolean } }).data.ok).toBe(true);
  });

  it("does not retry 403", async () => {
    let attempts = 0;
    const client = makeClient(async () => {
      attempts += 1;
      return jsonResponse(403, { message: "nope" });
    });
    await expect(client.get("/admin/users")).rejects.toBeInstanceOf(ApiError);
    expect(attempts).toBe(1);
  });

  it("retries network failures up to maxRetries for GET", async () => {
    let attempts = 0;
    const client = makeClient(async () => {
      attempts += 1;
      if (attempts < 3) throw new TypeError("fetch failed");
      return jsonResponse(200, { success: true });
    });
    await client.get("/realtor/dashboard");
    expect(attempts).toBe(3);
  });

  it("canonicalJson and hashArguments are stable regardless of key order", () => {
    expect(canonicalJson({ b: 2, a: 1 })).toBe(canonicalJson({ a: 1, b: 2 }));
    expect(hashArguments({ b: 2, a: 1 })).toBe(hashArguments({ a: 1, b: 2 }));
  });
});
