<?php

use NarrysTech\Api_Rest\controllers\RestController;

class Api_RestCategoryModuleFrontController extends RestController
{
    public $params = [
        'table' => 'category',
        'fields' => [
            [
                'name' => 'id',
                'required' => false,
                'type' => 'text',
                'default' => false
            ],
        ]
    ];

    /**
     * Product
     *
     * @var Category
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

            $this->category = new Category($id_category, true, $this->context->language->id);
            if (!Validate::isLoadedObject($this->category)) {
                $this->renderAjaxErrors($this->trans('This category is no longer available.', [], 'Shop.Notifications.Error'));
            }

            if (!(bool)$this->category->active) {
                $this->renderAjaxErrors($this->trans('This category is not enable.', [], 'Shop.Notifications.Warning'));
            }

            $categoryVar = $this->getTemplateVarCategory($this->category);

            $filteredCategory = Hook::exec(
                'filterCategoryContent',
                ['object' => $categoryVar],
                $id_module = null,
                $array_return = false,
                $check_exceptions = true,
                $use_push = false,
                $id_shop = null,
                $chain = true
            );
            if (!empty($filteredCategory['object'])) {
                $categoryVar = $filteredCategory['object'];
            }

            $this->datas = [
                'category' => $categoryVar,
                'subcategories' => $this->getTemplateVarSubCategories($this->category),
            ];

            $this->renderAjax();
        }

        $this->datas['categories'] = $this->getAllCategoriesParent();

        $this->renderAjax();
        parent::processGetRequest();
    }
}
