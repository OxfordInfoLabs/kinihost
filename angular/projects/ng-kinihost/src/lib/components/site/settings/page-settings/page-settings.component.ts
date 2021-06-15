import {Component, Input, OnInit} from '@angular/core';
import { Subscription } from 'rxjs';
import {SiteService} from '../../../../services/site.service';

@Component({
    selector: 'app-page-settings',
    templateUrl: './page-settings.component.html',
    styleUrls: ['./page-settings.component.sass']
})
export class PageSettingsComponent implements OnInit {

    public site: any;
    public siteSettings: any;
    public additionalInfo = false;

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
