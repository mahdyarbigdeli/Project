<?php

namespace App\Traits;

use Illuminate\Support\Str;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Builder;
use Morilog\Jalali\Jalalian;

trait FilterTrait
{
    abstract public function scopeFilter(Builder $query, $filter = []);

    public function scopeFilterStrColumn(Builder $builder, Collection $filter, $name)
    {
        return $builder->when($filter->get($name), function ($query) use ($name, $filter) {
            if ($name == 'first_name' || $name == 'last_name')
                $query->where('first_name', 'like', "%{$filter->get($name)}%")->orWhere('last_name', 'like', "%{$filter->get($name)}%");
            else
                $query->where($name, 'like', "%{$filter->get($name)}%");
        });
    }

    public function scopeFilterColumn(Builder $builder, Collection $filter, $name, $operator = '=')
    {
        return $builder->when(($filter->get($name) || $filter->get($name) === "0"), function ($query) use ($name, $filter, $operator) {
            if ($filter->get($name) === 'null')
                $query->whereNull($name);
            else
                $query->where($name, $operator, $filter->get($name));
        });
    }

    public function scopeFilterArrayColumn(Builder $query, Collection $filter, $name, $operator = '=')
    {
        if ($filter->get($name) && is_array($filter->get($name))) {
            return $query->whereIn($name, $filter->get($name));
        };
    }

    public function scopeFilterIsNullColumn(Builder $builder, Collection $filter, $name, $operator = '=')
    {
        return $builder->when($filter->get($name), function ($query) use ($name, $filter, $operator) {
            $filter->get($name) != 'null' ? $query->whereNotNull($name === 'bank_transaction' ?'bank_transaction_id' : $name ) : $query->whereNull($name === 'bank_transaction' ?'bank_transaction_id' : $name);
        });
    }

    public function scopeFilterBetweenColumn(Builder $builder, Collection $filter, $name)
    {
        $builder->when($filter->get('min_' . $name), function ($query) use ($name, $filter) {
            $query->where($name, '>=', $filter->get('min_' . $name));
        });

        $builder->when($filter->get('max_' . $name), function ($query) use ($name, $filter) {
            $query->where($name, '<=', $filter->get('max_' . $name));
        });
        return $builder;
    }

    public function scopeFilterDateBetweenColumn(Builder $builder, Collection $filter, $name)
    {
        $builder->when($filter->get('min_' . $name), function ($query) use ($name, $filter) {
            $query->where($name, '>=', (Jalalian::fromFormat('Y/m/d', $filter->get('min_' . $name))->toCarbon()));
        });

        $builder->when($filter->get('max_' . $name), function ($query) use ($name, $filter) {
            $query->where($name, '<=', (Jalalian::fromFormat('Y/m/d', $filter->get('max_' . $name))->toCarbon()));
        });
        return $builder;
    }

    public function scopeFilterRelColumn(Builder $query, Collection $filters, string $item)
    {
        if ($filters->get($item) && is_array($filters->get($item)) && array_filter($filters->get($item))) {
            $item_filter = array_filter($filters->get($item));
            $query->whereHas(Str::camel($item), function ($q) use ($item_filter) {
                $q->filter($item_filter);
            });
        }

        return $query;
    }

    public function scopeFilterRelMorphColumnType(Builder $query, Collection $filters, string $item)
    {
        // Get the class for the given morphable type
        $morphType = "App\\Models\\" . (Str::studly($filters->get($item)));
        $morphColumn = Str::replaceLast('_type', '', $item);

        if ($filters->has($item)) {
            $query->whereHasMorph(($morphColumn), [$morphType], function ($q) use ($filters, $item) {
                $itemFilter = $filters->get($item);
                $q->filter($itemFilter);
            });
        }

        return $query;
    }

    public function scopeFilterRelMorphColumn(Builder $query, Collection $filters, string $item, $type = '*')
    {
        if (is_array($filters->get($item)) && count($filters->get($item)) > 0) {
            $item_filter = $filters->get($item);
            if ($item === 'useable' || $item === "chargeable")
                $query->orWhereHasMorph(Str::camel($item), $type, function ($q) use ($item_filter) {
                    $q->filter($item_filter);
                });
            else
                $query->whereHasMorph(Str::camel($item), $type, function ($q) use ($item_filter) {
                    $q->filter($item_filter);
                });
        }

        return $query;
    }

