import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { Router } from '@angular/router';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../api/api-url';
import {
  ActionRequiredResponse,
  AuthApiResponse,
  CompanyAccountStartResponse,
  CurrentUserResponse,
  LoginOptionsResponse,
  PasswordResetCompleteResponse,
  PasswordResetStartResponse,
  SessionState,
} from './auth.models';

const STORAGE_KEY = 'assistdoc.session';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);
  private readonly router = inject(Router);

  readonly session = signal<SessionState | null>(this.readStoredSession());
  readonly feedback = signal('');
  readonly loginOptions = signal<LoginOptionsResponse['options']>([
    { id: 'company_account', label: 'Accedi con account aziendale' },
    { id: 'password', label: 'Accedi con email e password' },
  ]);
  readonly pendingChallengeId = signal<string | null>(null);
  readonly lastActionRequired = signal<ActionRequiredResponse | null>(null);

  async loadLoginOptions(): Promise<void> {
    const response = await firstValueFrom(
      this.http.get<LoginOptionsResponse>(apiUrl('/api/v1/auth/options')),
    );

    this.loginOptions.set(response.options);
  }

  async restoreSession(): Promise<void> {
    const current = this.session();

    if (!current?.token) {
      return;
    }

    try {
      const response = await firstValueFrom(
        this.http.get<CurrentUserResponse>(apiUrl('/api/v1/auth/me')),
      );

      this.persistSession({
        token: current.token,
        user: {
          id: response.user.id,
          email: response.user.email,
          fullName: response.user.full_name,
          role: response.user.role,
        },
        tenant: response.user.tenant,
      });
    } catch {
      this.handleUnauthorized();
    }
  }

  async startCompanyAccount(): Promise<CompanyAccountStartResponse> {
    this.feedback.set('');
    const response = await firstValueFrom(
      this.http.post<CompanyAccountStartResponse>(apiUrl('/api/v1/auth/company-account/start'), {}),
    );

    return response;
  }

  async signIn(email: string, password: string): Promise<void> {
    await this.signInWithPassword(email, password);
  }

  async signInWithPassword(email: string, password: string): Promise<void> {
    this.feedback.set('');
    this.lastActionRequired.set(null);
    this.pendingChallengeId.set(null);

    try {
      const response = await firstValueFrom(
        this.http.post<AuthApiResponse | ActionRequiredResponse>(apiUrl('/api/v1/auth/password/login'), { email, password }),
      );

      if (this.isActionRequiredResponse(response)) {
        this.lastActionRequired.set(response);
        this.pendingChallengeId.set(response.challenge_id ?? null);
        return;
      }

      this.persistSession({
        token: response.token,
        user: {
          id: response.user.id,
          email: response.user.email,
          fullName: response.user.full_name,
          role: response.user.role,
        },
        tenant: response.user.tenant,
      });
    } catch (error) {
      if (error instanceof HttpErrorResponse && error.status === 409 && error.error?.status === 'action_required') {
        const response = error.error as ActionRequiredResponse;
        this.lastActionRequired.set(response);
        this.pendingChallengeId.set(response.challenge_id ?? null);
        this.feedback.set('');
        return;
      }

      const message = this.extractErrorMessage(error, 'Accesso non riuscito.');
      this.feedback.set(message);
      throw new Error(message);
    }
  }

  async verifyMfa(verificationCode: string): Promise<void> {
    const challengeId = this.pendingChallengeId();

    if (!challengeId) {
      throw new Error('Nessuna verifica MFA in corso.');
    }

    this.feedback.set('');

    try {
      const response = await firstValueFrom(
        this.http.post<AuthApiResponse>(apiUrl('/api/v1/auth/mfa/verify'), {
          challenge_id: challengeId,
          verification_code: verificationCode,
        }),
      );

      this.persistSession({
        token: response.token,
        user: {
          id: response.user.id,
          email: response.user.email,
          fullName: response.user.full_name,
          role: response.user.role,
        },
        tenant: response.user.tenant,
      });
      this.pendingChallengeId.set(null);
      this.lastActionRequired.set(null);
    } catch (error) {
      if (error instanceof HttpErrorResponse && error.status === 409) {
        const message = this.extractErrorMessage(error, 'Verifica MFA non riuscita.');
        this.feedback.set(message);
        this.lastActionRequired.set({
          status: 'action_required',
          action: 'account_locked',
          message,
          lockedUntil: error.error?.error?.details?.locked_until ?? null,
        });
        throw new Error(message);
      }

      const message = this.extractErrorMessage(error, 'Verifica MFA non riuscita.');
      this.feedback.set(message);
      throw new Error(message);
    }
  }

  async requestPasswordReset(email: string): Promise<string> {
    this.feedback.set('');

    try {
      const response = await firstValueFrom(
        this.http.post<PasswordResetStartResponse>(apiUrl('/api/v1/auth/password-reset'), { email }),
      );

      this.feedback.set(response.message);
      return response.reset_id;
    } catch (error) {
      const message = this.extractErrorMessage(error, 'Reset password non riuscito.');
      this.feedback.set(message);
      throw new Error(message);
    }
  }

  async completePasswordReset(resetId: string, newPassword: string): Promise<void> {
    this.feedback.set('');

    try {
      const response = await firstValueFrom(
        this.http.post<PasswordResetCompleteResponse>(apiUrl('/api/v1/auth/password-reset/complete'), {
          reset_id: resetId,
          new_password: newPassword,
        }),
      );

      this.feedback.set(response.message);
    } catch (error) {
      const message = this.extractErrorMessage(error, 'Completamento reset password non riuscito.');
      this.feedback.set(message);
      throw new Error(message);
    }
  }

  async signOut(): Promise<void> {
    try {
      if (this.session()?.token) {
        await firstValueFrom(this.http.post(apiUrl('/api/v1/auth/logout'), {}));
      }
    } finally {
      this.clearSession();
      await this.router.navigateByUrl('/sign-in');
    }
  }

  hasAnyRole(roles: string[]): boolean {
    const currentRole = this.session()?.user.role;

    return !!currentRole && roles.includes(currentRole);
  }

  clearFeedback(): void {
    this.feedback.set('');
  }

  handleUnauthorized(message = 'La sessione è scaduta. Effettua nuovamente l\'accesso.'): void {
    this.clearSession(false);
    this.feedback.set(message);
    void this.router.navigateByUrl('/sign-in');
  }

  setAccessDenied(message = 'Operazione non consentita per il ruolo corrente.'): void {
    this.feedback.set(message);
  }

  clearActionRequired(): void {
    this.pendingChallengeId.set(null);
    this.lastActionRequired.set(null);
  }

  private persistSession(session: SessionState): void {
    this.session.set(session);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
  }

  private clearSession(clearFeedback = true): void {
    this.session.set(null);
    this.pendingChallengeId.set(null);
    this.lastActionRequired.set(null);
    localStorage.removeItem(STORAGE_KEY);

    if (clearFeedback) {
      this.feedback.set('');
    }
  }

  private readStoredSession(): SessionState | null {
    const value = localStorage.getItem(STORAGE_KEY);

    if (!value) {
      return null;
    }

    try {
      return JSON.parse(value) as SessionState;
    } catch {
      localStorage.removeItem(STORAGE_KEY);
      return null;
    }
  }

  private extractErrorMessage(error: unknown, fallback: string): string {
    if (error instanceof HttpErrorResponse) {
      return error.error?.error?.message ?? fallback;
    }

    return fallback;
  }

  private isActionRequiredResponse(response: AuthApiResponse | ActionRequiredResponse): response is ActionRequiredResponse {
    return 'status' in response && response.status === 'action_required';
  }
}
