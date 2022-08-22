<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
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
            /* [
                "name" => "email",
                "required" => true,
                "type" => "text"
            ],
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
        $id_store = Tools::getValue('id');
        $id_lang = Tools::getValue('id_lang');

        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        if ($id_store) {
            if ((int) $id_store) {
                $id_store = (int) $id_store;
                $store = Boutique::getStore($id_store, $id_lang ?? null);
                if ($store) {
                    $this->datas['store'] = $store;
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans("Shop with id {$id_store} not exists."));
                }
            } else {
                $store = Boutique::getStoreWithSlug($id_store, $id_lang ?? null);
                if ($store) {
                    $this->datas['store'] = $store;
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans("Shop with slug {$id_store} not exists."));
                }
            }
        }

        $this->datas['stores'] = Boutique::getFullStores($id_lang?? null);
        $this->renderAjax();

        parent::processGetRequest();
    }
}
