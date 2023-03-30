import {Component, Inject, OnInit} from '@angular/core';
import {SiteService} from '../../../services/site.service';
import {MAT_DIALOG_DATA, MatDialogRef} from '@angular/material/dialog';

@Component({
    selector: 'kh-create-site',
    templateUrl: './create-site.component.html',
    styleUrls: ['./create-site.component.css']
})
export class CreateSiteComponent implements OnInit {

    public newSite: any = {};

    constructor(private siteService: SiteService,
                public dialogRef: MatDialogRef<CreateSiteComponent>,
                @Inject(MAT_DIALOG_DATA) public data: any) {
    }

    ngOnInit(): void {
    }

    public async createSite() {
        await this.siteService.createSite(this.newSite.title);
        this.dialogRef.close(true);
    }
}
