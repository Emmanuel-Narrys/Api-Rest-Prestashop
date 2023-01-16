<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Module;

if (file_exists(_PS_MODULE_DIR_ . 'ets_blog/classes/Ets_blog_post.php')) {
    require_once(_PS_MODULE_DIR_ . 'ets_blog/classes/Ets_blog_post.php');
}
class Api_RestBootstrapModuleFrontController extends RestController
{

    protected function processGetRequest()
    {
        if (!Module::isEnabled('ps_imageslider')) {
            $this->renderAjaxErrors($this->trans("Module 'ps_imageslider' is not install."), $this->codeServeur);
        }
        if (!Module::isEnabled('ps_contactinfo')) {
            $this->renderAjaxErrors($this->trans("Module 'ps_contactinfo' is not install."), $this->codeServeur);
        }
        if (!Module::isEnabled('ps_featuredproducts')) {
            $this->renderAjaxErrors($this->trans("Module 'ps_featuredproducts' is not install."), $this->codeServeur);
        }
        if (!Module::isEnabled('ets_blog')) {
            $this->renderAjaxErrors($this->trans("Module 'ets_blog' is not install."), $this->codeServeur);
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

        $this->datas['shop'] = $this->getTemplateVarShop();
        $this->datas['blogs'] = $this->getNewsBlobs();

        /* $ps_contactinfo = Module::getInstanceByName('ps_contactinfo');
        $this->datas = array_merge($this->datas, $ps_contactinfo->getWidgetVariables(null, [])); */

        $this->renderAjax();
        parent::processGetRequest();
    }

    public function getNewsBlobs(int $limit = 10)
    {
        $posts = Ets_blog_post::getPostsWithFilter(' AND p.enabled=1', 'p.date_add DESC,', 0, $limit);
        $news_posts = [];
        if ($posts) {
            foreach ($posts as $key => $p) {
                $post = new Ets_blog_post((int) $p["id_post"]);
                if (!Validate::isLoadedObject($post))
                    continue;
                $news_posts[] = $post->getPost();
            }
            return $news_posts;
        }
        return [];
    }
}
