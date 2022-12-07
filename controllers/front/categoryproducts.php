<?php

use NarrysTech\Api_Rest\classes\RESTProductLazyArray;
use NarrysTech\Api_Rest\controllers\RestController;
use NarrysTech\Api_Rest\controllers\RestProductListingController;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use Viaziza\Smalldeals\Classes\ProductStore;

class Api_RestCategoryproductsModuleFrontController extends RestProductListingController
{

    public $params = [
        'table' => 'category',
        'fields' => [
            [
                'name' => 'id',
                'required' => false,
                'type' => 'text'
            ]
        ]
    ];

    /**
     * Product
     *
     * @var Category
     */
    protected $category;

    protected $quantity_discounts = [];

    protected $search_string;
    protected $search_tag;

    protected function processGetRequest()
    {
        $this->params = [
            'table' => 'category',
            'fields' => [
                [
                    'name' => 'id',
                    'required' => false,
                    'type' => 'text',
                    'default' => Category::getRootCategory()->id
                ],
                [
                    'name' => 'id_sd_store',
                    'required' => false,
                    'type' => 'number',
                    'default' => 0
                ]
            ]
        ];

        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_category = $inputs['id'];
        $id_sd_store = $inputs['id_sd_store'];

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

        $categoryVar = $this->getTemplateVarCategory();

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

        $variables = $this->getProductSearchVariables();
        $productList = $variables['products'];

        $retriever = new ImageRetriever(
            $this->context->link
        );

        $settings = $this->getProductPresentationSettings();

        foreach ($productList as $key => $product) {
            $populated_product = (new ProductAssembler($this->context))
                ->assembleProduct($product);

            if ($id_sd_store) {
                $productStore = ProductStore::getProductStore((int)$product['id_product'], $id_sd_store);
                if (!Validate::isLoadedObject($productStore)) {
                    $productStore = null;
                }
            } else {
                $productStore = null;
            }

            $lazy_product = new RESTProductLazyArray(
                $settings,
                $populated_product,
                $this->context->language,
                new PriceFormatter(),
                $retriever,
                $this->context->getTranslator(),
                $productStore
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
        $this->datas = array_merge([
            'category' => $categoryVar,
            'subcategories' => $this->getTemplateVarSubCategories(),
        ], $variables);

        $this->renderAjax();
        parent::processGetRequest();
    }

    protected function getTemplateVarCategory()
    {
        $category = $this->objectPresenter->present($this->category);
        $category['image'] = $this->getImage(
            $this->category,
            $this->category->id_image
        );

        return $category;
    }

    protected function getTemplateVarSubCategories()
    {
        return array_map(function (array $category) {
            $object = new Category(
                $category['id_category'],
                $this->context->language->id
            );

            $category['image'] = $this->getImage(
                $object,
                $object->id_image
            );

            $category['url'] = $this->context->link->getCategoryLink(
                $category['id_category'],
                $category['link_rewrite']
            );

            return $category;
        }, $this->category->getSubCategories($this->context->language->id));
    }

    public function getBreadcrumbLinks()
    {
        $breadcrumb = parent::getBreadcrumbLinks();

        foreach ($this->category->getAllParents() as $category) {
            if ($category->id_parent != 0 && !$category->is_root_category && $category->active) {
                $breadcrumb['links'][] = [
                    'title' => $category->name,
                    'url' => $this->context->link->getCategoryLink($category),
                ];
            }
        }

        if ($this->category->id_parent != 0 && !$this->category->is_root_category && $category->active) {
            $breadcrumb['links'][] = [
                'title' => $this->category->name,
                'url' => $this->context->link->getCategoryLink($this->category),
            ];
        }

        return $breadcrumb;
    }

    public function getCategory()
    {
        return $this->category;
    }

    public function getListingLabel()
    {
        if (!Validate::isLoadedObject($this->category)) {
            $this->category = new Category(
                (int) Tools::getValue('id_category'),
                $this->context->language->id
            );
        }

        return $this->trans(
            'Category: %category_name%',
            ['%category_name%' => $this->category->name],
            'Shop.Theme.Catalog'
        );
    }

    protected function getDefaultProductSearchProvider()
    {
        return new CategoryProductSearchProvider(
            $this->getTranslator(),
            $this->category
        );
    }
}
