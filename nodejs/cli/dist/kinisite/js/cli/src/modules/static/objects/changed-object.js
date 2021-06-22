"use strict";
Object.defineProperty(exports, "__esModule", { value: true });
/**
 * Changed object type
 */
var ChangedObject = /** @class */ (function () {
    function ChangedObject(objectKey, changeType, md5Hash) {
        this._objectKey = objectKey;
        this._changeType = changeType;
        this._md5Hash = md5Hash ? md5Hash : "";
    }
    Object.defineProperty(ChangedObject.prototype, "objectKey", {
        get: function () {
            return this._objectKey;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(ChangedObject.prototype, "changeType", {
        get: function () {
            return this._changeType;
        },
        enumerable: false,
        configurable: true
    });
    Object.defineProperty(ChangedObject.prototype, "md5Hash", {
        get: function () {
            return this._md5Hash;
        },
        enumerable: false,
        configurable: true
    });
    return ChangedObject;
}());
exports.default = ChangedObject;
