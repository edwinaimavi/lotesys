<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class LandingContactItem extends Model
{
    public const TYPES = ['phone', 'whatsapp', 'email', 'address', 'social'];
    public const NETWORKS = ['facebook', 'instagram', 'tiktok', 'youtube', 'linkedin', 'x', 'other'];

    protected $fillable = ['type', 'label', 'value', 'url', 'network', 'is_primary', 'use_for_cta', 'show_in_footer', 'sort_order', 'is_active'];
    protected $hidden = ['cta_slot'];
    protected $casts = ['is_primary' => 'boolean', 'use_for_cta' => 'boolean', 'show_in_footer' => 'boolean', 'is_active' => 'boolean', 'sort_order' => 'integer'];

    public function href(): ?string
    {
        return match ($this->type) {
            'whatsapp' => 'https://wa.me/'.preg_replace('/\D/', '', $this->value),
            'phone' => 'tel:'.(str_starts_with(trim($this->value), '+') ? '+' : '').preg_replace('/\D/', '', $this->value),
            'email' => filter_var($this->value, FILTER_VALIDATE_EMAIL) ? 'mailto:'.$this->value : null,
            'address', 'social' => filter_var($this->url, FILTER_VALIDATE_URL) && in_array(strtolower(parse_url($this->url, PHP_URL_SCHEME) ?? ''), ['http', 'https'], true) ? $this->url : null,
            default => null,
        };
    }

    public function icon(): string
    {
        return match ($this->type) {
            'phone' => 'phone', 'whatsapp' => 'whatsapp', 'email' => 'mail', 'address' => 'pin',
            'social' => in_array($this->network, ['facebook', 'instagram', 'tiktok'], true) ? $this->network : 'link',
            default => 'link',
        };
    }
}
