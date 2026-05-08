import { Injectable, signal } from '@angular/core';

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

@Injectable({ providedIn: 'root' })
export class AuthService {
  readonly session = signal<SessionState | null>({
    token: btoa('viewer@assistdoc.local|tenant-001|viewer'),
    user: {
      id: 'user-viewer-001',
      email: 'viewer@assistdoc.local',
      fullName: 'Viewer Demo',
      role: 'viewer',
    },
    tenant: {
      id: 'tenant-001',
      name: 'AssistDoc Demo',
      slug: 'assistdoc-demo',
    },
  });

  signIn(email: string, password: string): void {
    if (!email || !password) {
      throw new Error('Credenziali mancanti');
    }

    this.session.set({
      token: btoa(`${email}|tenant-001|viewer`),
      user: {
        id: 'user-' + email.replace(/[^a-z0-9]/gi, '').toLowerCase(),
        email,
        fullName: 'Utente AssistDoc',
        role: email.includes('admin') ? 'super-admin' : 'viewer',
      },
      tenant: {
        id: 'tenant-001',
        name: 'AssistDoc Demo',
        slug: 'assistdoc-demo',
      },
    });
  }

  signOut(): void {
    this.session.set(null);
  }
}

