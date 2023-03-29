import {Component, OnInit} from '@angular/core';
import {SiteService} from '../../services/site.service';
import {debounceTime, map, switchMap} from 'rxjs/operators';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {MatDialog} from '@angular/material/dialog';
import {CreateSiteComponent} from './create-site/create-site.component';

@Component({
    selector: 'kh-sites',
    templateUrl: './sites.component.html',
    styleUrls: ['./sites.component.css']
})
export class SitesComponent implements OnInit {

    public sites: any = [];
    public searchText = new BehaviorSubject('');
    public limit = 10;
    public offset = 0;
    public page = 1;
    public endOfResults = false;
    public loading = true;

    private reload = new Subject();

    constructor(private siteService: SiteService,
                private dialog: MatDialog) {
    }

    async ngOnInit(): Promise<any> {
        merge(this.searchText, this.reload)
            .pipe(
                debounceTime(300),
                // distinctUntilChanged(),
                switchMap(() =>
                    this.getSites()
                )
            ).subscribe((sites: any) => {
            this.endOfResults = sites.length < this.limit;
            this.sites = sites;
            this.loading = false;
        });

        this.searchText.subscribe(() => {
            this.page = 1;
            this.offset = 0;
        });
    }

    public createSite() {
        const dialogRef = this.dialog.open(CreateSiteComponent, {
            width: '900px',
            height: '900px'
        });

        dialogRef.afterClosed().subscribe(res => {

        });
    }

    // tslint:disable-next-line:typedef
    private getSites() {
        return this.siteService.getSites(
            this.searchText.getValue() || '',
            this.limit.toString(),
            this.offset.toString()
        ).pipe(map((feeds: any) => {
                return feeds;
            })
        );
    }

}
