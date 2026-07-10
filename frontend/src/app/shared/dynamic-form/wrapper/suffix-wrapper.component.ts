import { Component, ViewChild, ViewContainerRef } from '@angular/core';
import { FieldWrapper } from '@ngx-formly/core';

@Component({
    selector: 'formly-suffix-wrapper',
    template: `  
  <ng-container #fieldComponent></ng-container>
  <ng-container *ngIf="to.loading">
    Caricamento ...
  </ng-container>
  `,
    standalone: false
})
export class SuffixWrapperComponent extends FieldWrapper {  
  @ViewChild('fieldComponent', { read: ViewContainerRef, static: true }) fieldComponent: ViewContainerRef;
}


