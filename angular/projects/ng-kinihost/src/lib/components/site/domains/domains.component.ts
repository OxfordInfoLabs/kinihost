import {Component, Input, OnInit} from '@angular/core';
import { Subscription } from 'rxjs';
import * as _ from 'lodash';
import {SiteService} from '../../../services/site.service';

@Component({
    selector: 'app-domains',
    templateUrl: './domains.component.html',
    styleUrls: ['./domains.component.sass']
})
export class DomainsComponent implements OnInit {

    public site: any;
    public siteDomains: any[] = [];
    public maxRedirects = 5;
    public _ = _;

    private siteSub: Subscription;

    constructor(private siteService: SiteService) {
    }

    ngOnInit() {
        this.siteSub = this.siteService.activeSite.subscribe(site => {
            this.site = site;
            if (site.siteDomains.length) {
                this.siteDomains = site.siteDomains;
            }

            this.setSiteDomains();
        });
    }

    ngOnDestroy(): void {
        this.siteSub.unsubscribe();
    }


    public removeSiteDomain(index) {
        const message = 'Are you sure you would like to remove this domain from your site?';
        if (window.confirm(message)) {
            this.siteDomains.splice(index, 1);
            this.setSiteDomains();
        }
    }

    public saveSiteDomains() {
        const domainNames = _(this.siteDomains)
            .map('domainName')
            .reject(_.isNull)
            .valueOf();
        this.siteService.saveSiteDomains(domainNames, this.site.siteKey);
    }

    private setSiteDomains() {
        for (let i = this.siteDomains.length; i < this.maxRedirects; i++) {
            this.siteDomains.push({
                domainName: null,
                status: null
            });
        }
    }
}
