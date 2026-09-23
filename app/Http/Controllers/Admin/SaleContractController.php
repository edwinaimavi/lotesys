<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Sale;
use App\Services\Contracts\SaleContractService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Throwable;

class SaleContractController extends Controller
{
    public function data(Sale $sale, SaleContractService $contracts)
    {
        $data = $contracts->data($sale);
        unset($data['template']['path']);

        return response()->json($data)->header('Cache-Control', 'no-store, private');
    }

    public function generate(Request $request, Sale $sale, SaleContractService $contracts)
    {
        try {
            $file = $contracts->generate($sale, $request->all());

            return response()->download($file['path'], $file['filename'], [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'Cache-Control' => 'no-store, private',
            ])->deleteFileAfterSend(true);
        } catch (ValidationException $e) {
            throw $e;
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => 'No se pudo generar el contrato. Intente nuevamente o consulte al administrador.'], 500);
        }
    }
}
