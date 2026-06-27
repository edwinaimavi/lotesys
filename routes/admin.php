<?php

use App\Http\Controllers\Admin\AmortizationController;
use App\Http\Controllers\Admin\BankController;
use App\Http\Controllers\Admin\BlockController;

use App\Http\Controllers\Admin\CompanyController;
use App\Http\Controllers\Admin\CustomerController;

use App\Http\Controllers\Admin\HolidayController;

use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\LateFeeSettingController;

use App\Http\Controllers\Admin\LotController;
use App\Http\Controllers\Admin\PaymentController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\SaleController;
use App\Http\Controllers\Admin\RescissionController;
use Illuminate\Support\Facades\Route;



//Rutas para la gestión de usuarios en el panel de administración|
Route::get('users/list', [UserController::class, 'list'])->name('users.list');
Route::resource('users', UserController::class)->except(['create', 'show']);

Route::get('roles/list', [RoleController::class, 'list'])->name('roles.list');
Route::get('roles/{role}/permissions', [RoleController::class, 'getPermissions'])->name('roles.permissions');
Route::resource('roles', RoleController::class)->except(['create', 'show']);


//RUTAS PARA EMPRESAS
Route::get('companies/list', [CompanyController::class, 'list'])->name('companies.list');
Route::resource('companies', CompanyController::class)->except(['create', 'show']);


//RUTAS PARA PROYECTOS
Route::get('projects/list', [ProjectController::class, 'list'])->name('projects.list');
Route::get(
    'projects-generate-code',
    [ProjectController::class, 'generateCode']
)->name('projects.generate.code');
Route::resource('projects', ProjectController::class)->except(['create', 'show']);

//RUTAS PARA MANZANAS
Route::get('blocks/list', [BlockController::class, 'list'])->name('blocks.list');
Route::post(
    'blocks/generate-lots',
    [BlockController::class, 'generateLots']
)->name('blocks.generate.lots');
Route::resource('blocks', BlockController::class)->except(['blocks', 'show']);

//RUTAS PARA LOTES
Route::get('lots/list', [LotController::class, 'list'])->name('lots.list');
Route::get(
    'projects/{project}/blocks',
    [LotController::class, 'getBlocks']
)->name('projects.blocks');
Route::get(
    'lots/generate-code',
    [LotController::class, 'generateCode']
)->name('lots.generate.code');

Route::get(
    '/companies/{company}/projects',
    [LotController::class, 'getProjectsByCompany']
)->name('projects.by.company');

Route::resource('lots', LotController::class)->except(['blocks', 'show']);


//RUTAS PARA CLIENTES 
Route::get('customers/list', [CustomerController::class, 'list'])->name('customers.list');
Route::get('customers/consultar/{numero}', [CustomerController::class, 'consultarDocumento'])
    ->name('customers.consultar');

/* Route::get('clients/document/{dniruc}', [ClientController::class, 'consultarDniRuc'])
    ->name('clients.consultarDniRuc'); */

Route::resource('customers', CustomerController::class)->except(['create', 'show']);


// RUTAS PARA VENTAS
Route::get('sales/list', [SaleController::class, 'list'])
    ->name('sales.list');

Route::get(
    'sales/{sale}/payment-schedule',
    [SaleController::class, 'paymentSchedule']
)->name('sales.payment.schedule');

Route::get(
    'sales/generate-code',
    [SaleController::class, 'generateCode']
)->name('sales.generate.code');



Route::resource('sales', SaleController::class)
    ->except(['create', 'show']);


//RUTAS PARA PAGOS
Route::get('payments/list', [PaymentController::class, 'list'])->name('payments.list');

Route::get(
    'payments/schedules/{sale}',
    [PaymentController::class, 'getSchedules']
)->name('payments.schedules');

Route::post(
    'payments/{payment}/cancel',
    [PaymentController::class, 'cancel']
)->name('payments.cancel');

Route::get(
    '/payments/apisperu/companies',
    [PaymentController::class, 'getApisPeruCompanies']
)->name('payments.apisperu.companies');

Route::get(
    'admin/invoices/customer-data/{sale}',
    [InvoiceController::class, 'getCustomerData']
)->name('invoices.customer-data');

Route::get(
    'admin/invoices/next-number',
    [InvoiceController::class, 'getNextNumber']
)->name('invoices.next-number');

Route::get(
    'admin/invoices/payment-description/{payment}',
    [InvoiceController::class, 'getPaymentDescription']
)->name('invoices.payment-description');

