<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;

class Api_RestResetpasswordModuleFrontController extends RestController
{

    public $params = [
        'table' => 'Reset Password',
        'fields' => [
            [
                'name' => 'email',
                'type' => 'text',
                'required' => true,
                "message" => "username or email"
            ],
            [
                'name' => 'url',
                'type' => 'url',
                'required' => true,
            ],
        ]
    ];

    protected function processPostRequest()
    {
        if (Tools::getValue('schema', false)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

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

        if (!($email = trim($inputs['email'])) || !Validate::isEmail($inputs['email'])) {
            $this->renderAjaxErrors($this->trans('Invalid email address.', [], 'Shop.Notifications.Error'));
        }
        $customer = new Customer();
        $customer->getByEmail($email);
        if (null === $customer->email) {
            $customer->email = $inputs['email'];
        }

        if (!Validate::isLoadedObject($customer)) {
            $this->renderAjaxErrors($this->trans(
                'If this email address has been registered in our shop, you will receive a link to reset your password at %email%.',
                ['%email%' => $customer->email],
                'Shop.Notifications.Success'
            ));
        } elseif (!$customer->active) {
            $this->renderAjaxErrors($this->trans('You cannot regenerate the password for this account.', [], 'Shop.Notifications.Error'));
        } elseif ((strtotime($customer->last_passwd_gen . '+' . ($minTime = (int) Configuration::get('PS_PASSWD_TIME_FRONT')) . ' minutes') - time()) > 0) {
            $this->renderAjaxErrors($this->trans('You can regenerate your password only every %d minute(s)', [(int) $minTime], 'Shop.Notifications.Error'));
        }

        if (!$customer->hasRecentResetPasswordToken()) {
            $customer->stampResetPasswordToken();
            $customer->update();
        }

        $mailParams = [
            '{email}' => $customer->email,
            '{lastname}' => $customer->lastname,
            '{firstname}' => $customer->firstname,
            '{url}' => Helpers::formatUrlWithParams($inputs['url'], [
                'token' => $customer->secure_key,
                'id_customer' => $customer->id,
                'reset_token' => $customer->reset_password_token
            ])
        ];

        if (
            !Mail::Send(
                $this->context->language->id,
                'password_query',
                $this->trans(
                    'Password query confirmation',
                    [],
                    'Emails.Subject'
                ),
                $mailParams,
                $customer->email,
                $customer->firstname . ' ' . $customer->lastname
            )
        ) {
            $this->renderAjaxErrors($this->trans('An error occurred while sending the email.', [], 'Shop.Notifications.Error'));
        }

        $this->datas['message'] = $this->trans('If this email address has been registered in our shop, you will receive a link to reset your password at %email%.', ['%email%' => $customer->email], 'Shop.Notifications.Success');
        $this->renderAjax();
        parent::processPostRequest();
    }
}
