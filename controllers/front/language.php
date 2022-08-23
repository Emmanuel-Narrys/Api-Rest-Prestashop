<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Language;
use PrestaShop\PrestaShop\Adapter\Entity\Module;

class Api_RestLanguageModuleFrontController extends RestController{


    protected function processGetRequest()
    {
        $ps_languageselector = Module::getInstanceByName('ps_languageselector');
        $this->datas = $ps_languageselector->getWidgetVariables(null, []);
        $this->renderAjax();
        parent::processGetRequest();
    }
}