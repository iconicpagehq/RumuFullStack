import { describe, expect, it, vi } from "vitest";
import { ApiError } from "../src/errors.js";
import type { RumuApiClient } from "../src/client.js";
import { ConfirmationRegistry } from "../src/confirm.js";
import { IdentityResolver } from "../src/identity.js";
import type { RumuIdentity } from "../src/types.js";
import type { ToolContext } from "../src/toolkit.js";
import { allTools } from "../src/server.js";
import { loadConfig } from "../src/config.js";

const config = loadConfig({
  RUMU_API_BASE: "https://api.test/api/v1",
  RUMU_TOKEN: "token",
  NODE_ENV: "test",
});

function identityStub(role: RumuIdentity["role"]): IdentityResolver {
  return { resolve: async () => ({ id: 42, name: "Test User", email: "t@example.com", role, raw: {} }) } as IdentityResolver;
}

function auditStub() {
  return { record: vi.fn() } as unknown as ToolContext["audit"];
}

function findTool(name: string) {
  const tool = allTools.find((t) => t.name === name);
  if (!tool) throw new Error(`tool ${name} not registered`);
  return tool;
}

interface MockClient {
  ctx: ToolContext;
  calls: { method: string; path: string; body?: unknown; params?: unknown; options?: unknown }[];
  enqueue: (responder: (call: { method: string; path: string; body?: unknown; params?: Record<string, unknown> }) => unknown) => void;
}

function makeCtx(role: RumuIdentity["role"] = "realtor"): MockClient {
  const calls: MockClient["calls"] = [];
  const responders: Array<(call: MockClient["calls"][number]) => unknown> = [];
  const stub = {
    get: vi.fn(async (path: string, params?: unknown) => {
      const call = { method: "GET", path, params };
      calls.push(call);
      const r = responders.shift();
      return r ? r(call) : { data: [] };
    }),
    getList: vi.fn(async (path: string, params?: unknown) => {
      const call = { method: "GET", path, params };
      calls.push(call);
      const r = responders.shift();
      const res = r ? r(call) : { data: [] };
      return { items: (res as { data?: unknown[] }).data ?? [], meta: (res as { meta?: unknown }).meta };
    }),
    post: vi.fn(async (path: string, body?: unknown, options?: unknown) => {
      const call = { method: "POST", path, body, options };
      calls.push(call);
      const r = responders.shift();
      return r ? r(call) : { data: { id: 1 }, message: "Created." };
    }),
    put: vi.fn(async (path: string, body?: unknown, options?: unknown) => {
      const call = { method: "PUT", path, body, options };
      calls.push(call);
      const r = responders.shift();
      return r ? r(call) : { data: { id: 1 }, message: "Updated." };
    }),
    patch: vi.fn(async (path: string, body?: unknown, options?: unknown) => {
      const call = { method: "PATCH", path, body, options };
      calls.push(call);
      const r = responders.shift();
      return r ? r(call) : { data: { id: 1 }, message: "Saved." };
    }),
    delete: vi.fn(async (path: string, options?: unknown) => {
      const call = { method: "DELETE", path, options };
      calls.push(call);
      const r = responders.shift();
      return r ? r(call) : { message: "Deleted." };
    }),
    getText: vi.fn(async (path: string) => {
      calls.push({ method: "GET_TEXT", path });
      const r = responders.shift();
      return r ? r({ method: "GET_TEXT", path }) : { text: "h1\nrow\n", headers: new Headers() };
    }),
  };
  const ctx: ToolContext = {
    client: stub as unknown as RumuApiClient,
    identity: identityStub(role),
    confirm: new ConfirmationRegistry(300_000),
    audit: auditStub(),
    config,
  };
  return {
    ctx,
    calls,
    enqueue: (responder) => responders.push(responder as never),
  };
}

