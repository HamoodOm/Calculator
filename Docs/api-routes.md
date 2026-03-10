# API & Routes Documentation

## Route Overview

```bash
php artisan route:list | grep teacher
```

## Admin Routes

### GET /teacher/admin

**Purpose**: Display admin certificate editor

**Controller**: `AdminTeacherController@index`

**Authentication**: None (add middleware if needed)

**Response**: Blade view with full editor

**Query Parameters**: None

**Example**:
```
GET http://localhost:8000/teacher/admin
```

---

### POST /teacher/admin

**Purpose**: Generate final certificate with custom settings

**Controller**: `AdminTeacherController@store`

**Method**: POST

**Content-Type**: `multipart/form-data`

**Form Data**:
```php
[
    'track_key' => 't_full_stack_web',        // required
    'gender' => 'male',                        // required
    'name_ar' => 'محمد أحمد',                 // required
    'name_en' => 'Mohammed Ahmed',             // required
    'duration_mode' => 'range',                // 'range' or 'end'
    'duration_from' => '2024-01-01',           // optional
    'duration_to' => '2024-04-30',             // optional
    'photo' => <file>,                         // optional
    'remove_photo' => '0',                     // '0' or '1'
    'custom_positions' => '{"ar_name":{...}}', // JSON string
    'style' => [
        'colors' => ['ar_name' => '#334155', ...],
        'font_per' => ['ar_name' => 'Cairo', ...],
        'weight_per' => ['ar_name' => 'bold', ...],
        'size_per' => ['ar_name' => '7.0', ...],
    ],
    'print' => [
        'ar_name' => '1',
        'en_name' => '1',
        // ... other print flags
    ],
    'arabic_only' => '0',   // optional
    'english_only' => '0',  // optional
]
```

**Response**: Redirect with flash data
```php
[
    'success' => 'تم إنشاء الشهادة بنجاح.',
    'download_url' => 'http://localhost/download?...'
]
```

**Example**:
```bash
curl -X POST http://localhost:8000/teacher/admin \
  -F "track_key=t_full_stack_web" \
  -F "gender=male" \
  -F "name_ar=محمد أحمد" \
  -F "name_en=Mohammed Ahmed"
```

---

### POST /teacher/admin/preview

**Purpose**: Generate preview PDF

**Controller**: `AdminTeacherController@preview`

**Method**: POST

**Form Data**: Same as POST /teacher/admin

**Response**: PDF file (inline display)

**Headers**:
```
Content-Type: application/pdf
Content-Disposition: inline; filename="preview.pdf"
Cache-Control: no-store, no-cache, must-revalidate, max-age=0
```

**Example**:
```
POST http://localhost:8000/teacher/admin/preview
Form: (same as store endpoint)
```

---

### POST /teacher/admin/save-options

**Purpose**: Save certificate defaults to database

**Controller**: `AdminTeacherController@save`

**Method**: POST

**Form Data**:
```php
[
    'track_key' => 't_full_stack_web',  // required
    'gender' => 'male',                  // required
    'custom_positions' => '{"ar_name":{...}}',
    'style' => [...],
    'print' => [...],
    'duration_mode' => 'range',
    'notes' => 'Optional admin notes'
]
```

**Database Action**:
```php
TeacherSetting::updateOrCreate(
    ['track_id' => $trackId, 'gender' => $gender],
    [...settings...]
);
```

**Response**: Redirect with success message

**Example**:
```bash
curl -X POST http://localhost:8000/teacher/admin/save-options \
  -F "track_key=t_full_stack_web" \
  -F "gender=male" \
  -F "custom_positions={...}"
```

---

### GET /teacher/admin/bg/{track}/{gender}

**Purpose**: Serve background image for editor

**Controller**: `AdminTeacherController@bg`

**Method**: GET

**Parameters**:
- `track`: Track key (e.g., `t_full_stack_web`)
- `gender`: `male` or `female`

**Response**: Image file

**Example**:
```
GET http://localhost:8000/teacher/admin/bg/t_full_stack_web/male
```

---

## Simplified Teacher Routes

### GET /teacher

**Purpose**: Display simplified teacher form

**Controller**: `SimpleTeacherController@index`

**Response**: Blade view with form + track settings

**Data Passed to View**:
```php
[
    'tracks' => Collection<Track>,
    'trackSettings' => [
        't_full_stack_web' => [
            'male' => ['date_type' => 'duration'],
            'female' => ['date_type' => 'end']
        ],
        // ... other tracks
    ]
]
```

**Example**:
```
GET http://localhost:8000/teacher
```

---

### POST /teacher

**Purpose**: Generate certificate with defaults

**Controller**: `SimpleTeacherController@store`

**Method**: POST

**Form Data**:
```php
[
    'track_key' => 't_full_stack_web',  // required
    'gender' => 'male',                  // required
    'name_ar' => 'محمد أحمد',           // required
    'name_en' => 'Mohammed Ahmed',       // required
    'date_mode' => 'duration',           // 'duration' or 'end'
    'duration_from' => '2024-01-01',     // if date_mode=duration
    'duration_to' => '2024-04-30',       // if date_mode=duration
    'photo' => <file>,                   // optional
    'remove_photo' => '0'                // '0' or '1'
]
```

**Process**:
1. Load template via TemplateResolver (includes DB overrides)
2. Apply saved print defaults
3. Generate PDF
4. Clear photo session
5. Return download URL

**Response**: Redirect with flash data
```php
[
    'success' => 'تم إنشاء الشهادة بنجاح.',
    'download_url' => 'http://localhost/download?...'
]
```

**Example**:
```bash
curl -X POST http://localhost:8000/teacher \
  -F "track_key=t_full_stack_web" \
  -F "gender=male" \
  -F "name_ar=محمد أحمد" \
  -F "name_en=Mohammed Ahmed"
```

