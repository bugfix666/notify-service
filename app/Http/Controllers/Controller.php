<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;

#[
    OA\Info(version: '1.0.0', description: 'Service', title: 'Service'),
    OA\Server(url: 'http://localhost', description: 'local server'),
]
#[OA\OpenApi(
    security: [['bearerAuth' => []]]
)]
#[OA\Components(
    securitySchemes: [
        new OA\SecurityScheme(
            securityScheme: 'bearerAuth',
            type: 'http',
            in: 'header',
            bearerFormat: 'JWT',
            scheme: 'bearer'
        ),
    ]
)]
abstract class Controller
{
    //
}
