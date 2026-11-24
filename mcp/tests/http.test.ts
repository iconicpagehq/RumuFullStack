import { afterAll, beforeAll, describe, expect, it } from "vitest";
import { createServer, type Server } from "node:http";
import { tmpdir } from "node:os";
import { join } from "node:path";
import type { AddressInfo } from "node:net";
import type { GatewayConfig } from "../src/config.js";
import { authServerMetadata, createGateway } from "../src/http.js";

interface RecordedRequest {
  method?: string;
  url?: string;
  authorization?: string;
  contentType?: string;
  body?: string;
}

function startServer(handler: (req: import("node:http").IncomingMessage, res: import("node:http").ServerResponse) => void): Promise<{ server: Server; port: number }> {
  return new Promise((resolve) => {
    const server = createServer(handler);
    server.listen(0, "127.0.0.1", () => {
      resolve({ server, port: (server.address() as AddressInfo).port });
    });
  });
}

describe("http gateway", () => {
  let upstream: Server;
  let gateway: Server;
  let upstreamPort: number;
  let gatewayPort: number;
  let config: GatewayConfig;
  const recorded: RecordedRequest[] = [];

  const post = (path: string, body: unknown, token?: string) =>
    fetch(`http://127.0.0.1:${gatewayPort}${path}`, {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        Accept: "application/json, text/event-stream",
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
      body: JSON.stringify(body),
    });

  /** Parse an MCP response that may be plain JSON or an SSE stream. */
  const readMcpJson = async (response: Response): Promise<Record<string, any>> => {
    const text = await response.text();
    const dataLine = text.split("\n").find((line) => line.startsWith("data: "));
    const jsonText = dataLine ? dataLine.slice("data: ".length) : text;
    return JSON.parse(jsonText) as Record<string, any>;
  };

  beforeAll(async () => {
    ({ server: upstream, port: upstreamPort } = await startServer((req, res) => {
      const chunks: Buffer[] = [];
      req.on("data", (c: Buffer) => chunks.push(c));
      req.on("end", () => {
        recorded.push({
          method: req.method,
          url: req.url,
          authorization: req.headers.authorization,
          contentType: req.headers["content-type"],
          body: Buffer.concat(chunks).toString("utf8"),
        });
        if (req.url?.startsWith("/api/v1/auth/me")) {
          res.writeHead(200, { "Content-Type": "application/json" });
          res.end(JSON.stringify({ data: { id: 1, name: "Test User", email: "t@rumu.in", role: "realtor" } }));
          return;
        }
        res.writeHead(200, { "Content-Type": "application/json" });
        res.end(JSON.stringify({ ok: true, seen: req.url }));
      });
    }));

    config = {
      apiBase: `http://127.0.0.1:${upstreamPort}/api/v1`,
      token: undefined,
      timeoutMs: 5000,
      maxRetries: 0,
      pageSize: 20,
      minIntervalMs: 0,
      confirmTtlSeconds: 300,
      logRetentionDays: 7,
      logPath: join(tmpdir(), `rumu-mcp-http-test-${Date.now()}.jsonl`),
      nodeEnv: "test",
      port: 0,
      host: "127.0.0.1",
      publicOrigin: "http://127.0.0.1:0",
      oauthTarget: `http://127.0.0.1:${upstreamPort}`,
    };

    const handler = createGateway(config);
    ({ server: gateway, port: gatewayPort } = await startServer((req, res) => {
      void handler(req, res);
    }));
    config.publicOrigin = `http://127.0.0.1:${gatewayPort}`;
  });

  afterAll(async () => {
    await new Promise<void>((resolve) => gateway.close(() => resolve()));
    await new Promise<void>((resolve) => upstream.close(() => resolve()));
  });

  it("serves health and OAuth metadata", async () => {
    const health = await fetch(`http://127.0.0.1:${gatewayPort}/healthz`);
    expect(health.status).toBe(200);
    expect((await health.json()) as Record<string, unknown>).toMatchObject({ ok: true, name: "rumu-mcp" });

    const metadata = await fetch(`http://127.0.0.1:${gatewayPort}/.well-known/oauth-authorization-server`);
    expect(metadata.status).toBe(200);
    const meta = (await metadata.json()) as Record<string, string>;
    expect(meta.issuer).toBe(config.publicOrigin);
    expect(meta.authorization_endpoint).toBe(`${config.publicOrigin}/oauth/authorize`);
    expect(meta.token_endpoint).toBe(`${config.publicOrigin}/oauth/token`);
    expect(meta.registration_endpoint).toBe(`${config.publicOrigin}/oauth/register`);
    expect(meta.code_challenge_methods_supported).toEqual(["S256"]);

    const resource = await fetch(`http://127.0.0.1:${gatewayPort}/.well-known/oauth-protected-resource`);
    expect(resource.status).toBe(200);
    expect((await resource.json()) as Record<string, unknown>).toMatchObject({
      resource: config.publicOrigin,
      authorization_servers: [config.publicOrigin],
    });
  });

  it("requires a bearer token for MCP requests", async () => {
    const response = await post("/mcp", {
      jsonrpc: "2.0",
      id: 1,
      method: "initialize",
      params: { protocolVersion: "2024-11-05", capabilities: {}, clientInfo: { name: "t", version: "1" } },
    });
    expect(response.status).toBe(401);
    const wwwAuth = response.headers.get("www-authenticate") ?? "";
    expect(wwwAuth).toContain("resource_metadata=");
    expect(wwwAuth).toContain("/.well-known/oauth-protected-resource");
    expect((await response.json()) as Record<string, unknown>).toMatchObject({ error: "invalid_token" });
  });

  it("rejects non-POST methods on the MCP endpoint", async () => {
    const response = await fetch(`http://127.0.0.1:${gatewayPort}/mcp`, {
      headers: { Authorization: "Bearer tok" },
    });
    expect(response.status).toBe(405);
  });

  it("answers initialize and tools/list with a bearer token", async () => {
    const init = await post("/mcp", {
      jsonrpc: "2.0",
      id: 1,
      method: "initialize",
      params: { protocolVersion: "2024-11-05", capabilities: {}, clientInfo: { name: "t", version: "1" } },
    }, "tok-123");
    expect(init.status).toBe(200);
    const initBody = await readMcpJson(init);
    expect(initBody.result.serverInfo.name).toBe("rumu-mcp");

    const tools = await post("/mcp", { jsonrpc: "2.0", id: 2, method: "tools/list", params: {} }, "tok-123");
    expect(tools.status).toBe(200);
    const toolsBody = await readMcpJson(tools);
    const names = (toolsBody.result.tools as { name: string }[]).map((t) => t.name);
    expect(names).toContain("rumu_auth_me");
    expect(names.length).toBeGreaterThanOrEqual(33);
  });

  it("routes each request's bearer token to the Rumu API", async () => {
    const call = await post("/mcp", {
      jsonrpc: "2.0",
      id: 3,
      method: "tools/call",
      params: { name: "rumu_auth_me", arguments: {} },
    }, "per-request-token");
    expect(call.status).toBe(200);
    const callBody = await readMcpJson(call);
    const inner = JSON.parse(callBody.result.content[0].text as string) as { success: boolean; data: { name: string } };
    expect(inner.success).toBe(true);
    expect(inner.data.name).toBe("Test User");

    const meRequest = recorded.find((r) => r.url?.startsWith("/api/v1/auth/me"));
    expect(meRequest?.authorization).toBe("Bearer per-request-token");
  });

  it("proxies /oauth requests to the Laravel backend", async () => {
    const form = new URLSearchParams({ grant_type: "authorization_code", code: "abc" });
    const proxied = await fetch(`http://127.0.0.1:${gatewayPort}/oauth/token`, {
      method: "POST",
      headers: { "Content-Type": "application/x-www-form-urlencoded", Cookie: "session=xyz" },
      body: form.toString(),
    });
    expect(proxied.status).toBe(200);
    const body = (await proxied.json()) as { seen: string; ok: boolean };
    expect(body.ok).toBe(true);
    expect(body.seen).toBe("/oauth/token");

    const forwarded = recorded.find((r) => r.url === "/oauth/token");
    expect(forwarded?.method).toBe("POST");
    expect(forwarded?.contentType).toBe("application/x-www-form-urlencoded");
    expect(forwarded?.body).toContain("grant_type=authorization_code");
  });

  it("serves auth server metadata from the exported helper with a production origin", () => {
    const meta = authServerMetadata({
      ...config,
      publicOrigin: "https://mcp.rumu.in",
    });
    expect((meta as Record<string, string>).token_endpoint).toBe("https://mcp.rumu.in/oauth/token");
  });
});
