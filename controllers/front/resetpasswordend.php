<?php

use NarrysTech\Api_Rest\controllers\RestController;

class Api_RestResetpasswordendModuleFrontController extends RestController
{

    public $params = [
        'table' => 'Reset Password End',
        'fields' => [
            [
                'name' => 'token',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'reset_token',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'id_customer',
                'type' => 'number',
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

        $inputs = $this->checkErrorsRequiredOrType();
        $token = $inputs['token'];
        $id_customer = (int) $inputs['id_customer'];
        $reset_token = $inputs['reset_token'];
        $passwd = $inputs['password'];
        $confirmation = $inputs['confirmation'];

        $email = Db::getInstance()->getValue(
            'SELECT `email` FROM ' . _DB_PREFIX_ . 'customer c WHERE c.`secure_key` = \'' . pSQL($token) . '\' AND c.id_customer = ' . $id_customer
        );
        if (!$email) {
            $this->renderAjaxErrors($this->trans('We cannot regenerate your password with the data you\'ve submitted', [], 'Shop.Notifications.Error'));
        }

        $customer = new Customer();
        $customer->getByEmail($email);

        if (!Validate::isLoadedObject($customer)) {
            $this->renderAjaxErrors($this->trans('Customer account not found', [], 'Shop.Notifications.Error'));
        } elseif (!$customer->active) {
            $this->renderAjaxErrors($this->trans('You cannot regenerate the password for this account.', [], 'Shop.Notifications.Error'));
        } elseif ($customer->getValidResetPasswordToken() !== $reset_token) {
            $this->renderAjaxErrors($this->trans('The password change request expired. You should ask for a new one.', [], 'Shop.Notifications.Error'));
        }

        if ($passwd !== $confirmation) {
            $this->renderAjaxErrors($this->trans('The password and its confirmation do not match.', [], 'Shop.Notifications.Error'));
        }

        // Both password fields posted. Check if all is right and store new password properly.
        if ((strtotime($customer->last_passwd_gen . '+' . (int) Configuration::get('PS_PASSWD_TIME_FRONT') . ' minutes') - time()) > 0) {
            $this->renderAjaxErrors($this->trans('Password generation error.', [], 'Shop.Notifications.Error'));
        }

        $customer->passwd = $this->get('hashing')->hash($passwd, _COOKIE_KEY_);
        $customer->last_passwd_gen = date('Y-m-d H:i:s', time());

        if ($customer->update()) {
            Hook::exec('actionPasswordRenew', ['customer' => $customer, 'password' => $passwd]);
            $customer->removeResetPasswordToken();
            $customer->update();

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
        $this->datas['message'] = $this->trans('Your password has been successfully reset and a confirmation has been sent to your email address: %s', [$customer->email], 'Shop.Notifications.Success');
        $this->renderAjax();
        parent::processPostRequest();
    }
}
