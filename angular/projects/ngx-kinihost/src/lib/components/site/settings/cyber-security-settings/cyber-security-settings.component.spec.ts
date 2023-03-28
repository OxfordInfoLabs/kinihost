import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { CyberSecuritySettingsComponent } from './cyber-security-settings.component';

describe('CyberSecuritySettingsComponent', () => {
  let component: CyberSecuritySettingsComponent;
  let fixture: ComponentFixture<CyberSecuritySettingsComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ CyberSecuritySettingsComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(CyberSecuritySettingsComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
