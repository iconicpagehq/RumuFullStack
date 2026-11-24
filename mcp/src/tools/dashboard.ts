import { defineTool, editionPrefix } from "../toolkit.js";
import { compactScalars } from "../utils/pagination.js";
import { redactSensitive } from "../utils/redaction.js";

export const dashboardTools = [
  defineTool({
    name: "rumu_dashboard_today",
    description:
      "Compact daily CRM snapshot for the authenticated Realtor or Enterprise account: lead/campaign status counts, overdue tasks, upcoming visits, follow-ups, and call KPIs. Read-only. Requires a Realtor or Enterprise account.",
    schema: {},
    handler: async (_args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const res = await ctx.client.get<{ data?: Record<string, unknown>; message?: string }>(`${prefix}/dashboard`);
      const data = (res.data ?? res) as Record<string, unknown>;

      const kpis: Record<string, unknown> = {
        leads_by_status: data.lead_status ?? data.leads_by_status,
        campaigns_by_status: data.campaign_status ?? data.campaigns_by_status,
        overdue_tasks: data.overdue_tasks_count,
        kpis: data.kpis ? compactScalars(data.kpis) : undefined,
        calls: data.calls ? compactScalars(redactSensitive(data.calls)) : undefined,
      };
      for (const key of Object.keys(kpis)) {
        if (kpis[key] === undefined) delete kpis[key];
      }

      const alerts: string[] = [];
      const expiry = data.plan_expiry_warning;
      if (expiry && typeof expiry === "object" && (expiry as Record<string, unknown>).warning) {
        alerts.push(String((expiry as Record<string, unknown>).message ?? "Plan expiry warning"));
      }

      return {
        success: true,
        summary: "Daily CRM snapshot.",
        data: redactSensitive({
          date: new Date().toISOString().slice(0, 10),
          edition: prefix.slice(1),
          kpis,
          alerts,
        }),
      };
    },
  }),
];
