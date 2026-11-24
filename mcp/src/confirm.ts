import { createHash, randomBytes } from "node:crypto";

export interface PendingAction {
  tokenHash: string;
  tool: string;
  args: Record<string, unknown>;
  actionSummary: string;
  createdAt: number;
  expiresAt: number;
  userHash: string;
  execute: () => Promise<unknown>;
}

export interface PreparedConfirmation {
  requires_confirmation: true;
  confirm_token: string;
  expires_at: string;
  action_summary: string;
}

export class ConfirmationRegistry {
  private readonly pending = new Map<string, PendingAction>();
  private readonly now: () => number;

  constructor(private readonly ttlMs: number, now?: () => number) {
    this.now = now ?? (() => Date.now());
  }

  prepare(input: {
    tool: string;
    args: Record<string, unknown>;
    actionSummary: string;
    userHash: string;
    execute: () => Promise<unknown>;
  }): PreparedConfirmation {
    this.sweepExpired();
    const token = randomBytes(24).toString("hex");
    const now = this.now();
    this.pending.set(token, {
      tokenHash: hashToken(token),
      tool: input.tool,
      args: input.args,
      actionSummary: input.actionSummary,
      createdAt: now,
      expiresAt: now + this.ttlMs,
      userHash: input.userHash,
      execute: input.execute,
    });
    return {
      requires_confirmation: true,
      confirm_token: token,
      expires_at: new Date(now + this.ttlMs).toISOString(),
      action_summary: input.actionSummary,
    };
  }

  async confirm(token: string, userHash: string): Promise<unknown> {
    const action = this.lookup(token, userHash);
    this.pending.delete(token);
    return action.execute();
  }

  async cancel(token: string, userHash: string): Promise<{ success: true; summary: string }> {
    const action = this.lookup(token, userHash);
    this.pending.delete(token);
    return { success: true, summary: `Cancelled: ${action.actionSummary}` };
  }

  peek(token: string, userHash: string): { tool: string; action_summary: string; expires_at: string } {
    const action = this.lookup(token, userHash);
    return { tool: action.tool, action_summary: action.actionSummary, expires_at: new Date(action.expiresAt).toISOString() };
  }

  size(): number {
    this.sweepExpired();
    return this.pending.size;
  }

  private lookup(token: string, userHash: string): PendingAction {
    const action = this.pending.get(token);
    if (!action || action.tokenHash !== hashToken(token)) {
      throw new Error("Unknown confirmation token. Prepare the action again.");
    }
    if (action.userHash !== userHash) {
      throw new Error("This confirmation token belongs to a different user context.");
    }
    if (this.now() > action.expiresAt) {
      this.pending.delete(token);
      throw new Error("Confirmation expired. Prepare the action again.");
    }
    return action;
  }

  private sweepExpired(): void {
    const now = this.now();
    for (const [token, action] of this.pending) {
      if (now > action.expiresAt) this.pending.delete(token);
    }
  }
}

function hashToken(token: string): string {
  return createHash("sha256").update(token).digest("hex");
}
