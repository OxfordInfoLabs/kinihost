import {Component, Inject, OnInit} from '@angular/core';
import * as lodash from 'lodash';
const _ = lodash.default;
import {SiteService} from '../../../services/site.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

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

    constructor(private siteService: SiteService,
                public dialogRef: MatDialogRef<DomainsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit() {
        this.site = _.cloneDeep(this.data.site);
        if (this.site.siteDomains.length) {
            this.siteDomains = _.orderBy(this.site.siteDomains, ['type'], ['asc']);
        }

        this.setSiteDomains();
    }

    public removeSiteDomain(index) {
        const message = 'Are you sure you would like to remove this domain from your site?';
        if (window.confirm(message)) {
            this.siteDomains.splice(index, 1);
            this.setSiteDomains();
        }
    }

    public async saveSiteDomains() {
        const domainNames = _(this.siteDomains)
            .map('domainName')
            .filter()
            .valueOf();
        await this.siteService.saveSiteDomains(domainNames, this.site.siteKey);
        this.dialogRef.close(true);
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
