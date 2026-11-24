import { z } from "zod";
import { defineTool, enterprisePrefix } from "../toolkit.js";
import { IdParam, nonEmpty } from "../schemas/common.js";
import { redactSensitive } from "../utils/redaction.js";

export const reportTools = [
  defineTool({
    name: "rumu_reports_snapshot",
    description:
      "Enterprise performance snapshot: team/member reports plus optional targets progress for a month (YYYY-MM). Requires an Enterprise account. Realtor accounts should use rumu_dashboard_today instead. Read-only.",
    schema: {
      month: z.string().regex(/^\d{4}-\d{2}$/).optional().describe("Month as YYYY-MM (default: current month)"),
      team_id: IdParam.optional(),
      user_id: IdParam.optional(),
      include_targets: z.boolean().optional().describe("Also fetch targets progress (default true)"),
    },
    handler: async (args, ctx) => {
      const prefix = await enterprisePrefix(ctx);
      const params = nonEmpty({ month: args.month, team_id: args.team_id, user_id: args.user_id });
      const reports = await ctx.client.get<{ data?: unknown }>(`${prefix}/reports`, params);

      const includeTargets = args.include_targets ?? true;
      let targets: unknown;
      if (includeTargets) {
        const t = await ctx.client.get<{ data?: unknown }>(`${prefix}/reports/targets`, params).catch((err: unknown) => {
          const status = (err as { status?: number }).status;
          return status === 403 || status === 404 ? { data: null } : Promise.reject(err);
        });
        targets = t.data;
      }

      return {
        success: true,
        summary: "Enterprise reports snapshot.",
        data: redactSensitive({ reports: reports.data, targets }),
      };
    },
  }),
];
