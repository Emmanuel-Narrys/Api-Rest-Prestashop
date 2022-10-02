<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\Boutique;
use Viaziza\Smalldeals\Classes\ProductStore;

class Api_RestAdminproduct_changestatusModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Product',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'id_sd_store',
                'type' => 'number',
                'required' => true,
            ],
        ]
    ];

    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $inputs = $this->checkErrorsRequiredOrType();
        $id_product = $inputs['id'];
        $id_sd_store = $inputs['id_sd_store'];

        if ($id_product) {
            if ((int) $id_product) {
                $id_product = (int) $id_product;
                $product = new Product($id_product, false, $id_lang);
                if (!Validate::isLoadedObject($product)) {
                    $this->renderAjaxErrors($this->trans($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error')));
                }
            } else {
                $product_explode = explode('-', $id_product);
                $id_product = (int) $product_explode[0];
                $product = new Product($id_product, false, $id_lang);
                if (!Validate::isLoadedObject($product)) {
                    $this->renderAjaxErrors($this->trans($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error')));
                }
            }
        }

        $product_Store = ProductStore::getProductStore($product->id, $id_sd_store, $id_lang, $customer->id, false);
        if(!$product_Store){
            $this->renderAjaxErrors($this->trans($this->trans('This product store is no longer available for this customer or store.', [], 'Shop.Notifications.Error')));
        }

        $product_Store->active = !$product_Store->active;
        if(!$product_Store->save()){
            $this->renderAjaxErrors($this->trans("The status of product store has not been changed."));
        }

        $this->datas['product'] = $this->getFullProduct($product->id, $id_lang, $product_Store);

        $this->renderAjax();

        parent::processGetRequest();
    }
}
