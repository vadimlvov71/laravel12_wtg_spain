<?php
// database/seeders/SupplierSeeder.php
namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        Supplier::create([
            'code' => 'supplier-a',
            'name' => 'Supplier A',
            'api_key' => 'key-123'
        ]);

        Supplier::create([
            'code' => 'supplier-b',
            'name' => 'Supplier B',
            'api_key' => 'key-456'
        ]);
    }
}