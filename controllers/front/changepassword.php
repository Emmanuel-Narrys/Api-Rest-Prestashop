<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use PrestaShop\PrestaShop\Adapter\CoreException;
use PrestaShop\PrestaShop\Adapter\ServiceLocator;

class Api_RestChangepasswordModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Change Password',
        'fields' => [
            [
                'name' => 'old_password',
                'type' => 'password',
                'required' => true,
            ],
            [
                'name' => 'password',
                'type' => 'password',
                'required' => true,
            ],
            [
                'name' => 'confirmation',
                'type' => 'password',
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

        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $inputs = $this->checkErrorsRequiredOrType();
        $old_password = $inputs['old_password'];
        $passwd = $inputs['password'];
        $confirmation = $inputs['confirmation'];

        if ($passwd !== $confirmation) {
            $this->renderAjaxErrors($this->trans('The password and its confirmation do not match.', [], 'Shop.Notifications.Error'));
        }

        try {
            /** @var \PrestaShop\PrestaShop\Core\Crypto\Hashing $crypto */
            $crypto = ServiceLocator::get('\\PrestaShop\\PrestaShop\\Core\\Crypto\\Hashing');
        } catch (CoreException $e) {
            $this->renderAjaxErrors($e->getMessage(), $this->codeServeur);
        }

        if(!$crypto->checkHash($old_password, $customer->passwd)){
            $this->renderAjaxErrors($this->trans('The old password is not correct.', [], 'Shop.Notifications.Error'));
        }

        $customer->passwd = $this->get('hashing')->hash($passwd, _COOKIE_KEY_);
        if ($customer->update()) {
            Hook::exec('actionPasswordRenew', ['customer' => $customer, 'password' => $passwd]);

            $mail_params = [
                '{email}' => $customer->email,
                '{lastname}' => $customer->lastname,
                '{firstname}' => $customer->firstname,
            ];

            if (
                !Mail::Send(
                    $this->context->language->id,
                    'password',
                    $this->trans(
                        'Your new password',
                        [],
                        'Emails.Subject'
                    ),
                    $mail_params,
                    $customer->email,
                    $customer->firstname . ' ' . $customer->lastname
                )
            ) {
                $this->renderAjaxErrors($this->trans('An error occurred while sending the email.', [], 'Shop.Notifications.Error'));
            }
        } else {
            $this->renderAjaxErrors($this->trans('An error occurred with your account, which prevents us from updating the new password. Please report this issue using the contact form.', [], 'Shop.Notifications.Error'));
        }

        $this->context->updateCustomer($customer);
        $this->datas['message'] = $this->trans('Your password has been successfully change and a confirmation has been sent to your email address: %s', [$customer->email], 'Shop.Notifications.Success');
        $this->renderAjax();
        parent::processPostRequest();
    }
}
