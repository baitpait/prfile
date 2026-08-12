<?php

namespace App\Livewire;

use App\Livewire\Concerns\FiltersClientsForSelect;
use App\Livewire\Concerns\WithClientCheckIntake;
use App\Models\ClientPayment;
use App\Models\Invoice;
use App\Services\Finance\PaymentMethod;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class PaymentForm extends Component
{
    use FiltersClientsForSelect;
    use WithClientCheckIntake;
    use WithFileUploads;

    public ?int $recordId = null;

    public string $client_id = '';

    /** account = عموم الحساب | invoice = على فاتورة */
    public string $allocation_mode = 'account';

    public string $invoice_id = '';

    public string $amount = '';

    public string $currency_code = 'ILS';

    public string $paid_at = '';

    public string $payment_method = 'cash';

    public string $bank_reference = '';

    public string $notes = '';

    public string $clientSearch = '';

    public function mount(?ClientPayment $payment = null): void
    {
        if ($payment && $payment->exists) {
            $this->authorize('update', $payment);
        } else {
            $this->authorize('create', ClientPayment::class);
        }

        if ($payment && $payment->exists) {
            $payment->loadMissing(['client', 'invoice', 'receivedCheck']);
            $this->recordId = $payment->id;
            $this->client_id = (string) $payment->client_id;
            $this->clientSearch = $payment->client?->displayName() ?? '';
            $this->amount = (string) $payment->amount;
            $this->currency_code = $payment->currency_code ?? 'ILS';
            $this->paid_at = $payment->paid_at?->format('Y-m-d') ?? '';
            $this->payment_method = PaymentMethod::normalize($payment->method);
            $this->bank_reference = $payment->bank_reference ?? '';
            $this->notes = $payment->notes ?? '';
            if ($payment->invoice_id) {
                $this->allocation_mode = 'invoice';
                $this->invoice_id = (string) $payment->invoice_id;
            }
            if ($payment->receivedCheck) {
                $check = $payment->receivedCheck;
                $this->check_bank_name = $check->bank_name;
                $this->check_drawer_name = $check->drawer_name;
                $this->check_number = $check->check_number;
                $this->check_due_date = $check->due_date?->format('Y-m-d') ?? '';
                $this->check_notes = $check->notes ?? '';
                $this->existing_check_image_path = $check->image_path;
            }
        } else {
            $this->paid_at = now()->format('Y-m-d');
            $this->prefillClientSelect(request()->integer('client'));
        }
    }

    public function updatedPaymentMethod(string $value): void
    {
        $this->handleClientCheckPaymentMethodChanged($value);
    }

    public function updatedAllocationMode(string $value): void
    {
        if ($value !== 'invoice') {
            $this->invoice_id = '';
        }
    }

    public function updatedClientId(): void
    {
        $this->invoice_id = '';
        if ($this->allocation_mode === 'invoice') {
            $this->amount = '';
        }
    }

    public function updatedCurrencyCode(): void
    {
        $this->invoice_id = '';
        if ($this->allocation_mode === 'invoice') {
            $this->amount = '';
        }
    }

    /**
     * Business Purpose: When linking to an invoice, lock amount to the full invoice total (no split).
     */
    public function updatedInvoiceId(): void
    {
        if ($this->allocation_mode !== 'invoice' || $this->invoice_id === '') {
            return;
        }

        $invoice = $this->findSelectableInvoice((int) $this->invoice_id);
        if (! $invoice) {
            $this->invoice_id = '';
            $this->amount = '';

            return;
        }

        $this->currency_code = $invoice->currency_code ?? 'ILS';
        $this->amount = number_format((float) $invoice->total_amount, 2, '.', '');
    }

    public function save(): void
    {
        $rules = [
            'client_id' => 'required|exists:clients,id',
            'allocation_mode' => 'required|in:account,invoice',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'paid_at' => 'required|date',
            'payment_method' => PaymentMethod::validationRule(),
            'invoice_id' => 'nullable',
        ];

        if ($this->allocation_mode === 'invoice') {
            $rules['invoice_id'] = [
                'required',
                'integer',
                Rule::exists('invoices', 'id')->where(function ($query) {
                    $query->where('client_id', (int) $this->client_id)
                        ->where('currency_code', $this->currency_code)
                        ->where('status', 'issued')
                        ->whereNull('deleted_at');
                }),
            ];
        }

        if ($this->payment_method === PaymentMethod::CHECK) {
            $rules = $this->mergeClientCheckValidationRules($rules);
        }

        $this->validate($rules, [], array_merge([
            'client_id' => 'العميل',
            'allocation_mode' => 'نوع الربط',
            'invoice_id' => 'الفاتورة',
            'amount' => 'المبلغ',
            'currency_code' => 'العملة',
            'paid_at' => 'تاريخ الدفع',
            'payment_method' => 'طريقة الدفع',
        ], $this->clientCheckValidationAttributes()));

        $invoiceId = null;
        if ($this->allocation_mode === 'invoice') {
            $invoice = $this->findSelectableInvoice((int) $this->invoice_id);
            if (! $invoice) {
                $this->addError('invoice_id', 'الفاتورة غير متاحة للربط (صادرة، نفس العملة، وغير مربوطة بدفعة أخرى).');

                return;
            }

            $invoiceTotal = round((float) $invoice->total_amount, 2);
            $paymentAmount = round((float) $this->amount, 2);
            if (abs($invoiceTotal - $paymentAmount) > 0.009) {
                $this->addError('amount', 'مبلغ الدفعة يجب أن يساوي إجمالي الفاتورة ('.number_format($invoiceTotal, 2).').');

                return;
            }

            $this->amount = number_format($invoiceTotal, 2, '.', '');
            $invoiceId = $invoice->id;
        }

        $bankReference = $this->payment_method === PaymentMethod::CHECK
            ? ($this->bankReferenceForCheckPayment() ?: $this->bank_reference)
            : ($this->bank_reference ?: null);

        $data = [
            'client_id' => $this->client_id,
            'invoice_id' => $invoiceId,
            'amount' => $this->amount,
            'currency_code' => $this->currency_code,
            'paid_at' => $this->paid_at,
            'method' => $this->payment_method,
            'bank_reference' => $bankReference ?: null,
            'notes' => $this->notes ?: null,
            'recorded_by_user_id' => auth()->id(),
        ];

        DB::transaction(function () use ($data): void {
            if ($this->recordId) {
                $payment = ClientPayment::findOrFail($this->recordId);
                $payment->update($data);
            } else {
                $payment = ClientPayment::create($data);
            }

            if ($this->payment_method === PaymentMethod::CHECK) {
                $this->syncClientPaymentReceivedCheck($payment);
            } elseif ($payment->receivedCheck && $payment->receivedCheck->isPending()) {
                $this->deletePendingReceivedCheckForPayment($payment);
            }
        });

        session()->flash('toast', $this->recordId ? 'تم تحديث الدفعة' : 'تم تسجيل الدفعة بنجاح');
        $this->redirect(route('payments.index'), navigate: true);
    }

    public function render()
    {
        return view('livewire.payment-form', [
            'clients' => $this->clientsForSelect(),
            'openInvoices' => $this->openInvoicesForSelect(),
        ]);
    }

    /**
     * @return Collection<int, Invoice>
     */
    protected function openInvoicesForSelect(): Collection
    {
        if ($this->client_id === '' || $this->allocation_mode !== 'invoice') {
            return collect();
        }

        $linkedInvoiceIds = ClientPayment::query()
            ->whereNotNull('invoice_id')
            ->when($this->recordId, fn ($q) => $q->where('id', '!=', $this->recordId))
            ->pluck('invoice_id');

        return Invoice::query()
            ->where('client_id', (int) $this->client_id)
            ->where('currency_code', $this->currency_code)
            ->where('status', 'issued')
            ->whereNull('deleted_at')
            ->whereNotIn('id', $linkedInvoiceIds)
            ->orderByDesc('document_date')
            ->orderByDesc('id')
            ->get();
    }

    protected function findSelectableInvoice(int $invoiceId): ?Invoice
    {
        return $this->openInvoicesForSelect()->firstWhere('id', $invoiceId);
    }
}
