<?php

return [
    'nodes' => [
        'congregations' => ['users'],
        'prayer-requests' => ['prayerRequest', 'prayerRequests', 'prayer_requests', 'prayer-requests'],
        'announcements' => ['announcements', 'announcement', 'pengumuman'],
        'family-altars' => ['mk'],
        'pastor-messages' => ['pastorMessages', 'pastor_messages', 'pastor-messages', 'pastorMessage'],
    ],
    'import' => [
        'chunk_size' => 100,
        'missing_writer' => 'Penulis tidak tercantum',
    ],
    'storage' => [
        'base_url' => env('FIREBASE_STORAGE_BASE_URL', 'https://firebasestorage.googleapis.com'),
        'bucket' => env('FIREBASE_STORAGE_BUCKET', 'jki-hananeel-cinta.appspot.com'),
        'profile_object' => env('FIREBASE_PROFILE_OBJECT', 'profile-pictures'),
    ],
    'auth' => [
        'project_id' => env('FIREBASE_PROJECT_ID', 'jki-hananeel-cinta'),
        'public_keys_url' => env('FIREBASE_PUBLIC_KEYS_URL', 'https://www.googleapis.com/robot/v1/metadata/x509/securetoken@system.gserviceaccount.com'),
        'public_keys_cache_ttl' => (int) env('FIREBASE_PUBLIC_KEYS_CACHE_TTL', 3600),
        'http_timeout' => (int) env('FIREBASE_AUTH_HTTP_TIMEOUT', 5),
        'leeway' => (int) env('FIREBASE_AUTH_LEEWAY', 30),
    ],
    'images' => [
        'disk' => 'public',
        'directory' => 'announcements/imported',
        'max_bytes' => 5 * 1024 * 1024,
        'timeout' => 10,
        'retries' => 3,
        'allowed_mime_types' => ['image/jpeg', 'image/png', 'image/webp'],
    ],
];
