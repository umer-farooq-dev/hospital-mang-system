<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use Tests\TestCase;

class CertificateTest extends TestCase
{
    public function test_template_placeholders_are_filled(): void
    {
        $text = "I, {doctor}, certify that {patient_name} needs {days} day(s) of rest from {start_date} to {end_date}.\n\n{notes}\n\n\nEnd {unknown}";
        $filled = CertificateTemplate::fillPlaceholders($text, ['doctor' => 'Dr Sawadogo', 'patient_name' => 'Awa Ouedraogo', 'days' => 3,
            'start_date' => '22/11/2026', 'end_date' => '24/11/2026']);

        $this->assertSame("I, Dr Sawadogo, certify that Awa Ouedraogo needs 3 day(s) of rest from 22/11/2026 to 24/11/2026.\n\nEnd {unknown}", $filled);
    }

    public function test_numbers_count_per_year_with_the_admin_prefix(): void
    {
        $this->assertSame('CM-2026-0001', Certificate::nextNumber('CM', 2026, null));
        $this->assertSame('CM-2026-0043', Certificate::nextNumber('CM', 2026, 'CM-2026-0042'));
        $this->assertSame('CERT-2027-0001', Certificate::nextNumber('CERT', 2027, null));
    }

    public function test_seeded_templates_are_translated_until_edited(): void
    {
        app()->setLocale('fr');
        $seeded = new CertificateTemplate(['name' => 'Sick leave', 'lang_key' => 'messages.certificate.templates.sick_leave', 'body' => null]);
        $this->assertSame("Certificat d'arrêt de travail", $seeded->label);
        $this->assertStringContainsString('{days} jour(s)', $seeded->text);

        $edited = new CertificateTemplate(['name' => 'Repos', 'lang_key' => null, 'body' => 'Texte du médecin']);
        $this->assertSame('Repos', $edited->label);
        $this->assertSame('Texte du médecin', $edited->text);
    }
}
