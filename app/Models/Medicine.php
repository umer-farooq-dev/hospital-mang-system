<?php

namespace App\Models;

use Eloquent as Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * App\Models\Medicine
 *
 * @property int $id
 * @property int|null $category_id
 * @property int|null $brand_id
 * @property string $name
 * @property float $selling_price
 * @property float $buying_price
 * @property string $effect
 * @property Carbon $mfg_date
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 *
 * @method static Builder|Medicine newModelQuery()
 * @method static Builder|Medicine newQuery()
 * @method static Builder|Medicine query()
 * @method static Builder|Medicine whereBrandId($value)
 * @method static Builder|Medicine whereBuyingPrice($value)
 * @method static Builder|Medicine whereCategoryId($value)
 * @method static Builder|Medicine whereCreatedAt($value)
 * @method static Builder|Medicine whereId($value)
 * @method static Builder|Medicine whereName($value)
 * @method static Builder|Medicine whereSellingPrice($value)
 * @method static Builder|Medicine whereUpdatedAt($value)
 *
 * @mixin Model
 *
 * @property-read Brand|null $brand
 * @property-read Category|null $category
 * @property string $salt_composition
 * @property string|null $side_effects
 * @property string|null $description
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Medicine whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Medicine whereSaltComposition($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\Medicine whereSideEffects($value)
 *
 * @property int $is_default
 *
 * @method static Builder|Medicine whereIsDefault($value)
 */
class Medicine extends Model
{
    public $table = 'medicines';

    public $fillable = [
        'category_id',
        'brand_id',
        'name',
        'selling_price',
        'buying_price',
        'side_effects',
        'description',
        'salt_composition',
        'currency_symbol',
        'quantity',
        'common_name',
        'barcode',
        'cis_code',
        'unit',
        'dosage_form',
        'route',
    ];
    // available_quantity is not fillable: only App\Utils\Stock writes it (total of the batches).

    protected $casts = [
        'id' => 'integer',
        'category_id' => 'integer',
        'brand_id' => 'integer',
        'name' => 'string',
        'selling_price' => 'double',
        'buying_price' => 'double',
        'side_effects' => 'string',
        'description' => 'string',
        'salt_composition' => 'string',
        'currency_symbol' => 'string',
        'quantity' => 'integer',
        'available_quantity' => 'integer',
    ];

    public static $rules = [
        'category_id' => 'required',
        'brand_id' => 'required',
        'name' => 'required|min:2|unique:medicines,name',
        'selling_price' => 'required',
        'buying_price' => 'required',
        'side_effects' => 'nullable',
        'salt_composition' => 'required|nullable|string',
        'quantity' => 'required|integer',
        'common_name' => 'nullable|string|max:191',
        'barcode' => 'nullable|string|max:100',
        'cis_code' => 'nullable|string|max:50',
        'unit' => 'nullable|integer',
        'dosage_form' => 'nullable|integer',
        'route' => 'nullable|integer',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function prescriptionMedicines(): BelongsTo
    {
        return $this->belongsTo(PrescriptionMedicineModal::class, 'medicine');
    }

    public function usedMedicines(): BelongsTo
    {
        return $this->belongsTo(UsedMedicine::class);
    }

    public function purchasedMedicine(): BelongsTo
    {
        return $this->belongsTo(PurchasedMedicine::class);
    }

    // Column title in the file (lower case, spaces as _) => medicine field. French and English titles.
    const IMPORT_COLUMNS = ['name' => 'name', 'nom' => 'name', 'medicament' => 'name', 'dci' => 'common_name', 'common_name' => 'common_name',
        'inn' => 'common_name', 'cis' => 'cis_code', 'cis_code' => 'cis_code', 'code_cis' => 'cis_code', 'barcode' => 'barcode',
        'code_barre' => 'barcode', 'form' => 'dosage_form', 'forme' => 'dosage_form', 'route' => 'route', 'voie' => 'route', 'unit' => 'unit',
        'unite' => 'unit', 'category' => 'category', 'categorie' => 'category', 'brand' => 'brand', 'laboratoire' => 'brand',
        'selling_price' => 'selling_price', 'prix_vente' => 'selling_price', 'buying_price' => 'buying_price', 'prix_achat' => 'buying_price'];

    // Fields whose text is matched to a master list label.
    const IMPORT_LISTS = ['dosage_form' => 'dosage_form', 'route' => 'medicine_route', 'unit' => 'medicine_unit'];

    /**
     * Create or update medicines from file rows (matched by CIS code, then by name).
     * Texts not found in the master lists are left empty and reported, never invented.
     *
     * @return array{created: int, updated: int, skipped: int, unknown: array<string, string[]>, errors: string[]}
     */
    public static function importRows(array $rows, int $categoryId, int $brandId): array
    {
        $summary = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'unknown' => [], 'errors' => []];
        $key = fn ($text) => mb_strtolower(trim((string) $text));
        $lists = [];
        foreach (self::IMPORT_LISTS as $field => $type) {
            // A file may be in any installed language: match the stored name and every translation of the item.
            $locales = array_diff(array_map('basename', glob(lang_path('*'), GLOB_ONLYDIR) ?: []), ['vendor']);
            $lists[$field] = [];
            foreach (MasterListItem::where('type', $type)->get() as $item) {
                $names = [$item->name];
                foreach ($item->lang_key ? $locales : [] as $locale) {
                    $names[] = __($item->lang_key, [], $locale);
                }
                foreach ($names as $name) {
                    $lists[$field][$key($name)] ??= $item->code;
                }
            }
        }
        $categories = Category::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$key($name) => $id])->all();
        $brands = Brand::pluck('id', 'name')->mapWithKeys(fn ($id, $name) => [$key($name) => $id])->all();

        foreach ($rows as $i => $row) {
            $data = [];
            foreach ($row as $title => $value) {
                $field = self::IMPORT_COLUMNS[str_replace([' ', '-'], '_', \Illuminate\Support\Str::ascii($key($title)))] ?? null;
                if ($field && trim((string) $value) !== '') {
                    $data[$field] = trim((string) $value);
                }
            }
            if (empty($data['name'])) {
                $summary['skipped']++;

                continue;
            }
            foreach (self::IMPORT_LISTS as $field => $type) {
                if (isset($data[$field])) {
                    $code = $lists[$field][$key($data[$field])] ?? null;
                    if ($code === null) {
                        $summary['unknown'][$type][$data[$field]] = $data[$field];
                        unset($data[$field]);
                    } else {
                        $data[$field] = $code;
                    }
                }
            }
            foreach (['category' => [$categories, 'category_id'], 'brand' => [$brands, 'brand_id']] as $field => [$known, $column]) {
                if (isset($data[$field])) {
                    if (isset($known[$key($data[$field])])) {
                        $data[$column] = $known[$key($data[$field])];
                    }
                    unset($data[$field]);
                }
            }
            foreach (['selling_price', 'buying_price'] as $field) {
                if (isset($data[$field])) {
                    $number = str_replace([' ', ','], ['', '.'], $data[$field]);
                    if (is_numeric($number)) {
                        $data[$field] = (float) $number;
                    } else {
                        $summary['errors'][] = __('messages.medicine_import.bad_price', ['line' => $i + 2]);
                        unset($data[$field]);
                    }
                }
            }
            try {
                $medicine = (! empty($data['cis_code']) ? self::where('cis_code', $data['cis_code'])->first() : null) ?? self::where('name', $data['name'])->first();
                if ($medicine) {
                    $medicine->update($data);
                    $summary['updated']++;
                } else {
                    self::create($data + ['category_id' => $categoryId, 'brand_id' => $brandId, 'selling_price' => 0, 'buying_price' => 0,
                        'salt_composition' => $data['common_name'] ?? $data['name'], 'quantity' => 0]);
                    $summary['created']++;
                }
            } catch (\Throwable $e) {
                $summary['errors'][] = __('messages.medicine_import.line_error', ['line' => $i + 2, 'name' => $data['name']]);
            }
        }
        $summary['unknown'] = array_map('array_values', $summary['unknown']);

        return $summary;
    }
}
