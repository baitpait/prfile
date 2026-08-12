<x-layouts.app :title="'كشف حساب — '.$supplier->displayName()">
    <livewire:supplier-statement :supplier="$supplier" />
</x-layouts.app>
