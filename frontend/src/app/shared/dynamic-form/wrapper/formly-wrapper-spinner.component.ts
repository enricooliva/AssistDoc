import { Component, ChangeDetectionStrategy } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'formly-wrapper-spinner',
    template: `
    <div class="position-relative">
      <ng-container #fieldComponent></ng-container>
      <div *ngIf="showSpinner" class="spinner-border spinner-border-sm text-primary" 
      role="status" aria-hidden="true"
      style="position: absolute; top: 30%; right: 2rem; pointer-events: none;"></div>
    </div>
  `,
    changeDetection: ChangeDetectionStrategy.OnPush,
    standalone: false
})
export class FormlyWrapperSpinnerComponent extends FieldWrapper {
  get showSpinner() {
    // You can customize this condition as needed, for example:
    // show spinner if the parent field has class 'formly-loader' or if props.loading is true
    return this.field.className?.includes('formly-loader') //|| this.to.loading === true;
  }
}



