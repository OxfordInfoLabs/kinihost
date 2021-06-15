import {Component, Input, OnInit} from '@angular/core';
import { Subscription } from 'rxjs';
import { ActivatedRoute } from '@angular/router';
import * as moment from 'moment';
import {SiteService} from '../../../../services/site.service';
import {BuildService} from '../../../../services/build.service';

@Component({
    selector: 'app-build',
    templateUrl: './build.component.html',
    styleUrls: ['./build.component.sass']
})
export class BuildComponent implements OnInit {

    public site: any;
    public build: any;
    public moment = moment;

    private siteSub: Subscription;
    private routeSub: Subscription;

    constructor(private route: ActivatedRoute,
                private siteService: SiteService,
                private buildService: BuildService) {
    }

    ngOnInit() {
        this.routeSub = this.route.params.subscribe(res => {
            this.loadBuild(res.buildId);
        });
        this.siteSub = this.siteService.activeSite.subscribe(site => {
            this.site = site;
        });
    }

    ngOnDestroy(): void {
        this.siteSub.unsubscribe();
        this.routeSub.unsubscribe();
    }

    private loadBuild(id) {
        this.buildService.getBuild(id).then(build => {
            this.build = build;
        })
    }

}
