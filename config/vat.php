<?php

return [
    'enabled' => env('VAT_ENABLED', true),
    'rate' => (float) env('VAT_RATE', 0.12),
    'label' => 'VAT (12%)',
];
