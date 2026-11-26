<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuditLogTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Only the tables this test needs; the full migration set targets MySQL.
        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value')->nullable();
            $table->timestamps();
        });
        (require base_path('database/migrations/2026_11_20_120000_create_audit_logs_table.php'))->up();
    }

    public function test_it_logs_create_update_delete_and_masks_secrets(): void
    {
        $secret = AuditTestSetting::create(['key' => 'stripe_secret', 'value' => 'sk_test_123']);
        $name = AuditTestSetting::create(['key' => 'app_name', 'value' => 'HMS']);

        $created = AuditLog::where('event', 'created')->where('auditable_id', $secret->id)->first();
        $this->assertSame('******', $created->new_values['value']);
        $this->assertSame('HMS', AuditLog::where('auditable_id', $name->id)->first()->new_values['value']);

        $name->update(['value' => 'Hospital']);
        $updated = AuditLog::where('event', 'updated')->first();
        $this->assertSame(['value' => 'HMS'], $updated->old_values);
        $this->assertSame(['value' => 'Hospital'], $updated->new_values);

        $count = AuditLog::count();
        $name->touch(); // timestamp-only change is not logged
        $this->assertSame($count, AuditLog::count());

        // Settings named *_key hold credentials; ordinary *_key fields are not secrets.
        $aiKey = AuditTestSetting::create(['key' => 'open_ai_key', 'value' => 'sk-live-1']);
        $this->assertSame('******', AuditLog::where('auditable_id', $aiKey->id)->first()->new_values['value']);
        $this->assertSame('open_ai_key', AuditLog::where('auditable_id', $aiKey->id)->first()->new_values['key']);

        $secret->delete();
        $deleted = AuditLog::where('event', 'deleted')->first();
        $this->assertSame('******', $deleted->old_values['value']);
        $this->assertSame('stripe_secret', $deleted->old_values['key']);
        $this->assertSame(AuditTestSetting::class, $deleted->auditable_type);
    }

    public function test_history_of_a_receipt_includes_its_lines_even_deleted_ones(): void
    {
        $log = fn ($type, $id, $event, $old, $new) => AuditLog::create(['event' => $event, 'auditable_type' => $type, 'auditable_id' => $id,
            'old_values' => $old, 'new_values' => $new]);
        $log(\App\Models\Receipt::class, 8, 'created', [], ['amount' => 14850]);
        $log(\App\Models\ReceiptLine::class, 3, 'created', [], ['receipt_id' => 8, 'amount' => 4500]);
        $log(\App\Models\ReceiptLine::class, 4, 'deleted', ['receipt_id' => '8', 'amount' => 900], []);
        $log(\App\Models\ReceiptLine::class, 5, 'created', [], ['receipt_id' => 9, 'amount' => 1]); // another receipt
        $log(\App\Models\Receipt::class, 8, 'updated', ['payment_method' => 1, 'updated_at' => 'a'], ['payment_method' => 2, 'updated_at' => 'b']);

        $history = AuditLog::historyFor('receipt', 8);
        $this->assertCount(4, $history);
        $this->assertNotContains(5, $history->pluck('auditable_id')->all());

        $update = $history->firstWhere('event', 'updated');
        $this->assertSame(['payment_method' => [1, 2]], $update->changes(), 'only real changes, no bookkeeping columns');
    }
}

// Plain model on the settings table: same key/value shape as App\Models\Setting
// without its media-library dependencies.
class AuditTestSetting extends Model
{
    protected $table = 'settings';

    protected $guarded = [];
}
