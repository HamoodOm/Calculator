<?php

namespace Database\Seeders;

use App\Models\Institution;
use Illuminate\Database\Seeder;

class InstitutionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        // Create default institution
        Institution::updateOrCreate(
            ['slug' => 'main'],
            [
                'name' => 'المؤسسة الرئيسية',
                'description' => 'المؤسسة الافتراضية للنظام',
                'header_color' => 'bg-indigo-700',
                'badge_color' => 'bg-indigo-500',
                'is_active' => true,
            ]
        );

        // Create development department (example)
        Institution::updateOrCreate(
            ['slug' => 'development'],
            [
                'name' => 'قسم التطوير',
                'description' => 'قسم التطوير والتدريب',
                'header_color' => 'bg-emerald-700',
                'badge_color' => 'bg-emerald-500',
                'is_active' => true,
            ]
        );
    }
}
