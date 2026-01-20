<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Models\Track;

class TeacherCertificateRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        // Get tracks from both config and database
        $configKeys = array_keys(config('certificates.tracks_teacher', []));
        $dbKeys = Track::where('active', true)->pluck('key')->toArray();
        $allKeys = array_merge($configKeys, $dbKeys);
        $keys = implode(',', array_unique($allKeys));

        return [
            'name_ar'          => ['required','string','max:150'],
            'name_en'          => ['required','string','max:150'],
            'track_key'        => ['required','string','in:'.$keys],
            'gender'           => ['required','in:male,female'],
            'certificate_date' => ['nullable','date'],
            // duration mode (range|end)
            'duration_mode' => ['nullable', 'in:range,end'],

            //  the "to" date (used for both range end and end-only)
            'duration_to'   => ['nullable', 'date'],
            'duration_from'    => ['nullable','date'],
            'ar_duration'   => ['nullable', 'boolean'],
            'en_duration'   => ['nullable', 'boolean'],
            'custom_positions' => ['nullable','string'],
            'photo'            => ['nullable','file','mimes:jpg,jpeg,png,webp','max:4096'], //
        ];
    }

    public function messages(): array
    {
        return [
            'name_ar.required'   => 'الاسم بالعربية مطلوب.',
            'name_en.required'   => 'الاسم بالإنجليزية مطلوب.',
            'track_key.required' => 'يرجى اختيار اسم المسار.',
            'track_key.in'       => 'المسار المحدد غير صالح.',
            'gender.required'    => 'يرجى اختيار الجنس.',
            'photo.file'         => 'الملف يجب أن يكون صورة.',
            'photo.mimes'        => 'صيغة الصورة يجب أن تكون: jpg, jpeg, png, webp.',
            'photo.max'          => 'الحجم الأقصى للصورة 4MB.',
        ];
    }
}
