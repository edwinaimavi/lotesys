<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class LandingSlider extends Model
{
    protected $fillable = [
        'eyebrow',
        'title',
        'description',
        'secondary_text',
        'button_text',
        'button_url',
        'image_path',
        'mobile_image_path',
        'image_alt',
        'sort_order',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function imageUrl(): string
    {
        return $this->assetUrl($this->image_path);
    }

    public function mobileImageUrl(): ?string
    {
        return $this->mobile_image_path ? $this->assetUrl($this->mobile_image_path) : null;
    }

    private function assetUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://', '/'])) {
            return $path;
        }

        if (Str::startsWith($path, 'landing/sliders/')) {
            return Storage::disk('public')->url($path);
        }

        return asset($path);
    }
}
