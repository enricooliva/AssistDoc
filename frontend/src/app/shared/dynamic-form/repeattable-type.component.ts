import { Component, ChangeDetectorRef } from '@angular/core';
import { FieldArrayType, FormlyFormBuilder } from '@ngx-formly/core';
import { Observable, of } from 'rxjs';
import { FormArray } from '@angular/forms';
import { map } from 'rxjs/operators';
import { ConfirmationDialogService } from '../confirmation-dialog/confirmation-dialog.service';

@Component({
  selector: 'formly-repeat-section',
  template: `  
  <div class="mb-3">
  <div class="mb-1">    
    <button *ngIf="!to.btnHidden" type="button" [ngClass]="showError ? 'btn btn-sm btn-outline-danger rounded' : 'btn btn-sm btn-outline-primary rounded'" [disabled]="to?.disabled || formControl?.disabled || checkAddButtonDisability()" (click)="onAddNew()"  >              
      <span class="oi oi-plus"></span>
      <span class="ms-2">Aggiungi {{to.label}}</span>
    </button>  
  </div>   
  <small *ngIf="to.description" class="form-text text-muted mb-1">{{ to.description }}</small>

  <div class="table-responsive-md">
  <table *ngIf="model.length > 0"  class="table table-sm table-striped">  
  <tr>
    <th *ngFor="let tHead of field.props.columns">
      {{ tHead.name }}
    </th>
    <th *ngIf="!to.btnRemoveHidden">
    </th>
  </tr>

  <ng-container *ngFor="let row of model; let i = index;">
  <!-- Main row -->
  <tr>
    <ng-container *ngFor="let col of field.props.columns">
    <td>
      <!-- Display main row content if it's not an array -->
      <div *ngIf="row[col.prop] && !isArray(row[col.prop])">
        {{ col.pipe ? (row[col.prop] | dynamicPipe: col.pipe) : row[col.prop] }}
      </div>
      
      <!-- Display multi-valued column content -->
      <div *ngIf="row[col.prop] && isArray(row[col.prop])">
      <div *ngFor="let subValue of row[col.prop]">
        <ng-container *ngIf="subValue.selectedRow !== undefined ? subValue.selectedRow : true">
          {{ subValue.selectedRow ? subValue[col.subprop] : '' }}
          <br *ngIf="subValue.selectedRow ? subValue[col.subprop] : ''"> <!-- Add line break only if a value is shown -->
        </ng-container>
      </div>
      </div>
    </td>
    </ng-container>
    <!-- Actions -->
    <td *ngIf="!to.btnRemoveHidden" class="text-end w-auto">
    <div class="btn-group">
      <button *ngIf="!btnRemoveHiddenFunc(model[i])" type="button" [disabled]="to?.disabled || formControl?.disabled"  class="btn btn-sm btn-danger rounded me-1" (click)="onRemoveRepeat(i)"  >              
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
  </ng-container>
  </table>
  </div>

  <div *ngIf="model.length === 0">
  <small  class="form-text text-muted mb-1">Nessun elemento inserito.</small>
  </div>

  <div *ngIf="showError" class="invalid-feedback" [style.display]="'block'">
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

export class RepeattableTypeComponent extends FieldArrayType {
  constructor(public cd: ChangeDetectorRef, private confirmationDialogService: ConfirmationDialogService) {
    super();
  }

  ngOnInit() {
    this.to.add = this.add.bind(this);
    this.to.remove = this.remove.bind(this);
    this.to.setArray = this.setArray.bind(this);

    //this.field.model[this.field.key as string] = [];

    //console.log('RepeattableTypeComponent init',this.model);

    // if (this.to.min && this.to.min > 0) {
    //   for (let index = count; index < this.to.min; index++) {
    //     setTimeout(() => { this.add(); }, 0);
    //   }
    //   this.cd.detectChanges();
    // }

  }

  setArray(values: []) {
    this.clearFormArray();
    values.forEach(x => { if (x) this.add(null, x) });
    this.cd.detectChanges();
  }

  checkAddButtonDisability() {
    if (this.model)
      return this.to.disabled || //se il componente è disabilitato
        (!this.to.disabled && (this.to.max != null ? this.model.length == this.to.max : false));
    else
      return this.to.disabled;
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

  btnRemoveHiddenFunc(model) {
    if (this.to.btnRemoveHiddenFunc) {
      return this.to.btnRemoveHiddenFunc(model);
    }
    return false;
  }

  onRemoveRepeat(index) {
    if (this.to.onRemove) {
      let id = this.formControl.at(index).get('id').value;
      (this.to.onRemove(id) as Observable<any>).subscribe(
        data => { this.remove(index); },
        err => { },
      );
    } else {
      const removed = this.model[index];
      this.remove(index);
      this.cd.detectChanges();
      this.form.updateValueAndValidity();
      if (this.to.onChange) {
        return this.to.onChange(removed, this.field, 'rimozione');
      }
    }
  }

  onUpdateRepeat(index) {
    this.confirmationDialogService.inputConfirm('Modifica', '',
      'Conferma',
      'Annulla',
      null,
      'lg',
      '',
      this.to.fieldsPopUp(this.cd),
      null,
      JSON.parse(JSON.stringify(this.model[index])),
      {
        formState: this.options.formState,
        ctr: this.model,
      }, false)
      .then((confirmed) => {
        if (confirmed.result) {

          // 1. update existing row instead of remove/add
          this.model[index] = confirmed.entity;

          // 2. update the related form control
          const rowControl = this.formControl?.get([index]);
          rowControl?.setValue(confirmed.entity, { emitEvent: true });
          rowControl?.markAsDirty();
          rowControl?.markAsTouched();
          rowControl?.updateValueAndValidity({ emitEvent: true });

          // 3. refresh parent array/form too
          this.formControl?.updateValueAndValidity({ emitEvent: true });
          this.field.form?.updateValueAndValidity({ emitEvent: true });
          this.field.form?.markAsDirty();

          // 4. let Formly re-evaluate expressions if needed
          this.options?.detectChanges?.(this.field);

          if (this.to.onChange) {
            this.to.onChange(this.model, this.field, 'modifica');
          }
        }
      }).catch((error) => {
        console.log(error);
        console.log('cancelled');
      });
  }


  clearFormArray() {
    while (this.formControl.length !== 0) {
      this.remove(0);
    }
  }


  addAction() {
    this.confirmationDialogService.inputConfirm('Inserimento', '',
      'Conferma',
      'Annulla',
      null,
      'lg',
      '',
      this.to.fieldsPopUp(this.cd),//this.field.fieldArray.fieldGroup,
      null,
      null,
      {
        formState: this.options.formState,
        ctr: this.model,
      }, false)
      .then((confirmed) => {
        if (confirmed.result) {
          this.add(null, confirmed.entity);
          this.formControl.updateValueAndValidity();
          this.formControl.markAsTouched();
          if (this.to.onChange) {
            this.to.onChange(this.model, this.field, 'inserimento');
          }
          this.cd.detectChanges();
        } else {
          this.field.formControl.markAsTouched();
          this.field.formControl.markAsDirty();
          this.field.formControl.updateValueAndValidity();
        }
      }).catch((error) => {
        console.log(error);
        console.log('cancelled');
      });
  }

  btnModificaHiddenFunc(model) {
    if (this.to.btnModificaHiddenFunc) {
      return this.to.btnModificaHiddenFunc(model);
    }
    return false;
  }

  getFieldColumnsLength(): number {
    return this.field.props.columns.length;
  }

  isArray(value: any): boolean {
    const val = Array.isArray(value);
    return val;
  }
  // callbackRemove(index, context){
  //   context.remove(index); 
  //   context.formControl.removeAt(index);
  // }
}




