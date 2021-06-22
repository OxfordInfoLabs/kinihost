/**
 * Changed object type
 */
export default class ChangedObject {
    private _objectKey;
    private _changeType;
    private _md5Hash;
    constructor(objectKey: string, changeType: string, md5Hash?: string);
    get objectKey(): string;
    get changeType(): string;
    get md5Hash(): string;
}
