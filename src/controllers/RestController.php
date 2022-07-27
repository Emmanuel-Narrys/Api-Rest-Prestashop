<?php

namespace NarrysTech\Api_Rest\controllers;

use NarrysTech\Api_Rest\classes\Helpers;
use PrestaShop\PrestaShop\Adapter\Entity\ModuleFrontController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;

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
     * Undocumented variable
     *
     * @var integer
     */
    public $codeSuccess = 200;
    /**
     * Undocumented variable
     *
     * @var integer
     */
    public $codeMethod = 405;
    /**
     * Undocumented variable
     *
     * @var integer
     */
    public $codeServeur = 500;
    /**
     * Undocumented variable
     *
     * @var integer
     */
    public $codeNotFound = 404;
    /**
     * Undocumented variable
     *
     * @var integer
     */
    public $codeErrors = 400;

    public function init()
    {

        header("Content-type: application/json");
        parent::init();

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
        ], 405));
        die;
    }

    
    /**
     * Undocumented function
     *
     * @param string $type
     * @param mixed $value
     * @return boolean
     */
    public function isValideType(string $type, string $value):bool
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
    public function checkErrorsRequiredOrType ():array
    {
        $inputs = array_map(function ($a) {
            //Get Name
            $name = $a['name'];
            //Get Required
            $required = (bool) $a['required'];
            //Get Type
            $type = $a['type'];
            //Get Type
            $value = Tools::getValue($name);

            if (($required === true) && (($value == false || is_null($value)))) {//Field is required and null
                $this->errors["required"][] = $a;
            }else if ($this->isValideType($type, $value) == false) {//Field type if not valide
                $this->errors["type"][] = $a;
            }else {//Field is correct
                return [$name => $value];
            }

        }, $this->params['fields']);

        //If has errors required
        if(isset($this->errors["required"]) && !empty($this->errors["required"])){
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is required!");
            foreach($this->errors["required"] as $field){
                $errors["fields"][] = $field["name"];
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax(400);
        }

        //If has errors type
        if(isset($this->errors["type"]) && !empty($this->errors["type"])){
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is not correct!");
            foreach($this->errors["type"] as $field){
                $errors["fields"][$field["name"]] = Tools::getValue($field["name"]);
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax(400);
        }

        return $inputs;
    }

    public function renderAjax(int $status = 200, bool $success = true) {
        $this->ajaxRender(Helpers::response_json($this->datas, $status, $success));
        die;
    }
}
