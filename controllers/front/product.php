<?php

use NarrysTech\Api_Rest\classes\RESTProductLazyArray;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use Viaziza\Smalldeals\Classes\ProductStore;

class Api_RestProductModuleFrontController extends RestController
{

    public $params = [
        'table' => 'product',
        'fields' => [
            [
                'name' => 'id',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'id_product_attribute',
                'required' => false,
                'type' => 'number',
                'default' => 0
            ],
            [
                'name' => 'id_sd_store',
                'required' => false,
                'type' => 'number',
                'default' => 0
            ],
        ]
    ];

    /**
     * Product
     *
     * @var Product
     */
    protected $product;

    protected $quantity_discounts = [];

    protected function processGetRequest()
    {
        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_product = $inputs['id'];
        $id_sd_store = $inputs['id_sd_store'];

        if ((int) $id_product) {
            $id_product = (int) $id_product;
        } else {
            $product_explode = explode('-', $id_product);
            $id_product = (int) $product_explode[0];
            if ((int) $product_explode[1]) {
                $id_product_attribute = (int) $product_explode[1];
            } else {
                $id_product_attribute = 0;
            }
            $_GET['id_product_attribute'] = $id_product_attribute;
        }

        $this->product = new Product($id_product, true, $this->context->language->id);
        if (!Validate::isLoadedObject($this->product) || empty(ProductStore::getProductStores($this->product->id))) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        if (!(bool)$this->product->active) {
            $this->renderAjaxErrors($this->trans('This product is not enable.', [], 'Shop.Notifications.Warning'));
        }

        if ($id_sd_store) {
            $productStore = ProductStore::getProductStore($this->product->id, $id_sd_store, $this->context->language->id);
            if (Validate::isLoadedObject($productStore)) {
                $product = $this->getFullProduct($this->product->id, $this->context->language->id, $productStore);
            } else {
                $product = $this->getFullProduct($this->product->id, $this->context->language->id);
            }
        } else {
            $product = $this->getFullProduct($this->product->id, $this->context->language->id);
        }

        if (empty($product)) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        $this->datas['product'] = $product;
        $this->renderAjax();


        /* $product = $this->getProduct();
        $product['groups'] = $this->assignAttributesGroups($product);

        $this->datas['product'] = $product;
        $this->renderAjax(); */
        parent::processGetRequest();
    }
}
