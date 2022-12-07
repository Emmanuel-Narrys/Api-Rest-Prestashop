<?php

use NarrysTech\Api_Rest\classes\RESTProductLazyArray;
use NarrysTech\Api_Rest\controllers\RestProductListingController;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;

class Api_RestAdminproduct_searchModuleFrontController extends RestProductListingController
{

    public $params = [
        'table' => 'product',
        'fields' => [
            [
                'name' => 's',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'tag',
                'required' => false,
                'type' => 'text'
            ]
        ]
    ];

    protected $search_string;
    protected $search_tag;

    protected function processGetRequest()
    {
        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $this->search_string = $inputs['s'];
        $this->search_tag = $inputs['tag'];

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

        /* $facets = array();
        foreach ($variables['facets']['filters']->getFacets() as $facet) {
            array_push($facets, $facet->toArray());
        } */

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
        $variables['products'] = $productList;

        $this->datas = $variables;

        $this->renderAjax();
        parent::processGetRequest();
    }
}
