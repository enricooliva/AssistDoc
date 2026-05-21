import { Routes } from '@angular/router';
import { authGuard } from './core/auth/auth.guard';
import { AppShellComponent } from './core/layout/app-shell.component';
import { SignInPageComponent } from './features/auth/sign-in-page.component';
import { AuditPageComponent } from './features/audit/audit-page.component';
import { ChatPageComponent } from './features/chat/chat-page.component';
import { DocumentsPageComponent } from './features/documents/documents-page.component';
import { PasswordResetPageComponent } from './features/auth/password-reset-page.component';
import { UserManagementPageComponent } from './features/users/user-management-page.component';

export const appRoutes: Routes = [
  {
    path: 'sign-in',
    component: SignInPageComponent,
  },
  {
    path: 'reset-password',
    component: PasswordResetPageComponent,
  },
  {
    path: '',
    component: AppShellComponent,
    canActivate: [authGuard],
    children: [
      { path: '', pathMatch: 'full', component: ChatPageComponent },
      { path: 'documents', component: DocumentsPageComponent },
      { path: 'users', component: UserManagementPageComponent, canActivate: [authGuard], data: { roles: ['super-admin'] } },
      { path: 'audit', component: AuditPageComponent, canActivate: [authGuard], data: { roles: ['super-admin'] } },
    ],
  },
];
