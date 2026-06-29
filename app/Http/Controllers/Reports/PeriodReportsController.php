<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Services\ArabicPdfRenderer;
use App\Services\Reports\AggregatedClientStatementsService;
use App\Services\Reports\AggregatedSupplierStatementsService;
use App\Services\Reports\AsOfSummaryFilters;
use App\Services\Reports\CashflowReportService;
use App\Services\Reports\ClientAdjustmentsPeriodReportService;
use App\Services\Reports\ClientPaymentsReportService;
use App\Services\Reports\ClientReceivablesSummaryService;
use App\Services\Reports\ExpensesReportService;
use App\Services\Reports\FinancialPeriodSummaryService;
use App\Services\Reports\PurchaseOrdersPeriodReportService;
use App\Services\Reports\ReportPeriodFilters;
use App\Services\Reports\SalesPeriodReportService;
use App\Services\Reports\SupplierAdjustmentsPeriodReportService;
use App\Services\Reports\SupplierPaymentsReportService;
use App\Services\Reports\SupplierPayablesSummaryService;
use App\Services\Reports\UnifiedActivityLogService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodReportsController extends Controller
{
    use AuthorizesRequests;

    public function index(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.index');
    }

    public function cashflow(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.cashflow');
    }

    public function clientPayments(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.client-payments');
    }

    public function supplierPayments(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.supplier-payments');
    }

    public function expenses(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.expenses');
    }

    public function sales(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.sales');
    }

    public function purchaseOrders(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.purchase-orders');
    }

    public function supplierAdjustments(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.supplier-adjustments');
    }

    public function financialPeriod(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.financial-period');
    }

    public function activityLog(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.activity-log');
    }

    public function clientAdjustments(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.client-adjustments');
    }

    public function clientReceivablesSummary(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.client-receivables-summary');
    }

    public function supplierPayablesSummary(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.supplier-payables-summary');
    }

    public function aggregatedClientStatements(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.aggregated-client-statements');
    }

    public function aggregatedSupplierStatements(): View
    {
        $this->authorize('view-period-reports');

        return view('reports.aggregated-supplier-statements');
    }

    public function cashflowPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new CashflowReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.cashflow', [
            'rows' => $svc->timeline($filters),
            'summary' => $svc->summaryByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'كشف التدفق النقدي',
        ], 'cashflow-'.now()->format('Ymd-His').'.pdf', 'كشف التدفق النقدي');
    }

    public function clientPaymentsPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new ClientPaymentsReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.client-payments', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'دفعات العملاء',
        ], 'client-payments-'.now()->format('Ymd-His').'.pdf', 'دفعات العملاء');
    }

    public function supplierPaymentsPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new SupplierPaymentsReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.supplier-payments', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'دفعات الموردين',
        ], 'supplier-payments-'.now()->format('Ymd-His').'.pdf', 'دفعات الموردين');
    }

    public function expensesPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new ExpensesReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.expenses', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'المصروفات',
        ], 'expenses-'.now()->format('Ymd-His').'.pdf', 'المصروفات');
    }

    public function salesPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new SalesPeriodReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.sales', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'تقرير المبيعات',
        ], 'sales-'.now()->format('Ymd-His').'.pdf', 'تقرير المبيعات');
    }

    public function purchaseOrdersPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new PurchaseOrdersPeriodReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.purchase-orders', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'تقرير المشتريات',
        ], 'purchase-orders-'.now()->format('Ymd-His').'.pdf', 'تقرير المشتريات');
    }

    public function supplierAdjustmentsPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new SupplierAdjustmentsPeriodReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.supplier-adjustments', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'تسويات الموردين — الفترة',
        ], 'supplier-adjustments-'.now()->format('Ymd-His').'.pdf', 'تسويات الموردين');
    }

    public function financialPeriodPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new FinancialPeriodSummaryService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.financial-period', [
            'summary' => $svc->byCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'لوحة الفترة المالية',
        ], 'financial-period-'.now()->format('Ymd-His').'.pdf', 'لوحة الفترة المالية');
    }

    public function activityLogPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $result = (new UnifiedActivityLogService)->timeline($filters);

        return $this->streamPdf($pdfRenderer, 'pdf.reports.activity-log', [
            'rows' => $result['rows'],
            'truncated' => $result['truncated'],
            'total' => $result['total'],
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'سجل النشاط المالي',
        ], 'activity-log-'.now()->format('Ymd-His').'.pdf', 'سجل النشاط المالي');
    }

    public function clientAdjustmentsPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new ClientAdjustmentsPeriodReportService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.client-adjustments', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'تسويات العملاء — الفترة',
        ], 'client-adjustments-'.now()->format('Ymd-His').'.pdf', 'تسويات العملاء');
    }

    public function clientReceivablesSummaryPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = AsOfSummaryFilters::fromRequest($request);
        $svc = new ClientReceivablesSummaryService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.client-receivables-summary', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'ملخص ذمم العملاء',
        ], 'client-receivables-summary-'.now()->format('Ymd-His').'.pdf', 'ملخص ذمم العملاء');
    }

    public function supplierPayablesSummaryPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = AsOfSummaryFilters::fromRequest($request);
        $svc = new SupplierPayablesSummaryService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.supplier-payables-summary', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'ملخص ذمم الموردين',
        ], 'supplier-payables-summary-'.now()->format('Ymd-His').'.pdf', 'ملخص ذمم الموردين');
    }

    public function aggregatedClientStatementsPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new AggregatedClientStatementsService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.aggregated-client-statements', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'كشوف العملاء المجمّعة',
        ], 'aggregated-client-statements-'.now()->format('Ymd-His').'.pdf', 'كشوف العملاء المجمّعة');
    }

    public function aggregatedSupplierStatementsPdf(Request $request, ArabicPdfRenderer $pdfRenderer)
    {
        $this->authorize('export-period-reports');

        $filters = ReportPeriodFilters::fromRequest($request);
        $svc = new AggregatedSupplierStatementsService;

        return $this->streamPdf($pdfRenderer, 'pdf.reports.aggregated-supplier-statements', [
            'rows' => $svc->rows($filters),
            'totals' => $svc->totalsByCurrency($filters),
            'filterLabels' => $filters->describeActive(),
            'reportTitle' => 'كشوف الموردين المجمّعة',
        ], 'aggregated-supplier-statements-'.now()->format('Ymd-His').'.pdf', 'كشوف الموردين المجمّعة');
    }

    /** @param array<string, mixed> $data */
    private function streamPdf(ArabicPdfRenderer $pdfRenderer, string $view, array $data, string $filename, string $title)
    {
        $companyName = config('app.company_display_name', 'Profile Media Production');

        $html = view($view, array_merge($data, [
            'companyName' => $companyName,
            'printedAt' => now()->format('d/m/Y H:i'),
        ]))->render();

        return $pdfRenderer->stream($html, $filename, 'inline', $title, $companyName);
    }
}
