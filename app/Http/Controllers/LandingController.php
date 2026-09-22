<?php

namespace App\Http\Controllers;

use App\Models\Block;
use App\Models\LandingSlider;
use App\Models\Lot;
use App\Models\Project;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class LandingController extends Controller
{
    public function __construct(private \App\Services\LandingContacts $contacts) {}

    private const RESULT_LIMIT = 24;

    public function index(): View
    {
        $landingContacts = $this->contacts->footer();
        $primaryWhatsapp = $this->contacts->whatsapp();
        $heroSlides = LandingSlider::query()
            ->select([
                'id',
                'eyebrow',
                'title',
                'description',
                'secondary_text',
                'button_text',
                'button_url',
                'image_path',
                'mobile_image_path',
                'image_alt',
            ])
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (LandingSlider $slider) {
                $accent = Str::endsWith($slider->title, 'Perú.') ? 'Perú.' : null;

                return [
                    'image_url' => $slider->imageUrl(),
                    'mobile_image_url' => $slider->mobileImageUrl(),
                    'image_alt' => $slider->image_alt,
                    'eyebrow' => $slider->eyebrow,
                    'title' => $slider->title,
                    'title_lead' => $accent ? Str::beforeLast($slider->title, ' Perú.') : $slider->title,
                    'title_accent' => $accent,
                    'text' => $slider->description,
                    'support' => $slider->secondary_text,
                    'button' => $slider->button_text,
                    'button_url' => $slider->button_url,
                    'crop' => in_array(basename($slider->image_path), ['fondolote1.jpg', 'fondolote.jpg'], true),
                ];
            })
            ->all();

        if ($heroSlides === []) {
            $heroSlides = config('landing.hero_slides', []);
        }

        $publishedProjects = Project::query()
            ->select(['id', 'company_id', 'name', 'code', 'district', 'province', 'department', 'status'])
            ->with([
                'company:id,business_name',
                'webProfile:id,project_id,show_on_web,featured_on_home,commercial_status,cover_image_path,mobile_image_path,short_description,image_alt,badge_text,sort_order',
            ])
            ->whereHas('webProfile', fn ($query) => $query->where('show_on_web', true))
            ->withCount(['lots as available_lots_count' => fn ($query) => $query
                ->where('status', 'disponible')
                ->whereHas('block', fn ($blockQuery) => $blockQuery->where('status', 1))])
            ->withMin(['lots as available_lots_min_price' => fn ($query) => $query
                ->where('status', 'disponible')
                ->whereHas('block', fn ($blockQuery) => $blockQuery->where('status', 1))], 'cash_price')
            ->withMin(['lots as available_lots_min_area' => fn ($query) => $query
                ->where('status', 'disponible')
                ->whereHas('block', fn ($blockQuery) => $blockQuery->where('status', 1))], 'area');

        $homeProjects = (clone $publishedProjects)
            ->whereHas('webProfile', fn ($query) => $query->where('featured_on_home', true))
            ->orderBy(
                \App\Models\ProjectWebProfile::select('sort_order')->whereColumn('project_id', 'projects.id')
            )
            ->orderBy('projects.id')
            ->limit(6)
            ->get();

        if ($homeProjects->isEmpty()) {
            $homeProjects = $publishedProjects
                ->orderBy(
                    \App\Models\ProjectWebProfile::select('sort_order')->whereColumn('project_id', 'projects.id')
                )
                ->orderBy('projects.id')
                ->limit(6)
                ->get();
        }

        $availabilityProjects = Project::query()
            ->select(['id', 'name', 'district'])
            ->whereHas('webProfile', fn ($query) => $query->where('show_on_web', true))
            ->orderBy(
                \App\Models\ProjectWebProfile::select('sort_order')->whereColumn('project_id', 'projects.id')
            )
            ->orderBy('projects.id')
            ->get();

        $lotSearchProjects = Project::query()
            ->select(['id', 'name', 'district'])
            ->where('status', 1)
            ->whereHas('lots', function ($query) {
                $query->whereHas('block', fn ($blockQuery) => $blockQuery->where('status', 1));
            })
            ->orderBy('name')
            ->get()
            ->map(fn (Project $project) => [
                'id' => $project->id,
                'name' => $project->name,
                'location' => $project->district,
            ]);

        $lotSearchLocations = $lotSearchProjects
            ->pluck('location')
            ->filter(fn ($location) => is_string($location) && trim($location) !== '')
            ->unique()
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        $lotSearchPriceRanges = $this->commercialPriceRanges($this->availableLotsQuery());

        return view('welcome', compact('heroSlides', 'homeProjects', 'availabilityProjects', 'lotSearchProjects', 'lotSearchLocations', 'lotSearchPriceRanges', 'landingContacts', 'primaryWhatsapp'));
    }

    public function availabilitySummary(Project $project): JsonResponse
    {
        $project->load([
            'company:id,business_name',
            'webProfile:id,project_id,show_on_web,commercial_status,plan_image_path,plan_mobile_image_path,plan_image_alt,plan_caption',
        ]);

        abort_unless($project->webProfile?->show_on_web, 404);

        $summary = Lot::query()
            ->where('project_id', $project->id)
            ->where('status', 'disponible')
            ->whereHas('project', fn ($query) => $query->where('status', 1))
            ->whereHas('block', fn ($query) => $query->where('status', 1))
            ->selectRaw('COUNT(*) as available_count')
            ->selectRaw('MIN(cash_price) as minimum_price')
            ->selectRaw('MIN(area) as minimum_area')
            ->first();

        $profile = $project->webProfile;

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
                'company' => $project->company?->business_name,
                'location' => collect([$project->district, $project->province, $project->department])
                    ->filter()
                    ->unique(fn ($value) => mb_strtolower($value))
                    ->join(' · '),
                'commercial_status' => $profile->commercial_status,
                'commercial_status_label' => $profile->commercial_status_label,
            ],
            'plan' => [
                'url' => $profile->plan_image_url,
                'mobile_url' => $profile->plan_mobile_image_url ?: $profile->plan_image_url,
                'alt' => $profile->plan_image_alt ?: 'Plano de '.$project->name,
                'caption' => $profile->plan_caption,
            ],
            'availability' => [
                'has_lots' => (int) ($summary->available_count ?? 0) > 0,
                'available' => (int) ($summary->available_count ?? 0),
                'minimum_price' => $summary->minimum_price === null ? null : (float) $summary->minimum_price,
                'minimum_area' => $summary->minimum_area === null ? null : (float) $summary->minimum_area,
            ],
        ]);
    }


    public function availableProjectLots(Request $request, Project $project): JsonResponse
    {
        $filters = $request->validate([
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:6'],
        ]);

        $project->load(['webProfile:id,project_id,show_on_web']);

        abort_unless($project->webProfile?->show_on_web, 404);

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 6);

        $query = $this->availableLotsQuery()
            ->where('project_id', $project->id)
            ->select([
                'id',
                'project_id',
                'block_id',
                'code',
                'number',
                'area',
                'unit_measure',
                'cash_price',
                'status',
            ])
            ->with('block:id,project_id,name');

        $total = (clone $query)->count();

        $lots = $query
            ->orderBy('block_id')
            ->orderBy('number')
            ->forPage($page, $perPage)
            ->get()
            ->map(fn (Lot $lot) => $this->publicLotPayload($lot, $project))
            ->values();

        return response()->json([
            'success' => true,
            'project' => [
                'id' => $project->id,
                'name' => $project->name,
            ],
            'lots' => $lots,
            'pagination' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'shown' => $lots->count(),
                'has_more' => ($page * $perPage) < $total,
            ],
        ]);
    }

    public function projectLots(Request $request, Project $project): View
    {
        $primaryWhatsapp = $this->contacts->whatsapp();
        $project->load([
            'company:id,business_name',
            'webProfile:id,project_id,show_on_web,commercial_status,cover_image_path,mobile_image_path,short_description,image_alt,badge_text,plan_image_path,plan_mobile_image_path,plan_image_alt,plan_caption',
        ]);

        abort_unless($project->webProfile?->show_on_web, 404);

        $filters = $request->validate([
            'block_id' => [
                'nullable',
                'integer',
                Rule::exists('blocks', 'id')->where(fn ($query) => $query
                    ->where('project_id', $project->id)
                    ->where('status', 1)),
            ],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($request->filled('min_price'), ['gte:min_price']),
            ],
            'min_area' => ['nullable', 'numeric', 'min:0'],
            'max_area' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($request->filled('min_area'), ['gte:min_area']),
            ],
        ]);

        $baseQuery = $this->availableLotsQuery()->where('project_id', $project->id);
        $totalAvailable = (clone $baseQuery)->count();
        $minimumPrice = (clone $baseQuery)->min('cash_price');
        $minimumArea = (clone $baseQuery)->min('area');

        $query = (clone $baseQuery)
            ->select([
                'id',
                'project_id',
                'block_id',
                'code',
                'number',
                'area',
                'unit_measure',
                'cash_price',
                'status',
            ])
            ->with('block:id,project_id,name');

        if (! empty($filters['block_id'])) {
            $query->where('block_id', $filters['block_id']);
        }
        if (isset($filters['min_price'])) {
            $query->where('cash_price', '>=', $filters['min_price']);
        }
        if (isset($filters['max_price'])) {
            $query->where('cash_price', '<=', $filters['max_price']);
        }
        if (isset($filters['min_area'])) {
            $query->where('area', '>=', $filters['min_area']);
        }
        if (isset($filters['max_area'])) {
            $query->where('area', '<=', $filters['max_area']);
        }

        $lots = $query
            ->orderBy('block_id')
            ->orderBy('number')
            ->paginate(12)
            ->withQueryString();

        $lots->setCollection(
            $lots->getCollection()->map(fn (Lot $lot) => $this->publicLotPayload($lot, $project))
        );

        $blocks = Block::query()
            ->select(['id', 'name'])
            ->where('project_id', $project->id)
            ->where('status', 1)
            ->whereHas('lots', fn ($lotQuery) => $lotQuery->where('status', 'disponible'))
            ->orderBy('name')
            ->get();

        return view('project-lots', compact(
            'primaryWhatsapp',
            'project',
            'lots',
            'blocks',
            'filters',
            'totalAvailable',
            'minimumPrice',
            'minimumArea'
        ));
    }

    public function searchLots(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'location' => ['nullable', 'string', 'max:150'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
            'min_price' => ['nullable', 'numeric', 'min:0'],
            'max_price' => [
                'nullable',
                'numeric',
                'min:0',
                Rule::when($request->filled('min_price'), ['gte:min_price']),
            ],
        ], [
            'project_id.integer' => 'El proyecto seleccionado no es válido.',
            'project_id.exists' => 'El proyecto seleccionado no existe.',
            'min_price.numeric' => 'El presupuesto mínimo no es válido.',
            'max_price.numeric' => 'El presupuesto máximo no es válido.',
            'max_price.gte' => 'El presupuesto máximo no puede ser menor que el mínimo.',
        ]);

        $query = $this->applyAvailabilityFilters($this->availableLotsQuery(), $filters)
            ->select([
                'id',
                'project_id',
                'block_id',
                'code',
                'number',
                'area',
                'unit_measure',
                'cash_price',
                'status',
            ])
            ->with([
                'project:id,name,district,province,department',
                'block:id,project_id,name',
            ]);

        if (isset($filters['min_price'])) {
            $query->where('cash_price', '>=', $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $query->where('cash_price', '<=', $filters['max_price']);
        }

        $total = (clone $query)->count();
        $lots = $query
            ->orderBy('project_id')
            ->orderBy('block_id')
            ->orderBy('number')
            ->limit(self::RESULT_LIMIT)
            ->get()
            ->map(fn (Lot $lot) => [
                'project' => $lot->project->name,
                'location' => $lot->project->district ?: ($lot->project->province ?: $lot->project->department),
                'block' => $lot->block->name,
                'lot_number' => $lot->number,
                'code' => $lot->code,
                'area' => $lot->area === null ? null : (float) $lot->area,
                'unit_measure' => $lot->unit_measure,
                'cash_price' => (float) $lot->cash_price,
                'status' => $lot->status,
            ])
            ->values();

        return response()->json([
            'success' => true,
            'data' => $lots,
            'meta' => [
                'total' => $total,
                'shown' => $lots->count(),
                'limit' => self::RESULT_LIMIT,
            ],
        ]);
    }

    public function priceRanges(Request $request): JsonResponse
    {
        $filters = $request->validate([
            'location' => ['nullable', 'string', 'max:150'],
            'project_id' => ['nullable', 'integer', Rule::exists('projects', 'id')],
        ]);

        $ranges = $this->commercialPriceRanges(
            $this->applyAvailabilityFilters($this->availableLotsQuery(), $filters)
        );

        return response()->json([
            'success' => true,
            'data' => $ranges,
        ]);
    }

    private function publicLotPayload(Lot $lot, Project $project): array
    {
        $area = $lot->area === null ? null : (float) $lot->area;
        $price = $lot->cash_price === null ? null : (float) $lot->cash_price;
        $unit = $lot->unit_measure === 'm2' ? 'm²' : ($lot->unit_measure ?: 'm²');
        $details = [];

        if ($area !== null) {
            $areaText = rtrim(rtrim(number_format($area, 2, '.', ','), '0'), '.');
            $details[] = 'de '.$areaText.' '.$unit;
        }

        if ($price !== null) {
            $details[] = 'con precio S/ '.number_format($price, 2, '.', ',');
        }

        $blockName = trim((string) $lot->block->name);
        $blockMessage = preg_match('/^mz\s+/i', $blockName)
            ? preg_replace('/^mz\s+/i', 'manzana ', $blockName)
            : (preg_match('/^manzana\s+/i', $blockName) ? $blockName : 'manzana '.$blockName);

        $message = 'Hola, estoy interesado en el lote '.$lot->number
            .' de la '.$blockMessage
            .' del proyecto '.$project->name;

        if ($details !== []) {
            $message .= ', '.implode(' y ', $details);
        }

        $message .= '. ¿Podrían brindarme más información?';

        $whatsapp = $this->contacts->whatsapp();

        return [
            'id' => $lot->id,
            'code' => $lot->code,
            'block' => $lot->block->name,
            'lot_number' => $lot->number,
            'area' => $area,
            'unit_measure' => $lot->unit_measure,
            'cash_price' => $price,
            'status' => $lot->status,
            'whatsapp_url' => $whatsapp
                ? 'https://wa.me/'.$whatsapp.'?text='.rawurlencode($message)
                : null,
        ];
    }

    private function availableLotsQuery(): Builder
    {
        return Lot::query()
            ->where('status', 'disponible')
            ->whereHas('project', fn ($query) => $query->where('status', 1))
            ->whereHas('block', fn ($query) => $query->where('status', 1));
    }

    private function applyAvailabilityFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['location'])) {
            $query->whereHas('project', fn ($projectQuery) => $projectQuery->where('district', $filters['location']));
        }

        if (! empty($filters['project_id'])) {
            $query->where('project_id', $filters['project_id']);
        }

        return $query;
    }

    private function commercialPriceRanges(Builder $query): array
    {
        $prices = $query
            ->selectRaw('MIN(cash_price) as minimum, MAX(cash_price) as maximum')
            ->first();

        if ($prices?->minimum === null || $prices?->maximum === null) {
            return [];
        }

        $minimum = (float) $prices->minimum;
        $maximum = (float) $prices->maximum;
        $span = $maximum - $minimum;
        $preferredStep = match (true) {
            $span <= 40000 => 10000,
            $span <= 100000 => 20000,
            $span <= 200000 => 25000,
            default => 50000,
        };
        $steps = [10000, 20000, 25000, 50000, 100000, 200000, 250000, 500000];
        $step = $preferredStep;
        $bestScore = PHP_FLOAT_MAX;

        foreach ($steps as $candidate) {
            $bucketCount = $this->priceBucketCount($minimum, $maximum, $candidate);
            $outsideTarget = $bucketCount < 3 ? 3 - $bucketCount : max(0, $bucketCount - 5);
            $score = ($outsideTarget * 100) + abs($bucketCount - 4) + (abs($candidate - $preferredStep) / max(1, $preferredStep));

            if ($score < $bestScore) {
                $bestScore = $score;
                $step = $candidate;
            }
        }

        while ($this->priceBucketCount($minimum, $maximum, $step) > 5) {
            $step *= 2;
        }

        $boundaries = [];
        for ($boundary = (floor($minimum / $step) + 1) * $step; $boundary < $maximum; $boundary += $step) {
            $boundaries[] = (float) $boundary;
        }

        if ($boundaries === []) {
            $ceiling = max($step, ceil($maximum / $step) * $step);

            return [[
                'label' => 'Hasta S/ '.$this->formatCommercialAmount($ceiling),
                'min_price' => null,
                'max_price' => $ceiling,
            ]];
        }

        $ranges = [[
            'label' => 'Hasta S/ '.$this->formatCommercialAmount($boundaries[0]),
            'min_price' => null,
            'max_price' => $boundaries[0],
        ]];

        foreach (array_slice($boundaries, 1) as $boundary) {
            $previous = $boundaries[count($ranges) - 1];
            $ranges[] = [
                'label' => 'S/ '.$this->formatCommercialAmount($previous + 1).' - S/ '.$this->formatCommercialAmount($boundary),
                'min_price' => $previous + 0.01,
                'max_price' => $boundary,
            ];
        }

        $lastBoundary = end($boundaries);
        $ranges[] = [
            'label' => 'Más de S/ '.$this->formatCommercialAmount($lastBoundary),
            'min_price' => $lastBoundary + 0.01,
            'max_price' => null,
        ];

        return $ranges;
    }

    private function priceBucketCount(float $minimum, float $maximum, float $step): int
    {
        if ($minimum === $maximum) {
            return 1;
        }

        $boundaries = 0;
        for ($boundary = (floor($minimum / $step) + 1) * $step; $boundary < $maximum; $boundary += $step) {
            $boundaries++;
        }

        return $boundaries + 1;
    }

    private function formatCommercialAmount(float $amount): string
    {
        return number_format($amount, 0, '.', ',');
    }
}
