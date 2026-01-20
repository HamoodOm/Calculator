<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'الشهادات')</title>

    {{-- If you already have compiled CSS, replace the next line with:
         @vite(['resources/css/app.css','resources/js/app.js'])
         or: <link rel="stylesheet" href="{{ mix('css/app.css') }}"> --}}
    <script src="https://cdn.tailwindcss.com"></script>

    <style>
      /* Safe defaults if Tailwind fails to load */
      body{background:#f5f7fb}
    </style>
    @yield('head')
</head>
<body class="text-gray-900">
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-4 py-3 flex items-center gap-4">
            <h1 class="text-lg font-bold">نظام الشهادات</h1>
            <nav class="text-sm flex gap-3">
                <a class="text-indigo-700 hover:underline" href="{{ route('students.import.form') }}">الطلاب</a>
                {{-- أضف روابط أخرى حسب حاجتك --}}
            </nav>
        </div>
    </header>

    <main class="max-w-6xl mx-auto px-4 py-6">
        @if (session('status'))
            <div class="bg-green-50 border border-green-200 text-green-700 p-3 rounded mb-4">
                {{ session('status') }}
            </div>
        @endif

        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
