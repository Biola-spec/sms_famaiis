<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use DB;

class SiteSettingSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('site_settings')->updateOrInsert(
            ['id' => 1],
            [
                'school_name' => 'Easy School Management',
                'school_email' => 'support@easysms.com',
                'school_mobile_one' => '0123456789',
                'school_mobile_two' => '0987654321',
                'school_address' => 'Lagos, Nigeria',
                'current_session' => '2023-2024',
                'logo' => 'upload/logo/no_image.jpg',
                'copyright' => 'Copyright © 2024 Easy SMS. All Right Reserved',
            ]
        );
    }
}
