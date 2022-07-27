<?php

namespace NarrysTech\Api_Rest\classes;

class APIRoutes {

    public static final function getRoutes():array
    {
        return [
            'module-api_rest-register' => [
                'rule' => 'rest/register',
                'keywords' => [],
                'controller' => 'register',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ]
        ];
    }
}