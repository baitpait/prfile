<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Product;
use App\Models\Supplier;
use App\Services\PartyCurrencyConversionService;
use InvalidArgumentException;
use Livewire\Component;

class PartyCurrencyConverter extends Component
{
    public string $partyType;

    public int $partyId;

    public bool $showModal = false;

    public string $fromCurrency = '';

    public string $toCurrency = '';

    /** @var array{invoices: int, payments: int, adjustments: int, total: int}|null */
    public ?array $preview = null;

    /** @var list<string> */
    public array $availableCurrencies = [];

    public function mount(string $partyType, int $partyId): void
    {
        abort_unless(auth()->user()?->isManager(), 403);
        abort_unless(in_array($partyType, ['client', 'supplier'], true), 404);

        $this->partyType = $partyType;
        $this->partyId = $partyId;

        $service = new PartyCurrencyConversionService;
        $this->availableCurrencies = $partyType === 'client'
            ? $service->currenciesForClient(Client::findOrFail($partyId))
            : $service->currenciesForSupplier(Supplier::findOrFail($partyId));

        $this->fromCurrency = $this->availableCurrencies[0] ?? 'ILS';
        $this->toCurrency = $this->defaultTargetCurrency($this->fromCurrency);
    }

    public function openModal(): void
    {
        abort_unless(auth()->user()?->isManager(), 403);
        $this->preview = null;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->preview = null;
        $this->resetValidation();
    }

    public function updatedFromCurrency(): void
    {
        $this->preview = null;
        if ($this->fromCurrency === $this->toCurrency) {
            $this->toCurrency = $this->defaultTargetCurrency($this->fromCurrency);
        }
    }

    public function updatedToCurrency(): void
    {
        $this->preview = null;
    }

    public function runPreview(): void
    {
        abort_unless(auth()->user()?->isManager(), 403);

        $this->validate([
            'fromCurrency' => ['required', 'string', 'size:3', 'in:'.implode(',', Product::billingCurrencies())],
            'toCurrency' => ['required', 'string', 'size:3', 'in:'.implode(',', Product::billingCurrencies()), 'different:fromCurrency'],
        ], [], [
            'fromCurrency' => 'من عملة',
            'toCurrency' => 'إلى عملة',
        ]);

        try {
            $service = new PartyCurrencyConversionService;
            $this->preview = $this->partyType === 'client'
                ? $service->previewClient(Client::findOrFail($this->partyId), $this->fromCurrency, $this->toCurrency)
                : $service->previewSupplier(Supplier::findOrFail($this->partyId), $this->fromCurrency, $this->toCurrency);
        } catch (InvalidArgumentException $e) {
            $this->addError('fromCurrency', $e->getMessage());
        }
    }

    public function applyConversion(): void
    {
        abort_unless(auth()->user()?->isManager(), 403);

        $this->validate([
            'fromCurrency' => ['required', 'string', 'size:3', 'in:'.implode(',', Product::billingCurrencies())],
            'toCurrency' => ['required', 'string', 'size:3', 'in:'.implode(',', Product::billingCurrencies()), 'different:fromCurrency'],
        ], [], [
            'fromCurrency' => 'من عملة',
            'toCurrency' => 'إلى عملة',
        ]);

        try {
            $service = new PartyCurrencyConversionService;
            $userId = (int) auth()->id();

            if ($this->partyType === 'client') {
                $result = $service->applyClient(
                    Client::findOrFail($this->partyId),
                    $this->fromCurrency,
                    $this->toCurrency,
                    $userId
                );
            } else {
                $result = $service->applySupplier(
                    Supplier::findOrFail($this->partyId),
                    $this->fromCurrency,
                    $this->toCurrency,
                    $userId
                );
            }

            $this->closeModal();
            $this->dispatch('currency-converted');
            session()->flash('toast', "تم تحويل {$result['total']} سجلاً من {$this->fromCurrency} إلى {$this->toCurrency} (المبالغ دون تغيير).");
        } catch (InvalidArgumentException $e) {
            $this->addError('fromCurrency', $e->getMessage());
        }
    }

    public function render()
    {
        $documentLabel = $this->partyType === 'client' ? 'فاتورة' : 'فاتورة مشتريات';

        return view('livewire.party-currency-converter', [
            'billingCurrencies' => Product::billingCurrencies(),
            'documentLabel' => $documentLabel,
        ]);
    }

    private function defaultTargetCurrency(string $from): string
    {
        $options = array_values(array_filter(
            Product::billingCurrencies(),
            fn (string $c) => $c !== $from
        ));

        if ($from === 'ILS') {
            return 'USD';
        }

        return $options[0] ?? 'USD';
    }
}
