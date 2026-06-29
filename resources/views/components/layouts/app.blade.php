<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @php $appName = config('app.name', 'بروفايل ميديا'); @endphp
    <title>{{ ($title ?? '') ? $title . ' — ' . $appName : $appName }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">
    @livewireScriptConfig
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="min-h-screen font-sans antialiased flex flex-col">

{{-- ═══ شريط التنقل العلوي ═══ --}}
<header class="bg-white border-b border-[#E2E4E9] h-14 flex items-center px-5 sticky top-0 z-30 shadow-sm">
    <div class="flex items-center gap-3 flex-1">
        <img src="{{ asset('branding/logo.png') }}" alt="{{ $appName }}" class="h-8 w-auto" onerror="this.style.display='none'">
        <div class="flex flex-col leading-tight">
            <span class="text-sm font-bold text-[#3D3D3D]">{{ $appName }}</span>
            <span class="text-[10px] text-[#C9A227] font-medium tracking-wide">إنتاج إعلامي وتقارير تشغيلية</span>
        </div>
    </div>
    @auth
    <div class="flex items-center gap-3 shrink-0">
        <div class="hidden sm:block text-left">
            <div class="text-xs font-semibold text-[#3D3D3D]">{{ auth()->user()->full_name }}</div>
            <div class="text-[10px] text-gray-400">
                {{ match(auth()->user()->role) { 'manager' => 'مدير', 'accountant' => 'محاسب', default => 'مشاهد' } }}
            </div>
        </div>
        <div class="w-8 h-8 rounded-full bg-[#C9A227]/15 flex items-center justify-center text-[#C9A227] font-bold text-sm">
            {{ mb_substr(auth()->user()->full_name, 0, 1) }}
        </div>
        <form method="POST" action="{{ route('logout') }}" class="mr-1">
            @csrf
            <button type="submit" title="خروج"
                    class="w-8 h-8 flex items-center justify-center rounded-lg text-gray-400 hover:text-red-500 hover:bg-red-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                </svg>
            </button>
        </form>
    </div>
    @endauth
</header>

{{-- ═══ الهيكل الرئيسي ═══ --}}
<div class="flex flex-1 min-h-0">

    {{-- ═══ القائمة الجانبية ═══ --}}
    <aside class="w-56 bg-white border-l border-[#E2E4E9] hidden md:flex flex-col py-3 shrink-0">
        <nav class="flex-1 px-3 space-y-0.5 overflow-y-auto">

            @php
            $link = fn(string $route, string $label, string $icon, string $match = '') =>
                view('components.nav-link', [
                    'route'  => $route,
                    'label'  => $label,
                    'icon'   => $icon,
                    'active' => request()->routeIs($match ?: ($route . '*')),
                ]);
            @endphp

            {{-- لوحة التحكم --}}
            <x-nav-link :route="route('dashboard')" label="لوحة التحكم"
                        :active="request()->routeIs('dashboard')" match="dashboard">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <div class="pt-4 pb-1 px-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">المبيعات</div>

            <x-nav-link :route="route('clients.index')" label="العملاء" :active="request()->routeIs('clients.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('invoices.index')" label="الفواتير" :active="request()->routeIs('invoices.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('products.index')" label="الخدمات" :active="request()->routeIs('products.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('payments.index')" label="دفعات العملاء" :active="request()->routeIs('payments.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            @if(auth()->user()->isAccountant())
            <x-nav-link :route="route('client-adjustments.index')" label="تسويات العملاء"
                        :active="request()->routeIs(['client-adjustments.*', 'clients.adjustments.*'])">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 14l6-6m-5.5.5h.01m4.99 4.99h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>
            @endif

            <div class="pt-4 pb-1 px-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">المشتريات</div>

            <x-nav-link :route="route('suppliers.index')" label="الموردون" :active="request()->routeIs('suppliers.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('purchase-orders.index')" label="فواتير المشتريات" :active="request()->routeIs('purchase-orders.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('supplier-payments.index')" label="دفعات الموردين" :active="request()->routeIs('supplier-payments.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            @if(auth()->user()->isAccountant())
            <x-nav-link :route="route('supplier-adjustments.index')" label="تسويات الموردين"
                        :active="request()->routeIs(['supplier-adjustments.*', 'suppliers.adjustments.*'])">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 14l6-6m-5.5.5h.01m4.99 4.99h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>
            @endif

            <div class="pt-4 pb-1 px-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">المالية</div>

            <x-nav-link :route="route('financial-summary')" label="صناديق العملات"
                        :active="request()->routeIs('financial-summary')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('expenses.index')" label="المصروفات" :active="request()->routeIs('expenses.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            @can('view-period-reports')
            <div class="pt-4 pb-1 px-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">التقارير</div>

            <x-nav-link :route="route('reports.index')" label="مركز التقارير"
                        :active="request()->routeIs('reports.index')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.financial-period')" label="لوحة الفترة"
                        :active="request()->routeIs('reports.financial-period')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.activity-log')" label="سجل النشاط"
                        :active="request()->routeIs('reports.activity-log')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.cashflow')" label="التدفق النقدي"
                        :active="request()->routeIs('reports.cashflow')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.client-payments')" label="دفعات العملاء"
                        :active="request()->routeIs('reports.client-payments')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.supplier-payments')" label="دفعات الموردين"
                        :active="request()->routeIs('reports.supplier-payments')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.expenses')" label="تقرير المصروفات"
                        :active="request()->routeIs('reports.expenses')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M17 13l-5 5m0 0l-5-5m5 5V6"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.sales')" label="تقرير المبيعات"
                        :active="request()->routeIs('reports.sales')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.purchase-orders')" label="تقرير المشتريات"
                        :active="request()->routeIs('reports.purchase-orders')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.client-receivables-aging')" label="أعمار ذمم العملاء"
                        :active="request()->routeIs('reports.client-receivables-aging')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.supplier-receivables-aging')" label="أعمار ذمم الموردين"
                        :active="request()->routeIs('reports.supplier-receivables-aging')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.supplier-adjustments')" label="تسويات الموردين"
                        :active="request()->routeIs('reports.supplier-adjustments')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 14l6-6m-5.5.5h.01m4.99 4.99h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>
            <x-nav-link :route="route('reports.client-receivables-summary')" label="ملخص ذمم العملاء"
                        :active="request()->routeIs('reports.client-receivables-summary')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.aggregated-client-statements')" label="كشوف العملاء"
                        :active="request()->routeIs('reports.aggregated-client-statements')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.supplier-payables-summary')" label="ملخص ذمم الموردين"
                        :active="request()->routeIs('reports.supplier-payables-summary')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.aggregated-supplier-statements')" label="كشوف الموردين"
                        :active="request()->routeIs('reports.aggregated-supplier-statements')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('reports.client-adjustments')" label="تسويات العملاء"
                        :active="request()->routeIs('reports.client-adjustments')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M9 14l6-6m-5.5.5h.01m4.99 4.99h.01M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16l3.5-2 3.5 2 3.5-2 3.5 2z"/>
                    </svg>
                </x-slot>
            </x-nav-link>
            @endcan

            @if(auth()->user()->isManager())
            <div class="pt-4 pb-1 px-2 text-[10px] font-bold text-gray-400 uppercase tracking-widest">الإدارة</div>

            <x-nav-link :route="route('users.index')" label="المستخدمون" :active="request()->routeIs('users.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                </x-slot>
            </x-nav-link>

            <x-nav-link :route="route('database-backup.index')" label="نسخ احتياطي"
                        :active="request()->routeIs('database-backup.*')">
                <x-slot name="icon">
                    <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
                    </svg>
                </x-slot>
            </x-nav-link>
            @endif

        </nav>
    </aside>

    {{-- ═══ المحتوى الرئيسي ═══ --}}
    <main class="flex-1 p-6 min-w-0">
        {{ $slot }}
    </main>
</div>

@include('components.layouts.footer')

{{-- ═══ نظام الإشعارات (Toast) ═══ --}}
<div x-data="toastManager"
     @toast.window="add($event.detail.message, $event.detail.type ?? 'success')"
     class="fixed bottom-5 left-5 z-[200] flex flex-col gap-2 w-80">
    <template x-for="toast in toasts" :key="toast.id">
        <div x-show="toast.show"
             x-transition:enter="transition ease-out duration-300"
             x-transition:enter-start="opacity-0 translate-y-4"
             x-transition:enter-end="opacity-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200"
             x-transition:leave-start="opacity-100 translate-y-0"
             x-transition:leave-end="opacity-0 translate-y-2"
             :class="toast.type === 'success' ? 'border-r-4 border-green-500' :
                     toast.type === 'error'   ? 'border-r-4 border-red-500'   :
                                                'border-r-4 border-[#C9A227]'"
             class="card px-4 py-3 flex items-center gap-3 shadow-xl">
            <span x-show="toast.type === 'success'" class="text-green-500 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                </svg>
            </span>
            <span x-show="toast.type === 'error'" class="text-red-500 shrink-0">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </span>
            <span class="text-sm text-[#3D3D3D] flex-1" x-text="toast.message"></span>
            <button @click="remove(toast.id)" class="text-gray-300 hover:text-gray-500 transition shrink-0 text-lg leading-none">&times;</button>
        </div>
    </template>
</div>

@if(session('toast'))
<script>
    document.addEventListener('DOMContentLoaded', () => {
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: @json(session('toast')), type: 'success' } }));
    });
</script>
@endif

</body>
</html>
