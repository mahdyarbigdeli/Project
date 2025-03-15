<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubscriptionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('subscriptions')->insert([
            ['name' => ' اشتراک دائمی', 'price' => 599, 'name_en' => 'Lifetime Subscription'],
            ['name' => ' اشتراک یک ساله', 'price' => 120, 'name_en' => 'One Year Subscription'],
            ['name' => ' اشتراک شش ماهه ', 'price' => 90, 'name_en' => 'Six-Month Subscription'],
            ['name' => ' اشتراک ۱ ماهه', 'price' => 20, 'name_en' => 'One-Month Subscription'],
        ]);
    }
}
