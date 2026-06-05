import { ComponentFixture, TestBed } from '@angular/core/testing';
import { CitationPanelComponent } from './citation-panel.component';

describe('CitationPanelComponent', () => {
  let fixture: ComponentFixture<CitationPanelComponent>;
  let component: CitationPanelComponent;

  beforeEach(async () => {
    await TestBed.configureTestingModule({
      imports: [CitationPanelComponent],
    }).compileComponents();

    fixture = TestBed.createComponent(CitationPanelComponent);
    component = fixture.componentInstance;
  });

  it('renders the empty state when no citations are available', () => {
    component.citations = [];
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Nessuna citazione');
  });

  it('renders citation details when evidence is available', () => {
    component.citations = [
      {
        documentId: '1',
        documentName: 'Manuale Aziendale.pdf',
        sourceLabel: 'Segmento 1',
        quoteText: 'AssistDoc usa isolamento tenant lato server.',
      },
    ];
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Manuale Aziendale.pdf');
    expect(fixture.nativeElement.textContent).toContain('Segmento 1');
  });

  it('renders optional segment metadata when available', () => {
    component.citations = [
      {
        documentId: '1',
        documentName: 'Manuale Aziendale.pdf',
        sourceLabel: 'Segmento 1',
        quoteText: 'AssistDoc usa isolamento tenant lato server.',
        collection: 'assistdoc_segments',
        embedding: 'qwen3-embedding:0.6b',
      },
    ];
    fixture.detectChanges();

    expect(fixture.nativeElement.textContent).toContain('Collection: assistdoc_segments');
    expect(fixture.nativeElement.textContent).toContain('Embedding: qwen3-embedding:0.6b');
  });

  it('truncates long citation text with dots in the frontend', () => {
    component.citations = [
      {
        documentId: '1',
        documentName: 'Manuale Aziendale.pdf',
        sourceLabel: 'Segmento 1',
        quoteText: 'A'.repeat(200),
      },
    ];
    fixture.detectChanges();

    const quote = fixture.nativeElement.querySelector('p');

    expect(quote.textContent.endsWith('...')).toBeTrue();
    expect(quote.textContent.length).toBeLessThan(200);
  });

  it('shows a view full quote action only for long citations', () => {
    component.citations = [
      {
        documentId: '1',
        documentName: 'Manuale Aziendale.pdf',
        sourceLabel: 'Segmento 1',
        quoteText: 'A'.repeat(200),
      },
      {
        documentId: '2',
        documentName: 'Sintesi.pdf',
        sourceLabel: 'Segmento 2',
        quoteText: 'Breve citazione.',
      },
    ];
    fixture.detectChanges();

    const buttons = fixture.nativeElement.querySelectorAll('button');

    expect(buttons.length).toBe(1);
    expect(buttons[0].textContent).toContain('View full quote');
  });

  it('expands the full quote when the action is clicked', () => {
    const longQuote = 'A'.repeat(200);
    component.citations = [
      {
        documentId: '1',
        documentName: 'Manuale Aziendale.pdf',
        sourceLabel: 'Segmento 1',
        quoteText: longQuote,
      },
    ];
    fixture.detectChanges();

    const button = fixture.nativeElement.querySelector('button');
    button.click();
    fixture.detectChanges();

    const quote = fixture.nativeElement.querySelector('p');

    expect(quote.textContent).toBe(longQuote);
    expect(button.textContent).toContain('Show less');
  });
});
