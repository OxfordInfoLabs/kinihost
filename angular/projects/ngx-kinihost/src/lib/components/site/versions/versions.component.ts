import {Component, Input, OnInit} from '@angular/core';
import { Subscription } from 'rxjs';
import * as moment from 'moment';
import {SiteService} from '../../../services/site.service';

@Component({
    selector: 'app-versions',
    templateUrl: './versions.component.html',
    styleUrls: ['./versions.component.sass']
})
export class VersionsComponent implements OnInit {

    public site: any;
    public versions: any = [];
    public moment = moment;
    public loading: boolean;

    private siteSub: Subscription;

    constructor(private siteService: SiteService) {
    }

    ngOnInit() {
        this.siteSub = this.siteService.activeSite.subscribe(site => {
            this.site = site;
            this.loading = true;
            this.getVersions(site);
        });
    }

    ngOnDestroy(): void {
        this.siteSub.unsubscribe();
    }

    private getVersions(site) {
        return this.siteService.getSiteVersions(site.siteKey).then((versions: any) => {
            if (versions.length) {
                this.versions = versions;
            }
            this.loading = false;
            return site;
        })
    }


}
