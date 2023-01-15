<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;

class Api_RestYoutubeModuleFrontController extends RestController
{

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function processGetRequest()
    {
        $this->renderAjax();

        parent::processGetRequest();
    }
}
