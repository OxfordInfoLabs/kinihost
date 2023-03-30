import {Injectable} from '@angular/core';
import {KinihostModuleConfig} from '../ngx-kinihost.module';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class SourceService {

    constructor(private config: KinihostModuleConfig,
                private http: HttpClient) {
    }

    public listFolder(siteKey, subFolder?) {
        const params: any = {siteKey};
        if (subFolder) {
            params.subFolder = subFolder;
        }

        return this.http.get(this.config.adminHttpURL + '/account/staticwebsite/source/list', {
            params
        }).toPromise();
    }
}
