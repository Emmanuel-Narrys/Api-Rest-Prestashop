<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;

class Api_RestRegisterModuleFrontController extends RestController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [
        "table" => 'register',
        "fields" => [
            [
                "name" => "username",
                "required" => true,
                "type" => "text"
            ],
            [
                "name" => "email",
                "required" => true,
                "type" => "email"
            ],
            [
                "name" => "password",
                "required" => true,
                "type" => "password"
            ],
            [
                "name" => "ig_gender",
                "required" => false,
                "type" => "number",
                "default" => 1
            ],
            [
                "name" => "newsletter",
                "required" => false,
                "type" => "number",
                "default" => 0
            ],
            [
                "name" => "optin",
                "required" => false,
                "type" => "number",
                "default" => 0
            ],
            [
                "name" => "firstname",
                "required" => false,
                "type" => "text",
                "default" => ""
            ],
            [
                "name" => "lastname",
                "required" => false,
                "type" => "text",
                "default" => ""
            ],
            [
                "name" => "customer_privacy",
                "required" => false,
                "type" => "number",
                "default" => 1
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

            $inputs["sponsorship_code"] = Helpers::generateSponsorshipCode();

            $register_form = $this
                ->makeCustomerForm()
                ->setGuestAllowed(false)
                ->fillWith($inputs);

            $hookResult = array_reduce(
                Hook::exec('actionSubmitAccountBefore', [], null, true),
                function ($carry, $item) {
                    return $carry && $item;
                },
                true
            );

            if ($hookResult && $register_form->submit()) {
                $customer = $this->context->customer;
                if ($customer->isLogged()) {

                    $this->datas["registered"] = $customer->isLogged();
                    $this->datas["message"] = $this->getTranslator()->trans('User registered successfully');
                    $this->datas["is_logged"] = $customer->isLogged();
                    $this->datas["session_token"] = $this->context->cookie->getAll()["session_token"];
                    $this->datas["customer"] = $customer;
                    $this->datas["id_cart"] = $this->context->cart->id;
                }
            } else {
                $this->renderAjaxErrors(
                    $this->getTranslator()->trans("This username or email exists.")
                );
            }

            $this->renderAjax();
        } catch (\Exception $e) {
            $this->renderAjaxErrors($e->getMessage());
        }
    }
}
