{{-- Business Purpose: Shared search + filters for client/supplier directory lists. --}}
<div class="card p-4 mb-5 space-y-4">
    <div class="flex items-center gap-3">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
        </svg>
        <input wire:model.live.debounce.300ms="search"
               type="search"
               placeholder="{{ $searchPlaceholder }}"
               class="flex-1 bg-transparent text-sm focus:outline-none placeholder:text-gray-300"
               autocomplete="off">
        @if($search !== '')
        <button type="button" wire:click="$set('search','')" class="text-gray-300 hover:text-gray-500 transition text-lg leading-none" aria-label="مسح البحث">&times;</button>
        @endif
    </div>

    <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
        <div class="grid min-w-0 flex-1 grid-cols-1 gap-3 sm:grid-cols-2">
            <div class="min-w-0">
                <label class="label">المدينة</label>
                <select wire:model.live="filterCity" class="input w-full">
                    <option value="">كل المدن</option>
                    @foreach($cities as $city)
                        <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                </select>
            </div>
            <div class="min-w-0">
                <label class="label">الترتيب</label>
                <select wire:model.live="sort" class="input w-full">
                    <option value="newest">الأحدث أولاً</option>
                    <option value="name">حسب الاسم (أ–ي)</option>
                </select>
            </div>
        </div>
        @if($this->hasActivePartyFilters())
        <button type="button" wire:click="clearPartyFilters" class="btn btn-secondary shrink-0 self-start whitespace-nowrap sm:self-end">
            مسح الفلاتر
        </button>
        @endif
    </div>
</div>
