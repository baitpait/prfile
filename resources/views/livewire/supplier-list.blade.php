<div>

<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-xl font-bold text-[#3D3D3D]">الموردون</h1>
        <p class="text-sm text-gray-400 mt-0.5">{{ $rows->total() }} مورد مسجّل</p>
    </div>
    @if(auth()->user()->isAccountant())
    <button wire:click="openCreate" class="btn btn-primary">
        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        إضافة مورد
    </button>
    @endif
</div>

<div class="card px-4 py-3 mb-5 flex items-center gap-3">
    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-300 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0"/>
    </svg>
    <input wire:model.live.debounce.300ms="search" type="search"
           placeholder="بحث بالاسم، البريد، الهاتف، المدينة..."
           class="flex-1 bg-transparent text-sm focus:outline-none placeholder:text-gray-300">
    @if($search)
    <button wire:click="$set('search','')" class="text-gray-300 hover:text-gray-500 transition text-lg leading-none">&times;</button>
    @endif
</div>

<div class="card overflow-hidden">
    <div wire:loading.delay class="h-0.5 bg-[#C9A227]/20 relative overflow-hidden">
        <div class="absolute inset-y-0 right-0 w-1/3 bg-[#C9A227] animate-pulse"></div>
    </div>
    <table class="data-table">
        <thead><tr>
            <th>الاسم</th><th>البريد</th><th>الهاتف</th><th>المدينة</th><th class="w-28"></th>
        </tr></thead>
        <tbody>
            @forelse($rows as $s)
            <tr>
                <td class="font-semibold">{{ $s->displayName() }}</td>
                <td class="text-gray-500">{{ $s->email ?? '—' }}</td>
                <td class="text-gray-500 font-mono text-xs" dir="ltr">{{ $s->phone_primary ?? '—' }}</td>
                <td class="text-gray-500">{{ $s->city ?? '—' }}</td>
                <td>
                    <div class="flex items-center gap-1 justify-end">
                        <button wire:click="openView({{ $s->id }})" class="btn btn-ghost py-1 px-2 text-xs text-gray-500 hover:bg-gray-50">عرض</button>
                        @if(auth()->user()->isAccountant())
                        <button wire:click="openEdit({{ $s->id }})" class="btn btn-ghost py-1 px-2 text-xs text-blue-600 hover:bg-blue-50">تعديل</button>
                        @endif
                        @if(auth()->user()->isManager())
                        <button wire:click="confirmDelete({{ $s->id }})" class="btn btn-ghost py-1 px-2 text-xs text-red-500 hover:bg-red-50">حذف</button>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="5">
                <div class="text-center py-16 text-gray-300">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-12 h-12 mx-auto mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                    <p class="text-sm">{{ $search ? 'لا توجد نتائج' : 'لا يوجد موردون بعد' }}</p>
                </div>
            </td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@if($rows->hasPages())<div class="mt-5">{{ $rows->links() }}</div>@endif

{{-- ══ نافذة العرض التفصيلي ══ --}}
@if($viewingId !== null)
<div wire:key="view-{{ $viewingId }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeView"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg mx-0 sm:mx-4 z-10 max-h-[90vh] overflow-y-auto">
            @if($viewingRecord)
            @php $sup = $viewingRecord; @endphp
            <div class="p-6">
                <div class="flex items-start justify-between mb-5">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-xl bg-purple-50 flex items-center justify-center text-purple-600 font-bold text-lg shrink-0">
                            {{ mb_substr($sup->displayName(), 0, 1) }}
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-[#3D3D3D]">{{ $sup->displayName() }}</h2>
                            @if($sup->city)<p class="text-sm text-gray-400">{{ $sup->city }}</p>@endif
                        </div>
                    </div>
                    <button wire:click="closeView" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition shrink-0">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="bg-[#F9F9FB] rounded-xl p-4 mb-4 space-y-2">
                    <p class="text-xs font-bold text-gray-400 uppercase tracking-wider mb-3">معلومات التواصل</p>
                    @if($sup->email)
                    <div class="flex items-center gap-2 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span dir="ltr">{{ $sup->email }}</span>
                    </div>
                    @endif
                    @if($sup->phone_primary)
                    <div class="flex items-center gap-2 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span dir="ltr">{{ $sup->phone_primary }}</span>
                    </div>
                    @endif
                    @if($sup->phone_secondary)
                    <div class="flex items-center gap-2 text-sm">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 text-gray-400 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span dir="ltr">{{ $sup->phone_secondary }}</span>
                    </div>
                    @endif
                    @if(!$sup->email && !$sup->phone_primary)<p class="text-sm text-gray-400">لا توجد معلومات تواصل</p>@endif
                </div>
                @if($sup->notes)
                <div class="bg-amber-50 rounded-xl p-3 mb-4">
                    <p class="text-xs font-bold text-amber-600 mb-1">ملاحظات</p>
                    <p class="text-sm text-gray-700">{{ $sup->notes }}</p>
                </div>
                @endif
                <div class="flex justify-end gap-2 pt-4 border-t border-[#E2E4E9]">
                    <button wire:click="closeView" class="btn btn-secondary text-xs">إغلاق</button>
                    @if(auth()->user()->isAccountant())
                    <button wire:click="openEdit({{ $sup->id }})" class="btn btn-primary text-xs">تعديل</button>
                    @endif
                </div>
            </div>
            @endif
    </div>
</div>
@endif

{{-- مودال --}}
@if($showModal)
<div wire:key="form-{{ $editingId ?? 'new' }}"
     class="fixed inset-0 z-50 flex items-end sm:items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="closeModal"></div>
    <div class="relative bg-white rounded-t-2xl sm:rounded-2xl shadow-2xl w-full max-w-lg mx-0 sm:mx-4 z-10 max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-bold text-[#3D3D3D]">{{ $editingId ? 'تعديل بيانات المورد' : 'إضافة مورد جديد' }}</h2>
                    <button wire:click="closeModal" class="w-8 h-8 flex items-center justify-center rounded-full hover:bg-gray-100 text-gray-400 transition">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="col-span-2 form-group">
                        <label class="label">اسم الشركة</label>
                        <input wire:model="business_name" type="text" class="input">
                        @error('business_name')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group"><label class="label">الاسم الأول</label><input wire:model="first_name" type="text" class="input"></div>
                    <div class="form-group"><label class="label">الاسم الأخير</label><input wire:model="last_name" type="text" class="input"></div>
                    <div class="form-group">
                        <label class="label">البريد الإلكتروني</label>
                        <input wire:model="email" type="email" dir="ltr" class="input">
                        @error('email')<p class="field-error">{{ $message }}</p>@enderror
                    </div>
                    <div class="form-group">
                        <label class="label">الهاتف الرئيسي</label>
                        <input wire:model="phone_primary" type="tel" dir="ltr" class="input">
                    </div>
                    <div class="form-group"><label class="label">الهاتف الثانوي</label><input wire:model="phone_secondary" type="tel" dir="ltr" class="input"></div>
                    <div class="form-group"><label class="label">المدينة</label><input wire:model="city" type="text" class="input"></div>
                    <div class="col-span-2 form-group"><label class="label">ملاحظات</label><textarea wire:model="notes" rows="2" class="input"></textarea></div>
                </div>
                <div class="flex justify-end gap-2 mt-2 pt-4 border-t border-[#E2E4E9]">
                    <button wire:click="closeModal" class="btn btn-secondary">إلغاء</button>
                    <button wire:click="save" wire:loading.attr="disabled" class="btn btn-primary">
                        <svg wire:loading wire:target="save" xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path></svg>
                        <span wire:loading.remove wire:target="save">حفظ</span>
                        <span wire:loading wire:target="save">جاري الحفظ...</span>
                    </button>
                </div>
            </div>
    </div>
</div>
@endif

{{-- تأكيد الحذف --}}
@if($confirmDeleteId !== null)
<div wire:key="delete-{{ $confirmDeleteId }}"
     class="fixed inset-0 z-[60] flex items-center justify-center">
    <div class="fixed inset-0 bg-black/40 backdrop-blur-[2px]" wire:click="cancelDelete"></div>
    <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-sm mx-4 z-10 p-6">
        <div class="w-12 h-12 rounded-full bg-red-50 flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </div>
        <h3 class="text-base font-bold text-center mb-1">حذف المورد</h3>
        <p class="text-sm text-gray-400 text-center mb-5">هل أنت متأكد؟ يمكن استعادة السجل لاحقاً.</p>
        <div class="flex gap-2">
            <button wire:click="cancelDelete" class="btn btn-secondary flex-1">إلغاء</button>
            <button wire:click="delete" class="btn btn-danger flex-1">حذف</button>
        </div>
    </div>
</div>
@endif

</div>

