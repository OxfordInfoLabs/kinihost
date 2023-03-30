import { async, ComponentFixture, TestBed } from '@angular/core/testing';

import { SourceFilesComponent } from './source-files.component';

describe('SourceFilesComponent', () => {
  let component: SourceFilesComponent;
  let fixture: ComponentFixture<SourceFilesComponent>;

  beforeEach(async(() => {
    TestBed.configureTestingModule({
      declarations: [ SourceFilesComponent ]
    })
    .compileComponents();
  }));

  beforeEach(() => {
    fixture = TestBed.createComponent(SourceFilesComponent);
    component = fixture.componentInstance;
    fixture.detectChanges();
  });

  it('should create', () => {
    expect(component).toBeTruthy();
  });
});
