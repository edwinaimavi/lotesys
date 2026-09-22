<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingContactItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LandingContactController extends Controller
{
    private function permit(string $action): void
    {
        abort_unless(request()->user()?->can('admin.landing-contacts.'.$action), 403);
    }

    public function index(Request $request)
    {
        $this->permit('index');
        $items = LandingContactItem::orderBy('sort_order')->orderBy('id')->get();
        if ($request->expectsJson()) return response()->json(['data' => $items]);

        return view('admin.landing-contacts.index', compact('items'));
    }

    public function show(LandingContactItem $landingContact)
    {
        abort_unless(request()->user()?->can('admin.landing-contacts.show') || request()->user()?->can('admin.landing-contacts.update'), 403);

        return response()->json(['data' => $landingContact]);
    }

    public function store(Request $request)
    {
        $this->permit('store');
        return $this->save($request, new LandingContactItem);
    }

    public function update(Request $request, LandingContactItem $landingContact)
    {
        $this->permit('update');
        return $this->save($request, $landingContact);
    }

    private function save(Request $request, LandingContactItem $item)
    {
        $type = $request->input('type');
        $data = $request->validate([
            'type' => ['required', Rule::in(LandingContactItem::TYPES)],
            'label' => ['nullable', 'string', 'max:255'],
            'value' => [$type === 'social' ? 'nullable' : 'required', 'string', 'max:1000', ...($type === 'email' ? ['email:rfc', 'max:254'] : []),
                function ($attribute, $value, $fail) use ($type) {
                    if (in_array($type, ['phone', 'whatsapp'], true) && (! preg_match('/^\+?[0-9\s()\-]+$/D', $value) || ! preg_match('/^[0-9]{7,15}$/D', preg_replace('/\D/', '', $value)))) $fail('Ingresa un teléfono válido de 7 a 15 dígitos, con código de país para WhatsApp.');
                }],
            'url' => [$type === 'social' ? 'required' : 'nullable', 'string', 'max:2048', 'url', function ($attribute, $value, $fail) use ($type) {
                $schemes = $type === 'social' ? ['https'] : ['https', 'http'];
                if (! in_array(strtolower(parse_url($value, PHP_URL_SCHEME) ?? ''), $schemes, true) || preg_match('/[\x00-\x20\x7f]/', $value)) $fail('Ingresa una URL segura'.($type === 'social' ? ' HTTPS.' : ' HTTP o HTTPS.'));
            }],
            'network' => [$type === 'social' ? 'required' : 'nullable', Rule::in(LandingContactItem::NETWORKS)],
            'is_primary' => ['required', 'boolean'],
            'use_for_cta' => ['required', 'boolean', function ($attribute, $value, $fail) use ($type) {
                if ($value && $type !== 'whatsapp') $fail('Solo un WhatsApp puede usarse como CTA principal.');
            }],
            'show_in_footer' => ['required', 'boolean'], 'is_active' => ['required', 'boolean'],
            'sort_order' => ['required', 'integer', 'min:1', 'max:4294967295'],
        ]);
        $data['network'] = $type === 'social' ? $data['network'] : null;
        $data['url'] = in_array($type, ['social', 'address'], true) ? ($data['url'] ?? null) : null;
        if ($type === 'social') $data['value'] = ($data['value'] ?? null) ?: (($data['label'] ?? null) ?: ucfirst($data['network']));
        $created = ! $item->exists;
        DB::transaction(function () use ($item, $data) {
            // UPDATE locks existing CTA rows; the unique generated slot guards concurrent inserts.
            if ($data['use_for_cta']) LandingContactItem::where('use_for_cta', true)->update(['use_for_cta' => false]);
            if ($data['is_primary']) LandingContactItem::where('type', $data['type'])->where('is_primary', true)->update(['is_primary' => false]);
            if ($item->exists) $item->refresh();
            $item->fill($data)->save();
        }, 3);

        return response()->json(['success' => true, 'message' => 'Contacto guardado correctamente.', 'data' => $item->fresh()], $created ? 201 : 200);
    }

    public function toggle(LandingContactItem $landingContact)
    {
        $this->permit('toggle');
        $item = DB::transaction(function () use ($landingContact) {
            $item = LandingContactItem::whereKey($landingContact->id)->lockForUpdate()->firstOrFail();
            $item->update(['is_active' => ! $item->is_active]);
            return $item;
        });

        return response()->json(['success' => true, 'message' => 'Estado actualizado.', 'data' => $item]);
    }
}
