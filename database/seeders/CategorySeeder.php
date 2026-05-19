<?php
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['name' => 'الهواتف الذكية', 'slug' => 'phones'],
            ['name' => 'سماعات الرأس', 'slug' => 'headphones'],
            ['name' => 'الأكسسوارات', 'slug' => 'accessories'],
            ['name' => 'مشغولات الذهبية', 'slug' => 'gold-jewelry'], 
            ['name' => 'السلاسل', 'slug' => 'chains'],
            ['name' => 'الأساور', 'slug' => 'bracelets'],
            ['name' => 'الخواتم', 'slug' => 'rings'],
        ];

        foreach ($categories as $category) {
            Category::updateOrCreate(
                ['slug' => $category['slug']], 
                ['name' => $category['name']]
            );
        }
    }
}
