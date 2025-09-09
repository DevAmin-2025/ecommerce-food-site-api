<?php

namespace App\Models;

use Carbon\Carbon;
use App\Models\Category;
use App\Models\ProductImage;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Product extends Model
{
    protected $guarded = [];

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function getIsSaleAttribute(): bool
    {
        return $this->sale_price > 0
            & $this->date_on_sale_from < Carbon::now()
            & $this->date_on_sale_to > Carbon::now();
    }

    public function scopeFilter(Builder $query): void
    {
        if (request()->filled('sortBy')) {
            switch (request()->sortBy) {
                case 'max':
                    $query->orderByDesc('price');
                    break;
                case 'min':
                    $query->orderBy('price');
                    break;
                case 'bestseller':
                    $topProducts = DB::table('transactions as t')
                        ->join('order_items as oi', 't.order_id', '=', 'oi.order_id')
                        ->where('t.status', 1)
                        ->select('oi.product_id')
                        ->groupBy('oi.product_id')
                        ->orderByDesc(DB::raw('COUNT(oi.product_id)'))
                        ->pluck('oi.product_id');
                    $query->whereIn('id', $topProducts)
                        ->orderByRaw('FIELD(id, ' . $topProducts->implode(',') . ')');
                    break;
                case 'sale':
                    $query->where('sale_price', '>', 0)
                        ->where('date_on_sale_from', '<', Carbon::now())
                        ->where('date_on_sale_to', '>', Carbon::now());
                    break;
                default:
                    $query;
                    break;
            };
        };

        if (request()->filled('category')) {
            $query->where('category_id', request()->category);
        };
    }

    public function scopeSearch(Builder $query): Builder
    {
        if (request()->filled('search')) {
            $search = '%' . trim(request()->search) . '%';
            return $query->where('name', 'LIKE', $search);
        };
        return $query;
    }
}
