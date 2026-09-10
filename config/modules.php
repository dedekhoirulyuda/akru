<?php

/**
 * AKRU Module Registry
 *
 * Defines all available modules and their configuration.
 * Module order follows dependency chain (Blueprint §5.5).
 */
return [
    'modules' => [
        'Core'         => ['enabled' => true, 'priority' => 1],
        'Identity'     => ['enabled' => true, 'priority' => 2],
        'MasterData'   => ['enabled' => true, 'priority' => 3],
        'Accounting'   => ['enabled' => true, 'priority' => 4],
        'Sales'        => ['enabled' => true, 'priority' => 5],
        'Purchase'     => ['enabled' => true, 'priority' => 6],
        'Inventory'    => ['enabled' => true, 'priority' => 7],
        'Finance'      => ['enabled' => true, 'priority' => 8],
        'Tax'          => ['enabled' => true, 'priority' => 9],
        'Document'     => ['enabled' => true, 'priority' => 10],
        'Workflow'     => ['enabled' => true, 'priority' => 11],
        'Audit'        => ['enabled' => true, 'priority' => 12],
        'Notification' => ['enabled' => true, 'priority' => 13],
        'Reporting'    => ['enabled' => true, 'priority' => 14],
        'Subscription' => ['enabled' => true, 'priority' => 15],
        'Partner'      => ['enabled' => true, 'priority' => 16],
    ],
];
