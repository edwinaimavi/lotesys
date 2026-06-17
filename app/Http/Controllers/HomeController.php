<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\Payment;
use App\Models\Lot;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    public function index()
    {
        $totalSales = Sale::count();

        $activeSales = Sale::where('status', 'activo')->count();

        $rescindedSales = Sale::where('status', 'rescindido')->count();

        $totalCollected = Payment::where('status', 'activo')
            ->sum('amount');

        $availableLots = Lot::where('status', 'disponible')
            ->count();

        $soldLots = Lot::where('status', 'vendido')
            ->count();

        $reservedLots = Lot::where('status', 'reservado')
            ->count();

        /*
        |--------------------------------------------------------------------------
        | VENTAS POR MES
        |--------------------------------------------------------------------------
        */

        $salesByMonth = [];

        for ($i = 1; $i <= 12; $i++) {

            $salesByMonth[] = Sale::whereMonth('sale_date', $i)
                ->count();
        }

        /*
        |--------------------------------------------------------------------------
        | PAGOS POR MES
        |--------------------------------------------------------------------------
        */

        $paymentsByMonth = [];

        for ($i = 1; $i <= 12; $i++) {

            $paymentsByMonth[] = Payment::where('status', 'activo')
                ->whereMonth('payment_date', $i)
                ->sum('amount');
        }

        return view('home', compact(
            'totalSales',
            'activeSales',
            'rescindedSales',
            'totalCollected',
            'availableLots',
            'soldLots',
            'reservedLots',
            'salesByMonth',
            'paymentsByMonth'
        ));
    }
}
