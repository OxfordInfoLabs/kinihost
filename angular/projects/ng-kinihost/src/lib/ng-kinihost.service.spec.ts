import { TestBed } from '@angular/core/testing';

import { NgKinihostService } from './ng-kinihost.service';

describe('NgKinihostService', () => {
  let service: NgKinihostService;

  beforeEach(() => {
    TestBed.configureTestingModule({});
    service = TestBed.inject(NgKinihostService);
  });

  it('should be created', () => {
    expect(service).toBeTruthy();
  });
});
