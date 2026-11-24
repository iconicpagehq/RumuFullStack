import { describe, expect, it, vi } from "vitest";
import { ConfirmationRegistry } from "../src/confirm.js";

const USER_A = "user-a-hash";
const USER_B = "user-b-hash";

function registryWithClock(ttlMs = 300_000) {
  let clock = new Date("2026-11-07T10:00:00Z").getTime();
  const reg = new ConfirmationRegistry(ttlMs, () => clock);
  return {
    reg,
    advance: (ms: number) => { clock += ms; },
  };
}

describe("ConfirmationRegistry", () => {
  it("prepares a token, then confirm executes exactly once", async () => {
    const { reg } = registryWithClock();
    const execute = vi.fn(async () => ({ done: true }));
    const prepared = reg.prepare({ tool: "t", args: { id: 1 }, actionSummary: "Do thing", userHash: USER_A, execute });

    expect(prepared.requires_confirmation).toBe(true);
    expect(prepared.confirm_token).toHaveLength(48);

    const result = await reg.confirm(prepared.confirm_token, USER_A);
    expect(result).toEqual({ done: true });
    expect(execute).toHaveBeenCalledTimes(1);

    await expect(reg.confirm(prepared.confirm_token, USER_A)).rejects.toThrow("Unknown confirmation token");
  });

  it("cancel invalidates the token without executing", async () => {
    const { reg } = registryWithClock();
    const execute = vi.fn(async () => ({}));
    const prepared = reg.prepare({ tool: "t", args: {}, actionSummary: "x", userHash: USER_A, execute });

    await reg.cancel(prepared.confirm_token, USER_A);
    expect(execute).not.toHaveBeenCalled();
    await expect(reg.confirm(prepared.confirm_token, USER_A)).rejects.toThrow("Unknown confirmation token");
  });

  it("expires tokens after the TTL", async () => {
    const { reg, advance } = registryWithClock(300_000);
    const first = reg.prepare({ tool: "t", args: {}, actionSummary: "x", userHash: USER_A, execute: async () => ({}) });

    advance(299_000);
    await expect(reg.confirm(first.confirm_token, USER_A)).resolves.toEqual({});

    const second = reg.prepare({ tool: "t", args: {}, actionSummary: "y", userHash: USER_A, execute: async () => ({}) });
    advance(301_000);
    await expect(reg.confirm(second.confirm_token, USER_A)).rejects.toThrow("Confirmation expired");
  });

  it("rejects tokens from a different user context", async () => {
    const { reg } = registryWithClock();
    const prepared = reg.prepare({ tool: "t", args: {}, actionSummary: "x", userHash: USER_A, execute: async () => ({}) });
    await expect(reg.confirm(prepared.confirm_token, USER_B)).rejects.toThrow("different user context");
    await expect(reg.cancel(prepared.confirm_token, USER_B)).rejects.toThrow("different user context");
  });

  it("rejects unknown tokens", async () => {
    const { reg } = registryWithClock();
    await expect(reg.confirm("deadbeef".repeat(6), USER_A)).rejects.toThrow("Unknown confirmation token");
  });
});
