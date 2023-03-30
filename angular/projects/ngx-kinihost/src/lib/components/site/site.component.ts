import {Component, OnDestroy, OnInit} from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { SiteService } from '../../services/site.service';
import { Subscription } from 'rxjs';
import { BuildService } from '../../services/build.service';
import * as moment from 'moment';
import {MatSlideToggleChange} from '@angular/material/slide-toggle';
import {MatDialog} from '@angular/material/dialog';
import {DomainsComponent} from './domains/domains.component';
import {PageSettingsComponent} from './page-settings/page-settings.component';
import {BuildsComponent} from './builds/builds.component';
import {SourceFilesComponent} from './source-files/source-files.component';
import {VersionsComponent} from './versions/versions.component';

@Component({
    selector: 'kh-site',
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
                private buildService: BuildService,
                private dialog: MatDialog) {
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
            });
    }

    public manageDomains() {
        const dialogRef = this.dialog.open(DomainsComponent, {
            width: '900px',
            height: '900px',
            data: {
                site: this.site
            }
        });

        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.loadSite(this.site.siteKey);
            }
        });
    }

    public changeMaintenanceMode(event: MatSlideToggleChange) {
        this.siteService.updateMaintenanceMode(this.site.siteKey, event.checked);
    }

    public managePageSettings() {
        const dialogRef = this.dialog.open(PageSettingsComponent, {
            width: '900px',
            height: '700px',
            data: {
                site: this.site
            }
        });

        dialogRef.afterClosed().subscribe(res => {
            if (res) {
                this.loadSite(this.site.siteKey);
            }
        });
    }

    public viewSourceFiles() {
        const dialogRef = this.dialog.open(SourceFilesComponent, {
            width: '1000px',
            height: '1000px',
            data: {
                site: this.site
            }
        });
    }

    public viewBuilds() {
        const dialogRef = this.dialog.open(BuildsComponent, {
            width: '1000px',
            height: '1000px',
            data: {
                site: this.site
            }
        });
    }

    public viewVersions() {
        const dialogRef = this.dialog.open(VersionsComponent, {
            width: '1000px',
            height: '1000px',
            data: {
                site: this.site
            }
        });
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
        }).catch(e => {
            this.loadingVersions = false;
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
