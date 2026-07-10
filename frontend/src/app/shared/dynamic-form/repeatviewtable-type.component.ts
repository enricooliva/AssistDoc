import { Component, ChangeDetectorRef } from '@angular/core';
import { FieldArrayType, FieldType, FieldTypeConfig, FormlyFormBuilder } from '@ngx-formly/core';
import { Observable, of } from 'rxjs';
import { FormArray } from '@angular/forms';
import { map } from 'rxjs/operators';
import { ConfirmationDialogService } from '../confirmation-dialog/confirmation-dialog.service';

@Component({
    selector: 'formly-repeat-view-section',
    template: `  
  <div class="mb-3">
  <div class="mb-1">    
  <button *ngIf="!to.btnHidden" type="button" [ngClass]="showError ? 'btn btn-sm btn-outline-danger rounded' : 'btn btn-sm btn-outline-primary rounded'"  [disabled]="to?.disabled || formControl?.disabled || checkAddButtonDisability()" (click)="onAddNew()"  >              
    <span class="oi oi-plus"></span>
    <span class="ms-2">Aggiungi {{to.label}}</span>
    </button>  
  </div>   
  <label *ngIf="to.label && to.btnHidden == true" [attr.for]="field.key">
    {{ to.label }} <ng-container *ngIf="to.required && to.hideRequiredMarker !== true">*</ng-container>
  </label>

  <div class="table-responsive-md" *ngIf="getValue() && getValue().length > 0; else emptyRows" >
  <table  class="table table-sm table-striped" style="margin-bottom: 4px;">
    <ng-container *ngIf="shouldVisualizeRow()">   
    <tr>
      <!-- Add a checkbox column header -->
      <th *ngIf="to.visibleCheckbox == true" style="width: 15px">
      <input *ngIf="to.visibleAllCheckbox == true" type="checkbox" (change)="toggleSelectAll($event.target.checked)">
      </th>
      <th *ngFor="let tHead of field.props.columns">
        {{ tHead.name }}
      </th>
      <th *ngIf="!to.btnRemoveHidden">
      </th>
    </tr>    
    </ng-container>
   
    <tr *ngFor="let row of getValue(); let i = index;">
    <!-- Bind checkbox to model's selectedRow property and conditionally show based on visibleCheckbox -->
      <td *ngIf="to.visibleCheckbox == true" >      
        <input [class.is-invalid]="showError" class="form-check-input" type="checkbox" [id]="i + '_' + field.key" [checked]="row.selectedRow" (change)="updateValue(row, $event.target.checked)">      
      </td>
      <td *ngFor="let col of field.props.columns">
        {{ (col.pipe  ? ( row[col.prop] | dynamicPipe: col.pipe ) : (row[col.prop])) }}
      </td>
      <td *ngIf="!to.btnRemoveHidden" class="text-end w-auto">
      <div class="btn-group">
        <button *ngIf="!btnRemoveHiddenFunc(model[i])" type="button"  [disabled]="to?.disabled || formControl?.disabled" class="btn btn-sm btn-danger rounded me-1" (click)="onRemoveRepeat(i)"  >              
          <span class="oi oi-trash" title="Rimuovi"></span>  
          <span class="ms-2">Rimuovi</span>
        </button>  
        <button *ngIf="!btnModificaHiddenFunc(model[i])" type="button" [disabled]="to?.disabled || formControl?.disabled" class="btn btn-sm btn-primary rounded" (click)="onUpdateRepeat(i)" >
          <span class="oi oi-pencil"  title="Modifica"></span>
          <span class="ms-2">Modifica</span>
        </button>
      </div>
      </td>    
    </tr>
 
  </table>
  <small *ngIf="to.description" class="form-text text-muted mb-1">{{ to.description }}</small>
  </div>
  <ng-template #emptyRows>
  <br>
  <small class="form-text text-muted mb-1">nessun dato</small>
  </ng-template>
  <div  *ngIf="showError" class="invalid-feedback" [style.display]="'block'">
  <formly-validation-message [field]="field"></formly-validation-message>  
  </div>  
  </div>
  `,
    standalone: false
})

//prova utilizzo ngx-datatable
// <ngx-datatable
// #table  class="bootstrap" 
// [messages]="{emptyMessage: 'NODATA' | translate, totalMessage: 'TOTAL' | translate, selectedMessage: false}"
// [rows]="model"
// [columns]="field.props.columns"
// [columnMode]="'force'"
// [rowHeight]="40"   
// [headerHeight]="'auto'"      
// [footerHeight]="0"
// [scrollbarH]="true"    
// [scrollbarV]="true"  
// [reorderable]="false"      
// [limit]="10">     
// </ngx-datatable>
//<input type="checkbox" [id]="i + '_' + field.key"  [(ngModel)]="row.selectedRow" [name]="i + '_' + field.key">

