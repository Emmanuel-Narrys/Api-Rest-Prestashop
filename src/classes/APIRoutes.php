<?php

namespace NarrysTech\Api_Rest\classes;

class APIRoutes
{

    public static final function getRoutes(): array
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
            ],
            'module-api_rest-login' => [
                'rule' => 'rest/login',
                'keywords' => [],
                'controller' => 'login',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-store' => [
                'rule' => 'rest/stores{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'store',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-product' => [
                'rule' => 'rest/product{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'product',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-category-products' => [
                'rule' => 'rest/category-products{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'categoryproducts',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-share-product' => [
                'rule' => 'rest/share-product{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'shareproduct',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-bootstrap' => [
                'rule' => 'rest/bootstrap',
                'keywords' => [],
                'controller' => 'bootstrap',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-languages' => [
                'rule' => 'rest/languages',
                'keywords' => [],
                'controller' => 'language',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-comments' => [
                'rule' => 'rest/comments',
                'keywords' => [],
                'controller' => 'comment',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
        ];
    }
}
