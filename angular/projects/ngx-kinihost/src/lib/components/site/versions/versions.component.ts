import {Component, Inject, OnInit} from '@angular/core';
import * as moment from 'moment';
import {SiteService} from '../../../services/site.service';
import * as lodash from 'lodash';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {BuildService} from '../../../services/build.service';
const _ = lodash.default;

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

    constructor(private siteService: SiteService,
                private buildService: BuildService,
                public dialogRef: MatDialogRef<VersionsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit() {
        this.site = _.cloneDeep(this.data.site);
        this.siteService.getSiteVersions(this.site.siteKey).then((versions: any) => {
            if (versions.length) {
                this.versions = versions;
            }
            this.loading = false;
        });
    }

    public revertVersion(version) {
        const message = 'Are you sure you want to revert your site to this version?';
        if (window.confirm(message)) {
            this.buildService.createVersionRevertBuild(this.site.siteKey, version)
                .then(() => {
                    this.dialogRef.close(true);
                });
        }
    }

}
