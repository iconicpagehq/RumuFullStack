import { z } from "zod";
import { defineTool, enterprisePrefix, userHash, withAudit } from "../toolkit.js";
import { IdParam, PageSchema, nonEmpty } from "../schemas/common.js";
import { buildListResult, paginationParams } from "../utils/pagination.js";
import { maskPhonesInList, redactSensitive } from "../utils/redaction.js";
import { ApiError } from "../errors.js";

const TargetMetric = z.enum([
  "leads_assigned", "leads_contacted", "leads_qualified",
  "site_visits_completed", "deals_won", "deal_value",
]);

export const enterpriseTools = [
  defineTool({
    name: "rumu_enterprise_teams_list",
    description: "List teams in the authenticated enterprise (admin sees all; managers/employees see their scope). Read-only.",
    schema: { ...PageSchema },
    handler: async (args, ctx) => {
      const prefix = await enterprisePrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/teams`, paginationParams(args, ctx.config.pageSize));
      return buildListResult(redactSensitive(items) as unknown[], meta);
    },
  }),

  defineTool({
    name: "rumu_enterprise_members_list",
    description:
      "List enterprise members within the caller's allowed scope. Managers and employees only receive their own scope — the backend enforces this. Phones are masked by default. Read-only.",
    schema: {
      ...PageSchema,
      show_phone: z.boolean().optional().describe("Set true to include full phone numbers (default: masked)"),
    },
    handler: async (args, ctx) => {
      const prefix = await enterprisePrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/users`, paginationParams(args, ctx.config.pageSize));
      const visible = args.show_phone ? items : maskPhonesInList(items);
      return buildListResult(redactSensitive(visible) as unknown[], meta);
    },
  }),

  defineTool({
    name: "rumu_enterprise_audit",
    description: "Read enterprise audit logs. Requires the enterprise admin role; other roles are rejected by the backend. Read-only.",
    schema: { ...PageSchema },
    handler: async (args, ctx) => {
      const prefix = await enterprisePrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/audit-logs`, paginationParams(args, ctx.config.pageSize));
      return buildListResult(redactSensitive(items) as unknown[], meta);
    },
  }),

  defineTool({
    name: "rumu_enterprise_targets_get",
    description:
      "Show configured sales targets for a month (YYYY-MM) with actual progress, optionally filtered by team or member. Read-only.",
    schema: {
      month: z.string().regex(/^\d{4}-\d{2}$/).optional().describe("Month as YYYY-MM (default: current month)"),
      team_id: IdParam.optional(),
      user_id: IdParam.optional(),
    },
    handler: async (args, ctx) => {
      const prefix = await enterprisePrefix(ctx);
      const res = await ctx.client.get<{ data?: unknown }>(`${prefix}/targets`, nonEmpty({
        month: args.month,
        team_id: args.team_id,
        user_id: args.user_id,
      }));
      return { success: true, summary: "Sales targets.", data: redactSensitive(res.data ?? res) };
    },
  }),

  defineTool({
    name: "rumu_enterprise_targets_set",
    description:
      "Create or update a sales target. Sensitive configuration write: returns a confirmation token; only rumu_confirm executes it. Requires enterprise admin or manager role.",
    schema: {
      target_id: IdParam.optional().describe("When provided, updates this target instead of creating one"),
      period_month: z.string().regex(/^\d{4}-\d{2}$/).describe("Target month as YYYY-MM"),
      metric: TargetMetric,
      target_value: z.number().min(0),
      team_id: IdParam.optional(),
      user_id: IdParam.optional(),
    },
    handler: async (args, ctx) => {
      const prefix = await enterprisePrefix(ctx);
      const body = nonEmpty({
        period_month: args.period_month,
        metric: args.metric,
        target_value: args.target_value,
        team_id: args.team_id,
        user_id: args.user_id,
      });
      const summary = args.target_id
        ? `Update target ${args.target_id} (${args.metric} for ${args.period_month} = ${args.target_value})`
        : `Create ${args.metric} target for ${args.period_month} = ${args.target_value}${args.team_id ? ` (team ${args.team_id})` : ""}${args.user_id ? ` (user ${args.user_id})` : ""}`;
      return ctx.confirm.prepare({
        tool: "rumu_enterprise_targets_set",
        args: body,
        actionSummary: summary,
        userHash: await userHash(ctx),
        execute: async () =>
          withAudit(ctx, "rumu_enterprise_targets_set", body, async () => {
            const res = args.target_id
              ? await ctx.client.put(`${prefix}/targets/${args.target_id}`, body)
              : await ctx.client.post(`${prefix}/targets`, body);
            const data = redactSensitive(res.data ?? res) as { id?: number };
            return {
              affected_id: args.target_id ?? data?.id,
              result: { success: true, affected_id: args.target_id ?? data?.id, summary: res.message ?? "Target saved.", data },
            };
          }),
      });
    },
  }),

  defineTool({
    name: "rumu_enterprise_distribute",
    description:
      "Distribute up to 100 leads to a team (mode: round_robin or manual with assigned_to). Sensitive bulk operation: call with dry_run=true first to preview, then confirm via rumu_confirm to execute. Resolve lead IDs beforehand with rumu_leads_list. Requires enterprise admin/manager role.",
    schema: {
      lead_ids: z.array(IdParam).min(1).max(100),
      team_id: IdParam,
      mode: z.enum(["round_robin", "manual"]),
      assigned_to: IdParam.optional().describe("Required when mode is manual"),
      dry_run: z.boolean().optional().describe("Default true: validate and preview without executing"),
    },
    handler: async (args, ctx) => {
      const prefix = await enterprisePrefix(ctx);
      if (args.mode === "manual" && !args.assigned_to) {
        throw new ApiError(422, "validation", "assigned_to is required for manual distribution.", {
          assigned_to: ["The assigned_to field is required when mode is manual."],
        });
      }
      const body = nonEmpty({
        lead_ids: args.lead_ids,
        team_id: args.team_id,
        mode: args.mode,
        assigned_to: args.assigned_to,
      });

      if (args.dry_run ?? true) {
        await ctx.client.get(`${prefix}/teams/${args.team_id}`).catch((err: unknown) => {
          if (err instanceof ApiError && (err.status === 404 || err.status === 403)) {
            throw new ApiError(422, "validation", "Selected team was not found in your enterprise scope.", {
              team_id: ["Select a team from this enterprise."],
            });
          }
          throw err;
        });
        return {
          success: true,
          summary: "Dry run only — nothing was distributed.",
          data: {
            records_selected: args.lead_ids.length,
            target_team: args.team_id,
            distribution_method: args.mode,
            estimated_assignments: args.mode === "manual" ? 1 : args.lead_ids.length,
            exceptions: [],
            next_step: "Call rumu_enterprise_distribute again with dry_run=false to obtain a confirmation token, then rumu_confirm to execute.",
          },
        };
      }

      const summary = `Distribute ${args.lead_ids.length} lead(s) to team ${args.team_id} using ${args.mode}`;
      return ctx.confirm.prepare({
        tool: "rumu_enterprise_distribute",
        args: body,
        actionSummary: summary,
        userHash: await userHash(ctx),
        execute: async () =>
          withAudit(ctx, "rumu_enterprise_distribute", body, async () => {
            const res = await ctx.client.post(`${prefix}/leads/distribute`, body, { retryableWrite: false });
            const data = redactSensitive(res.data ?? res) as { assigned_count?: number };
            return {
              affected_id: undefined,
              result: {
                success: true,
                summary: res.message ?? `${data?.assigned_count ?? args.lead_ids.length} lead(s) distributed.`,
                data,
              },
            };
          }),
      });
    },
  }),
];
