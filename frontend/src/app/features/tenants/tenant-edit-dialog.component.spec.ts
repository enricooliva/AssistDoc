import { ComponentFixture, TestBed } from '@angular/core/testing';
import { NgbActiveModal } from '@ng-bootstrap/ng-bootstrap';
import { TenantAdminApiService } from './tenant-admin-api.service';
import { TenantEditDialogComponent } from './tenant-edit-dialog.component';

class TenantAdminApiServiceStub {
  readonly updateTenant = jasmine.createSpy('updateTenant').and.resolveTo({
    tenant: {
      id: '1',
      name: 'AssistDoc Demo Aggiornato',
      slug: 'assistdoc-demo-aggiornato',
      status: 'inactive',
      member_count: 3,
      admin_count: 1,
      last_provisioned_at: '2026-05-22T10:00:00Z',
    },
  });
}

class NgbActiveModalStub {
  readonly close = jasmine.createSpy('close');
  readonly dismiss = jasmine.createSpy('dismiss');
}

describe('TenantEditDialogComponent', () => {
  let fixture: ComponentFixture<TenantEditDialogComponent>;
  let component: TenantEditDialogComponent;
  let api: TenantAdminApiServiceStub;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [TenantEditDialogComponent],
      providers: [
        { provide: TenantAdminApiService, useClass: TenantAdminApiServiceStub },
        { provide: NgbActiveModal, useClass: NgbActiveModalStub },
      ],
    }).compileComponents();

    fixture = TestBed.createComponent(TenantEditDialogComponent);
    component = fixture.componentInstance;
    api = TestBed.inject(TenantAdminApiService) as unknown as TenantAdminApiServiceStub;
    component.tenant = {
      id: '1',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
      status: 'active',
      member_count: 3,
      admin_count: 1,
      last_provisioned_at: '2026-05-22T10:00:00Z',
    };
    fixture.detectChanges();
  });

  it('pre-fills the modal with the saved tenant values', () => {
    expect(component.form.getRawValue()).toEqual({
      tenant_name: 'AssistDoc Demo',
      tenant_slug: 'assistdoc-demo',
      status: 'active',
    });
  });

  it('submits the updated tenant payload', async () => {
    component.form.controls.tenant_name.setValue('AssistDoc Demo Aggiornato');
    component.form.controls.tenant_slug.setValue('assistdoc-demo-aggiornato');
    component.form.controls.status.setValue('inactive');

    await component.submit();

    expect(api.updateTenant).toHaveBeenCalledWith('1', {
      tenant_name: 'AssistDoc Demo Aggiornato',
      tenant_slug: 'assistdoc-demo-aggiornato',
      status: 'inactive',
    });
  });
});
