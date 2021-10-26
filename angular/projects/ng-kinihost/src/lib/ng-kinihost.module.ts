import {ModuleWithProviders, NgModule} from '@angular/core';
import {HttpClientModule} from '@angular/common/http';
import {BuildsComponent} from './components/site/builds/builds.component';
import {BuildComponent} from './components/site/builds/build/build.component';
import {DomainsComponent} from './components/site/domains/domains.component';
import {SettingsComponent} from './components/site/settings/settings.component';
import {CyberSecuritySettingsComponent} from './components/site/settings/cyber-security-settings/cyber-security-settings.component';
import {PageSettingsComponent} from './components/site/settings/page-settings/page-settings.component';
import {SourceFilesComponent} from './components/site/source-files/source-files.component';
import {VersionsComponent} from './components/site/versions/versions.component';
import {SiteComponent} from './components/site/site.component';
import {SecurityPolicyComponent} from './components/site/settings/cyber-security-settings/security-policy/security-policy.component';
import {RouterModule} from '@angular/router';
import {SitePickerComponent} from './components/site/site-picker/site-picker.component';
import {MatDialogModule} from '@angular/material/dialog';
import {MatIconModule} from '@angular/material/icon';
import {MatButtonModule} from '@angular/material/button';
import {DragDropModule} from '@angular/cdk/drag-drop';
import {BrowserModule} from '@angular/platform-browser';
import {CommonModule} from '@angular/common';
import { SitesComponent } from './components/site/sites/sites.component';


@NgModule({
    declarations: [
        BuildsComponent,
        BuildComponent,
        DomainsComponent,
        SettingsComponent,
        CyberSecuritySettingsComponent,
        PageSettingsComponent,
        SourceFilesComponent,
        VersionsComponent,
        SiteComponent,
        SecurityPolicyComponent,
        SitePickerComponent,
        SitesComponent
    ],
    imports: [
        BrowserModule,
        CommonModule,
        HttpClientModule,
        RouterModule,
        MatDialogModule,
        MatIconModule,
        MatButtonModule,
        DragDropModule
    ],
    exports: [
        // BuildsComponent,
        // BuildComponent,
        // DomainsComponent,
        // SettingsComponent,
        // CyberSecuritySettingsComponent,
        // PageSettingsComponent,
        // SourceFilesComponent,
        // VersionsComponent,
        SiteComponent,
        // SecurityPolicyComponent,
        SitePickerComponent
    ]
})
export class NgKinihostModule {
    static forRoot(conf?: KinihostModuleConfig): ModuleWithProviders<NgKinihostModule> {
        return {
            ngModule: NgKinihostModule,
            providers: [
                { provide: KinihostModuleConfig, useValue: conf || {} }
            ]
        };
    }
}

export class KinihostModuleConfig {
    guestHttpURL: string;
    accessHttpURL: string;
}