    public function scopeFilterJsonColumn(Builder $query, Collection $filters, string $item)
    {
        if ($filters->has($item) && is_array($filters->get($item))) {
            $item_filter = $filters->get($item);
            // foreach ($item_filter as $key => $value) {
            //     if (is_numeric($value)) {
            //         $item_filter[$key] = (int)$value;
            //     }
            //     if (is_float($value)) {
            //         $item_filter[$key] = (float)$value;
            //     }
            // }

            $query->when($item, function ($q) use ($item_filter, $item) {
                foreach ($item_filter as $key => $value) {
                    if ($key === 'withdraw_date') {
                        $value = explode("/", $value);
                        // $value = Jalalian::fromFormat('Y/m/d', $value)->toCarbon()->format('Y-m');
                        $fromValue = Jalalian::fromFormat('Y/m/d', $value[0] . '/' . $value[1] . '/' . '01')->toCarbon()->format('Y-m-d');
                        $toValue = Jalalian::fromFormat('Y/m/d', $value[0] . '/' . $value[1] . '/' . ($value[1] < 7 ? '31' : ($value[1] == 12 ? '29'  : '30')))->toCarbon()->format('Y-m-d');
                        // $q->where('data->withdraw_date', 'LIKE', $value . '%');
                        $q->where('data->withdraw_date', '>=', $fromValue)->where('data->withdraw_date', '<=', $toValue);
                    } else
                        // $q->whereJsonContains($item, $item_filter);
                        // $q->where('data->' . $key, 'LIKE', $value);
                        $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(data, '$.$key')) LIKE ?", ["%$value%"]);
                }
            });
        }

        return $query;
    }


    public function scopeFilterJsonNullColumn(Builder $query, Collection $filters, string $item)
    {
        if ($filters->has($item) && is_array($filters->get($item))) {
            $item_filter = $filters->get($item);
            $query->when($item, function ($q) use ($item_filter, $item) {
                foreach ($item_filter as $key => $value) {
                    // $q->whereJsonContains($item, $item_filter);
                    // $q->orWhereJsonDoesntContain($item, $item_filter);
                    if ($value == 'null') {
                        $q->where(function ($query) use ($key) {
                            $query->where('data->' . $key, 'LIKE', "");
                            $query->orWhere('data', 'not like', '%' . $key . '%');
                        });
                        if ($key === 'receipt_number') {
                            $q->whereNull("bank_transaction_id")
                                ->where('transactionable_type', 'App\Models\Insurance')
                                ->whereIn('paymentable_id', [1, 2, 4, 5]);

                            $q->orWhere(function ($query) use ($key) {
                                $query->where(function ($innerQuery) use ($key) {
                                    $innerQuery
                                        ->where('paymentable_id', 6)
                                        ->whereNull("bank_transaction_id");
                                    // ->where('data->payment_type', "2");
                                    $innerQuery->where(function ($_innerQuery) use ($key) {
                                        $_innerQuery->where('data->' . $key, 'LIKE', "")
                                            ->orWhere('data', 'not like', '%' . $key . '%');
                                    });
                                });
                            });
                        }
                    } else if ($value !== "null") {
                        $q->where(function ($query) use ($key) {
                            $query->where('data->' . $key, 'Not LIKE', "");
                        });
                        if ($key === 'receipt_number') {
                            $q->whereNotNull("bank_transaction_id")
                                ->where('transactionable_type', 'App\Models\Insurance')
                                ->whereIn('paymentable_id', [1, 2, 4, 5]);

                            $q->orWhere(function ($query) use ($key) {
                                $query->where(function ($innerQuery) use ($key) {
                                    $innerQuery
                                        ->where('paymentable_id', 6)
                                        ->where('data->' . $key, 'Not LIKE', "")
                                        // ->where('data->payment_type', "2")
                                        ->whereNotNull("bank_transaction_id");
                                });
                            });
                        }
                    }
                }
            });
        }

        return $query;
    }


    public function toNumber($arr)
    {
        return ($arr);
    }
}
