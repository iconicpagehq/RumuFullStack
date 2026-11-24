import { loadGatewayConfig } from "./config.js";
import { startGateway } from "./http.js";

startGateway(loadGatewayConfig());
