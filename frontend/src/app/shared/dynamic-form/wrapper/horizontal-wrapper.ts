import { Component, ViewChild, ViewContainerRef } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'formly-horizontal-wrapper',
    template: `
  <div class="form-group row"
    [ngClass]="to.wrapperClass || 'pb-3'">
    <label [attr.for]="id"  [className]="to.classLabel ? to.classLabel + ' col-form-label' : 'col-sm-3 col-form-label' " *ngIf="to.label">
      {{ to.label }}
      <ng-container *ngIf="to.required && to.hideRequiredMarker !== true">*</ng-container>
    </label>
    <div class="col-md">
      <ng-template #fieldComponent></ng-template>
    </div>
    
    <div *ngIf="showError" class="col-sm-3 invalid-feedback d-block">
      <formly-validation-message [field]="field"></formly-validation-message>
    </div>
    <small *ngIf="to.description" class="form-text text-muted">{{ to.description }}</small>
  </div>
  `,
    standalone: false
})
export class FormlyHorizontalWrapper extends FieldWrapper {
  @ViewChild('fieldComponent', { read: ViewContainerRef, static: true }) fieldComponent: ViewContainerRef;  
}


