<?php

namespace Tests\Unit;

use App\Models\FrontService;
use App\Models\FrontSetting;
use PHPUnit\Framework\TestCase;

class FrontWebsiteTest extends TestCase
{
    public function test_home_sections_keep_the_admin_order_and_add_new_sections(): void
    {
        $saved = json_encode([['key' => 'blog', 'on' => 1], ['key' => 'about', 'on' => 0], ['key' => 'hacked', 'on' => 1]]);
        $sections = FrontSetting::homeSections(['home_sections' => $saved]);

        $this->assertSame(['blog', 'about'], array_slice(array_column($sections, 'key'), 0, 2));
        $this->assertSame(0, $sections[1]['on']);
        $this->assertNotContains('hacked', array_column($sections, 'key'));
        $this->assertCount(count(FrontSetting::HOME_SECTIONS), $sections);
    }

    public function test_content_text_becomes_escaped_paragraphs_and_a_check_list(): void
    {
        $item = new FrontService(['description' => "Intro <b>x</b>\nsuite\n\n- un\n- deux"]);

        $this->assertSame('<p>Intro &lt;b&gt;x&lt;/b&gt;<br />'."\n".'suite</p><ul><li><i class="fa-solid fa-badge-check"></i> un</li><li><i class="fa-solid fa-badge-check"></i> deux</li></ul>', $item->body_html);
    }
}
