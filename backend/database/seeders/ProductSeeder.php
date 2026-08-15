<?php

namespace Database\Seeders;

use App\Modules\Pharmacy\Models\Product;
use App\Modules\Settings\Models\Hospital;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $hospital = Hospital::query()->where('code', 'GH01')->firstOrFail();

        $products = [
            ['sku' => 'PARA500', 'name' => 'Paracetamol 500mg', 'generic_name' => 'Paracetamol', 'form' => 'tablet', 'strength' => '500mg', 'unit' => 'tablet', 'reorder_level' => 50],
            ['sku' => 'AMOX250', 'name' => 'Amoxicillin 250mg', 'generic_name' => 'Amoxicillin', 'form' => 'capsule', 'strength' => '250mg', 'unit' => 'capsule', 'reorder_level' => 40],
            ['sku' => 'ORS1', 'name' => 'ORS Sachet', 'generic_name' => 'Oral rehydration salts', 'form' => 'sachet', 'strength' => null, 'unit' => 'sachet', 'reorder_level' => 30],
            ['sku' => 'IBU400', 'name' => 'Ibuprofen 400mg', 'generic_name' => 'Ibuprofen', 'form' => 'tablet', 'strength' => '400mg', 'unit' => 'tablet', 'reorder_level' => 40],
        ];

        foreach ($products as $product) {
            Product::query()->updateOrCreate(
                ['hospital_id' => $hospital->id, 'sku' => $product['sku']],
                [...$product, 'is_active' => true],
            );
        }
    }
}
