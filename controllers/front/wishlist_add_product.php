<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;

if (file_exists(_PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php")) {
    require_once _PS_MODULE_DIR_ . "blockwishlist/classes/WishList.php";
}

if (file_exists(_PS_MODULE_DIR_ . "blockwishlist/classes/Statistics.php")) {
    require_once _PS_MODULE_DIR_ . "blockwishlist/classes/Statistics.php";
}

class Api_RestWishlist_add_productModuleFrontController extends AuthRestController
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
                'name' => 'id_product',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'id_product_attribute',
                'type' => 'number',
                'required' => false,
                'default' => 0
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
                        'name' => 'id_product',
                        'type' => 'number',
                        'required' => true,
                    ],
                    [
                        'name' => 'id_product_attribute',
                        'type' => 'number',
                        'required' => false,
                        'default' => 0
                    ],
                ]
            ];
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_wishlist = (int) $inputs["id"];
        $id_product = (int) $inputs["id_product"];
        $id_product_attribute = (int) $inputs["id_product_attribute"];

        $wishlist = new WishList($id_wishlist, $id_lang);
        if (!Validate::isLoadedObject($wishlist) || $customer->id != $wishlist->id_customer) {
            $this->renderAjaxErrors($this->trans("Wishlist do not exists."));
        }

        $product = new Product($id_product);
        if (!Validate::isLoadedObject($product) || !$product->active) {
            $this->renderAjaxErrors($this->trans("Tere was an error adding the product."));
        }
        $quantity = $product->minimal_quantity;

        if (!$this->assertProductAttributeExists($id_product, $id_product_attribute) && $id_product_attribute !== 0) {
            $this->renderAjaxErrors($this->trans('There was an error while adding the product attributes', [], 'Modules.Blockwishlist.Shop'));
        }
        
        $productIsAdded = $wishlist->addProduct(
            $wishlist->id,
            $customer->id,
            $id_product,
            $id_product_attribute,
            $quantity
        );

        $newStat = new Statistics();
        $newStat->id_product = $id_product;
        $newStat->id_product_attribute = $id_product_attribute;
        $newStat->id_shop = $this->context->shop->id;
        $newStat->save();

        if (!$productIsAdded) {
            $this->renderAjaxErrors($this->trans('There was an error adding the product', [], 'Modules.Blockwishlist.Shop'));
        }
        
        Hook::exec('actionWishlistAddProduct', [
            'idWishlist' => $id_wishlist,
            'customerId' => $customer->id,
            'idProduct' => $id_product,
            'idProductAttribute' => $id_product_attribute,
        ]);

        $this->datas["message"] = $this->trans("Product added to Wishlist with success.");
        $this->renderAjax();

        parent::processGetRequest();
    }

    private function generateWishListToken()
    {
        return strtoupper(substr(sha1(uniqid((string) rand(), true) . _COOKIE_KEY_ . $this->context->customer->id), 0, 16));
    }
    
    /**
     * Check if product attribute id is related to the product
     *
     * @param int $id_product
     * @param int $id_product_attribute
     *
     * @return bool
     */
    private function assertProductAttributeExists($id_product, $id_product_attribute)
    {
        return Db::getInstance()->getValue(
            'SELECT pas.`id_product_attribute` ' .
            'FROM `' . _DB_PREFIX_ . 'product_attribute` pa ' .
            'INNER JOIN `' . _DB_PREFIX_ . 'product_attribute_shop` pas ON (pas.id_product_attribute = pa.id_product_attribute) ' .
            'WHERE pas.id_shop =' . (int) $this->context->shop->id . ' AND pa.`id_product` = ' . (int) $id_product . ' ' .
            'AND pas.id_product_attribute = ' . (int) $id_product_attribute
        );
    }
}
