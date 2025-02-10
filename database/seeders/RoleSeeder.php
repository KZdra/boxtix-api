<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        DB::table("roles")->truncate();
        Schema::enableForeignKeyConstraints();
        $role = [
            ['name' => 'admin', 'created_at' => now()],
            ['name' => 'eo', 'created_at' => now()],
        ];

        DB::table('roles')->insert($role);
    }
}
