<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Institution;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InstitutionController extends Controller
{
    /**
     * Display a listing of institutions.
     */
    public function index()
    {
        $institutions = Institution::withCount(['users', 'tracks'])->get();

        return view('auth.institutions.index', [
            'institutions' => $institutions,
        ]);
    }

    /**
     * Show the form for creating a new institution.
     */
    public function create()
    {
        $colors = Institution::getAvailableColors();

        return view('auth.institutions.create', [
            'colors' => $colors,
        ]);
    }

    /**
     * Store a newly created institution.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:institutions'],
            'description' => ['nullable', 'string', 'max:500'],
            'header_color' => ['nullable', 'string', 'max:50'],
            'custom_hex_color' => ['nullable', 'string', 'max:10', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_type' => ['nullable', 'string', 'in:hex,preset'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($request->name);

        // Ensure unique slug
        $baseSlug = $slug;
        $counter = 1;
        while (Institution::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
        }

        // Determine color based on color_type selection
        $useHexColor = $request->color_type === 'hex' && $request->custom_hex_color;
        $headerColor = $useHexColor ? 'bg-gray-700' : ($request->header_color ?? 'bg-indigo-700');
        $customHexColor = $useHexColor ? $request->custom_hex_color : null;

        Institution::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'header_color' => $headerColor,
            'custom_hex_color' => $customHexColor,
            'badge_color' => str_replace('-700', '-500', $headerColor),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('institutions.index')
            ->with('status', 'تم إنشاء المؤسسة بنجاح!');
    }

    /**
     * Show the form for editing an institution.
     */
    public function edit(Institution $institution)
    {
        $colors = Institution::getAvailableColors();

        return view('auth.institutions.edit', [
            'institution' => $institution,
            'colors' => $colors,
        ]);
    }

    /**
     * Update the specified institution.
     */
    public function update(Request $request, Institution $institution)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:institutions,name,' . $institution->id],
            'description' => ['nullable', 'string', 'max:500'],
            'header_color' => ['nullable', 'string', 'max:50'],
            'custom_hex_color' => ['nullable', 'string', 'max:10', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'color_type' => ['nullable', 'string', 'in:hex,preset'],
            'is_active' => ['boolean'],
        ]);

        // Determine color based on color_type selection
        $useHexColor = $request->color_type === 'hex' && $request->custom_hex_color;
        $headerColor = $useHexColor ? 'bg-gray-700' : ($request->header_color ?? $institution->header_color);
        $customHexColor = $useHexColor ? $request->custom_hex_color : null;

        $institution->update([
            'name' => $request->name,
            'description' => $request->description,
            'header_color' => $headerColor,
            'custom_hex_color' => $customHexColor,
            'badge_color' => str_replace('-700', '-500', $headerColor),
            'is_active' => $request->boolean('is_active', true),
        ]);

        return redirect()->route('institutions.index')
            ->with('status', 'تم تحديث المؤسسة بنجاح!');
    }

    /**
     * Remove the specified institution.
     */
    public function destroy(Institution $institution)
    {
        // Check if institution has users
        if ($institution->users()->count() > 0) {
            return back()->withErrors(['error' => 'لا يمكن حذف مؤسسة لها مستخدمين. قم بنقل المستخدمين أولاً.']);
        }

        // Check if institution has tracks
        if ($institution->tracks()->count() > 0) {
            return back()->withErrors(['error' => 'لا يمكن حذف مؤسسة لها مسارات. قم بحذف المسارات أولاً.']);
        }

        $institution->delete();

        return redirect()->route('institutions.index')
            ->with('status', 'تم حذف المؤسسة بنجاح!');
    }

    /**
     * Toggle institution active status.
     */
    public function toggle(Institution $institution)
    {
        $institution->update([
            'is_active' => !$institution->is_active,
        ]);

        $status = $institution->is_active ? 'تم تفعيل' : 'تم تعطيل';

        return back()->with('status', "{$status} المؤسسة: {$institution->name}");
    }

    /**
     * Toggle track active status.
     */
    public function toggleTrack(Institution $institution, $track)
    {
        $trackModel = \App\Models\Track::findOrFail($track);

        // Ensure track belongs to this institution
        if ($trackModel->institution_id !== $institution->id) {
            abort(403, 'المسار لا ينتمي لهذه المؤسسة');
        }

        $trackModel->update([
            'active' => !$trackModel->active,
        ]);

        $status = $trackModel->active ? 'تم تفعيل' : 'تم تعطيل';

        return back()->with('status', "{$status} المسار: {$trackModel->name_ar}");
    }
}
