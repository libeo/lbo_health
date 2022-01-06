<?php
declare(strict_types=1);

use Libeo\LboHealth\Command\CheckCacheControlUsage;

return [
    'lbo_health:check-cache-control-usage' => [
        'class' => CheckCacheControlUsage::class,
        'schedulable' => false
    ],
];
