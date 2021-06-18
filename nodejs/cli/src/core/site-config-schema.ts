let SiteConfigSchema = {
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
        }
    }
};


export default SiteConfigSchema;