---

### POST /teacher/preview

**Purpose**: Preview certificate with defaults

**Controller**: `SimpleTeacherController@preview`

**Method**: POST

**Form Data**: Same as POST /teacher

**Response**: PDF file (inline)

**Example**:
```
POST http://localhost:8000/teacher/preview
Form: (same as store endpoint)
```

---

## Shared Routes

### GET /download

**Purpose**: Download generated certificate

**Controller**: `TeacherCertificateController@download`

**Method**: GET

**Middleware**: `signed` (validates signature)

**Query Parameters**:
- `p`: Encrypted path to PDF file
- `signature`: Signed URL signature (auto-added)
- `expires`: Expiration timestamp (auto-added)

**Response**: PDF file download

**Example**:
```
GET http://localhost:8000/download?p=eyJpdiI6...&signature=...&expires=...
```

**Generate Signed URL**:
```php
$url = URL::temporarySignedRoute('download', now()->addMinutes(60), [
    'p' => Crypt::encryptString('generated/certificate.pdf')
]);
```

---

### GET /template-info

**Purpose**: Get template information (positions, styles, background)

**Controller**: `TemplateController@info`

**Method**: GET

**Query Parameters**:
- `role`: `teacher` or `student` (required)
- `track_key`: Track identifier (required)
- `gender`: `male` or `female` (required)

**Response**: JSON

```json
{
  "success": true,
  "background_url": "http://localhost/images/templates/teacher/...",
  "positions": {
    "cert_date": {"top": 23, "right": 56, "width": 78, "font": 5},
    "ar_name": {"top": 78, "right": 12, "width": 90, "font": 7}
  },
  "style": {
    "font": {"ar": "Amiri", "en": "DejaVu Sans"},
    "colors": {"ar_name": "#334155", "en_name": "#0f172a"}
  }
}
```

**Process**:
1. Call `TemplateResolver::resolve()`
2. Returns config + DB merged data
3. Used by position editor JavaScript

**Example**:
```bash
curl "http://localhost:8000/template-info?role=teacher&track_key=t_full_stack_web&gender=male"
```

---

## Student Routes (Existing)

### GET /students

**Purpose**: Display student bulk certificate form

**Controller**: `StudentCertificatesController@index`

**Example**:
```
GET http://localhost:8000/students
```

### POST /students

**Purpose**: Generate multiple certificates from CSV/Excel

**Controller**: `StudentCertificatesController@store`

### POST /students/preview

**Purpose**: Preview student certificates

**Controller**: `StudentCertificatesController@preview`

### POST /students/clear

**Purpose**: Clear uploaded student data

**Controller**: `StudentCertificatesController@clear`

### GET /students/template/{type}

**Purpose**: Download CSV/Excel template

**Controller**: `StudentCertificatesController@template`

**Parameters**:
- `type`: `csv` or `xlsx`

**Example**:
```
GET http://localhost:8000/students/template/csv
GET http://localhost:8000/students/template/xlsx
```

### GET /students/bg/{track}/{gender}

**Purpose**: Serve background for student editor

**Controller**: `StudentCertificatesController@bg`

---

## HTTP Status Codes

| Code | Meaning | When |
|------|---------|------|
| 200 | OK | Successful GET request |
| 302 | Redirect | After POST (form submission) |
| 403 | Forbidden | Signed URL expired/invalid |
| 404 | Not Found | Template/track/file not found |
| 422 | Validation Error | Invalid form input |
| 500 | Server Error | Database/PHP error |

## Error Handling

### Validation Errors (422)

**Response**: Redirect back with errors

```php
Session::get('errors')->all()
```

**Example Blade**:
```blade
@if ($errors->any())
  <div class="errors">
    @foreach ($errors->all() as $error)
      <li>{{ $error }}</li>
    @endforeach
  </div>
@endif
```

### Not Found Errors (404)

**Response**: Laravel 404 page

**Customize**: Edit `resources/views/errors/404.blade.php`

### Server Errors (500)

**Response**: Laravel error page (development) or generic error (production)

**Logs**: Check `storage/logs/laravel.log`

## CORS (if needed)

To enable CORS for API endpoints:

**File**: `config/cors.php`

```php
'paths' => ['api/*', 'template-info'],
'allowed_origins' => ['http://localhost:3000'],
```

**Middleware**: Apply `cors` middleware to routes

## Rate Limiting

To prevent abuse:

```php
Route::middleware('throttle:60,1')->group(function () {
    Route::post('/teacher', ...);
    Route::post('/teacher/preview', ...);
});
```

**Limits**: 60 requests per minute

## Authentication (Optional)

To require login:

```php
Route::middleware('auth')->group(function () {
    Route::prefix('teacher/admin')->group(function () {
        // Admin routes protected
    });
});
```

## API Testing

### Postman Collection

Create collection with:
- Environment variables: `{{base_url}}`
- Saved requests for each endpoint
- CSRF token handling

### cURL Examples

**Save Default Settings**:
```bash
curl -X POST http://localhost:8000/teacher/admin/save-options \
  -H "Content-Type: multipart/form-data" \
  -F "track_key=t_full_stack_web" \
  -F "gender=male" \
  -F "_token=YOUR_CSRF_TOKEN"
```

**Get Template Info**:
```bash
curl "http://localhost:8000/template-info?role=teacher&track_key=t_full_stack_web&gender=male" | jq
```

## Webhook Integration (Future)

Potential webhook for certificate generation:

```php
POST /api/webhooks/certificate-generated
{
  "event": "certificate.generated",
  "track_key": "t_full_stack_web",
  "gender": "male",
  "student_name": "Mohammed Ahmed",
  "download_url": "...",
  "generated_at": "2026-01-15T10:30:00Z"
}
```
