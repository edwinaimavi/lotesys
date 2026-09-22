<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Nota de venta</title>
<style>body{font-family:DejaVu Sans,sans-serif;color:#193d31;font-size:12px;margin:30px}h1{font-size:25px}table{width:100%;border-collapse:collapse;margin-top:30px}td{padding:12px;border-bottom:1px solid #ddd}</style></head><body>
<h1>Grupo Krea</h1><h2>Nota de venta {{ $document['code'] }}</h2>
<p>Copia de consulta del portal del cliente</p><p>{{ $name }}</p>
<table><tr><td>Fecha</td><td>{{ $document['date'] }}</td></tr><tr><td>Estado</td><td>{{ $document['status'] }}</td></tr><tr><td>Importe</td><td>{{ $document['currency'] }} {{ number_format($document['amount'], 2) }}</td></tr></table>
<p>Documento no tributario. No reemplaza una boleta o factura.</p>
</body></html>
