<?php

namespace Tests\Feature;

use App\Models\ExamOrderItem;
use App\Models\ImagingReportTemplate;
use Tests\TestCase;

class ImagingTest extends TestCase
{
    public function test_pacs_link_uses_the_admin_template(): void
    {
        $item = new ExamOrderItem(['sample_number' => 'S000042']);

        $this->assertSame('https://pacs.local/view?acc=S000042', $item->pacsUrl('https://pacs.local/view?acc={sample_number}'));
        $this->assertNull($item->pacsUrl(''), 'no PACS configured');
        $this->assertNull((new ExamOrderItem())->pacsUrl('https://pacs.local/view?acc={sample_number}'), 'no sample yet');
    }

    public function test_seeded_templates_follow_the_language_until_edited(): void
    {
        app()->setLocale('fr');
        $seeded = new ImagingReportTemplate(['name' => 'Chest x-ray', 'lang_key' => 'messages.imaging.templates.chest_xray', 'body' => null]);
        $this->assertSame('Radiographie thoracique', $seeded->label);
        $this->assertStringContainsString("Conclusion :", $seeded->text);

        $edited = new ImagingReportTemplate(['name' => 'Écho pelvienne', 'body' => 'Utérus :']);
        $this->assertSame('Écho pelvienne', $edited->label);
        $this->assertSame('Utérus :', $edited->text);
    }
}
