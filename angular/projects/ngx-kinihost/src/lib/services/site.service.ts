import {Injectable} from '@angular/core';
import {KinihostModuleConfig} from '../ngx-kinihost.module';
import {BehaviorSubject} from 'rxjs';
import {MatSnackBar} from '@angular/material/snack-bar';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class SiteService {

    constructor(private http: HttpClient,
                private snackBar: MatSnackBar,
                private config: KinihostModuleConfig) {

    }

    public getSites(searchString = '', limit = '10', offset = '0') {
        return this.http.get(this.config.accessHttpURL + '/site/list', {
            params: {searchString, limit, offset}
        });
    }

    public createSite(newTitle: string) {
        return this.http.post(this.config.accessHttpURL + '/site', {
            title: newTitle
        }).toPromise();
    }

    public saveSite(site) {
        return this.http.post(this.config.accessHttpURL + '/site/save', site)
            .toPromise();
    }

    public saveSiteDomains(siteDomains, siteKey) {
        return this.http.post(this.config.accessHttpURL + '/site/siteDomains?siteKey=' + siteKey, siteDomains)
            .toPromise().then(this.getSite);
    }

    public removeSiteDomain(siteDomainId) {
        return this.http.get(this.config.accessHttpURL + '/site/removeSiteDomain', {
            params: {siteDomainId}
        }).toPromise();
    }

    public getSite(siteKey) {
        return this.http.get(this.config.accessHttpURL + '/site', {
            params: {siteKey}
        }).toPromise();
    }

    public getSiteVersions(siteKey) {
        return this.http.get(this.config.accessHttpURL + '/site/versions', {
            params: {siteKey}
        }).toPromise();
    }

    public getSiteSettings(siteKey) {
        return this.http.get(this.config.accessHttpURL + '/site/settings', {
            params: {siteKey}
        }).toPromise();
    }

    public updateSiteSettings(siteKey, siteSettings) {
        return this.http.post(this.config.accessHttpURL +
            '/site/updateSettings?siteKey=' + siteKey, siteSettings)
            .toPromise().then(() => {
                this.snackBar.open(
                    'Settings have been saved. You will be notified by email once they have' +
                    ' been successfully applied.',
                    'Close',
                    {duration: 3000, verticalPosition: 'top'}
                );
            });
    }

    public updateMaintenanceMode(siteKey, mode) {
        const maintenanceMode = mode ? '1' : '0';
        return this.http.get(this.config.accessHttpURL + '/site/maintenance', {
            params: {siteKey, maintenanceMode}
        }).toPromise();
    }


}
