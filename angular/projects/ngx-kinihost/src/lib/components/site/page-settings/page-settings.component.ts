import {Component, Inject, OnInit} from '@angular/core';
import {SiteService} from '../../../services/site.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'app-page-settings',
    templateUrl: './page-settings.component.html',
    styleUrls: ['./page-settings.component.sass']
})
export class PageSettingsComponent implements OnInit {

    public site: any;
    public siteSettings: any;

    constructor(private siteService: SiteService,
                public dialogRef: MatDialogRef<PageSettingsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit() {
        this.site = _.cloneDeep(this.data.site);
        this.siteService.getSiteSettings(this.site.siteKey).then(siteSettings => {
            this.siteSettings = siteSettings;
        });
    }

    public async saveSiteSettings() {
        await this.siteService.updateSiteSettings(this.site.siteKey, this.siteSettings);
        this.dialogRef.close(true);
    }

}
