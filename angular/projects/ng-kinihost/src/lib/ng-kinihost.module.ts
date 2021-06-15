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
        SecurityPolicyComponent
    ],
    imports: [
        HttpClientModule
    ],
    exports: [
        BuildsComponent,
        BuildComponent,
        DomainsComponent,
        SettingsComponent,
        CyberSecuritySettingsComponent,
        PageSettingsComponent,
        SourceFilesComponent,
        VersionsComponent,
        SiteComponent,
        SecurityPolicyComponent
    ]
})
export class NgKinihostModule {
    static forRoot(conf?: KinihostModuleConfig): ModuleWithProviders<any> {
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
