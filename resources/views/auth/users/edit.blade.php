@extends('auth.layouts.app')

@section('title', 'تعديل المستخدم')

@section('content')
<div class="mb-6">
    <a href="{{ route('users.index') }}" class="text-indigo-600 hover:text-indigo-800">&larr; العودة للمستخدمين</a>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">تعديل المستخدم: {{ $user->name }}</h1>
        @if($user->id === $currentUser->id)
        <span class="px-3 py-1 bg-blue-100 text-blue-600 rounded-full text-sm">حسابك</span>
        @endif
    </div>

    @if(!($canChangeRole ?? true))
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-yellow-700">
            <strong>تنبيه:</strong> ليس لديك صلاحية لتغيير دور هذا المستخدم.
        </p>
    </div>
    @endif

    @if($user->isSuperAdmin() && !$currentUser->isSuperAdmin())
    <div class="mb-6 p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
        <p class="text-yellow-700">
            <strong>تنبيه:</strong> لا يمكنك تعديل صلاحيات مستخدم مسؤول عام.
        </p>
    </div>
    @endif

    <form method="POST" action="{{ route('users.update', $user) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-gray-700 font-medium mb-2">الاسم</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $user->name) }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">البريد الإلكتروني</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email', $user->email) }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    dir="ltr"
                >
            </div>

            <div>
                <label for="password" class="block text-gray-700 font-medium mb-2">كلمة المرور الجديدة (اختياري)</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="اتركه فارغاً للإبقاء على كلمة المرور الحالية"
                >
            </div>

            <div>
                <label for="password_confirmation" class="block text-gray-700 font-medium mb-2">تأكيد كلمة المرور الجديدة</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div>
                <label for="role_id" class="block text-gray-700 font-medium mb-2">الدور</label>
                <select
                    id="role_id"
                    name="role_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ !($canChangeRole ?? true) ? 'bg-gray-100' : '' }}"
                    {{ !($canChangeRole ?? true) ? 'disabled' : '' }}
                >
                    <option value="">بدون دور</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id', $user->role_id) == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                    @endforeach
                </select>
                @if(!($canChangeRole ?? true))
                <input type="hidden" name="role_id" value="{{ $user->role_id }}">
                <p class="text-sm text-gray-500 mt-1">ليس لديك صلاحية لتغيير هذا الدور</p>
                @endif
            </div>

            {{-- Institution Selection --}}
            @if($institutions->isNotEmpty())
            <div>
                <label for="institution_id" class="block text-gray-700 font-medium mb-2">المؤسسة</label>
                <select
                    id="institution_id"
                    name="institution_id"
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ !$canSelectInstitution ? 'bg-gray-100' : '' }}"
                    {{ !$canSelectInstitution ? 'disabled' : '' }}
                >
                    <option value="">بدون مؤسسة</option>
                    @foreach($institutions as $institution)
                    <option value="{{ $institution->id }}" {{ old('institution_id', $user->institution_id) == $institution->id ? 'selected' : '' }}>
                        {{ $institution->name }}
                    </option>
                    @endforeach
                </select>
                @if(!$canSelectInstitution && $user->institution_id)
                <input type="hidden" name="institution_id" value="{{ $user->institution_id }}">
                @endif
            </div>
            @endif

            {{-- Custom Color (Super users only) --}}
            @if($canChangeColor ?? false)
            <div class="md:col-span-2">
                <label class="block text-gray-700 font-medium mb-2">لون مخصص</label>

                {{-- Custom Hex Color Picker --}}
                @php
                    $isHexColor = old('custom_color', $user->custom_color) && str_starts_with(old('custom_color', $user->custom_color) ?? '', '#');
                    $currentHex = $isHexColor ? old('custom_color', $user->custom_color) : '#4f46e5';
                @endphp
                <div class="mb-3 p-3 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center gap-3">
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="radio" name="color_type" value="hex" id="user_color_type_hex" class="w-4 h-4" {{ $isHexColor ? 'checked' : '' }}>
                            <span class="text-sm font-medium">لون مخصص:</span>
                        </label>
                        <input type="color" name="hex_color_value" id="user_hex_color" value="{{ $currentHex }}" class="w-12 h-10 rounded border border-gray-300 cursor-pointer">
                        <span id="user_hex_value" class="text-sm text-gray-500 font-mono">{{ $currentHex }}</span>
                    </div>
                </div>

                {{-- Preset Colors --}}
                <div class="flex items-center gap-2 mb-2">
                    <input type="radio" name="color_type" value="preset" id="user_color_type_preset" class="w-4 h-4" {{ !$isHexColor ? 'checked' : '' }}>
                    <label for="user_color_type_preset" class="text-sm font-medium text-gray-700">الألوان الجاهزة:</label>
                </div>
                <div class="grid grid-cols-8 gap-2">
                    <label class="cursor-pointer preset-user-color">
                        <input type="radio" name="preset_color" value="" class="sr-only peer" {{ !old('custom_color', $user->custom_color) ? 'checked' : '' }}>
                        <div class="w-full h-10 bg-gray-200 rounded-lg flex items-center justify-center text-gray-600 text-xs peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-gray-900 transition">
                            افتراضي
                        </div>
                    </label>
                    @foreach($colors as $colorClass => $colorName)
                    <label class="cursor-pointer preset-user-color">
                        <input type="radio" name="preset_color" value="{{ $colorClass }}" class="sr-only peer" {{ old('custom_color', $user->custom_color) === $colorClass ? 'checked' : '' }}>
                        <div class="w-full h-10 {{ $colorClass }} rounded-lg peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-gray-900 transition" title="{{ $colorName }}"></div>
                    </label>
                    @endforeach
                </div>
                <input type="hidden" name="custom_color" id="final_custom_color" value="{{ old('custom_color', $user->custom_color) }}">
            </div>
            @endif

            <div class="flex items-center">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', $user->is_active) ? 'checked' : '' }}
                        {{ $user->id === $currentUser->id ? 'disabled' : '' }}
                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    >
                    <span class="mr-2 text-gray-700">حساب نشط</span>
                </label>
                @if($user->id === $currentUser->id)
                <input type="hidden" name="is_active" value="1">
                <span class="mr-4 text-sm text-gray-500">(لا يمكنك تعطيل حسابك)</span>
                @endif
            </div>
        </div>

        {{-- User Info Panel --}}
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <h3 class="font-medium text-gray-700 mb-2">معلومات المستخدم</h3>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div>
                    <span class="text-gray-500">تاريخ الإنشاء:</span>
                    <span class="font-medium">{{ $user->created_at->format('Y-m-d') }}</span>
                </div>
                @if($user->institution)
                <div>
                    <span class="text-gray-500">المؤسسة:</span>
                    <span class="font-medium">{{ $user->institution->name }}</span>
                </div>
                @endif
                @if($user->custom_color)
                <div>
                    <span class="text-gray-500">لون مخصص:</span>
                    <span class="inline-block w-4 h-4 {{ $user->custom_color }} rounded mr-1"></span>
                </div>
                @endif
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <button
                type="submit"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"
            >
                حفظ التغييرات
            </button>
            <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

