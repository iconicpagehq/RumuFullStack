const SENSITIVE_KEYS = new Set([
  "recording_url",
  "audio_url",
  "signed_url",
  "playback_url",
  "download_url",
  "token",
  "access_token",
  "refresh_token",
  "password",
  "secret",
]);

/**
 * Remove raw-recording URLs and any token/secret material from API payloads
 * before they are returned to the LLM. CRM records are untrusted content and
 * must never carry playable audio links or credentials into the model context.
 */
export function redactSensitive(value: unknown): unknown {
  if (Array.isArray(value)) {
    return value.map(redactSensitive);
  }
  if (value !== null && typeof value === "object") {
    const out: Record<string, unknown> = {};
    for (const [key, inner] of Object.entries(value as Record<string, unknown>)) {
      const lower = key.toLowerCase();
      if (SENSITIVE_KEYS.has(lower) || lower.endsWith("_token") || lower.endsWith("_secret")) {
        continue;
      }
      if (lower === "recording" && inner !== null && typeof inner === "object") {
        const rec: Record<string, unknown> = {};
        for (const [rk, rv] of Object.entries(inner as Record<string, unknown>)) {
          if (rk.toLowerCase() === "url") continue;
          rec[rk] = redactSensitive(rv);
        }
        out[key] = rec;
        continue;
      }
      out[key] = redactSensitive(inner);
    }
    return out;
  }
  return value;
}

export function maskPhone(phone: unknown): unknown {
  if (typeof phone !== "string" || phone.length < 4) return phone;
  return `******${phone.slice(-4)}`;
}

export function maskPhonesInList(items: unknown[]): unknown[] {
  return items.map((item) => {
    if (item !== null && typeof item === "object" && !Array.isArray(item)) {
      const out: Record<string, unknown> = { ...(item as Record<string, unknown>) };
      if ("phone" in out) out.phone = maskPhone(out.phone);
      return out;
    }
    return item;
  });
}
