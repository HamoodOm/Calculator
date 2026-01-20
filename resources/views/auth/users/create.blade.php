@extends('auth.layouts.app')

@section('title', 'إضافة مستخدم جديد')

@section('content')
<div class="mb-6">
    <a href="{{ route('users.index') }}" class="text-indigo-600 hover:text-indigo-800">&larr; العودة للمستخدمين</a>
</div>

<div class="bg-white rounded-lg shadow p-6">
    <h1 class="text-2xl font-bold text-gray-800 mb-6">إضافة مستخدم جديد</h1>

    <form method="POST" action="{{ route('users.store') }}">
        @csrf

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="name" class="block text-gray-700 font-medium mb-2">الاسم</label>
                <input
                    type="text"
                    id="name"
                    name="name"
                    value="{{ old('name') }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="أدخل الاسم الكامل"
                >
            </div>

            <div>
                <label for="email" class="block text-gray-700 font-medium mb-2">البريد الإلكتروني</label>
                <input
                    type="email"
                    id="email"
                    name="email"
                    value="{{ old('email') }}"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="example@domain.com"
                    dir="ltr"
                >
            </div>

            <div>
                <label for="password" class="block text-gray-700 font-medium mb-2">كلمة المرور</label>
                <input
                    type="password"
                    id="password"
                    name="password"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="8 أحرف على الأقل"
                >
            </div>

            <div>
                <label for="password_confirmation" class="block text-gray-700 font-medium mb-2">تأكيد كلمة المرور</label>
                <input
                    type="password"
                    id="password_confirmation"
                    name="password_confirmation"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                    placeholder="أعد إدخال كلمة المرور"
                >
            </div>

            <div>
                <label for="role_id" class="block text-gray-700 font-medium mb-2">الدور</label>
                <select
                    id="role_id"
                    name="role_id"
                    required
                    class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                >
                    <option value="">اختر الدور...</option>
                    @foreach($roles as $role)
                    <option value="{{ $role->id }}" {{ old('role_id') == $role->id ? 'selected' : '' }}>
                        {{ $role->name }}
                    </option>
                    @endforeach
                </select>
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
                    <option value="{{ $institution->id }}" {{ old('institution_id', $defaultInstitutionId) == $institution->id ? 'selected' : '' }}>
                        {{ $institution->name }}
                    </option>
                    @endforeach
                </select>
                @if(!$canSelectInstitution && $defaultInstitutionId)
                <input type="hidden" name="institution_id" value="{{ $defaultInstitutionId }}">
                <p class="text-sm text-gray-500 mt-1">سيتم إضافة المستخدم لمؤسستك تلقائياً</p>
                @endif
            </div>
            @endif

            {{-- Custom Color (Super users only) --}}
            @if(auth()->user()->isSuperUser())
            <div>
                <label class="block text-gray-700 font-medium mb-2">لون مخصص (اختياري)</label>
                <div class="grid grid-cols-5 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="custom_color" value="" class="sr-only peer" {{ old('custom_color') === null ? 'checked' : '' }}>
                        <div class="w-full h-10 bg-gray-200 rounded-lg flex items-center justify-center text-gray-600 text-xs peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-gray-900 transition">
                            افتراضي
                        </div>
                    </label>
                    @foreach($colors as $colorClass => $colorName)
                    <label class="cursor-pointer">
                        <input type="radio" name="custom_color" value="{{ $colorClass }}" class="sr-only peer" {{ old('custom_color') === $colorClass ? 'checked' : '' }}>
                        <div class="w-full h-10 {{ $colorClass }} rounded-lg peer-checked:ring-2 peer-checked:ring-offset-2 peer-checked:ring-gray-900 transition" title="{{ $colorName }}"></div>
                    </label>
                    @endforeach
                </div>
            </div>
            @endif

            <div class="flex items-center">
                <label class="flex items-center">
                    <input
                        type="checkbox"
                        name="is_active"
                        value="1"
                        {{ old('is_active', true) ? 'checked' : '' }}
                        class="w-4 h-4 text-indigo-600 border-gray-300 rounded focus:ring-indigo-500"
                    >
                    <span class="mr-2 text-gray-700">حساب نشط</span>
                </label>
            </div>
        </div>

        <div class="mt-6 flex gap-4">
            <button
                type="submit"
                class="bg-indigo-600 text-white px-6 py-2 rounded-lg hover:bg-indigo-700 transition"
            >
                إنشاء المستخدم
            </button>
            <a href="{{ route('users.index') }}" class="bg-gray-200 text-gray-700 px-6 py-2 rounded-lg hover:bg-gray-300 transition">
                إلغاء
            </a>
        </div>
    </form>
</div>
@endsection
