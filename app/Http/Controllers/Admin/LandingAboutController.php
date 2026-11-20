<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\LandingAboutSection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Throwable;

class LandingAboutController extends Controller
{
    public function index(): View
    {
        $this->ensurePermission('admin.landing-about.index');

        $about = LandingAboutSection::query()
            ->where('key', 'about')
            ->firstOrFail();

        return view('admin.landing-about.index', compact('about'));
    }

    public function update(Request $request): RedirectResponse
    {
        $this->ensurePermission('admin.landing-about.update');

        $about = LandingAboutSection::query()
            ->where('key', 'about')
            ->firstOrFail();

        $data = $request->validate([
            'eyebrow' => ['nullable', 'string', 'max:150'],
            'title' => ['required', 'string', 'max:220'],
            'description' => ['required', 'string', 'max:3000'],
            'mission_title' => ['required', 'string', 'max:100'],
            'mission_text' => ['required', 'string', 'max:2500'],
            'vision_title' => ['required', 'string', 'max:100'],
            'vision_text' => ['required', 'string', 'max:2500'],
            'image_alt' => ['nullable', 'string', 'max:180'],
            'image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'is_active' => ['required', 'boolean'],
        ], [
            'title.required' => 'El título es obligatorio.',
            'description.required' => 'La descripción de Quiénes somos es obligatoria.',
            'mission_title.required' => 'El título de la misión es obligatorio.',
            'mission_text.required' => 'La misión es obligatoria.',
            'vision_title.required' => 'El título de la visión es obligatorio.',
            'vision_text.required' => 'La visión es obligatoria.',
            'image.mimes' => 'La imagen debe ser JPG, JPEG, PNG o WEBP.',
            'image.max' => 'La imagen no puede superar los 5 MB.',
        ]);

        $newImage = null;
        $oldImage = $about->image_path;

        try {
            if ($request->hasFile('image')) {
                $newImage = $request->file('image')->store('landing/about', 'public');
                $data['image_path'] = $newImage;
            }

            unset($data['image']);
            $data['updated_by'] = $request->user()?->id;

            DB::transaction(function () use ($about, $data) {
                $about->update($data);
            });
        } catch (Throwable $exception) {
            if ($newImage) {
                Storage::disk('public')->delete($newImage);
            }

            report($exception);

            return back()
                ->withInput()
                ->with('error', 'No se pudo actualizar la sección Quiénes somos.');
        }

        if ($newImage && $oldImage && Str::startsWith($oldImage, 'landing/about/')) {
            Storage::disk('public')->delete($oldImage);
        }

        return back()->with('success', 'La sección Quiénes somos se actualizó correctamente.');
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless(request()->user()?->can($permission), 403);
    }
}
