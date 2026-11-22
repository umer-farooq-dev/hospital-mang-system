<?php

namespace Tests\Feature;

use App\Models\Icd10Code;
use Tests\TestCase;

class Icd10Test extends TestCase
{
    public function test_import_reads_common_csv_layouts(): void
    {
        $file = "\xEF\xBB\xBFcode;libelle\nA01.0;Fièvre typhoïde\nB509;\"Paludisme à Plasmodium falciparum, sans précision\"\n\nI10|Hypertension essentielle\nJ18.9\tPneumopathie, sans précision\nnot a code;ignored\nE11,Diabète sucré de type 2, sans complication\n";
        $rows = Icd10Code::parse($file);

        $this->assertSame(['A01.0', 'B50.9', 'I10', 'J18.9', 'E11'], array_keys($rows));
        $this->assertSame('Paludisme à Plasmodium falciparum, sans précision', $rows['B50.9']);
        $this->assertSame('Diabète sucré de type 2, sans complication', $rows['E11']);
        $this->assertSame('A01.0', Icd10Code::normalize(' a01.0 '));
    }

    public function test_windows_encoded_files_are_converted(): void
    {
        $rows = Icd10Code::parse(mb_convert_encoding("A09;Diarrhée et gastro-entérite\n", 'Windows-1252', 'UTF-8'));

        $this->assertSame(['A09' => 'Diarrhée et gastro-entérite'], $rows);
    }
}