//Works on a plain array (this.model[this.field.key]), not a FormArray.
export class RepeatviewtableTypeComponent extends FieldType<FieldTypeConfig> {
 
  constructor(public cd: ChangeDetectorRef, private confirmationDialogService: ConfirmationDialogService) {
    super();
  }

  getValue(){    
    return this.model[this.field.key as string];
    //return this.formControl.value;
  }

  checkAddButtonDisability() {
    if (this.model)
      return this.to.disabled || //se il componente è disabilitato
        (!this.to.disabled && (this.to.max != null ? this.model.length == this.to.max : false));
    else
      return this.to.disabled;
  }



  btnRemoveHiddenFunc(model) {
    if (this.to.btnRemoveHiddenFunc) {
      return this.to.btnRemoveHiddenFunc(model);
    }
    return false;
  }

  onAddNew() {

    this.addAction();

    // if (this.to.onAddInitialModel) {
    //   let init = this.to.onAddInitialModel();
    //   this.add(null, init);
    // } else {
    //   this.add();
    //   this.cd.detectChanges();
    // }
  }

  addAction() {          
    this.confirmationDialogService.inputConfirm('Inserimento', '',
      'Conferma',
      'Annulla',
      null,
      'lg',
      '',
      this.to.fieldsPopUp(this.field),//this.field.fieldArray.fieldGroup,
      null,
      null,
      {
        formState: this.options.formState,
        ctr: this.model,
      }, false)
      .then((confirmed) => {
        if (confirmed.result) {          
          this.formControl.setValue(this.addElementToArray(this.getValue(), confirmed.entity));        
          this.formControl.updateValueAndValidity(); 
          if (this.to.onChange) {
            return this.to.onChange(this.model,this.field, 'inserimento');
          }
        }
      }).catch((error) => {
        console.log(error);
        console.log('cancelled');
      });
  }

  onUpdateRepeat(index) {
    this.confirmationDialogService.inputConfirm('Modifica', '',
      'Conferma',
      'Annulla',
      null,
      'lg',
      '',
      this.to.fieldsPopUp(this.field),
      null,
      JSON.parse(JSON.stringify(this.model[index])),
      {
        formState: this.options.formState,
        ctr: this.model,
      }, false)
      .then((confirmed) => {
        if (confirmed.result) {
          this.model[index] = confirmed.entity;          
          //lanciare evento di salvataggio ...
          this.formControl.updateValueAndValidity();
          this.field.form.markAsDirty();          
          if (this.to.onChange) {
            return this.to.onChange(this.model,this.field);
          }
        } 
      }).catch(() => {
        console.log('cancelled');
      });
  }


 

  btnModificaHiddenFunc(model) {
    if (this.to.btnModificaHiddenFunc) {
      return this.to.btnModificaHiddenFunc(model);
    }
    return false;
  }

  
  onRemoveRepeat(index) {
    if (this.to.onRemove) {
     
      (this.to.onRemove(index) as Observable<any>).subscribe(
        data => { this.removeElementAtIndex(this.getValue(),index);},
        err => { },
      );
    } else {            
      this.formControl.setValue(this.removeElementAtIndex(this.getValue(),index));
      //this.cd.detectChanges();
      if (this.to.onChange) {
        return this.to.onChange(index, this.field, 'rimozione');
      }
    }
  }

  addElementToArray<T>(arr: T[], element: T): T[] {
      return [...arr, element];
  }

  removeElementAtIndex<T>(arr: T[], index: number): T[] {
    if (index < 0 || index >= arr.length) {
        throw new Error("Index out of range");
    }
    return arr.filter((_, i) => i !== index);
    // arr.splice(index, 1);
  }

  toggleSelectAll(checked: boolean) {
    const model = this.getValue();
    model.forEach(item => item.selectedRow = checked);
  }

  shouldVisualizeRow(): boolean {
    return this.field.props.columns.some(column => column.name !== '');
  }
  // callbackRemove(index, context){
  //   context.remove(index); 
  //   context.formControl.removeAt(index);
  // }

  updateValue(row, checked: boolean) {
    row.selectedRow = checked;
    // Update form control value manually
    this.field.formControl.patchValue(this.model[this.field.key as string]);
    this.field.formControl.markAsTouched();
    this.field.formControl.markAsDirty();
    this.field.formControl.updateValueAndValidity(); // Trigger validation
  }
}




