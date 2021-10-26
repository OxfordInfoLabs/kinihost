import {ChangeDetectorRef, Component, ViewChild} from '@angular/core';
import {Router} from '@angular/router';
import {MatDialog} from '@angular/material/dialog';
import {MediaMatcher} from '@angular/cdk/layout';
import {MatSidenav} from '@angular/material/sidenav';
import {Subscription} from 'rxjs';
import {SidenavService} from './services/sidenav.service';
import {AuthenticationService} from 'ng-kiniauth';
import {environment} from '../environments/environment';
import {SitePickerComponent, SiteService} from 'ng-kinihost';

@Component({
    selector: 'app-root',
    templateUrl: './app.component.html',
    styleUrls: ['./app.component.sass']
})
export class AppComponent {
    @ViewChild('snav', {static: false}) public snav: MatSidenav;

    public mobileQuery: MediaQueryList;
    public showFixedSidebar: boolean;
    public activeSite: any;
    public environment = environment;
    public loggedIn = false;
    public sessionUser: any = {};

    private readonly mobileQueryListener: () => void;
    private siteSub: Subscription;
    private authSub: Subscription;

    constructor(private changeDetectorRef: ChangeDetectorRef,
                private media: MediaMatcher,
                private sidenavService: SidenavService,
                private dialog: MatDialog,
                private authService: AuthenticationService,
                private router: Router,
                private siteService: SiteService) {

        this.mobileQuery = media.matchMedia('(max-width: 768px)');
        this.mobileQueryListener = () => changeDetectorRef.detectChanges();
        this.mobileQuery.addListener(this.mobileQueryListener);
    }

    ngOnInit() {

        this.siteSub = this.siteService.activeSite.subscribe(site => {
            this.activeSite = site;
            if (this.siteService) {
                this.router.navigate(['/site']);
            }

        });

        this.authSub = this.authService.authUser.subscribe(user => {
            this.loggedIn = !!user;
            if (this.loggedIn) {
                this.sessionUser = this.authService.sessionData.getValue().user;
            }
        });
    }

    ngAfterViewInit() {
        setTimeout(() => {
            this.showFixedSidebar = this.mobileQuery.matches;
        }, 0);

        this.sidenavService.setSidenav(this.snav);
        this.snav.closedStart.subscribe(opened => {
            this.showFixedSidebar = true;
        });
        this.snav.openedStart.subscribe(() => {
            this.showFixedSidebar = false;
        });
    }

    ngOnDestroy() {
        this.mobileQuery.removeListener(this.mobileQueryListener);
        this.authSub.unsubscribe();
    }

    public selectProject(disableClose = false) {
        const dialogRef = this.dialog.open(SitePickerComponent, {
            width: '700px',
            height: '500px',
            disableClose
        });
    }

    public logout() {
        this.authService.logout().then(() => {
            this.router.navigate(['/login']);
        });
    }
}
