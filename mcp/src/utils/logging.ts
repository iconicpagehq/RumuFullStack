import { appendFileSync, existsSync, readFileSync, writeFileSync, mkdirSync } from "node:fs";
import { dirname, resolve } from "node:path";
import { hashArguments } from "../client.js";

export interface AuditEntry {
  timestamp: string;
  tool: string;
  args_hash: string;
  status: "success" | "error";
  affected_id?: number | string;
  error_code?: string;
  duration_ms?: number;
}

/**
 * Local supplemental audit log (JSONL). Never stores tokens, raw args, or CRM
 * payloads — only hashes. The Laravel backend remains the authoritative audit
 * system; retention is bounded by MCP_LOG_RETENTION_DAYS.
 */
export class AuditLog {
  private readonly filePath: string;

  constructor(logPath: string, private readonly retentionDays: number) {
    this.filePath = resolve(process.cwd(), logPath);
    this.prune();
  }

  record(entry: Omit<AuditEntry, "timestamp" | "args_hash"> & { args?: unknown }): void {
    const line: AuditEntry = {
      timestamp: new Date().toISOString(),
      tool: entry.tool,
      args_hash: hashArguments(entry.args ?? {}),
      status: entry.status,
      affected_id: entry.affected_id,
      error_code: entry.error_code,
      duration_ms: entry.duration_ms,
    };
    try {
      mkdirSync(dirname(this.filePath), { recursive: true });
      appendFileSync(this.filePath, JSON.stringify(line) + "\n", "utf8");
    } catch {
      // Logging must never break tool execution.
    }
  }

  private prune(): void {
    if (this.retentionDays <= 0 || !existsSync(this.filePath)) return;
    try {
      const cutoff = Date.now() - this.retentionDays * 24 * 60 * 60 * 1000;
      const lines = readFileSync(this.filePath, "utf8").split("\n").filter(Boolean);
      const kept = lines.filter((line) => {
        try {
          const ts = Date.parse((JSON.parse(line) as { timestamp?: string }).timestamp ?? "");
          return Number.isFinite(ts) && ts >= cutoff;
        } catch {
          return false;
        }
      });
      if (kept.length !== lines.length) {
        writeFileSync(this.filePath, kept.length > 0 ? kept.join("\n") + "\n" : "", "utf8");
      }
    } catch {
      // Best effort only.
    }
  }
}
