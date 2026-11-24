import { describe, expect, it } from "vitest";
import { maskPhone, maskPhonesInList, redactSensitive } from "../src/utils/redaction.js";
import { buildListResult, compactScalars, pickAffectedId } from "../src/utils/pagination.js";

describe("redactSensitive", () => {
  it("removes recording playback URLs but keeps availability metadata", () => {
    const payload = {
      id: 5,
      recording: {
        status: "available",
        url: "/realtor/calls/5/recording",
        duration_seconds: 90,
      },
    };
    const out = redactSensitive(payload) as Record<string, unknown>;
    const rec = out.recording as Record<string, unknown>;
    expect(rec.status).toBe("available");
    expect(rec.duration_seconds).toBe(90);
    expect(rec.url).toBeUndefined();
  });

  it("strips tokens, secrets, and signed URLs anywhere in the payload", () => {
    const payload = {
      data: { access_token: "abc", meta: { razorpay_secret: "x", signed_url: "https://..." }, note: "hello" },
    };
    const out = redactSensitive(payload) as { data: Record<string, unknown> };
    expect(out.data.access_token).toBeUndefined();
    expect((out.data.meta as Record<string, unknown>).razorpay_secret).toBeUndefined();
    expect((out.data.meta as Record<string, unknown>).signed_url).toBeUndefined();
    expect(out.data.note).toBe("hello");
  });

  it("leaves ordinary fields untouched", () => {
    const payload = { lead: { id: 1, name: "Riya", campaign: { id: 2, name: "Skyline" } } };
    expect(redactSensitive(payload)).toEqual(payload);
  });
});

describe("masking", () => {
  it("masks phone numbers keeping the last 4 digits", () => {
    expect(maskPhone("9876543210")).toBe("******3210");
    expect(maskPhone("12")).toBe("12");
    expect(maskPhone(null)).toBe(null);
  });

  it("masks phones inside list items", () => {
    const items = maskPhonesInList([
      { id: 1, name: "A", phone: "9876543210" },
      { id: 2, name: "B" },
      "plain",
    ]);
    expect(items[0]).toEqual({ id: 1, name: "A", phone: "******3210" });
    expect(items[1]).toEqual({ id: 2, name: "B" });
    expect(items[2]).toBe("plain");
  });
});

describe("pagination helpers", () => {
  it("builds next_cursor/has_more from meta", () => {
    const page1 = buildListResult([1, 2], { current_page: 1, last_page: 3, total: 6 });
    expect(page1.data.has_more).toBe(true);
    expect(page1.data.next_cursor).toBe(2);
    const last = buildListResult([6], { current_page: 3, last_page: 3, total: 6 });
    expect(last.data.has_more).toBe(false);
    expect(last.data.next_cursor).toBeNull();
  });

  it("returns an honest empty result instead of fabricating data", () => {
    const empty = buildListResult([], { current_page: 1, last_page: 1, total: 0 });
    expect(empty.summary).toBe("No matching records found.");
    expect(empty.data.count).toBe(0);
  });

  it("pickAffectedId finds ids directly or on common nested keys", () => {
    expect(pickAffectedId({ id: 9 })).toBe(9);
    expect(pickAffectedId({ note: { id: 4 } })).toBe(4);
    expect(pickAffectedId({ nothing: true })).toBeUndefined();
  });

  it("compactScalars keeps scalars and status maps, drops arrays/objects", () => {
    const out = compactScalars({ total: 5, name: "x", recent: [1, 2], by_status: { completed: 3 }, nested: { a: 1 } });
    expect(out).toEqual({ total: 5, name: "x", by_status: { completed: 3 } });
  });
});
