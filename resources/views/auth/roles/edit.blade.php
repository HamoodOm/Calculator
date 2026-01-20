@extends('auth.layouts.app')

@section('title', 'تعديل الدور')

@section('content')
<div class="mb-6">
    <a href="{{ route('roles.index') }}" class="text-indigo-600 hover:text-indigo-800">&larr; العودة للأدوار</a>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-2xl font-bold text-gray-800">تعديل الدور: {{ $role->name }}</h1>
        @if($role->is_system)
        <span class="px-3 py-1 bg-gray-100 text-gray-600 rounded-full text-sm">دور نظامي</span>
        @endif
    </div>

    @if($role->isSuperAdmin())
    <div class="mb-6 p-4 bg-purple-50 border border-purple-200 rounded-lg">
        <p class="text-purple-700">
            <strong>ملاحظة:</strong> هذا الدور يمتلك جميع الصلاحيات تلقائياً ولا يمكن تغيير صلاحياته.
        </p>
    </div>
    @endif

    <form method="POST" action="{{ route('roles.update', $role) }}">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-gray-700 font-medium mb-2">اسم الدور</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name', $role->name) }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
            </div>

            <div>
                <label for="slug" class="block text-gray-700 font-medium mb-2">المعرف (بالإنجليزية)</label>
                <input
                    type="text"
                    id="slug"
                    name="slug"
                    value="{{ old('slug', $role->slug) }}"
                    required
                    pattern="[a-z0-9-]+"
                    {{ $role->is_system ? 'disabled' : '' }}
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 {{ $role->is_system ? 'bg-gray-100' : '' }}"
                    dir="ltr"
                >
                @if($role->is_system)
                <input type="hidden" name="slug" value="{{ $role->slug }}">
                <p class="text-sm text-gray-500 mt-1">لا يمكن تغيير معرف الدور النظامي</p>
                @endif
            </div>
        </div>

        <div class="mt-6">
            <label for="description" class="block text-gray-700 font-medium mb-2">الوصف</label>
            <textarea
                id="description"
                name="description"
                rows="2"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
            >{{ old('description', $role->description) }}</textarea>
        </div>

        {{-- Role Level / Hierarchy (only for non-system roles) --}}
        @if(!$role->is_system)
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <label for="level" class="block text-gray-700 font-medium mb-2">ترتيب الدور (المستوى)</label>
            <p class="text-sm text-gray-500 mb-3">رقم أصغر = صلاحيات أعلى. المستخدمون يمكنهم إدارة من هم في مستوى أدنى منهم فقط.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <input
                        type="number"
                        id="level"
                        name="level"
                        value="{{ old('level', $role->level ?? 99) }}"
                        min="0"
                        max="99"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    >
                </div>
                <div class="text-sm text-gray-600">
                    <p class="font-medium mb-1">المستويات الحالية:</p>
                    @foreach($existingLevels as $lvl)
                    <span class="inline-block px-2 py-1 bg-gray-200 rounded mr-1 mb-1">{{ $lvl->name }}: {{ $lvl->level }}</span>
                    @endforeach
                </div>
            </div>
        </div>
        @else
        <div class="mt-6 p-4 bg-gray-100 rounded-lg">
            <p class="text-gray-600">
                <strong>المستوى:</strong> {{ $role->getLevel() }}
                <span class="text-sm text-gray-500 mr-2">(لا يمكن تغيير مستوى الأدوار النظامية)</span>
            </p>
        </div>
        @endif

        {{-- Theme Colors --}}
        <div class="mt-6 p-4 bg-gray-50 rounded-lg">
            <label class="block text-gray-700 font-medium mb-2">ألوان الواجهة (اختياري)</label>
            <p class="text-sm text-gray-500 mb-4">هذه الألوان تستخدم في الواجهة. لون الخلفية يأتي من المؤسسة.</p>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                {{-- Hover Color --}}
                <div>
                    <label for="theme_hover" class="block text-sm text-gray-600 mb-1">لون التحويم</label>
                    <select name="theme_hover" id="theme_hover" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">افتراضي</option>
                        @foreach($themeColors['hover'] as $value => $name)
                        <option value="{{ $value }}" {{ old('theme_hover', $role->theme_hover) === $value ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Text Color --}}
                <div>
                    <label for="theme_text" class="block text-sm text-gray-600 mb-1">لون النص</label>
                    <select name="theme_text" id="theme_text" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">افتراضي</option>
                        @foreach($themeColors['text'] as $value => $name)
                        <option value="{{ $value }}" {{ old('theme_text', $role->theme_text) === $value ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Accent Color --}}
                <div>
                    <label for="theme_accent" class="block text-sm text-gray-600 mb-1">لون التمييز</label>
                    <select name="theme_accent" id="theme_accent" class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:ring-indigo-500 focus:border-indigo-500">
                        <option value="">افتراضي</option>
                        @foreach($themeColors['accent'] as $value => $name)
                        <option value="{{ $value }}" {{ old('theme_accent', $role->theme_accent) === $value ? 'selected' : '' }}>{{ $name }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Badge Color - Custom Hex Picker --}}
                <div>
                    <label for="custom_hex_color" class="block text-sm text-gray-600 mb-1">لون الشارة (مخصص)</label>
                    <div class="flex items-center gap-2">
                        <input type="color" name="custom_hex_color" id="custom_hex_color" value="{{ old('custom_hex_color', $role->custom_hex_color ?? '#6366f1') }}" class="w-12 h-10 rounded border border-gray-300 cursor-pointer">
                        <span id="badge_hex_value" class="text-sm text-gray-500 font-mono">{{ old('custom_hex_color', $role->custom_hex_color ?? '#6366f1') }}</span>
                        <button type="button" id="clear_hex_color" class="text-xs text-gray-500 hover:text-red-600">مسح</button>
                    </div>
                    <p class="text-xs text-gray-400 mt-1">اختر لوناً مخصصاً للشارة</p>
                </div>
            </div>
        </div>

        @if(!$role->isSuperAdmin())
        <div class="mt-6">
            <label class="block text-gray-700 font-medium mb-4">الصلاحيات</label>

            @foreach($permissions as $group => $groupPermissions)
            <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                <h3 class="font-medium text-gray-800 mb-3">{{ $group ?? 'عام' }}</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-3">
                    @foreach($groupPermissions as $permission)
                    <label class="flex items-center">
                        <input
                            type="checkbox"
                            name="permissions[]"
                            value="{{ $permission->id }}"
                            {{ in_array($permission->id, old('permissions', $rolePermissionIds)) ? 'checked' : '' }}
                            class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                        >
                        <span class="mr-2 text-gray-700">{{ $permission->name }}</span>
                    </label>
                    @endforeach
                </div>
            </div>
            @endforeach
        </div>
        @endif

        <div class="mt-6 flex gap-4">
            <button
                type="submit"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"
            >
                حفظ التغييرات
            </button>
            <a href="{{ route('roles.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const hexInput = document.getElementById('custom_hex_color');
    const hexValue = document.getElementById('badge_hex_value');
    const clearBtn = document.getElementById('clear_hex_color');

    if (hexInput && hexValue) {
        hexInput.addEventListener('input', function() {
            hexValue.textContent = this.value;
        });
    }

    if (clearBtn && hexInput) {
        clearBtn.addEventListener('click', function() {
            hexInput.value = '#6366f1';
            hexValue.textContent = '#6366f1';
        });
    }
});
</script>
@endsection
