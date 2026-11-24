import type { ApiListMeta } from "../types.js";

export interface ListEnvelope<T> {
  success: true;
  summary: string;
  data: {
    items: T[];
    count: number;
    has_more: boolean;
    next_cursor: number | null;
  };
  meta?: Record<string, unknown>;
}

export function buildListResult<T>(
  items: T[],
  meta?: ApiListMeta,
  extraMeta?: Record<string, unknown>,
): ListEnvelope<T> {
  const current = meta?.current_page ?? 1;
  const last = meta?.last_page ?? 1;
  const hasMore = current < last;
  const out: ListEnvelope<T> = {
    success: true,
    summary: items.length === 0 ? "No matching records found." : `${items.length} record(s) returned.`,
    data: {
      items,
      count: items.length,
      has_more: hasMore,
      next_cursor: hasMore ? current + 1 : null,
    },
  };
  const passthrough: Record<string, unknown> = {};
  if (meta?.total !== undefined) passthrough.total = meta.total;
  if (meta?.status_counts) passthrough.status_counts = meta.status_counts;
  if (extraMeta) Object.assign(passthrough, extraMeta);
  if (Object.keys(passthrough).length > 0) out.meta = passthrough;
  return out;
}

export function paginationParams(args: { page?: number; per_page?: number }, defaultPageSize: number): {
  page?: number;
  per_page?: number;
} {
  return {
    page: args.page && args.page > 1 ? args.page : undefined,
    per_page: args.per_page ? Math.min(Math.max(args.per_page, 1), 20) : defaultPageSize,
  };
}

/** Extract a stable affected ID from a write response for confirmation summaries. */
export function pickAffectedId(data: unknown): number | string | undefined {
  if (data === null || typeof data !== "object") return undefined;
  const obj = data as Record<string, unknown>;
  if (typeof obj.id === "number" || typeof obj.id === "string") return obj.id;
  for (const key of ["lead", "task", "note", "visit", "site_visit", "campaign", "call", "target"]) {
    const inner = obj[key];
    if (inner !== null && typeof inner === "object" && typeof (inner as Record<string, unknown>).id === "number") {
      return (inner as Record<string, unknown>).id as number;
    }
  }
  return undefined;
}

/** Keep only shallow scalar fields + small status maps for KPI snapshots. */
export function compactScalars(value: unknown): Record<string, unknown> {
  const out: Record<string, unknown> = {};
  if (value === null || typeof value !== "object" || Array.isArray(value)) return out;
  for (const [key, inner] of Object.entries(value as Record<string, unknown>)) {
    if (typeof inner === "number" || typeof inner === "string" || typeof inner === "boolean") {
      out[key] = inner;
    } else if (
      inner !== null && typeof inner === "object" && !Array.isArray(inner) &&
      (key === "by_status" || key === "status_counts" || key === "counts")
    ) {
      out[key] = inner;
    }
  }
  return out;
}
