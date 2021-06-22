declare let SiteConfigSchema: {
    id: string;
    type: string;
    properties: {
        deployment: {
            type: string;
            properties: {
                type: {
                    type: string;
                };
                siteKey: {
                    type: string;
                };
                title: {
                    type: string;
                };
                description: {
                    type: string;
                };
                heroImage: {
                    type: string;
                };
                thumbnailImage: {
                    type: string;
                };
                tags: {
                    type: string;
                    items: {
                        type: string;
                    };
                };
                publishDirectory: {
                    type: string[];
                };
                indexPage: {
                    type: string;
                };
                notFoundPage: {
                    type: string;
                };
            };
            dependencies: {
                type: string[];
            };
        };
        localServer: {
            type: string;
            properties: {
                port: {
                    type: string;
                };
                dataSource: {
                    type: string;
                    enum: string[];
                };
                processors: {
                    type: string;
                    patternProperties: {
                        "^.*$": {
                            type: string;
                            properties: {
                                files: {
                                    type: string;
                                    items: {
                                        type: string;
                                    };
                                };
                                command: {
                                    type: string;
                                };
                            };
                            required: string[];
                        };
                    };
                };
            };
        };
    };
};
export default SiteConfigSchema;
