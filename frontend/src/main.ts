import { bootstrapApplication } from '@angular/platform-browser';
import { provideHttpClient, withInterceptors } from '@angular/common/http';
import { provideRouter } from '@angular/router';
import { provideFormlyCore } from '@ngx-formly/core';
import { withFormlyBootstrap } from '@ngx-formly/bootstrap';
import { AppComponent } from './app/app.component';
import { appRoutes } from './app/app.routes';
import { authInterceptor } from './app/core/http/auth.interceptor';
import { InputFileComponent } from './app/shared/dynamic-form/input-file/input-file.component';

bootstrapApplication(AppComponent, {
  providers: [
    provideRouter(appRoutes),
    provideHttpClient(withInterceptors([authInterceptor])),
    provideFormlyCore([
      ...withFormlyBootstrap(),
      {
        types: [
          {
            name: 'document-file',
            component: InputFileComponent,
            wrappers: ['form-field'],
          },
        ],
      },
    ]),
  ],
}).catch((error) => console.error(error));
