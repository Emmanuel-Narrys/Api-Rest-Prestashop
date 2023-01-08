<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;

class Api_RestYoutubeModuleFrontController extends RestController
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

        /* $schema = Tools::getValue('schema');
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
                [
                    "name" => "page",
                    "required" => false,
                    "type" => "number",
                    'default' => 1,
                ]
            ]
        ];

        $inputs = $this->checkErrorsRequiredOrType();

        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        } */
        $response = Helpers::refreshTokenGoogleApi("1\/\/0dytbKekZC1LKCgYIARAAGA0SNwF-L9IrJE_8_0UlJx4PKr1yQDHI6eWkOMEcvGJ1C0NSy4MdFwt-9Nq9Zh66G5nAUrbLkWU6R80");
        $this->datas["response"] = $response;

        $this->renderAjax();

        parent::processGetRequest();
    }
}
