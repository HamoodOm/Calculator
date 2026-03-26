@extends('auth.layouts.app')

@section('title', 'إدارة الملفات والتخزين')

@section('content')
<div class="max-w-7xl mx-auto">

    <!-- Page Header -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">إدارة الملفات والتخزين</h1>
        <p class="text-gray-500 mt-1 text-sm">إدارة المجلدات الأربعة في storage/app/ — متاحة للمشرفين العامين والمطورين فقط</p>
    </div>

    @if (session('status'))
        <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded-lg mb-6 flex items-start gap-3">
            <svg class="w-5 h-5 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <span>{{ session('status') }}</span>
        </div>
    @endif

    @if ($errors->any())
        <div class="bg-red-50 border border-red-200 text-red-700 p-4 rounded-lg mb-6">
            @foreach ($errors->all() as $error) <p>{{ $error }}</p> @endforeach
        </div>
    @endif

<!--  بخفيه لفترة مؤقتة فقط لشرح طريقة عمل المجلدات وتنظيفها يدوياً من الواجهة، قبل أن يتم الاعتماد الكامل على الجدولة التلقائية عبر Cron. -->   
    <!-- ===== USAGE GUIDE ===== --/>
    <div class="bg-blue-50 border border-blue-200 rounded-lg p-5 mb-6">
        <h2 class="text-base font-semibold text-blue-800 mb-3 flex items-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            دليل الاستخدام — المجلدات الأربعة
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4 text-sm">

            <!-- api_certificates_temp --/>
            <div class="bg-white rounded-md p-4 border border-blue-100">
                <h3 class="font-semibold text-yellow-700 mb-2 text-xs flex items-center gap-1">
                    🕐 ملفات المعاينة المؤقتة
                </h3>
                <ul class="space-y-1 text-gray-600 text-xs">
                    <li>📁 <code class="bg-gray-100 px-1 rounded font-mono">api_certificates_temp/</code></li>
                    <li>تُنشأ عند معاينة الشهادات قبل الحفظ النهائي (ويب + API)</li>
                    <li class="font-medium text-yellow-700">🗑 تُحذف بعد <strong>30 دقيقة</strong></li>
                    <li>مُضمَّنة في: <code>--temp-only</code></li>
                </ul>
            </div>

            <!-- tmp_uploads --/>
            <div class="bg-white rounded-md p-4 border border-blue-100">
                <h3 class="font-semibold text-blue-700 mb-2 text-xs flex items-center gap-1">
                    📤 ملفات مؤقتة عامة
                </h3>
                <ul class="space-y-1 text-gray-600 text-xs">
                    <li>📁 <code class="bg-gray-100 px-1 rounded font-mono">tmp_uploads/</code></li>
                    <li>صور مرفوعة، ملفات Excel/CSV، أرشيف ZIP للصور، ملفات PDF/ZIP للتحميل</li>
                    <li>كل الملفات تُستخدم خلال طلب HTTP واحد فقط</li>
                    <li class="font-medium text-blue-700">🗑 تُحذف بعد <strong>ساعتين</strong></li>
                    <li>مُضمَّنة في: <code>--temp-only</code></li>
                </ul>
            </div>

            <!-- certificates --/>
            <div class="bg-white rounded-md p-4 border border-blue-100">
                <h3 class="font-semibold text-orange-700 mb-2 text-xs flex items-center gap-1">
                    🖼 شهادات الويب 
                </h3>
                <ul class="space-y-1 text-gray-600 text-xs">
                    <li>📁 <code class="bg-gray-100 px-1 rounded font-mono">certificates/</code></li>
                    <li>صور الشهادات PNG/PDF التي تم انشاءها  عبر واجهة الويب</li>
                    <li class="font-medium text-orange-700">🗑 تُحذف بعد <strong>24 ساعة</strong></li>
                    <li>مُضمَّنة في: <code>--generated-only</code></li>
                </ul>
            </div>

            <!-- api_certificates --/>
            <div class="bg-white rounded-md p-4 border border-blue-100">
                <h3 class="font-semibold text-purple-700 mb-2 text-xs flex items-center gap-1">
                    🔗 شهادات API التي تم أنشاءها
                </h3>
                <ul class="space-y-1 text-gray-600 text-xs">
                    <li>📁 <code class="bg-gray-100 px-1 rounded font-mono">api_certificates/</code></li>
                    <li>هيكل: <code class="bg-gray-100 px-1 rounded">{client}/{year-m}/{id}/</code></li>
                    <li>روابط التحميل صالحة 24 ساعة فقط</li>
                    <li class="font-medium text-purple-700">🗑 تُحذف بعد <strong>48 ساعة</strong> (هامش أمان)</li>
                    <li>مُضمَّنة في: <code>--generated-only</code></li>
                </ul>
            </div>
        </div>

        <!-- Schedule + CLI Reference --/>
        <div class="mt-4 grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white rounded-md p-4 border border-blue-100">
                <h3 class="font-semibold text-gray-700 mb-2 text-xs uppercase tracking-wide">جدول التنظيف التلقائي (Cron)</h3>
                <div class="space-y-3 text-xs text-gray-600">
                    <div class="flex items-start gap-2">
                        <span class="text-green-500 mt-0.5">⚡</span>
                        <div>
                            <strong>كل 30 دقيقة</strong> — يُنظِّف api_certificates_temp (>30 دق) + tmp_uploads (>2 ساعة)<br>
                            <code class="bg-gray-100 px-1 rounded">php artisan files:cleanup --temp-only</code>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-blue-500 mt-0.5">🌙</span>
                        <div>
                            <strong>يومياً الساعة 2:00 ص</strong> — يُنظِّف certificates (>24h) + api_certificates (>48h)<br>
                            <code class="bg-gray-100 px-1 rounded">php artisan files:cleanup --generated-only</code>
                        </div>
                    </div>
                    <div class="flex items-start gap-2">
                        <span class="text-purple-500 mt-0.5">⚙️</span>
                        <div>
                            <strong>تفعيل الجدولة على الخادم:</strong><br>
                            <code class="bg-gray-100 px-1 rounded">* * * * * php /path/to/artisan schedule:run</code>
                        </div>
                    </div>
                </div>
            </div>
            <div class="bg-white rounded-md p-4 border border-blue-100">
                <h3 class="font-semibold text-gray-700 mb-2 text-xs uppercase tracking-wide">أوامر Artisan المتاحة</h3>
                <table class="w-full text-xs">
                    <tbody class="text-gray-600 divide-y divide-gray-50">
                        <tr>
                            <td class="py-1 pr-2"><code class="bg-gray-100 px-1 rounded" dir="ltr">files:cleanup</code></td>
                            <td class="py-1">ينظِّف جميع المجلدات الأربعة</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-2"><code class="bg-gray-100 px-1 rounded" dir="ltr">files:cleanup --temp-only</code></td>
                            <td class="py-1">api_certificates_temp + tmp_uploads</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-2"><code class="bg-gray-100 px-1 rounded" dir="ltr">files:cleanup --generated-only</code></td>
                            <td class="py-1">certificates + api_certificates</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-2"><code class="bg-gray-100 px-1 rounded" dir="ltr">files:cleanup --dry-run</code></td>
                            <td class="py-1">معاينة بدون حذف فعلي</td>
                        </tr>
                        <tr>
                            <td class="py-1 pr-2"><code class="bg-gray-100 px-1 rounded" dir="ltr">schedule:run</code></td>
                            <td class="py-1">تشغيل المهام المجدولة (للاختبار)</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div> 
    
    -->

    <!-- ===== QUICK ACTION BUTTONS ===== -->
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-800">إجراءات يدوية سريعة</h2>
            <p class="text-xs text-gray-500 mt-0.5">تعمل فوراً — لا تنتظر الجدولة التلقائية</p>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap gap-3">

                <!-- Cleanup api_certificates_temp + tmp_uploads -->
                <form action="{{ route('storage.cleanup-temp') }}" method="POST"
                    onsubmit="return confirm('سيتم حذف ملفات api_certificates_temp (>30 دق) وtmp_uploads (>2 ساعة). هل تريد المتابعة؟')">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2.5 rounded-lg transition font-medium text-sm shadow-sm">
                        🕐 تنظيف الملفات المؤقتة
                        @php $expiredTemp = $stats['temp']['expired'] + $stats['uploads']['expired']; @endphp
                        @if($expiredTemp > 0)
                            <span class="bg-yellow-700 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $expiredTemp }}</span>
                        @endif
                    </button>
                </form>

                <!-- Cleanup tmp_uploads only -->
                <form action="{{ route('storage.cleanup-uploads') }}" method="POST"
                    onsubmit="return confirm('سيتم حذف ملفات tmp_uploads الأقدم من ساعتين. هل تريد المتابعة؟')">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 bg-blue-500 hover:bg-blue-600 text-white px-4 py-2.5 rounded-lg transition font-medium text-sm shadow-sm">
                        📤 تنظيف الملفات المرفوعة المؤقتة tmp_uploads
                        @if($stats['uploads']['expired'] > 0)
                            <span class="bg-blue-700 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $stats['uploads']['expired'] }}</span>
                        @endif
                    </button>
                </form>

                <!-- Cleanup certificates + api_certificates -->
                <form action="{{ route('storage.cleanup-generated') }}" method="POST"
                    onsubmit="return confirm('سيتم حذف الشهادات المنتهية في certificates (>24h) وapi_certificates (>48h). هل تريد المتابعة؟')">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 bg-orange-500 hover:bg-orange-600 text-white px-4 py-2.5 rounded-lg transition font-medium text-sm shadow-sm">
                        🖼 تنظيف الشهادات القديمة
                        @php $expiredCerts = $stats['certificates']['expired'] + $stats['api']['expired']; @endphp
                        @if($expiredCerts > 0)
                            <span class="bg-orange-700 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $expiredCerts }}</span>
                        @endif
                    </button>
                </form>

                <!-- Cleanup All -->
                <form action="{{ route('storage.cleanup-all') }}" method="POST"
                    onsubmit="return confirm('تحذير: سيتم حذف جميع الملفات المنتهية الصلاحية في المجلدات الأربعة. لا يمكن التراجع. هل أنت متأكد؟')">
                    @csrf
                    <button type="submit"
                        class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2.5 rounded-lg transition font-medium text-sm shadow-sm">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        تنظيف الكل (4 مجلدات)
                        @php $totalExpired = $stats['temp']['expired'] + $stats['uploads']['expired'] + $stats['certificates']['expired'] + $stats['api']['expired']; @endphp
                        @if($totalExpired > 0)
                            <span class="bg-red-800 text-white text-xs px-1.5 py-0.5 rounded-full">{{ $totalExpired }}</span>
                        @endif
                    </button>
                </form>
            </div>

            @if($stats['temp']['expired'] == 0 && $stats['uploads']['expired'] == 0 && $stats['certificates']['expired'] == 0 && $stats['api']['expired'] == 0)
                <div class="mt-4 flex items-center gap-2 text-green-600 text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    لا توجد ملفات منتهية الصلاحية — التخزين نظيف تماماً!
                </div>
            @endif
        </div>
    </div>

    <!-- ===== STORAGE OVERVIEW CARDS ===== -->
    <div class="grid grid-cols-2 xl:grid-cols-4 gap-4 mb-6">

        @php
        $cards = [
            ['key' => 'temp',         'label' => 'معاينة مؤقتة',    'dir' => 'api_certificates_temp/', 'color' => 'yellow',  'threshold' => '30 دقيقة'],
            ['key' => 'uploads',      'label' => 'الملفات المرفوعة tmp',       'dir' => 'tmp_uploads/',           'color' => 'blue',    'threshold' => 'ساعتان'],
            ['key' => 'certificates', 'label' => 'شهادات الويب',    'dir' => 'certificates/',           'color' => 'orange',  'threshold' => '24 ساعة'],
            ['key' => 'api',          'label' => 'شهادات API',      'dir' => 'api_certificates/',       'color' => 'purple',  'threshold' => '48 ساعة'],
        ];
        @endphp

        @foreach($cards as $card)
        @php $s = $stats[$card['key']]; $c = $card['color']; @endphp
        <div class="bg-white rounded-lg shadow">
            <div class="px-4 py-3 border-b border-gray-200">
                <h2 class="text-sm font-bold text-gray-800">{{ $card['label'] }}</h2>
                <p class="text-xs text-gray-400 font-mono mt-0.5">{{ $card['dir'] }}</p>
                <span class="inline-block mt-1 text-xs bg-{{ $c }}-100 text-{{ $c }}-700 px-2 py-0.5 rounded-full font-medium">حد {{ $card['threshold'] }}</span>
            </div>
            <div class="p-4">
                <div class="grid grid-cols-3 gap-2 mb-2">
                    <div class="text-center p-2 bg-gray-50 rounded">
                        <div class="text-lg font-bold text-gray-700">{{ $s['total'] }}</div>
                        <div class="text-xs text-gray-400">إجمالي</div>
                    </div>
                    <div class="text-center p-2 {{ $s['expired'] > 0 ? 'bg-red-50' : 'bg-gray-50' }} rounded">
                        <div class="text-lg font-bold {{ $s['expired'] > 0 ? 'text-red-600' : 'text-gray-400' }}">{{ $s['expired'] }}</div>
                        <div class="text-xs text-gray-400">منتهية</div>
                    </div>
                    <div class="text-center p-2 bg-gray-50 rounded">
                        <div class="text-xs font-bold text-gray-700">{{ \App\Http\Controllers\Auth\StorageController::formatBytes($s['size']) }}</div>
                        <div class="text-xs text-gray-400">الحجم</div>
                    </div>
                </div>
                @if($s['expired_size'] > 0)
                    <div class="flex items-center justify-between text-xs bg-red-50 rounded px-2 py-1">
                        <span class="text-red-600">يمكن تحرير:</span>
                        <span class="font-semibold text-red-700">{{ \App\Http\Controllers\Auth\StorageController::formatBytes($s['expired_size']) }}</span>
                    </div>
                @endif
            </div>
        </div>
        @endforeach
    </div>

    <!-- ===== FILE LISTS ===== -->

    {{-- Helper macro: show file table for a directory that supports per-file deletion --}}
    @php
    $deletableSections = [
        [
            'key'   => 'certificates',
            'title' => 'شهادات الويب — آخر',
            'dir'   => 'certificates/',
            'type'  => 'certificates',
            'empty' => 'لا توجد ملفات في certificates/ حتى الآن',
            'note'  => null,
            'color' => 'red',
        ],
        [
            'key'   => 'uploads',
            'title' => 'رفوعات tmp_uploads — آخر',
            'dir'   => 'tmp_uploads/',
            'type'  => 'uploads',
            'empty' => 'لا توجد ملفات في tmp_uploads/ حتى الآن',
            'note'  => 'صور مرفوعة، ملفات استيراد، PDF/ZIP مولّدة — كلها مؤقتة لطلب HTTP واحد.',
            'color' => 'yellow',
        ],
        [
            'key'   => 'temp',
            'title' => 'معاينة مؤقتة — آخر',
            'dir'   => 'api_certificates_temp/',
            'type'  => 'temp',
            'empty' => 'لا توجد ملفات في api_certificates_temp/ حتى الآن',
            'note'  => 'تُنشأ عند معاينة الشهادات في الواجهة.',
            'color' => 'yellow',
        ],
    ];
    @endphp

    @foreach($deletableSections as $sec)
    @php $s = $stats[$sec['key']]; @endphp
    @if(!empty($s['files']))
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h2 class="text-base font-bold text-gray-800">{{ $sec['title'] }} {{ count($s['files']) }} ملف</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    📁 {{ $sec['dir'] }}
                    @if($sec['note']) — {{ $sec['note'] }} @endif
                </p>
            </div>
            @if($s['expired'] > 0)
                <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">{{ $s['expired'] }} منتهية</span>
            @else
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">غير منتهية</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">اسم الملف</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحجم</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">حذف</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($s['files'] as $file)
                    <tr class="{{ $file['expired'] ? 'bg-' . $sec['color'] . '-50 hover:bg-' . $sec['color'] . '-100' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-2.5 text-gray-700 font-mono text-xs">{{ $file['name'] }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ \App\Http\Controllers\Auth\StorageController::formatBytes($file['size']) }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">
                            <div>{{ $file['modified']->format('Y-m-d H:i') }}</div>
                            <div class="text-gray-400">{{ $file['modified']->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-2.5">
                            @if($file['expired'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">منتهية</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                            @endif
                        </td>
                        <td class="px-4 py-2.5">
                            <form action="{{ route('storage.delete-file') }}" method="POST"
                                onsubmit="return confirm('حذف {{ addslashes($file['name']) }}؟ لا يمكن التراجع.')">
                                @csrf
                                <input type="hidden" name="file" value="{{ $file['name'] }}">
                                <input type="hidden" name="type" value="{{ $sec['type'] }}">
                                <button type="submit" class="text-red-600 hover:text-red-800 text-xs font-medium hover:underline">حذف</button>
                            </form>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($s['total'] > count($s['files']))
            <div class="px-6 py-3 bg-gray-50 border-t text-xs text-gray-500 text-center">
                يُعرض آخر {{ count($s['files']) }} ملف من أصل {{ $s['total'] }}
            </div>
        @endif
    </div>
    @else
    <div class="bg-white rounded-lg shadow mb-4 px-6 py-6 text-center">
        <p class="text-gray-400 text-sm">لا توجد ملفات في {{ $sec['dir'] }} حتى الآن</p>
    </div>
    @endif
    @endforeach

    <!-- ===== API CERTIFICATES (read-only, nested dirs) ===== -->
    @if(!empty($stats['api']['files']))
    <div class="bg-white rounded-lg shadow mb-6">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <div>
                <h2 class="text-base font-bold text-gray-800">شهادات API — آخر {{ count($stats['api']['files']) }} ملف</h2>
                <p class="text-xs text-gray-500 mt-0.5">
                    📁 api_certificates/ — هيكل: {client}/{year-month}/{id}/
                    — الحذف الفردي غير متاح من الواجهة (مجلدات متداخلة)، استخدم <code>--generated-only</code>.
                </p>
            </div>
            @if($stats['api']['expired'] > 0)
                <span class="text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">{{ $stats['api']['expired'] }} منتهية</span>
            @else
                <span class="text-xs bg-green-100 text-green-700 px-2 py-1 rounded-full">لا منتهية</span>
            @endif
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">المسار الكامل</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحجم</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">تاريخ الإنشاء</th>
                        <th class="px-4 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @foreach($stats['api']['files'] as $file)
                    <tr class="{{ $file['expired'] ? 'bg-red-50 hover:bg-red-100' : 'hover:bg-gray-50' }}">
                        <td class="px-4 py-2.5 text-gray-700 font-mono text-xs">{{ $file['rel_path'] }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">{{ \App\Http\Controllers\Auth\StorageController::formatBytes($file['size']) }}</td>
                        <td class="px-4 py-2.5 text-gray-500 text-xs">
                            <div>{{ $file['modified']->format('Y-m-d H:i') }}</div>
                            <div class="text-gray-400">{{ $file['modified']->diffForHumans() }}</div>
                        </td>
                        <td class="px-4 py-2.5">
                            @if($file['expired'])
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">منتهية</span>
                            @else
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">نشط</span>
                            @endif
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @if($stats['api']['total'] > count($stats['api']['files']))
            <div class="px-6 py-3 bg-gray-50 border-t text-xs text-gray-500 text-center">
                يُعرض آخر {{ count($stats['api']['files']) }} ملف من أصل {{ $stats['api']['total'] }}
            </div>
        @endif
    </div>
    @endif

</div>
@endsection
