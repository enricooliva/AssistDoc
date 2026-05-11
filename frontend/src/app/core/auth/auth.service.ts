import { HttpClient, HttpErrorResponse } from '@angular/common/http';
import { Injectable, inject, signal } from '@angular/core';
import { firstValueFrom } from 'rxjs';
import { apiUrl } from '../api/api-url';

export interface SessionState {
  token: string;
  user: {
    id: string;
    email: string;
    fullName: string;
    role: 'super-admin' | 'operator' | 'viewer';
  };
  tenant: {
    id: string;
    name: string;
    slug: string;
  };
}

interface AuthApiResponse {
  token: string;
  token_type: 'Bearer';
  expires_in: number;
  user: {
    id: string;
    email: string;
    full_name: string;
    role: 'super-admin' | 'operator' | 'viewer';
    tenant: {
      id: string;
      name: string;
      slug: string;
    };
  };
}

interface CurrentUserResponse {
  user: AuthApiResponse['user'];
}

const STORAGE_KEY = 'assistdoc.session';

@Injectable({ providedIn: 'root' })
export class AuthService {
  private readonly http = inject(HttpClient);

  readonly session = signal<SessionState | null>(this.readStoredSession());
  readonly feedback = signal('');

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
      this.clearSession(false);
    }
  }

  async signIn(email: string, password: string): Promise<void> {
    this.feedback.set('');

    try {
      const response = await firstValueFrom(
        this.http.post<AuthApiResponse>(apiUrl('/api/v1/auth/login'), { email, password }),
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
    } catch (error) {
      const message = this.extractErrorMessage(error, 'Accesso non riuscito.');
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
  }

  setAccessDenied(message = 'Operazione non consentita per il ruolo corrente.'): void {
    this.feedback.set(message);
  }

  private persistSession(session: SessionState): void {
    this.session.set(session);
    localStorage.setItem(STORAGE_KEY, JSON.stringify(session));
  }

  private clearSession(clearFeedback = true): void {
    this.session.set(null);
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
}
