<?php

use Illuminate\Database\Seeder;
use Illuminate\Database\QueryException;

class KeySeeder extends Seeder
{
    public function run()
    {
        try {
            DB::table('key')->insert([
                'id' => 1,
                'name' => 'X-Key',
                'hash' => env('APP_KEY'),
                'created_at' => new DateTime('now'),
                'updated_at' => new DateTime('now')
            ]);
        } catch (QueryException $e) {
            if ($e->getCode() != 23000) {
                throw $e;   // re-throw if it's not a duplicate key exception (this works like an INSERT IGNORE statement)
            }
        }
    }
}
