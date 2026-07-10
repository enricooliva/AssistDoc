import { signal } from '@angular/core';
import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NgbModal } from '@ng-bootstrap/ng-bootstrap';
import { AuthService } from '../../core/auth/auth.service';
import { TenantAdminApiService } from './tenant-admin-api.service';
import { TenantAdminPageComponent } from './tenant-admin-page.component';

class TenantAdminApiServiceStub {
  readonly items = signal([
    {
      id: '1',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
      status: 'active' as const,
      member_count: 3,
      admin_count: 1,
      last_provisioned_at: '2026-05-22T10:00:00Z',
    },
  ]);
  readonly selectedTenant = signal({
    tenant: {
      id: '1',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
      status: 'active' as const,
      member_count: 3,
      admin_count: 1,
      last_provisioned_at: '2026-05-22T10:00:00Z',
    },
    members: [],
  });
  readonly loading = signal(false);
  readonly page = signal(1);
  readonly perPage = signal(10);
  readonly total = signal(1);
  readonly feedback = signal('');
  readonly selectedTenantId = signal('1');
  readonly load = jasmine.createSpy('load').and.resolveTo();
  readonly loadTenant = jasmine.createSpy('loadTenant').and.resolveTo();
  readonly selectTenant = jasmine.createSpy('selectTenant').and.resolveTo();
  readonly updateTenant = jasmine.createSpy('updateTenant').and.resolveTo({
    tenant: {
      id: '1',
      name: 'AssistDoc Demo Aggiornato',
      slug: 'assistdoc-demo-aggiornato',
      status: 'active',
      member_count: 3,
      admin_count: 1,
      last_provisioned_at: '2026-05-22T10:00:00Z',
    },
  });
  readonly createTenant = jasmine.createSpy('createTenant').and.resolveTo({
    tenant: {
      id: '2',
      name: 'Nuovo Tenant',
      slug: 'nuovo-tenant',
      status: 'active',
      member_count: 1,
      admin_count: 1,
      last_provisioned_at: '2026-05-22T10:00:00Z',
    },
    initial_admin: {
      id: '10',
      full_name: 'Mario Rossi',
      email: 'mario@example.it',
      tenant: {
        id: '2',
        name: 'Nuovo Tenant',
        slug: 'nuovo-tenant',
      },
      role: 'tenant-admin',
      status: 'active',
      access_methods: ['company_account'],
      mfa_policy: 'optional',
    },
  });
  readonly addUser = jasmine.createSpy('addUser').and.resolveTo({
    tenant: {
      id: '1',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
      status: 'active',
      member_count: 4,
      admin_count: 1,
      last_provisioned_at: '2026-05-22T10:00:00Z',
    },
    user: {
      id: '11',
      full_name: 'Lucia Bianchi',
      email: 'lucia@example.it',
      tenant: {
        id: '1',
        name: 'AssistDoc Demo',
        slug: 'assistdoc-demo',
      },
      role: 'viewer',
      status: 'active',
      access_methods: ['password'],
      mfa_policy: 'optional',
    },
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
      role: 'super-admin' as const,
    },
    tenant: {
      id: 'tenant-1',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
    },
  });
}

class ModalRefStub {
  readonly componentInstance: Record<string, unknown> = {};
  readonly result = Promise.resolve();
}

class NgbModalStub {
  readonly open = jasmine.createSpy('open').and.callFake(() => new ModalRefStub() as never);
}

describe('TenantAdminPageComponent', () => {
  let fixture: ComponentFixture<TenantAdminPageComponent>;
  let component: TenantAdminPageComponent;
  let api: TenantAdminApiServiceStub;
  let modal: NgbModalStub;

  beforeEach(async () => {
    modal = new NgbModalStub();

    await TestBed.configureTestingModule({
      imports: [TenantAdminPageComponent],
      providers: [
        { provide: TenantAdminApiService, useClass: TenantAdminApiServiceStub },
        { provide: AuthService, useClass: AuthServiceStub },
        { provide: NgbModal, useValue: modal },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(TenantAdminPageComponent);
    component = fixture.componentInstance;
    api = TestBed.inject(TenantAdminApiService) as unknown as TenantAdminApiServiceStub;
    fixture.detectChanges();
  });

  it('loads the tenant overview on init', () => {
    expect(api.load).toHaveBeenCalled();
  });

  it('creates a tenant with the initial admin', async () => {
    component.tenantDraft.tenant_name = 'Nuovo Tenant';
    component.tenantDraft.tenant_slug = 'nuovo-tenant';
    component.tenantDraft.admin_full_name = 'Mario Rossi';
    component.tenantDraft.admin_email = 'mario@example.it';

    await component.createTenant();

    expect(api.createTenant).toHaveBeenCalled();
    expect(api.selectTenant).not.toHaveBeenCalled();
  });

  it('adds a user to the selected tenant', async () => {
    component.userDraft.tenant_id = '1';
    component.userDraft.full_name = 'Lucia Bianchi';
    component.userDraft.email = 'lucia@example.it';

    await component.addUser();

    expect(api.addUser).toHaveBeenCalledWith(jasmine.objectContaining({
      tenant_id: '1',
      full_name: 'Lucia Bianchi',
    }));
  });

  it('opens the tenant edit dialog with the selected tenant values', () => {
    component.editTenant(api.selectedTenant().tenant);

    expect(modal.open).toHaveBeenCalled();
    const modalRef = modal.open.calls.mostRecent().returnValue as unknown as ModalRefStub;
    expect(modalRef.componentInstance['tenant']).toEqual(api.selectedTenant().tenant);
  });
});
