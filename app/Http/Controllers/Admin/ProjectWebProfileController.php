<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\ProjectWebProfile;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ProjectWebProfileController extends Controller
{
    public function index()
    {
        $this->ensurePermission('admin.project-web-profiles.index');

        $projects = Project::query()
            ->with(['company', 'webProfile'])
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        return view('admin.project-web-profiles.index', [
            'projects' => $projects,
            'statuses' => ProjectWebProfile::STATUSES,
        ]);
    }

    public function show(Project $project): JsonResponse
    {
        abort_unless(
            request()->user()?->can('admin.project-web-profiles.show')
                || request()->user()?->can('admin.project-web-profiles.update'),
            403,
        );

        return response()->json([
            'success' => true,
            'data' => $this->serialize($project->loadMissing(['company', 'webProfile'])),
        ]);
    }

    public function update(Request $request, Project $project): JsonResponse
    {
        $this->ensurePermission('admin.project-web-profiles.update');

        $profile = $project->webProfile;
        $data = $request->validate([
            'show_on_web' => ['required', 'boolean'],
            'featured_on_home' => ['required', 'boolean'],
            'commercial_status' => ['required', Rule::in(array_keys(ProjectWebProfile::STATUSES))],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'mobile_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'plan_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'plan_mobile_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:8192'],
            'plan_image_alt' => ['nullable', 'string', 'max:180', $this->plainTextRule()],
            'plan_caption' => ['nullable', 'string', 'max:180', $this->plainTextRule()],
            'short_description' => ['nullable', 'string', 'max:500', $this->plainTextRule()],
            'image_alt' => ['nullable', 'string', 'max:180', $this->plainTextRule()],
            'badge_text' => ['nullable', 'string', 'max:80', $this->plainTextRule()],
            'sort_order' => ['required', 'integer', 'min:1'],
        ], [
            'commercial_status.in' => 'Selecciona un estado comercial válido.',
            'cover_image.mimes' => 'La portada debe ser JPG, JPEG, PNG o WEBP.',
            'cover_image.max' => 'La portada no puede superar los 5 MB.',
            'mobile_image.mimes' => 'La imagen móvil debe ser JPG, JPEG, PNG o WEBP.',
            'mobile_image.max' => 'La imagen móvil no puede superar los 5 MB.',
            'plan_image.mimes' => 'El plano debe ser JPG, JPEG, PNG o WEBP.',
            'plan_image.max' => 'El plano no puede superar los 8 MB.',
            'plan_mobile_image.mimes' => 'El plano móvil debe ser JPG, JPEG, PNG o WEBP.',
            'plan_mobile_image.max' => 'El plano móvil no puede superar los 8 MB.',
        ]);

        if ((bool) $data['show_on_web'] && ! $profile?->cover_image_path && ! $request->hasFile('cover_image')) {
            throw ValidationException::withMessages([
                'cover_image' => 'La portada es obligatoria para mostrar el proyecto en la web.',
            ]);
        }

        $newFiles = [];
        $oldFiles = [];

        try {
            if ($request->hasFile('cover_image')) {
                $data['cover_image_path'] = $request->file('cover_image')->store('landing/projects', 'public');
                $newFiles[] = $data['cover_image_path'];
                $oldFiles[] = $profile?->cover_image_path;
            }

            if ($request->hasFile('mobile_image')) {
                $data['mobile_image_path'] = $request->file('mobile_image')->store('landing/projects', 'public');
                $newFiles[] = $data['mobile_image_path'];
                $oldFiles[] = $profile?->mobile_image_path;
            }

            if ($request->hasFile('plan_image')) {
                $data['plan_image_path'] = $request->file('plan_image')->store('landing/projects/plans', 'public');
                $newFiles[] = $data['plan_image_path'];
                $oldFiles[] = $profile?->plan_image_path;
            }

            if ($request->hasFile('plan_mobile_image')) {
                $data['plan_mobile_image_path'] = $request->file('plan_mobile_image')->store('landing/projects/plans', 'public');
                $newFiles[] = $data['plan_mobile_image_path'];
                $oldFiles[] = $profile?->plan_mobile_image_path;
            }

            unset($data['cover_image'], $data['mobile_image'], $data['plan_image'], $data['plan_mobile_image']);

            DB::transaction(function () use ($project, $data): void {
                $project->webProfile()->updateOrCreate([], $data);
            });
        } catch (\Throwable $exception) {
            Storage::disk('public')->delete(array_filter($newFiles));
            report($exception);

            return response()->json(['success' => false, 'message' => 'No se pudo guardar la configuración web.'], 500);
        }

        $managedOldFiles = collect($oldFiles)
            ->filter(fn ($path) => is_string($path) && Str::startsWith($path, 'landing/projects/'))
            ->all();
        Storage::disk('public')->delete($managedOldFiles);

        return response()->json([
            'success' => true,
            'message' => 'Configuración web actualizada correctamente.',
            'data' => $this->serialize($project->fresh(['company', 'webProfile'])),
        ]);
    }

    private function ensurePermission(string $permission): void
    {
        abort_unless(request()->user()?->can($permission), 403);
    }

    private function plainTextRule(): \Closure
    {
        return function (string $attribute, mixed $value, \Closure $fail): void {
            if ($value !== strip_tags((string) $value)) {
                $fail('Este campo no admite HTML.');
            }
        };
    }

    private function serialize(Project $project): array
    {
        $profile = $project->webProfile;

        return [
            'id' => $project->id,
            'name' => $project->name,
            'company' => $project->company?->business_name ?? 'Sin empresa',
            'location' => collect([$project->district, $project->province, $project->department])->filter()->join(', '),
            'configured' => (bool) $profile,
            'show_on_web' => $profile?->show_on_web ?? false,
            'featured_on_home' => $profile?->featured_on_home ?? false,
            'commercial_status' => $profile?->commercial_status ?? 'coming_soon',
            'commercial_status_label' => $profile?->commercial_status_label ?? ProjectWebProfile::STATUSES['coming_soon'],
            'cover_image_url' => $profile?->cover_image_url,
            'mobile_image_url' => $profile?->mobile_image_url,
            'plan_image_url' => $profile?->plan_image_url,
            'plan_mobile_image_url' => $profile?->plan_mobile_image_url,
            'plan_image_alt' => $profile?->plan_image_alt,
            'plan_caption' => $profile?->plan_caption,
            'short_description' => $profile?->short_description,
            'image_alt' => $profile?->image_alt,
            'badge_text' => $profile?->badge_text,
            'sort_order' => $profile?->sort_order ?? 1,
            'updated_at' => optional($profile?->updated_at)->format('d/m/Y H:i'),
        ];
    }
}
