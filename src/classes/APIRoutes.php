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
            'module-api_rest-categorys' => [
                'rule' => 'rest/categorys{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'category',
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
            'module-api_rest-customer-comments' => [
                'rule' => 'rest/front-office/customer/comment',
                'keywords' => [],
                'controller' => 'postcomment',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-customer' => [
                'rule' => 'rest/front-office/customer',
                'keywords' => [],
                'controller' => 'customer',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-customer-sponsorships' => [
                'rule' => 'rest/front-office/customer/sponsorships',
                'keywords' => [],
                'controller' => 'customersponsorships',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-city' => [
                'rule' => 'rest/front-office/cities{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'city',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-stores' => [
                'rule' => 'rest/front-office/stores{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminstore',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-stores-update' => [
                'rule' => 'rest/front-office/update/stores{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminstore_update',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-stores-delete' => [
                'rule' => 'rest/front-office/delete/stores{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminstore_delete',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
        ];
    }
}
