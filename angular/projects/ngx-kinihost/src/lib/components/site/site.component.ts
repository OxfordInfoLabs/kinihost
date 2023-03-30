import {Component, OnDestroy, OnInit} from '@angular/core';
import { ActivatedRoute } from '@angular/router';
import { SiteService } from '../../services/site.service';
import { Subscription } from 'rxjs';
import { BuildService } from '../../services/build.service';
import {MatSlideToggleChange} from '@angular/material/slide-toggle';
import {MatDialog} from '@angular/material/dialog';
import {DomainsComponent} from './domains/domains.component';
import {PageSettingsComponent} from './page-settings/page-settings.component';
import {BuildsComponent} from './builds/builds.component';
import {SourceFilesComponent} from './source-files/source-files.component';
import {VersionsComponent} from './versions/versions.component';
import * as moment from 'moment';

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
    public latestProduction = null;
    public latestPreview = null;
    public latestUpload = null;
    public lastUpload: string;

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
            height: '800px',
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

    public async pushProduction() {
        await this.buildService.createProductionBuild(this.site.siteKey);
    }

    public async pushPreview() {
        await this.buildService.createPreviewBuild(this.site.siteKey);
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
            this.versions = versions.slice(0, 4);
            this.loadingVersions = false;
            return site;
        }).catch(e => {
            this.loadingVersions = false;
        });
    }

    private setBuildStatus(builds) {
        if (builds.length) {
            this.builds = builds.slice(0, 4);
            this.builds.map(build => {
                if (build.completedDate && build.completedDate.timestamp) {
                    build.completed = moment.unix(build.completedDate.timestamp).format('Do MMM @ HH:mm');
                }
                return build;
            });
            this.lastBuild = this.builds[0];

            this.latestProduction = null;
            this.latestPreview = null;
            this.latestUpload = null;

            for (const build of builds) {
                if (!this.latestUpload && build.buildType === 'SOURCE_UPLOAD' && build.completedDate) {
                    this.latestUpload = build.completedDate.timestamp;
                }

                if (!this.latestPreview && build.buildType === 'PREVIEW' && build.completedDate) {
                    this.latestPreview = build.completedDate.timestamp;
                }

                if (!this.latestProduction && build.buildType === 'PUBLISH' && build.completedDate) {
                    this.latestProduction = build.completedDate.timestamp;
                }

                if (this.latestProduction && this.latestPreview && this.latestUpload) {
                    break;
                }
            }

            if (this.latestUpload) {
                this.lastUpload = moment.unix(this.latestUpload).format('Do MMMM @ HH:mm');
            }

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
