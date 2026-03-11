<?php

return [
    'api_key'            => env('TMDB_API_KEY'),
    'read_access_token'  => env('TMDB_READ_ACCESS_TOKEN'),
    'base_url'           => env('TMDB_BASE_URL', 'https://api.themoviedb.org/3'),
    'image_base_url'     => env('TMDB_IMAGE_BASE_URL', 'https://image.tmdb.org/t/p'),
    'poster_size'        => 'w500',
    'backdrop_size'      => 'w1280',
    'profile_size'       => 'w185',
    'timeout'            => 15,
];
