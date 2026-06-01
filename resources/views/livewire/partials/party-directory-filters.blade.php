{{-- Business Purpose: Shared search + filters for client/supplier directory lists. --}}
<form wire:submit.prevent="applyPartyFilters" class="card p-4 mb-5 space-y-4">
    <div class="min-w-0">
        <label class="label">بحث</label>
        <input wire:model="search"
               type="search"
               placeholder="{{ $searchPlaceholder }}"
               class="input w-full"
               autocomplete="off">
    </div>

    <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="min-w-0">
                <label class="label">المدينة</label>
                <select wire:model="filterCity" class="input w-full">
                    <option value="">كل المدن</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="label">الترتيب</label>
                <select wire:model="sort" class="input w-full">
                    <option value="newest">الأحدث أولاً</option>
                    <option value="name">حسب الاسم (أ–ي)</option>
                </select>
            </div>
        </div>
        @include('livewire.partials.list-filter-actions', [
            'applyMethod' => 'applyPartyFilters',
            'clearMethod' => 'clearPartyFilters',
            'showClear' => $this->hasActivePartyFilters(),
        ])
    </div>
</form>
