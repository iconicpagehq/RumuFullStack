import { McpServer } from "@modelcontextprotocol/sdk/server/mcp.js";
import { z } from "zod";
import type { McpConfig } from "./config.js";
import { createClient, type RumuApiClient } from "./client.js";
import { ConfirmationRegistry } from "./confirm.js";
import { IdentityResolver } from "./identity.js";
import { AuditLog } from "./utils/logging.js";
import { ApiError } from "./errors.js";
import { withAudit, type ToolContext, type RumuTool } from "./toolkit.js";
import { authTools } from "./tools/auth.js";
import { dashboardTools } from "./tools/dashboard.js";
import { leadsTools } from "./tools/leads.js";
import { campaignTools } from "./tools/campaigns.js";
import { taskTools } from "./tools/tasks.js";
import { siteVisitTools } from "./tools/siteVisits.js";
import { inventoryTools } from "./tools/inventory.js";
import { customerTools } from "./tools/customers.js";
import { callTools } from "./tools/calls.js";
import { reportTools } from "./tools/reports.js";
import { enterpriseTools } from "./tools/enterprise.js";
import { adminTools } from "./tools/admin.js";
import { confirmationTools } from "./tools/confirmation.js";
import { morningBriefPrompt } from "./prompts/morningBrief.js";
import { leadDossierPrompt } from "./prompts/leadDossier.js";
import { resources } from "./resources/index.js";

export const SERVER_NAME = "rumu-mcp";
export const SERVER_VERSION = "1.0.0";

export const allTools: RumuTool[] = [
  ...authTools,
  ...dashboardTools,
  ...leadsTools,
  ...campaignTools,
  ...taskTools,
  ...siteVisitTools,
  ...inventoryTools,
  ...customerTools,
  ...callTools,
  ...reportTools,
  ...enterpriseTools,
  ...adminTools,
  ...confirmationTools,
];

const READ_ONLY_TOOLS = new Set([
  "rumu_admin_billing_subscriptions",
  "rumu_admin_broadcast_preview",
  "rumu_admin_enterprises_list",
  "rumu_admin_plans_list",
  "rumu_admin_seat_requests",
  "rumu_admin_users_list",
  "rumu_auth_me",
  "rumu_calls_get",
  "rumu_calls_list",
  "rumu_calls_summary",
  "rumu_campaigns_list",
  "rumu_customers_list",
  "rumu_dashboard_today",
  "rumu_enterprise_audit",
  "rumu_enterprise_members_list",
  "rumu_enterprise_targets_get",
  "rumu_enterprise_teams_list",
  "rumu_inventory_projects",
  "rumu_inventory_units_search",
  "rumu_leads_get",
  "rumu_leads_list",
  "rumu_reports_snapshot",
  "rumu_tasks_list",
  "rumu_visits_list",
]);

const DESTRUCTIVE_TOOLS = new Set([
  "rumu_admin_broadcast_send",
  "rumu_admin_enterprise_suspend",
  "rumu_admin_plan_upsert",
  "rumu_confirm",
  "rumu_enterprise_distribute",
  "rumu_enterprise_targets_set",
  "rumu_leads_delete",
  "rumu_leads_export",
]);

const MUTATING_TOOLS = new Set([
  "rumu_admin_broadcast_send",
  "rumu_admin_enterprise_suspend",
  "rumu_admin_plan_upsert",
  "rumu_cancel",
  "rumu_confirm",
  "rumu_enterprise_distribute",
  "rumu_enterprise_targets_set",
  "rumu_campaigns_create",
  "rumu_calls_set_disposition",
  "rumu_leads_add_note",
  "rumu_leads_create",
  "rumu_leads_delete",
  "rumu_leads_export",
  "rumu_leads_set_followup",
  "rumu_leads_update_status",
  "rumu_tasks_create",
  "rumu_tasks_update_status",
  "rumu_visits_close",
  "rumu_visits_schedule",
]);

function toolAnnotations(toolName: string) {
  if (!READ_ONLY_TOOLS.has(toolName) && !MUTATING_TOOLS.has(toolName)) {
    throw new Error(`Missing MCP behavior annotations for ${toolName}.`);
  }
  return {
    readOnlyHint: READ_ONLY_TOOLS.has(toolName),
    openWorldHint: true,
    destructiveHint: DESTRUCTIVE_TOOLS.has(toolName),
  };
}

export interface ServerDeps {
  config: McpConfig;
  /**
   * Factory for the tool context used per tool invocation. Defaults to a
   * single env-token context. The HTTP gateway passes a per-request factory
   * so each bearer token gets its own API client and identity.
   */
  createContext?: () => ToolContext;
}

/** Shared state across contexts: confirm tokens and the audit log. */
export interface SharedState {
  confirm: ConfirmationRegistry;
  audit: AuditLog;
}

export function makeSharedState(config: McpConfig): SharedState {
  return {
    confirm: new ConfirmationRegistry(config.confirmTtlSeconds * 1000),
    audit: new AuditLog(config.logPath, config.logRetentionDays),
  };
}

/** Build a tool context for a specific bearer token against the Rumu API. */
export function makeContext(
  config: McpConfig,
  token: string,
  shared: SharedState,
): ToolContext {
  const client = createClient({
    apiBase: config.apiBase,
    token,
    timeoutMs: config.timeoutMs,
    maxRetries: config.maxRetries,
    minIntervalMs: config.minIntervalMs,
  });

  return {
    client,
    identity: new IdentityResolver(client),
    confirm: shared.confirm,
    audit: shared.audit,
    config,
  };
}

function asErrorResult(err: unknown): { content: { type: "text"; text: string }[] } {
  if (err instanceof ApiError) {
    return { content: [{ type: "text", text: JSON.stringify(err.toResult()) }] };
  }
  if (err instanceof z.ZodError) {
    return {
      content: [{
        type: "text",
        text: JSON.stringify({
          success: false,
          error: "validation",
          message: "Invalid tool arguments.",
          field_errors: err.flatten().fieldErrors,
          retryable: false,
        }),
      }],
    };
  }
  const message = err instanceof Error ? err.message : "Unknown error.";
  return {
    content: [{
      type: "text",
      text: JSON.stringify({ success: false, error: "unknown", message, retryable: false }),
    }],
  };
}

export function buildServer(deps: ServerDeps): McpServer {
  const server = new McpServer({ name: SERVER_NAME, version: SERVER_VERSION });

  const shared: SharedState = makeSharedState(deps.config);
  let defaultContext: ToolContext | undefined;
  const createContext = deps.createContext ?? (() => (defaultContext ??= makeContext(
    deps.config,
    deps.config.token ?? "",
    shared,
  )));

  for (const tool of allTools) {
    server.tool(
      tool.name,
      tool.description,
      tool.schema,
      toolAnnotations(tool.name),
      async (args) => {
        try {
          const ctx = createContext();
          const result = await withAudit(ctx, tool.name, args, async () => ({
            result: await tool.handler(args as never, ctx),
          }));
          return { content: [{ type: "text", text: JSON.stringify(result) }] };
        } catch (err) {
          return asErrorResult(err);
        }
      },
    );
  }

  for (const prompt of [morningBriefPrompt, leadDossierPrompt]) {
    server.prompt(prompt.name, prompt.description, prompt.argsSchema, prompt.handler);
  }

  for (const resource of resources) {
    server.resource(
      resource.name,
      resource.uri,
      { description: `Rumu reference: ${resource.name}`, mimeType: resource.mimeType },
      async () => ({ contents: [{ uri: resource.uri, mimeType: resource.mimeType, text: resource.text }] }),
    );
  }

  return server;
}
