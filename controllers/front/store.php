<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use Viaziza\Smalldeals\Classes\Boutique;

class Api_RestStoreModuleFrontController extends RestController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [
        "table" => 'stores',
        "fields" => [
            [
                "name" => "id",
                "required" => false,
                "type" => "text",
                'default' => false
            ],
            /* 
            [
                "name" => "password",
                "required" => true,
                "type" => "password"
            ],
            [
                "name" => "remember",
                "required" => false,
                "type" => "number",
                "default" => 0
            ], */]
    ];

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function processGetRequest()
    {

        $schema = Tools::getValue('schema');
        $inputs = $this->checkErrorsRequiredOrType();
        $id_store = $inputs['id'];

        $id_lang = $this->context->language->id;

        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        if ($id_store) {
            if ((int) $id_store) {
                $id_store = (int) $id_store;
                $store = Boutique::getStore($id_store, $id_lang ?? null);
                if (Validate::isLoadedObject($store)) {
                    $this->datas['store'] = $store;
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
                }
            } else {
                $store_explode = explode('-', $id_store);
                $id_store = (int) $store_explode[0];
                $store = Boutique::getStore($id_store, $id_lang ?? null);
                if (Validate::isLoadedObject($store)) {
                    $this->datas['store'] = $store;
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
                }
            }
        }

        $this->datas['stores'] = Boutique::getFullStores($id_lang?? null);
        $this->renderAjax();

        parent::processGetRequest();
    }
}
