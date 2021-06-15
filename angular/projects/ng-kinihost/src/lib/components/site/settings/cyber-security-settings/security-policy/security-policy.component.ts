import {Component, Input, OnInit} from '@angular/core';
import { Subscription } from 'rxjs';
import {SiteService} from '../../../../../services/site.service';

@Component({
    selector: 'app-security-policy',
    templateUrl: './security-policy.component.html',
    styleUrls: ['./security-policy.component.sass']
})
export class SecurityPolicyComponent implements OnInit {

    public site: any;
    public siteSettings: any;

    private siteSub: Subscription;

    constructor(private siteService: SiteService) {
    }

    ngOnInit() {
        this.siteSub = this.siteService.activeSite.subscribe(site => {
            this.site = site;
            if (site) {
                this.loadSiteSettings();
            }
        });
    }

    public saveSiteSettings() {
        this.siteService.updateSiteSettings(this.site.siteKey, this.siteSettings);
    }

    private loadSiteSettings() {
        this.siteService.getSiteSettings(this.site.siteKey).then(siteSettings => {
            this.siteSettings = siteSettings;
        });
    }
}
