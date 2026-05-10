@props(['route', 'label', 'active' => false, 'icon' => null])

<a href="{{ $route }}"
   class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm font-medium transition-all
          {{ $active
             ? 'bg-[#C9A227] text-white shadow-sm'
             : 'text-gray-500 hover:text-[#3D3D3D] hover:bg-[#F5F5F5]' }}">
    @if(isset($icon))
    <span class="{{ $active ? 'text-white' : 'text-gray-400' }} shrink-0 transition-colors">
        {{ $icon }}
    </span>
    @endif
    <span>{{ $label }}</span>
</a>
