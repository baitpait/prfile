<?php

namespace App\Services\Reports;

use App\Models\ClientBalanceAdjustment;
use App\Models\ClientPayment;
use App\Models\Expense;
use App\Models\Invoice;
use App\Models\PurchaseOrder;
use App\Models\SupplierBalanceAdjustment;
use App\Models\SupplierPayment;
use Illuminate\Support\Collection;

/**
 * Business Purpose: Chronological ledger of all financial document types in a period.
 */
class UnifiedActivityLogService
{
    public const MAX_ROWS = 500;

    /**
     * @return array{rows: Collection<int, array<string, mixed>>, truncated: bool, total: int}
     */
    public function timeline(ReportPeriodFilters $filters, ?int $limit = null): array
    {
        $limit ??= self::MAX_ROWS;
        $events = collect();

        $from = $filters->resolvedDateFrom();
        $to = $filters->resolvedDateTo();

        $invoices = Invoice::query()
            ->with('client')
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->when($filters->clientId, fn ($q) => $q->where('client_id', $filters->clientId))
            ->orderBy('document_date')
            ->get();

        foreach ($invoices as $inv) {
            $ref = $inv->legacy_invoice_no ?? '#'.$inv->id;
            $events->push([
                'sort' => $inv->document_date->format('Y-m-d').'_0_'.$inv->id,
                'type' => 'invoice',
                'type_label' => 'فاتورة صادرة',
                'category' => 'sales',
                'date' => $inv->document_date,
                'party' => $inv->client?->displayName() ?? '—',
                'reference' => $ref,
                'currency' => $inv->currency_code,
                'amount' => (float) $inv->total_amount,
                'signed_amount' => (float) $inv->total_amount,
            ]);
        }

        $orders = PurchaseOrder::query()
            ->with('supplier')
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereDate('document_date', '>=', $from)
            ->whereDate('document_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->when($filters->supplierId, fn ($q) => $q->where('supplier_id', $filters->supplierId))
            ->orderBy('document_date')
            ->get();

        foreach ($orders as $po) {
            $ref = $po->legacy_po_no ?? '#'.$po->id;
            $events->push([
                'sort' => $po->document_date->format('Y-m-d').'_1_'.$po->id,
                'type' => 'purchase_order',
                'type_label' => 'أمر شراء صادر',
                'category' => 'purchases',
                'date' => $po->document_date,
                'party' => $po->supplier?->displayName() ?? '—',
                'reference' => $ref,
                'currency' => $po->currency_code,
                'amount' => (float) $po->total_amount,
                'signed_amount' => (float) $po->total_amount,
            ]);
        }

        $clientPayments = ClientPayment::query()
            ->with('client')
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->when($filters->clientId, fn ($q) => $q->where('client_id', $filters->clientId))
            ->when($filters->method, fn ($q) => $q->where('method', $filters->method))
            ->orderBy('paid_at')
            ->get();

        foreach ($clientPayments as $pay) {
            $events->push([
                'sort' => $pay->paid_at->format('Y-m-d H:i:s').'_2_'.$pay->id,
                'type' => 'client_payment',
                'type_label' => 'دفعة عميل',
                'category' => 'cash_in',
                'date' => $pay->paid_at,
                'party' => $pay->client?->displayName() ?? '—',
                'reference' => $pay->bank_reference ?? '#'.$pay->id,
                'currency' => $pay->currency_code,
                'amount' => (float) $pay->amount,
                'signed_amount' => (float) $pay->amount,
            ]);
        }

        $supplierPayments = SupplierPayment::query()
            ->with('supplier')
            ->whereNull('deleted_at')
            ->whereDate('paid_at', '>=', $from)
            ->whereDate('paid_at', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->when($filters->supplierId, fn ($q) => $q->where('supplier_id', $filters->supplierId))
            ->when($filters->method, fn ($q) => $q->where('method', $filters->method))
            ->orderBy('paid_at')
            ->get();

        foreach ($supplierPayments as $pay) {
            $events->push([
                'sort' => $pay->paid_at->format('Y-m-d H:i:s').'_3_'.$pay->id,
                'type' => 'supplier_payment',
                'type_label' => 'دفعة مورد',
                'category' => 'cash_out',
                'date' => $pay->paid_at,
                'party' => $pay->supplier?->displayName() ?? '—',
                'reference' => $pay->bank_reference ?? '#'.$pay->id,
                'currency' => $pay->currency_code,
                'amount' => (float) $pay->amount,
                'signed_amount' => -1 * (float) $pay->amount,
            ]);
        }

        $expenses = Expense::query()
            ->whereNull('deleted_at')
            ->whereDate('expense_date', '>=', $from)
            ->whereDate('expense_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->orderBy('expense_date')
            ->get();

        foreach ($expenses as $exp) {
            $events->push([
                'sort' => $exp->expense_date->format('Y-m-d').'_4_'.$exp->id,
                'type' => 'expense',
                'type_label' => 'مصروف',
                'category' => 'cash_out',
                'date' => $exp->expense_date,
                'party' => $exp->description,
                'reference' => '#'.$exp->id,
                'currency' => $exp->currency_code,
                'amount' => (float) $exp->amount,
                'signed_amount' => -1 * (float) $exp->amount,
            ]);
        }

        $clientAdjustments = ClientBalanceAdjustment::query()
            ->with('client')
            ->whereNull('deleted_at')
            ->whereDate('adjustment_date', '>=', $from)
            ->whereDate('adjustment_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->when($filters->clientId, fn ($q) => $q->where('client_id', $filters->clientId))
            ->orderBy('adjustment_date')
            ->get();

        foreach ($clientAdjustments as $adj) {
            $events->push([
                'sort' => $adj->adjustment_date->format('Y-m-d').'_5_'.$adj->id,
                'type' => 'client_adjustment',
                'type_label' => 'تسوية عميل',
                'category' => 'adjustment',
                'date' => $adj->adjustment_date,
                'party' => $adj->client?->displayName() ?? '—',
                'reference' => '#'.$adj->id.' ('.$adj->typeLabel().')',
                'currency' => $adj->currency_code,
                'amount' => (float) $adj->amount,
                'signed_amount' => -1 * (float) $adj->amount,
            ]);
        }

        $supplierAdjustments = SupplierBalanceAdjustment::query()
            ->with('supplier')
            ->whereNull('deleted_at')
            ->whereDate('adjustment_date', '>=', $from)
            ->whereDate('adjustment_date', '<=', $to)
            ->when($filters->currency, fn ($q) => $q->where('currency_code', $filters->currency))
            ->when($filters->supplierId, fn ($q) => $q->where('supplier_id', $filters->supplierId))
            ->orderBy('adjustment_date')
            ->get();

        foreach ($supplierAdjustments as $adj) {
            $events->push([
                'sort' => $adj->adjustment_date->format('Y-m-d').'_6_'.$adj->id,
                'type' => 'supplier_adjustment',
                'type_label' => 'تسوية مورد',
                'category' => 'adjustment',
                'date' => $adj->adjustment_date,
                'party' => $adj->supplier?->displayName() ?? '—',
                'reference' => '#'.$adj->id.' ('.$adj->typeLabel().')',
                'currency' => $adj->currency_code,
                'amount' => (float) $adj->amount,
                'signed_amount' => -1 * (float) $adj->amount,
            ]);
        }

        $sorted = $events->sortBy('sort')->values();
        $total = $sorted->count();
        $truncated = $total > $limit;

        return [
            'rows' => $sorted->take($limit)->values(),
            'truncated' => $truncated,
            'total' => $total,
        ];
    }

    /** @return list<string> */
    public function currencyOptions(): array
    {
        return \App\Models\Product::billingCurrencies();
    }
}
