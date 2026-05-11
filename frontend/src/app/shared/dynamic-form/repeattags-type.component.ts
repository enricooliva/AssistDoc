import { Component, ChangeDetectorRef } from '@angular/core';
import { FieldArrayType, FormlyFieldConfig } from '@ngx-formly/core';
import { ConfirmationDialogService } from '../confirmation-dialog/confirmation-dialog.service';
import { last, Observable } from 'rxjs';

@Component({
  selector: 'formly-repeat-tags',
  standalone: false,
  template: `
  <div>
    <ng-container *ngFor="let row of model">
      <ng-container *ngIf="row.fixcondition_edit">
        <formly-form 
          [fields]="to?.fieldsPopUp()" 
          [model]="row" 
          [form]="form" 
          [options]="options">
        </formly-form>
      </ng-container>
    </ng-container>

    <!-- Add Button -->
    <button
      *ngIf="!to.btnHidden"
      type="button"
      class="btn btn-sm btn-outline-primary rounded-lg mb-2"
      [disabled]="to?.disabled || formControl?.disabled || checkAddButtonDisability()"
      (click)="onAddNew()">
      <span class="oi oi-plus"></span>
      <span class="ms-1">Aggiungi {{ to.label }}</span>
    </button>

    <!-- Tags no fixcondition --> 
    <ng-container *ngFor="let row of model; let i = index">
      <div *ngIf="!row.fixcondition_edit && hasRenderableRow(i)" class="row mt-2">

      <!-- Field -->
      <div class="col-md-4">
         <formly-field [field]="getFieldField(i, row)"></formly-field>
      </div>

      <!-- Operator -->
      <div class="col-md-2">
      <formly-field [field]="getFieldOperator(i, row)"></formly-field>        
      </div>

      <!-- Value -->
      <div class="col-md-5" >
      <formly-field [field]="getFieldValue(i, row)"></formly-field>      
      </div>

      <!-- Action Buttons -->
      <div *ngIf="!row.fixcondition" class="col-md">
        <!-- Edit 
        <button *ngIf="!btnEditHiddenFunc(row)" 
                type="button"  
                class="btn btn-sm ms-2 btn-outline-primary p-1 border-0"       
                [disabled]="row.fixcondition"     
                (click)="onEditRow(i)" 
                title="Modifica">
          <span class="oi oi-pencil"></span>
        </button>-->

        <!-- Remove -->
        <button *ngIf="!btnRemoveHiddenFunc(row)" 
                type="button" 
                class="btn btn-sm btn-outline-danger mt-1"              
                [disabled]="row.fixcondition"
                (click)="onRemoveRow(i)" 
                title="Rimuovi">
          <span class="oi oi-trash"></span>
        </button>
      </div>
  
      </div>
    </ng-container>

    <!-- Empty state -->
    <small class="form-text text-muted ms-1 mt-2 d-block">
      {{ model?.length > 0 
          ? 'Filtri attivi. Premi "Avvia la ricerca" per aggiornare i risultati.' 
          : 'Nessun filtro inserito. Premi "Avvia la ricerca" per visualizzare i risultati.' }}
    </small>
 

    <!-- Validation -->
    <div *ngIf="showError" class="invalid-feedback d-block">
      <formly-validation-message [field]="field"></formly-validation-message>
    </div>
  </div>
  `
})
export class RepeattagsTypeComponent extends FieldArrayType {

  constructor(private cd: ChangeDetectorRef, private dialogService: ConfirmationDialogService) {
    super();
  }

  /** Add new tag via popup */
  onAddNew() {
    this.dialogService.inputConfirm('Inserimento', '', 'Conferma', 'Annulla',
      null, 'lg', '', this.to.fieldsPopUp(this.cd), null, null,
      { formState: this.options.formState, ctr: this.model }, false)
      .then(result => {
        if (result.result) {
          const newRule = JSON.parse(JSON.stringify(result.entity));
          this.add(null, newRule);
          this.cd.detectChanges();
          if (this.to.onChange) this.to.onChange(this.model, this.field, 'inserimento');

          const lastIndex = this.field.fieldGroup.length -1;
          const fieldValue = this.getFieldValue(lastIndex, null);          
          if (fieldValue && fieldValue.formControl) {
            setTimeout(() => fieldValue.focus = true, 0);
          }
          

        }
      }).catch((error) => {
        console.log(error);
        console.log('cancelled');
      });
  }

