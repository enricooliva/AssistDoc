export type UserRole = 'super-admin' | 'operator' | 'viewer';
export type LoginMethod = 'company_account' | 'password';
export type MfaPolicy = 'required' | 'optional' | 'inherited';

export interface TenantSummary {
  id: string;
  name: string;
  slug: string;
}

export interface AuthenticatedUser {
  id: string;
  email: string;
  fullName: string;
  role: UserRole;
}

export interface SessionState {
  token: string;
  user: AuthenticatedUser;
  tenant: TenantSummary;
}

export interface LoginOption {
  id: LoginMethod;
  label: string;
  description?: string;
}

export interface LoginOptionsResponse {
  options: LoginOption[];
}

export interface CompanyAccountStartResponse {
  status: 'redirect_required';
  redirect_url: string;
}

export interface AuthApiResponse {
  token: string;
  token_type: 'Bearer';
  expires_in: number;
  user: {
    id: string;
    email: string;
    full_name: string;
    role: UserRole;
    tenant: TenantSummary;
  };
}

export interface ActionRequiredResponse {
  status: 'action_required';
  action: 'mfa_required' | 'password_reset_required' | 'account_locked' | 'access_denied';
  challenge_id?: string | null;
  message: string;
  lockedUntil?: string | null;
}

export interface CurrentUserResponse {
  user: AuthApiResponse['user'];
}

export interface PasswordResetStartResponse {
  status: 'accepted';
  message: string;
  reset_id: string;
}

export interface PasswordResetCompleteResponse {
  status: 'completed';
  message: string;
}
