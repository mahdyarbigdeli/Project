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
            ['name' => ' اشتراک دائمی', 'price' => 10.00],
            ['name' => ' اشتراک یک ساله', 'price' => 20.00],
            ['name' => ' اشتراک ۲ ماهه', 'price' => 30.00],
        ]);
    }
}
