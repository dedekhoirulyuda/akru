<?php

/**
 * AKRU Application Configuration
 *
 * Central configuration for AKRU — Accounting, Finance & Tax Control.
 * Platform: AKRU OS
 */
return [

    /*
    |--------------------------------------------------------------------------
    | Application Identity
    |--------------------------------------------------------------------------
    */

    'name' => 'AKRU',
    'descriptor' => 'Accounting, Finance & Tax Control',
    'tagline' => 'Data nyata. Kendali penuh.',
    'platform' => 'AKRU OS',
    'version' => '1.0.0-dev',

    /*
    |--------------------------------------------------------------------------
    | Editions
    |--------------------------------------------------------------------------
    | Blueprint §1.8: AKRU Standard (no AI dependency) and AKRU AI (add-on).
    */

    'editions' => [
        'standard' => 'AKRU Standard',
        'ai'       => 'AKRU AI',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Settings
    |--------------------------------------------------------------------------
    */

    'defaults' => [
        'currency'               => 'IDR',
        'timezone'               => 'Asia/Jakarta',
        'fiscal_year_start_month' => 1,
        'date_format'            => 'd/m/Y',
        'decimal_separator'      => ',',
        'thousand_separator'     => '.',
        'money_scale'            => 2,
        'quantity_scale'         => 4,
        'rate_scale'             => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Entity Types
    |--------------------------------------------------------------------------
    */

    'entity_types' => [
        'pt'       => 'PT (Perseroan Terbatas)',
        'cv'       => 'CV (Commanditaire Vennootschap)',
        'firma'    => 'Firma',
        'koperasi' => 'Koperasi',
        'ud'       => 'Usaha Dagang',
        'op'       => 'Orang Pribadi',
    ],

    /*
    |--------------------------------------------------------------------------
    | Export
    |--------------------------------------------------------------------------
    | Blueprint invariant: Excel and PDF only. NO CSV.
    */

    'export_formats' => ['xlsx', 'pdf'],

    /*
    |--------------------------------------------------------------------------
    | Offline / PWA
    |--------------------------------------------------------------------------
    | Blueprint §2.12: Certain actions are online-only.
    */

    'online_only_actions' => [
        'posting',
        'closing',
        'approval_material',
        'tax_finalization',
        'export_official',
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Configuration
    |--------------------------------------------------------------------------
    | Blueprint §1.8, §14.10: AI only suggests, never posts autonomously.
    */

    'ai' => [
        'enabled'                 => env('AKRU_AI_ENABLED', false),
        'auto_post_allowed'       => false, // NEVER change this to true
        'confidence_threshold'    => 0.85,
        'require_human_approval'  => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Audit
    |--------------------------------------------------------------------------
    */

    'audit' => [
        'log_reads'          => false,
        'log_writes'         => true,
        'log_login_attempts' => true,
        'retention_days'     => 365 * 7, // 7 years
    ],
];
