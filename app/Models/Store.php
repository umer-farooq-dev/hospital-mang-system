<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

/** A place that holds stock: main store, pharmacy, branch pharmacy, ward, theatre. */
class Store extends Model
{
    protected $fillable = ['name', 'code', 'store_type', 'bed_type_id', 'can_sell', 'is_active', 'sort_order'];

    protected $casts = ['can_sell' => 'boolean', 'is_active' => 'boolean', 'store_type' => 'integer', 'sort_order' => 'integer'];

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'store_user');
    }

    /** Active stores a user may work in: admins all, others the stores they are assigned to or stores with nobody assigned. */
    public static function usableBy(?User $user): Builder
    {
        $query = self::where('is_active', true)->orderBy('sort_order')->orderBy('name');
        if ($user && ! $user->hasRole('Admin')) {
            $query->where(fn ($q) => $q->whereDoesntHave('users')->orWhereHas('users', fn ($u) => $u->where('users.id', $user->id)));
        }

        return $query;
    }

    /** id => name of the stores the logged-in user may use. */
    public static function options(): array
    {
        return self::usableBy(auth()->user())->pluck('name', 'id')->all();
    }
}
