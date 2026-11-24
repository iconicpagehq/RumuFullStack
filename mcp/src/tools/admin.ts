import { z } from "zod";
import { adminPrefix, defineTool, userHash, withAudit } from "../toolkit.js";
import { IdParam, PageSchema, nonEmpty } from "../schemas/common.js";
import { buildListResult, paginationParams } from "../utils/pagination.js";
import { maskPhonesInList, redactSensitive } from "../utils/redaction.js";

export const adminTools = [
  defineTool({
    name: "rumu_admin_users_list",
    description: "List platform users (admin only). Phones are masked by default. Read-only.",
    schema: {
      role: z.enum(["admin", "realtor", "enterprise"]).optional(),
      ...PageSchema,
      show_phone: z.boolean().optional().describe("Set true to include full phone numbers (default: masked)"),
    },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/users`, nonEmpty({
        role: args.role,
        ...paginationParams(args, ctx.config.pageSize),
      }));
      const visible = args.show_phone ? items : maskPhonesInList(items);
      return buildListResult(redactSensitive(visible) as unknown[], meta);
    },
  }),

  defineTool({
    name: "rumu_admin_enterprises_list",
    description: "List enterprises on the platform with subscription status (admin only). Read-only.",
    schema: { ...PageSchema },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/enterprises`, paginationParams(args, ctx.config.pageSize));
      return buildListResult(redactSensitive(items) as unknown[], meta);
    },
  }),

  defineTool({
    name: "rumu_admin_seat_requests",
    description: "List pending/resolved enterprise seat requests (admin only). Read-only.",
    schema: {
      status: z.enum(["pending", "approved", "rejected"]).optional(),
      ...PageSchema,
    },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/seat-requests`, nonEmpty({
        status: args.status,
        ...paginationParams(args, ctx.config.pageSize),
      }));
      return buildListResult(redactSensitive(items) as unknown[], meta);
    },
  }),

  defineTool({
    name: "rumu_admin_billing_subscriptions",
    description: "List platform billing subscriptions (admin only). Read-only; no payment execution is ever performed through MCP.",
    schema: {
      status: z.string().trim().max(30).optional(),
      ...PageSchema,
    },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/billing/subscriptions`, nonEmpty({
        status: args.status,
        ...paginationParams(args, ctx.config.pageSize),
      }));
      return buildListResult(redactSensitive(items) as unknown[], meta);
    },
  }),

  defineTool({
    name: "rumu_admin_plans_list",
    description: "List platform pricing plans (admin only). Read-only.",
    schema: {},
    handler: async (_args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const res = await ctx.client.get<{ data?: unknown[] }>(`${prefix}/plans`);
      const items = Array.isArray(res.data) ? res.data : [];
      return buildListResult(redactSensitive(items) as unknown[]);
    },
  }),

  defineTool({
    name: "rumu_admin_broadcast_preview",
    description:
      "Preview an admin broadcast (audience sizing) before sending. Admin only. Always preview before rumu_admin_broadcast_send.",
    schema: {
      title: z.string().trim().min(1).max(255),
      body: z.string().trim().min(1).max(5000),
      audience: z.record(z.string(), z.unknown()).optional().describe("Audience filter as accepted by the backend"),
    },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const res = await ctx.client.post(`${prefix}/broadcasts/preview`, nonEmpty({
        title: args.title,
        body: args.body,
        audience: args.audience,
      }));
      return {
        success: true,
        summary: "Broadcast preview (not sent).",
        data: redactSensitive(res.data ?? res),
      };
    },
  }),

  defineTool({
    name: "rumu_admin_broadcast_send",
    description:
      "Send an admin broadcast to many users. Sensitive: never executes immediately — returns a confirmation token; only rumu_confirm sends it. Always run rumu_admin_broadcast_preview first. Admin only.",
    schema: {
      title: z.string().trim().min(1).max(255),
      body: z.string().trim().min(1).max(5000),
      audience: z.record(z.string(), z.unknown()).optional(),
    },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const body = nonEmpty({ title: args.title, body: args.body, audience: args.audience });
      return ctx.confirm.prepare({
        tool: "rumu_admin_broadcast_send",
        args: { title: args.title, body: args.body, audience: args.audience },
        actionSummary: `Send broadcast "${args.title}" to the configured audience`,
        userHash: await userHash(ctx),
        execute: async () =>
          withAudit(ctx, "rumu_admin_broadcast_send", body, async () => {
            const res = await ctx.client.post(`${prefix}/broadcasts`, body, { retryableWrite: false });
            const data = redactSensitive(res.data ?? res) as { id?: number };
            return {
              affected_id: data?.id,
              result: { success: true, affected_id: data?.id, summary: res.message ?? "Broadcast sent.", data },
            };
          }),
      });
    },
  }),

  defineTool({
    name: "rumu_admin_enterprise_suspend",
    description:
      "Suspend or resume an enterprise (action=suspend|resume). Sensitive: returns a confirmation token; only rumu_confirm executes it. Admin only; the action is audit-logged by the backend.",
    schema: {
      enterprise_id: IdParam,
      action: z.enum(["suspend", "resume"]),
      reason: z.string().trim().max(500).optional(),
    },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const summary = `${args.action === "suspend" ? "Suspend" : "Resume"} enterprise ${args.enterprise_id}${args.reason ? ` (${args.reason})` : ""}`;
      return ctx.confirm.prepare({
        tool: "rumu_admin_enterprise_suspend",
        args: { enterprise_id: args.enterprise_id, action: args.action, reason: args.reason },
        actionSummary: summary,
        userHash: await userHash(ctx),
        execute: async () =>
          withAudit(ctx, "rumu_admin_enterprise_suspend", args, async () => {
            const res = await ctx.client.post(`${prefix}/enterprises/${args.enterprise_id}/${args.action}`, {}, { retryableWrite: false });
            return {
              affected_id: args.enterprise_id,
              result: { success: true, affected_id: args.enterprise_id, summary: res.message ?? `Enterprise ${args.action}ed.`, data: redactSensitive(res.data ?? res) },
            };
          }),
      });
    },
  }),

  defineTool({
    name: "rumu_admin_plan_upsert",
    description:
      "Create or update a platform pricing plan (plan_id provided = update via PUT, otherwise create via POST). Sensitive: returns a confirmation token; only rumu_confirm executes it. Admin only.",
    schema: {
      plan_id: IdParam.optional(),
      fields: z.record(z.string(), z.unknown()).describe("Plan fields as accepted by the backend (name, price, limits, etc.)"),
    },
    handler: async (args, ctx) => {
      const prefix = await adminPrefix(ctx);
      const body = args.fields;
      const summary = args.plan_id
        ? `Update plan ${args.plan_id} (${Object.keys(body).join(", ") || "no fields"})`
        : `Create plan (${Object.keys(body).join(", ") || "no fields"})`;
      return ctx.confirm.prepare({
        tool: "rumu_admin_plan_upsert",
        args: { plan_id: args.plan_id, fields: Object.keys(body) },
        actionSummary: summary,
        userHash: await userHash(ctx),
        execute: async () =>
          withAudit(ctx, "rumu_admin_plan_upsert", args, async () => {
            const res = args.plan_id
              ? await ctx.client.put(`${prefix}/plans/${args.plan_id}`, body, { retryableWrite: false })
              : await ctx.client.post(`${prefix}/plans`, body, { retryableWrite: false });
            const data = redactSensitive(res.data ?? res) as { id?: number };
            return {
              affected_id: args.plan_id ?? data?.id,
              result: { success: true, affected_id: args.plan_id ?? data?.id, summary: res.message ?? "Plan saved.", data },
            };
          }),
      });
    },
  }),
];
