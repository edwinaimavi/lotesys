<?php

namespace App\Services;

use App\Models\LandingContactItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class LandingContacts
{
    private ?Collection $active = null;

    private function active(): Collection
    {
        return $this->active ??= Schema::hasTable('landing_contact_items')
            ? LandingContactItem::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
            : collect();
    }

    public function whatsapp(): string
    {
        $item = $this->active()->first(fn ($item) => $item->type === 'whatsapp' && $item->use_for_cta);

        return preg_replace('/\D/', '', (string) ($item?->value ?? config('landing.whatsapp', '')));
    }

    public function footer(): Collection
    {
        if ($this->active()->isNotEmpty()) {
            return $this->active()->where('show_in_footer', true)->values();
        }

        $items = collect();
        $add = function ($type, $value, $url = null, $network = null) use ($items) {
            if (filled($value)) $items->push(new LandingContactItem(compact('type', 'value', 'url', 'network')));
        };
        $add('phone', config('landing.phone'));
        foreach (config('landing.additional_phones', []) as $phone) $add('whatsapp', $phone);
        $add('email', config('landing.email'));
        $add('address', config('landing.address'));
        foreach (config('landing.socials', []) as $network => $url) $add('social', $network, $url, strtolower($network));

        return $items;
    }
}
