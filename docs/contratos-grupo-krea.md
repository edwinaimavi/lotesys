# Contratos de Grupo Krea

## Implementación y verificación

Implementado únicamente Grupo Krea por RUC `20607752312`. No se hicieron commit ni push.

- PHPWord `1.4.0` y dependencia `phpoffice/math 0.3.0`; instalación limitada, sin actualizar otros paquetes.
- Migración `database/migrations/2026_09_22_000001_add_gender_to_customers_table.php`: columna string nullable. Aplicada únicamente después de confirmar `APP_ENV=local`.
- `GET admin/sales/{sale}/contract-data` y `POST admin/sales/{sale}/contract-generate`: sesión web, CSRF en POST y permiso existente `admin.sales.index`.
- El servicio vuelve a resolver la venta y empresa desde la base de datos; el `sale_id` editable del documento nunca cambia el recurso de la ruta.
- Generación temporal, sin persistencia de borradores, y eliminación después de enviar la descarga comprobada mediante prueba.
- Datos de `sale_lots` con prioridad al principal; fallback a `sales.lot_id` para ventas anteriores.
- Cuotas reales `schedule_type=cuota`, ordenadas por `installment_number,id`; inicial excluida y rechazo explícito de más de 36 cuotas.
- Los campos calculados se recalculan al generar solo si conservan su valor inicial. Las modificaciones explícitas de palabras, firma, tratamiento y fechas derivadas se respetan.

Pruebas ejecutadas:

| Comando | Resultado |
|---|---|
| `php artisan test --filter=SaleContract` | 19 pruebas, 176 aserciones, correctas; última revisión exclusivamente por código/ZIP |
| `php artisan test --filter=PaymentScheduleGeneration` | 2 pruebas, 6 aserciones, correctas |
| `php artisan test --filter=CustomerPortalTest` | 15 pruebas, 124 aserciones, correctas |
| `php -l` sobre los 16 PHP/Blade nuevos o modificados | Sin errores |
| `php artisan route:list --path=contract -v` | Ambas rutas con auth y permiso de ventas |
| `git diff --check` | Sin errores |
| `npm.cmd run build` | Correcto; se usó npm.cmd por la política de ejecución de PowerShell |
| Apertura en Microsoft Word, solo lectura | Copia parametrizada y documento de prueba abren sin reparación |
| Renderizado del documento sintético con Word | 8 páginas inspeccionadas; imágenes, logo, marca de agua y plano presentes |
| ZIP/XML y relaciones | XML válido, vínculos internos existentes y marcadores continuos |

Las pruebas trabajan en SQLite en memoria. La validación visual usó Word instalado: no estaba disponible el runtime de renderizado empaquetado. Se conservaron todos los saltos y formatos del original; la extensión de los datos puede desplazar líneas y separar el bloque de firma entre páginas, por lo que se debe revisar la paginación con los valores definitivos.

Revisión posterior solicitada: no se volvió a usar Word COM ni a renderizar. Se confirmó la migración ya aplicada (lote 14), sin volver a ejecutarla. Las 19 pruebas focales verificaron ZIP consistente, XML de todas las partes, ausencia de marcadores pendientes, relaciones idénticas al original y con destinos existentes, 8 imágenes idénticas y rechazo de lotes de otra empresa. No había PHP/pruebas ni comandos de automatización pendientes; la sesión de Word anterior y terminales de VS Code se conservaron. El flujo del modal se revisó por código: abrir precarga mediante GET; solo Generar envía POST; cerrar no envía solicitudes de escritura. No se repitió una prueba interactiva del navegador ni las suites previamente aprobadas.

## Archivos creados

- `app/Http/Controllers/Admin/SaleContractController.php`
- `app/Services/Contracts/SaleContractService.php`
- `app/Services/Contracts/AmountToWordsService.php`
- `config/contracts.php`
- `database/migrations/2026_09_22_000001_add_gender_to_customers_table.php`
- `resources/js/pages/sale-contract.js`
- `resources/views/admin/sales/partials/contractModal.blade.php`
- `tests/Feature/SaleContractTest.php`
- `scripts/contracts/parametrize-grupo-krea.php`
- `scripts/contracts/verify-grupo-krea.php`
- `docs/contratos-grupo-krea.md`
- Copia DOCX parametrizada y `parametrizacion-auditoria.json` en el directorio privado de plantillas.

