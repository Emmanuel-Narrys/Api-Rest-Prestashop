<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\AuthRestController;

if (file_exists(_PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php")) {
    require_once _PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php";
}

class Api_RestWishlist_updateModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Wistlist',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'name',
                'type' => 'text',
                'required' => true,
            ],
        ]
    ];

    protected function processPostRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        if (!Module::isEnabled("blockwishlist")) {
            $this->renderAjaxErrors($this->trans("Module 'blockwishlist' is not install."), $this->codeServeur);
        }

        if (Tools::getValue('schema', false)) {
            $this->params = [
                'table' => 'Wistlist',
                'fields' => [
                    [
                        'name' => 'name',
                        'type' => 'text',
                        'required' => true,
                    ],
                ]
            ];
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_wishlist = (int) $inputs["id"];
        $name = $inputs["name"];

        $wishlist = new WishList($id_wishlist, $id_lang);
        if (!Validate::isLoadedObject($wishlist) || $customer->id != $wishlist->id_customer) {
            $this->renderAjaxErrors($this->trans("Wishlist do not exists."));
        }

        $wishlist->name = $name;
        if (!$wishlist->update()) {
            $this->renderAjaxErrors($this->trans("Wishlist has not updated."));
        }

        $this->datas["message"] = $this->trans("Wishlist updated with success.");
        $this->datas["wishlist"] = $wishlist;
        $this->renderAjax();

        parent::processGetRequest();
    }

    private function generateWishListToken()
    {
        return strtoupper(substr(sha1(uniqid((string) rand(), true) . _COOKIE_KEY_ . $this->context->customer->id), 0, 16));
    }
}