describe("leads tools", () => {
  it("rumu_leads_list maps status for realtor", async () => {
    const { ctx, calls } = makeCtx("realtor");
    await findTool("rumu_leads_list").handler({ status: "qualified", page: 2 }, ctx);
    expect(calls[0]!.path).toBe("/realtor/leads");
    expect((calls[0]!.params as Record<string, unknown>).status).toBe("qualified");
    expect((calls[0]!.params as Record<string, unknown>).stage).toBeUndefined();
  });

  it("rumu_leads_list maps stage for enterprise", async () => {
    const { ctx, calls } = makeCtx("enterprise");
    await findTool("rumu_leads_list").handler({ stage: "negotiation" }, ctx);
    expect(calls[0]!.path).toBe("/enterprise/leads");
    expect((calls[0]!.params as Record<string, unknown>).stage).toBe("negotiation");
  });

  it("rumu_leads_create (realtor) requires campaign_id before calling the API", async () => {
    const { ctx, calls } = makeCtx("realtor");
    const err = (await findTool("rumu_leads_create").handler({ name: "Riya" }, ctx).catch((e: unknown) => e)) as ApiError;
    expect(err).toBeInstanceOf(ApiError);
    expect(err.code).toBe("validation");
    expect(err.fieldErrors?.campaign_id).toBeDefined();
    expect(calls).toHaveLength(0);
  });

  it("rumu_leads_create (realtor) posts into the campaign route", async () => {
    const { ctx, calls } = makeCtx("realtor");
    const result = (await findTool("rumu_leads_create").handler(
      { name: "Riya", campaign_id: 9, status: "new", note: "Budget 1.4Cr" },
      ctx,
    )) as { success: boolean; summary: string };
    expect(result.success).toBe(true);
    expect(calls[0]!.path).toBe("/realtor/campaigns/9/leads");
    expect((calls[0]!.body as Record<string, unknown>).note).toBe("Budget 1.4Cr");
  });

  it("rumu_leads_update_status uses /status for realtor and /stage for enterprise", async () => {
    const realtor = makeCtx("realtor");
    await findTool("rumu_leads_update_status").handler({ lead_id: 3, status: "qualified" }, realtor.ctx);
    expect(realtor.calls[0]!.path).toBe("/realtor/leads/3/status");

    const enterprise = makeCtx("enterprise");
    await findTool("rumu_leads_update_status").handler({ lead_id: 3, stage: "won", deal_value: 100 }, enterprise.ctx);
    expect(enterprise.calls[0]!.path).toBe("/enterprise/leads/3/stage");
    expect((enterprise.calls[0]!.body as Record<string, unknown>).deal_value).toBe(100);
  });

  it("rumu_leads_delete only executes after rumu_confirm", async () => {
    const { ctx, calls } = makeCtx("realtor");
    const prepared = (await findTool("rumu_leads_delete").handler({ lead_id: 7 }, ctx)) as {
      requires_confirmation: boolean;
      confirm_token: string;
      action_summary: string;
    };
    expect(prepared.requires_confirmation).toBe(true);
    expect(prepared.action_summary).toContain("7");
    expect(calls).toHaveLength(0);

    const confirmed = (await findTool("rumu_confirm").handler({ token: prepared.confirm_token }, ctx)) as { success: boolean };
    expect(confirmed.success).toBe(true);
    expect(calls[0]!.method).toBe("DELETE");
    expect(calls[0]!.path).toBe("/realtor/leads/7");
  });

  it("rumu_cancel aborts a pending delete", async () => {
    const { ctx, calls } = makeCtx("realtor");
    const prepared = (await findTool("rumu_leads_delete").handler({ lead_id: 7 }, ctx)) as { confirm_token: string };
    await findTool("rumu_cancel").handler({ token: prepared.confirm_token }, ctx);
    expect(calls).toHaveLength(0);
    await expect(findTool("rumu_confirm").handler({ token: prepared.confirm_token }, ctx)).rejects.toBeInstanceOf(ApiError);
  });
});

describe("task / visit / call tools", () => {
  it("rumu_tasks_create posts to the edition route", async () => {
    const { ctx, calls } = makeCtx("enterprise");
    await findTool("rumu_tasks_create").handler({ title: "Follow up", priority: "high", lead_id: 5 }, ctx);
    expect(calls[0]!.path).toBe("/enterprise/tasks");
    expect((calls[0]!.body as Record<string, unknown>).title).toBe("Follow up");
  });

  it("rumu_visits_schedule sends scheduled_at as ISO datetime", async () => {
    const { ctx, calls } = makeCtx("realtor");
    await findTool("rumu_visits_schedule").handler({ lead_id: 2, scheduled_at: "2026-11-08T11:00:00+05:30" }, ctx);
    expect(calls[0]!.path).toBe("/realtor/site-visits");
    expect((calls[0]!.body as Record<string, unknown>).scheduled_at).toBe("2026-11-08T11:00:00+05:30");
  });

  it("rumu_calls_get strips recording URLs from the response", async () => {
    const { ctx, enqueue } = makeCtx("realtor");
    enqueue(() => ({
      data: { id: 11, recording: { status: "available", url: "/realtor/calls/11/recording", size: 123 } },
    }));
    const result = (await findTool("rumu_calls_get").handler({ call_id: 11 }, ctx)) as {
      data: { recording: Record<string, unknown> };
    };
    expect(result.data.recording.status).toBe("available");
    expect(result.data.recording.url).toBeUndefined();
  });

  it("rumu_calls_set_disposition patches the disposition", async () => {
    const { ctx, calls } = makeCtx("realtor");
    await findTool("rumu_calls_set_disposition").handler({ call_id: 4, disposition: "interested" }, ctx);
    expect(calls[0]!.path).toBe("/realtor/calls/4/disposition");
    expect((calls[0]!.body as Record<string, unknown>).disposition).toBe("interested");
  });
});

