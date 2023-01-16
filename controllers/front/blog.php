<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;

if (file_exists(_PS_MODULE_DIR_ . 'ets_blog/classes/Ets_blog_post.php')) {
    require_once(_PS_MODULE_DIR_ . 'ets_blog/classes/Ets_blog_post.php');
}

class Api_RestBlogModuleFrontController extends RestController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [
        "table" => 'blogs',
        "fields" => [
            [
                "name" => "id",
                "required" => false,
                "type" => "text",
                'default' => 0
            ]
        ]
    ];

    /**
     * Undocumented function
     *
     * @return void
     */
    protected function processGetRequest()
    {

        $id_lang = $this->context->language->id;

        $schema = Tools::getValue('schema');
        $this->params = [
            "table" => 'stores',
            "fields" => [
                [
                    "name" => "id",
                    "required" => false,
                    "type" => "text",
                    'default' => 0
                ],
                [
                    "name" => "page",
                    "required" => false,
                    "type" => "number",
                    'default' => 1,
                ]
            ]
        ];

        $inputs = $this->checkErrorsRequiredOrType();
        $id_blog = $inputs['id'];

        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        if ($id_blog) {
            if ((int) $id_blog) {
                $id_blog = (int) $id_blog;
                $post = new Ets_blog_post($id_blog, $id_lang ?? null);
                if (Validate::isLoadedObject($post)) {
                    $this->datas['blog'] = $post->getPost();
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This blog is no longer available.', [], 'Shop.Notifications.Error')));
                }
            } else {
                $blog_explode = explode('-', $id_blog);
                $id_blog = (int) $blog_explode[0];
                $post = new Ets_blog_post($id_blog, $id_lang ?? null);
                if (Validate::isLoadedObject($post)) {
                    $this->datas['blog'] = $post->getPost();
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This blog is no longer available.', [], 'Shop.Notifications.Error')));
                }
            }
        }

        list("pagination" => $this->datas['pagination'], "blogs" => $this->datas['blogs']) = $this->getBlogsWithPagination();
        $this->renderAjax();

        parent::processGetRequest();
    }

    private function getBlogsWithPagination()
    {
        $per_page = (int) Configuration::get("SMALLDEALS_BLOG_PER_PAGE", null, null, null, 10);
        $results = Ets_blog_post::getPostsWithFilter();
        $total = 0;
        $total_page = 0;
        $offset = 0;
        $page = (int) Tools::getValue("page", 1);
        $to = 0;
        $from = 0;

        if ($results && !empty($results)) {
            $total = count($results);
            $total_page = ceil($total / $per_page);
            $offset = ($page - 1) * $per_page;
            $from = $offset + 1;
            $to = $offset + $per_page;
        }

        $results = Ets_blog_post::getPostsWithFilter(false, false, $offset, $per_page);
        $blogs = [];
        foreach($results as $key => $p){
            $post = new Ets_blog_post((int) $p["id_post"], $this->context->language->id);
            if(!Validate::isLoadedObject($post))
                continue;
            $blogs[] = $post->getPost();
        }

        $pagination = [
            "total" => $total,
            "total_page" => $total_page,
            "per_page" => $per_page,
            "page" => $page,
            "from" => $from,
            "to" => $offset + count($blogs)
        ];

        return ["blogs" => $blogs, "pagination" => $pagination];
    }
}
