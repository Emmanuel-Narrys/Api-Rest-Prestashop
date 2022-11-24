<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;

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
                $this->datas["customer"] = [
                    "id" => $customer->id,
                    "id_gender" => $customer->id_gender,
                    "id_lang" => $customer->id_lang,
                    "username" => $customer->username,
                    "email" => $customer->email,
                    "sponsorship_code" => $customer->sponsorship_code
                ];
                $this->datas["id_cart"] = $this->context->cart->id;
            }

            $this->renderAjax();
        } catch (\Exception $e) {
            $this->renderAjaxErrors($e->getMessage());
        }
    }
}
