<?php

namespace NarrysTech\Api_Rest\controllers;

use PDO;
use PrestaShop\PrestaShop\Adapter\Entity\Address;
use PrestaShop\PrestaShop\Adapter\Entity\Cart;
use PrestaShop\PrestaShop\Adapter\Entity\CartRule;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\DbQuery;
use PrestaShop\PrestaShop\Adapter\Entity\Hook;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;

class AuthRestController extends RestController
{
    public function authenticate()
    {
        parent::authenticate();

        //Check if customer has Authenticate with the session token

        $session_token = Tools::getValue('session_token');
        if ($session_token == false || empty($session_token) || is_null($session_token)) {
            $this->renderAjaxErrors(
                $this->trans("Field session_token is required!")
            );
        }

        $customer = $this->getCustomerWithSessionToken($session_token);
        $this->updateContext($customer);
    }

    public function getCustomerWithSessionToken(string $session_token)
    {
        try {

            $q = new DbQuery();
            $q->select("a.*")
                ->from("customer_session", "a")
                ->where("a.token = '$session_token'");

            $customerSession = Db::getInstance()->executeS($q, false)->fetch(PDO::FETCH_OBJ);
            if (empty($customerSession) || is_null($customerSession)) {
                $this->renderAjaxErrors(
                    "Authentication failed. The session token is not correct.",
                    $this->codeAuthenticateCustomer
                );
            } else {
                $this->context->cookie->session_id = (int) $customerSession->id_customer_session;
                $this->context->cookie->session_token = $customerSession->token;
                return new Customer((int)$customerSession->id_customer);
            }
        } catch (\Exception $e) {
            $this->renderAjaxErrors($e->getMessage(), $this->codeServeur);
        }
    }

    public function updateContext(Customer $customer)
    {

        Hook::exec('actionAuthenticationBefore');

        $this->context->customer = $customer;
        $this->context->cookie->id_customer = (int) $customer->id;
        $this->context->cookie->customer_lastname = $customer->lastname;
        $this->context->cookie->customer_firstname = $customer->firstname;
        $this->context->cookie->passwd = $customer->passwd;
        $this->context->cookie->logged = 1;
        $customer->logged = 1;
        $this->context->cookie->email = $customer->email;
        $this->context->cookie->is_guest = $customer->isGuest();

        if (Configuration::get('PS_CART_FOLLOWING') && (empty($this->context->cookie->id_cart) || Cart::getNbProducts($this->context->cookie->id_cart) == 0) && $idCart = (int) Cart::lastNoneOrderedCart($this->context->customer->id)) {
            $this->context->cart = new Cart($idCart);
            $this->context->cart->secure_key = $customer->secure_key;
        } else {
            $idCarrier = (int) $this->context->cart->id_carrier;
            $this->context->cart->secure_key = $customer->secure_key;
            $this->context->cart->id_carrier = 0;
            $this->context->cart->setDeliveryOption(null);
            $this->context->cart->updateAddressId($this->context->cart->id_address_delivery, (int) Address::getFirstCustomerAddressId((int) ($customer->id)));
            $this->context->cart->id_address_delivery = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
            $this->context->cart->id_address_invoice = (int) Address::getFirstCustomerAddressId((int) ($customer->id));
        }
        $this->context->cart->id_customer = (int) $customer->id;

        if (isset($idCarrier) && $idCarrier) {
            $deliveryOption = [$this->context->cart->id_address_delivery => $idCarrier . ','];
            $this->context->cart->setDeliveryOption($deliveryOption);
        }

        $this->context->cart->save();
        $this->context->cookie->id_cart = (int) $this->context->cart->id;
        $this->context->cookie->write();
        $this->context->cart->autosetProductAddress();

        Hook::exec('actionAuthentication', ['customer' => $customer]);

        // Login information have changed, so we check if the cart rules still apply
        CartRule::autoRemoveFromCart($this->context);
        CartRule::autoAddToCart($this->context);
    }
}
