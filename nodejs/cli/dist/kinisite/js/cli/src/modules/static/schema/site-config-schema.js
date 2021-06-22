"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
var SiteConfigSchema = {
    "id": "/SiteConfig",
    "type": "object",
    "properties": {
        "deployment": {
            "type": "object",
            "properties": {
                "type": {
                    "type": "string"
                },
                "siteKey": {
                    "type": "string"
                },
                "title": {
                    "type": "string"
                },
                "description": {
                    "type": "string"
                },
                "heroImage": {
                    "type": "string"
                },
                "thumbnailImage": {
                    "type": "string"
                },
                "tags": {
                    "type": "array",
                    "items": {
                        "type": "string"
                    }
                },
                "publishDirectory": {
                    "type": ["null", "string"]
                },
                "indexPage": {
                    "type": "string"
                },
                "notFoundPage": {
                    "type": "string"
                }
            },
            "dependencies": {
                "type": ["siteKey", "title"]
            }
        },
        "localServer": {
            "type": "object",
            "properties": {
                "port": {
                    "type": "integer"
                },
                "dataSource": {
                    "type": "string",
                    "enum": ["bootstrap", "remote"]
                },
                "processors": {
                    "type": "object",
                    "patternProperties": {
                        "^.*$": {
                            "type": "object",
                            "properties": {
                                "files": {
                                    "type": "array",
                                    "items": {
                                        "type": "string"
                                    }
                                },
                                "command": {
                                    "type": "string"
                                }
                            },
                            "required": [
                                "files", "command"
                            ]
                        }
                    }
                }
            }
        }
    }
};
exports.default = SiteConfigSchema;