Los scripts de parametrización y verificación son utilidades manuales, no se ejecutan al generar contratos. El segundo regenera el inventario de variables de este informe. La plantilla privada no está versionada por las reglas existentes de storage; debe conservarse junto con el despliegue del proyecto.

## Archivos modificados

- `app/Models/Customer.php`
- `app/Http/Controllers/Admin/CustomerController.php` (incluye el HTML de acciones que realmente utiliza el DataTable)
- `resources/views/admin/customers/partials/modal.blade.php`
- `resources/views/admin/customers/partials/viewModal.blade.php`
- `resources/js/pages/customer.js`
- `resources/views/admin/sales/partials/acciones.blade.php`
- `resources/views/admin/sales/index.blade.php`
- `resources/js/pages/sale.js` (solo importa el módulo de contratos)
- `routes/admin.php`
- `composer.json`
- `composer.lock`

Vite regeneró `public/build` conforme a las reglas existentes del proyecto.

## Prueba manual

1. Entrar con un usuario que tenga `admin.sales.index`.
2. Clientes → editar cliente → elegir género → guardar; abrir el detalle y comprobarlo. La consulta DNI no altera ese campo.
3. Ventas → acciones → Contrato. Puede usarse una venta recién registrada sin pagos.
4. Revisar comprador, empresa, proyecto, lote o lotes, importes y cuotas. Los datos ausentes deben estar vacíos.
5. Completar predio matriz, estado civil y textos de depósitos. Revisar nombre y DNI del representante.
6. Cambiar datos del documento; generar Word y comprobar que los registros del sistema no cambiaron.
7. Abrir la descarga `Contrato_{sale_code}_{document_number}_Grupo_Krea.docx` en Word.
8. Revisar diseño, encabezados, tipografía, vouchers, plano, firmas y saltos de página con los datos reales.
9. Comprobar Comprador/Compradora, fecha contractual, importes en letras y coincidencia del cronograma con el sistema.
10. Probar también una empresa sin plantilla y una venta con más de 36 cuotas: deben mostrar los errores en español.
11. En ventas múltiples, comprobar que aparecen todos los lotes en el modal; revisar la adaptación pendiente del contrato singular antes de utilizarlo para firma.

## Plantillas

- Original intacto: `storage/app/private/contracts/templates/grupo-krea/contrato_compra_venta_lote_grupo_krea.docx`.
- Copia utilizada: `storage/app/private/contracts/templates/grupo-krea/contrato_compra_venta_lote_grupo_krea_parametrizado.docx`.
- SHA-256 original: `9092cf419544b94c0eaf5804a5802e1bf86d81f2d203767233364fef6282eb37`.
- Imágenes: 8 antes y 8 después, contenido binario idéntico. Vouchers, plano, encabezados, pies, estilos y relaciones intactos.
- Solo se modificó texto en `word/document.xml`; los demás miembros ZIP son idénticos al original.
- Word abrió la copia en modo de solo lectura sin reparación. Los marcadores largos pueden aumentar la paginación de la plantilla; revisar el Word final con los valores definitivos.

## Alcance de la plantilla

La plantilla conserva la redacción singular de las cláusulas segunda y siguientes y su plano fijo. El formulario carga todos los sale_lots y ofrece un resumen, pero no se agregó un espacio nuevo ni se adaptó la redacción para múltiples lotes. Es necesario revisar/adaptar ese documento antes de firmar una venta múltiple.

Los campos del catálogo sin un espacio equivalente en el original se ofrecen como datos de apoyo y no se insertan artificialmente en el texto. Incluyen teléfono/correo de empresa, datos separados de nombres/apellidos, saldo, inicial en letras, código del lote, resumen de lotes y número/palabras de cuota. Las letras sí tienen sus importes, palabras y números. El formulario identifica los campos sin espacio.

El predio matriz es independiente del proyecto: sus campos se dejan manuales y vacíos para no atribuirle datos sin confirmar. Las penalidades y gastos fijos de las cláusulas se conservaron.

## Variables reales y fuentes

Total: 228 variables únicas. Cada marcador está íntegro dentro de un único nodo de texto.

