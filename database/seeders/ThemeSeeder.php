<?php

namespace Database\Seeders;

use App\Models\Theme;
use Illuminate\Database\Seeder;

class ThemeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $themes = [
            [
                'name' => 'Light',
                'slug' => 'light',
                'css_variables' => [
                    'main' => '#007bff',
                    'accent' => '#6c757d',
                    'background' => '#ffffff',
                    'text' => '#333333',
                    'card' => '#f8f9fa',
                    'border' => '#dee2e6',
                ],
                'is_default' => true,
            ],
            [
                'name' => 'Dark',
                'slug' => 'dark',
                'css_variables' => [
                    'main' => '#007bff',
                    'accent' => '#6c757d',
                    'background' => '#121212',
                    'text' => '#ffffff',
                    'card' => '#1e1e1e',
                    'border' => '#333333',
                ],
                'is_default' => false,
            ],
            [
                'name' => 'Movies',
                'slug' => 'movies',
                'css_variables' => [
                    'main' => '#e50914',
                    'accent' => '#f5c518',
                    'background' => '#000000',
                    'text' => '#ffffff',
                    'card' => '#141414',
                    'border' => '#333333',
                ],
                'is_default' => false,
            ],
            [
                'name' => 'Sports',
                'slug' => 'sports',
                'css_variables' => [
                    'main' => '#1e40af',
                    'accent' => '#dc2626',
                    'background' => '#0f172a',
                    'text' => '#f8fafc',
                    'card' => '#1e293b',
                    'border' => '#334155',
                ],
                'is_default' => false,
            ],
            [
                'name' => 'Rugby',
                'slug' => 'rugby',
                'css_variables' => [
                    'main' => '#1e40af',
                    'accent' => '#dc2626',
                    'background' => '#0f172a',
                    'text' => '#f8fafc',
                    'card' => '#1e293b',
                    'border' => '#334155',
                ],
                'is_default' => false,
            ],
        ];

        foreach ($themes as $theme) {
            Theme::create($theme);
        }
    }
}
