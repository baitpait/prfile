<x-layouts.app title="العملاء">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-[#3D3D3D]">العملاء</h1>
    </div>

    <div class="bg-white border border-[#E0E0E0] rounded overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-[#F5F5F5]">
                <tr>
                    <th class="text-right px-4 py-3 font-semibold">الاسم</th>
                    <th class="text-right px-4 py-3 font-semibold">البريد</th>
                    <th class="text-right px-4 py-3 font-semibold">الهاتف</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody>
                @foreach(\App\Models\Client::latest()->paginate(20) as $client)
                <tr class="border-t border-[#E0E0E0] hover:bg-[#F5F5F5]">
                    <td class="px-4 py-2 font-medium">{{ $client->displayName() }}</td>
                    <td class="px-4 py-2 text-gray-500">{{ $client->email ?? '—' }}</td>
                    <td class="px-4 py-2 text-gray-500" dir="ltr">{{ $client->phone_primary ?? '—' }}</td>
                    <td class="px-4 py-2 text-left">
                        <a href="{{ route('clients.statement', $client) }}"
                           class="text-xs text-[#C9A227] hover:underline font-medium">
                            كشف الحساب
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @if(\App\Models\Client::count() === 0)
        <div class="text-center py-8 text-gray-400 text-sm">لا يوجد عملاء بعد.</div>
        @endif
    </div>
</x-layouts.app>
