<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use Viaziza\Smalldeals\Classes\Boutique;
use Viaziza\Smalldeals\Classes\CategoryStore;
use Viaziza\Smalldeals\Classes\City;
use Viaziza\Smalldeals\Classes\TypeStore;

class Api_RestSearch_storeModuleFrontController extends RestController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [
        "table" => 'search stores',
        "fields" => [
            [
                "name" => "query",
                "required" => false,
                "type" => "text",
                "default" => 0
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
            "table" => 'search stores',
            "fields" => [
                [
                    "name" => "query",
                    "required" => false,
                    "type" => "text",
                    "default" => 0
                ],
                [
                    "name" => "id_type_store",
                    "required" => false,
                    "type" => "number",
                    'default' => 0,
                    "data" => TypeStore::getTypeStores($id_lang)
                ],
                [
                    "name" => "id_category_store",
                    "required" => false,
                    "type" => "number",
                    'default' => 0,
                    "data" => CategoryStore::getCategoryStore($id_lang)
                ],
                [
                    "name" => "id_city",
                    "required" => false,
                    "type" => "number",
                    'default' => 0,
                    "data" => City::getFullCities(true)
                ],
                [
                    "name" => "id_currency",
                    "required" => false,
                    "type" => "number",
                    'default' => 0,
                    "data" => Currency::getCurrencies(true)
                ],
                [
                    "name" => "page",
                    "required" => false,
                    "type" => "number",
                    'default' => 1,
                ]
            ]
        ];

        $inputs = $this->checkErrorsRequiredOrType();

        $query = $inputs['query'];
        $query = ($query && !is_null($query) && $query != "") ? $query : null;

        $id_type_store = (int) $inputs['id_type_store'];
        $id_type_store = ($id_type_store && !is_null($id_type_store)) ? $id_type_store : null;

        $id_city = (int) $inputs['id_city'];
        $id_city = ($id_city && !is_null($id_city)) ? $id_city : null;

        $id_category_store = (int) $inputs['id_category_store'];
        $id_category_store = ($id_category_store && !is_null($id_category_store)) ? $id_category_store : null;

        $id_currency = (int) $inputs['id_currency'];
        $id_currency = ($id_currency && !is_null($id_currency)) ? $id_currency : null;

        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        list("pagination" => $this->datas['pagination'], "stores" => $this->datas['stores']) = Boutique::getFullStores($id_lang ?? null, null, false, $id_type_store, true,$query, $id_category_store, $id_currency, $id_city);
        $this->renderAjax();

        parent::processGetRequest();
    }
}
