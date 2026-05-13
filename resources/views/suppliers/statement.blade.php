<x-layouts.app :title="'كشف حساب مورد — '.$supplier->displayName()">
    <livewire:supplier-statement :supplier="$supplier" />
</x-layouts.app>
