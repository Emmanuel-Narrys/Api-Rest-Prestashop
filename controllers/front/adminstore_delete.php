<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\Boutique;

class Api_RestAdminstore_deleteModuleFrontController extends AuthRestController
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

        Boutique::attachCategories($store->id, []);
        Boutique::attachAddresses($store->id, []);
        Boutique::attachCurrencies($store->id, []);
        Boutique::attachOpeningDays($store->id, []);
        Boutique::attachSocialNetWorks($store->id, []);

        $logo_filename = Boutique::getPathLogo(false) . DIRECTORY_SEPARATOR . $store->id . '.jpg';
        if (file_exists($logo_filename)) {
            unlink($logo_filename);
        }

        $image_filename = Boutique::getPathImage(false) . DIRECTORY_SEPARATOR . $store->id . '.jpg';
        if (file_exists($image_filename)) {
            unlink($image_filename);
        }

        if (!$store->delete()) {
            $this->renderAjaxErrors($this->trans("The store has not been deleted."));
        }

        $this->datas['message'] = $this->trans("The store has been deleted.");
        $this->datas['stores'] = Boutique::getFullStores($id_lang, $customer->id);

        $this->renderAjax();
        parent::processGetRequest();
    }
}
