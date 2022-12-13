<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\AuthRestController;

if (file_exists(_PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php")) {
    require_once _PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php";
}

class Api_RestWishlist_deleteModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Wistlist',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => true,
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

        $wishlist = new WishList($id_wishlist, $id_lang);
        if (!Validate::isLoadedObject($wishlist) || $customer->id != $wishlist->id_customer) {
            $this->renderAjaxErrors($this->trans("Wishlist do not exists."));
        }

        if(!$wishlist->delete()){
            $this->renderAjaxErrors($this->trans("Wishlist has not deleted."));
        }

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

        $this->datas["message"] = $this->trans("Wishlist deleted with success.");
        $this->datas["wishlists"] = $wishlists;
        $this->renderAjax();

        parent::processGetRequest();
    }

    private function generateWishListToken()
    {
        return strtoupper(substr(sha1(uniqid((string) rand(), true) . _COOKIE_KEY_ . $this->context->customer->id), 0, 16));
    }
}
