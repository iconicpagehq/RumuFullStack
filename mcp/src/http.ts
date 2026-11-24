import { createServer, type IncomingMessage, type Server, type ServerResponse } from "node:http";
import { realpathSync } from "node:fs";
import { fileURLToPath } from "node:url";
import { StreamableHTTPServerTransport } from "@modelcontextprotocol/sdk/server/streamableHttp.js";
import type { GatewayConfig } from "./config.js";
import { loadGatewayConfig } from "./config.js";
import { SERVER_NAME, buildServer, makeContext, makeSharedState } from "./server.js";

const MAX_BODY_BYTES = 1024 * 1024;
const PROXY_FORWARDED_HEADERS = ["content-type", "accept", "cookie", "authorization", "user-agent"];
const PROXY_RESPONSE_HEADERS = ["content-type", "location", "set-cookie", "cache-control"];

function json(res: ServerResponse, status: number, body: unknown, headers: Record<string, string> = {}): void {
  const payload = JSON.stringify(body);
  res.writeHead(status, {
    "Content-Type": "application/json; charset=utf-8",
    "Content-Length": Buffer.byteLength(payload),
    "Access-Control-Allow-Origin": "*",
    ...headers,
  });
  res.end(payload);
}

function readBody(req: IncomingMessage, limit = MAX_BODY_BYTES): Promise<Buffer> {
  return new Promise((resolve, reject) => {
    const chunks: Buffer[] = [];
    let size = 0;
    req.on("data", (chunk: Buffer) => {
      size += chunk.length;
      if (size > limit) {
        reject(new Error("payload_too_large"));
        req.destroy();
        return;
      }
      chunks.push(chunk);
    });
    req.on("end", () => resolve(Buffer.concat(chunks)));
    req.on("error", reject);
  });
}

function extractBearer(req: IncomingMessage): string {
  const header = req.headers.authorization ?? "";
  return header.startsWith("Bearer ") ? header.slice("Bearer ".length).trim() : "";
}

export function authServerMetadata(config: GatewayConfig): Record<string, unknown> {
  const origin = config.publicOrigin;
  return {
    issuer: origin,
    authorization_endpoint: `${origin}/oauth/authorize`,
    token_endpoint: `${origin}/oauth/token`,
    registration_endpoint: `${origin}/oauth/register`,
    response_types_supported: ["code"],
    response_modes_supported: ["query"],
    grant_types_supported: ["authorization_code"],
    code_challenge_methods_supported: ["S256"],
    token_endpoint_auth_methods_supported: ["none"],
    scopes_supported: ["mcp"],
    service_documentation: "https://rumu.in",
  };
}

export function protectedResourceMetadata(config: GatewayConfig): Record<string, unknown> {
  return {
    resource: config.publicOrigin,
    authorization_servers: [config.publicOrigin],
    scopes_supported: ["mcp"],
    bearer_methods_supported: ["header"],
  };
}

export type GatewayHandler = (req: IncomingMessage, res: ServerResponse) => Promise<void>;

/**
 * Stateless MCP-over-HTTP gateway. Every POST is an independent JSON-RPC
 * request authenticated by its own bearer token; confirmation and audit
 * state is shared in-process.
 */
