export type RumuRole = "admin" | "realtor" | "enterprise";

export interface RumuIdentity {
  id: number;
  name: string;
  email: string | null;
  role: RumuRole;
  raw: Record<string, unknown>;
}

export type Edition = "realtor" | "enterprise";

export function editionOf(identity: RumuIdentity): Edition | null {
  if (identity.role === "realtor") return "realtor";
  if (identity.role === "enterprise") return "enterprise";
  return null;
}

export interface ApiListMeta {
  current_page?: number;
  last_page?: number;
  per_page?: number;
  total?: number;
  status_counts?: Record<string, number>;
  [key: string]: unknown;
}

export interface ApiListResponse<T = unknown> {
  data: T[] | T;
  meta?: ApiListMeta;
  message?: string | null;
}
