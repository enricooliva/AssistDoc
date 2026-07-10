import { inject } from '@angular/core';
import { CanActivateFn, Router } from '@angular/router';
import { AuthService } from './auth.service';

export const authGuard: CanActivateFn = (route) => {
  const authService = inject(AuthService);
  const router = inject(Router);
  const requiredRoles = (route.data?.['roles'] as string[] | undefined) ?? [];

  if (authService.session()) {
    if (requiredRoles.length > 0 && !authService.hasAnyRole(requiredRoles)) {
      authService.setAccessDenied();
      return router.createUrlTree(['/']);
    }

    return true;
  }

  return router.createUrlTree(['/sign-in']);
};
