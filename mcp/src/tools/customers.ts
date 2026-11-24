import { z } from "zod";
import { defineTool, editionPrefix } from "../toolkit.js";
import { PageSchema, SearchSchema, nonEmpty } from "../schemas/common.js";
import { buildListResult, paginationParams } from "../utils/pagination.js";
import { maskPhonesInList, redactSensitive } from "../utils/redaction.js";

export const customerTools = [
  defineTool({
    name: "rumu_customers_list",
    description:
      "List customer records within the authenticated scope. Phones are masked by default. Read-only — unrelated customer records are never exposed.",
    schema: {
      ...SearchSchema,
      ...PageSchema,
      show_phone: z.boolean().optional().describe("Set true to include full phone numbers (default: masked)"),
    },
    handler: async (args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/customers`, nonEmpty({
        search: args.search,
        ...paginationParams(args, ctx.config.pageSize),
      }));
      const visible = args.show_phone ? items : maskPhonesInList(items);
      return buildListResult(redactSensitive(visible) as unknown[], meta);
    },
  }),
];
