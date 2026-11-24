/* End-to-end smoke: full OAuth flow + MCP calls through the gateway. */
const GW = "http://127.0.0.1:8787";
const REDIRECT = "http://localhost:9999/callback";

function b64url(buf) {
  return Buffer.from(buf).toString("base64").replace(/\+/g, "-").replace(/\//g, "_").replace(/=+$/, "");
}
const verifier = b64url(require("node:crypto").randomBytes(64));
const challenge = b64url(require("node:crypto").createHash("sha256").update(verifier).digest());

const jar = new Map();
function cookieHeader() {
  return [...jar.entries()].map(([k, v]) => `${k}=${v}`).join("; ");
}
function storeCookies(res) {
  const raw = res.headers.getSetCookie?.() ?? [];
  for (const c of raw) {
    const [pair] = c.split(";");
    const idx = pair.indexOf("=");
    jar.set(pair.slice(0, idx).trim(), pair.slice(idx + 1).trim());
  }
}

async function readMcp(res) {
  const text = await res.text();
  const line = text.split("\n").find((l) => l.startsWith("data: "));
  return JSON.parse(line ? line.slice(6) : text);
}

(async () => {
  const step = (name, ok, extra = "") => console.log(`${ok ? "PASS" : "FAIL"}  ${name}${extra ? " — " + extra : ""}`);

  // 1. Metadata
  const meta = await (await fetch(`${GW}/.well-known/oauth-authorization-server`)).json();
  step("metadata", meta.issuer === GW && meta.code_challenge_methods_supported?.includes("S256"));

  // 2. 401 without token
  const noAuth = await fetch(`${GW}/mcp`, {
    method: "POST",
    headers: { "Content-Type": "application/json", Accept: "application/json, text/event-stream" },
    body: JSON.stringify({ jsonrpc: "2.0", id: 1, method: "initialize", params: { protocolVersion: "2024-11-05", capabilities: {}, clientInfo: { name: "smoke", version: "1" } } }),
  });
  step("401 without token", noAuth.status === 401 && (noAuth.headers.get("www-authenticate") ?? "").includes("resource_metadata="));

  // 3. Register client (through gateway proxy)
  const reg = await fetch(`${GW}/oauth/register`, {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify({ client_name: "Smoke Client", redirect_uris: [REDIRECT] }),
  });
  const client = await reg.json();
  step("client registration", reg.status === 201 && !!client.client_id, client.client_id);

  // 4. Authorize page
  const authUrl = `${GW}/oauth/authorize?` + new URLSearchParams({
    response_type: "code", client_id: client.client_id, redirect_uri: REDIRECT,
    scope: "mcp", state: "smoke-state", code_challenge: challenge, code_challenge_method: "S256",
  });
  const page = await fetch(authUrl, { headers: { Cookie: cookieHeader() } });
  storeCookies(page);
  const html = await page.text();
  const csrf = html.match(/name="_token" value="([^"]+)"/)?.[1];
  step("consent page", page.status === 200 && !!csrf && html.includes("Connect your Rumu account"));

  // 5. Approve (login + consent in one POST)
  const form = new URLSearchParams({
    _token: csrf, response_type: "code", client_id: client.client_id, redirect_uri: REDIRECT,
    scope: "mcp", state: "smoke-state", code_challenge: challenge, code_challenge_method: "S256",
    email: "mcp-smoke@rumu.in", password: "SmokePass123!", decision: "approve",
  });
  const approve = await fetch(`${GW}/oauth/authorize`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded", Cookie: cookieHeader() },
    body: form.toString(),
    redirect: "manual",
  });
  storeCookies(approve);
  const location = approve.headers.get("location") ?? "";
  const code = new URL(location).searchParams.get("code");
  const state = new URL(location).searchParams.get("state");
  step("approve issues code", approve.status === 302 && !!code && state === "smoke-state");

  // 6. Token exchange
  const tokenRes = await fetch(`${GW}/oauth/token`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      grant_type: "authorization_code", client_id: client.client_id, code,
      redirect_uri: REDIRECT, code_verifier: verifier,
    }),
  });
  const token = await tokenRes.json();
  step("token exchange", tokenRes.status === 200 && !!token.access_token, `${token.token_type}, expires_in=${token.expires_in}`);

  // 7. MCP initialize + auth_me with the OAuth token
  const mcp = (payload) => fetch(`${GW}/mcp`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
      Accept: "application/json, text/event-stream",
      Authorization: `Bearer ${token.access_token}`,
    },
    body: JSON.stringify(payload),
  });
  const init = await readMcp(await mcp({ jsonrpc: "2.0", id: 1, method: "initialize", params: { protocolVersion: "2024-11-05", capabilities: {}, clientInfo: { name: "smoke", version: "1" } } }));
  step("initialize with OAuth token", init.result?.serverInfo?.name === "rumu-mcp");

  const me = await readMcp(await mcp({ jsonrpc: "2.0", id: 2, method: "tools/call", params: { name: "rumu_auth_me", arguments: {} } }));
  const inner = JSON.parse(me.result?.content?.[0]?.text ?? "{}");
  step("rumu_auth_me via OAuth token", inner.success === true && inner.data?.email === "mcp-smoke@rumu.in", `role=${inner.data?.role}`);

  // 8. Replay of the same code must fail
  const replay = await fetch(`${GW}/oauth/token`, {
    method: "POST",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      grant_type: "authorization_code", client_id: client.client_id, code,
      redirect_uri: REDIRECT, code_verifier: verifier,
    }),
  });
  step("code replay rejected", replay.status === 400 && (await replay.json()).error === "invalid_grant");

  process.exit(0);
})().catch((err) => {
  console.error("SMOKE ERROR", err);
  process.exit(1);
});
