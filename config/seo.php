<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Preferred production host
    |--------------------------------------------------------------------------
    |
    | When enabled, GET/HEAD requests on a host other than the host in
    | APP_URL are permanently redirected to the APP_URL host (www → apex or
    | apex → www, whichever the shop uses). Off by default so local and
    | staging environments are never touched.
    |
    */

    'force_canonical_host' => env('SEO_FORCE_CANONICAL_HOST', false),

    /*
    |--------------------------------------------------------------------------
    | Business details for structured data
    |--------------------------------------------------------------------------
    |
    | Address, phone and social links are read from the Company Details table
    | (admin → Company). Locality, region and country are not stored there, so
    | they live here. Everything is optional — empty values are omitted from
    | the JSON-LD rather than emitted as blanks.
    |
    */

    'business' => [
        'locality' => env('SEO_BUSINESS_LOCALITY', 'Northampton'),
        'region' => env('SEO_BUSINESS_REGION', 'Northamptonshire'),
        'country' => env('SEO_BUSINESS_COUNTRY', 'GB'),
        'price_range' => env('SEO_BUSINESS_PRICE_RANGE', '££'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Service area
    |--------------------------------------------------------------------------
    |
    | Named places the shop genuinely serves, used for areaServed. Local
    | delivery is checked by radius at checkout, so these are descriptive only
    | and never generate location pages.
    |
    */

    'area_served' => array_filter(array_map('trim', explode(',', (string) env(
        'SEO_AREA_SERVED',
        'Northampton,Far Cotton,Delapre'
    )))),

];
