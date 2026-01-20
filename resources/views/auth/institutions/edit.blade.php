@extends('auth.layouts.app')

@section('title', 'تعديل المؤسسة')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">تعديل المؤسسة</h1>
        <p class="text-gray-600 mt-1">تعديل بيانات المؤسسة</p>
    </div>

    <form method="POST" action="{{ route('institutions.update', $institution) }}" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf
        @method('PUT')

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">اسم المؤسسة</label>
            <input type="text" name="name" id="name" value="{{ old('name', $institution->name) }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">الوصف (اختياري)</label>
            <textarea name="description" id="description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('description') border-red-500 @enderror">{{ old('description', $institution->description) }}</textarea>
            @error('description')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700 mb-2">لون الواجهة</label>

            {{-- Custom Hex Color Picker --}}
            <div class="mb-4 p-3 bg-gray-50 rounded-lg border border-gray-200">
                <div class="flex items-center gap-3">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="radio" name="color_type" value="hex" id="color_type_hex" class="w-4 h-4" {{ old('custom_hex_color', $institution->custom_hex_color) ? 'checked' : '' }}>
                        <span class="text-sm font-medium">لون مخصص:</span>
                    </label>
                    <input type="color" name="custom_hex_color" id="custom_hex_color" value="{{ old('custom_hex_color', $institution->custom_hex_color ?? '#4f46e5') }}" class="w-16 h-10 rounded border border-gray-300 cursor-pointer">
                    <span id="hex_value" class="text-sm text-gray-500 font-mono">{{ old('custom_hex_color', $institution->custom_hex_color ?? '#4f46e5') }}</span>
                </div>
            </div>

            {{-- Preset Colors --}}
            <div class="flex items-center gap-2 mb-2">
                <input type="radio" name="color_type" value="preset" id="color_type_preset" class="w-4 h-4" {{ !old('custom_hex_color', $institution->custom_hex_color) ? 'checked' : '' }}>
                <label for="color_type_preset" class="text-sm font-medium text-gray-700">أو اختر من الألوان الجاهزة:</label>
            </div>
            <div class="grid grid-cols-5 gap-2" id="preset_colors">
                @foreach($colors as $colorClass => $colorName)
                <label class="cursor-pointer preset-color-label">
                    <input type="radio" name="header_color" value="{{ $colorClass }}" class="sr-only peer" {{ old('header_color', $institution->header_color) === $colorClass && !old('custom_hex_color', $institution->custom_hex_color) ? 'checked' : '' }}>
                    <div class="w-full h-12 {{ $colorClass }} rounded-lg flex items-center justify-center text-white text-xs font-medium peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-gray-900 transition">
                        {{ $colorName }}
                    </div>
                </label>
                @endforeach
            </div>
            @error('header_color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
            @error('custom_hex_color')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const hexInput = document.getElementById('custom_hex_color');
            const hexValue = document.getElementById('hex_value');
            const colorTypeHex = document.getElementById('color_type_hex');
            const colorTypePreset = document.getElementById('color_type_preset');
            const presetLabels = document.querySelectorAll('.preset-color-label input');

            hexInput.addEventListener('input', function() {
                hexValue.textContent = this.value;
                colorTypeHex.checked = true;
            });

            hexInput.addEventListener('click', function() {
                colorTypeHex.checked = true;
            });

            presetLabels.forEach(function(input) {
                input.addEventListener('change', function() {
                    colorTypePreset.checked = true;
                });
            });
        });
        </script>

        <div class="flex items-center">
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $institution->is_active) ? 'checked' : '' }}
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="is_active" class="mr-2 block text-sm text-gray-900">مؤسسة نشطة</label>
        </div>

        <div class="p-4 bg-gray-50 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-2">إحصائيات المؤسسة</h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">عدد المستخدمين:</span>
                    @if($institution->users()->count() > 0)
                    <a href="{{ route('users.index', ['institution' => $institution->id]) }}" class="font-medium text-indigo-600 hover:text-indigo-900 hover:underline">
                        {{ $institution->users()->count() }}
                    </a>
                    @else
                    <span class="font-medium">0</span>
                    @endif
                </div>
                <div>
                    <span class="text-gray-500">عدد المسارات:</span>
                    <span class="font-medium">{{ $institution->tracks()->count() }}</span>
                </div>
            </div>
        </div>

        {{-- Users List --}}
        @if($institution->users()->count() > 0)
        <div class="p-4 bg-blue-50 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-2">المستخدمين ({{ $institution->users()->count() }})</h3>
            <div class="flex flex-wrap gap-2">
                @foreach($institution->users()->with('role')->limit(10)->get() as $user)
                <a href="{{ route('users.edit', $user) }}" class="inline-flex items-center gap-1 px-3 py-1 bg-white rounded-full text-sm text-gray-700 hover:bg-blue-100 transition border border-blue-200">
                    <span>{{ $user->name }}</span>
                    @if($user->role)
                    <span class="text-xs text-gray-400">({{ $user->role->name }})</span>
                    @endif
                </a>
                @endforeach
                @if($institution->users()->count() > 10)
                <a href="{{ route('users.index', ['institution' => $institution->id]) }}" class="inline-flex items-center px-3 py-1 bg-indigo-100 rounded-full text-sm text-indigo-700 hover:bg-indigo-200 transition">
                    عرض الكل ({{ $institution->users()->count() }})
                </a>
                @endif
            </div>
        </div>
        @endif

        {{-- Tracks List --}}
        @if($institution->tracks()->count() > 0)
        <div id="tracks" class="p-4 bg-green-50 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <h3 class="font-medium text-gray-700">المسارات ({{ $institution->tracks()->count() }})</h3>
                @if(auth()->user()->canAccessTeacherAdmin())
                <a href="{{ route('teacher.admin.index') }}" class="text-xs text-indigo-600 hover:text-indigo-800">
                    إدارة المسارات &larr;
                </a>
                @endif
            </div>
            <div class="space-y-2">
                @foreach($institution->tracks as $track)
                <div class="flex items-center justify-between p-2 bg-white rounded border border-green-200 hover:border-green-400 transition cursor-pointer group" onclick="toggleTrackDetails({{ $track->id }})">
                    <div class="flex-1">
                        <span class="font-medium text-gray-800 group-hover:text-indigo-600">{{ $track->name_ar }}</span>
                        @if($track->name_en)
                        <span class="text-sm text-gray-500 mr-2">{{ $track->name_en }}</span>
                        @endif
                        <div id="track-details-{{ $track->id }}" class="hidden mt-2 text-xs text-gray-500">
                            <span>المفتاح: {{ $track->key }}</span>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        @if(auth()->user()->hasPermission(\App\Models\Permission::TRACKS_EDIT))
                        <form action="{{ route('institutions.toggle-track', ['institution' => $institution->id, 'track' => $track->id]) }}" method="POST" onclick="event.stopPropagation();">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="text-xs px-2 py-1 {{ $track->active ? 'bg-green-100 text-green-700 hover:bg-red-100 hover:text-red-700' : 'bg-gray-100 text-gray-500 hover:bg-green-100 hover:text-green-700' }} rounded transition" title="{{ $track->active ? 'تعطيل المسار' : 'تفعيل المسار' }}">
                                {{ $track->active ? 'نشط' : 'معطل' }}
                            </button>
                        </form>
                        @else
                        <span class="text-xs px-2 py-1 {{ $track->active ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500' }} rounded">
                            {{ $track->active ? 'نشط' : 'معطل' }}
                        </span>
                        @endif
                    </div>
                </div>
                @endforeach
            </div>
        </div>
        <script>
        function toggleTrackDetails(trackId) {
            const details = document.getElementById('track-details-' + trackId);
            if (details) {
                details.classList.toggle('hidden');
            }
        }
        </script>
        @endif

        <div class="flex justify-end gap-4">
            <a href="{{ route('institutions.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">إلغاء</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                حفظ التغييرات
            </button>
        </div>
    </form>
</div>
@endsection
