import { z } from "zod";

export const IdParam = z.number().int().positive();

export const PageSchema = {
  page: z.number().int().positive().optional().describe("1-based page number (default 1)"),
  per_page: z.number().int().min(1).max(20).optional().describe("Records per page (max 20)"),
};

export const SearchSchema = {
  search: z.string().trim().min(1).max(100).optional(),
};

export const RealtorLeadStatus = z.enum(["new", "contacted", "qualified", "proposal", "negotiation", "won", "lost"]);
export const EnterpriseLeadStage = z.enum([
  "new", "contacted", "qualified", "site_visit_scheduled", "site_visit_completed",
  "negotiation", "won", "lost", "on_hold",
]);
export const Priority = z.enum(["low", "medium", "high", "urgent"]);
export const TaskStatus = z.enum(["pending", "in_progress", "completed", "cancelled"]);
export const VisitStatus = z.enum(["scheduled", "completed", "cancelled", "no_show"]);
export const CallStatus = z.enum([
  "initiating", "ringing", "in_progress", "completed", "no_answer", "failed", "canceled",
]);
export const CallDisposition = z.enum([
  "connected", "not_connected", "busy", "wrong_number", "call_back",
  "interested", "not_interested", "converted", "other",
]);

/** Masks phone numbers in list responses unless the caller opts out. */
export const MaskingSchema = {
  show_phone: z.boolean().optional().describe("Set true to include full phone numbers (default: masked)"),
};

export function nonEmpty<T extends Record<string, unknown>>(obj: T): Partial<T> {
  const out: Partial<T> = {};
  for (const [key, value] of Object.entries(obj)) {
    if (value !== undefined && value !== null && value !== "") {
      (out as Record<string, unknown>)[key] = value;
    }
  }
  return out;
}
