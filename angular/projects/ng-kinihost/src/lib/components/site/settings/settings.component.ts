import {Component, Input, OnInit} from '@angular/core';
import { Subscription } from 'rxjs';
import { MatSlideToggleChange } from '@angular/material/slide-toggle';
import {SiteService} from '../../../services/site.service';

@Component({
    selector: 'app-settings',
    templateUrl: './settings.component.html',
    styleUrls: ['./settings.component.sass']
})
export class SettingsComponent implements OnInit {

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

    public changeMaintenanceMode(event: MatSlideToggleChange) {
        this.siteService.updateMaintenanceMode(this.site.siteKey, event.checked);
    }

    private loadSiteSettings() {
        this.siteService.getSiteSettings(this.site.siteKey).then(siteSettings => {
            this.siteSettings = siteSettings;
        });
    }
}
