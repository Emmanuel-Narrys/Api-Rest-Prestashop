<?php

use NarrysTech\Api_Rest\controllers\RestController;
use Viaziza\Smalldeals\Classes\CategoryStore;

class Api_RestCategory_storeModuleFrontController extends RestController
{
    public $params = [
        'table' => 'category store',
        'fields' => [
            [
                'name' => 'id',
                'required' => false,
                'type' => 'text',
                'default' => 0
            ],
        ]
    ];

    /**
     * Product
     *
     * @var CategoryStore
     */
    protected $category;

    protected function processGetRequest()
    {
        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_category = $inputs['id'];

        if ($id_category) {
            if ((int) $id_category) {
                $id_category = (int) $id_category;
            } else {
                $category_explode = explode('-', $id_category);
                $id_category = (int) $category_explode[0];
            }
            $_GET['id_category'] = $id_category;

            $this->category = new CategoryStore($id_category, true, $this->context->language->id);
            if (!Validate::isLoadedObject($this->category)) {
                $this->renderAjaxErrors($this->trans('This category store is no longer available.', [], 'Shop.Notifications.Error'));
            }

            if (!(bool)$this->category->active) {
                $this->renderAjaxErrors($this->trans('This category store is not enable.', [], 'Shop.Notifications.Warning'));
            }

            $this->datas = [
                'category' => $this->category,
            ];

            $this->renderAjax();
        }

        $this->datas['categories'] = CategoryStore::getCategoryStore($this->context->language->id);

        $this->renderAjax();
        parent::processGetRequest();
    }
}
