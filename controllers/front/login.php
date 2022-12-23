<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use Viaziza\Smalldeals\Classes\City;

class Api_RestLoginModuleFrontController extends RestController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [
        "table" => 'login',
        "fields" => [
            [
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
            ],
        ]
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

            if (!Validate::isEmail($inputs["email"])) {

                if (!Helpers::validateUsername($inputs["email"])) {
                    $this->renderAjaxErrors(
                        $this->getTranslator()->trans("This username is not correct.")
                    );
                }

                $email = Helpers::getEmailByUsername($inputs["email"]);
                if ($email != false) {
                    $inputs['email'] = $email;
                } else {
                    $this->renderAjaxErrors(
                        $this->getTranslator()->trans("This username is not correct.")
                    );
                }
            }

            $login_form = $this->makeLoginForm()->fillWith(
                $inputs
            );

            if (!$login_form->submit()) {
                $this->renderAjaxErrors($this->translator->trans('Authentication failed.', [], 'Shop.Notifications.Error'));
            }

            $customer = $this->context->customer;
            if ($customer->isLogged()) {
                $this->datas["is_logged"] = $customer->isLogged();
                $this->datas["session_token"] = $this->context->cookie->getAll()["session_token"];

                $id_address = (int) Address::getFirstCustomerAddressId($customer->id);
                $address = new Address($id_address);
                if (!$id_address || !Validate::isLoadedObject($address)) {
                    $this->datas["customer"] = [
                        "id" => $customer->id,
                        "id_gender" => $customer->id_gender,
                        "id_lang" => $customer->id_lang,
                        "username" => $customer->username,
                        "email" => $customer->email,
                        "sponsorship_code" => $customer->sponsorship_code
                    ];
                } else {
                    $id_lang = $this->context->language->id;
                    $country = new Country($address->id_country, $id_lang);
                    $state = new State($address->id_state, $id_lang);
                    $gender = new Gender($customer->id_gender, $id_lang);
                    if ($address->id_sd_city != 0) {
                        $city = new City($address->id_sd_city, $id_lang);
                    } else {
                        $city = null;
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
                            ],
                            '_city' => $city ? (object) [
                                'id_city' => $city->id,
                                'name' => $city->name
                            ] : $city,
                        ] : $address,
                        'gender' => $gender ? (object) [
                            'id_gender' => $gender->id,
                            'name' => $gender->name
                        ] : $gender,
                    ]);
                }

                $this->datas["id_cart"] = $this->context->cart->id;
            }

            $this->renderAjax();
        } catch (\Exception $e) {
            $this->renderAjaxErrors($e->getMessage());
        }
    }
}
