import { ChangeDetectorRef, Component, ElementRef, HostListener, OnDestroy, OnInit, Sanitizer, ViewChild } from '@angular/core';
import { AbstractControl } from '@angular/forms';
import { DomSanitizer } from '@angular/platform-browser';
import { FieldType } from '@ngx-formly/core';
import { of } from 'rxjs';
import { ISignResponse } from 'src/app/model/common.model';
import { SignNamiralService } from '../sign.service';
import { encode, decode } from 'base64-arraybuffer'


@Component({
    selector: 'formly-field-sign',
    template: `
    
    <div class="form-group">                 
        <div class="d-flex">        
            <button type="button" class="btn btn-sm btn-success rounded-lg me-2" [disabled]="to?.disabled" (click)="capturesig()">{{ to.label }}</button>
        </div>
        
    </div>
    `,
    standalone: false
})

// <label [attr.for]="id" class="form-control-label control-label" *ngIf="to.label">
//       {{ to.label }}
//       <ng-container *ngIf="to.required && to.hideRequiredMarker !== true">*</ng-container>
//     </label>    
//<div class="d-inline-flex justify-content-start border bg-light">
//  <div id="imageBox" class="boxed" style="height:35mm;width:60mm; border:1px solid #d3d3d3;">

export class FormlyFieldSignNamiral extends FieldType implements OnInit, OnDestroy {

    @ViewChild('imageBox', { static: true }) imageBox: ElementRef<HTMLElement>;

    private toBeCancelled: boolean = false;
    //identifica lo stato del pulsante conferma
    public premutoConferma: boolean = false;

    public signedDocument: string;
    private pdfResult: Blob = null;

    static currentFormControl: FormlyFieldSignNamiral;

    constructor(private sanitizer: DomSanitizer, public signService: SignNamiralService, private cdr: ChangeDetectorRef) {
        super()
    }

    ngOnDestroy(): void {
        console.log('clearmemory');
        this.signService.clearMemory();
    }

    ngOnInit(): void {
    }

    ngAfterViewInit() {
    }

    @HostListener('document:responseerror', ['$event'])
    onResponseError(ev)
    {        
        if (ev.detail == this.key || ev.detail == 'offline_callback') {
            this.formState.isLoading = false;
            this.cdr.detectChanges();
        }
    }

    @HostListener('document:signaturetext', ['$event'])
    onSignatureText(ev) {
        //console.log(ev); // element that triggered event, in this case HTMLUnknownElement
        if (ev.detail == this.key) {
            //console.log('on signature text');
            this.setControlValue();
        }            
    }
    
    setControlValue() {

        this.cdr.detectChanges();

        this.pdfResult['arrayBuffer']().then(value => {
            const data = {
                filevalue: encode(value)
            }            
            this.formControl.setValue(data);    
        });
    
    }

    capturesig() {
        //il componente può essere associato a un controllo di firma singola o multipla
        //ad esempio nel caso del contratto ho una firma multipla

        FormlyFieldSignNamiral.currentFormControl = this;
        //inizio processo di firma: caricamento file e firma
        FormlyFieldSignNamiral.currentFormControl.formState.isLoading = true;
        if (this.to.ordinefirma != null && this.to.ordinefirma > 0){
            //significa che una firma è già stata fatta
            this.signService.loadSignedDocumentFromMemory('https://localhost:7777/files/memory/signeddocument.pdf',this.loadDocumentEnd);            
        }else{            
            this.signService.loadDocument(this.formState.extraData.ctr.id, this.to.tipo_modello == 'modulo' ? this.model.tipo_modello : this.to.tipo_modello, this.loadDocumentEnd);
        }
        
    }


    signEnd(response: ISignResponse) {
        //console.log('--fine firma--');
        //console.log(response);
        if (!response.success) {
            //caso di errore
            console.log(response);
            FormlyFieldSignNamiral.currentFormControl.signService.messageService.error(response.errorMessage || "Non è stato prodotto nessun file");
            FormlyFieldSignNamiral.currentFormControl.signedDocument = null;
            document.dispatchEvent(new CustomEvent('responseerror', { bubbles: true, detail: FormlyFieldSignNamiral.currentFormControl.key }));     
        }
        else {
            if (response.signedDocument) {
                FormlyFieldSignNamiral.currentFormControl.signedDocument = response.signedDocument;
                //ricaricare documento nella preview
                //memorizzare il file in filevalue ...   
                //leggi il documento dopo la firma
                FormlyFieldSignNamiral.currentFormControl.signService.readSignedDocumentArray((response) => {                    
                    //lettura
                    FormlyFieldSignNamiral.currentFormControl.formState.isLoading = false;
                    if (!response.success) {                            
                        //caso di errore
                        // console.log(response);
                        FormlyFieldSignNamiral.currentFormControl.signService.messageService.error(response.errorMessage || "Non è stato caricato nessun file");                        
                        document.dispatchEvent(new CustomEvent('responseerror', { bubbles: true, detail: FormlyFieldSignNamiral.currentFormControl.key }));                
                    } else {
                        const bytearray = new Uint8Array(response.content)
                        FormlyFieldSignNamiral.currentFormControl.pdfResult = new Blob([bytearray], { type: "application/pdf" });

                        FormlyFieldSignNamiral.currentFormControl.pdfResult['arrayBuffer']().then(value => {
                            FormlyFieldSignNamiral.currentFormControl.options.formState.pdfSrc = of(value)
                            FormlyFieldSignNamiral.currentFormControl.formControl.markAsDirty();
                        });
                        //fire event di fine firma                
                        //console.log('Fire event signaturetext');
                        const domEvent = new CustomEvent('signaturetext', { bubbles: true, detail: FormlyFieldSignNamiral.currentFormControl.key });
                        document.dispatchEvent(domEvent);
                    }
                    //console.log('--fine readSignedDocumentArray--');
                });

            }
        }
    }


    //callback
    loadDocumentEnd(response: ISignResponse) {
        //console.log('---fine loadDocument---');
        //console.log(response);
        if (!response.success || response.errorMessage) {
            FormlyFieldSignNamiral.currentFormControl.signService.messageService.error(response.errorMessage || "Non è stato caricato nessun file");
            FormlyFieldSignNamiral.currentFormControl.signedDocument = null;
            document.dispatchEvent(new CustomEvent('responseerror', { bubbles: true, detail: FormlyFieldSignNamiral.currentFormControl.key }));     
        }
        else {            
            FormlyFieldSignNamiral.currentFormControl.signStart();
        }
    }


    signStart(){
        //calcola la posizione della firma
        let position = FormlyFieldSignNamiral.currentFormControl.options.formState.widgetPDFSignaturePosition;
        //se position è un array prendi il numero indicato dal bottone di firma 
        if (Array.isArray(position)){
            position = FormlyFieldSignNamiral.currentFormControl.options.formState.widgetPDFSignaturePosition[FormlyFieldSignNamiral.currentFormControl.field.props.ordinefirma];
        }

        //firma 
        FormlyFieldSignNamiral.currentFormControl.signService.signStart(position, FormlyFieldSignNamiral.currentFormControl.signEnd);

    }



}


