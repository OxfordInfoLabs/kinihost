import {NgModule} from '@angular/core';
import {RouterModule, Routes} from '@angular/router';
import {SitesComponent} from './views/sites/sites.component';
import {AuthGuard} from './guards/auth.guard';
import {LoginComponent} from './views/login/login.component';
import {SiteComponent} from './views/sites/site/site.component';

const routes: Routes = [
    {
        path: '',
        redirectTo: 'sites',
        pathMatch: 'full'
    },
    {
        path: 'sites',
        component: SitesComponent,
        canActivate: [AuthGuard]
    },
    {
        path: 'sites/:siteKey',
        component: SiteComponent,
        canActivate: [AuthGuard]
    },
    {
        path: 'login',
        component: LoginComponent
    }
];

@NgModule({
    imports: [RouterModule.forRoot(routes)],
    exports: [RouterModule]
})
export class AppRoutingModule {
}
