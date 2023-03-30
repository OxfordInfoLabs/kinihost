import {Component, OnInit} from '@angular/core';
import {environment} from '../../../environments/environment';
import {AuthenticationService} from 'ng-kiniauth';

@Component({
    selector: 'app-login',
    templateUrl: './login.component.html',
    styleUrls: ['./login.component.sass']
})
export class LoginComponent implements OnInit {

    public environment = environment;

    constructor(private authService: AuthenticationService) {
    }

    ngOnInit() {
        this.authService.getSessionData();
    }

}
