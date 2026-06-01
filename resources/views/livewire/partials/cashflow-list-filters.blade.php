{{-- Business Purpose: Filters for payment lists (client or supplier) and expenses. --}}
@php
    $isClient = ($partyType ?? 'client') === 'client';
    $dateLabel = $dateLabel ?? 'تاريخ الدفع';
    $partyLabel = $isClient ? 'العميل' : 'المورد';
    $partySearchPlaceholder = $isClient ? 'ابحث باسم العميل...' : 'ابحث باسم المورد...';
    $partyAllLabel = $isClient ? 'كل العملاء' : 'كل الموردين';
    $applyMethod = $applyMethod ?? 'applyListFilters';
    $clearMethod = $clearMethod ?? 'clearListFilters';
@endphp

<x-list-filter-form applyMethod="{{ $applyMethod }}" class="card p-4 mb-5">
    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between lg:gap-6">
        <div class="flex min-w-0 flex-1 flex-col gap-3">
            @if(!empty($generalSearchPlaceholder))
            <div class="min-w-0">
                <label class="label">بحث عام</label>
                <input type="text" wire:model.blur="searchDraft" class="input w-full text-sm" placeholder="{{ $generalSearchPlaceholder }}" autocomplete="off">
            </div>
            @endif

            @if($showParty ?? true)
            <div class="min-w-0">
                <label class="label">بحث {{ $partyLabel }}</label>
                @if($isClient)
                <input type="text" wire:model.blur="clientSearchDraft" class="input w-full text-sm" placeholder="{{ $partySearchPlaceholder }}" autocomplete="off">
                @else
                <input type="text" wire:model.blur="supplierSearchDraft" class="input w-full text-sm" placeholder="{{ $partySearchPlaceholder }}" autocomplete="off">
                @endif
            </div>

            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">{{ $partyLabel }}</label>
                    @if($isClient)
                    <select wire:model.change="filterClientIdDraft" class="input w-full">
                        <option value="">{{ $partyAllLabel }}</option>
                        @foreach($parties as $party)
                            <option value="{{ $party->id }}">{{ $party->displayName() }}</option>
                        @endforeach
                    </select>
                    @else
                    <select wire:model.change="filterSupplierIdDraft" class="input w-full">
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
                    <select wire:model.change="filterMethodDraft" class="input w-full">
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
            <div class="grid min-w-0 grid-cols-1 gap-3 sm:grid-cols-2">
                <div class="min-w-0">
                    <label class="label">العملة</label>
                    <select wire:model.change="filterCurrencyDraft" class="input w-full">
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
                    <input wire:model.blur="filterDateFromDraft" type="date" class="input w-full" dir="ltr">
                </div>
                <div class="min-w-0">
                    <label class="label">إلى {{ $dateLabel }}</label>
                    <input wire:model.blur="filterDateToDraft" type="date" class="input w-full" dir="ltr">
                </div>
            </div>
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => $applyMethod,
            'clearMethod' => $clearMethod,
            'showClear' => $this->hasActiveListFilters(),
        ])
    </div>
</x-list-filter-form>
