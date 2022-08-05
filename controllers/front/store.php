<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;

class Api_RestStoreModuleFrontController extends AuthRestController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [
        "table" => 'store',
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

        try {
            $inputs = $this->checkErrorsRequiredOrType();
        } catch (\Exception $e) {
            $this->renderAjaxErrors($e->getMessage());
        }

        $this->renderAjax();
    }
}
