import { MfaPolicy, TenantAdminRole, TenantUserRole } from '../../core/auth/auth.models';
import { EnterpriseUserSummary } from '../users/user-management.models';

export interface TenantMembershipSummary {
  id: string;
  name: string;
  slug: string;
  status: 'active' | 'inactive';
  member_count: number;
  admin_count: number;
  last_provisioned_at: string | null;
}

export interface TenantListResponse {
  items: TenantMembershipSummary[];
  page: number;
  perPage: number;
  total: number;
}

export interface TenantDetailResponse {
  tenant: TenantMembershipSummary;
  members: EnterpriseUserSummary[];
}

export interface TenantUpdatePayload {
  tenant_name: string;
  tenant_slug: string;
  status: TenantMembershipSummary['status'];
}

export interface TenantProvisionPayload {
  tenant_name: string;
  tenant_slug: string;
  admin_full_name: string;
  admin_email: string;
  admin_role: TenantAdminRole;
  admin_access_methods: Array<'company_account' | 'password'>;
  admin_status: 'provisioned' | 'active' | 'suspended' | 'locked' | 'deactivated';
  admin_mfa_policy: MfaPolicy;
  admin_password?: string;
}

export interface TenantProvisionResponse {
  tenant: TenantMembershipSummary;
  initial_admin: EnterpriseUserSummary;
}

export interface TenantUpdateResponse {
  tenant: TenantMembershipSummary;
}

export interface TenantUserProvisionPayload {
  tenant_id: string;
  full_name: string;
  email: string;
  role: TenantUserRole;
  access_methods: Array<'company_account' | 'password'>;
  status: 'provisioned' | 'active' | 'suspended' | 'locked' | 'deactivated';
  mfa_policy: MfaPolicy;
  password?: string;
}

export interface TenantUserProvisionResponse {
  tenant: TenantMembershipSummary;
  user: EnterpriseUserSummary;
}
