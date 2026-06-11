<?php

return [
    'api_token' => env('CLOUDFLARE_API_TOKEN'),
    'api_base' => env('CLOUDFLARE_API_BASE', 'https://api.cloudflare.com/client/v4'),
    'tracker_ip' => env('CLOUDFLARE_TRACKER_IP', '167.233.41.29'),
];
