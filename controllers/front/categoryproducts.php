<?php

use NarrysTech\Api_Rest\classes\RESTProductLazyArray;
use NarrysTech\Api_Rest\controllers\RestController;
use NarrysTech\Api_Rest\controllers\RestProductListingController;
use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class Api_RestCategoryproductsModuleFrontController extends RestProductListingController
{

    public $params = [
        'table' => 'category',
        'fields' => [
            [
                'name' => 'id',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ];

    /**
     * Product
     *
     * @var Category
     */
    protected $category;

    protected $quantity_discounts = [];

    protected function processGetRequest()
    {
        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_category = $inputs['id'];

        if ((int) $id_category) {
            $id_category = (int) $id_category;
        } else {
            $category_explode = explode('-', $id_category);
            $id_category = (int) $category_explode[0];
        }
        $_GET['id_category'] = $id_category;

        $this->category = new Category($id_category, true, $this->context->language->id);
        if (!Validate::isLoadedObject($this->category)) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        if (!(bool)$this->category->active) {
            $this->renderAjaxErrors($this->trans('This category is not enable.', [], 'Shop.Notifications.Warning'));
        }

        $variables = $this->getProductSearchVariables();
        $productList = $variables['products'];
        $retriever = new ImageRetriever(
            $this->context->link
        );

        $settings = $this->getProductPresentationSettings();

        foreach ($productList as $key => $product) {
            $populated_product = (new ProductAssembler($this->context))
                ->assembleProduct($product);

            $lazy_product = new RESTProductLazyArray(
                $settings,
                $populated_product,
                $this->context->language,
                new PriceFormatter(),
                $retriever,
                $this->context->getTranslator()
            );

            $productList[$key] = $lazy_product->getProduct();
        }

        $facets = array();
        foreach ($variables['facets']['filters']->getFacets() as $facet) {
            array_push($facets, $facet->toArray());
        }

        foreach ($productList as $key => $product) {
            $p = new Product(
                $product['id_product'],
                true,
                $this->context->language->id,
                $this->context->shop->id,
                $this->context
            );
            $productList[$key]['manufacturer_name'] = $p->manufacturer_name;
        }

        $this->datas['category'] = [
            'description' => $this->category->description,
            'active' => $this->category->active,
            'images' => $this->getImage(
                $this->category,
                $this->category->id_image
            ),
            'label' => $variables['label'],
            'products' => $productList,
            'sort_orders' => $variables['sort_orders'],
            'sort_selected' => $variables['sort_selected'],
            'pagination' => $variables['pagination'],
            'facets' => $facets
        ];

        $this->renderAjax();
        parent::processGetRequest();
    }
}
