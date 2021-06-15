import {Injectable} from '@angular/core';
import {KinihostModuleConfig} from '../ng-kinihost.module';
import {HttpClient} from '@angular/common/http';
import {map, switchMap} from 'rxjs/operators';
import {interval} from 'rxjs';

@Injectable({
    providedIn: 'root'
})
export class BuildService {

    constructor(private config: KinihostModuleConfig,
                private http: HttpClient) {
    }

    public getBuilds(site, limit = '5') {
        return this.http.get(this.config.accessHttpURL + '/account/staticwebsite/build/list', {
            params: {
                siteId: site.siteId,
                limit
            }
        }).toPromise();
    }

    public getBuild(buildId) {
        return this.http.get(this.config.accessHttpURL + '/account/staticwebsite/build', {
            params: {
                buildId
            }
        }).toPromise();
    }

    public watchSiteBuilds(site) {
        return interval(5000)
            .pipe(
                switchMap(() =>
                    this.http.get(this.config.accessHttpURL + '/account/staticwebsite/build/list', {
                        params: {
                            siteId: site.siteId
                        }
                    }).pipe(
                        map(result => {
                            return result;
                        }))
                )
            );
    }

    public createProductionBuild(siteKey) {
        return this.http.get(this.config.accessHttpURL + '/account/staticwebsite/build/production/' + siteKey)
            .toPromise();
    }

    public createVersionRevertBuild(siteKey, targetVersion) {
        return this.http.get(this.config.accessHttpURL + '/account/staticwebsite/build/versionRevert/' + siteKey + '/' + targetVersion)
            .toPromise();
    }
}
