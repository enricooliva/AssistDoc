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
  readonly load = jasmine.createSpy('load').and.resolveTo();
  readonly create = jasmine.createSpy('create').and.resolveTo();
  readonly updateStatus = jasmine.createSpy('updateStatus').and.resolveTo();
  readonly unlock = jasmine.createSpy('unlock').and.resolveTo();
  readonly clearFeedback = jasmine.createSpy('clearFeedback');
}

class AuthServiceStub {
  readonly session = signal({
    token: 'jwt',
    user: {
      id: '1',
      email: 'admin@assistdoc.local',
      fullName: 'Admin Demo',
      role: 'super-admin' as const,
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
});
