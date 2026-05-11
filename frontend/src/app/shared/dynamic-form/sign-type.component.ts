
import { Component, ViewChild } from '@angular/core';
import { FieldType } from '@ngx-formly/core';

import { map, take } from 'rxjs/operators';
import SignaturePad from 'signature_pad';
import { IsoData, SignaturePadComponent } from '../signature-pad/signature-pad.component';

export interface ControlData {
    isoData: IsoData;
    dataUrl: string;
}
@Component({
    selector: 'formly-field-sign',
    template: `
    
    <div class="form-group"> 
    <label [attr.for]="id" class="form-control-label control-label" *ngIf="to.label">
      {{ to.label }}
      <ng-container *ngIf="to.required && to.hideRequiredMarker !== true">*</ng-container>
    </label>    
    <div class="d-flex mb-1">
        <div class="d-inline-flex justify-content-start border bg-light">
            <signature-pad #signature [options]="signaturePadOptions" (drawStart)="drawBegin()" (drawEnd)="drawComplete()" > </signature-pad>
        </div>
    </div>
    <div class="btn-group" role="group">
        <button type="button" class="btn btn-sm btn-success rounded-lg me-2" (click)="onConfermaHandler()" [disabled]="isEmpty() || premutoConferma">Conferma</button>
        <button type="button" class="btn btn-sm btn-danger rounded-lg" (click)="onCancellaHandler()" [disabled]="isEmpty()">Cancella</button>
    </div>
    </div>
    `,
    standalone: false
})


export class FormlyFieldSign extends FieldType {

    @ViewChild('signature', { static: true }) signaturePad: SignaturePadComponent;
    
    private toBeCancelled: boolean = false;
    //identifica lo stato del pulsante conferma
    public premutoConferma: boolean = false;

    public signaturePadOptions: Object  = { // passed through to szimek/signature_pad constructor
        minWidth: 1,
        canvasWidth: 500,
        canvasHeight: 150,
        //penColor: 'rgb(255, 0, 0)',
        backgroundColor: '#f8f9fa',
        throttle: 0,
        minDistance: 5
    };

  
    ngAfterViewInit() {
        // this.signaturePad is now available
        //this.signaturePad.set('minWidth', 3); // set szimek/signature_pad options at runtime        
        this.formControl.valueChanges.pipe<ControlData>(take(1)).subscribe(value => {
            if (value) {
                this.toBeCancelled = true;
                if (value.dataUrl){
                    this.signaturePad.fromDataURL(value.dataUrl);
                }                
            }
        });
        this.signaturePad.clear(); // invoke functions from szimek/signature_pad API

    }

    drawComplete() {
        // will be notified of szimek/signature_pad's onEnd event
        //console.log('Completed drawing');
        //console.log(this.signaturePad.toDataURL()); 
        
        // const data: ControlData =  {
        //     //data: this.signaturePad.toData(),
        //     isoData: null,
        //     dataUrl: null,
        // }               
        // this.formControl.setValue(data);
    }

    drawBegin() {
        // will be notified of szimek/signature_pad's onBegin event
        //console.log('Start drawing');
    }


    onCancellaHandler() {
        console.log('onclear clicked...');
        this.premutoConferma = false;
        this.signaturePad.on();
        this.formControl.setValue(null);
        this.signaturePad.clear(); 
        if (this.to.propagateChange  instanceof Function && this.toBeCancelled ) { 
            this.toBeCancelled = false;
            this.to.propagateChange('onCancella', this.model, this.field, this.options);
        }
    }

    onConfermaHandler() {
        //console.log(this.signaturePad.toDataURL());
        this.premutoConferma = true;
        this.signaturePad.off();
        this.setControlValue();
        if (this.to.propagateChange  instanceof Function) { 
            this.toBeCancelled = true;
            //console.log(this.signaturePad.toISOData());
            //console.log(this.signaturePad.toData());
            this.to.propagateChange('onConferma', this.model, this.field, this.options);
        }
    }
     
    setControlValue() {
        
        
        const data: ControlData =  {
            //data: this.signaturePad.toData(),    
            isoData: this.signaturePad.toISOData(),        
            dataUrl: this.signaturePad.toDataURL('image/jpg', 1.0) //con image/jpg per evitare errori di compressione
        }
        this.formControl.setValue(data);
    }

    isEmpty(): boolean {
        return this.signaturePad.isEmpty();
    }
}


