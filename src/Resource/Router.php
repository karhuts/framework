<?php
declare(strict_types=1);

use Apps\Controller\Users;
use Karthus\Http\Request;
use Karthus\Http\Response;
use Karthus\Http\Router\RouterMethod;

return [
    "/"         => [
        'method'    => RouterMethod::GET,
        'handle'    => function(Request $request,Response $response){}
    ],
    //分组
    "/users"    => [
        "groups"    => [
            "/users/:uid"   => [
                "method"    => RouterMethod::GET,
                "class"     => Users::class,
                "action"    => "index",
            ],
        ],
    ],
];
