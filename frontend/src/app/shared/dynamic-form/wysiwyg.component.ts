import { Component, ViewChild } from '@angular/core';
import { FieldType } from '@ngx-formly/core';
import { AngularEditorComponent, AngularEditorConfig } from '@kolkov/angular-editor';



@Component({
    selector: 'app-form-wysiwyg-type',
    template: `
  <div  [class.is-invalid]="showError"> 

	<div class="input-group mb-1">
    <div class="input-group-prepend">
      <label class="input-group-text">Posizione</label>
    </div>
<select class="form-select" (change)="selectChangeHandler($event)" data-style="btn-outline-secondary"> 
    <option value="0">1</option>
    <option value="1">2</option>
    <option value="2">3</option>
    <option value="3">4</option>
    <option value="4">5</option>
    <option value="5">6</option>
    <option value="6">7</option>
    <option value="7">8</option>
    <option value="8">9</option>
    <option value="9">10</option>
    <option value="10">11</option>
    <option value="11">12</option>
    <option value="12">13</option>
    <option value="13">14</option>
    <option value="14">15</option>
    <option value="15">16</option>
    <option selected value="-1">ultima</option>
    </select>			
			<button type="button" class="btn btn-outline-secondary rounded" (click)="aggiungiPremessa()">Aggiungi premessa</button>
      <button type="button" class="btn btn-outline-secondary rounded" (click)="rimuoviPremessa()">Rimuovi premessa</button>		 
	</div>   
 
  <angular-editor #editor id="{{key}}" [config]="config"   (paste)="onPaste($event)"
   [formControl]="formControl" [formlyAttributes]="field" [class.is-invalid]="showError" ></angular-editor>
  </div>
  `,
    styleUrls: ['./wysiwyg.component.css'],
    standalone: false
})
export class WysiwygTypeComponent extends FieldType {

  @ViewChild('editor', { static: true }) editor: any;
  @ViewChild('editor', { static: true }) editor1: AngularEditorComponent;
  
  private selectedPosizione: number = -1;
  selectChangeHandler (event: any) {
    //update the ui
    this.selectedPosizione = event.target.value;
  }

  aggiungiPremessa(pos = -1){    
    this.editor1.focus();
    const node = this.addRow("table_premesse", this.selectedPosizione); 
    //aggiorna editor
    //
    //seleziona posizione testo inserito
    this.editor.doc.getSelection().selectAllChildren(node);    
    const editableElement = this.editor.textArea.nativeElement;
    this.editor.onContentChange(editableElement);
    //this.editor.editorService.insertHtml("inserisci testo qui ...");       
  }

  rimuoviPremessa(){
    this.editor1.focus();
    this.removeRow("table_premesse",this.selectedPosizione);   
    const editableElement = this.editor.textArea.nativeElement;
    this.editor.onContentChange(editableElement);
    //this.editor.editorService.insertHtml("");    
  }

  removeRow(tableID, pos: number=-1){
    // Get a reference to the table
    let tableRef = document.getElementById(tableID) as HTMLTableElement;
        
    // remove a row at the end of the table
    let newRow = tableRef.deleteRow(pos);   
  }

  addRow(tableID, pos: number) {        
    // Get a reference to the table
    let tableRef = document.getElementById(tableID) as HTMLTableElement;
    
    // Insert a row at the end of the table
    console.log(pos);
    let newRow = tableRef.insertRow(pos);   
    // Insert a cell in the row at index 0
    let newCell = newRow.insertCell(0);
    //style="padding:0; vertical-align:top; width:20px"
    newCell.style.padding = "0";
    newCell.style.verticalAlign = "top";
    newCell.style.width = "20px";
    newCell.innerHTML = '<p class="normal">-</p>'

    // Insert a cell in the row at index 1
    newCell = newRow.insertCell(1);    
    //style="padding:0; text-align:justify"
    newCell.style.padding = "0";
    newCell.style.textAlign = "justify";

    newCell.innerHTML = '<p class="normal"> inserisci testo qui ...</p>';
          
    return newCell;

    // Append a text node to the cell
    // let newText = document.createTextNode("New bottom row");
    // newCell.appendChild(newText);
  }
  
  onPaste(event: ClipboardEvent){    
      event.preventDefault();
      const text = event.clipboardData.getData('text/plain');
      this.editor.editorService.insertHtml(text);          
  }


  config: AngularEditorConfig = {
      editable: true,
      spellcheck: false,
      height: '30rem',
      minHeight: '5rem',
      //maxHeight: 'auto',
      width: 'auto',
      minWidth: '0',
      translate: 'no',
      enableToolbar: true,
      showToolbar: true,
      placeholder: 'Inserisci il testo qui ...',
      defaultParagraphSeparator: '',
      toolbarPosition: 'top',
      sanitize: false,
      toolbarHiddenButtons: [
        [
          'insertUnorderedList',
          'insertOrderedList',
          'heading',
          'fontName'
        ],
        [
          'fontSize',
          'textColor',
          'backgroundColor',
          'customClasses',
          'link',
          'unlink',
          'insertImage',
          'insertVideo',
          'insertHorizontalRule',
          'removeFormat',
          //'toggleEditorMode'
        ]
      ],
      //rawPaste: true
  };

  editorConfig: AngularEditorConfig = {
    editable: true,
      spellcheck: true,
      height: 'auto',
      minHeight: '0',
      maxHeight: 'auto',
      width: 'auto',
      minWidth: '0',
      translate: 'no',
      enableToolbar: true,
      showToolbar: true,
      placeholder: 'Enter text here...',
      defaultParagraphSeparator: 'p',
      defaultFontName: 'Arial',    
      fonts: [
        {class: 'arial', name: 'Arial'},
        {class: 'times-new-roman', name: 'Times New Roman'},
        {class: 'calibri', name: 'Calibri'},
        {class: 'comic-sans-ms', name: 'Comic Sans MS'}
      ],
      customClasses: [
      {
        name: 'quote',
        class: 'quote',
      },
      {
        name: 'redText',
        class: 'redText'
      },
      {
        name: 'titleText',
        class: 'titleText',
        tag: 'h1',
      },
    ],         
    //sanitize: true,
    toolbarPosition: 'top',
  };

}