describe("role boundaries", () => {
  it("admin tools reject a realtor identity before any API call", async () => {
    const { ctx, calls } = makeCtx("realtor");
    const err = (await findTool("rumu_admin_users_list").handler({}, ctx).catch((e: unknown) => e)) as ApiError;
    expect(err).toBeInstanceOf(ApiError);
    expect(err.status).toBe(403);
    expect(calls).toHaveLength(0);
  });

  it("enterprise reports reject a realtor identity", async () => {
    const { ctx, calls } = makeCtx("realtor");
    const err = (await findTool("rumu_reports_snapshot").handler({}, ctx).catch((e: unknown) => e)) as ApiError;
    expect(err).toBeInstanceOf(ApiError);
    expect(err.status).toBe(403);
    expect(calls).toHaveLength(0);
  });

  it("enterprise tools reject a realtor identity", async () => {
    const { ctx, calls } = makeCtx("realtor");
    const err = (await findTool("rumu_enterprise_teams_list").handler({}, ctx).catch((e: unknown) => e)) as ApiError;
    expect(err).toBeInstanceOf(ApiError);
    expect(calls).toHaveLength(0);
  });
});

describe("enterprise distribution", () => {
  it("requires assigned_to for manual mode before any API call", async () => {
    const { ctx, calls } = makeCtx("enterprise");
    const err = (await findTool("rumu_enterprise_distribute").handler(
      { lead_ids: [1, 2], team_id: 3, mode: "manual" },
      ctx,
    ).catch((e: unknown) => e)) as ApiError;
    expect(err.code).toBe("validation");
    expect(err.fieldErrors?.assigned_to).toBeDefined();
    expect(calls).toHaveLength(0);
  });

  it("dry_run previews without distributing, then executes only via confirmation", async () => {
    const { ctx, calls, enqueue } = makeCtx("enterprise");
    enqueue(() => ({ data: { id: 3, name: "Team A" } }));

    const dry = (await findTool("rumu_enterprise_distribute").handler(
      { lead_ids: [1, 2, 3], team_id: 3, mode: "round_robin", dry_run: true },
      ctx,
    )) as { success: boolean; data: { records_selected: number } };
    expect(dry.success).toBe(true);
    expect(dry.data.records_selected).toBe(3);
    expect(calls.every((c) => c.method === "GET")).toBe(true);

    const prepared = (await findTool("rumu_enterprise_distribute").handler(
      { lead_ids: [1, 2, 3], team_id: 3, mode: "round_robin", dry_run: false },
      ctx,
    )) as { requires_confirmation: boolean; confirm_token: string };
    expect(prepared.requires_confirmation).toBe(true);

    const confirmed = (await findTool("rumu_confirm").handler({ token: prepared.confirm_token }, ctx)) as { success: boolean };
    expect(confirmed.success).toBe(true);
    const distributeCall = calls.find((c) => c.path === "/enterprise/leads/distribute");
    expect(distributeCall?.method).toBe("POST");
  });
});

describe("config validation", () => {
  it("rejects HTTP base URL in production", () => {
    expect(() => loadConfig({ RUMU_API_BASE: "http://127.0.0.1:8000/api/v1", RUMU_TOKEN: "t", NODE_ENV: "production" })).toThrow(/HTTPS/);
  });

  it("requires a token", () => {
    expect(() => loadConfig({ RUMU_API_BASE: "https://api.rumu.in/api/v1" })).toThrow(/RUMU_TOKEN/);
  });
});
