/**
 * Mock inquirer which can be configured with return values etc.
 */
export default class MockInquirer {

    private _promptCalls: any[] = [];
    private _promptResults: any[] = [];

    /**
     * Construct with an optional array of prompt results.
     *
     * @param promptResults
     */
    constructor(promptResults?: any[]) {
        if (promptResults)
            this._promptResults = promptResults;
    }

// Add an expected return value for
    public addExpectedPromptResult(promptResult: any) {
        this._promptResults.push(promptResult);
    }


    get promptCalls(): any[] {
        return this._promptCalls;
    }

    /**
     * Mock implementation of prompt which simply returns a promise immediately with the
     * next return value configured
     *
     * @param configuration
     */
    public prompt(configuration: any[]) {
        this._promptCalls.push(configuration);
        return Promise.resolve(this._promptResults.shift());
    }

}
