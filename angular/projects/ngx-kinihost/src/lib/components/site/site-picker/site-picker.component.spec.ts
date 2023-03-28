import { ComponentFixture, TestBed } from '@angular/core/testing';

import { SitePickerComponent } from './project-picker.component';

describe('ProjectPickerComponent', () => {
  let component: SitePickerComponent;
  let fixture: ComponentFixture<SitePickerComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ SitePickerComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(SitePickerComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
