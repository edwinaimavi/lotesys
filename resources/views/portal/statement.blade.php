<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title>Estado de cuenta</title>
<style>
@page { margin: 35px; } body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #183d32; }
h1 { font-size: 23px; margin-bottom: 5px; } h2 { font-size: 14px; margin-top: 24px; }
table { width: 100%; border-collapse: collapse; margin-top: 10px; } th { background: #eaf2ec; text-align: left; }
td, th { padding: 7px 5px; border-bottom: 1px solid #dce5df; } tr { page-break-inside: avoid; } thead { display: table-header-group; }
.muted { color: #60776c; } .money { text-align: right; white-space: nowrap; }
</style></head><body>
<h1>Grupo Krea</h1><p>Estado de cuenta · {{ $data['code'] }}</p>
<p>{{ $name }}<br>{{ $data['project'] }} · Compra: {{ $data['date'] }}</p>
<p>@foreach($data['lots'] as $lot) Mz {{ $lot['block'] }} · Lote {{ $lot['number'] }}{{ !$loop->last ? ' / ' : '' }} @endforeach</p>
<table><tr><th>Monto de compra</th><th>Total pagado</th><th>Saldo de cuotas</th></tr><tr>
@foreach(['total','paid','balance'] as $key)<td>S/ {{ number_format($data['summary'][$key], 2) }}</td>@endforeach
</tr></table>
<p>{{ $data['summary']['installments'] }} cuotas · {{ $data['summary']['paid_installments'] }} pagadas · {{ $data['summary']['pending_installments'] }} pendientes.</p>
<p class="muted">Saldo de cuotas con mora según las reglas vigentes y aplicaciones registradas. Pagos anulados no integran el total pagado.</p>
<h2>Cronograma</h2><table><thead><tr><th>N°</th><th>Vencimiento</th><th>Monto</th><th>Pagado</th><th>Saldo</th><th>Estado</th></tr></thead><tbody>
@forelse($data['schedules'] as $row)<tr><td>{{ $row['number'] }} {{ $row['label'] }}</td><td>{{ $row['due_date'] }}</td>
@foreach(['amount','paid','balance'] as $key)<td class="money">{{ number_format($row[$key], 2) }}</td>@endforeach
<td>{{ ucfirst($row['status']) }}{{ $row['overdue'] ? ' · Vencida' : '' }}</td></tr>
@empty<tr><td colspan="6">Sin cuotas registradas.</td></tr>@endforelse</tbody></table>
<h2>Pagos</h2><table><thead><tr><th>Fecha</th><th>Código</th><th>Concepto</th><th>Medio</th><th>Monto</th><th>Estado</th></tr></thead><tbody>
@forelse($data['payments'] as $row)<tr><td>{{ $row['date'] }}</td><td>#{{ $row['id'] }}</td><td>{{ $row['concept'] }}</td><td>{{ $row['method'] }}</td><td class="money">{{ number_format($row['amount'], 2) }}</td><td>{{ $row['status'] }}</td></tr>
@empty<tr><td colspan="6">Sin pagos registrados.</td></tr>@endforelse</tbody></table>
<p class="muted">Consulta emitida el {{ now()->format('d/m/Y H:i') }}. Importes expresados en soles.</p>
</body></html>
