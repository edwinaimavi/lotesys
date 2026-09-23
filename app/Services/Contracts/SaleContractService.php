<?php

namespace App\Services\Contracts;

use App\Models\Sale;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use PhpOffice\PhpWord\Settings;
use PhpOffice\PhpWord\TemplateProcessor;
use Throwable;

class SaleContractService
{
    public function __construct(private AmountToWordsService $words) {}

    public function data(Sale $sale): array
    {
        $sale->load(['customer', 'lot.project.company', 'lot.block', 'saleLots.lot.project.company', 'saleLots.lot.block']);
        $lots = $sale->saleLots->sortByDesc('is_primary')->pluck('lot')->filter()->values();
        if ($lots->isEmpty() && $sale->lot) {
            $lots = collect([$sale->lot]);
        }
        $lot = $lots->first();
        $project = $lot?->project;
        $company = $project?->company;
        $template = collect(config('contracts.templates'))->first(fn ($item) => $company && (string) $company->ruc === $item['ruc']);
        if (!$template) {
            $this->fail('No existe una plantilla de contrato configurada para esta empresa.');
        }
        if ($lots->contains(fn ($item) => (string) $item->project?->company?->ruc !== $template['ruc'])) {
            $this->fail('Los lotes pertenecen a empresas diferentes. Revise la venta antes de generar el contrato.');
        }
        $path = storage_path($template['path']);
        if (!is_file($path)) {
            $this->fail('No se encuentra el archivo de la plantilla de contrato configurada.');
        }
        $schedules = $sale->paymentSchedules()->where('schedule_type', 'cuota')->orderBy('installment_number')->orderBy('id')->get();
        $limit = min($template['max_installments'], $template['max_bills'] ?? $template['max_installments']);
        if ($schedules->count() > $limit) {
            $this->fail($template['installment_limit_message'] ?? 'La plantilla de '.$template['name'].' admite hasta '.$limit.' cuotas. Revise el contrato o la plantilla.');
        }
        $processor = new TemplateProcessor($path);
        $variables = array_values(array_unique($processor->getVariables()));
        $fields = config('contracts.fields');
        if (!($template['independent_bills'] ?? true)) {
            $fields = array_filter($fields, fn ($key) => !str_starts_with($key, 'bill_'), ARRAY_FILTER_USE_KEY);
        }
        foreach ($template['aliases'] ?? [] as $alias => $source) {
            $fields[$alias] = $fields[$source];
        }
        $context = compact('sale', 'lot', 'project', 'company', 'schedules') + ['customer' => $sale->customer];
        $values = [];
        foreach ($fields as $key => $field) {
            $value = $field['source'] ? data_get($context, $field['source']) : null;
            $values[$key] = $value === null ? '' : (string) $value;
        }
        foreach ($variables as $variable) {
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $variable)) {
                $this->fail('La plantilla contiene un nombre de variable no admitido.');
            }
            if (!isset($fields[$variable])) {
                $fields[$variable] = ['label' => str_replace('_', ' ', $variable), 'group' => 'Otros datos de la plantilla', 'source' => null, 'type' => 'text'];
                $values[$variable] = '';
            }
        }
        // All sale_lots remain individually editable; sales.lot_id is only a legacy fallback.
        foreach ($lots as $index => $item) {
            foreach (config('contracts.lot_fields') as $name => $label) {
                $key = 'lot_'.sprintf('%02d', $index + 1).'_'.$name;
                $fields[$key] = ['label' => $label, 'group' => 'Lote '.($index + 1), 'source' => 'sale_lots.'.($index + 1).'.'.$name, 'type' => 'text'];
                $values[$key] = (string) (data_get($item, config('contracts.lot_sources.'.$name)) ?? '');
            }
        }
        $values = array_replace($values, $template['defaults']);
        $values = $this->derived($values, [], $lots->count(), $schedules->count(), $template['aliases'] ?? []);
        $genderDefaults = config('contracts.gender_defaults');
        foreach ($genderDefaults as &$defaults) {
            foreach ($template['aliases'] ?? [] as $alias => $source) {
                if (isset($defaults[$source])) $defaults[$alias] = $defaults[$source];
            }
        }
        unset($defaults);
        foreach ($fields as $key => &$field) {
            $field['in_template'] = in_array($key, $variables, true);
            $field['value'] = $values[$key] ?? '';
        }
        unset($field);

        return [
            'sale_id' => $sale->id, 'sale_code' => $sale->sale_code, 'values' => $values, 'fields' => $fields,
            'template' => $template, 'variables' => $variables, 'lots_count' => $lots->count(),
            'schedule_count' => $schedules->count(), 'gender_defaults' => $genderDefaults,
        ];
    }

    public function validateValues(array $input, array $data): array
    {
        $rules = ['values' => ['required', 'array:'.implode(',', array_keys($data['fields']))]];
        foreach ($data['fields'] as $key => $field) {
            $rule = ['present', 'nullable', 'string', 'max:2000', 'not_regex:/\$\{/', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F]/'];
            if ($field['type'] === 'money') {
                $rule = ['present', 'nullable', 'numeric', 'min:0', 'max:999999999.99', 'regex:/^\d+(\.\d{1,2})?$/'];
            } elseif ($field['type'] === 'date') {
                $rule[] = 'date_format:Y-m-d';
            } elseif ($key === 'gender') {
                $rule[] = 'in:masculino,femenino,no_especificado';
            }
            $rules['values.'.$key] = $rule;
        }
        $validated = Validator::make($input, $rules, [
            'present' => 'Este campo debe enviarse, aunque esté vacío.', 'required' => 'Debe completar este campo.',
            'array' => 'Los campos del contrato no son válidos. Vuelva a abrir el formulario.',
            'string' => 'Ingrese un texto válido.', 'max' => 'El valor supera el máximo permitido (:max).',
            'min' => 'El importe no puede ser negativo.', 'numeric' => 'Ingrese un importe válido.',
            'regex' => 'Ingrese un importe sin separadores de miles y con hasta dos decimales.',
            'not_regex' => 'El texto contiene caracteres o marcadores no permitidos.',
            'date_format' => 'Ingrese una fecha válida.', 'in' => 'Seleccione un género válido.',
        ])->validate();
        $values = array_map(fn ($value) => $value === null ? '' : (string) $value, $validated['values']);

        return $this->derived($values, $data['values'], $data['lots_count'], $data['schedule_count'], $data['template']['aliases'] ?? []);
    }

    // Update calculated defaults only when the user has not overridden that field.
    private function derived(array $v, array $original, int $lotCount, int $scheduleCount, array $aliases = []): array
    {
        $set = static function ($key, $value) use (&$v, $original): void {
            if (!$original || ($v[$key] ?? '') === ($original[$key] ?? '')) {
                $v[$key] = $value;
            }
        };
        foreach (config('contracts.gender_defaults.'.$v['gender'], []) as $key => $value) {
            $set($key, $value);
        }
        if ($lotCount) {
            foreach (config('contracts.lot_fields') as $key => $label) {
                // Both views of the principal lot stay coherent unless explicitly overridden.
                if ($original && ($v[$key] ?? '') !== ($original[$key] ?? '')
                    && ($v['lot_01_'.$key] ?? '') === ($original['lot_01_'.$key] ?? '')) {
                    $v['lot_01_'.$key] = $v[$key];
                }
                $set($key, $v['lot_01_'.$key]);
            }
        }
        foreach (['name' => 'full_name', 'document_type' => 'document_type', 'document_number' => 'document_number'] as $suffix => $source) {
            $set('buyer_signature_'.$suffix, $v[$source]);
        }
        foreach (['lot_price', 'initial_payment', 'balance_finance'] as $key) {
            $set($key.'_words', $v[$key] === '' ? '' : $this->words->convert($v[$key]));
        }
        $date = $v['sale_date'] ? Carbon::parse($v['sale_date']) : null;
        foreach (['day' => 'd', 'month' => 'm', 'year' => 'Y'] as $suffix => $format) {
            $set('sale_date_'.$suffix, $date?->format($format) ?? '');
        }
        $set('sale_date_month_name', $date?->locale('es')->translatedFormat('F') ?? '');
        for ($i = 1; $i <= config('contracts.schedule_slots'); $i++) {
            $n = sprintf('%02d', $i);
            $s = 'schedule_'.$n.'_';
            $b = 'bill_'.$n.'_';
            if ($i > $scheduleCount) {
                // Unused slots cannot invent contractual installments.
                foreach (array_keys($v) as $key) {
                    if (str_starts_with($key, $s) || str_starts_with($key, $b)) {
                        $v[$key] = '';
                    }
                }
                continue;
            }
            $set($s.'installment_amount_words', $v[$s.'installment_amount'] === '' ? '' : $this->words->convert($v[$s.'installment_amount']));
            if (array_key_exists($b.'amount', $v)) {
                $set($b.'amount', $v[$s.'installment_amount']);
                $set($b.'amount_words', $v[$b.'amount'] === '' ? '' : $this->words->convert($v[$b.'amount']));
                $set($b.'number', $v[$s.'installment_number'] === '' ? '' : str_pad($v[$s.'installment_number'], 2, '0', STR_PAD_LEFT));
            }
        }
        $set('last_installment_due_date', $scheduleCount ? $v['schedule_'.sprintf('%02d', $scheduleCount).'_due_date'] : '');
        // The initial is the first numbered item of clause three in this template.
        $set('installment_clause_numerals', $scheduleCount ? implode(', ', range(2, $scheduleCount + 1)) : '');
        $summary = [];
        for ($i = 1; $i <= $lotCount; $i++) {
            $prefix = 'lot_'.sprintf('%02d', $i).'_';
            $summary[] = 'Mz. '.$v[$prefix.'block_name'].' - Lote '.$v[$prefix.'lot_number'].' - '.$v[$prefix.'lot_area'].' '.$v[$prefix.'lot_unit_measure'];
        }
        $set('lots_summary', implode("\n", $summary));
        foreach ($aliases as $alias => $source) {
            $set($alias, $v[$source] ?? '');
        }

        return $v;
    }

    public function generate(Sale $sale, array $input): array
    {
        // Resolve from persisted company every time, never from posted RUC or sale_id.
        $data = $this->data($sale);
        $values = $this->validateValues($input, $data);
        Settings::setOutputEscapingEnabled(true);
        $processor = new TemplateProcessor(storage_path($data['template']['path']));
        foreach ($data['variables'] as $key) {
            $value = $values[$key] ?? '';
            $type = $data['fields'][$key]['type'];
            if ($value !== '' && $type === 'money') {
                $value = number_format((float) $value, 2, '.', ',');
            } elseif ($value !== '' && $type === 'date') {
                $value = Carbon::parse($value)->format('d/m/Y');
            }
            // Preserve the legacy template's literal currency suffix.
            if ($key === 'lot_price_words' && !($data['template']['price_words_include_currency'] ?? false)) {
                $value = preg_replace('/\s+Soles$/u', '', $value);
            }
            $processor->setValue($key, $value);
        }
        $directory = storage_path('app/private/contracts/tmp');
        if (!is_dir($directory) && !mkdir($directory, 0700, true) && !is_dir($directory)) {
            $this->fail('No se pudo crear el directorio temporal del contrato.');
        }
        $path = tempnam($directory, 'contract_');
        if ($path === false) {
            $this->fail('No se pudo crear el archivo temporal del contrato.');
        }
        try {
            $processor->saveAs($path);
        } catch (Throwable $e) {
            @unlink($path);
            throw $e;
        }
        $safe = static fn ($value) => substr(preg_replace('/[^A-Za-z0-9_-]/', '_', $value), 0, 80);
        $filename = 'Contrato_'.$safe($values['sale_code']).'_'.$safe($values['document_number']).'_'.$data['template']['filename_suffix'].'.docx';

        return compact('path', 'filename');
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['contract' => $message]);
    }
}
