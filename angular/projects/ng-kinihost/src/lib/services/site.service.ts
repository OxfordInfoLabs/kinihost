import {Injectable} from '@angular/core';
import {KinihostModuleConfig} from '../ng-kinihost.module';
import {BehaviorSubject} from 'rxjs';
import {MatSnackBar} from '@angular/material/snack-bar';
import {HttpClient} from '@angular/common/http';

@Injectable({
    providedIn: 'root'
})
export class SiteService {

    public activeSite: BehaviorSubject<any> = new BehaviorSubject<any>(null);

    constructor(private http: HttpClient,
                private snackBar: MatSnackBar,
                private config: KinihostModuleConfig) {

        const activeSite = sessionStorage.getItem('activeSite');
        if (activeSite) {
            this.setActiveSite(JSON.parse(activeSite));
        }

    }

    public getSites(searchString = '', limit = '10', offset = '0') {
        return this.http.get(this.config.accessHttpURL + '/site/list', {
            params: {searchString, limit, offset}
        });
    }

    public saveSite(site) {
        return this.http.post(this.config.accessHttpURL + '/site/save', site)
            .toPromise().then(() => {
                this.setActiveSite(site);
                return site;
            });
    }

    public saveSiteDomains(siteDomains, siteKey) {
        return this.http.post(this.config.accessHttpURL + '/site/siteDomains?siteKey=' + siteKey, siteDomains)
            .toPromise().then(() => {
                this.getSite(this.activeSite.getValue().siteKey);
            });
    }

    public removeSiteDomain(siteDomainId) {
        return this.http.get(this.config.accessHttpURL + '/site/removeSiteDomain', {
            params: {
                siteDomainId
            }
        }).toPromise().then(() => {
            this.getSite(this.activeSite.getValue().siteKey);
        });
    }

    public getSite(siteKey) {
        return this.http.get(this.config.accessHttpURL + '/site', {
            params: { siteKey }
        }).toPromise().then(site => {
            this.setActiveSite(site);
            return site;
        });
    }

    public getSiteVersions(siteKey) {
        return this.http.get(this.config.accessHttpURL + '/site/versions', {
            params: {
                siteKey
            }
        })
            .toPromise();
    }

    public getSiteSettings(siteKey) {
        return this.http.get(this.config.accessHttpURL + '/site/settings', {
            params: {
                siteKey
            }
        })
            .toPromise();
    }

    public updateSiteSettings(siteKey, siteSettings) {
        return this.http.post(this.config.accessHttpURL +
            '/site/updateSettings?siteKey=' + siteKey, siteSettings)
            .toPromise().then(() => {
                this.snackBar.open(
                    'Settings have been saved. You will be notified by email once they have' +
                    ' been successfully applied.',
                    'Close',
                    { duration: 3000, verticalPosition: 'top' }
                );
            });
    }

    public updateMaintenanceMode(siteKey, mode) {
        const maintenanceMode = mode ? '1' : '0';
        return this.http.get(this.config.accessHttpURL + '/site/maintenance', {
            params: {
                siteKey, maintenanceMode
            }
        })
            .toPromise().then(site => {
                return this.setActiveSite(site);
            });
    }

    public setActiveSite(site) {
        if (!site) {
            sessionStorage.removeItem('activeSite');
            this.activeSite.next(null);
        } else {
            sessionStorage.setItem('activeSite', JSON.stringify(site));
            this.activeSite.next(site);
        }
    }


}
