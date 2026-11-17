<?php

return [
    'sending_enabled' => (bool) env('NEWSLETTER_SENDING_ENABLED', false),
    'batch_size' => (int) env('NEWSLETTER_BATCH_SIZE', 25),
    'max_attachment_kb' => (int) env('MATERIAL_MAX_UPLOAD_KB', 10240),
];
