<?php

namespace Database\Seeders;

use App\Models\Branch;
use App\Models\Product;
use App\Models\StockLevel;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Demo data for local development and portfolio review.
 *
 * Creates 2 branches, 3 users (one per role), 2 suppliers,
 * 8 products and stock levels — including one low-stock line
 * so the dashboard alerts render on a fresh install.
 */
class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        $joburg = Branch::firstOrCreate(
            ['code' => 'JHB-01'],
            ['name' => 'Johannesburg Central', 'address' => '123 Main Street, Johannesburg', 'phone' => '+27 11 000 1000', 'email' => 'jhb@branchops.test', 'is_active' => true]
        );
        $capeTown = Branch::firstOrCreate(
            ['code' => 'CPT-01'],
            ['name' => 'Cape Town Harbour', 'address' => '45 Dock Road, Cape Town', 'phone' => '+27 21 000 2000', 'email' => 'cpt@branchops.test', 'is_active' => true]
        );

        User::firstOrCreate(
            ['email' => 'admin@branchops.test'],
            ['name' => 'Super Admin', 'password' => Hash::make('password123'), 'role' => 'super_admin', 'branch_id' => null, 'is_active' => true]
        );
        User::firstOrCreate(
            ['email' => 'manager@branchops.test'],
            ['name' => 'Branch Manager', 'password' => Hash::make('password123'), 'role' => 'branch_manager', 'branch_id' => $joburg->id, 'is_active' => true]
        );
        User::firstOrCreate(
            ['email' => 'staff@branchops.test'],
            ['name' => 'Counter Staff', 'password' => Hash::make('password123'), 'role' => 'staff', 'branch_id' => $joburg->id, 'is_active' => true]
        );

        $acme = Supplier::firstOrCreate(
            ['name' => 'Acme Foods'],
            ['contact_name' => 'Sarah Naidoo', 'email' => 'orders@acmefoods.test', 'phone' => '+27 11 555 0101', 'address' => '7 Factory Lane, Johannesburg', 'tax_id' => 'TAX-ACME-001', 'is_active' => true]
        );
        $cape = Supplier::firstOrCreate(
            ['name' => 'Cape Fresh Produce'],
            ['contact_name' => 'Pieter van Wyk', 'email' => 'sales@capefresh.test', 'phone' => '+27 21 555 0202', 'address' => '12 Farm Road, Stellenbosch', 'tax_id' => 'TAX-CAPE-002', 'is_active' => true]
        );

        $catalog = [
            ['name' => 'Whole Milk 2L', 'sku' => 'MILK-2L', 'barcode' => '600100100001', 'cost_price' => 22.50, 'selling_price' => 32.99, 'reorder_level' => 20, 'supplier' => $acme],
            ['name' => 'Brown Bread 800g', 'sku' => 'BRD-BRN-800', 'barcode' => '600100100002', 'cost_price' => 14.00, 'selling_price' => 21.99, 'reorder_level' => 25, 'supplier' => $acme],
            ['name' => 'Free Range Eggs 30s', 'sku' => 'EGG-30', 'barcode' => '600100100003', 'cost_price' => 58.00, 'selling_price' => 79.99, 'reorder_level' => 15, 'supplier' => $cape],
            ['name' => 'Apples 1.5kg', 'sku' => 'APL-15KG', 'barcode' => '600100100004', 'cost_price' => 25.00, 'selling_price' => 39.99, 'reorder_level' => 15, 'supplier' => $cape],
            ['name' => 'Coffee Beans 1kg', 'sku' => 'COF-1KG', 'barcode' => '600100100005', 'cost_price' => 140.00, 'selling_price' => 199.99, 'reorder_level' => 8, 'supplier' => $acme],
            ['name' => 'Sunflower Oil 5L', 'sku' => 'OIL-5L', 'barcode' => '600100100006', 'cost_price' => 120.00, 'selling_price' => 169.99, 'reorder_level' => 10, 'supplier' => $acme],
            ['name' => 'Sugar 2.5kg', 'sku' => 'SUG-25KG', 'barcode' => '600100100007', 'cost_price' => 45.00, 'selling_price' => 62.99, 'reorder_level' => 12, 'supplier' => $acme],
            ['name' => 'Bananas 1kg', 'sku' => 'BAN-1KG', 'barcode' => '600100100008', 'cost_price' => 16.00, 'selling_price' => 24.99, 'reorder_level' => 10, 'supplier' => $cape],
        ];

        foreach ($catalog as $i => $row) {
            $product = Product::firstOrCreate(
                ['sku' => $row['sku']],
                ['name' => $row['name'], 'barcode' => $row['barcode'], 'cost_price' => $row['cost_price'], 'selling_price' => $row['selling_price'], 'reorder_level' => $row['reorder_level'], 'supplier_id' => $row['supplier']->id, 'is_active' => true]
            );

            // Healthy stock in Johannesburg, deliberately low bananas in Cape Town
            StockLevel::firstOrCreate(
                ['product_id' => $product->id, 'branch_id' => $joburg->id],
                ['quantity' => 40 + ($i * 5), 'valuation' => 0]
            );
            StockLevel::firstOrCreate(
                ['product_id' => $product->id, 'branch_id' => $capeTown->id],
                ['quantity' => $row['sku'] === 'BAN-1KG' ? 2 : 25, 'valuation' => 0]
            );
        }
    }
}
