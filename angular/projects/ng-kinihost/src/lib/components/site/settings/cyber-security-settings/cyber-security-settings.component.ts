import {Component, Input, OnInit} from '@angular/core';
import { Subscription } from 'rxjs';
import {SiteService} from '../../../../services/site.service';

@Component({
    selector: 'app-cyber-security-settings',
    templateUrl: './cyber-security-settings.component.html',
    styleUrls: ['./cyber-security-settings.component.sass']
})
export class CyberSecuritySettingsComponent implements OnInit {

    public site: any;

    private siteSub: Subscription;

    constructor(private siteService: SiteService) {
    }

    ngOnInit() {
        this.siteSub = this.siteService.activeSite.subscribe(site => {
            this.site = site;
        });
    }

}
