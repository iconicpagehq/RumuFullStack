import { z } from "zod";
import { defineTool, editionPrefix } from "../toolkit.js";
import { IdParam, nonEmpty } from "../schemas/common.js";
import { buildListResult } from "../utils/pagination.js";
import { redactSensitive } from "../utils/redaction.js";

export const inventoryTools = [
  defineTool({
    name: "rumu_inventory_projects",
    description:
      "List inventory projects visible in the authenticated scope. Availability answers must come from the API response — never infer availability. Read-only.",
    schema: {},
    handler: async (_args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const res = await ctx.client.get<{ data?: unknown[] }>(`${prefix}/inventory/projects`);
      const items = Array.isArray(res.data) ? res.data : [];
      return buildListResult(redactSensitive(items) as unknown[]);
    },
  }),

  defineTool({
    name: "rumu_inventory_units_search",
    description:
      "Search inventory units by free text (unit number or project name) and/or project_id. Returns at most 50 units in the assignable scope. Read-only — report availability exactly as returned by the API.",
    schema: {
      query: z.string().trim().min(1).max(100).optional().describe("Free-text search: unit number or project name"),
      project_id: IdParam.optional(),
    },
    handler: async (args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const { items } = await ctx.client.getList(`${prefix}/inventory/units/search`, nonEmpty({
        q: args.query,
        project_id: args.project_id,
      }));
      return buildListResult(redactSensitive(items) as unknown[]);
    },
  }),
];
