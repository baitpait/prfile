<x-layouts.app title="تفاصيل المورد">
<div class="max-w-2xl mx-auto">

    <div class="flex items-center gap-3 mb-6">
        <a href="{{ route('suppliers.index') }}" wire:navigate
           style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;border-radius:8px;background:#fff;border:1px solid #E2E4E9;color:#9CA3AF;text-decoration:none;transition:color .15s;"
           onmouseover="this.style.color='#3D3D3D'" onmouseout="this.style.color='#9CA3AF'">
            <svg xmlns="http://www.w3.org/2000/svg" style="width:16px;height:16px;" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <div>
            <h1 class="text-xl font-bold text-[#3D3D3D]">تفاصيل المورد</h1>
        </div>
    </div>

    <div class="card overflow-hidden">
        <div style="background:linear-gradient(135deg,#3D3D3D 0%,#1a1a1a 100%);padding:20px 24px;position:relative;overflow:hidden;">
            <div style="position:absolute;inset:0;opacity:.06;background:radial-gradient(circle at 30% 50%,#C9A227 0%,transparent 60%);pointer-events:none;"></div>
            <div style="display:flex;align-items:center;gap:14px;position:relative;">
                <div style="width:48px;height:48px;border-radius:14px;background:linear-gradient(135deg,#7C3AED,#9333EA);display:flex;align-items:center;justify-content:center;font-size:20px;font-weight:900;color:#fff;flex-shrink:0;box-shadow:0 4px 14px rgba(124,58,237,.4);">
                    {{ mb_substr($supplier->displayName(), 0, 1) }}
                </div>
                <div>
                    <h2 style="font-size:17px;font-weight:800;color:#fff;">{{ $supplier->displayName() }}</h2>
                    @if($supplier->city)
                    <p style="font-size:12px;color:rgba(255,255,255,.5);margin-top:2px;">{{ $supplier->city }}</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-6">
            <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-4">معلومات التواصل</p>
            <div class="space-y-3 mb-6">
                @if($supplier->email)
                <a href="mailto:{{ $supplier->email }}" class="flex items-center gap-3 p-3 bg-[#F9F9FB] rounded-xl hover:bg-gray-100 transition" style="text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C9A227] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    <span class="text-sm text-[#3D3D3D]" dir="ltr">{{ $supplier->email }}</span>
                </a>
                @endif
                @if($supplier->phone_primary)
                <a href="tel:{{ $supplier->phone_primary }}" class="flex items-center gap-3 p-3 bg-[#F9F9FB] rounded-xl hover:bg-gray-100 transition" style="text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-[#C9A227] shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span class="text-sm text-[#3D3D3D]" dir="ltr">{{ $supplier->phone_primary }}</span>
                </a>
                @endif
                @if($supplier->phone_secondary)
                <div class="flex items-center gap-3 p-3 bg-[#F9F9FB] rounded-xl">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                    <span class="text-sm text-gray-500" dir="ltr">{{ $supplier->phone_secondary }}</span>
                </div>
                @endif
                @if(!$supplier->email && !$supplier->phone_primary)
                <p class="text-sm text-gray-300 text-center py-4">لا توجد معلومات تواصل</p>
                @endif
            </div>

            @if($supplier->notes)
            <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                <p class="text-xs font-bold text-amber-600 mb-1">ملاحظات</p>
                <p class="text-sm text-amber-900">{{ $supplier->notes }}</p>
            </div>
            @endif

            <div class="flex justify-end gap-2 pt-4 border-t border-[#E2E4E9]">
                <a href="{{ route('suppliers.index') }}" wire:navigate class="btn btn-secondary" style="text-decoration:none;">رجوع</a>
                @if(auth()->user()->isAccountant())
                <a href="{{ route('suppliers.edit', $supplier->id) }}" wire:navigate class="btn btn-primary" style="text-decoration:none;">تعديل</a>
                @endif
            </div>
        </div>
    </div>

</div>
</x-layouts.app>
