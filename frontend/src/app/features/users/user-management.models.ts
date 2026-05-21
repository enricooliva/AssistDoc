import { MfaPolicy, TenantSummary, UserRole } from '../../core/auth/auth.models';

export interface EnterpriseUserSummary {
  id: string;
  full_name: string;
  email: string;
  tenant: TenantSummary;
  role: UserRole;
  status: 'provisioned' | 'active' | 'suspended' | 'locked' | 'deactivated' | 'reset_pending';
  access_methods: Array<'company_account' | 'password'>;
  mfa_policy: MfaPolicy;
  lockout?: {
    status: string;
    reason: string;
    locked_until: string | null;
  } | null;
}

export interface EnterpriseUserListResponse {
  items: EnterpriseUserSummary[];
  page: number;
  perPage: number;
  total: number;
}

export interface EnterpriseUserCreatePayload {
  full_name: string;
  email: string;
  tenant_id: string;
  role: UserRole;
  status: 'provisioned' | 'active' | 'suspended' | 'locked' | 'deactivated';
  access_methods: Array<'company_account' | 'password'>;
  mfa_policy: MfaPolicy;
  password?: string;
}
