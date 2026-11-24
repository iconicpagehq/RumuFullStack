export interface McpConfig {
  apiBase: string;
  token?: string;
  timeoutMs: number;
  maxRetries: number;
  pageSize: number;
  minIntervalMs: number;
  confirmTtlSeconds: number;
  logRetentionDays: number;
  logPath: string;
  nodeEnv: string;
}

export interface GatewayConfig extends McpConfig {
  port: number;
  host: string;
  /** Public origin advertised in OAuth metadata, e.g. https://mcp.rumu.in */
  publicOrigin: string;
  /** Laravel base URL used to proxy /oauth/* requests, e.g. https://api.rumu.in */
  oauthTarget: string;
}

function int(value: string | undefined, fallback: number): number {
  const parsed = Number.parseInt(value ?? "", 10);
  return Number.isFinite(parsed) && parsed >= 0 ? parsed : fallback;
}

export function loadConfig(
  env: NodeJS.ProcessEnv = process.env,
  options: { requireToken?: boolean } = {},
): McpConfig {
  const apiBase = (env.RUMU_API_BASE ?? "").replace(/\/+$/, "");
  const token = env.RUMU_TOKEN ?? "";
  const requireToken = options.requireToken ?? true;

  if (!apiBase) {
    throw new Error("RUMU_API_BASE is required (e.g. https://api.rumu.in/api/v1)");
  }
  if (requireToken && !token) {
    throw new Error("RUMU_TOKEN is required. Provide a Sanctum personal access token.");
  }
  if (env.NODE_ENV === "production" && !apiBase.startsWith("https://")) {
    throw new Error("RUMU_API_BASE must use HTTPS in production.");
  }

  return {
    apiBase,
    token: token || undefined,
    timeoutMs: int(env.RUMU_REQUEST_TIMEOUT_MS, 15000),
    maxRetries: int(env.RUMU_MAX_RETRIES, 2),
    pageSize: Math.min(Math.max(int(env.RUMU_PAGE_SIZE, 20), 1), 20),
    minIntervalMs: int(env.RUMU_MIN_INTERVAL_MS, 200),
    confirmTtlSeconds: int(env.MCP_CONFIRM_TTL_SECONDS, 300),
    logRetentionDays: int(env.MCP_LOG_RETENTION_DAYS, 7),
    logPath: env.MCP_LOG_PATH ?? ".mcp-log.jsonl",
    nodeEnv: env.NODE_ENV ?? "development",
  };
}

export function loadGatewayConfig(env: NodeJS.ProcessEnv = process.env): GatewayConfig {
  const base = loadConfig(env, { requireToken: false });

  const port = int(env.RUMU_MCP_PORT, int(env.PORT, 8787));
  const host = env.RUMU_MCP_HOST ?? "127.0.0.1";
  const derivedOrigin = `http://${host}:${port}`;
  const publicOrigin = (env.RUMU_MCP_PUBLIC_ORIGIN ?? derivedOrigin).replace(/\/+$/, "");
  const oauthTarget = (
    env.RUMU_OAUTH_TARGET ?? base.apiBase.replace(/\/api\/v1\/?$/, "")
  ).replace(/\/+$/, "");

  if (base.nodeEnv === "production" && !publicOrigin.startsWith("https://")) {
    throw new Error("RUMU_MCP_PUBLIC_ORIGIN must use HTTPS in production.");
  }
  if (!oauthTarget) {
    throw new Error("RUMU_OAUTH_TARGET could not be derived; set it explicitly (e.g. https://api.rumu.in).");
  }

  return { ...base, port, host, publicOrigin, oauthTarget };
}
