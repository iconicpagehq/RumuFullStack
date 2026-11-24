import { z } from "zod";
import { defineTool, editionPrefix, withAudit } from "../toolkit.js";
import { IdParam, PageSchema, Priority, SearchSchema, TaskStatus, nonEmpty } from "../schemas/common.js";
import { buildListResult, paginationParams, pickAffectedId } from "../utils/pagination.js";
import { redactSensitive } from "../utils/redaction.js";

export const taskTools = [
  defineTool({
    name: "rumu_tasks_list",
    description:
      "List tasks for the authenticated scope. Filters: status (pending|in_progress|completed|cancelled), overdue only, lead, free-text search. Read-only.",
    schema: {
      status: TaskStatus.optional(),
      overdue: z.boolean().optional().describe("Only incomplete tasks past their due date"),
      lead_id: IdParam.optional(),
      ...SearchSchema,
      ...PageSchema,
    },
    handler: async (args, ctx) => {
      const prefix = await editionPrefix(ctx);
      const { items, meta } = await ctx.client.getList(`${prefix}/tasks`, nonEmpty({
        status: args.status,
        overdue: args.overdue ? 1 : undefined,
        lead_id: args.lead_id,
        search: args.search,
        ...paginationParams(args, ctx.config.pageSize),
      }));
      return buildListResult(redactSensitive(items) as unknown[], meta, {
        overdue_count: meta?.["overdue_count"],
      });
    },
  }),

  defineTool({
    name: "rumu_tasks_create",
    description:
      "Create a task (title required). Optional: description, priority (low|medium|high|urgent, default medium), due_at ISO datetime, linked lead_id, status. Enterprise may also set assigned_to / team_id. Safe write — no confirmation.",
    schema: {
      title: z.string().trim().min(1).max(255),
      description: z.string().trim().max(5000).optional(),
      priority: Priority.optional(),
      due_at: z.string().datetime({ offset: true }).optional(),
      lead_id: IdParam.optional(),
      status: TaskStatus.optional(),
      assigned_to: IdParam.optional().describe("Enterprise only"),
      team_id: IdParam.optional().describe("Enterprise only"),
      idempotency_key: z.string().trim().min(8).max(80).optional(),
    },
    handler: async (rawArgs, ctx) => {
      const args = rawArgs as Record<string, unknown>;
      return withAudit(ctx, "rumu_tasks_create", args, async () => {
        const prefix = await editionPrefix(ctx);
        const body = nonEmpty(args);
        delete body.idempotency_key;
        const res = await ctx.client.post(`${prefix}/tasks`, body, {
          idempotencyKey: rawArgs.idempotency_key,
          retryableWrite: Boolean(rawArgs.idempotency_key),
        });
        const data = redactSensitive(res.data ?? res);
        const affected_id = pickAffectedId(data);
        return {
          affected_id,
          result: { success: true, affected_id, summary: res.message ?? "Task created.", data },
        };
      });
    },
  }),

  defineTool({
    name: "rumu_tasks_update_status",
    description:
      "Update a task's status (pending|in_progress|completed|cancelled) with optional completion notes. Safe write — no confirmation.",
    schema: {
      task_id: IdParam,
      status: TaskStatus,
      completion_notes: z.string().trim().max(2000).optional(),
      idempotency_key: z.string().trim().min(8).max(80).optional(),
    },
    handler: async (rawArgs, ctx) => {
      const args = rawArgs as Record<string, unknown>;
      return withAudit(ctx, "rumu_tasks_update_status", args, async () => {
        const prefix = await editionPrefix(ctx);
        const body = nonEmpty({ status: args.status, completion_notes: args.completion_notes });
        const res = await ctx.client.patch(`${prefix}/tasks/${args.task_id}/status`, body, {
          idempotencyKey: rawArgs.idempotency_key,
        });
        const data = redactSensitive(res.data ?? res);
        return {
          affected_id: args.task_id as number,
          result: {
            success: true,
            affected_id: args.task_id,
            summary: res.message ?? `Task marked ${args.status}.`,
            data,
          },
        };
      });
    },
  }),
];
