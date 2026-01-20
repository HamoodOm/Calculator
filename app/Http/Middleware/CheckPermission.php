<?php

namespace App\Http\Middleware;

use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * Check if the authenticated user has one of the specified permissions.
     * Redirects to the best available page if user lacks permission.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  ...$permissions  Permission slugs to check
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$permissions)
    {
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        $user = Auth::user();

        // Check if user is active
        if (!$user->is_active) {
            Auth::logout();
            return redirect()->route('login')
                ->withErrors(['email' => 'تم تعطيل حسابك. يرجى التواصل مع المسؤول.']);
        }

        // Super admin has access to everything
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Check if user has any of the required permissions
        if ($user->hasAnyPermission($permissions)) {
            return $next($request);
        }

        // Redirect to the best available page based on user's permissions
        return $this->redirectToAccessiblePage($user);
    }

    /**
     * Redirect user to a page they have access to
     *
     * @param  \App\Models\User  $user
     * @return \Illuminate\Http\RedirectResponse
     */
    protected function redirectToAccessiblePage($user)
    {
        // Check permissions in order of priority
        if ($user->hasPermission(Permission::TEACHER_SIMPLE_VIEW)) {
            return redirect()->route('teacher.index')
                ->with('status', 'ليس لديك صلاحية للوصول إلى تلك الصفحة. تم توجيهك إلى صفحة شهادات المعلمين.');
        }

        if ($user->hasPermission(Permission::STUDENT_SIMPLE_VIEW)) {
            return redirect()->route('students.index')
                ->with('status', 'ليس لديك صلاحية للوصول إلى تلك الصفحة. تم توجيهك إلى صفحة شهادات الطلاب.');
        }

        if ($user->hasPermission(Permission::TEACHER_ADMIN_VIEW)) {
            return redirect()->route('teacher.admin.index')
                ->with('status', 'ليس لديك صلاحية للوصول إلى تلك الصفحة. تم توجيهك إلى صفحة إدارة شهادات المعلمين.');
        }

        if ($user->hasPermission(Permission::STUDENT_ADMIN_VIEW)) {
            return redirect()->route('students.admin.index')
                ->with('status', 'ليس لديك صلاحية للوصول إلى تلك الصفحة. تم توجيهك إلى صفحة إدارة شهادات الطلاب.');
        }

        if ($user->hasPermission(Permission::USERS_VIEW)) {
            return redirect()->route('users.index')
                ->with('status', 'ليس لديك صلاحية للوصول إلى تلك الصفحة. تم توجيهك إلى صفحة إدارة المستخدمين.');
        }

        if ($user->hasPermission(Permission::ROLES_VIEW)) {
            return redirect()->route('roles.index')
                ->with('status', 'ليس لديك صلاحية للوصول إلى تلك الصفحة. تم توجيهك إلى صفحة إدارة الأدوار.');
        }

        // No permissions at all - go to dashboard with message
        return redirect()->route('dashboard')
            ->with('status', 'ليس لديك صلاحيات للوصول إلى أي صفحة. يرجى التواصل مع المسؤول.');
    }
}
