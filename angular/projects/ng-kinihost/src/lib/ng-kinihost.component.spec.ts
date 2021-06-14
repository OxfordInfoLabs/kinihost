import { ComponentFixture, TestBed } from '@angular/core/testing';

import { NgKinihostComponent } from './ng-kinihost.component';

describe('NgKinihostComponent', () => {
  let component: NgKinihostComponent;
  let fixture: ComponentFixture<NgKinihostComponent>;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      declarations: [ NgKinihostComponent ]
    })
    .compileComponents();
  });

  beforeEach(() => {
    fixture = TestBed.createComponent(NgKinihostComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
