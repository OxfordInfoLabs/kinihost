import {ModuleWithProviders, NgModule} from '@angular/core';
import {HttpClientModule} from '@angular/common/http';
import {RouterModule} from '@angular/router';
import {BrowserModule} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import {SitesComponent} from './components/sites/sites.component';
import {MatSnackBarModule} from '@angular/material/snack-bar';
import {SiteComponent} from './components/site/site.component';
import {MatIconModule} from '@angular/material/icon';
import {MatProgressSpinnerModule} from '@angular/material/progress-spinner';
import {MatProgressBarModule} from '@angular/material/progress-bar';
import {MatSlideToggleModule} from '@angular/material/slide-toggle';
import {MatDialogModule} from '@angular/material/dialog';
import {FormsModule, ReactiveFormsModule} from '@angular/forms';
import {DomainsComponent} from './components/site/domains/domains.component';
import {PageSettingsComponent} from './components/site/page-settings/page-settings.component';
import {BuildsComponent} from './components/site/builds/builds.component';
import {MatTreeModule} from '@angular/material/tree';
import {SourceFilesComponent} from './components/site/source-files/source-files.component';
import {VersionsComponent} from './components/site/versions/versions.component';
import { CreateSiteComponent } from './components/sites/create-site/create-site.component';


@NgModule({
    declarations: [
        SitesComponent,
        SiteComponent,
        DomainsComponent,
        PageSettingsComponent,
        BuildsComponent,
        SourceFilesComponent,
        VersionsComponent,
        CreateSiteComponent
    ],
    imports: [
        BrowserModule,
        CommonModule,
        HttpClientModule,
        RouterModule,
        MatSnackBarModule,
        MatIconModule,
        MatProgressSpinnerModule,
        MatProgressBarModule,
        MatSlideToggleModule,
        MatDialogModule,
        FormsModule,
        ReactiveFormsModule,
        MatTreeModule
    ],
    exports: [
        SitesComponent,
        SiteComponent
    ]
})
export class NgxKinihostModule {
    static forRoot(conf?: KinihostModuleConfig): ModuleWithProviders<NgxKinihostModule> {
        return {
            ngModule: NgxKinihostModule,
            providers: [
                { provide: KinihostModuleConfig, useValue: conf || {} }
            ]
        };
    }
}

export class KinihostModuleConfig {
    guestHttpURL: string;
    accessHttpURL: string;
    adminHttpURL?: string;
}
