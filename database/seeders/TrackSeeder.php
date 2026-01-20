<?php

namespace Database\Seeders;

use App\Models\Track;
use Illuminate\Database\Seeder;

class TrackSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $tracks = config('certificates.tracks_teacher');

        foreach ($tracks as $key => $names) {
            Track::updateOrCreate(
                ['key' => $key],
                [
                    'name_ar' => $names['ar'],
                    'name_en' => $names['en'],
                    'active' => true,
                ]
            );
        }

        $this->command->info('Tracks seeded successfully from config.');
    }
}
