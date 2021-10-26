import {NgModule} from '@angular/core';
import {BrowserModule} from '@angular/platform-browser';

import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {BrowserAnimationsModule} from '@angular/platform-browser/animations';
import {MatToolbarModule} from '@angular/material/toolbar';
import {MatIconModule} from '@angular/material/icon';
import {MatButtonModule} from '@angular/material/button';
import {MatSidenavModule} from '@angular/material/sidenav';
import {MatChipsModule} from '@angular/material/chips';
import {MatDialogModule} from '@angular/material/dialog';
import {NgKiniAuthModule} from 'ng-kiniauth';
import {environment} from '../environments/environment';
import {MatSnackBarModule} from '@angular/material/snack-bar';
import {NgKinihostModule} from 'ng-kinihost';
import {SessionInterceptor} from './session.interceptor';
import {HTTP_INTERCEPTORS} from '@angular/common/http';
import {CommonModule} from '@angular/common';
import {LoginComponent} from './views/login/login.component';
import { SitesComponent } from './views/sites/sites.component';
import { SiteComponent } from './views/sites/site/site.component';

@NgModule({
    declarations: [
        AppComponent,
        LoginComponent,
        SitesComponent,
        SiteComponent
    ],
    imports: [
        BrowserModule,
        CommonModule,
        AppRoutingModule,
        BrowserAnimationsModule,
        MatToolbarModule,
        MatIconModule,
        MatButtonModule,
        MatSidenavModule,
        MatChipsModule,
        MatDialogModule,
        MatSnackBarModule,
        NgKiniAuthModule.forRoot({
            guestHttpURL: `${environment.backendURL}/guest`,
            accessHttpURL: `${environment.backendURL}/account`
        }),
        NgKinihostModule.forRoot({
            guestHttpURL: `${environment.backendURL}/guest`,
            accessHttpURL: `${environment.backendURL}/account`
        })
    ],
    providers: [
        {
            provide: HTTP_INTERCEPTORS,
            useClass: SessionInterceptor,
            multi: true
        }
    ],
    bootstrap: [AppComponent]
})
export class AppModule {
}
