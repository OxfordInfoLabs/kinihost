import {NgModule} from '@angular/core';
import {BrowserModule} from '@angular/platform-browser';

import {AppRoutingModule} from './app-routing.module';
import {AppComponent} from './app.component';
import {SitesComponent} from './views/sites/sites.component';
import {NgxKinihostModule} from 'ngx-kinihost';
import {environment} from '../environments/environment';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import {MatSnackBarModule} from '@angular/material/snack-bar';
import { LoginComponent } from './views/login/login.component';
import {NgKiniAuthModule} from 'ng-kiniauth';
import {SessionInterceptor} from './session.interceptor';
import {HTTP_INTERCEPTORS} from '@angular/common/http';
import { SiteComponent } from './views/sites/site/site.component';

@NgModule({
    declarations: [
        AppComponent,
        SitesComponent,
        LoginComponent,
        SiteComponent
    ],
    imports: [
        BrowserModule,
        AppRoutingModule,
        NgxKinihostModule.forRoot({
            accessHttpURL: environment.backendURL + '/account',
            guestHttpURL: environment.backendURL + '/guest',
            adminHttpURL: environment.backendURL + '/admin'
        }),
        BrowserAnimationsModule,
        MatSnackBarModule,
        NgKiniAuthModule.forRoot({
            guestHttpURL: `${environment.backendURL}/guest`,
            accessHttpURL: `${environment.backendURL}/account`
        }),
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
