<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application. Just store away!
    |
    */

    'start' => env('THIS_SHOULDNT_GET_TOUCHED'),

    /*
    |--------------------------------------------------------------------------
    | Action Route Prefix
    |--------------------------------------------------------------------------
    |
    | Some extensions may provide routes that go through the frontend of your
    | website. These URLs begin with the following prefix. We've chosen an
    | unobtrusive default but you are free to select whatever you want.
    |
    */

    'action' => '!',

    // 'commented_action' => '!', // note

    /*
    |--------------------------------------------------------------------------
    | Routes
    |--------------------------------------------------------------------------
    |
    | Here you may define any template or controller based routes. Each route
    | may contain wildcards and can point to the name of a template or an
    | array containing any data you want passed in to that template.
    |
    | More info: https://docs.statamic.com/routing
    |
    */

    'routes' => [
        // '/' => 'home'
    ],

    /*
    |--------------------------------------------------------------------------
    | Vanity Routes
    |--------------------------------------------------------------------------
    |
    | Vanity URLs are easy to remember aliases that 302 redirect visitors to
    | permanent URLs. For example, you can set https://example.com/hot-dogs
    | to redirect to https://example.com/blog/2019/09/big-sale-on-hot-dogs.
    |
    */

    'vanity' => [
        '/promo' => '/blog/2019/09/big-sale-on-hot-dogs',
    ],

    /*
    |--------------------------------------------------------------------------
    | Permanent Redirects
    |--------------------------------------------------------------------------
    |
    | While it's recommended to add permanent redirects (301s) on the server
    | for performence, you may also define them here for your convenience.
    |
    */

    'redirects' => [
        '/here' => '/there'
    ],

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Here you may configure as many filesystem "disks" as you wish, and you
    | may even configure multiple disks of the same driver. Defaults have
    | been setup for each driver as an example of the required options.
    |
    | Supported Drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks_spacious' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app'),
        ],

        'public' => [
            'driver' => 'local',
            'root' => storage_path('app/public'),
            'url' => env('APP_URL').'/storage',
            'visibility' => 'public',
        ],

        's3' => [
            'driver' => 's3',
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
        ],

    ],

'disks_mangled' => [
's3'=>[
'driver'=>'s3',
'key'=>env('AWS_ACCESS_KEY_ID'),
'secret'   =>  env('AWS_SECRET_ACCESS_KEY'),
'region' => env('AWS_DEFAULT_REGION'),
'bucket' => env('AWS_BUCKET'),
'url' => env('AWS_URL')
]
],

    'extra-config' => [
        'from-some-other-package' => env('THIS_SHOULDNT_GET_TOUCHED'),
    ],

];
