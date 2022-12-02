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
            'module-api_rest-search-stores' => [
                'rule' => 'rest/search/stores',
                'keywords' => [],
                'controller' => 'search_store',
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
            'module-api_rest-categorystore' => [
                'rule' => 'rest/categoriesstore{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'category_store',
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
                'rule' => 'rest/front-office/update/store{/:id}',
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
                'rule' => 'rest/front-office/delete/store{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminstore_delete',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-stores-change-status' => [
                'rule' => 'rest/front-office/change-status/store{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminstore_changestatus',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-products' => [
                'rule' => 'rest/front-office/products{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminproduct',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-products-update' => [
                'rule' => 'rest/front-office/update/product{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminproduct_update',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-products-delete' => [
                'rule' => 'rest/front-office/delete/product{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminproduct_delete',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-products-change-status' => [
                'rule' => 'rest/front-office/change-status/product{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[_a-zA-Z0-9_-]+', 'param' => 'id']
                ],
                'controller' => 'adminproduct_changestatus',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-products-image-delete' => [
                'rule' => 'rest/front-office/delete/product-image{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[0-9]+', 'param' => 'id']
                ],
                'controller' => 'adminproductimages_delete',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-address-store' => [
                'rule' => 'rest/front-office/address-store{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[0-9]+', 'param' => 'id']
                ],
                'controller' => 'adminaddress',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-address-store-update' => [
                'rule' => 'rest/front-office/update/address-store{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[0-9]+', 'param' => 'id']
                ],
                'controller' => 'adminaddress_update',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-address-store-delete' => [
                'rule' => 'rest/front-office/delete/address-store{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[0-9]+', 'param' => 'id']
                ],
                'controller' => 'adminaddress_delete',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-address-store-change-status' => [
                'rule' => 'rest/front-office/change-status/address-store{/:id}',
                'keywords' => [
                    'id' => ['regexp' => '[0-9]+', 'param' => 'id']
                ],
                'controller' => 'adminaddress_changestatus',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-product-search' => [
                'rule' => 'rest/product-search',
                'keywords' => [],
                'controller' => 'productsearch',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-reset-password' => [
                'rule' => 'rest/reset-password',
                'keywords' => [],
                'controller' => 'resetpassword',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-reset-password-end' => [
                'rule' => 'rest/reset-password-end',
                'keywords' => [],
                'controller' => 'resetpasswordend',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-change-password' => [
                'rule' => 'rest/front-office/change-password',
                'keywords' => [],
                'controller' => 'changepassword',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
            'module-api_rest-email-subscription' => [
                'rule' => 'rest/email-subscription',
                'keywords' => [],
                'controller' => 'emailsubscription',
                'params' => [
                    'fc' => 'module',
                    'module' => 'api_rest'
                ]
            ],
        ];
    }
}
