<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\Module\ProductComment\Repository\ProductCommentRepository;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;

class Api_RestCommentModuleFrontController extends RestController
{
    public $params = [
        'table' => 'comment',
        'fields' => [
            [
                'name' => 'id_product',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'page',
                'required' => false,
                'type' => 'number',
                'default' => 1
            ],
        ]
    ];

    /**
     * Product
     *
     * @var Product
     */
    protected $product;

    protected function processGetRequest()
    {
        $inputs = $this->checkErrorsRequiredOrType();
        $id_product = (int) $inputs['id_product'];
        $this->product = new Product($id_product, true, $this->context->language->id);

        if (!Validate::isLoadedObject($this->product)) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        $page = (int) $inputs['page'];
        $isLastNameAnynomus = Configuration::get('PRODUCT_COMMENTS_ANONYMISATION');
        /** @var ProductCommentRepository $productCommentRepository */
        $productCommentRepository = $this->context->controller->getContainer()->get('product_comment_repository');

        $productComments = $productCommentRepository->paginate(
            $this->product->id,
            $page,
            (int) Configuration::get('PRODUCT_COMMENTS_COMMENTS_PER_PAGE'),
            (bool) Configuration::get('PRODUCT_COMMENTS_MODERATE')
        );
        $productCommentsNb = $productCommentRepository->getCommentsNumber(
            $this->product->id,
            (bool) Configuration::get('PRODUCT_COMMENTS_MODERATE')
        );

        $responseArray = [
            'comments_nb' => $productCommentsNb,
            'comments_per_page' => Configuration::get('PRODUCT_COMMENTS_COMMENTS_PER_PAGE'),
            'comments' => [],
        ];

        foreach ($productComments as $productComment) {
            $dateAdd = new \DateTime($productComment['date_add'], new \DateTimeZone('UTC'));
            $dateAdd->setTimezone(new \DateTimeZone(date_default_timezone_get()));
            $dateFormatter = new \IntlDateFormatter(
                $this->context->language->locale,
                \IntlDateFormatter::SHORT,
                \IntlDateFormatter::SHORT
            );
            $productComment['customer_name'] = htmlentities($productComment['customer_name']);
            $productComment['title'] = htmlentities($productComment['title']);
            $productComment['content'] = htmlentities($productComment['content']);
            $productComment['date_add'] = $dateFormatter->format($dateAdd);

            if ($isLastNameAnynomus) {
                $productComment['lastname'] = substr($productComment['lastname'], 0, 1) . '.';
            }

            $usefulness = $productCommentRepository->getProductCommentUsefulness($productComment['id_product_comment']);
            $productComment = array_merge($productComment, $usefulness);
            if (empty($productComment['customer_name']) && !isset($productComment['firstname']) && !isset($productComment['lastname'])) {
                $productComment['customer_name'] = $this->trans('Deleted account', [], 'Modules.Productcomments.Shop');
            }

            $responseArray['comments'][] = $productComment;
        }

        $this->datas = $responseArray;

        $this->renderAjax();
        parent::processGetRequest();
    }

    protected function processPostRequest()
    {
        parent::processPostRequest();
    }
}
