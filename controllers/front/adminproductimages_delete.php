<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;

class Api_RestAdminproductimages_deleteModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Product-Images',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => true,
            ]
        ]
    ];

    /* protected function processGetRequest()
    {

        $inputs = $this->checkErrorsRequiredOrType();
        $id_image = (int) $inputs['id'];

        $image = new Image($id_image);
        if (Validate::isLoadedObject($image)) {
            if (!$image->delete()) {
                $this->renderAjaxErrors($this->trans($this->trans('Image do not deleted.', [], 'Shop.Notifications.Error')));
            }
        } else {
            $this->renderAjaxErrors($this->trans($this->trans('This image is no longer available.', [], 'Shop.Notifications.Error')));
        }

        $this->datas['message'] = $this->trans("Image has been deleted.");

        $this->renderAjax();

        parent::processGetRequest();
    } */
}
