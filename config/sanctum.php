<?php

return [

    'stateful' => [],

    'guard' => ['web'],

    'expiration' => env('SANCTUM_TOKEN_EXPIRATION'),

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', ''),

];
