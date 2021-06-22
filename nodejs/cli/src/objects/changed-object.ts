/**
 * Changed object type
 */
export default class ChangedObject {

    private _objectKey: string;
    private _changeType: string;
    private _md5Hash: string;


    constructor(objectKey: string, changeType: string, md5Hash?: string) {
        this._objectKey = objectKey;
        this._changeType = changeType;
        this._md5Hash = md5Hash ? md5Hash : "";
    }


    get objectKey(): string {
        return this._objectKey;
    }

    get changeType(): string {
        return this._changeType;
    }

    get md5Hash(): string {
        return this._md5Hash;
    }
}
