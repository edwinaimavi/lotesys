<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProjectWebProfile extends Model
{
    public const STATUSES = [
        'coming_soon' => 'PRÓXIMAMENTE',
        'presale' => 'PREVENTA',
        'available' => 'DISPONIBLE',
        'last_units' => 'ÚLTIMOS LOTES',
        'sold_out' => 'AGOTADO',
    ];

    protected $fillable = [
        'project_id', 'show_on_web', 'featured_on_home', 'commercial_status',
        'cover_image_path', 'mobile_image_path', 'plan_image_path', 'plan_mobile_image_path',
        'plan_image_alt', 'plan_caption', 'short_description',
        'image_alt', 'badge_text', 'sort_order',
    ];

    protected $casts = [
        'show_on_web' => 'boolean',
        'featured_on_home' => 'boolean',
        'sort_order' => 'integer',
    ];

    protected $appends = [
        'commercial_status_label', 'cover_image_url', 'mobile_image_url',
        'plan_image_url', 'plan_mobile_image_url',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getCommercialStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->commercial_status] ?? self::STATUSES['coming_soon'];
    }

    public function getCoverImageUrlAttribute(): ?string
    {
        return $this->cover_image_path ? Storage::disk('public')->url($this->cover_image_path) : null;
    }

    public function getMobileImageUrlAttribute(): ?string
    {
        return $this->mobile_image_path ? Storage::disk('public')->url($this->mobile_image_path) : null;
    }

    public function getPlanImageUrlAttribute(): ?string
    {
        return $this->plan_image_path ? Storage::disk('public')->url($this->plan_image_path) : null;
    }

    public function getPlanMobileImageUrlAttribute(): ?string
    {
        return $this->plan_mobile_image_path ? Storage::disk('public')->url($this->plan_mobile_image_path) : null;
    }
}
