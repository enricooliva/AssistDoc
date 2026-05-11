import { Component, Input } from '@angular/core';
import { FieldType } from '@ngx-formly/core';
import { Observable } from 'rxjs';


@Component({
    selector: 'formly-pdfviewer-type',
    template: `
  <ngx-loading [show]="getLoadingState()" style="height: 70vh;" [config]="{ backdropBorderRadius: '0px' }"></ngx-loading>   
  <div class="row">
    <div class="col" *ngIf="to.pdfSrc">
    <ng-container *ngIf="to.pdfSrc | async as pdfFilevalue">                
    <ng-container *ngIf="isArrayBuffer(pdfFilevalue)">
        <app-pdf-view [pdfSrc]="pdfFilevalue" [isVisibleButtons]="to.isVisibleButtons !== undefined ? to.isVisibleButtons : true"></app-pdf-view>
    </ng-container>
    </ng-container>  
    </div>
  </div>
  `,
    standalone: false
})
export class FormlyFieldPdfViewer extends FieldType {

  getLoadingState() {
    if (this.options?.formState && 'isPdfLoading' in this.options.formState) {
      return this.options.formState.isPdfLoading;
    }

    return this.options?.formState?.isLoading;
  }

  isArrayBuffer(candidate){
    return candidate instanceof ArrayBuffer;
  }
  
}