Route::get(
    'admin/invoices/companies',
    [InvoiceController::class, 'getCompanies']
)->name('invoices.companies');

Route::resource('payments', PaymentController::class)->except(['create', 'show']);



//RUTAS PARA AMORTIZACION 
Route::get('amortizations/list', [AmortizationController::class, 'list'])->name('amortizations.list');
Route::resource('amortizations', AmortizationController::class)->except(['create']);

//RUTAS PARA BANCOS
Route::get('banks/list', [BankController::class, 'list'])->name('banks.list');
Route::resource('banks', BankController::class)->except(['create']);

//RUTAS PARA MORAS
Route::get('lateFeeSettings/list', [LateFeeSettingController::class, 'list'])->name('lateFeeSettings.list');
Route::resource('lateFeeSettings', LateFeeSettingController::class)->except(['create']);

//RUTAS PARA FERIADOS
Route::get('holidays/list', [HolidayController::class, 'list'])->name('holidays.list');
Route::resource('holidays', HolidayController::class)->except(['create']);

//rutas para comprobantes


Route::get('invoices/list', [InvoiceController::class, 'list'])->name('invoices.list');

Route::post(
    'invoices/{payment}/generate',
    [InvoiceController::class, 'generate']
)->name('invoices.generate');

/* Route::post(
    'invoices/{invoice}/send-sunat',
    [InvoiceController::class, 'sendToSunat']
)->name('invoices.sendSunat'); */

Route::get(
    'invoices/{invoice}/pdf',
    [InvoiceController::class, 'downloadPdf']
)->name('invoices.downloadPdf');

Route::get(
    'invoices/{invoice}/xml',
    [InvoiceController::class, 'downloadXml']
)->name('invoices.downloadXml');

/* Route::post(
    'admin/invoices/store-temp',
    [InvoiceController::class, 'store']
)->name('invoices.store-temp'); */


Route::post(
    'admin/invoices/generate',
    [InvoiceController::class, 'generate']
)->name('invoices.generate');
Route::resource('invoices', InvoiceController::class)->except(['create']);

Route::get(
    'invoices/{invoice}/ticket',
    [InvoiceController::class, 'ticket']
)->name('invoices.ticket');



//rutas para reportes
//RUTAS PARA REPORTES
Route::get(
    'reports/sales',
    [ReportController::class, 'sales']
)->name('reports.sales');

Route::get(
    'reports/sales/list',
    [ReportController::class, 'salesList']
)->name('reports.sales.list');


// FILTROS REPORTE DE VENTAS
Route::get(
    'reports/companies',
    [ReportController::class, 'getCompanies']
)->name('reports.companies');

Route::get(
    'reports/projects/{company}',
    [ReportController::class, 'getProjects']
)->name('reports.projects');

Route::get(
    'reports/blocks/{project}',
    [ReportController::class, 'getBlocks']
)->name('reports.blocks');

Route::get(
    'reports/sales/export/excel',
    [ReportController::class, 'exportSalesExcel']
)->name('reports.sales.excel');

Route::get(
    'reports/sales/export/pdf',
    [ReportController::class, 'exportSalesPdf']
)->name('reports.sales.pdf');

Route::get(
    'reports/payments',
    [ReportController::class, 'payments']
)->name('reports.payments');

/*
|--------------------------------------------------------------------------
| REPORTE DE PAGOS
|--------------------------------------------------------------------------
*/

Route::get(
    'reports/payments/list',
    [ReportController::class, 'paymentsList']
)->name('reports.payments.list');

Route::get(
    'reports/payments/export/excel',
    [
        ReportController::class,
        'exportPaymentsExcel'
    ]
)->name('reports.payments.excel');

Route::get(
    'reports/payments/export/pdf',
    [
        ReportController::class,
        'exportPaymentsPdf'
    ]
)->name('reports.payments.pdf');


Route::get(
    'reports/invoices',
    [ReportController::class, 'invoices']
)->name('reports.invoices');

Route::get(
    'reports/collections',
    [ReportController::class, 'collections']
)->name('reports.collections');

Route::get(
    'reports/overdue',
    [ReportController::class, 'overdue']
)->name('reports.overdue');

Route::resource(
    'reports',
    ReportController::class
)->only(['index']);

//rutas para recisions 
Route::get('rescissions/list', [RescissionController::class, 'list'])->name('rescissions.list');

Route::get(
    'rescissions/{sale}/show',
    [RescissionController::class, 'show']
)->name('rescissions.show');
Route::resource('rescissions', RescissionController::class)->except(['create']);


//RUTAS PARA HOME 
