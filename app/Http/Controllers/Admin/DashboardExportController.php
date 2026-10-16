<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class DashboardExportController extends Controller
{
    private const DATE_FORMAT_DMY  = 'd/m/Y';
    private const NUMBER_FORMAT_MX = '"$"#,##0.00_-';

    public function exportGanancias(Request $request)
    {
        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date . ' 23:59:59';

        $summary = Order::selectRaw('payment_method, COUNT(*) as count, SUM(total) as sum')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('table_number')
                         ->whereIn('status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->groupBy('payment_method')
            ->get();

        return $this->generateExcelGanancias($summary, $startDate, $endDate);
    }

    /**
     * Exporta el reporte OPERATIVO (Rotación por Categoría)
     */
    public function exportRotacion(Request $request)
    {
        $startDate = $request->start_date . ' 00:00:00';
        $endDate   = $request->end_date . ' 23:59:59';

        $ventasPorCategoria = DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->join('products', 'order_items.product_id', '=', 'products.id')
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('orders.status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('orders.table_number')
                         ->whereIn('orders.status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->whereNotNull('products.category')
            ->select('products.category', DB::raw('SUM(order_items.quantity) as total_vendido'), DB::raw('SUM(order_items.subtotal) as ingresos'))
            ->groupBy('products.category')
            ->orderByDesc('total_vendido')
            ->get();

        return $this->generateExcelRotacion($ventasPorCategoria, $startDate, $endDate);
    }

    // ─── MÉTODOS DE DISEÑO (ESTILOS COMPARTIDOS) ─────────────────────────────

    private function getStyles()
    {
        return [
            'title' => [
                'font' => ['bold' => true, 'size' => 14, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FF18181B']],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ],
            'header' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FFFFFFFF']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDD3A1D']], // Rojo del sistema
            ],
            'total' => [
                'font' => ['bold' => true, 'color' => ['argb' => 'FF15803D']],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FFDCFCE7']], // Verde claro
            ]
        ];
    }

    private function setCabeceraGeneral($sheet, $titulo, $startDate, $endDate)
    {
        $styles = $this->getStyles();
        
        $sheet->mergeCells('A1:E2');
        $sheet->setCellValue('A1', '📊 ' . $titulo);
        $sheet->getStyle('A1:E2')->applyFromArray($styles['title']);

        $sheet->setCellValue('A4', 'Sucursal:');
        $sheet->setCellValue('B4', 'La 501 Sports Bar');
        $sheet->setCellValue('A5', 'Fecha de Emisión:');
        $sheet->setCellValue('B5', date(self::DATE_FORMAT_DMY . ' h:i A'));
        $sheet->setCellValue('A6', 'Rango Reportado:');
        $sheet->setCellValue('B6', date(self::DATE_FORMAT_DMY, strtotime($startDate)) . ' al ' . date(self::DATE_FORMAT_DMY, strtotime($endDate)));
        $sheet->getStyle('A4:A6')->getFont()->setBold(true);
    }

    // ─── MÉTODOS DE GENERACIÓN DE EXCEL ─────────────────────────────────────

    private function generateExcelGanancias($summary, $startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Corte Financiero');
        $styles = $this->getStyles();

        // 1. Cabecera
        $this->setCabeceraGeneral($sheet, 'REPORTE FINANCIERO DE VENTAS', $startDate, $endDate);

        // 2. Resumen de Cobros
        $sheet->setCellValue('A8', 'RESUMEN DE COBROS');
        $sheet->mergeCells('A8:E8');
        $sheet->getStyle('A8:E8')->applyFromArray($styles['header']);

        $sheet->setCellValue('A9', 'Método de Pago');
        $sheet->mergeCells('A9:B9');
        $sheet->setCellValue('C9', 'Operaciones');
        $sheet->setCellValue('D9', 'Monto Total');
        $sheet->mergeCells('D9:E9');
        $sheet->getStyle('A9:E9')->getFont()->setBold(true);

        $row = 10;
        $totalSum = 0;
        $totalCount = 0;

        foreach ($summary as $item) {
            $methodName = $item->payment_method ? mb_strtoupper($item->payment_method) : 'EFECTIVO';
            $sheet->setCellValue('A' . $row, $methodName);
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue('C' . $row, $item->count);
            $sheet->setCellValue('D' . $row, $item->sum);
            $sheet->mergeCells("D{$row}:E{$row}");
            $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);
            
            $totalSum += $item->sum;
            $totalCount += $item->count;
            $row++;
        }

        // Total Resumen
        $sheet->setCellValue('A' . $row, 'TOTAL INGRESOS');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue('C' . $row, $totalCount);
        $sheet->setCellValue('D' . $row, $totalSum);
        $sheet->mergeCells("D{$row}:E{$row}");
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($styles['total']);
        $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);

        // 3. Desglose de Movimientos (Consultado por Chunk para no saturar memoria)
        $row += 3;
        $sheet->setCellValue('A' . $row, 'DESGLOSE DETALLADO DE MOVIMIENTOS');
        $sheet->mergeCells("A{$row}:E{$row}");
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($styles['header']);
        $row++;

        $sheet->setCellValue('A' . $row, 'Folio');
        $sheet->setCellValue('B' . $row, 'Fecha');
        $sheet->setCellValue('C' . $row, 'Hora');
        $sheet->setCellValue('D' . $row, 'Método Pago');
        $sheet->setCellValue('E' . $row, 'Total');
        $sheet->getStyle("A{$row}:E{$row}")->getFont()->setBold(true);
        $row++;

        Order::whereBetween('created_at', [$startDate, $endDate])
            ->where(function ($q) {
                $q->where('status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('table_number')
                         ->whereIn('status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->orderBy('created_at', 'asc')
            ->chunk(200, function ($ordersChunk) use ($sheet, &$row) {
                foreach ($ordersChunk as $order) {
                    $metodoPago = $order->payment_method ? ucfirst(strtolower($order->payment_method)) : 'Efectivo';
                    $sheet->setCellValue('A' . $row, '#' . str_pad($order->id, 4, '0', STR_PAD_LEFT));
                    $sheet->setCellValue('B' . $row, $order->created_at->format(self::DATE_FORMAT_DMY));
                    $sheet->setCellValue('C' . $row, $order->created_at->format('h:i A'));
                    $sheet->setCellValue('D' . $row, $metodoPago);
                    $sheet->setCellValue('E' . $row, $order->total);
                    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);
                    $row++;
                }
            });

        // 4. Auto-ajustar columnas
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->downloadExcel($spreadsheet, 'Reporte_Financiero_La501');
    }

    private function generateExcelRotacion($ventasPorCategoria, $startDate, $endDate)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Análisis de Rotación');
        $styles = $this->getStyles();

        // 1. Cabecera
        $this->setCabeceraGeneral($sheet, 'ANÁLISIS DE ROTACIÓN POR CATEGORÍA', $startDate, $endDate);

        // 2. Tabla de Categorías
        $sheet->setCellValue('A8', 'RENDIMIENTO POR CATEGORÍA DEL MENÚ');
        $sheet->mergeCells('A8:E8');
        $sheet->getStyle('A8:E8')->applyFromArray($styles['header']);

        $sheet->setCellValue('A9', 'Categoría');
        $sheet->mergeCells('A9:B9');
        $sheet->setCellValue('C9', 'Platillos Vendidos');
        $sheet->setCellValue('D9', '% Participación');
        $sheet->setCellValue('E9', 'Ingresos Generados');
        $sheet->getStyle('A9:E9')->getFont()->setBold(true);

        $row = 10;
        $totalArticulosVendidos = $ventasPorCategoria->sum('total_vendido');
        $totalIngresosCategorias = $ventasPorCategoria->sum('ingresos');

        foreach ($ventasPorCategoria as $cat) {
            $sheet->setCellValue('A' . $row, $cat->category);
            $sheet->mergeCells("A{$row}:B{$row}");
            $sheet->setCellValue('C' . $row, $cat->total_vendido);
            
            // Fórmula matemática de porcentaje
            $porcentaje = $totalArticulosVendidos > 0 ? round(($cat->total_vendido / $totalArticulosVendidos) * 100, 2) : 0;
            $sheet->setCellValue('D' . $row, $porcentaje . '%');
            
            $sheet->setCellValue('E' . $row, $cat->ingresos);
            $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);
            $row++;
        }

        // Total Resumen
        $sheet->setCellValue('A' . $row, 'TOTALES');
        $sheet->mergeCells("A{$row}:B{$row}");
        $sheet->setCellValue('C' . $row, $totalArticulosVendidos);
        $sheet->setCellValue('D' . $row, '100%');
        $sheet->setCellValue('E' . $row, $totalIngresosCategorias);
        $sheet->getStyle("A{$row}:E{$row}")->applyFromArray($styles['total']);
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);

        // 4. Auto-ajustar columnas
        foreach (range('A', 'E') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->downloadExcel($spreadsheet, 'Reporte_Rotacion_La501');
    }

    public function exportCorteDiario(Request $request)
    {
        $corteDate = $request->input('corte_date', date('Y-m-d'));
        
        $ordersCorte = Order::whereDate('created_at', $corteDate)
            ->where(function ($q) {
                $q->where('status', 'paid')
                  ->orWhere(function ($sq) {
                      $sq->whereNull('table_number')
                         ->whereIn('status', ['ready', 'delivering', 'delivered']);
                  });
            })
            ->get();

        $corteTotal = $ordersCorte->sum('total');
        $corteCount = $ordersCorte->count();
        $corteFirstTime = $ordersCorte->min('created_at') ? Carbon::parse($ordersCorte->min('created_at'))->format('d/m/Y h:i A') : 'N/A';
        $corteLastTime = $ordersCorte->max('created_at') ? Carbon::parse($ordersCorte->max('created_at'))->format('d/m/Y h:i A') : 'N/A';

        // Parse orders by payment method
        $detailedPayments = [
            'EFECTIVO' => ['orders' => [], 'total' => 0.0],
            'TARJETA DE CRÉDITO / DÉBITO' => ['orders' => [], 'total' => 0.0],
        ];

        foreach ($ordersCorte as $order) {
            $method = $order->payment_method ?: 'Efectivo';
            $parsed = self::parsePaymentMethodAmountsStatic($method, (float) $order->total);

            if ($parsed['efectivo'] > 0) {
                $detailedPayments['EFECTIVO']['orders'][] = [
                    'id' => $order->id,
                    'monto' => $parsed['efectivo']
                ];
                $detailedPayments['EFECTIVO']['total'] += $parsed['efectivo'];
            }
            if ($parsed['tarjeta'] > 0) {
                $detailedPayments['TARJETA DE CRÉDITO / DÉBITO']['orders'][] = [
                    'id' => $order->id,
                    'monto' => $parsed['tarjeta']
                ];
                $detailedPayments['TARJETA DE CRÉDITO / DÉBITO']['total'] += $parsed['tarjeta'];
            }
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Cierre Diario');

        // Styles
        $sheet->setCellValue('A1', 'Sucursal: La 501 Sports Bar');
        $sheet->mergeCells('A1:D1');
        $sheet->getStyle('A1')->getFont()->setBold(true);

        $sheet->setCellValue('A2', 'Fecha de Cierre: ' . date('d/m/Y', strtotime($corteDate)));
        $sheet->mergeCells('A2:D2');

        $sheet->setCellValue('A3', 'Fecha y Hora Reg. de Apertura: ' . $corteFirstTime);
        $sheet->mergeCells('A3:D3');

        $sheet->setCellValue('A4', 'Fecha y Hora Reg. de Cierre: ' . $corteLastTime);
        $sheet->mergeCells('A4:D4');

        $sheet->setCellValue('A6', 'Resumen de Ventas');
        $sheet->getStyle('A6')->getFont()->setBold(true)->setSize(12);

        $row = 8;
        foreach ($detailedPayments as $methodName => $data) {
            if ($data['total'] <= 0) continue;

            $sheet->setCellValue('A' . $row, $methodName . ' (' . count($data['orders']) . ')');
            $sheet->setCellValue('B' . $row, $data['total']);
            $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
            $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);
            $row++;

            // Headers
            $sheet->setCellValue('B' . $row, 'Tipo Documento');
            $sheet->setCellValue('C' . $row, 'Nº Documento');
            $sheet->setCellValue('D' . $row, 'Monto Movimiento');
            $sheet->getStyle("B{$row}:D{$row}")->getFont()->setItalic(true)->setBold(true);
            $row++;

            // Orders list
            foreach ($data['orders'] as $ord) {
                $sheet->setCellValue('B' . $row, 'TICKET DE VENTA T');
                $sheet->setCellValue('C' . $row, '#' . str_pad($ord['id'], 4, '0', STR_PAD_LEFT));
                $sheet->setCellValue('D' . $row, $ord['monto']);
                $sheet->getStyle('D' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);
                $row++;
            }
            $row++; // blank line
        }

        // Total
        $sheet->setCellValue('A' . $row, 'Total');
        $sheet->setCellValue('B' . $row, $corteTotal);
        $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->setSize(11);
        $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode(self::NUMBER_FORMAT_MX);

        foreach (range('A', 'D') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $this->downloadExcel($spreadsheet, 'Corte_Diario_La501_' . $corteDate);
    }

    private static function parsePaymentMethodAmountsStatic(string $method, float $total)
    {
        $efectivo = 0.0;
        $tarjeta = 0.0;

        $methodLower = strtolower(trim($method));

        if (str_starts_with($methodLower, 'mixto')) {
            if (preg_match('/efectivo:\s*\$?\s*([\d\.]+)/i', $method, $m)) {
                $efectivo = (float) $m[1];
            }
            if (preg_match('/tarjeta:\s*\$?\s*([\d\.]+)/i', $method, $m)) {
                $tarjeta = (float) $m[1];
            }
        } elseif (str_contains($methodLower, 'efectivo')) {
            $efectivo = $total;
        } else {
            // Tarjeta, terminal, o cualquier otro método (ej. registros viejos de Mercado Pago).
            $tarjeta = $total;
        }

        return [
            'efectivo' => $efectivo,
            'tarjeta' => $tarjeta,
        ];
    }

    private function downloadExcel($spreadsheet, $name)
    {
        $fileName = $name . '_' . date('d_m_Y_His') . '.xlsx';
        
        // Limpiar buffer para evitar archivos corruptos
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $writer = new Xlsx($spreadsheet);
        $ruta = storage_path('app/'.$fileName);
        $writer->save($ruta);
        
        return response()->download($ruta, $fileName, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }
}
