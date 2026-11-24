import { createHash } from "node:crypto";
import type { z } from "zod";
import type { AuditLog } from "./utils/logging.js";
import type { ConfirmationRegistry } from "./confirm.js";
import type { IdentityResolver } from "./identity.js";
import type { RumuApiClient } from "./client.js";
import type { McpConfig } from "./config.js";
import { ApiError } from "./errors.js";
import type { Edition } from "./types.js";

export interface ToolContext {
  client: RumuApiClient;
  identity: IdentityResolver;
  confirm: ConfirmationRegistry;
  audit: AuditLog;
  config: McpConfig;
}

export interface RumuTool {
  name: string;
  description: string;
  schema: z.ZodRawShape;
  handler: (args: any, ctx: ToolContext) => Promise<unknown>;
}

export function defineTool<TSchema extends z.ZodRawShape>(tool: {
  name: string;
  description: string;
  schema: TSchema;
  handler: (args: z.output<z.ZodObject<TSchema>>, ctx: ToolContext) => Promise<unknown>;
}): RumuTool {
  return tool;
}

/** Edition-scoped API prefix (e.g. /realtor, /enterprise). Throws for admin. */
export async function editionPrefix(ctx: ToolContext): Promise<`/${Edition}`> {
  const identity = await ctx.identity.resolve();
  if (identity.role === "realtor") return "/realtor";
  if (identity.role === "enterprise") return "/enterprise";
  throw new ApiError(403, "forbidden", "This tool requires a Realtor or Enterprise account. Admin accounts use the rumu_admin_* tools.");
}

/** Admin-scoped API prefix. Throws for non-admin. */
export async function adminPrefix(ctx: ToolContext): Promise<"/admin"> {
  const identity = await ctx.identity.resolve();
  if (identity.role !== "admin") {
    throw new ApiError(403, "forbidden", "Admin tools require an admin Rumu account token.");
  }
  return "/admin";
}

export async function enterprisePrefix(ctx: ToolContext): Promise<"/enterprise"> {
  const identity = await ctx.identity.resolve();
  if (identity.role !== "enterprise") {
    throw new ApiError(403, "forbidden", "This tool requires an Enterprise account.");
  }
  return "/enterprise";
}

export function userHash(ctx: ToolContext): Promise<string> {
  return ctx.identity.resolve().then((identity) =>
    createHash("sha256").update(String(identity.id)).digest("hex"),
  );
}

export async function withAudit(
  ctx: ToolContext,
  tool: string,
  args: unknown,
  run: () => Promise<{ affected_id?: number | string; result: unknown }>,
): Promise<unknown> {
  const started = Date.now();
  try {
    const { affected_id, result } = await run();
    ctx.audit.record({ tool, args, status: "success", affected_id, duration_ms: Date.now() - started });
    return result;
  } catch (err) {
    ctx.audit.record({
      tool,
      args,
      status: "error",
      error_code: err instanceof ApiError ? err.code : "unknown",
      duration_ms: Date.now() - started,
    });
    throw err;
  }
}

const DEDUPE_WINDOW_MS = 120_000;
const DEDUPE_MAX_ENTRIES = 500;

interface DedupeEntry {
  at: number;
  result: unknown;
}

/**
 * Short-window write dedupe: identical (user, tool, args) retries within two
 * minutes return the first result instead of creating duplicate side effects.
 */
export class WriteDeduper {
  private readonly seen = new Map<string, DedupeEntry>();

  async run<T>(keyParts: { userId: number; tool: string; args: unknown }, produce: () => Promise<T>): Promise<T> {
    const key = createHash("sha256")
      .update(`${keyParts.userId}|${keyParts.tool}|${JSON.stringify(keyParts.args ?? {})}`)
      .digest("hex");
    const now = Date.now();
    const existing = this.seen.get(key);
    if (existing && now - existing.at <= DEDUPE_WINDOW_MS) {
      return { ...(existing.result as Record<string, unknown>), duplicate_of_previous: true } as T;
    }
    const result = await produce();
    this.seen.set(key, { at: now, result });
    if (this.seen.size > DEDUPE_MAX_ENTRIES) {
      const oldest = this.seen.keys().next().value;
      if (oldest !== undefined) this.seen.delete(oldest);
    }
    return result;
  }
}
