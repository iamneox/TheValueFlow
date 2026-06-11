<?php

return [
    'alert_email' => env('TVF_ALERT_EMAIL'),
    'google_safe_browsing_key' => env('GOOGLE_SAFE_BROWSING_KEY'),
    'tracker_redis_host' => env('TRACKER_REDIS_HOST', '127.0.0.1'),
    'tracker_internal_url' => env('TRACKER_INTERNAL_URL', 'http://10.5.0.2:8081'),
];
