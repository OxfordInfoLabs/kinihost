import {Component, Input, OnInit} from '@angular/core';
import {Subscription} from 'rxjs';
import * as moment from 'moment';
import {SiteService} from '../../../services/site.service';
import {BuildService} from '../../../services/build.service';

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

    private siteSub: Subscription;

    constructor(private siteService: SiteService,
                private buildService: BuildService) {
    }

    ngOnInit() {
        this.siteSub = this.siteService.activeSite.subscribe(site => {
            this.site = site;
            this.loading = true;
            this.getBuilds(site);
        });
    }

    ngOnDestroy(): void {
        this.siteSub.unsubscribe();
    }

    private getBuilds(site) {
        return this.buildService.getBuilds(site, 1000).then((builds: any) => {
            if (builds.length) {
                this.builds = builds;
            }
            this.loading = false;
            return site;
        })
    }

}
