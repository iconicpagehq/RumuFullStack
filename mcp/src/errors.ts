export type ApiErrorCode =
  | "network"
  | "timeout"
  | "unauthorized"
  | "forbidden"
  | "not_found"
  | "validation"
  | "rate_limited"
  | "server"
  | "unknown";

export class ApiError extends Error {
  constructor(
    public readonly status: number,
    public readonly code: ApiErrorCode,
    message: string,
    public readonly fieldErrors?: Record<string, string[]>,
    public readonly retryable: boolean = false,
    public readonly retryAfterSeconds?: number,
  ) {
    super(message);
    this.name = "ApiError";
  }

  toResult(): Record<string, unknown> {
    const result: Record<string, unknown> = {
      success: false,
      error: this.code,
      message: this.message,
      retryable: this.retryable,
    };
    if (this.fieldErrors) result.field_errors = this.fieldErrors;
    if (this.retryAfterSeconds != null) result.retry_after_seconds = this.retryAfterSeconds;
    return result;
  }
}

export function mapStatusToError(
  status: number,
  body: { message?: unknown; errors?: unknown } | null,
  headers: Headers,
): ApiError {
  const message = typeof body?.message === "string" && body.message ? body.message : defaultMessage(status);
  const fieldErrors = normalizeFieldErrors(body?.errors);

  switch (status) {
    case 401:
      return new ApiError(status, "unauthorized", "Rumu authentication failed or the token expired. Reconnect with a valid token.", undefined, false);
    case 403:
      return new ApiError(status, "forbidden", "Your Rumu account does not have permission for this operation.", undefined, false);
    case 404:
      return new ApiError(status, "not_found", "Not found or outside your permitted scope.", undefined, false);
    case 422:
      return new ApiError(status, "validation", "The request failed validation.", fieldErrors ?? undefined, false);
    case 429: {
      const retryAfter = Number.parseInt(headers.get("retry-after") ?? "", 10);
      return new ApiError(
        status,
        "rate_limited",
        "Rate limited by the Rumu API. Wait before retrying.",
        undefined,
        true,
        Number.isFinite(retryAfter) ? retryAfter : 5,
      );
    }
    default:
      if (status >= 500) {
        return new ApiError(status, "server", "The Rumu API is temporarily unavailable. Try again shortly.", undefined, true);
      }
      return new ApiError(status, "unknown", message, undefined, false);
  }
}

function defaultMessage(status: number): string {
  if (status === 401) return "Authentication failed.";
  if (status === 403) return "Forbidden.";
  if (status === 404) return "Not found or outside your permitted scope.";
  if (status === 422) return "Validation failed.";
  if (status === 429) return "Rate limited.";
  if (status >= 500) return "Temporary service error.";
  return `Request failed with status ${status}.`;
}

function normalizeFieldErrors(errors: unknown): Record<string, string[]> | null {
  if (!errors || typeof errors !== "object") return null;
  const out: Record<string, string[]> = {};
  for (const [key, value] of Object.entries(errors as Record<string, unknown>)) {
    if (Array.isArray(value)) {
      out[key] = value.map((v) => String(v));
    } else if (typeof value === "string") {
      out[key] = [value];
    }
  }
  return Object.keys(out).length > 0 ? out : null;
}
