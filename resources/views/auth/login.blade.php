<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>تسجيل الدخول — بروفايل ميدا</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans+Arabic:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-[#F5F5F5] flex items-center justify-center font-sans" dir="rtl">
    <div class="w-full max-w-sm bg-white border border-[#E0E0E0] rounded p-8 shadow-sm">

        <div class="text-center mb-8">
            <div class="text-2xl font-bold text-[#3D3D3D]">بروفايل ميدا</div>
            <div class="text-sm text-[#C9A227] mt-1">إنتاج إعلامي وتقارير تشغيلية</div>
        </div>

        <form method="POST" action="{{ route('login') }}" class="space-y-4">
            @csrf

            <div>
                <label class="block text-sm font-medium text-[#3D3D3D] mb-1">البريد الإلكتروني</label>
                <input type="email" name="email" value="{{ old('email') }}" required autofocus
                       class="w-full border border-[#E0E0E0] rounded px-3 py-2 text-sm focus:outline-none focus:border-[#C9A227]
                              @error('email') border-[#DC2626] @enderror">
                @error('email')
                    <p class="text-xs text-[#DC2626] mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-[#3D3D3D] mb-1">كلمة المرور</label>
                <input type="password" name="password" required
                       class="w-full border border-[#E0E0E0] rounded px-3 py-2 text-sm focus:outline-none focus:border-[#C9A227]">
            </div>

            <div class="flex items-center gap-2">
                <input type="checkbox" name="remember" id="remember" class="rounded">
                <label for="remember" class="text-sm text-[#3D3D3D]">تذكّرني</label>
            </div>

            <button type="submit"
                    class="w-full bg-[#C9A227] text-white font-semibold py-2 rounded hover:opacity-90 transition">
                دخول
            </button>
        </form>
    </div>
</body>
</html>
