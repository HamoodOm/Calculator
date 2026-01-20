@extends('auth.layouts.app')

@section('title', 'إضافة مؤسسة جديدة')

@section('content')
<div class="max-w-2xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-gray-800">إضافة مؤسسة جديدة</h1>
        <p class="text-gray-600 mt-1">إنشاء مؤسسة أو قسم جديد</p>
    </div>

    <form method="POST" action="{{ route('institutions.store') }}" class="bg-white rounded-lg shadow p-6 space-y-6">
        @csrf

        <div>
            <label for="name" class="block text-sm font-medium text-gray-700 mb-1">اسم المؤسسة</label>
            <input type="text" name="name" id="name" value="{{ old('name') }}" required
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('name') border-red-500 @enderror">
            @error('name')
                <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="description" class="block text-sm font-medium text-gray-700 mb-1">الوصف (اختياري)</label>
            <textarea name="description" id="description" rows="3"
                class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-indigo-500 focus:border-indigo-500 @error('description') border-red-500 @enderror">{{ old('description') }}</textarea>
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
                        <input type="radio" name="color_type" value="hex" id="color_type_hex" class="w-4 h-4" {{ old('custom_hex_color') ? 'checked' : '' }}>
                        <span class="text-sm font-medium">لون مخصص:</span>
                    </label>
                    <input type="color" name="custom_hex_color" id="custom_hex_color" value="{{ old('custom_hex_color', '#4f46e5') }}" class="w-16 h-10 rounded border border-gray-300 cursor-pointer">
                    <span id="hex_value" class="text-sm text-gray-500 font-mono">{{ old('custom_hex_color', '#4f46e5') }}</span>
                </div>
            </div>

            {{-- Preset Colors --}}
            <div class="flex items-center gap-2 mb-2">
                <input type="radio" name="color_type" value="preset" id="color_type_preset" class="w-4 h-4" {{ !old('custom_hex_color') ? 'checked' : '' }}>
                <label for="color_type_preset" class="text-sm font-medium text-gray-700">أو اختر من الألوان الجاهزة:</label>
            </div>
            <div class="grid grid-cols-5 gap-2" id="preset_colors">
                @foreach($colors as $colorClass => $colorName)
                <label class="cursor-pointer preset-color-label">
                    <input type="radio" name="header_color" value="{{ $colorClass }}" class="sr-only peer" {{ old('header_color', 'bg-indigo-700') === $colorClass && !old('custom_hex_color') ? 'checked' : '' }}>
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
            <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', true) ? 'checked' : '' }}
                class="h-4 w-4 text-indigo-600 focus:ring-indigo-500 border-gray-300 rounded">
            <label for="is_active" class="mr-2 block text-sm text-gray-900">مؤسسة نشطة</label>
        </div>

        <div class="flex justify-end gap-4">
            <a href="{{ route('institutions.index') }}" class="px-4 py-2 text-gray-700 hover:text-gray-900">إلغاء</a>
            <button type="submit" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
                إضافة المؤسسة
            </button>
        </div>
    </form>
</div>
@endsection
