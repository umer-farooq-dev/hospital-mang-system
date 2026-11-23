<?php

namespace Tests\Feature;

use App\Http\Middleware\ExportTables;
use Tests\TestCase;

class ExportTablesTest extends TestCase
{
    public function test_amounts_become_numbers_and_codes_dates_and_references_stay_text(): void
    {
        $cases = ['6 000' => 6000.0, '1 250,50' => 1250.5, '-3 000 FCFA' => -3000.0, '1,250.50' => 1250.5, 'CFA 12' => 12.0, '5711' => 5711.0,
            '0002/11/26' => '0002/11/26', '00123' => '00123', '23/11/2026' => '23/11/2026', 'CA26-00001' => 'CA26-00001', '10%' => '10%', 'Caisse' => 'Caisse', '' => ''];
        foreach ($cases as $text => $expected) {
            $this->assertSame($expected, ExportTables::value((string) $text), "\"$text\"");
        }
    }

    public function test_page_tables_are_read_without_the_modals(): void
    {
        $html = '<html><body><div class="card"><table><thead><tr><th>Compte</th><th>Débit</th><th class="d-print-none">x</th></tr></thead>'
            .'<tbody><tr><td>5711 Caisse</td><td>1 500</td><td class="d-print-none"><a>edit</a></td></tr><tr><td colspan="2">Total</td></tr></tbody></table></div>'
            .'<div class="modal fade"><table><tr><td>hidden</td></tr></table></div></body></html>';
        $this->assertSame([['Balance'], [], ['Compte', 'Débit'], ['5711 Caisse', 1500.0], ['Total', ''], []], ExportTables::rows($html, 'Balance'));
    }
}
