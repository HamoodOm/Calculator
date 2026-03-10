@php
    $user = auth()->user();

    // Get theme from user's getThemeColors method (considers custom_color, institution, then role)
    $theme = $user ? $user->getThemeColors() : [
        'bg' => 'bg-gray-800',
        'hover' => 'hover:bg-gray-900',
        'text' => 'text-gray-100',
        'accent' => 'text-gray-300',
        'badge' => 'bg-gray-600',
    ];

    // Check if using hex colors
    $useHexBg = !empty($theme['bg_hex']);
    $useHexBadge = !empty($theme['badge_hex']);
    $bgStyle = $useHexBg ? "background-color: {$theme['bg_hex']};" : '';
    $badgeStyle = $useHexBadge ? "background-color: {$theme['badge_hex']};" : '';
@endphp

<nav class="{{ $theme['bg'] }} shadow-lg" @if($useHexBg) style="{{ $bgStyle }}" @endif>
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex items-center">
                <a href="{{ route('dashboard') }}" class="text-xl font-bold text-white">
                    نظام الشهادات
                </a>

                @auth
                <div class="hidden md:flex items-center mr-8 space-x-4 space-x-reverse">
                    {{-- Teacher Pages --}}
                    @if($user->canAccessTeacherSimple() || $user->canAccessTeacherAdmin())
                    <div class="relative group">
                        <button class="{{ $theme['text'] }} {{ $theme['hover'] }} px-3 py-2 text-sm font-medium rounded-md flex items-center">
                            المعلمين
                            <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            @if($user->canAccessTeacherSimple())
                            <a href="{{ route('teacher.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                الواجهة البسيطة
                            </a>
                            @endif
                            @if($user->canAccessTeacherAdmin())
                            <a href="{{ route('teacher.admin.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                المحرر المتقدم
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Student Pages --}}
                    @if($user->canAccessStudentSimple() || $user->canAccessStudentAdmin())
                    <div class="relative group">
                        <button class="{{ $theme['text'] }} {{ $theme['hover'] }} px-3 py-2 text-sm font-medium rounded-md flex items-center">
                            الطلاب
                            <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            @if($user->canAccessStudentSimple())
                            <a href="{{ route('students.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                الواجهة البسيطة
                            </a>
                            @endif
                            @if($user->canAccessStudentAdmin())
                            <a href="{{ route('students.admin.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                المحرر المتقدم
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif

                    {{-- Admin Menu --}}
                    @if($user->canManageUsers() || $user->canManageRoles() || $user->canManageInstitutions() || $user->hasPermission(\App\Models\Permission::TRACKS_VIEW) || $user->hasPermission(\App\Models\Permission::ACTIVITY_LOGS_VIEW) || $user->hasPermission(\App\Models\Permission::API_CLIENTS_VIEW))
                    <div class="relative group">
                        <button class="{{ $theme['text'] }} {{ $theme['hover'] }} px-3 py-2 text-sm font-medium rounded-md flex items-center">
                            الإدارة
                            <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </button>
                        <div class="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                            @if($user->canManageUsers())
                            <a href="{{ route('users.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                المستخدمين
                            </a>
                            @endif
                            @if($user->canManageRoles())
                            <a href="{{ route('roles.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                الأدوار والصلاحيات
                            </a>
                            @endif
                            @if($user->canManageInstitutions())
                            <a href="{{ route('institutions.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                المؤسسات
                            </a>
                            @endif
                            @if($user->hasPermission(\App\Models\Permission::TRACKS_VIEW))
                            <a href="{{ route('tracks.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                المسارات
                            </a>
                            @endif
                            @if($user->hasPermission(\App\Models\Permission::ACTIVITY_LOGS_VIEW))
                            <a href="{{ route('activity-logs.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                سجل النشاطات
                            </a>
                            @endif
                            @if($user->hasPermission(\App\Models\Permission::API_CLIENTS_VIEW))
                            <a href="{{ route('api-clients.index') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                                عملاء API
                            </a>
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
                @endauth
            </div>

            <div class="flex items-center">
                @auth
                <div class="relative group">
                    <button class="flex items-center {{ $theme['text'] }} text-sm font-medium">
                        <span class="hidden sm:block">{{ $user->name }}</span>
                        <span class="mr-2 px-2 py-1 text-xs {{ $theme['badge'] }} text-white rounded-full" @if($useHexBadge) style="{{ $badgeStyle }}" @endif>
                            {{ $user->role->name ?? 'بدون صلاحيات' }}
                        </span>
                        <svg class="mr-1 w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                        </svg>
                    </button>
                    <div class="absolute left-0 mt-2 w-48 bg-white rounded-md shadow-lg opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-50">
                        <div class="px-4 py-2 border-b">
                            <p class="text-sm font-medium text-gray-900">{{ $user->name }}</p>
                            <p class="text-xs text-gray-500">{{ $user->email }}</p>
                            @if($user->institution)
                            <p class="text-xs text-gray-400 mt-1">{{ $user->institution->name }}</p>
                            @endif
                        </div>
                        <a href="{{ route('dashboard') }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                            لوحة التحكم
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="block w-full text-right px-4 py-2 text-sm text-red-600 hover:bg-gray-100">
                                تسجيل الخروج
                            </button>
                        </form>
                    </div>
                </div>
                @else
                <a href="{{ route('login') }}" class="text-white hover:text-gray-200 text-sm font-medium">
                    تسجيل الدخول
                </a>
                @endauth
            </div>
        </div>
    </div>

    {{-- Mobile Navigation --}}
    @auth
    <div class="md:hidden {{ $theme['bg'] }} border-t border-white/10" @if($useHexBg) style="{{ $bgStyle }}" @endif>
        <div class="px-4 py-2 space-y-1">
            @if($user->canAccessTeacherSimple())
            <a href="{{ route('teacher.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">شهادات المعلمين</a>
            @endif
            @if($user->canAccessTeacherAdmin())
            <a href="{{ route('teacher.admin.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">إدارة شهادات المعلمين</a>
            @endif
            @if($user->canAccessStudentSimple())
            <a href="{{ route('students.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">شهادات الطلاب</a>
            @endif
            @if($user->canAccessStudentAdmin())
            <a href="{{ route('students.admin.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">إدارة شهادات الطلاب</a>
            @endif
            @if($user->canManageUsers())
            <a href="{{ route('users.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">المستخدمين</a>
            @endif
            @if($user->canManageRoles())
            <a href="{{ route('roles.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">الأدوار والصلاحيات</a>
            @endif
            @if($user->canManageInstitutions())
            <a href="{{ route('institutions.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">المؤسسات</a>
            @endif
            @if($user->hasPermission(\App\Models\Permission::ACTIVITY_LOGS_VIEW))
            <a href="{{ route('activity-logs.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">سجل النشاطات</a>
            @endif
            @if($user->hasPermission(\App\Models\Permission::API_CLIENTS_VIEW))
            <a href="{{ route('api-clients.index') }}" class="block px-3 py-2 text-sm {{ $theme['text'] }} {{ $theme['hover'] }} rounded">عملاء API</a>
            @endif
        </div>
    </div>
    @endauth
</nav>
