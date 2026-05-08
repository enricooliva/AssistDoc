import { HttpInterceptorFn } from '@angular/common/http';
import { inject } from '@angular/core';
import { catchError, throwError } from 'rxjs';
import { AuthService } from '../auth/auth.service';

export const authInterceptor: HttpInterceptorFn = (request, next) => {
  const authService = inject(AuthService);
  const session = authService.session();

  if (!session) {
    return next(request);
  }

  return next(
    request.clone({
      setHeaders: {
        Authorization: `Bearer ${session.token}`,
      },
    }),
  ).pipe(
    catchError((error) => {
      if (error.status === 401) {
        authService.handleUnauthorized();
      }

      if (error.status === 403) {
        authService.setAccessDenied();
      }

      return throwError(() => error);
    }),
  );
};
