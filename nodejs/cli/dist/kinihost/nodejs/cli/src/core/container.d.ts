export default class Container {
    /**
     * Repository of instances indexed ny class type
     *
     * @private
     */
    private static _instances;
    private static _instanceClasses;
    /**
     * Get an instance by class name
     *
     * @param className
     */
    static getInstance(className: string): any;
    /**
     * Set an instance class
     *
     * @param baseClassName
     * @param newInstanceClassName
     */
    static setInstanceClass(baseClassName: string, newInstanceClassName: string): void;
    private static initialiseInstanceClasses;
}
