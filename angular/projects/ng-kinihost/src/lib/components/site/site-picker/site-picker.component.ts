import {Component, Inject, Input, OnInit} from '@angular/core';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';
import {BehaviorSubject, merge, Subject} from 'rxjs';
import {debounceTime, distinctUntilChanged, map, switchMap} from 'rxjs/operators';
import {SiteService} from '../../../services/site.service';

@Component({
    selector: 'kh-site-picker',
    templateUrl: './site-picker.component.html',
    styleUrls: ['./site-picker.component.sass'],
    host: {class: 'dialog-wrapper'}
})
export class SitePickerComponent implements OnInit {

    public sites: any = [];
    public searchText = new BehaviorSubject<string>('');
    public reload = new Subject();
    public activeSite: any = {};


    constructor(public dialogRef: MatDialogRef<SitePickerComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any,
                private siteService: SiteService) {
    }

    ngOnInit(): void {

        this.activeSite = this.siteService.activeSite.getValue();

        merge(this.searchText, this.reload).pipe(
            debounceTime(300),
            distinctUntilChanged(),
            switchMap(() =>
                this.getSites()
            )
        ).subscribe((sites: any) => {
            this.sites = sites;
        });
    }

    public activateSite(site) {
        this.siteService.setActiveSite(site);
        this.dialogRef.close();
    }

    private getSites() {
        return this.siteService.getSites(
            this.searchText.getValue()
        ).pipe(map((projects: any) => {
                return projects;
            })
        );
    }

}
