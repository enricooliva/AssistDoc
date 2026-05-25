import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { HttpTestingController, provideHttpClientTesting } from '@angular/common/http/testing';
import { TestBed } from '@angular/core/testing';
import { apiUrl } from '../../core/api/api-url';
import { authInterceptor } from '../../core/http/auth.interceptor';
import { UserManagementApiService } from './user-management-api.service';

describe('UserManagementApiService', () => {
  let service: UserManagementApiService;
  let httpMock: HttpTestingController;

  beforeEach(() => {
    TestBed.configureTestingModule({
      providers: [
        provideHttpClient(withInterceptors([authInterceptor])),
        provideHttpClientTesting(),
        UserManagementApiService,
      ],
    });

    service = TestBed.inject(UserManagementApiService);
    httpMock = TestBed.inject(HttpTestingController);
  });

  afterEach(() => {
    httpMock.verify();
  });

  it('patches an enterprise user and reloads the current list', async () => {
    const updatePromise = service.update('42', {
      full_name: 'Mario Rossi',
      email: 'mario.rossi@example.test',
      tenant_id: 'tenant-1',
      role: 'viewer',
      status: 'active',
      access_methods: ['company_account'],
      mfa_policy: 'optional',
      password: 'Password12345',
    });

    const patchRequest = httpMock.expectOne(apiUrl('/api/v1/users/42'));
    expect(patchRequest.request.method).toBe('PATCH');
    expect(patchRequest.request.body.full_name).toBe('Mario Rossi');
    patchRequest.flush({
      user: {
        id: '42',
        full_name: 'Mario Rossi',
        email: 'mario.rossi@example.test',
        tenant: {
          id: 'tenant-1',
          name: 'AssistDoc Demo',
          slug: 'assistdoc-demo',
        },
        role: 'viewer',
        status: 'active',
        access_methods: ['company_account'],
        mfa_policy: 'optional',
        deleted_at: null,
        deleted_by: null,
        lockout: null,
      },
    });

    const reloadRequest = httpMock.expectOne((request) =>
      request.method === 'GET' && request.url === apiUrl('/api/v1/users'),
    );
    expect(reloadRequest.request.params.get('page')).toBe('1');
    expect(reloadRequest.request.params.get('perPage')).toBe('10');
    reloadRequest.flush({ items: [], page: 1, perPage: 10, total: 0 });

    const result = await updatePromise;

    expect(result.full_name).toBe('Mario Rossi');
    expect(service.feedback()).toBe('Utente aggiornato correttamente.');
  });
});
