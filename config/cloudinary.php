<?php

return [
    /*
     * URL kết nối Cloudinary dạng: cloudinary://api_key:api_secret@cloud_name
     * Đọc từ biến môi trường CLOUDINARY_URL trong .env
     * Dùng config('cloudinary.url') thay vì env() trực tiếp để hoạt động
     * đúng khi cache cấu hình production (php artisan config:cache).
     */
    'url' => env('CLOUDINARY_URL'),
];
