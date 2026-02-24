<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            FdSeeder::class,
            BadgeSeeder::class,
            ParametreValeurSeeder::class,
            DepartementSeeder::class,
            DemoSeeder::class,
            SuperviseursSeeder::class,
        ]);
    }
}
