<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Context;
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use PrestaShop\PrestaShop\Adapter\Entity\Module;
use PrestaShop\PrestaShop\Adapter\Entity\Product;

class Api_RestBootstrapModuleFrontController extends RestController
{

    protected function processGetRequest()
    {
        if(!Module::isEnabled('ps_imageslider')){
            $this->renderAjaxErrors($this->trans("Module 'ps_imageslider' is not install."), $this->codeServeur);
        }
        if(!Module::isEnabled('ps_contactinfo')){
            $this->renderAjaxErrors($this->trans("Module 'ps_contactinfo' is not install."), $this->codeServeur);
        }
        if(!Module::isEnabled('ps_featuredproducts')){
            $this->renderAjaxErrors($this->trans("Module 'ps_featuredproducts' is not install."), $this->codeServeur);
        }

        $ps_imageslider = Module::getInstanceByName('ps_imageslider');
        $this->datas = array_merge($this->datas, $ps_imageslider->getWidgetVariables(null, []));

        $this->datas['categories'] = self::getAllCategoriesParent();

        $ps_featuredproducts = Module::getInstanceByName('ps_featuredproducts');
        $this->datas['featured_products'] = $ps_featuredproducts->getWidgetVariables(null, [])['products'];

        $this->datas['number_of_ads'] = self::getNbProduct();
        $this->datas['number_of_customers'] = self::getNbCustomer();
        $this->datas['number_of_categories'] = self::getNbCategory();

        $ps_contactinfo = Module::getInstanceByName('ps_contactinfo');
        $this->datas = array_merge($this->datas, $ps_contactinfo->getWidgetVariables(null, []));


        $this->renderAjax();
        parent::processGetRequest();
    }

    private static function getAllCategoriesParent(): array
    {
        $results = Category::getHomeCategories(Context::getContext()->language->id, true);

        return array_map(function ($a) {
            $cat = new Category((int) $a['id_category'], Context::getContext()->language->id);
            return array_merge($a, [
                'description' => $cat->description,
                'meta_title' => $cat->meta_title,
                'meta_description' => $cat->meta_description,
                'number_of_ads' => self::getNbProductsToCategory($cat->id),
                'image' => Context::getContext()->link->getCatImageLink($cat->name, $cat->id, 'category_default'),
                'thumb' => Context::getContext()->link->getCatImageLink($cat->name, $cat->id, '0_thumb')
            ]);
        }, $results);
    }

    private static function getNbProductsToCategory(int $id_category): int
    {
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', $id_category, true, Context::getContext());
        return count($results);
    }

    private static function getNbCategory():int
    {
        $results = Category::getCategories();
        return count($results);
    }

    private static function getNbProduct():int
    {
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', false, true);
        return count($results);
    }

    private static function getNbCustomer():int
    {
        $results = Customer::getCustomers(true);
        return count($results);
    }
}
