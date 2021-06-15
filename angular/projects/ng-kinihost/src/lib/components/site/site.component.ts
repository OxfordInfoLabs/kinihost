import {Component, OnDestroy, OnInit} from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { SiteService } from '../../services/site.service';
import { Subscription } from 'rxjs';
import { BuildService } from '../../services/build.service';
import * as moment from 'moment';

@Component({
    selector: 'app-site',
    templateUrl: './site.component.html',
    styleUrls: ['./site.component.sass']
})
export class SiteComponent implements OnInit, OnDestroy {

    public site;
    public versions: any = [];
    public builds: any = [];
    public lastBuild: any = null;
    public previewStatus = 'remove';
    public productionStatus = 'remove';
    public updateInProgress = false;
    public moment = moment;
    public loadingVersions = true;

    private routeSub: Subscription;
    private buildSub: Subscription;

    constructor(private route: ActivatedRoute,
                private siteService: SiteService,
                private buildService: BuildService) {
    }

    ngOnInit() {
        this.routeSub = this.route.params.subscribe(res => {
            if (this.buildSub) {
                this.buildSub.unsubscribe();
            }
            this.loadSite(res.siteKey);
        });
    }

    ngOnDestroy(): void {
        this.routeSub.unsubscribe();
        if (this.buildSub) {
            this.buildSub.unsubscribe();
        }
    }

    public revertVersion(version) {
        const message = 'Are you sure you want to revert your site to this version?';
        if (window.confirm(message)) {
            this.buildService.createVersionRevertBuild(this.site.siteKey, version)
                .then(() => {
                    this.loadSite(this.site.siteKey);
                });
        }
    }

    public updateProduction() {
        this.updateInProgress = true;
        this.buildService.createProductionBuild(this.site.siteKey)
            .then(() => {
                this.updateInProgress = false;
                this.loadSite(this.site.siteKey);
            })
    }

    private loadSite(siteKey) {
        this.builds = [];
        this.lastBuild = null;
        this.previewStatus = 'remove';
        this.productionStatus = 'remove';
        this.siteService.getSite(siteKey)
            .then(site => {
                this.site = site;
                this.buildService.getBuilds(site).then(this.setBuildStatus.bind(this));
                this.buildSub = this.buildService.watchSiteBuilds(site)
                    .subscribe(this.setBuildStatus.bind(this));

                return site;
            })
            .then(this.getVersions.bind(this));
    }

    private getVersions(site) {
        this.versions = [];
        this.loadingVersions = true;
        return this.siteService.getSiteVersions(site.siteKey).then((versions: any) => {
            this.versions = versions.slice(0, 5);
            this.loadingVersions = false;
            return site;
        });
    }

    private setBuildStatus(builds) {
        if (builds.length) {
            this.builds = builds;
            this.lastBuild = builds[0];

            switch (this.lastBuild.buildTarget) {
                case 'PREVIEW':
                    this.productionStatus = 'warning';
                    this.previewStatus = this.lastBuild.status === 'SUCCEEDED' ? 'done' : this.lastBuild.status;
                    break;
                case 'PRODUCTION':
                    this.previewStatus = 'done';
                    this.productionStatus = this.lastBuild.status === 'SUCCEEDED' ? 'done' : this.lastBuild.status;
                    break;
            }
        }
    }
}
