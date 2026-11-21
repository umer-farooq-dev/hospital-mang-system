<?php

namespace App\Models;

use Eloquent as Model;

/**
 * Class ChargeCategory
 *
 * @version April 11, 2020, 5:26 am UTC
 *
 * @property int $id
 * @property string $name
 * @property string $description
 * @property int $charge_type
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 *
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory query()
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory whereChargeType($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory whereName($value)
 * @method static \Illuminate\Database\Eloquent\Builder|\App\Models\ChargeCategory whereUpdatedAt($value)
 *
 * @mixin \Eloquent
 */
class ChargeCategory extends Model
{
    public $table = 'charge_categories';



    public $fillable = [
        'name',
        'description',
        'charge_type',
    ];

    protected $casts = [
        'id' => 'integer',
        'name' => 'string',
        'description' => 'string',
        'charge_type' => 'string',
    ];

    public static $rules = [
        'name' => 'required|unique:charge_categories,name',
        'description' => 'nullable|string',
        'charge_type' => 'required',
    ];
}
