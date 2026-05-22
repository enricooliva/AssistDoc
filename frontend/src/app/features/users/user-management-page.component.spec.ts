import { signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { AuthService } from '../../core/auth/auth.service';
import { UserManagementApiService } from './user-management-api.service';
import { UserManagementPageComponent } from './user-management-page.component';

class UserManagementApiServiceStub {
  readonly items = signal([]);
  readonly feedback = signal('');
  readonly loading = signal(false);
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(0);
  readonly query = signal('');
  readonly load = jasmine.createSpy('load').and.resolveTo();
  readonly search = jasmine.createSpy('search').and.resolveTo();
  readonly create = jasmine.createSpy('create').and.resolveTo();
  readonly updateStatus = jasmine.createSpy('updateStatus').and.resolveTo();
  readonly unlock = jasmine.createSpy('unlock').and.resolveTo();
  readonly delete = jasmine.createSpy('delete').and.resolveTo({
    status: 'deleted',
    userId: '2',
    deletedAt: '2026-05-22T10:00:00Z',
    deletedBy: { id: '1', fullName: 'Admin Demo' },
    removedFromList: true,
  });
  readonly clearFeedback = jasmine.createSpy('clearFeedback');
}

class AuthServiceStub {
  readonly session = signal({
    token: 'jwt',
      user: {
        id: '1',
        email: 'admin@assistdoc.local',
        fullName: 'Admin Demo',
        role: 'tenant-admin' as const,
      },
    tenant: {
      id: 'tenant-1',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
    },
  });
}

describe('UserManagementPageComponent', () => {
  let fixture: ComponentFixture<UserManagementPageComponent>;
  let component: UserManagementPageComponent;
  let api: UserManagementApiServiceStub;

  beforeEach(async () => {
    spyOn(window, 'confirm').and.returnValue(true);

    await TestBed.configureTestingModule({
      imports: [UserManagementPageComponent],
      providers: [
        { provide: UserManagementApiService, useClass: UserManagementApiServiceStub },
        { provide: AuthService, useClass: AuthServiceStub },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(UserManagementPageComponent);
    component = fixture.componentInstance;
    api = TestBed.inject(UserManagementApiService) as unknown as UserManagementApiServiceStub;
    fixture.detectChanges();
  });

  it('loads users on init', () => {
    expect(api.load).toHaveBeenCalled();
  });

  it('creates a user with selected access methods', async () => {
    component.draft.full_name = 'Mario Rossi';
    component.draft.email = 'mario@example.test';
    component.useCompanyAccount = true;
    component.usePassword = true;
    component.draft.password = 'Password12345';

    await component.createUser();

    expect(api.create).toHaveBeenCalledWith(jasmine.objectContaining({
      tenant_id: 'tenant-1',
      access_methods: ['company_account', 'password'],
    }));
  });

  it('shows the backend validation message when provisioning fails', async () => {
    api.create.and.rejectWith(new Error('Il campo password deve contenere almeno 12 caratteri.'));
    component.usePassword = true;
    component.draft.full_name = 'Mario Rossi';
    component.draft.email = 'mario@example.test';

    await component.createUser();

    expect(component.error()).toBe('Il campo password deve contenere almeno 12 caratteri.');
  });

  it('submits a tenant-scoped search query', async () => {
    component.searchQuery = 'viewer';

    await component.searchUsers();

    expect(api.search).toHaveBeenCalledWith('viewer');
  });

  it('confirms and soft deletes a user', async () => {
    await component.deleteUser('2', 'Mario Rossi');

    expect(window.confirm).toHaveBeenCalled();
    expect(api.delete).toHaveBeenCalledWith('2');
  });
});
