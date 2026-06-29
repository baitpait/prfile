@props(['pdfExportUrl', 'showMethod' => true])

<div class="flex flex-wrap items-end gap-3">
    @can('export-period-reports')
    <a href="{{ $pdfExportUrl }}" target="_blank" rel="noopener"
       class="px-4 py-2 text-sm bg-[#3D3D3D] text-white rounded hover:bg-[#2a2a2a] font-medium self-end"
       style="text-decoration:none;">
        تصدير PDF
    </a>
    <button type="button" wire:click="exportCsv"
            class="px-4 py-2 text-sm bg-white border border-[#E0E0E0] rounded hover:bg-[#F5F5F5] font-medium self-end">
        تصدير CSV
    </button>
    @endcan
</div>
