<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use Viaziza\Smalldeals\Classes\Boutique;
use Viaziza\Smalldeals\Classes\TypeStore;

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
                'default' => 0
            ],
            [
                "name" => "id_type_store",
                "required" => false,
                "type" => "number",
                'default' => 0
            ],
        ]
    ];

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function processGetRequest()
    {

        $id_lang = $this->context->language->id;

        $schema = Tools::getValue('schema');
        $this->params = [
            "table" => 'stores',
            "fields" => [
                [
                    "name" => "id",
                    "required" => false,
                    "type" => "text",
                    'default' => 0
                ],
                [
                    "name" => "id_type_store",
                    "required" => false,
                    "type" => "number",
                    'default' => 0,
                    "data" => TypeStore::getTypeStores($id_lang)
                ],
            ]
        ];

        $inputs = $this->checkErrorsRequiredOrType();
        $id_store = $inputs['id'];
        $id_type_store = (int) $inputs['id_type_store'];
        $id_type_store = ($id_type_store && !is_null($id_type_store)) ? $id_type_store : null;

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

        $this->datas['stores'] = Boutique::getFullStores($id_lang ?? null, null, true, $id_type_store);
        $this->renderAjax();

        parent::processGetRequest();
    }
}
