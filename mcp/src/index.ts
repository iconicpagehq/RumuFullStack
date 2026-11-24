import { StdioServerTransport } from "@modelcontextprotocol/sdk/server/stdio.js";
import { loadConfig } from "./config.js";
import { buildServer } from "./server.js";

async function main(): Promise<void> {
  const config = loadConfig();
  const server = buildServer({ config });
  const transport = new StdioServerTransport();
  await server.connect(transport);
  console.error(`rumu-mcp connected (api: ${config.apiBase})`);
}

main().catch((err) => {
  console.error("rumu-mcp failed to start:", err instanceof Error ? err.message : err);
  process.exit(1);
});
