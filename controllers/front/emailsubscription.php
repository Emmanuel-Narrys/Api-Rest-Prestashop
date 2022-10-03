<?php

use NarrysTech\Api_Rest\controllers\RestController;

class Api_RestEmailsubscriptionModuleFrontController extends RestController
{

    public $params = [
        'table' => 'Email Subscription',
        'fields' => [
            [
                'name' => 'email',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'action',
                'type' => 'number',
                'required' => true,
                'datas' => [
                    [
                        'value' => 0,
                        'name' => 'Subscription'
                    ],
                    [
                        'value' => 1,
                        'name' => 'Unsubscription'
                    ],
                ]
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
        
        if(!Module::isEnabled('ps_emailsubscription')){
            $this->renderAjaxErrors($this->trans("Module 'ps_emailsubscription' is not install or enable."), $this->codeServeur);
        }
        
        $ps_emailsubscription = Module::getInstanceByName('ps_emailsubscription');
        $return = $ps_emailsubscription->newsletterRegistration();
        if(!$return){
            $this->renderAjaxErrors($this->trans('An error occurred during the subscription process.', [], 'Modules.Emailsubscription.Shop'));
        }else if ($ps_emailsubscription->error){
            $this->renderAjaxErrors($ps_emailsubscription->error);
        }

        $this->datas['message'] = $ps_emailsubscription->valid;
        $this->renderAjax();
        parent::processPostRequest();
    }
}
