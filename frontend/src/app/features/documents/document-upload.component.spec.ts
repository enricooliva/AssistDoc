import { provideHttpClient } from '@angular/common/http';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideFormlyCore } from '@ngx-formly/core';
import { withFormlyBootstrap } from '@ngx-formly/bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { DocumentApiService } from './document-api.service';
import { DocumentUploadComponent } from './document-upload.component';
import { InputFileComponent } from '../../shared/dynamic-form/input-file/input-file.component';

class AuthServiceStub {
  role: 'super-admin' | 'tenant-admin' | 'operator' | 'viewer' = 'operator';

  hasAnyRole(roles: string[]): boolean {
    return roles.includes(this.role);
  }
}

class DocumentApiServiceStub {
  error = () => '';
  uploadDocument = jasmine.createSpy('uploadDocument').and.resolveTo({
    id: 'doc-1',
    filename: 'manuale.txt',
    tags: [],
  });
}

describe('DocumentUploadComponent', () => {
  let fixture: ComponentFixture<DocumentUploadComponent>;
  let authService: AuthServiceStub;

  beforeEach(async () => {
    authService = new AuthServiceStub();

    await TestBed.configureTestingModule({
      imports: [DocumentUploadComponent],
      providers: [
        provideHttpClient(),
        provideFormlyCore([
          ...withFormlyBootstrap(),
          {
            types: [{ name: 'document-file', component: InputFileComponent, wrappers: ['form-field'] }],
          },
        ]),
        { provide: AuthService, useValue: authService },
        { provide: DocumentApiService, useClass: DocumentApiServiceStub },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(DocumentUploadComponent);
    fixture.detectChanges();
  });

  it('shows the upload heading for privileged users', () => {
    expect(fixture.nativeElement.textContent).toContain('Carica documento');
  });

  it('shows the tags field for privileged users', () => {
    expect(fixture.nativeElement.textContent).toContain('Tag');
  });

  it('renders a single explicit action to choose the file', () => {
    const text = fixture.nativeElement.textContent as string;

    expect(text).toContain('Scegli file');
    expect(text).not.toContain('Sfoglia');
  });

  it('shows a permission notice for viewers', () => {
    authService.role = 'viewer';
    fixture = TestBed.createComponent(DocumentUploadComponent);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Solo gli utenti con ruolo tenant-admin, operatore o super-admin');
  });

  it('allows tenant-admin users to upload documents', () => {
    authService.role = 'tenant-admin';
    fixture = TestBed.createComponent(DocumentUploadComponent);
    fixture.detectChanges();

    expect((fixture.componentInstance as DocumentUploadComponent).canUpload()).toBeTrue();
  });
});
