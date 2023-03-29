import {Component, Inject, Input, OnInit} from '@angular/core';
import {Subscription} from 'rxjs';
import * as moment from 'moment';
import {SiteService} from '../../../services/site.service';
import {BuildService} from '../../../services/build.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import * as lodash from 'lodash';
const _ = lodash.default;

@Component({
    selector: 'app-builds',
    templateUrl: './builds.component.html',
    styleUrls: ['./builds.component.sass']
})
export class BuildsComponent implements OnInit {

    public site: any;
    public builds: any = [];
    public moment = moment;
    public loading: boolean;

    constructor(private siteService: SiteService,
                private buildService: BuildService,
                public dialogRef: MatDialogRef<BuildsComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit() {
        this.site = _.cloneDeep(this.data.site);
        this.getBuilds(this.site);
    }

    private getBuilds(site) {
        return this.buildService.getBuilds(site, '1000').then((builds: any) => {
            if (builds.length) {
                this.builds = builds;
            }
            this.loading = false;
            return site;
        });
    }

}
