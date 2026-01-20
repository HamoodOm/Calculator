<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'نظام الشهادات - الإدارة')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        /* Admin theme - Professional and dark accents */
        :root {
            --admin-bg: #f1f5f9;
            --admin-card: #ffffff;
            --admin-border: #cbd5e1;
            --admin-accent: #4f46e5;
            --ed-width: 1000;
        }
        body {
            background-color: var(--admin-bg);
        }
        .box { position:absolute; border:2px dashed rgba(99,102,241,.9); border-radius:8px; cursor:move; user-select:none; }
        .box .handle { position:absolute; bottom:-8px; right:-8px; width:12px; height:12px; border-radius:50%; background:#6366f1; cursor:nwse-resize; }
        .box .tag { position:absolute; top:-22px; left:0; font-size:11px; background:#fff; border:1px dashed rgba(99,102,241,.5); padding:0 6px; border-radius:6px; }
        .editor-shell { width:fit-content; max-width:none; }
        .editor-scroll { overflow:auto; -webkit-overflow-scrolling:touch; background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; padding:10px; }
        #canvas-wrap { position:relative; width: calc(var(--ed-width) * 1px); max-width:none; }
        #bg { width:100%; height:auto; display:block; border-radius:10px; }
        #grid { position:absolute; left:0; top:0; right:0; bottom:0; pointer-events:none; display:none;
                background-image: repeating-linear-gradient(0deg, rgba(99,102,241,.12), rgba(99,102,241,.12) 1px, transparent 1px, transparent 20px),
                                  repeating-linear-gradient(90deg, rgba(99,102,241,.12), rgba(99,102,241,.12) 1px, transparent 1px, transparent 20px); }
        #preview-iframe { width:100%; height:560px; border:1px solid #e5e7eb; border-radius:10px; background:#fff; }
        .btn { padding:.5rem 1rem; border-radius:.6rem; }
        .kbd { font: 11px/1.6 ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", "Courier New", monospace; background:#f3f4f6; border:1px solid #e5e7eb; padding:2px 6px; border-radius:6px; }
        .admin-card {
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
    </style>
    @yield('head')
</head>
<body class="text-gray-900 min-h-screen bg-gradient-to-br from-slate-100 via-gray-100 to-zinc-100">
    @include('layouts.partials.header')

    <main class="max-w-7xl mx-auto p-4 space-y-6">
        {{-- Page Header with conditional simple link --}}
        <div class="flex justify-between items-center">
            <h1 class="text-xl font-bold text-gray-800">@yield('page-title')</h1>
            @hasSection('simple-link')
                @yield('simple-link')
            @endif
        </div>

        {{-- Flash Messages --}}
        @if(session('success'))
            <div class="bg-green-50 border border-green-200 text-green-800 p-3 rounded flex items-center justify-between">
                <div>{{ session('success') }}</div>
                @if(session('download_url'))
                    <a href="{{ session('download_url') }}" class="btn bg-green-600 text-white hover:bg-green-700">
                        @yield('download-text', 'تحميل')
                    </a>
                @endif
            </div>
        @endif

        @if(session('status'))
            <div class="bg-blue-50 border border-blue-200 text-blue-800 p-3 rounded">{{ session('status') }}</div>
        @endif

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-800 p-3 rounded">
                <div class="font-semibold mb-1">حدثت أخطاء:</div>
                <ul class="list-disc ms-5">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="flash-area" class="hidden bg-red-50 border border-red-200 text-red-800 p-3 rounded"></div>

        @yield('content')
    </main>

    @yield('scripts')
</body>
</html>
