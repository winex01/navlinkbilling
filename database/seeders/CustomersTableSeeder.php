<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class CustomersTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('customers')->delete();
        
        \DB::table('customers')->insert(array (
            0 => 
            array (
                'id' => 1,
                'first_name' => 'Audra',
                'last_name' => 'Benson',
                'date_of_birth' => '1985-03-24',
                'contact_number' => '992',
                'email' => 'fode@mailinator.com',
                'block_street' => 'Qui mollit officiis',
                'barangay' => 'Debitis et velit est',
                'city_or_municipality' => 'Nemo ut magna molest',
                'social_media' => 'Ad laborum Nisi duc',
                'notes' => 'Molestiae ipsum duis',
                'signature' => 'signature/signature_1718940095.png',
                'deleted_at' => NULL,
                'created_at' => '2024-06-21 03:21:35',
                'updated_at' => '2024-06-21 03:21:35',
            ),
            1 => 
            array (
                'id' => 2,
                'first_name' => 'Aiko',
                'last_name' => 'Banks',
                'date_of_birth' => '2007-07-11',
                'contact_number' => '419',
                'email' => 'dypu@mailinator.com',
                'block_street' => 'Consequatur enim ul',
                'barangay' => 'Fugiat tempore atq',
                'city_or_municipality' => 'Et magna at dolor ip',
                'social_media' => 'Omnis autem consequa',
                'notes' => 'Aut molestiae et non',
                'signature' => 'signature/signature_1718940106.png',
                'deleted_at' => NULL,
                'created_at' => '2024-06-21 03:21:46',
                'updated_at' => '2024-06-21 03:21:46',
            ),
            2 => 
            array (
                'id' => 3,
                'first_name' => 'Jorden',
                'last_name' => 'Mcfarland',
                'date_of_birth' => '1980-05-14',
                'contact_number' => '43',
                'email' => 'zujini@mailinator.com',
                'block_street' => 'Culpa et tempor nes',
                'barangay' => 'Dolor architecto qui',
                'city_or_municipality' => 'A est velit dolor pl',
                'social_media' => 'Aut aliquid ipsa sa',
                'notes' => 'Et aliquid autem qua',
                'signature' => 'signature/signature_1718940116.png',
                'deleted_at' => NULL,
                'created_at' => '2024-06-21 03:21:56',
                'updated_at' => '2024-06-21 03:21:56',
            ),
        ));
        
        
    }
}