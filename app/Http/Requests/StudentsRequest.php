<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Track;

class StudentsRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Get config tracks
        $configKeys = array_keys(config('certificates.tracks_student', []));

        // Get database tracks (student tracks only - not starting with 't_')
        $dbKeys = Track::where('active', true)
            ->where(function($q) {
                $q->where('key', 'like', 's_%')
                  ->orWhere('key', 'not like', 't_%');
            })
            ->pluck('key')
            ->toArray();

        // Combine both config and database tracks
        $allKeys = array_merge($configKeys, $dbKeys);
        $keys = implode(',', $allKeys);

        return [
            'track_key'        => ['required','string','in:'.$keys],
            'gender'           => ['required','in:male,female'],
            'certificate_date' => ['nullable','date'],
            //'duration_from'    => ['nullable','date'],
            'students_file'    => ['required','file','mimes:csv,txt,xlsx','max:20480'],
            'images_zip'       => ['nullable','file','mimes:zip','max:51200'],
            // UI saves percentages; server converts to mm and merges with template
            'custom_positions' => ['nullable','string'],
            // app/Http/Requests/TeacherCertificateRequest.php

            'duration_mode' => ['nullable', 'in:range,end'],
            'duration_from' => ['nullable', 'date'],
            'duration_to'   => ['nullable', 'date'],

        ];
    }

    public function messages(): array
    {
        return [
            'track_key.required'     => 'يجب اختيار المسار.',
            'track_key.in'           => 'المسار المختار غير صالح.',
            'gender.required'        => 'الرجاء اختيار الجنس.',
            'gender.in'              => 'قيمة الجنس غير صحيحة.',
            'students_file.required' => 'يجب رفع ملف الطلاب.',
            'students_file.mimes'    => 'الملف يجب أن يكون CSV أو TXT أو XLSX.',
            'images_zip.mimes'       => 'ملف الصور يجب أن يكون ZIP.',
        ];
    }
}
