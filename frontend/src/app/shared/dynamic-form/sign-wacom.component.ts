import { ChangeDetectorRef, Component, ElementRef, HostListener, OnDestroy, OnInit, ViewChild } from '@angular/core';
import { AbstractControl } from '@angular/forms';
import { FieldType } from '@ngx-formly/core';

import { map, take } from 'rxjs/operators';
import SignaturePad from 'signature_pad';
import { IsoData, SignaturePadComponent } from '../signature-pad/signature-pad.component';
import { BITMAP_BACKGROUNDCOLOR, BITMAP_IMAGEFORMAT, BITMAP_INKCOLOR, BITMAP_INKWIDTH, BITMAP_PADDING_X, BITMAP_PADDING_Y, HTMLIds } from '../wacom-sic-capt-x/SigCaptX-Globals';
import { SessionControl } from '../wacom-sic-capt-x/SigCaptX-SessionControl';
import { SigCapture } from '../wacom-sic-capt-x/wacom-sic-capt-x.component';

export interface ControlData {
    isoData: IsoData;
    dataUrl: string;
}
@Component({
    selector: 'formly-field-sign',
    template: `
    
    <div class="form-group">     
        <div class="d-flex flex-column mb-1">        
            <div #imageBox class="boxed" style="height:35mm;width:120mm; border:1px solid #d3d3d3;">
        </div>       
        <div class="d-flex">        
            <button type="button" class="btn btn-sm btn-success rounded-lg me-2" (click)="capturesig()">{{ to.label }}</button>
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

export class FormlyFieldSignWacom extends FieldType implements OnInit, OnDestroy {

    @ViewChild('imageBox',{static: true}) imageBox: ElementRef<HTMLElement>;

    private toBeCancelled: boolean = false;
    //identifica lo stato del pulsante conferma
    public premutoConferma: boolean = false;

    public dataUrl: string;
    
    static currentFormControl: FormlyFieldSignWacom; 

    constructor(public cdr: ChangeDetectorRef){
        super()
    }

    ngOnDestroy(): void {
        if (window.sdkPtr && window.sdkPtr.running) {
            window.sdkPtr.destroySession(() => { });
        }
    }

    ngOnInit(): void {
        SigCapture.actionWhenRestarted();
    }

    ngAfterViewInit() {

         //TODO FARE ARRIVARE I DATI FIRMATARIO ...    
        SigCapture.HTMLTagIds = new HTMLIds('Enrico', 'Oliva', this.imageBox.nativeElement);  
         
        if (this.formControl.value) {
            if (SigCapture.HTMLTagIds.imageBox){
                let img = new Image();
                img.src = this.formControl.value.dataUrl;
                if( null == SigCapture.HTMLTagIds.imageBox.firstChild)
                {
                    SigCapture.HTMLTagIds.imageBox.appendChild(img);
                }
                else
                {
                    SigCapture.HTMLTagIds.imageBox.replaceChild(img, SigCapture.HTMLTagIds.imageBox.firstChild);
                }
            }                           
        }
              
    }

    @HostListener('document:signaturetext', ['$event'])
    onSignatureText(ev) {
      //console.log(ev); // element that triggered event, in this case HTMLUnknownElement
      if (ev.detail == this.key){
        console.log('on signature text');
        this.setControlValue();
      }
      
    }

    setControlValue() {                
        const data: ControlData =  {
            //data: this.signaturePad.toData(),    
            isoData: SigCapture.HTMLTagIds.textSig,        
            dataUrl: this.dataUrl,        
        }
        this.formControl.setValue(data);
        if ( this.to.propagateChange  instanceof Function) {             
            //console.log(this.signaturePad.toISOData());
            //console.log(this.signaturePad.toData());
            this.to.propagateChange('onConferma',  this.model,  this.field,  this.options);
        }
       
    }


    static setControlValue() {                
        const data: ControlData =  {
            //data: this.signaturePad.toData(),    
            isoData: SigCapture.HTMLTagIds.textSig,        
            dataUrl: FormlyFieldSignWacom.currentFormControl.dataUrl,        
        }
        FormlyFieldSignWacom.currentFormControl.formControl.setValue(data);
        if ( FormlyFieldSignWacom.currentFormControl.to.propagateChange  instanceof Function) {             
            //console.log(this.signaturePad.toISOData());
            //console.log(this.signaturePad.toData());
            FormlyFieldSignWacom.currentFormControl.to.propagateChange('onConferma',  FormlyFieldSignWacom.currentFormControl.model,  FormlyFieldSignWacom.currentFormControl.field,  FormlyFieldSignWacom.currentFormControl.options);
        }
       
    }

    capturesig() {

        FormlyFieldSignWacom.currentFormControl = this;
        
        //TODO FARE ARRIVARE I DATI FIRMATARIO ...    
        //<HTMLElement>document.getElementById("imageBox")
        SigCapture.HTMLTagIds = new HTMLIds('Enrico', 'Oliva', this.imageBox.nativeElement);  

        if (window.sdkPtr.running) {
            this.Capture();
        }
        else {
            SigCapture.actionWhenRestarted();
            return;
        }
    }

    Capture() {
        // Construct a hash object to contain the hash
        SigCapture.hash = new window.sdkPtr.Hash(this.onHashConstructor);
    }

    onHashConstructor(hashV, status) {
        if (window.sdkPtr.ResponseStatus.OK == status) {
            SigCapture.GetHash(hashV, FormlyFieldSignWacom.onGetInitialHash);
        }
        else {
            SigCapture.print("Hash Constructor error: " + status);
            if (window.sdkPtr.ResponseStatus.INVALID_SESSION == status) {
                SigCapture.print("Error: invalid session. Restarting the session.");
                SigCapture.actionWhenRestarted();
            }
        }
    }


    // Once the hash value has been calculated successfully next step is to capture the signature
    static onGetInitialHash = () => {
        var firstName = SigCapture.HTMLTagIds.firstName;
        var lastName = SigCapture.HTMLTagIds.lastName;
        var fullName = firstName + " " + lastName;

        SigCapture.dynCapt.Capture(SigCapture.sigCtl, fullName, "Approvazione", SigCapture.hash, null, FormlyFieldSignWacom.onDynCaptCapture);
    }

    static onDynCaptCapture = (dynCaptV, SigObjV, status) => {
        if (window.sdkPtr.ResponseStatus.INVALID_SESSION == status) {
            SigCapture.print("Error: invalid session. Restarting the session.");
            SigCapture.actionWhenRestarted();  // See SigCaptX-SessionControl.ts
        }
        else {
            /* Check the status returned from the signature capture */
            switch (status) {
                case window.sdkPtr.DynamicCaptureResult.DynCaptOK:
                    SigCapture.sigObj = SigObjV;  // Populate the sigObj static property for later use
                    SigCapture.print("Signature captured successfully");

                    /* Set the RenderBitmap flags as appropriate depending on whether the user wants to use a picture image or B64 text value */
                    //var outputFlags = window.sdkPtr.RBFlags.RenderOutputBase64 | window.sdkPtr.RBFlags.RenderColor32BPP;
                    var outputFlags = window.sdkPtr.RBFlags.RenderOutputPicture | window.sdkPtr.RBFlags.RenderColor32BPP;

                    SigObjV.RenderBitmap(BITMAP_IMAGEFORMAT, SigCapture.HTMLTagIds.imageBox.clientWidth-5, SigCapture.HTMLTagIds.imageBox.clientHeight, BITMAP_INKWIDTH, BITMAP_INKCOLOR, BITMAP_BACKGROUNDCOLOR, outputFlags, BITMAP_PADDING_X, BITMAP_PADDING_Y, FormlyFieldSignWacom.onRenderBitmap);
                    break;

                case window.sdkPtr.DynamicCaptureResult.DynCaptCancel:
                    SigCapture.print("Signature capture cancelled");
                    break;

                case window.sdkPtr.DynamicCaptureResult.DynCaptPadError:
                    SigCapture.print("No capture service available");
                    break;

                case window.sdkPtr.DynamicCaptureResult.DynCaptError:
                    SigCapture.print("Tablet Error");
                    break;

                case window.sdkPtr.DynamicCaptureResult.DynCaptNotLicensed:
                    SigCapture.print("No valid Signature Capture licence found");
                    break;

                default:
                    SigCapture.print("Capture Error " + status);
                    break;
            }
        }
    }

    static onRenderBitmap = (sigObjV, bmpObj, status) =>   // Handles the output of the RenderBitmap() function
    {
     
        if (SigCapture.callbackStatusOK("Signature Render Bitmap", status)) {
            //SigCapture.print("base64_image:>" + bmpObj + "<");
            //let img = new Image();
            //img.src = "data:image/png;base64," + bmpObj;                    
            // if (null == SigCapture.HTMLTagIds.imageBox.firstChild) {
            //     SigCapture.HTMLTagIds.imageBox.appendChild(img);
            // }
            // else {
            //     SigCapture.HTMLTagIds.imageBox.replaceChild(img, SigCapture.HTMLTagIds.imageBox.firstChild);
            // }

            //usiamo l'immagine non base64
            if (SigCapture.HTMLTagIds.imageBox){
                if( null == SigCapture.HTMLTagIds.imageBox.firstChild)
                {
                    SigCapture.HTMLTagIds.imageBox.appendChild(bmpObj.image);
                }
                else
                {
                    SigCapture.HTMLTagIds.imageBox.replaceChild(bmpObj.image, SigCapture.HTMLTagIds.imageBox.firstChild);
                }
            } 

            const toDataURL = () => {
                const canvas = document.createElement('canvas');
        
                // We use naturalWidth and naturalHeight to get the real image size vs the size at which the image is shown on the page
                canvas.width = bmpObj.image.naturalWidth;
                canvas.height = bmpObj.image.naturalHeight;
        
                // We get the 2d drawing context and draw the image in the top left
                canvas.getContext('2d').drawImage(bmpObj.image, 0, 0);
        
                // Convert canvas to DataURL and log to console
                const dataURL = canvas.toDataURL();
                //console.log(dataURL);
                // logs data:image/png;base64,wL2dvYWwgbW9yZ...
        
                return dataURL;               
            };

            FormlyFieldSignWacom.currentFormControl.dataUrl = toDataURL(); //"data:image/png;base64," + bmpObj;

            sigObjV.GetSigText(FormlyFieldSignWacom.onGetSigText);

        }
    }

    /* This function takes the SigText value returned by the callback and places it in the txtSignature tag on the form */
    static onGetSigText = (sigObjV, text, status) => {
        if (SigCapture.callbackStatusOK("Signature Render Bitmap", status)) {
            SigCapture.HTMLTagIds.textSig = text;

            //fire event di fine firma
            console.log('Fire event signaturetext');
            const domEvent = new CustomEvent('signaturetext', { bubbles: true, detail: FormlyFieldSignWacom.currentFormControl.key }); 
            document.dispatchEvent(domEvent);

            //setTimeout(() =>FormlyFieldSignWacom.setControlValue());
        }
    }

    static GetHash = (hash, callback) => {
        SigCapture.callbackFunc = callback;

        SigCapture.print("Creating hash:");
        hash.Clear(SigCapture.onClear);   // Clear any pre-existing hash value before creating a new one
    }

    static onClear = (hashV, status) => {
        if (SigCapture.callbackStatusOK("Hash Clear", status)) {
            hashV.PutType(window.sdkPtr.HashType.HashMD5, SigCapture.onPutType);
        }
    }

    static onPutType = (hashV, status) => {
        if (SigCapture.callbackStatusOK("Hash PutType", status)) {
            var vFname = new window.sdkPtr.Variant();
            vFname.Set(SigCapture.HTMLTagIds.firstName); //SigCapture.HTMLTagIds.firstName.value
            hashV.Add(vFname, SigCapture.onAddFname);  // Add the first name to the hash
        }
    }

    static onAddFname = (hashV, status) => {
        if (SigCapture.callbackStatusOK("Hash Add", status)) {
            var vLname = new window.sdkPtr.Variant();
            vLname.Set(SigCapture.HTMLTagIds.lastName); //SigCapture.HTMLTagIds.lastName.value
            hashV.Add(vLname, SigCapture.onAddLname);  // Add the surname to the hash
        }
    }

    static onAddLname = (hashV, status) => {
        if (SigCapture.callbackStatusOK("Hash Add", status)) {
            SigCapture.callbackFunc();
        }
    }

}


