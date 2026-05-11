import { ComponentFixture, TestBed } from '@angular/core/testing';
import { signal } from '@angular/core';
import { AuthService } from '../../core/auth/auth.service';
import { DocumentApiService } from './document-api.service';
import { DocumentListComponent } from './document-list.component';

class AuthServiceStub {
  hasAnyRole(roles: string[]): boolean {
    return roles.includes('operator');
  }
}

class DocumentApiServiceStub {
  readonly documents = signal([{
    id: 'doc-1',
    filename: 'Manuale Aziendale.txt',
    mediaType: 'text/plain',
    sizeBytes: 200,
    status: 'failed' as const,
    uploadedAt: new Date().toISOString(),
    lastStatusAt: new Date().toISOString(),
    uploadedBy: { id: '1', fullName: 'Operator Demo' },
    failureReason: 'Indicizzazione non riuscita.',
    segmentsCount: 1,
    searchableSegmentsCount: 0,
  }]);
  readonly loading = signal(false);
  readonly error = signal('');
  readonly loadDocuments = jasmine.createSpy('loadDocuments').and.resolveTo();
  readonly retryDocument = jasmine.createSpy('retryDocument').and.resolveTo({ documentId: 'doc-1', status: 'queued' });
}

describe('DocumentListComponent', () => {
  let fixture: ComponentFixture<DocumentListComponent>;
  let api: DocumentApiServiceStub;

  beforeEach(async () => {
    api = new DocumentApiServiceStub();

    await TestBed.configureTestingModule({
      imports: [DocumentListComponent],
      providers: [
        { provide: AuthService, useClass: AuthServiceStub },
        { provide: DocumentApiService, useValue: api },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DocumentListComponent);
    fixture.detectChanges();
  });

  it('renders document rows from the API signal', () => {
    expect(fixture.nativeElement.textContent).toContain('Manuale Aziendale.txt');
    expect(fixture.nativeElement.textContent).toContain('Indicizzazione non riuscita.');
  });
});
