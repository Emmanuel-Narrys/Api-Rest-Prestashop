<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\AddressStore;

class Api_RestAdminaddress_deleteModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Address_Store',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => true,
            ]
        ]
    ];

    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $inputs = $this->checkErrorsRequiredOrType();
        $id_address = (int) $inputs['id'];

        if ($id_address) {
            $address = new AddressStore($id_address, $id_lang);
            if (Validate::isLoadedObject($address)) {
                if ((int) $address->id_customer != $customer->id) {
                    $this->renderAjaxErrors($this->trans($this->trans('This address store has not shop is no longer available.', [], 'Shop.Notifications.Error')));
                }
            } else {
                $this->renderAjaxErrors($this->trans($this->trans('This address store is no longer available.', [], 'Shop.Notifications.Error')));
            }
        }

        if (!$address->delete()) {
            $this->renderAjaxErrors($this->trans("The address store has not been deleted."));
        }

        $this->datas['message'] = $this->trans("The address store has been deleted.");
        $this->datas['address'] = AddressStore::getFullAddressStores($id_lang, $customer->id, null, false);
        $this->renderAjax();

        parent::processGetRequest();
    }
}
