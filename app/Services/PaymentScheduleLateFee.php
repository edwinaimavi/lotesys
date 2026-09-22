<?php

namespace App\Services;

use App\Models\PaymentSchedule;
use App\Models\Holiday;
use Carbon\Carbon;

class PaymentScheduleLateFee
{
    public function calculate(PaymentSchedule $schedule): float
    {
        $sale = $schedule->sale;

        if (!$sale || !$sale->lateFeeSetting) {
            return 0;
        }

        $setting = $sale->lateFeeSetting;
        $today = Carbon::today();
        $dueDate = $schedule->getEffectiveDueDate();

        if ($today->lte($dueDate)) {
            return 0;
        }

        $daysLate = 0;
        $current = $dueDate->copy();

        while ($current->lt($today)) {

            $current->addDay();

            $isSunday = $current->dayOfWeek === Carbon::SUNDAY;

            $isHoliday = Holiday::where(
                'date',
                $current->format('Y-m-d')
            )
                ->where('status', 'activo')
                ->exists();

            if (!$setting->apply_sundays && $isSunday) {
                continue;
            }

            if (!$setting->apply_holidays && $isHoliday) {
                continue;
            }

            $daysLate++;
        }

        $daysLate -= (int) $setting->grace_days;

        if ($daysLate < 0) {
            $daysLate = 0;
        }

        $lateFee = $daysLate * (float) $setting->daily_late_fee;

        if (
            $setting->max_late_fee &&
            $lateFee > $setting->max_late_fee
        ) {
            $lateFee = $setting->max_late_fee;
        }

        return round($lateFee, 2);
    }

}
