{{-- Business Purpose: Filters for payment lists (client or supplier) and expenses. --}}
@php
    $isClient = ($partyType ?? 'client') === 'client';
    $dateLabel = $dateLabel ?? 'تاريخ الدفع';
    $partyLabel = $isClient ? 'العميل' : 'المورد';
    $partySearchPlaceholder = $isClient ? 'ابحث باسم العميل...' : 'ابحث باسم المورد...';
    $partyAllLabel = $isClient ? 'كل العملاء' : 'كل الموردين';
@endphp

<div class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            @if($showParty ?? true)
            <div class="min-w-0">
                <label class="label">بحث {{ $partyLabel }}</label>
                @if($isClient)
                <input type="search" wire:model.live.debounce.300ms="clientSearch" class="input w-full text-sm" placeholder="{{ $partySearchPlaceholder }}" autocomplete="off">
                @else
                <input type="search" wire:model.live.debounce.300ms="supplierSearch" class="input w-full text-sm" placeholder="{{ $partySearchPlaceholder }}" autocomplete="off">
                @endif
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">{{ $partyLabel }}</label>
                    @if($isClient)
                    <select wire:model.live="filterClientId" class="input w-full">
                        <option value="">{{ $partyAllLabel }}</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}">{{ $party->displayName() }}</option>
                        @endforeach
                    </select>
                    @else
                    <select wire:model.live="filterSupplierId" class="input w-full">
                        <option value="">{{ $partyAllLabel }}</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}">{{ $party->displayName() }}</option>
                        @endforeach
                    </select>
                    @endif
                </div>
                @if($showMethod ?? true)
                <div class="min-w-0">
                    <label class="label">طريقة الدفع</label>
                    <select wire:model.live="filterMethod" class="input w-full">
                        <option value="">الكل</option>
                        <option value="cash">نقدي</option>
                        <option value="bank">بنكي</option>
                        <option value="check">شيك</option>
                        <option value="transfer">تحويل</option>
                    </select>
                </div>
                @endif
            </div>
            @endif

            @if(count($currencies) > 0)
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2 {{ ($showParty ?? true) || ($showMethod ?? true) ? '' : '' }}">
                <div class="min-w-0 {{ !($showParty ?? true) && !($showMethod ?? true) ? 'sm:col-span-1' : 'sm:col-span-2 md:col-span-1' }}">
                    <label class="label">العملة</label>
                    <select wire:model.live="filterCurrency" class="input w-full">
                        <option value="">كل العملات</option>
                        @foreach($currencies as $code)
                            <option value="{{ $code }}">{{ $code }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            @endif

            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">من {{ $dateLabel }}</label>
                    <input wire:model.live="filterDateFrom" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى {{ $dateLabel }}</label>
                    <input wire:model.live="filterDateTo" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @if($this->hasActiveListFilters())
        <button type="button" wire:click="clearListFilters" class="btn btn-secondary shrink-0 self-start whitespace-nowrap lg:self-end">
            مسح الفلاتر
        </button>
        @endif
    </div>
</div>