@if($canChangeColor ?? false)
<script>
document.addEventListener('DOMContentLoaded', function() {
    const hexInput = document.getElementById('user_hex_color');
    const hexValue = document.getElementById('user_hex_value');
    const colorTypeHex = document.getElementById('user_color_type_hex');
    const colorTypePreset = document.getElementById('user_color_type_preset');
    const presetInputs = document.querySelectorAll('.preset-user-color input');
    const finalColor = document.getElementById('final_custom_color');

    function updateFinalColor() {
        if (colorTypeHex && colorTypeHex.checked) {
            finalColor.value = hexInput.value;
        } else {
            const checked = document.querySelector('.preset-user-color input:checked');
            finalColor.value = checked ? checked.value : '';
        }
    }

    if (hexInput) {
        hexInput.addEventListener('input', function() {
            hexValue.textContent = this.value;
            if (colorTypeHex) colorTypeHex.checked = true;
            updateFinalColor();
        });

        hexInput.addEventListener('click', function() {
            if (colorTypeHex) colorTypeHex.checked = true;
            updateFinalColor();
        });
    }

    presetInputs.forEach(function(input) {
        input.addEventListener('change', function() {
            if (colorTypePreset) colorTypePreset.checked = true;
            updateFinalColor();
        });
    });

    if (colorTypeHex) colorTypeHex.addEventListener('change', updateFinalColor);
    if (colorTypePreset) colorTypePreset.addEventListener('change', updateFinalColor);
});
</script>
@endif
@endsection
