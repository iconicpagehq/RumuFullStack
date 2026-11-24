import { z } from "zod";
import { defineTool, editionPrefix, withAudit } from "../toolkit.js";
import { IdParam, PageSchema, VisitStatus, nonEmpty } from "../schemas/common.js";
import { buildListResult, paginationParams } from "../utils/pagination.js";
import { redactSensitive } from "../utils/redaction.js";

export const siteVisitTools = [
  defineTool({
    name: "rumu_visits_list",
    description:
      "List site visits (scheduled, completed, cancelled, no_show) for the authenticated scope, optionally filtered by lead. Read-only.",
    schema: {
      status: VisitStatus.optional(),
      lead_id: IdParam.optional(),
      ...PageSchema,
    },
    handler: async (args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/site-visits`, nonEmpty({
        status: args.status,
        lead_id: args.lead_id,
        ...paginationParams(args, ctx.config.pageSize),
      }));
      return buildListResult(redactSensitive(items) as unknown[], meta, {
        upcoming_count: meta?.["upcoming_count"],
      });
    },
  }),

  defineTool({
    name: "rumu_visits_schedule",
    description:
      "Schedule a site visit for a lead at an ISO datetime (e.g. 2026-11-08T11:00:00+05:30). Enterprise additionally requires inventory_project_id and may set assigned_to / notes. Safe write — no confirmation. If a required field is missing the backend returns field_errors.",
    schema: {
      lead_id: IdParam,
      scheduled_at: z.string().datetime({ offset: true }),
      inventory_project_id: IdParam.optional().describe("Required for Enterprise accounts"),
      inventory_unit_id: IdParam.optional(),
      assigned_to: IdParam.optional().describe("Enterprise only"),
      team_id: IdParam.optional().describe("Enterprise only"),
      notes: z.string().trim().max(5000).optional().describe("Enterprise only"),
      status: VisitStatus.optional(),
      idempotency_key: z.string().trim().min(8).max(80).optional(),
    },
    handler: async (rawArgs, ctx) => {
      const args = rawArgs as Record<string, unknown>;
      return withAudit(ctx, "rumu_visits_schedule", args, async () => {
        const prefix = await editionPrefix(ctx);
        const body = nonEmpty(args);
        delete body.idempotency_key;
        const res = await ctx.client.post(`${prefix}/site-visits`, body, {
          idempotencyKey: rawArgs.idempotency_key,
          retryableWrite: Boolean(rawArgs.idempotency_key),
        });
        const data = redactSensitive(res.data ?? res) as { id?: number };
        return {
          affected_id: data?.id,
          result: { success: true, affected_id: data?.id, summary: res.message ?? "Site visit scheduled.", data },
        };
      });
    },
  }),

  defineTool({
    name: "rumu_visits_close",
    description:
      "Update a site visit's status (completed, cancelled, no_show, scheduled). Safe write — no confirmation. The backend decides whether the transition is legal.",
    schema: {
      visit_id: IdParam,
      status: VisitStatus,
      outcome: z.string().trim().max(1000).optional(),
      feedback: z.string().trim().max(2000).optional(),
      customer_attended: z.boolean().optional(),
      next_action: z.string().trim().max(1000).optional(),
      cancellation_reason: z.string().trim().max(1000).optional(),
      idempotency_key: z.string().trim().min(8).max(80).optional(),
    },
    handler: async (rawArgs, ctx) => {
      const args = rawArgs as Record<string, unknown>;
      return withAudit(ctx, "rumu_visits_close", args, async () => {
        const prefix = await editionPrefix(ctx);
        const body = nonEmpty(args);
        delete body.visit_id;
        delete body.idempotency_key;
        const res = await ctx.client.patch(`${prefix}/site-visits/${args.visit_id}/status`, body, {
          idempotencyKey: rawArgs.idempotency_key,
        });
        return {
          affected_id: args.visit_id as number,
          result: {
            success: true,
            affected_id: args.visit_id,
            summary: res.message ?? `Site visit marked ${args.status}.`,
            data: redactSensitive(res.data ?? res),
          },
        };
      });
    },
  }),
];
