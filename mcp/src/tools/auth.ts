import { z } from "zod";
import { defineTool } from "../toolkit.js";
import { nonEmpty } from "../schemas/common.js";

export const authTools = [
  defineTool({
    name: "rumu_auth_me",
    description:
      "Identify the current Rumu account (id, name, role: realtor|enterprise|admin) and resolve which edition-scoped tools are usable. Call this first when unsure of the account context. Read-only.",
    schema: {
      refresh: z.boolean().optional().describe("Force re-fetch identity from the API"),
    },
    handler: async (args, ctx) => {
      const identity = await ctx.identity.resolve(args.refresh);
      return {
        success: true,
        summary: `Authenticated as ${identity.name} (${identity.role}).`,
        data: nonEmpty({
          id: identity.id,
          name: identity.name,
          email: identity.email,
          role: identity.role,
        }),
      };
    },
  }),
];