  /** Edit tag */
  onEditRow(index: number) {
    console.log(this.model);
    const rowCopy = JSON.parse(JSON.stringify(this.model[index]));
    this.dialogService.inputConfirm('Modifica', '', 'Conferma', 'Annulla',
      null, 'lg', '', this.to.fieldsPopUp(this.cd), null, rowCopy,
      { formState: this.options.formState, ctr: this.model }, false)
      .then(result => {
        if (result.result) {
          this.model[index] = result.entity;
          // Force Formly to re-evaluate the FormArray negli eventi perdo il valore perchè nella onit del cambio di 
          // field viene registrato un evento che al cambiamento setvalue a null 
          //this.field.formControl.patchValue([...this.model]);
          this.field.formControl.patchValue([...this.model], { emitEvent: false });
          this.field.options?.build?.(this.field);
          this.cd.detectChanges();
          if (this.props.onChange) {
            this.to.onChange(this.model, this.field, 'modifica');
          }
        }
      }).catch((error) => {
        console.log(error);
        console.log('cancelled');
      });
  }

  /** Remove tag */
  onRemoveRow(index: number) {
    if (this.to.onRemove) {
      (this.to.onRemove(index) as Observable<any>).subscribe(() => this.remove(index));
    } else {
      this.remove(index);
      this.formControl.updateValueAndValidity();
      if (this.to.onChange) this.to.onChange(this.model, this.field, 'rimozione');
    }
  }

  getTagLabel(row: any): string {
  
  
    const fieldLabel = row.field_label || row.field || '';
    const operatorLabel = row.operator_label || row.operator || '';
    const valueLabel = row.value_label || row.value || '';

    return `${fieldLabel} ${operatorLabel} ${valueLabel}`;
  }

  /** Badge color based on operator */
  getBadgeClass(row): string {
    return row.fixcondition ? 'bg-secondary text-light' : 'bg-primary';
    switch (row.operator) {
      case '=': return 'bg-success';
      case '!=': return 'bg-danger';
      case 'contains': return 'bg-info';
      case '>':
      case '>=':
      case '<':
      case '<=': return 'bg-warning text-dark';
      case 'In': return 'bg-primary';
      case 'NotIn': return 'bg-secondary';
      default: return 'bg-dark';
    }
  }

  /** Button visibility */
  btnRemoveHiddenFunc(row: any) { return this.to.btnRemoveHiddenFunc?.(row) || false; }
  btnEditHiddenFunc(row: any) { return this.to.btnEditHiddenFunc?.(row) || false; }

  /** Disable add button if max reached */
  checkAddButtonDisability() {
    return this.to.disabled || (this.to.max != null && this.model.length >= this.to.max);
  }



  hasRenderableRow(rowIndex: number): boolean {
    return !!this.field?.fieldGroup?.[rowIndex]?.fieldGroup?.[0]?.fieldGroup;
  }
  getFieldField(rowIndex: number, row: any): FormlyFieldConfig {
    const borderColor = row?.fixcondition
      ? 'border: 1px solid var(--bs-light-border-subtle)'     // bordo grigio se fixcondition = true
      : 'border: 1px solid var(--bs-secondary-border-subtle)'; // bordo secondario se modificabile

    let result = this.field.fieldGroup[rowIndex].fieldGroup[0].fieldGroup[0];
    result.wrappers = [];
    // result.props.attributes = {
    //   style: `background-color: transparent;  ${borderColor}; padding-top: 0; padding-bottom:0`
    // };    
    result.props.disabled = true;
    return result;
  }

  getFieldValue(rowIndex: number, row: any): FormlyFieldConfig {
    const borderColor = row?.fixcondition
      ? 'border: 1px solid var(--bs-light-border-subtle)'     // bordo grigio se fixcondition = true
      : 'border: 1px solid var(--bs-secondary-border-subtle)'; // bordo secondario se modificabile

    let result = this.field.fieldGroup[rowIndex].fieldGroup[0].fieldGroup[2].fieldGroup[0];
    result.wrappers = [];
    // result.props.attributes = {
    //   style: `background-color: transparent;  ${borderColor}; padding-top: 0; padding-bottom:0`
    // };    
    result.props.disabled = !!row?.fixcondition;

    // Attenzione all'updateGenericField che se viene chiamato con il parametro reset a true cancella il value e value_label
    // Keep rendered control value aligned when rules are injected/updated externally.
    // if (result.formControl && row) {
    //   result.formControl.setValue(row.value, { emitEvent: false });
    // }

    return result;
  }

  getFieldOperator(rowIndex: number, row: any): any {
    const borderColor = row?.fixcondition
      ? 'border: 1px solid var(--bs-light-border-subtle)' //      // bordo grigio se fixcondition = true
      : 'border: 1px solid var(--bs-success-border-subtle)';   // bordo verde se modificabile

    let result = this.field.fieldGroup[rowIndex].fieldGroup[0].fieldGroup[1];
    result.wrappers = [];
    // result.props.attributes = {
    //   style: `background-color: transparent;  ${borderColor}; padding-top: 0; padding-bottom:0`
    // };
    result.props.disabled = !!row?.fixcondition;
    return result;
  }

}

