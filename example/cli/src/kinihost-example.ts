#!/usr/bin/env node

// Construct the kinihost CLI
import KinihostCli from "kinihost-cli/dist/kinihost-cli";

new KinihostCli("http://localhost:3050", "kinihost-example.json", "Kinihost Example");