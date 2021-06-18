/**
 * Container class
 */
import SiteConfig from "./site-config";
import Api from "./api";
import Config from "./config";

export default class Container {

    /**
     * Repository of instances indexed ny class type
     *
     * @private
     */
    private static _instances: any = {};

    // Instance classes
    private static _instanceClasses: any = null;

    /**
     * Get an instance by class name
     *
     * @param className
     */
    public static getInstance(className: string) {
        this.initialiseInstanceClasses();
        if (!this._instances[className]) {
            this._instances[className] = Reflect.construct(this._instanceClasses[className], []);
        }
        return this._instances[className];
    }


    /**
     * Set an instance class
     *
     * @param baseClassName
     * @param newInstanceClassName
     */
    public static setInstanceClass(baseClassName: string, newInstanceClassName: string) {
        this.initialiseInstanceClasses();
        this._instanceClasses[baseClassName] = newInstanceClassName;
    }


    private static initialiseInstanceClasses() {
        if (!this._instanceClasses) {
            this._instanceClasses = {
                "Api": Api,
                "Config": Config,
                "SiteConfig": SiteConfig
            };
        }
        return this._instanceClasses;
    }

}