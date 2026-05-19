<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Product;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'category_id' => 1,
                'name' => 'آيفون 14 برو',
                'slug' => 'iphone-14-pro',
                'description' => 'أحدث هاتف من أبل بمواصفات عالية جداً',
                'price' => 55000,
                'discount_price' => 45000,
                'stock' => 20,
                'color' => 'أسود',
                'images' => json_encode(['image1.jpg', 'image2.jpg']),
                'rating' => 5
            ],
            [
                'category_id' => 1,
                'name' => 'آيفون 15',
                'slug' => 'iphone-15',
                'description' => 'أحدث هاتف من أبل',
                'price' => 55000,
                'discount_price' => 45000,
                'stock' => 15,
                'color' => 'أزرق',
                'images' => json_encode(['image1.jpg']),
                'rating' => 4
            ],
            [
                'category_id' => 2,
                'name' => 'سماعات رأس لاسلكية',
                'slug' => 'wireless-headphones',
                'description' => 'سماعات بتقنية ضوضاء عالية',
                'price' => 1500,
                'discount_price' => 1200,
                'stock' => 50,
                'color' => 'أسود',
                'images' => json_encode(['headphone.jpg']),
                'rating' => 4
            ],
        ];

        foreach ($products as $product) {
            Product::create($product);
        }
    }
}

