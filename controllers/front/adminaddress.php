<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\AddressStore;
use Viaziza\Smalldeals\Classes\City;

class Api_RestAdminaddressModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Address_Store',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => false,
                'default' => 0
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
            $address = AddressStore::getAddressStore($id_address, $id_lang, false);
            if ($address) {
                if ((int) $address->id_customer == $customer->id) {
                    $this->datas['address'] = $address;
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This address store has not shop is no longer available.', [], 'Shop.Notifications.Error')));
                }
            } else {
                $this->renderAjaxErrors($this->trans($this->trans('This address store is no longer available.', [], 'Shop.Notifications.Error')));
            }
        }

        $address = AddressStore::getFullAddressStores($id_lang, $customer->id, null, false);

        $this->datas['address'] = $address;

        $this->renderAjax();

        parent::processGetRequest();
    }

    protected function processPostRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $this->params = [
            'table' => 'Address_Store',
            'fields' => [
                [
                    'name' => 'id_country',
                    'type' => 'number',
                    'required' => true,
                    'datas' => Country::getCountries($id_lang, true)
                ],
                [
                    'name' => 'id_state',
                    'type' => 'number',
                    'required' => true,
                    'datas' => State::getStates($id_lang, true)
                ],
                [
                    'name' => 'id_city',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0,
                    'datas' => City::getFullCities($id_lang)
                ],
                [
                    'name' => 'alias',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'city',
                    'type' => 'text',
                    'required' => false,
                    'default' => false
                ],
                [
                    'name' => 'phone',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'phone_mobile',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'address',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'postcode',
                    'type' => 'text',
                    'required' => false,
                    'default' => false
                ],
                [
                    'name' => 'latitude',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0.00
                ],
                [
                    'name' => 'longitude',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0.00
                ],
                [
                    'name' => 'active',
                    'type' => 'number',
                    'required' => false,
                    'default' => 1
                ],
            ]
        ];

        if (Tools::getValue('schema', false)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();

        //Check if country exist
        $country = new Country((int) $inputs['id_country'], $id_lang);
        if (!Validate::isLoadedObject($country)) {
            $this->renderAjaxErrors($this->trans("Country do not exist."));
        }

        //Check if state exist
        $state = new State((int) $inputs['id_state'], $id_lang);
        if (!Validate::isLoadedObject($state)) {
            $this->renderAjaxErrors($this->trans("State do not exist."));
        }

        //Check if city exist
        if ((int) $inputs['id_city']) {
            $city = new State((int) $inputs['id_city'], $id_lang);
            if (!Validate::isLoadedObject($city)) {
                $this->renderAjaxErrors($this->trans("City do not exist."));
            }
        }

        $address = new AddressStore();
        $address->id_country = $inputs['id_country'];
        $address->id_sd_state = $inputs['id_state'];
        $address->id_sd_city = $inputs['id_city'];
        $address->city = $inputs['city'];
        $address->address = $inputs['address'];
        $address->postcode = $inputs['postcode'];
        $address->alias = $inputs['alias'];
        $address->phone = $inputs['phone'];
        $address->phone_mobile = $inputs['phone_mobile'];
        $address->latitude = $inputs['latitude'];
        $address->longitude = $inputs['longitude'];
        $address->active = $inputs['active'];
        $address->id_customer = $customer->id;

        if (!$address->save()) {
            $this->renderAjaxErrors($this->trans("The address store has not been saved."));
        }

        $this->datas['message'] = $this->trans("The address store has been saved.");
        $this->datas['address'] = AddressStore::getFullAddressStores($id_lang, $customer->id, null, false);

        $this->renderAjax();
        parent::processPostRequest();
    }
}
