<?php

namespace NarrysTech\Api_Rest\controllers;

use NarrysTech\Api_Rest\classes\Helpers;
use PrestaShop\PrestaShop\Adapter\Entity\ModuleFrontController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use PrestaShop\PrestaShop\Adapter\Entity\WebserviceKey;

class RestController extends ModuleFrontController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [];
    /**
     * Datas to send in ajax
     *
     * @var array
     */
    public $datas = [];
    /**
     * success
     *
     * @var integer
     */
    public $codeSuccess = 200;
    /**
     * Errors method if not found for this route
     *
     * @var integer
     */
    public $codeMethod = 405;
    /**
     * Error internal serveur
     *
     * @var integer
     */
    public $codeServeur = 500;
    /**
     * Page or route if not exists
     *
     * @var integer
     */
    public $codeNotFound = 404;
    /**
     * Errors fields required or type if not correct
     *
     * @var integer
     */
    public $codeErrors = 400;
    /**
     * Error Authenticate
     *
     * @var integer
     */
    public $codeAuthenticate = 401;
    /**
     * Error Authenticate Customer
     *
     * @var integer
     */
    public $codeAuthenticateCustomer = 402;

    public function init()
    {

        header("Content-type: application/json");
        parent::init();

        //Authenticate application with Bearer token
        $this->authenticate();

        //Check method is submit
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processGetRequest();
                break;
            case 'POST':
                $this->processPostRequest();
                break;
            case 'PUT':
                $this->processPutRequest();
                break;
            case 'DELETE':
                $this->processDeleteRequest();
                break;

            default:
                # code...
                break;
        }
    }

    protected function processGetRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processPostRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processPutRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processDeleteRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function methodNotAllowed()
    {
        $this->ajaxRender(Helpers::response_json([
            "message" => "Method not allowed"
        ], $this->codeMethod, false));
        die;
    }


    /**
     * Undocumented function
     *
     * @param string $type
     * @param mixed $value
     * @return boolean
     */
    public function isValideType(string $type, string $value): bool
    {
        switch ($type) {
            case 'text':
                return Validate::isString($value);
                break;
            case 'number':
                return Validate::isInt((int) $value) || Validate::isFloat((float) $value);
                break;
            case 'tel':
                return Validate::isPhoneNumber($value);
                break;
            case 'email':
                return Validate::isEmail($value);
                break;
            case 'file':
                return Validate::isFileName($value);
                break;
            case 'password':
                return Validate::isString($value);
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function checkErrorsRequiredOrType(): array
    {
        $inputs = array();

        foreach ($this->params['fields'] as $key => $a) {
            //Get Name
            $name = $a['name'];
            //Get Required
            $required = (bool) $a['required'];
            //Get Type
            $type = $a['type'];
            //Get Value
            $value = Tools::getValue($name);

            //Field is required and null
            if (($required === true) && (($value == false || is_null($value)))) {
                $this->errors["required"][] = $a;
            }
            //Field type if not valide
            if ($this->isValideType($type, $value) == false) {
                $this->errors["type"][] = $a;
            }
            //If field is not required and if not submit
            if ($required === false && (($value == false || is_null($value)))) {
                $value = isset($a["default"]) ? $a["default"] : "null";
            }

            $inputs[$name] = $value;
        }

        //If has errors required
        if (isset($this->errors["required"]) && !empty($this->errors["required"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is required!");
            foreach ($this->errors["required"] as $field) {
                $errors["fields"][] = $field["name"];
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        //If has errors type
        if (isset($this->errors["type"]) && !empty($this->errors["type"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is not correct!");
            foreach ($this->errors["type"] as $field) {
                $errors["fields"][$field["name"]] = Tools::getValue($field["name"]);
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        return $inputs;
    }

    public function renderAjax(int $status = 200, bool $success = true)
    {
        $this->ajaxRender(Helpers::response_json($this->datas, $status, $success));
        die;
    }

    public function renderAjaxErrors($message, int $status = null)
    {
        $this->datas = [];
        $this->datas["errors"]["message"] = $message;
        $this->renderAjax($status === null ? $this->codeErrors : $status, false);
    }

    public function authenticate()
    {
        //Check if Bearer Token passing in header
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $token = $matches[1];
            if (WebserviceKey::keyExists($token)) { //If Bearer token exists
                if (!WebserviceKey::isKeyActive($token)) { //If Bearer token if active
                    $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is not active");
                    $this->renderAjax($this->codeAuthenticate, false);
                }
            } else {
                $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is not correct");
                $this->renderAjax($this->codeAuthenticate, false);
            }
        } else {
            $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is empty");
            $this->renderAjax($this->codeAuthenticate, false);
        }
    }
}
