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
            ['name' => ' اشتراک دائمی', 'price' => 499],
            ['name' => ' اشتراک یک ساله', 'price' => 120],
            ['name' => ' اشتراک شش ماهه ', 'price' => 90],
            ['name' => ' اشتراک ۲ ماهه', 'price' => 30],
            ['name' => ' اشتراک ۱ ماهه', 'price' => 20],
        ]);
    }
}
