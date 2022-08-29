<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;

class Api_RestCustomerModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Customer',
        'fields' => [
            [
                'name' => 'id_customer',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'id_gender',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'id_country',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'id_state',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'id_city',
                'type' => 'number',
                'required' => false,
                'default' => false
            ],
            [
                'name' => 'firstname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'lastname',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'birthday',
                'type' => 'date',
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
                'name' => 'website',
                'type' => 'text',
                'required' => false,
                'default' => false
            ],
            [
                'name' => 'address1',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'address2',
                'type' => 'text',
                'required' => false,
                'default' => false
            ],
            [
                'name' => 'postcode',
                'type' => 'text',
                'required' => false,
                'default' => false
            ],
        ]
    ];

    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $id_address = Address::getFirstCustomerAddressId($customer->id);
        if (!$id_address) {
            $address = null;
        }

        $address = new Address($id_address, $id_lang);
        if (!Validate::isLoadedObject($address)) {
            $address = null;
            $country = null;
            $state = null;
        } else {
            $country = new Country($address->id_country, $id_lang);
            $state = new State($address->id_state, $id_lang);
        }

        $gender = new Gender($customer->id_gender, $id_lang);
        if (!Validate::isLoadedObject($gender)) {
            $gender = null;
        }

        $this->datas['customer'] = array_merge([
            'id_customer' => $customer->id,
            'username' => $customer->username,
            'email' => $customer->email,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'birthday' => $customer->birthday,
            'sponsorship_code' => $customer->sponsorship_code,
            'date_add' => $customer->date_add,
            'website' => $customer->website,
            'newsletter' => $customer->newsletter,
        ], [
            'addresse' => $address ? (object) [
                'id_address' => $address->id,
                'address1' => $address->address1,
                'address2' => $address->address2,
                'postcode' => $address->postcode,
                'city' => $address->city,
                'phone' => $address->phone,
                'phone_whatsapp' => $address->phone_mobile,
                'country' => (object) [
                    'id_country' => $country->id,
                    'name' => $country->name,
                    'iso_code' => $country->iso_code,
                    'contains_states' => $country->contains_states
                ],
                'state' => (object) [
                    'id_state' => $state->id,
                    'name' => $state->name,
                    'iso_code' => $state->iso_code
                ]
            ] : $address,
            'gender' => $gender ? (object) [
                'id_gender' => $gender->id,
                'name' => $gender->name
            ] : $gender,
        ]);

        $this->renderAjax();

        parent::processGetRequest();
    }

    protected function processPutRequest()
    {
        $inputs = $this->checkErrorsRequiredOrType();

        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        //Update Customer
        $customer->firstname = $inputs['firstname'];
        $customer->lastname = $inputs['lastname'];
        $customer->email = $inputs['email'];
        $customer->website = isset($inputs['website']) ? $inputs['website'] : "";
        isset($inputs['birthday']) ? $customer->birthday = $inputs['birthday'] : null;
        $customer->id_gender = (int) $inputs['id_gender'];
        if (!$customer->save()) {
            $this->renderAjaxErrors($this->trans("Customer do not save."));
        }

        //Create or Update Address Customer
        $id_address = (int) Address::getFirstCustomerAddressId($customer->id);
        $address = new Address($id_address, $id_lang);
        if (!$id_address && !Validate::isLoadedObject($address)) {
            $address->id_customer = $customer->id;
            $address->id_country = (int) $inputs['id_country'];
            $address->id_state = (int) $inputs['id_state'];
            $address->id_city = (int) $inputs['id_city'];
            $address->alias = $customer->firstname . ' ' . $customer->lastname;
            $address->firstname = $customer->firstname;
            $address->lastname = $customer->lastname;
            $address->address1 = $inputs['address1'];
            $address->address2 = isset($inputs['address2']) ? $inputs['address2'] : '';
            $address->city = isset($inputs['city']) ? $inputs['city'] : '';
            $address->postcode = isset($inputs['postcode']) ? $inputs['postcode'] : '';
            $address->phone = $inputs['phone'];
            $address->phone_mobile = $inputs['phone_mobile'];
            if (!$address->save()) {
                $this->renderAjaxErrors($this->trans("Address customer do not save."));
            }
        } else {
            $address->id_country = (int) $inputs['id_country'];
            $address->id_state = (int) $inputs['id_state'];
            $address->id_city = (int) $inputs['id_city'];
            $address->address1 = $inputs['address1'];
            $address->address2 = isset($inputs['address2']) ? $inputs['address2'] : '';
            $address->city = isset($inputs['city']) ? $inputs['city'] : '';
            $address->postcode = isset($inputs['postcode']) ? $inputs['postcode'] : '';
            $address->phone = $inputs['phone'];
            $address->phone_mobile = $inputs['phone_mobile'];
            if (!$address->save()) {
                $this->renderAjaxErrors($this->trans("Address customer do not save."));
            }
        }

        $country = new Country($address->id_country, $id_lang);
        $state = new State($address->id_state, $id_lang);
        $gender = new Gender($customer->id_gender, $id_lang);

        $this->datas['customer'] = array_merge([
            'id_customer' => $customer->id,
            'username' => $customer->username,
            'email' => $customer->email,
            'firstname' => $customer->firstname,
            'lastname' => $customer->lastname,
            'birthday' => $customer->birthday,
            'sponsorship_code' => $customer->sponsorship_code,
            'date_add' => $customer->date_add,
            'website' => $customer->website,
            'newsletter' => $customer->newsletter,
        ], [
            'addresse' => $address ? (object) [
                'id_address' => $address->id,
                'address1' => $address->address1,
                'address2' => $address->address2,
                'postcode' => $address->postcode,
                'city' => $address->city,
                'phone' => $address->phone,
                'phone_whatsapp' => $address->phone_mobile,
                'country' => (object) [
                    'id_country' => $country->id,
                    'name' => $country->name,
                    'iso_code' => $country->iso_code,
                    'contains_states' => $country->contains_states
                ],
                'state' => (object) [
                    'id_state' => $state->id,
                    'name' => $state->name,
                    'iso_code' => $state->iso_code
                ]
            ] : $address,
            'gender' => $gender ? (object) [
                'id_gender' => $gender->id,
                'name' => $gender->name
            ] : $gender,
        ]);

        $this->renderAjax();
        parent::processPutRequest();
    }
}
