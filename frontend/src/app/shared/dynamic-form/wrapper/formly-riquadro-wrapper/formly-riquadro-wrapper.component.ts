import { Component, OnInit, ViewContainerRef, ViewChild } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'formly-riquadro-wrapper',
    template: `  
  <div class="border border-primary pt-2 pe-2 ps-2 mb-2" style="border-radius: 3px !important;" >
    <h5 *ngIf="to?.title" class="{{ to?.class ? to.class : ''}}">{{ to.title }}</h5>
    <ng-container #fieldComponent></ng-container>
    
    <div *ngIf="showError" class="invalid-feedback" [style.display]="'block'">
      <formly-validation-message [field]="field"></formly-validation-message>
    </div>
    <small *ngIf="to.description" class="form-text text-muted">{{ to.description }}</small>
  
  </div>
  `,
    styles: [],
    standalone: false
})
export class FormlyRiquadroWrapperComponent extends FieldWrapper {
  @ViewChild('fieldComponent', { read: ViewContainerRef, static: true }) fieldComponent: ViewContainerRef;  

}



