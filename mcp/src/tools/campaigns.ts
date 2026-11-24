import { z } from "zod";
import { defineTool, editionPrefix, withAudit } from "../toolkit.js";
import { nonEmpty } from "../schemas/common.js";
import { buildListResult } from "../utils/pagination.js";
import { redactSensitive } from "../utils/redaction.js";

export const campaignTools = [
  defineTool({
    name: "rumu_campaigns_list",
    description: "List campaigns visible to the authenticated account with lead counts. Read-only.",
    schema: {},
    handler: async (_args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const res = await ctx.client.get<{ data?: unknown[] }>(`${prefix}/campaigns`);
      const items = Array.isArray(res.data) ? res.data : [];
      return buildListResult(redactSensitive(items) as unknown[]);
    },
  }),

  defineTool({
    name: "rumu_campaigns_create",
    description:
      "Create a campaign (name required; status defaults to active). Plan limits are enforced by the backend. Safe write — no confirmation.",
    schema: {
      name: z.string().trim().min(1).max(255),
      description: z.string().trim().max(2000).optional(),
      status: z.enum(["active", "paused", "completed"]).optional().default("active"),
      idempotency_key: z.string().trim().min(8).max(80).optional(),
    },
    handler: async (rawArgs, ctx) => {
      const args = rawArgs as Record<string, unknown>;
      return withAudit(ctx, "rumu_campaigns_create", args, async () => {
        const prefix = await editionPrefix(ctx);
        const body = nonEmpty({ name: args.name, description: args.description, status: args.status ?? "active" });
        const res = await ctx.client.post(`${prefix}/campaigns`, body, {
          idempotencyKey: rawArgs.idempotency_key,
          retryableWrite: Boolean(rawArgs.idempotency_key),
        });
        const data = redactSensitive(res.data ?? res);
        return {
          affected_id: (data as { id?: number })?.id,
          result: { success: true, affected_id: (data as { id?: number })?.id, summary: res.message ?? "Campaign created.", data },
        };
      });
    },
  }),
];
