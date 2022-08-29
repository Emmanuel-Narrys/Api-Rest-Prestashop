<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;

class Api_RestCustomersponsorshipsModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Customer',
        'fields' => []
    ];

    protected function processGetRequest()
    {
        $customer = $this->context->customer;

        $this->datas['sponsorships'] = $this->getSponsorships($customer->id);
        $this->datas['nb_of_sponsorships'] = count($this->datas['sponsorships']);

        $this->renderAjax();

        parent::processGetRequest();
    }

    public function getSponsorships(int $id_customer):array
    {
       $q = new DbQuery();
       $q->select("c.id_customer, c.firstname, c.lastname")
       ->from('customer' , 'c')
       ->where("c.id_sponsorship = $id_customer");

       $result = Db::getInstance()->executeS($q);
       if(!empty($result) && $result){
        return array_map(function ($a){
            return (object) [
                "id_customer" => $a['id_customer'],
                "firstname" => $a['firstname'],
                "lastname" => $a['lastname'],
            ];
        }, $result);
       }

       return [];
    }
}
