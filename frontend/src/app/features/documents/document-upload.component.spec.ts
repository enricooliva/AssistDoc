import { provideHttpClient } from '@angular/common/http';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { provideFormlyCore } from '@ngx-formly/core';
import { withFormlyBootstrap } from '@ngx-formly/bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { DocumentApiService } from './document-api.service';
import { DocumentUploadComponent } from './document-upload.component';
import { InputFileComponent } from '../../shared/dynamic-form/input-file/input-file.component';

class AuthServiceStub {
  role: 'super-admin' | 'operator' | 'viewer' = 'operator';

  hasAnyRole(roles: string[]): boolean {
    return roles.includes(this.role);
  }
}

class DocumentApiServiceStub {
  error = () => '';
  uploadDocument = jasmine.createSpy('uploadDocument').and.resolveTo({
    id: 'doc-1',
    filename: 'manuale.txt',
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

  it('shows a permission notice for viewers', () => {
    authService.role = 'viewer';
    fixture = TestBed.createComponent(DocumentUploadComponent);
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Solo gli utenti con ruolo operatore o super-admin');
  });
});
