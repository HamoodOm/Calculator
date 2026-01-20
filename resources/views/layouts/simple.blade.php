<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام الشهادات')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Simple theme - Light and clean */
        :root {
            --simple-bg: #f8fafc;
            --simple-card: #ffffff;
            --simple-border: #e2e8f0;
            --simple-accent: #3b82f6;
        }
        body {
            background-color: var(--simple-bg);
        }
        .preview-container {
            width: 100%;
            max-width: 1122px;
            margin: 0 auto;
        }
        .preview-frame {
            width: 100%;
            height: 600px;
            border: 1px solid var(--simple-border);
            background: var(--simple-card);
            border-radius: 8px;
        }
    </style>
    @yield('head')
</head>
<body class="min-h-screen bg-gradient-to-br from-sky-50 via-white to-blue-50">
    @include('layouts.partials.header')

    <main class="max-w-5xl mx-auto p-6">
        {{-- Page Header with conditional admin link --}}
        <div class="flex justify-between items-center mb-6">
            <h1 class="text-2xl font-bold text-gray-800">@yield('page-title')</h1>
            @hasSection('admin-link')
                @yield('admin-link')
            @endif
        </div>

        {{-- Flash Messages --}}
        @if ($errors->any())
            <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded">
                <p class="font-semibold text-red-800 mb-2">حدثت أخطاء:</p>
                <ul class="list-disc mr-6 text-red-700 space-y-1">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if (session('success'))
            <div class="mb-4 p-4 bg-green-50 border border-green-200 rounded">
                <p class="text-green-800">{{ session('success') }}</p>
                @if (session('download_url'))
                    <a href="{{ session('download_url') }}" class="inline-block mt-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        @yield('download-text', 'تحميل')
                    </a>
                @endif
            </div>
        @endif

        @if (session('status'))
            <div class="mb-4 p-4 bg-blue-50 border border-blue-200 rounded">
                <p class="text-blue-800">{{ session('status') }}</p>
            </div>
        @endif

        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