| Variable | Fuente / valor inicial |
|---|---|
| `${address}` | customer.address |
| `${bill_01_amount}` | payment_schedules cuota 1 → installment_amount |
| `${bill_01_amount_words}` | payment_schedules cuota 1 → installment_amount → letras |
| `${bill_01_number}` | payment_schedules cuota 1 → installment_number |
| `${bill_02_amount}` | payment_schedules cuota 2 → installment_amount |
| `${bill_02_amount_words}` | payment_schedules cuota 2 → installment_amount → letras |
| `${bill_02_number}` | payment_schedules cuota 2 → installment_number |
| `${bill_03_amount}` | payment_schedules cuota 3 → installment_amount |
| `${bill_03_amount_words}` | payment_schedules cuota 3 → installment_amount → letras |
| `${bill_03_number}` | payment_schedules cuota 3 → installment_number |
| `${bill_04_amount}` | payment_schedules cuota 4 → installment_amount |
| `${bill_04_amount_words}` | payment_schedules cuota 4 → installment_amount → letras |
| `${bill_04_number}` | payment_schedules cuota 4 → installment_number |
| `${bill_05_amount}` | payment_schedules cuota 5 → installment_amount |
| `${bill_05_amount_words}` | payment_schedules cuota 5 → installment_amount → letras |
| `${bill_05_number}` | payment_schedules cuota 5 → installment_number |
| `${bill_06_amount}` | payment_schedules cuota 6 → installment_amount |
| `${bill_06_amount_words}` | payment_schedules cuota 6 → installment_amount → letras |
| `${bill_06_number}` | payment_schedules cuota 6 → installment_number |
| `${bill_07_amount}` | payment_schedules cuota 7 → installment_amount |
| `${bill_07_amount_words}` | payment_schedules cuota 7 → installment_amount → letras |
| `${bill_07_number}` | payment_schedules cuota 7 → installment_number |
| `${bill_08_amount}` | payment_schedules cuota 8 → installment_amount |
| `${bill_08_amount_words}` | payment_schedules cuota 8 → installment_amount → letras |
| `${bill_08_number}` | payment_schedules cuota 8 → installment_number |
| `${bill_09_amount}` | payment_schedules cuota 9 → installment_amount |
| `${bill_09_amount_words}` | payment_schedules cuota 9 → installment_amount → letras |
| `${bill_09_number}` | payment_schedules cuota 9 → installment_number |
| `${bill_10_amount}` | payment_schedules cuota 10 → installment_amount |
| `${bill_10_amount_words}` | payment_schedules cuota 10 → installment_amount → letras |
| `${bill_10_number}` | payment_schedules cuota 10 → installment_number |
| `${bill_11_amount}` | payment_schedules cuota 11 → installment_amount |
| `${bill_11_amount_words}` | payment_schedules cuota 11 → installment_amount → letras |
| `${bill_11_number}` | payment_schedules cuota 11 → installment_number |
| `${bill_12_amount}` | payment_schedules cuota 12 → installment_amount |
| `${bill_12_amount_words}` | payment_schedules cuota 12 → installment_amount → letras |
| `${bill_12_number}` | payment_schedules cuota 12 → installment_number |
| `${bill_13_amount}` | payment_schedules cuota 13 → installment_amount |
| `${bill_13_amount_words}` | payment_schedules cuota 13 → installment_amount → letras |
| `${bill_13_number}` | payment_schedules cuota 13 → installment_number |
| `${bill_14_amount}` | payment_schedules cuota 14 → installment_amount |
| `${bill_14_amount_words}` | payment_schedules cuota 14 → installment_amount → letras |
| `${bill_14_number}` | payment_schedules cuota 14 → installment_number |
| `${bill_15_amount}` | payment_schedules cuota 15 → installment_amount |
| `${bill_15_amount_words}` | payment_schedules cuota 15 → installment_amount → letras |
| `${bill_15_number}` | payment_schedules cuota 15 → installment_number |
| `${bill_16_amount}` | payment_schedules cuota 16 → installment_amount |
| `${bill_16_amount_words}` | payment_schedules cuota 16 → installment_amount → letras |
| `${bill_16_number}` | payment_schedules cuota 16 → installment_number |
| `${bill_17_amount}` | payment_schedules cuota 17 → installment_amount |
| `${bill_17_amount_words}` | payment_schedules cuota 17 → installment_amount → letras |
| `${bill_17_number}` | payment_schedules cuota 17 → installment_number |
| `${bill_18_amount}` | payment_schedules cuota 18 → installment_amount |
| `${bill_18_amount_words}` | payment_schedules cuota 18 → installment_amount → letras |
| `${bill_18_number}` | payment_schedules cuota 18 → installment_number |
| `${bill_19_amount}` | payment_schedules cuota 19 → installment_amount |
| `${bill_19_amount_words}` | payment_schedules cuota 19 → installment_amount → letras |
| `${bill_19_number}` | payment_schedules cuota 19 → installment_number |
| `${bill_20_amount}` | payment_schedules cuota 20 → installment_amount |
| `${bill_20_amount_words}` | payment_schedules cuota 20 → installment_amount → letras |
| `${bill_20_number}` | payment_schedules cuota 20 → installment_number |
| `${bill_21_amount}` | payment_schedules cuota 21 → installment_amount |
| `${bill_21_amount_words}` | payment_schedules cuota 21 → installment_amount → letras |
| `${bill_21_number}` | payment_schedules cuota 21 → installment_number |
| `${bill_22_amount}` | payment_schedules cuota 22 → installment_amount |
| `${bill_22_amount_words}` | payment_schedules cuota 22 → installment_amount → letras |
| `${bill_22_number}` | payment_schedules cuota 22 → installment_number |
| `${bill_23_amount}` | payment_schedules cuota 23 → installment_amount |
| `${bill_23_amount_words}` | payment_schedules cuota 23 → installment_amount → letras |
| `${bill_23_number}` | payment_schedules cuota 23 → installment_number |
| `${bill_24_amount}` | payment_schedules cuota 24 → installment_amount |
| `${bill_24_amount_words}` | payment_schedules cuota 24 → installment_amount → letras |
| `${bill_24_number}` | payment_schedules cuota 24 → installment_number |
| `${bill_25_amount}` | payment_schedules cuota 25 → installment_amount |
| `${bill_25_amount_words}` | payment_schedules cuota 25 → installment_amount → letras |
| `${bill_25_number}` | payment_schedules cuota 25 → installment_number |
| `${bill_26_amount}` | payment_schedules cuota 26 → installment_amount |
| `${bill_26_amount_words}` | payment_schedules cuota 26 → installment_amount → letras |
| `${bill_26_number}` | payment_schedules cuota 26 → installment_number |
| `${bill_27_amount}` | payment_schedules cuota 27 → installment_amount |
| `${bill_27_amount_words}` | payment_schedules cuota 27 → installment_amount → letras |
| `${bill_27_number}` | payment_schedules cuota 27 → installment_number |
| `${bill_28_amount}` | payment_schedules cuota 28 → installment_amount |
| `${bill_28_amount_words}` | payment_schedules cuota 28 → installment_amount → letras |
| `${bill_28_number}` | payment_schedules cuota 28 → installment_number |
| `${bill_29_amount}` | payment_schedules cuota 29 → installment_amount |
| `${bill_29_amount_words}` | payment_schedules cuota 29 → installment_amount → letras |
| `${bill_29_number}` | payment_schedules cuota 29 → installment_number |
| `${bill_30_amount}` | payment_schedules cuota 30 → installment_amount |
| `${bill_30_amount_words}` | payment_schedules cuota 30 → installment_amount → letras |
| `${bill_30_number}` | payment_schedules cuota 30 → installment_number |
| `${bill_31_amount}` | payment_schedules cuota 31 → installment_amount |
| `${bill_31_amount_words}` | payment_schedules cuota 31 → installment_amount → letras |
| `${bill_31_number}` | payment_schedules cuota 31 → installment_number |
| `${bill_32_amount}` | payment_schedules cuota 32 → installment_amount |
| `${bill_32_amount_words}` | payment_schedules cuota 32 → installment_amount → letras |
| `${bill_32_number}` | payment_schedules cuota 32 → installment_number |
| `${bill_33_amount}` | payment_schedules cuota 33 → installment_amount |
| `${bill_33_amount_words}` | payment_schedules cuota 33 → installment_amount → letras |
| `${bill_33_number}` | payment_schedules cuota 33 → installment_number |
| `${bill_34_amount}` | payment_schedules cuota 34 → installment_amount |
| `${bill_34_amount_words}` | payment_schedules cuota 34 → installment_amount → letras |
| `${bill_34_number}` | payment_schedules cuota 34 → installment_number |
| `${bill_35_amount}` | payment_schedules cuota 35 → installment_amount |
| `${bill_35_amount_words}` | payment_schedules cuota 35 → installment_amount → letras |
| `${bill_35_number}` | payment_schedules cuota 35 → installment_number |
| `${bill_36_amount}` | payment_schedules cuota 36 → installment_amount |
| `${bill_36_amount_words}` | payment_schedules cuota 36 → installment_amount → letras |
| `${bill_36_number}` | payment_schedules cuota 36 → installment_number |
| `${block_name}` | lot.block.name |
| `${buyer_identified}` | Customer.gender; editable |
| `${buyer_informed}` | Customer.gender; editable |
| `${buyer_marital_status}` | Manual, inicialmente vacío |
| `${buyer_nationality}` | Customer.gender: peruana / peruano; editable |
| `${buyer_obliged}` | Customer.gender; editable |
| `${buyer_role}` | Customer.gender: La Compradora / El Comprador; editable |
| `${buyer_signature_document_number}` | customer.document_number |
| `${buyer_signature_document_type}` | customer.document_type |
| `${buyer_signature_name}` | customer.full_name |
| `${company_address}` | company.address |
| `${company_business_name}` | company.business_name |
| `${company_ruc}` | company.ruc |
| `${department}` | customer.department |
| `${district}` | customer.district |
| `${document_number}` | customer.document_number |
| `${document_type}` | customer.document_type |
| `${full_name}` | customer.full_name |
| `${initial_payment}` | sale.initial_payment |
| `${initial_payment_account}` | Manual, inicialmente vacío |
| `${initial_payment_bank}` | Manual, inicialmente vacío |
| `${initial_payment_dates_text}` | Manual, inicialmente vacío |
| `${initial_payment_deposit_count}` | Manual, inicialmente vacío |
| `${installment_clause_numerals}` | Posiciones de las cuotas en cláusula tercera (la inicial ocupa la primera) |
| `${installments_count}` | sale.installments_count |
| `${last_installment_due_date}` | Última cuota real ordenada por installment_number, id |
| `${lot_area}` | lot.area |
| `${lot_number}` | lot.number |
| `${lot_price}` | sale.lot_price |
| `${lot_price_words}` | sale.lot_price → AmountToWordsService |
| `${lot_unit_measure}` | lot.unit_measure |
| `${matrix_department}` | Manual, inicialmente vacío |
| `${matrix_district}` | Manual, inicialmente vacío |
| `${matrix_property_name}` | Manual, inicialmente vacío |
| `${matrix_province}` | Manual, inicialmente vacío |
| `${matrix_registry_number}` | Manual, inicialmente vacío |
| `${matrix_sector}` | Manual, inicialmente vacío |
| `${matrix_total_area}` | Manual, inicialmente vacío |
| `${matrix_total_area_words}` | Manual, inicialmente vacío |
| `${project_name}` | project.name |
| `${province}` | customer.province |
| `${representative_document}` | Default de plantilla; editable: 74723182 |
| `${representative_name}` | Default de plantilla; editable: KENNY JOSUE FARJE PULACHE |
| `${representative_registry_entry}` | Default de plantilla; editable: 11175694 |
| `${sale_date_day}` | sale.sale_date → día |
| `${sale_date_month_name}` | sale.sale_date → mes en español |
| `${sale_date_year}` | sale.sale_date → año |
| `${schedule_01_due_date}` | schedules.0.due_date |
| `${schedule_01_installment_amount}` | schedules.0.installment_amount |
| `${schedule_02_due_date}` | schedules.1.due_date |
| `${schedule_02_installment_amount}` | schedules.1.installment_amount |
| `${schedule_03_due_date}` | schedules.2.due_date |
| `${schedule_03_installment_amount}` | schedules.2.installment_amount |
| `${schedule_04_due_date}` | schedules.3.due_date |
| `${schedule_04_installment_amount}` | schedules.3.installment_amount |
| `${schedule_05_due_date}` | schedules.4.due_date |
| `${schedule_05_installment_amount}` | schedules.4.installment_amount |
| `${schedule_06_due_date}` | schedules.5.due_date |
| `${schedule_06_installment_amount}` | schedules.5.installment_amount |
| `${schedule_07_due_date}` | schedules.6.due_date |
| `${schedule_07_installment_amount}` | schedules.6.installment_amount |
| `${schedule_08_due_date}` | schedules.7.due_date |
| `${schedule_08_installment_amount}` | schedules.7.installment_amount |
| `${schedule_09_due_date}` | schedules.8.due_date |
| `${schedule_09_installment_amount}` | schedules.8.installment_amount |
| `${schedule_10_due_date}` | schedules.9.due_date |
| `${schedule_10_installment_amount}` | schedules.9.installment_amount |
| `${schedule_11_due_date}` | schedules.10.due_date |
| `${schedule_11_installment_amount}` | schedules.10.installment_amount |
| `${schedule_12_due_date}` | schedules.11.due_date |
| `${schedule_12_installment_amount}` | schedules.11.installment_amount |
| `${schedule_13_due_date}` | schedules.12.due_date |
| `${schedule_13_installment_amount}` | schedules.12.installment_amount |
| `${schedule_14_due_date}` | schedules.13.due_date |
| `${schedule_14_installment_amount}` | schedules.13.installment_amount |
| `${schedule_15_due_date}` | schedules.14.due_date |
| `${schedule_15_installment_amount}` | schedules.14.installment_amount |
| `${schedule_16_due_date}` | schedules.15.due_date |
| `${schedule_16_installment_amount}` | schedules.15.installment_amount |
| `${schedule_17_due_date}` | schedules.16.due_date |
| `${schedule_17_installment_amount}` | schedules.16.installment_amount |
| `${schedule_18_due_date}` | schedules.17.due_date |
| `${schedule_18_installment_amount}` | schedules.17.installment_amount |
| `${schedule_19_due_date}` | schedules.18.due_date |
| `${schedule_19_installment_amount}` | schedules.18.installment_amount |
| `${schedule_20_due_date}` | schedules.19.due_date |
| `${schedule_20_installment_amount}` | schedules.19.installment_amount |
| `${schedule_21_due_date}` | schedules.20.due_date |
| `${schedule_21_installment_amount}` | schedules.20.installment_amount |
| `${schedule_22_due_date}` | schedules.21.due_date |
| `${schedule_22_installment_amount}` | schedules.21.installment_amount |
| `${schedule_23_due_date}` | schedules.22.due_date |
| `${schedule_23_installment_amount}` | schedules.22.installment_amount |
| `${schedule_24_due_date}` | schedules.23.due_date |
| `${schedule_24_installment_amount}` | schedules.23.installment_amount |
| `${schedule_25_due_date}` | schedules.24.due_date |
| `${schedule_25_installment_amount}` | schedules.24.installment_amount |
| `${schedule_26_due_date}` | schedules.25.due_date |
| `${schedule_26_installment_amount}` | schedules.25.installment_amount |
| `${schedule_27_due_date}` | schedules.26.due_date |
| `${schedule_27_installment_amount}` | schedules.26.installment_amount |
| `${schedule_28_due_date}` | schedules.27.due_date |
| `${schedule_28_installment_amount}` | schedules.27.installment_amount |
| `${schedule_29_due_date}` | schedules.28.due_date |
| `${schedule_29_installment_amount}` | schedules.28.installment_amount |
| `${schedule_30_due_date}` | schedules.29.due_date |
| `${schedule_30_installment_amount}` | schedules.29.installment_amount |
| `${schedule_31_due_date}` | schedules.30.due_date |
| `${schedule_31_installment_amount}` | schedules.30.installment_amount |
| `${schedule_32_due_date}` | schedules.31.due_date |
| `${schedule_32_installment_amount}` | schedules.31.installment_amount |
| `${schedule_33_due_date}` | schedules.32.due_date |
| `${schedule_33_installment_amount}` | schedules.32.installment_amount |
| `${schedule_34_due_date}` | schedules.33.due_date |
| `${schedule_34_installment_amount}` | schedules.33.installment_amount |
| `${schedule_35_due_date}` | schedules.34.due_date |
| `${schedule_35_installment_amount}` | schedules.34.installment_amount |
| `${schedule_36_due_date}` | schedules.35.due_date |
| `${schedule_36_installment_amount}` | schedules.35.installment_amount |
