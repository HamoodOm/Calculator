#!/bin/bash

echo "=== Quick Verification Script ==="
echo ""

echo "1. Checking if new files exist..."
if [ -f "app/Models/StudentSetting.php" ]; then
    echo "✅ StudentSetting.php exists"
else
    echo "❌ StudentSetting.php NOT FOUND"
fi

if [ -f "database/migrations/2025_11_04_000000_create_student_settings_table.php" ]; then
    echo "✅ Migration file exists"
else
    echo "❌ Migration file NOT FOUND"
fi

if [ -f "docs/student-guide.md" ]; then
    echo "✅ Student guide exists"
else
    echo "❌ Student guide NOT FOUND"
fi

echo ""
echo "2. Checking for key features in StudentCertificatesController..."
if grep -q "PrintFlags::fromRequest" app/Http/Controllers/StudentCertificatesController.php; then
    echo "✅ Print flags support found"
else
    echo "❌ Print flags support NOT FOUND"
fi

if grep -q "function addTrack" app/Http/Controllers/StudentCertificatesController.php; then
    echo "✅ addTrack method found"
else
    echo "❌ addTrack method NOT FOUND"
fi

if grep -q "function deleteTrack" app/Http/Controllers/StudentCertificatesController.php; then
    echo "✅ deleteTrack method found"
else
    echo "❌ deleteTrack method NOT FOUND"
fi

if grep -q "function save" app/Http/Controllers/StudentCertificatesController.php; then
    echo "✅ save method found"
else
    echo "❌ save method NOT FOUND"
fi

if grep -q "strpos.*t_" app/Http/Controllers/StudentCertificatesController.php; then
    echo "✅ Teacher track filtering found"
else
    echo "❌ Teacher track filtering NOT FOUND"
fi

echo ""
echo "3. Checking for UI features in student view..."
if grep -q "@include('partials.print-flags')" resources/views/students/index.blade.php; then
    echo "✅ Print flags UI found"
else
    echo "❌ Print flags UI NOT FOUND"
fi

if grep -q "openAddTrackModal" resources/views/students/index.blade.php; then
    echo "✅ Track management UI found"
else
    echo "❌ Track management UI NOT FOUND"
fi

if grep -q "saveDefaults" resources/views/students/index.blade.php; then
    echo "✅ Save defaults button found"
else
    echo "❌ Save defaults button NOT FOUND"
fi

echo ""
echo "4. Checking routes..."
if grep -q "students.save" routes/web.php; then
    echo "✅ Save defaults route found"
else
    echo "❌ Save defaults route NOT FOUND"
fi

if grep -q "students.tracks.add" routes/web.php; then
    echo "✅ Add track route found"
else
    echo "❌ Add track route NOT FOUND"
fi

if grep -q "students.tracks.delete" routes/web.php; then
    echo "✅ Delete track route found"
else
    echo "❌ Delete track route NOT FOUND"
fi

echo ""
echo "=== Verification Complete ==="
echo ""
echo "Next steps:"
echo "1. Run: php artisan migrate"
echo "2. Run: php artisan cache:clear"
echo "3. Run: php artisan view:clear"
echo "4. Visit: http://your-domain/students"
