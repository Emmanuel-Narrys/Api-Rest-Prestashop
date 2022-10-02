<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\Boutique;

class Api_RestAdminstore_changestatusModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Store',
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
        $id_lang = $this->context->language->id;
        $customer = $this->context->customer;

        $inputs = $this->checkErrorsRequiredOrType();
        $id_store = $inputs['id'];

        if ((int) $id_store) {
            $id_store = (int) $id_store;
            $store = new Boutique($id_store, $id_lang);
            if (Validate::isLoadedObject($store)) {
                if ($store->id_customer != $customer->id) {
                    $this->renderAjaxErrors($this->trans("This shop is not for this customer.", [], "Shop.Notifications.Error"));
                }
            } else {
                $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
            }
        } else {
            $store_explode = explode('-', $id_store);
            $id_store = (int) $store_explode[0];
            $store = new Boutique($id_store, $id_lang);
            if (Validate::isLoadedObject($store)) {
                if ($store->id_customer != $customer->id) {
                    $this->renderAjaxErrors($this->trans("This shop is not for this customer.", [], "Shop.Notifications.Error"));
                }
            } else {
                $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
            }
        }

        $store->active = !$store->active;
        if (!$store->save()) {
            $this->renderAjaxErrors($this->trans("The status of store has not been changed."));
        }

        $this->datas['message'] = $this->trans("The status of store has been change.");
        $this->datas['store'] = Boutique::getStore($store->id, $id_lang, false);

        $this->renderAjax();
        parent::processGetRequest();
    }
}
