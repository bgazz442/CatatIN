<?php

return [
    'uploads' => [
        'disk' => 'learning_private',
        'max_files' => 10,
        'max_file_bytes' => 50 * 1024 * 1024,
        'max_total_bytes' => 100 * 1024 * 1024,
        'extensions' => [
            'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'csv',
            'jpg', 'jpeg', 'png', 'webp',
        ],
        'mime_types' => [
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-powerpoint',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation',
            'text/plain',
            'text/csv',
            'application/csv',
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
        'preview_mime_types' => [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/webp',
        ],
    ],
];