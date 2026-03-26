@extends('auth.layouts.app')

@section('title', 'ربط الدورات')

@section('content')
<div class="mb-6">
    <a href="{{ route('api-clients.show', $apiClient) }}" class="text-indigo-600 hover:text-indigo-800 flex items-center gap-1">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z" clip-rule="evenodd" />
        </svg>
        العودة للتفاصيل
    </a>
</div>

<div class="flex justify-between items-center mb-6">
    <div>
        <h1 class="text-2xl font-bold text-gray-800">ربط الدورات</h1>
        <p class="text-gray-600 mt-1">ربط دورات {{ $apiClient->name }} بمسارات الشهادات</p>
    </div>
</div>

@if (session('status'))
    <div class="bg-green-50 border border-green-200 text-green-700 p-4 rounded mb-6">
        {{ session('status') }}
    </div>
@endif

<!-- Add New Mapping Form -->
@if(auth()->user()->hasAnyPermission([\App\Models\Permission::API_CLIENTS_MAPPINGS_CREATE, \App\Models\Permission::API_CLIENTS_MANAGE]))
<div class="bg-white rounded-lg shadow mb-6">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-bold text-gray-800">إضافة ربط جديد</h2>
    </div>
    <form action="{{ route('api-clients.mappings.store', $apiClient) }}" method="POST" class="p-6">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div>
                <label for="external_course_id" class="block text-sm font-medium text-gray-700 mb-1">
                    معرف الدورة الخارجي <span class="text-red-500">*</span>
                </label>
                <input type="text" name="external_course_id" id="external_course_id" value="{{ old('external_course_id') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('external_course_id') border-red-500 @enderror"
                    placeholder="course-101" required>
                @error('external_course_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="external_course_name" class="block text-sm font-medium text-gray-700 mb-1">
                    اسم الدورة (عربي) <span class="text-red-500">*</span>
                </label>
                <input type="text" name="external_course_name" id="external_course_name" value="{{ old('external_course_name') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('external_course_name') border-red-500 @enderror"
                    placeholder="دورة البرمجة" required>
                @error('external_course_name')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="external_course_name_en" class="block text-sm font-medium text-gray-700 mb-1">
                    اسم الدورة (إنجليزي)
                </label>
                <input type="text" name="external_course_name_en" id="external_course_name_en" value="{{ old('external_course_name_en') }}"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                    placeholder="Programming Course">
            </div>

            <div>
                <label for="track_id" class="block text-sm font-medium text-gray-700 mb-1">
                    المسار <span class="text-red-500">*</span>
                </label>
                <select name="track_id" id="track_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 @error('track_id') border-red-500 @enderror" required>
                    <option value="">اختر المسار...</option>
                    @foreach($tracks as $track)
                        <option value="{{ $track->id }}" {{ old('track_id') == $track->id ? 'selected' : '' }}>
                            {{ $track->name_ar }} ({{ $track->key }})
                        </option>
                    @endforeach
                </select>
                @error('track_id')
                    <p class="text-red-500 text-sm mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label for="certificate_type" class="block text-sm font-medium text-gray-700 mb-1">
                    نوع الشهادة <span class="text-red-500">*</span>
                </label>
                <select name="certificate_type" id="certificate_type"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    <option value="student" {{ old('certificate_type') === 'student' ? 'selected' : '' }}>طالب</option>
                    <option value="teacher" {{ old('certificate_type') === 'teacher' ? 'selected' : '' }}>معلم</option>
                </select>
            </div>

            <div>
                <label for="default_gender" class="block text-sm font-medium text-gray-700 mb-1">
                    الجنس الافتراضي
                </label>
                <select name="default_gender" id="default_gender"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    <option value="">يتم تحديده من البيانات</option>
                    <option value="male" {{ old('default_gender') === 'male' ? 'selected' : '' }}>ذكر</option>
                    <option value="female" {{ old('default_gender') === 'female' ? 'selected' : '' }}>أنثى</option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <button type="submit" class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition">
                إضافة الربط
            </button>
        </div>
    </form>
</div>
@endif

<!-- Existing Mappings -->
<div class="bg-white rounded-lg shadow overflow-hidden">
    <div class="px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg font-bold text-gray-800">الدورات المربوطة ({{ $mappings->count() }})</h2>
    </div>

    @if($mappings->count() > 0)
    <table class="min-w-full divide-y divide-gray-200">
        <thead class="bg-gray-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">معرف الدورة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">اسم الدورة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">المسار</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">النوع</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الحالة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">الإجراءات</th>
            </tr>
        </thead>
        <tbody class="bg-white divide-y divide-gray-200">
            @foreach($mappings as $mapping)
                <tr class="hover:bg-gray-50 {{ !$mapping->active ? 'bg-red-50' : '' }}">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <code class="bg-gray-100 px-2 py-0.5 rounded text-sm">{{ $mapping->external_course_id }}</code>
                    </td>
                    <td class="px-6 py-4">
                        <div class="font-medium text-gray-900">{{ $mapping->external_course_name }}</div>
                        @if($mapping->external_course_name_en)
                            <div class="text-sm text-gray-500">{{ $mapping->external_course_name_en }}</div>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="font-medium text-gray-900">{{ $mapping->track->name_ar ?? '-' }}</div>
                        <div class="text-sm text-gray-500">
                            <code class="bg-gray-100 px-1 rounded">{{ $mapping->track->key ?? '-' }}</code>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($mapping->certificate_type === 'teacher')
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                معلم
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-purple-100 text-purple-800">
                                طالب
                            </span>
                        @endif
                        @if($mapping->default_gender)
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-600 mr-1">
                                {{ $mapping->default_gender === 'male' ? 'ذكر' : 'أنثى' }}
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        @if($mapping->active)
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                مفعل
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                معطل
                            </span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                        <div class="flex items-center gap-2">
                            @if(auth()->user()->hasAnyPermission([\App\Models\Permission::API_CLIENTS_MAPPINGS_EDIT, \App\Models\Permission::API_CLIENTS_MANAGE]))
                            <!-- Toggle -->
                            <form action="{{ route('api-clients.mappings.toggle', [$apiClient, $mapping]) }}" method="POST" class="inline">
                                @csrf
                                @method('PATCH')
                                <button type="submit" class="text-{{ $mapping->active ? 'yellow' : 'green' }}-600 hover:text-{{ $mapping->active ? 'yellow' : 'green' }}-900" title="{{ $mapping->active ? 'تعطيل' : 'تفعيل' }}">
                                    @if($mapping->active)
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M13.477 14.89A6 6 0 015.11 6.524l8.367 8.368zm1.414-1.414L6.524 5.11a6 6 0 018.367 8.367zM18 10a8 8 0 11-16 0 8 8 0 0116 0z" clip-rule="evenodd" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd" />
                                        </svg>
                                    @endif
                                </button>
                            </form>

                            <!-- Edit (Modal trigger) -->
                            <button type="button" onclick="openEditModal({{ json_encode($mapping) }})" class="text-indigo-600 hover:text-indigo-900" title="تعديل">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path d="M13.586 3.586a2 2 0 112.828 2.828l-.793.793-2.828-2.828.793-.793zM11.379 5.793L3 14.172V17h2.828l8.38-8.379-2.83-2.828z" />
                                </svg>
                            </button>
                            @endif

                            @if(auth()->user()->hasAnyPermission([\App\Models\Permission::API_CLIENTS_MAPPINGS_DELETE, \App\Models\Permission::API_CLIENTS_MANAGE]))
                            <!-- Delete -->
                            <form action="{{ route('api-clients.mappings.destroy', [$apiClient, $mapping]) }}" method="POST" class="inline" onsubmit="return confirm('هل أنت متأكد من حذف هذا الربط؟')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-900" title="حذف">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M9 2a1 1 0 00-.894.553L7.382 4H4a1 1 0 000 2v10a2 2 0 002 2h8a2 2 0 002-2V6a1 1 0 100-2h-3.382l-.724-1.447A1 1 0 0011 2H9zM7 8a1 1 0 012 0v6a1 1 0 11-2 0V8zm5-1a1 1 0 00-1 1v6a1 1 0 102 0V8a1 1 0 00-1-1z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </form>
                            @endif
                        </div>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
    @else
    <div class="px-6 py-12 text-center text-gray-500">
        <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" />
        </svg>
        <p class="mt-2 text-lg font-medium">لا توجد دورات مربوطة</p>
        <p class="mt-1">قم بإضافة ربط جديد للبدء</p>
    </div>
    @endif
</div>

<!-- Edit Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4">
        <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
            <h3 class="text-lg font-bold text-gray-800">تعديل ربط الدورة</h3>
            <button type="button" onclick="closeEditModal()" class="text-gray-400 hover:text-gray-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
        <form id="editForm" method="POST" class="p-6 space-y-4">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">اسم الدورة (عربي) <span class="text-red-500">*</span></label>
                <input type="text" name="external_course_name" id="edit_external_course_name"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">اسم الدورة (إنجليزي)</label>
                <input type="text" name="external_course_name_en" id="edit_external_course_name_en"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">المسار <span class="text-red-500">*</span></label>
                <select name="track_id" id="edit_track_id"
                    class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                    @foreach($tracks as $track)
                        <option value="{{ $track->id }}">{{ $track->name_ar }} ({{ $track->key }})</option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">نوع الشهادة <span class="text-red-500">*</span></label>
                    <select name="certificate_type" id="edit_certificate_type"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="student">طالب</option>
                        <option value="teacher">معلم</option>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">الجنس الافتراضي</label>
                    <select name="default_gender" id="edit_default_gender"
                        class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                        <option value="">يتم تحديده من البيانات</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end gap-3 pt-4 border-t">
                <button type="button" onclick="closeEditModal()" class="bg-gray-300 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-400 transition">
                    إلغاء
                </button>
                <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition">
                    حفظ التغييرات
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openEditModal(mapping) {
        document.getElementById('editForm').action = '{{ route("api-clients.mappings.update", [$apiClient, ""]) }}/' + mapping.id;
        document.getElementById('edit_external_course_name').value = mapping.external_course_name;
        document.getElementById('edit_external_course_name_en').value = mapping.external_course_name_en || '';
        document.getElementById('edit_track_id').value = mapping.track_id;
        document.getElementById('edit_certificate_type').value = mapping.certificate_type;
        document.getElementById('edit_default_gender').value = mapping.default_gender || '';
        document.getElementById('editModal').classList.remove('hidden');
    }

    function closeEditModal() {
        document.getElementById('editModal').classList.add('hidden');
    }

    // Close modal on outside click
    document.getElementById('editModal').addEventListener('click', function(e) {
        if (e.target === this) {
            closeEditModal();
        }
    });
</script>
@endsection
