import { z } from "zod";
import { defineTool, editionPrefix, withAudit } from "../toolkit.js";
import {
  CallDisposition,
  CallStatus,
  IdParam,
  PageSchema,
  nonEmpty,
} from "../schemas/common.js";
import { buildListResult, compactScalars, paginationParams } from "../utils/pagination.js";
import { maskPhonesInList, redactSensitive } from "../utils/redaction.js";

export const callTools = [
  defineTool({
    name: "rumu_calls_summary",
    description:
      "Compact call summary for the authenticated scope (totals, by-status counts, recent calls, recording availability). Calls are placed only from the Rumu Android app — this tool reads metadata, it never places calls or streams recordings. Read-only.",
    schema: {},
    handler: async (_args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const res = await ctx.client.get<{ data?: Record<string, unknown> }>(`${prefix}/calls/summary`);
      const data = redactSensitive(res.data ?? {}) as Record<string, unknown>;
      const compact: Record<string, unknown> = compactScalars(data);
      if (Array.isArray(data.recent)) {
        compact.recent = maskPhonesInList(redactSensitive(data.recent) as unknown[]).slice(0, 5);
      }
      return {
        success: true,
        summary: "Call summary for the current scope.",
        data: compact,
      };
    },
  }),

  defineTool({
    name: "rumu_calls_list",
    description:
      "List call logs in the authenticated scope, filterable by status (initiating|ringing|in_progress|completed|no_answer|failed|canceled). Phones are masked by default. Read-only; no audio or playback URLs are returned.",
    schema: {
      status: CallStatus.optional(),
      ...PageSchema,
      show_phone: z.boolean().optional().describe("Set true to include full phone numbers (default: masked)"),
    },
    handler: async (args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/calls`, nonEmpty({
        status: args.status,
        ...paginationParams(args, ctx.config.pageSize),
      }));
      const visible = args.show_phone ? items : maskPhonesInList(items);
      return buildListResult(redactSensitive(visible) as unknown[], meta, {
        status_counts: meta?.status_counts,
      });
    },
  }),

  defineTool({
    name: "rumu_calls_get",
    description:
      "Call detail: metadata, lead, timestamps, duration, disposition, and recording availability. Never returns raw audio or playback URLs — playback stays inside the authenticated Rumu web/app players.",
    schema: { call_id: IdParam },
    handler: async (args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const res = await ctx.client.get<{ data?: unknown }>(`${prefix}/calls/${args.call_id}`);
      return { success: true, summary: "Call detail.", data: redactSensitive(res.data ?? res) };
    },
  }),

  defineTool({
    name: "rumu_calls_set_disposition",
    description:
      "Set a call's outcome disposition (connected|not_connected|busy|wrong_number|call_back|interested|not_interested|converted|other) with optional notes. Safe write — no confirmation; backend authorization still applies.",
    schema: {
      call_id: IdParam,
      disposition: CallDisposition,
      notes: z.string().trim().max(2000).optional(),
      idempotency_key: z.string().trim().min(8).max(80).optional(),
    },
    handler: async (rawArgs, ctx) => {
      const args = rawArgs as Record<string, unknown>;
      return withAudit(ctx, "rumu_calls_set_disposition", args, async () => {
        const prefix = await editionPrefix(ctx);
        const body = nonEmpty({ disposition: args.disposition, notes: args.notes });
        const res = await ctx.client.patch(`${prefix}/calls/${args.call_id}/disposition`, body, {
          idempotencyKey: rawArgs.idempotency_key,
        });
        return {
          affected_id: args.call_id as number,
          result: {
            success: true,
            affected_id: args.call_id,
            summary: res.message ?? "Call outcome saved.",
            data: redactSensitive(res.data ?? res),
          },
        };
      });
    },
  }),
];
