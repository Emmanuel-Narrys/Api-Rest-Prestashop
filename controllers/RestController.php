<?php

namespace ApiRest\Controller;

use ApiRest\Classes\Helpers;
use PrestaShop\PrestaShop\Adapter\Entity\ModuleFrontController;

class RestController extends ModuleFrontController{

    public function init() {

        header("Content-type: application/json");
        parent::init();
        var_dump($_SERVER);die;
    }

    protected function processGetRequest (){
        return $this->methodNotAllowed();
    }

    protected function processPostRequest (){
        return $this->methodNotAllowed();
    }

    protected function processPutRequest (){
        return $this->methodNotAllowed();
    }

    protected function processDeleteRequest (){
        return $this->methodNotAllowed();
    }

    protected function methodNotAllowed ():string
    {
        return Helpers::response_json(true, 405, [
            "message" => "Method not allowed"
        ]);
    }
}