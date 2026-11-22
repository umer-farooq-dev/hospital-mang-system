<?php

namespace Tests\Feature;

use App\Models\LabImportProfile;
use Tests\TestCase;

class LabImportTest extends TestCase
{
    public function test_semicolon_csv_with_bom_and_a_title_row_below_a_banner(): void
    {
        $csv = "\xEF\xBB\xBFAnalyseur XN-350;;;\nSample ID;WBC;HGB;Comment\nS000012;7,2;10,5;ok\ns000013;;13.1;\n;;;\n";
        $rows = LabImportProfile::rowsFromGrid(LabImportProfile::csvGrid($csv), 2);

        $this->assertCount(2, $rows);
        $this->assertSame(['Sample ID' => 'S000012', 'WBC' => '7,2', 'HGB' => '10,5', 'Comment' => 'ok'], $rows[0]);
    }

    public function test_plan_maps_columns_to_parameters_without_case(): void
    {
        $profile = new LabImportProfile(['sample_column' => 'sample id', 'mappings' => ['WBC' => 11, 'hgb' => 12, 'Missing' => 13]]);
        $plan = $profile->plan([
            ['Sample ID' => 'S000012', 'WBC' => '7,2', 'HGB' => '10,5'],
            ['Sample ID' => 's000013', 'WBC' => '', 'HGB' => '13.1'],
            ['Sample ID' => '', 'WBC' => '5'],
        ]);

        $this->assertSame(['S000012' => [11 => '7,2', 12 => '10,5'], 'S000013' => [12 => '13.1']], $plan);
    }
}
