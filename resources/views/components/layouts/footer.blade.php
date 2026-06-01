@php
    $developerUrl = config('app.developer_url', 'https://baitpait.com');
    $developerCredit = config('app.developer_credit', 'تطوير وبرمجة بيت البرمجيات وتكنولوجيا المعلومات');
    $dark = $dark ?? false;
@endphp

<footer class="shrink-0 border-t px-5 py-3 text-center text-xs
    {{ $dark ? 'border-white/10 bg-transparent text-white/40' : 'border-[#E2E4E9] bg-[#FAFAFA] text-gray-500' }}">
    <a href="{{ $developerUrl }}"
       target="_blank"
       rel="noopener noreferrer"
       class="font-medium transition {{ $dark ? 'text-[#C9A227] hover:text-[#dfc060]' : 'text-[#C9A227] hover:text-[#B8941F]' }}"
       style="text-decoration:none;">
        {{ $developerCredit }}
    </a>
</footer>
