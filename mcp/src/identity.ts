import type { RumuApiClient } from "./client.js";
import { ApiError } from "./errors.js";
import type { RumuIdentity } from "./types.js";

export class IdentityResolver {
  private cached: RumuIdentity | null = null;

  constructor(private readonly client: RumuApiClient) {}

  async resolve(forceRefresh = false): Promise<RumuIdentity> {
    if (this.cached && !forceRefresh) return this.cached;
    let res: { data?: unknown };
    try {
      res = await this.client.get<{ data?: unknown }>("/auth/me");
    } catch (err) {
      if (err instanceof ApiError) throw err;
      throw new ApiError(0, "network", "Could not verify Rumu identity.", undefined, true);
    }
    const raw = (res.data ?? res) as Record<string, unknown>;
    const role = normalizeRole(raw.role);
    if (typeof raw.id !== "number" || !role) {
      throw new ApiError(0, "unknown", "The Rumu /auth/me response did not include a usable id and role.");
    }
    this.cached = {
      id: raw.id,
      name: typeof raw.name === "string" ? raw.name : "Unknown",
      email: typeof raw.email === "string" ? raw.email : null,
      role,
      raw,
    };
    return this.cached;
  }
}

function normalizeRole(role: unknown): RumuIdentity["role"] | null {
  if (role === "admin" || role === "realtor" || role === "enterprise") return role;
  return null;
}
