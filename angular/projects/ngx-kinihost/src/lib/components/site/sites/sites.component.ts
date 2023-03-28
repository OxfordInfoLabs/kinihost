import {Component, OnInit} from '@angular/core';
import {SiteService} from '../../../services/site.service';

@Component({
    selector: 'kh-sites',
    templateUrl: './sites.component.html',
    styleUrls: ['./sites.component.sass']
})
export class SitesComponent implements OnInit {

    public sites = [];

    constructor(private siteService: SiteService) {
    }

    ngOnInit(): void {
    }

}
