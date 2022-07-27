<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;

class Api_RestRegisterModuleFrontController extends RestController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [
        "table" => 'customer',
        "fields" => [
            [
                "name" => "username",
                "required" => true,
                "type" => "text"
            ],
            [
                "name" => "email",
                "required" => true,
                "type" => "email"
            ],
            [
                "name" => "password",
                "required" => true,
                "type" => "password"
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

        $schema = Tools::getValue('schema');

        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        parent::processGetRequest();
    }

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function processPostRequest()
    {

        $inputs = $this->checkErrorsRequiredOrType();

        $this->datas = $inputs;
        $this->renderAjax();
    }
}
