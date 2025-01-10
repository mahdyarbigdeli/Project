<?php

namespace App\Http\Middleware;

use Closure;

class NumberToEngMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $parameter = $request->all() + ['index' => 'value'];

        foreach ($parameter as $index => $value) {
            $persinaDigits1 = ['۰', '۱', '۲', '۳', '۴', '۵', '۶', '۷', '۸', '۹'];
            $persinaDigits2 = ['٩', '٨', '٧', '٦', '٥', '٤', '٣', '٢', '١', '٠'];
            $allPersianDigits = array_merge($persinaDigits1, $persinaDigits2);
            $replaces = [...range(0, 9), ...range(0, 9)];
            $newValue =  str_replace($allPersianDigits, $replaces, $request->$index);
            $request->$index = $newValue;
            $request->merge([
                $index => $newValue
            ]);
        }
        return $next($request);
    }
}
