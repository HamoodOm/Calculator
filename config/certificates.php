<?php

return [
    // === Separate dropdown lists ===
    'tracks_teacher' => [
        't_laravel_fundamentals' => ['ar' => 'أساسيات لارافيل (معلم)', 'en' => 'Laravel Fundamentals (Teacher)'],
        't_full_stack_web'       => ['ar' => 'تطوير الويب المتكامل (معلم)', 'en' => 'Full-Stack Web (Teacher)'],
        't_data_analysis_python' => ['ar' => 'تحليل البيانات ببايثون', 'en' => 'Data Analysis with Python'],
        't_cybersecurity_basics' => ['ar' => 'مبادئ الأمن السيبراني', 'en' => 'Cybersecurity Basics'],
    ],

    'tracks_student' => [
        's_laravel_fundamentals' => ['ar' => 'أساسيات لارافيل (طلاب)', 'en' => 'Laravel Fundamentals (Students)'],
        's_data_analysis_python' => ['ar' => 'تحليل البيانات ببايثون (طلاب)', 'en' => 'Data Analysis with Python (Students)'],
        's_cybersecurity_basics' => ['ar' => 'مبادئ الأمن السيبراني (طلاب)', 'en' => 'Cybersecurity Basics (Students)'],
        's_full_stack_web'       => ['ar' => 'تطوير الويب المتكامل', 'en' => 'Full-Stack Web'],
    ],

    // === Templates by role → trackKey → gender ===
    // - trackKey must exist in tracks_teacher for role=teacher, tracks_student for role=student
    // - colors per field; fonts per language (ar/en). Put matching TTFs under public/fonts (see styles.blade.php).
    'templates' => [
        'teacher' => [
            't_laravel_fundamentals' => [
                'male' => [
                    'bg'  => 'images/templates/teacher/t_laravel_fundamentals-male.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Lateef', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#0f172a',
                            'ar_name'   => '#334155',
                            'ar_track'  => '#0891b2',
                            'ar_from'   => '#0891b2',
                            'en_name'   => '#0f172a',
                            'en_track'  => '#0891b2',
                            'en_from'   => '#0f172a',
                        ],
                    ],
                    'photo'    => ['top'=>35,'left'=>30,'width'=>30,'height'=>30,'radius'=>6,'border'=>0.6,'border_color'=>'#1f2937'],
                ],
                'female' => [
                    'bg'  => 'images/templates/teacher/t_laravel_fundamentals-female.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#111827',
                            'ar_name'   => '#1f2937',
                            'ar_track'  => '#8b5cf6',
                            'ar_from'   => '#8b5cf6',
                            'en_name'   => '#111827',
                            'en_track'  => '#8b5cf6',
                            'en_from'   => '#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],
            't_full_stack_web' => [
                'male' => [
                    'bg'    => 'images/templates/teacher/t_full_stack_web-male.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#0ea5e9','ar_from'=>'#0ea5e9',
                            'en_name'   => '#111827','en_track'=>'#0ea5e9','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
                'female' => [
                    'bg'    => 'images/templates/teacher/t_full_stack_web-female.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#ef4444','ar_from'=>'#ef4444',
                            'en_name'   => '#111827','en_track'=>'#ef4444','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],

            't_data_analysis_python' => [
                'male' => [
                    'bg'    => 'images/templates/teacher/t_data_analysis_python-male.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#0ea5e9','ar_from'=>'#0ea5e9',
                            'en_name'   => '#111827','en_track'=>'#0ea5e9','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
                'female' => [
                    'bg'    => 'images/templates/teacher/t_data_analysis_python-female.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#ef4444','ar_from'=>'#ef4444',
                            'en_name'   => '#111827','en_track'=>'#ef4444','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],

            't_cybersecurity_basics' => [
                'male' => [
                    'bg'    => 'images/templates/teacher/t_cybersecurity_basics-male.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#0ea5e9','ar_from'=>'#0ea5e9',
                            'en_name'   => '#111827','en_track'=>'#0ea5e9','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
                'female' => [
                    'bg'    => 'images/templates/teacher/t_cybersecurity_basics-female.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 78,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#ef4444','ar_from'=>'#ef4444',
                            'en_name'   => '#111827','en_track'=>'#ef4444','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],
        ],

        'student' => [
            's_laravel_fundamentals' => [
                'male' => [
                    'bg'  => 'images/templates/student/s_laravel_fundamentals-male.jpg',
                    'pos' => [
                        'cert_date' => ['top'=>25,'right'=>210,'width'=>30,'font'=>5],
                        'ar_name'   => ['top'=>82,'right'=>190,'width'=>88,'font'=>7],
                        'ar_track'  => ['top'=>100,'right'=>190,'width'=>88,'font'=>6],
                        'ar_from'   => ['top'=>118,'right'=>235,'width'=>44,'font'=>6],
                        'en_name'   => ['top'=>82,'left'=>28,'width'=>118,'font'=>6],
                        'en_track'  => ['top'=>100,'left'=>28,'width'=>118,'font'=>5.5],
                        'en_from'   => ['top'=>118,'left'=>28,'width'=>60,'font'=>5.5],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Amiri', 'en' => 'Poppins'],
                        'colors' => [
                            'cert_date' => '#0f172a',
                            'ar_name'   => '#0f766e',
                            'ar_track'  => '#0ea5e9',
                            'ar_from'   => '#0ea5e9',
                            'en_name'   => '#0f172a',
                            'en_track'  => '#0ea5e9',
                            'en_from'   => '#0f172a',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
                'female' => [
                    'bg'  => 'images/templates/student/s_laravel_fundamentals-female.jpg',
                    'pos' => [
                        'cert_date' => ['top'=>25,'right'=>210,'width'=>30,'font'=>5],
                        'ar_name'   => ['top'=>83,'right'=>186,'width'=>90,'font'=>7],
                        'ar_track'  => ['top'=>101,'right'=>186,'width'=>90,'font'=>6],
                        'ar_from'   => ['top'=>118,'right'=>233,'width'=>44,'font'=>6],
                        'en_name'   => ['top'=>83,'left'=>30,'width'=>118,'font'=>6],
                        'en_track'  => ['top'=>101,'left'=>30,'width'=>118,'font'=>5.5],
                        'en_from'   => ['top'=>118,'left'=>30,'width'=>60,'font'=>5.5],
                    ],
                    'style' => [
                        'font' => ['ar' => 'Cairo', 'en' => 'Poppins'],
                        'colors' => [
                            'cert_date' => '#111827',
                            'ar_name'   => '#b45309',
                            'ar_track'  => '#d946ef',
                            'ar_from'   => '#d946ef',
                            'en_name'   => '#111827',
                            'en_track'  => '#d946ef',
                            'en_from'   => '#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],

            's_data_analysis_python' => [
                'male' => [
                    'bg'    => 'images/templates/student/s_data_analysis_python-male.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 30,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font'   => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#22c55e','ar_from'=>'#22c55e',
                            'en_name'   => '#111827','en_track'=>'#22c55e','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
                'female' => [
                    'bg'    => 'images/templates/student/s_data_analysis_python-female.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 30,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font'   => ['ar' => 'Cairo', 'en' => 'Poppins'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#f43f5e','ar_from'=>'#f43f5e',
                            'en_name'   => '#111827','en_track'=>'#f43f5e','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],

            's_cybersecurity_basics' => [
                'male' => [
                    'bg'    => 'images/templates/student/s_cybersecurity_basics-male.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 30,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font'   => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#22c55e','ar_from'=>'#22c55e',
                            'en_name'   => '#111827','en_track'=>'#22c55e','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
                'female' => [
                    'bg'    => 'images/templates/student/s_cybersecurity_basics-female.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 30,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font'   => ['ar' => 'Cairo', 'en' => 'Poppins'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#f43f5e','ar_from'=>'#f43f5e',
                            'en_name'   => '#111827','en_track'=>'#f43f5e','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],

            's_full_stack_web' => [
                'male' => [
                    'bg'    => 'images/templates/student/s_full_stack_web-male.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 30,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font'   => ['ar' => 'Amiri', 'en' => 'DejaVu Sans'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#22c55e','ar_from'=>'#22c55e',
                            'en_name'   => '#111827','en_track'=>'#22c55e','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
                'female' => [
                    'bg'    => 'images/templates/student/s_full_stack_web-female.jpg',
                    'pos' => [
                        'cert_date' => ['top' => 23,  'right' => 56, 'width' => 30,  'font' => 5],
                        'ar_name'   => ['top' => 78,  'right' => 12, 'width' => 90,  'font' => 7],
                        'ar_track'  => ['top' => 98,  'right' => 12, 'width' => 90,  'font' => 6],
                        'ar_from'   => ['top' => 118, 'right' => 45, 'width' => 45,  'font' => 6],
                        'en_name'   => ['top' => 78,  'left'  => 13,  'width' => 120, 'font' => 6],
                        'en_track'  => ['top' => 98,  'left'  => 13,  'width' => 120, 'font' => 5.5],
                        'en_from'   => ['top' => 114, 'left'  => 50,  'width' => 60,  'font' => 5.2],
                    ],
                    'style' => [
                        'font'   => ['ar' => 'Cairo', 'en' => 'Poppins'],
                        'colors' => [
                            'cert_date' => '#1f2937','ar_name'=>'#111827','ar_track'=>'#f43f5e','ar_from'=>'#f43f5e',
                            'en_name'   => '#111827','en_track'=>'#f43f5e','en_from'=>'#111827',
                        ],
                    ],
                    'photo' => [
                        'top' => 35,     // mm from top
                        'left'=> 30,     // mm from left (use left, not right)
                        'width'=> 30,    // mm
                        'height'=>30,    // mm
                        'radius'=> 6,    // mm rounded corners (optional)
                        'border'=> 0.6,  // mm border width (optional)
                        'border_color' => '#1f2937' // optional
                    ],
                ],
            ],
        ],
    ],

    'organization' => 'اسم المؤسسة هنا',
    'instructor'   => 'اسم المعلم هنا',
];
