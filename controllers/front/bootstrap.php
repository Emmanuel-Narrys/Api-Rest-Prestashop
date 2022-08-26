<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Module;

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

        $this->datas['categories'] = $this->getAllCategoriesParent();

        /* $ps_featuredproducts = Module::getInstanceByName('ps_featuredproducts');
        $this->datas['featured_products'] = $ps_featuredproducts->getWidgetVariables(null, [])['products']; */
        $this->datas['featured_products'] = $this->getFeaturedProducts();

        $this->datas['number_of_ads'] = Helpers::getNbProduct();
        $this->datas['number_of_customers'] = Helpers::getNbCustomer();
        $this->datas['number_of_categories'] = Helpers::getNbCategory();

        $ps_contactinfo = Module::getInstanceByName('ps_contactinfo');
        $this->datas = array_merge($this->datas, $ps_contactinfo->getWidgetVariables(null, []));

        $this->renderAjax();
        parent::processGetRequest();
    }

}
