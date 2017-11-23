<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         DB::table('users')->insert([

            ['name' => 'Admin',
            'email' => 'admin@la.fr',
            'password' => bcrypt('admin'),
            'role' => 'admin'],

            ['name' => 'Redactor',
            'email' => 'redac@la.fr',
            'password' => bcrypt('admin'),
            'role' => 'redac'],

            ['name' => 'Manager',
            'email' => 'manager@la.fr',
            'password' => bcrypt('admin'),
            'role' => 'manager'],

            ['name' => 'employee',
            'email' => 'employee@la.fr',
            'password' => bcrypt('admin'),
            'role' => 'user'],
         ]);
    }
}
