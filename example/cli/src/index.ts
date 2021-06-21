#!/usr/bin/env node

// Construct the kinihost CLI
import KinihostCli from "kinihost-cli/dist/kinihost-cli";

new KinihostCli("http://localhost:3000", "kinihost-example.json", "Kinihost Example");