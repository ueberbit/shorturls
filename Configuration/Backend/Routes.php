<?php

use UEBERBIT\Shorturls\Controller\ShortUrlController;

return [
    'shorturls_create' => [
        'path' => '/shorturls/create',
        'target' => ShortUrlController::class . '::createAction',
    ],
];
