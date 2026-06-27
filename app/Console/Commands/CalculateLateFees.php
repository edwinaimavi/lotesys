<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use Carbon\Carbon;

use App\Models\Sale;
use App\Models\Holiday;
use App\Models\LateFeeHistory;
use App\Models\PaymentSchedule;

class CalculateLateFees extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'latefees:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calcula mora automática de cuotas vencidas';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('===================================');
        $this->info('INICIANDO CÁLCULO DE MORA');
        $this->info('===================================');

        // =====================================================
        // OBTENER CUOTAS PENDIENTES
        // =====================================================

        $schedules = PaymentSchedule::with(
            'sale.lateFeeSetting'
        )
            ->whereIn('status', [
                'pendiente',
                'parcial',
                'vencido'
            ])
            ->get();

        foreach ($schedules as $schedule) {

            $sale = $schedule->sale;

            // ================================================
            // VALIDAR CONFIGURACIÓN MORA
            // ================================================

            if (
                !$sale ||
                !$sale->lateFeeSetting
            ) {
                continue;
            }

            $setting = $sale->lateFeeSetting;

            $today = Carbon::today();

            $dueDate = $schedule->getEffectiveDueDate();

            // ================================================
            // SI AÚN NO VENCE
            // ================================================

            if ($today->lte($dueDate)) {

                $schedule->late_fee = 0;

                $schedule->save();

                continue;
            }

            // ================================================
            // CONTAR DÍAS MORA
            // ================================================

            $daysLate = 0;

            $current = $dueDate->copy();

            while ($current->lt($today)) {

                $current->addDay();

                $isSunday =
                    $current->dayOfWeek === Carbon::SUNDAY;

                $isHoliday = Holiday::where(
                    'date',
                    $current->format('Y-m-d')
                )
                    ->where('status', 'activo')
                    ->exists();

                // ============================================
                // EXCLUIR DOMINGOS
                // ============================================

                if (
                    !$setting->apply_sundays &&
                    $isSunday
                ) {
                    continue;
                }

                // ============================================
                // EXCLUIR FERIADOS
                // ============================================

                if (
                    !$setting->apply_holidays &&
                    $isHoliday
                ) {
                    continue;
                }

                $daysLate++;
            }

            // ================================================
            // RESTAR DÍAS GRACIA
            // ================================================

            $daysLate -= $setting->grace_days;

            if ($daysLate < 0) {

                $daysLate = 0;
            }

            // ================================================
            // CALCULAR MORA
            // ================================================

            $lateFee =
                $daysLate *
                $setting->daily_late_fee;

            // ================================================
            // VALIDAR MORA MÁXIMA
            // ================================================

            if (
                $setting->max_late_fee &&
                $lateFee > $setting->max_late_fee
            ) {

                $lateFee =
                    $setting->max_late_fee;
            }

            // ================================================
            // ACTUALIZAR CUOTA
            // ================================================

            $schedule->late_fee = round(
                $lateFee,
                2
            );

            $schedule->save();

            // ================================================
            // EVITAR DUPLICADOS EN HISTORIAL
            // ================================================

            $exists = LateFeeHistory::where(
                'payment_schedule_id',
                $schedule->id
            )
                ->whereDate(
                    'calculated_at',
                    $today
                )
                ->exists();

            if (!$exists) {

                LateFeeHistory::create([

                    'payment_schedule_id' => $schedule->id,

                    'late_fee_amount' => round(
                        $lateFee,
                        2
                    ),

                    'late_days' => $daysLate,

                    'daily_late_fee_applied' =>
                    $setting->daily_late_fee,

                    'calculation_type' =>
                    'automatico',

                    'calculated_at' => now(),

                    'status' => 'activo',

                    'created_by' => 1,
                ]);
            }

            $this->info(
                'Cuota #' .
                    $schedule->id .
                    ' -> Mora: S/ ' .
                    $lateFee
            );
        }

        $this->info('===================================');
        $this->info('FINALIZADO');
        $this->info('===================================');

        return Command::SUCCESS;
    }
}
