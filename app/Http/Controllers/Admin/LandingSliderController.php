<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingSlider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class LandingSliderController extends Controller
{
    public function index()
    {
        $this->ensurePermission('admin.landing-sliders.index');

        $sliders = LandingSlider::query()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $activeCount = $sliders->where('is_active', true)->count();

        return view('admin.landing-sliders.index', compact('sliders', 'activeCount'));
    }

    public function store(Request $request): JsonResponse
    {
        $this->ensurePermission('admin.landing-sliders.store');
        $data = $this->validatedData($request);
        $storedFiles = [];

        try {
            $data['image_path'] = $request->file('image')->store('landing/sliders', 'public');
            $storedFiles[] = $data['image_path'];

            if ($request->hasFile('mobile_image')) {
                $data['mobile_image_path'] = $request->file('mobile_image')->store('landing/sliders', 'public');
                $storedFiles[] = $data['mobile_image_path'];
            }

            unset($data['image'], $data['mobile_image']);
            $slider = DB::transaction(fn () => LandingSlider::create($data));

            return response()->json([
                'success' => true,
                'message' => 'Slide registrado correctamente.',
                'data' => $this->serialize($slider),
            ], 201);
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_filter($storedFiles));
            Log::error('Error al registrar slide de landing.', ['exception' => $exception]);

            return response()->json(['success' => false, 'message' => 'No se pudo registrar el slide.'], 500);
        }
    }

    public function show(LandingSlider $landingSlider): JsonResponse
    {
        abort_unless(
            request()->user()?->can('admin.landing-sliders.show')
                || request()->user()?->can('admin.landing-sliders.update'),
            403,
        );

        return response()->json(['success' => true, 'data' => $this->serialize($landingSlider)]);
    }

    public function update(Request $request, LandingSlider $landingSlider): JsonResponse
    {
        $this->ensurePermission('admin.landing-sliders.update');
        $data = $this->validatedData($request, $landingSlider);
        $newFiles = [];
        $oldFiles = [];

        try {
            if ($request->hasFile('image')) {
                $data['image_path'] = $request->file('image')->store('landing/sliders', 'public');
                $newFiles[] = $data['image_path'];
                $oldFiles[] = $landingSlider->image_path;
            }

            if ($request->hasFile('mobile_image')) {
                $data['mobile_image_path'] = $request->file('mobile_image')->store('landing/sliders', 'public');
                $newFiles[] = $data['mobile_image_path'];
                $oldFiles[] = $landingSlider->mobile_image_path;
            }

            unset($data['image'], $data['mobile_image']);
            DB::transaction(fn () => $landingSlider->update($data));
        } catch (Throwable $exception) {
            Storage::disk('public')->delete(array_filter($newFiles));
            Log::error('Error al actualizar slide de landing.', ['exception' => $exception]);

            return response()->json(['success' => false, 'message' => 'No se pudo actualizar el slide.'], 500);
        }

        try {
            $this->deleteManagedFiles($oldFiles);
        } catch (Throwable $exception) {
            Log::warning('El slide se actualizó, pero no se pudo retirar una imagen anterior.', ['exception' => $exception]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Slide actualizado correctamente.',
            'data' => $this->serialize($landingSlider->fresh()),
        ]);
    }

    public function toggle(LandingSlider $landingSlider): JsonResponse
    {
        $this->ensurePermission('admin.landing-sliders.toggle');

        $landingSlider->update(['is_active' => ! $landingSlider->is_active]);

        return response()->json([
            'success' => true,
            'message' => $landingSlider->is_active ? 'Slide activado correctamente.' : 'Slide desactivado correctamente.',
            'data' => [
                'id' => $landingSlider->id,
                'is_active' => $landingSlider->is_active,
            ],
        ]);
    }

    private function validatedData(Request $request, ?LandingSlider $slider = null): array
    {
        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:220'],
            'description' => ['nullable', 'string', 'max:1000'],
            'secondary_text' => ['nullable', 'string', 'max:200'],
            'button_text' => ['nullable', 'string', 'max:80'],
            'button_url' => ['nullable', 'string', 'max:255', function (string $attribute, mixed $value, \Closure $fail) {
                $url = trim((string) $value);

                if (preg_match('/[\x00-\x1F\x7F]/', $url) || preg_match('/^(javascript|data|vbscript):/i', $url)) {
                    $fail('El enlace del botón utiliza un protocolo no permitido.');

                    return;
                }

                if ($url !== '' && ! preg_match('~^(#|/(?!/)|https?://|mailto:|tel:)~i', $url)) {
                    $fail('El enlace debe ser una ancla, una ruta interna o una URL segura.');
                }
            }],
            'image_alt' => ['nullable', 'string', 'max:180'],
            'sort_order' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'image' => [$slider ? 'nullable' : 'required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'title.required' => 'El título es obligatorio.',
            'title.max' => 'El título no puede superar los 220 caracteres.',
            'eyebrow.max' => 'El texto superior no puede superar los 150 caracteres.',
            'description.max' => 'La descripción no puede superar los 1000 caracteres.',
            'secondary_text.max' => 'El texto secundario no puede superar los 200 caracteres.',
            'button_text.max' => 'El texto del botón no puede superar los 80 caracteres.',
            'button_url.max' => 'El enlace no puede superar los 255 caracteres.',
            'image_alt.max' => 'El texto alternativo no puede superar los 180 caracteres.',
            'sort_order.required' => 'El orden es obligatorio.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'sort_order.min' => 'El orden debe ser mayor o igual a 1.',
            'is_active.required' => 'El estado es obligatorio.',
            'is_active.boolean' => 'El estado seleccionado no es válido.',
            'image.required' => 'La imagen principal es obligatoria.',
            'image.mimes' => 'La imagen principal debe ser JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen principal no puede superar los 5 MB.',
            'mobile_image.mimes' => 'La imagen móvil debe ser JPG, JPEG, PNG o WEBP.',
            'mobile_image.max' => 'La imagen móvil no puede superar los 5 MB.',
        ]);

        if (array_key_exists('button_url', $data) && $data['button_url'] !== null) {
            $data['button_url'] = trim($data['button_url']);
        }

        return $data;
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless(request()->user()?->can($permission), 403);
    }

    private function deleteManagedFiles(array $paths): void
    {
        $managedPaths = collect($paths)
            ->filter(fn ($path) => is_string($path) && Str::startsWith($path, 'landing/sliders/'))
            ->values()
            ->all();

        if ($managedPaths !== []) {
            Storage::disk('public')->delete($managedPaths);
        }
    }

    private function serialize(LandingSlider $slider): array
    {
        return [
            'id' => $slider->id,
            'eyebrow' => $slider->eyebrow,
            'title' => $slider->title,
            'description' => $slider->description,
            'secondary_text' => $slider->secondary_text,
            'button_text' => $slider->button_text,
            'button_url' => $slider->button_url,
            'image_path' => $slider->image_path,
            'mobile_image_path' => $slider->mobile_image_path,
            'image_alt' => $slider->image_alt,
            'sort_order' => $slider->sort_order,
            'is_active' => $slider->is_active,
            'image_url' => $slider->imageUrl(),
            'mobile_image_url' => $slider->mobileImageUrl(),
            'created_at' => optional($slider->created_at)->format('d/m/Y H:i'),
            'updated_at' => optional($slider->updated_at)->format('d/m/Y H:i'),
        ];
    }
}