export function createGateway(config: GatewayConfig): GatewayHandler {
  const shared = makeSharedState(config);

  const handleMcpPost = async (req: IncomingMessage, res: ServerResponse): Promise<void> => {
    const token = extractBearer(req);
    if (!token) {
      res.setHeader(
        "WWW-Authenticate",
        `Bearer realm="${SERVER_NAME}", resource_metadata="${config.publicOrigin}/.well-known/oauth-protected-resource"`,
      );
      json(res, 401, {
        error: "invalid_token",
        error_description: "A Rumu OAuth access token is required. Connect through an MCP client to authorize.",
      });
      return;
    }

    const raw = await readBody(req);
    let parsedBody: unknown;
    try {
      parsedBody = raw.length > 0 ? JSON.parse(raw.toString("utf8")) : undefined;
    } catch {
      json(res, 400, { error: "invalid_json", error_description: "Request body is not valid JSON." });
      return;
    }

    const server = buildServer({
      config,
      createContext: () => makeContext(config, token, shared),
    });
    const transport = new StreamableHTTPServerTransport({ sessionIdGenerator: undefined });

    await server.connect(transport);
    res.on("close", () => {
      server.close().catch(() => {});
      transport.close().catch(() => {});
    });
    await transport.handleRequest(req, res, parsedBody);
  };

  const proxyOAuth = async (req: IncomingMessage, res: ServerResponse, url: URL): Promise<void> => {
    const target = `${config.oauthTarget}${url.pathname}${url.search}`;
    const headers: Record<string, string> = {};
    for (const name of PROXY_FORWARDED_HEADERS) {
      const value = req.headers[name];
      if (typeof value === "string") headers[name] = value;
    }

    let body: Buffer | undefined;
    if (req.method !== "GET" && req.method !== "HEAD") {
      body = await readBody(req);
    }

    const upstream = await fetch(target, {
      method: req.method ?? "GET",
      headers,
      body,
      redirect: "manual",
    });

    const responseHeaders: Record<string, string | string[]> = {};
    for (const name of PROXY_RESPONSE_HEADERS) {
      if (name === "set-cookie") continue;
      const value = upstream.headers.get(name);
      if (value !== null) responseHeaders[name] = value;
    }
    // getSetCookie() preserves individual cookies — headers.get() would merge
    // them into one comma-joined string and corrupt the session cookie.
    const setCookies = upstream.headers.getSetCookie?.() ?? [];
    if (setCookies.length > 0) {
      responseHeaders["set-cookie"] = setCookies;
    }

    const buffer = Buffer.from(await upstream.arrayBuffer());
    res.writeHead(upstream.status, {
      "Access-Control-Allow-Origin": "*",
      "Content-Length": buffer.byteLength,
      ...responseHeaders,
    });
    res.end(buffer);
  };

  return async (req, res) => {
    const url = new URL(req.url ?? "/", "http://localhost");
    const path = url.pathname.replace(/\/+$/, "") || "/";

    try {
      if (path === "/healthz") {
        json(res, 200, { ok: true, name: SERVER_NAME, version: "1.0.0" });
        return;
      }
      if (path === "/.well-known/oauth-authorization-server") {
        json(res, 200, authServerMetadata(config), { "Access-Control-Allow-Origin": "*" });
        return;
      }
      if (path === "/.well-known/oauth-protected-resource") {
        json(res, 200, protectedResourceMetadata(config));
        return;
      }
      if (path === "/mcp" || path === "/") {
        if (req.method === "POST") {
          await handleMcpPost(req, res);
          return;
        }
        json(res, 405, { error: "method_not_allowed", error_description: "Use POST for MCP requests." }, { Allow: "POST" });
        return;
      }
      if (path.startsWith("/oauth/")) {
        if (req.method !== "GET" && req.method !== "POST") {
          json(res, 405, { error: "method_not_allowed" }, { Allow: "GET, POST" });
          return;
        }
        await proxyOAuth(req, res, url);
        return;
      }
      json(res, 404, { error: "not_found" });
    } catch (err) {
      if (!res.headersSent) {
        json(res, 500, {
          error: "internal_error",
          error_description: err instanceof Error ? err.message : "Unexpected gateway error.",
        });
      } else {
        res.end();
      }
    }
  };
}

export function startGateway(config: GatewayConfig): Server {
  const handler = createGateway(config);
  const server = createServer((req, res) => {
    void handler(req, res);
  });
  server.listen(config.port, config.host, () => {
    console.error(
      `${SERVER_NAME} gateway listening on http://${config.host}:${config.port} ` +
      `(mcp: POST /mcp, metadata: ${config.publicOrigin}/.well-known/oauth-authorization-server, oauth target: ${config.oauthTarget})`,
    );
  });
  return server;
}

const invokedDirectly = (() => {
  try {
    return !!process.argv[1] && realpathSync(process.argv[1]) === fileURLToPath(import.meta.url);
  } catch {
    return false;
  }
})();

if (invokedDirectly) {
  try {
    startGateway(loadGatewayConfig());
  } catch (err) {
    console.error(err instanceof Error ? err.message : err);
    process.exit(1);
  }
}
