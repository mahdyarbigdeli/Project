<?php

namespace App\Models;

use App\Traits\FilterTrait;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    use FilterTrait;

    protected $guarded = [];

    public function scopeFilter(Builder $query, $filter = [])
    {

        $filter = $filter ?: request()->all();

        $filter = collect($filter);
        $query->filterColumn($filter, 'id');
        return $query;
    }
}
