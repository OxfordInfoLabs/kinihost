#!/usr/bin/env node
"use strict";
var __importDefault = (this && this.__importDefault) || function (mod) {
    return (mod && mod.__esModule) ? mod : { "default": mod };
};
Object.defineProperty(exports, "__esModule", { value: true });
// Construct the kinihost CLI
var kinihost_cli_1 = __importDefault(require("kinihost-cli/dist/kinihost-cli"));
new kinihost_cli_1.default("http://localhost:3000", "kinihost-example.json", "Kinihost Example");
