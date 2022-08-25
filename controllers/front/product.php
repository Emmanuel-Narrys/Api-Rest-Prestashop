<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Product;

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
                'name' => 'refresh',
                'required' => false,
                'type' => 'number',
                'default' => 1
            ],
            [
                'name' => 'id_product_attribute',
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
        if (!Validate::isLoadedObject($this->product)) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        if (!(bool)$this->product->active) {
            $this->renderAjaxErrors($this->trans('This product is not enable.', [], 'Shop.Notifications.Warning'));
        }

        //this is when you change an attribute, every time a request is sent to get the price and its discount
        if ((bool) $inputs['refresh']) {
            $product = $this->getTemplateVarProduct();
            $product['groups'] = $this->assignAttributesGroups($product);

            $this->datas['product'] = $product;
            $this->renderAjax();
        }

        $product = $this->getProduct();
        $product['groups'] = $this->assignAttributesGroups($product);

        $this->datas['product'] = $product;
        $this->renderAjax();
        parent::processGetRequest();
    }
}
