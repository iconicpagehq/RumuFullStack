import { z } from "zod";
import { defineTool, userHash } from "../toolkit.js";
import { ApiError } from "../errors.js";

export const confirmationTools = [
  defineTool({
    name: "rumu_confirm",
    description:
      "Execute a pending sensitive action using its confirmation token. Tokens are single-use, expire after 5 minutes, and are bound to this user context. Only call this when the user explicitly confirms the exact action summary.",
    schema: {
      token: z.string().trim().min(16).max(128).describe("The confirm_token returned by a sensitive tool"),
    },
    handler: async (args, ctx) => {
      try {
        const result = await ctx.confirm.confirm(args.token, await userHash(ctx));
        return result;
      } catch (err) {
        if (err instanceof Error) {
          throw new ApiError(400, "validation", err.message);
        }
        throw err;
      }
    },
  }),

  defineTool({
    name: "rumu_cancel",
    description: "Cancel a pending sensitive action using its confirmation token. The token becomes invalid immediately.",
    schema: {
      token: z.string().trim().min(16).max(128).describe("The confirm_token returned by a sensitive tool"),
    },
    handler: async (args, ctx) => {
      try {
        return ctx.confirm.cancel(args.token, await userHash(ctx));
      } catch (err) {
        if (err instanceof Error) {
          throw new ApiError(400, "validation", err.message);
        }
        throw err;
      }
    },
  }),
];
