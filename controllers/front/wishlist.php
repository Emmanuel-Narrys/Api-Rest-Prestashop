<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\AuthRestController;

if (file_exists(_PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php")) {
    require_once _PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php";
}

class Api_RestWishlistModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Wistlist',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => false,
                'default' => 0
            ],
            [
                'name' => 'all_products',
                'type' => 'number',
                'required' => false,
                'default' => 0
            ],
        ]
    ];

    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        if (!Module::isEnabled("blockwishlist")) {
            $this->renderAjaxErrors($this->trans("Module 'blockwishlist' is not install."), $this->codeServeur);
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_wishlist = (int) $inputs["id"];
        $all_products = (bool) $inputs["all_products"];

        if ($id_wishlist) {
            $wishlist = new WishList($id_wishlist, $id_lang);
            if (!Validate::isLoadedObject($wishlist) || $customer->id != $wishlist->id_customer) {
                $this->renderAjaxErrors($this->trans("Wishlist do not exists."));
            }
            $this->datas["wishlist"] = $wishlist;
            $products_wishlist = WishList::getProductsByWishlist($wishlist->id);
            $products_wishlist ?? [];
            $products = [];
            foreach ($products_wishlist as $product) {
                $_GET['id_product_attribute'] = (int) $product["id_product_attribute"];
                $products[] = $this->getFullProduct((int) $product["id_product"], $id_lang);
            }
            $this->datas["products"] = $products;
            $this->renderAjax();
        }

        if ($all_products) {
            $_products = WishList::getAllProductByCustomer($customer->id, $this->context->shop->id);
            $_products ?? [];
            $products = [];
            foreach ($_products as $product) {
                $_GET['id_product_attribute'] = (int) $product["id_product_attribute"];
                $products[] = $this->getFullProduct((int) $product["id_product"], $id_lang);
            }
            $this->datas["products"] = $products;
        } else {
            $wishlists = WishList::getAllWishListsByIdCustomer($customer->id);
            if (empty($wishlists)) {
                $wishlist = new WishList();
                $wishlist->id_shop = $this->context->shop->id;
                $wishlist->id_shop_group = $this->context->shop->id_shop_group;
                $wishlist->id_customer = $customer->id;
                $wishlist->name = Configuration::get('blockwishlist_WishlistDefaultTitle', $id_lang);
                $wishlist->token = $this->generateWishListToken();
                $wishlist->default = 1;
                $wishlist->add();

                $wishlists = WishList::getAllWishListsByIdCustomer($customer->id);
            }

            $this->datas["wishlists"] = $wishlists;
        }
        $this->renderAjax();

        parent::processGetRequest();
    }

    protected function processPostRequest()
    {

        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        if (!Module::isEnabled("blockwishlist")) {
            $this->renderAjaxErrors($this->trans("Module 'blockwishlist' is not install."), $this->codeServeur);
        }

        $this->params = [
            'table' => 'Wislist',
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                ],
            ]
        ];

        if (Tools::getValue('schema', false)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $name = $inputs["name"];
        $wishlist = new WishList();
        $wishlist->name = $name;
        $wishlist->id_shop_group = $this->context->shop->id_shop_group;
        $wishlist->id_customer = $customer->id;
        $wishlist->id_shop = $this->context->shop->id;
        $wishlist->token = $this->generateWishListToken();
        if (!$wishlist->save()) {
            $this->renderAjaxErrors($this->trans("Wishlist can't be created."));
        }

        $this->datas["message"] = $this->trans("Wishlist create with success.");
        $this->datas["wishlist"] = $wishlist;
        $this->renderAjax();
        parent::processPostRequest();
    }

    private function generateWishListToken()
    {
        return strtoupper(substr(sha1(uniqid((string) rand(), true) . _COOKIE_KEY_ . $this->context->customer->id), 0, 16));
    }
}
