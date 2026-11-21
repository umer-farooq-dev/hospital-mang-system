<?php

namespace Tests\Feature;

use App\Models\MasterListItem;
use App\Repositories\MasterListRepository;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;
use Tests\TestCase;

class MasterListItemTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('master_list_items', function (Blueprint $table) {
            $table->id();
            $table->string('type', 50);
            $table->unsignedInteger('code');
            $table->string('name');
            $table->string('lang_key')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('visitors', function (Blueprint $table) {
            $table->id();
            $table->integer('purpose');
        });
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();

        DB::table('master_list_items')->insert([
            ['type' => 'visitor_purpose', 'code' => 1, 'name' => 'Enquiry', 'lang_key' => 'messages.visitor.enquiry', 'sort_order' => 2, 'is_active' => true],
            ['type' => 'visitor_purpose', 'code' => 3, 'name' => 'Visit', 'lang_key' => 'messages.visitor.visit', 'sort_order' => 1, 'is_active' => true],
            ['type' => 'visitor_purpose', 'code' => 2, 'name' => 'Seminar', 'lang_key' => null, 'sort_order' => 3, 'is_active' => false],
        ]);
        MasterListItem::flushCache();
    }

    public function test_options_labels_and_filters(): void
    {
        app()->setLocale('fr');

        // Active only, in display order, seeded items translated.
        $this->assertSame([3 => __('messages.visitor.visit'), 1 => __('messages.visitor.enquiry')], MasterListItem::options('visitor_purpose'));
        $this->assertSame('Visite', MasterListItem::options('visitor_purpose')[3]);
        // An edit form keeps the record's own, since deactivated item.
        $this->assertArrayHasKey(2, MasterListItem::options('visitor_purpose', 2));
        // Stored values of inactive items still show.
        $this->assertSame('Seminar', MasterListItem::label('visitor_purpose', 2));
        $this->assertSame('n/a', MasterListItem::label('visitor_purpose', 99, 'n/a'));
        $this->assertSame([0, 3, 1], array_keys(MasterListItem::filterOptions('visitor_purpose')));
        $this->assertSame(4, MasterListItem::nextCode('visitor_purpose'));
    }

    public function test_renaming_a_seeded_item_drops_its_translation(): void
    {
        app()->setLocale('fr');
        $repo = app(MasterListRepository::class);
        $visit = MasterListItem::where('code', 3)->first();

        $repo->updateItem($visit, ['name' => 'Visit', 'sort_order' => 1, 'is_active' => 1]);
        $this->assertNotNull($visit->fresh()->lang_key, 'same name keeps the translation');

        $repo->updateItem($visit, ['name' => 'Family visit', 'sort_order' => 1, 'is_active' => 1]);
        MasterListItem::flushCache();
        $this->assertSame('Family visit', MasterListItem::label('visitor_purpose', 3));
    }

    public function test_item_in_use_cannot_be_deleted(): void
    {
        $repo = app(MasterListRepository::class);
        DB::table('visitors')->insert(['purpose' => 1]);

        $this->expectException(UnprocessableEntityHttpException::class);
        $repo->delete(MasterListItem::where('code', 1)->first()->id);
    }

    public function test_unused_item_can_be_deleted_and_new_items_get_the_next_code(): void
    {
        $repo = app(MasterListRepository::class);
        $repo->delete(MasterListItem::where('code', 2)->first()->id);
        $item = $repo->store(['type' => 'visitor_purpose', 'name' => 'Training', 'is_active' => 1]);

        $this->assertSame(4, $item->code);
        $this->assertFalse(MasterListItem::where('code', 2)->exists());
    }
}
