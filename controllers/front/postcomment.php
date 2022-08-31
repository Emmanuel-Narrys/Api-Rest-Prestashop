<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use PrestaShop\Module\ProductComment\Entity\ProductComment;
use PrestaShop\Module\ProductComment\Entity\ProductCommentCriterion;
use PrestaShop\Module\ProductComment\Entity\ProductCommentGrade;

class Api_RestPostcommentModuleFrontController extends AuthRestController
{
    public $params = [
        'table' => 'comment',
        'fields' => [
            [
                'name' => 'id_product',
                'required' => true,
                'type' => 'number'
            ],
            [
                'name' => 'title',
                'required' => false,
                'type' => 'text',
                'default' => 'NULL'
            ],
            [
                'name' => 'content',
                'required' => true,
                'type' => 'text',
            ],
            [
                'name' => 'grade',
                'required' => true,
                'type' => 'number',
            ],
        ]
    ];

    /**
     * Product
     *
     * @var Product
     */
    protected $product;

    protected function processPostRequest()
    {
        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }
        
        $customer = $this->context->customer;

        if (!(int) $customer->id && !Configuration::get('PRODUCT_COMMENTS_ALLOW_GUESTS')) {
            $this->renderAjaxErrors($this->trans('User should login to leave a comment'));
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_product = (int) $inputs['id_product'];
        $this->product = new Product($id_product, true, $this->context->language->id);

        if (!Validate::isLoadedObject($this->product)) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        $title = $inputs['title'];
        $content = $inputs['content'];
        $grade = (int) $inputs['grade'];

        /** @var ProductCommentRepository $productCommentRepository */
        $productCommentRepository = $this->context->controller->getContainer()->get('product_comment_repository');
        $isPostAllowed = $productCommentRepository->isPostAllowed(
            $id_product,
            (int) $customer->id,
            (int) $customer->id_guest
        );
        if (!$isPostAllowed) {
            $this->renderAjaxErrors($this->trans('You are not allowed to post a review at the moment, please try again later.', [], 'Modules.Productcomments.Shop'));
        }

        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');

        //Create product comment
        $productComment = new ProductComment();
        $productComment
            ->setProductId($id_product)
            ->setTitle($title)
            ->setContent($content)
            ->setCustomerName($customer->firstname.' '.$customer->lastname)
            ->setCustomerId($customer->id)
            ->setGuestId($customer->id_guest)
            ->setDateAdd(new \DateTime('now', new \DateTimeZone('UTC')));
        $entityManager->persist($productComment);
        $this->addCommentGrades($productComment, $grade);

        //Validate comment
        $errors = $this->validateComment($productComment);
        if (!empty($errors)) {
            $this->renderAjaxErrors($errors);
        }

        $entityManager->flush();

        $this->datas['comment'] = $productComment->toArray();
        $this->renderAjax();

        parent::processPostRequest();
    }


    /**
     * @param ProductComment $productComment
     * @param array $criterions
     *
     * @throws Exception
     */
    private function addCommentGrades(ProductComment $productComment, int $grade)
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $this->container->get('doctrine.orm.entity_manager');
        $criterionRepository = $entityManager->getRepository(ProductCommentCriterion::class);

        $criterion = $criterionRepository->findOneBy(['id' => 1]);
        $criterionGrade = new ProductCommentGrade(
            $productComment,
            $criterion,
            $grade
        );

        $entityManager->persist($criterionGrade);
        $productComment->setGrade($grade);
    }

    /**
     * Manual validation for now, this would be nice to use Symfony validator with the annotation
     *
     * @param ProductComment $productComment
     *
     * @return array
     */
    private function validateComment(ProductComment $productComment)
    {
        $errors = [];
        if (empty($productComment->getTitle())) {
            $errors[] = $this->trans('Title cannot be empty', [], 'Modules.Productcomments.Shop');
        } elseif (strlen($productComment->getTitle()) > ProductComment::TITLE_MAX_LENGTH) {
            $errors[] = $this->trans('Title cannot be more than %s characters', [ProductComment::TITLE_MAX_LENGTH], 'Modules.Productcomments.Shop');
        }

        if (!$productComment->getCustomerId()) {
            if (empty($productComment->getCustomerName())) {
                $errors[] = $this->trans('Customer name cannot be empty', [], 'Modules.Productcomments.Shop');
            } elseif (strlen($productComment->getCustomerName()) > ProductComment::CUSTOMER_NAME_MAX_LENGTH) {
                $errors[] = $this->trans('Customer name cannot be more than %s characters', [ProductComment::CUSTOMER_NAME_MAX_LENGTH], 'Modules.Productcomments.Shop');
            }
        }

        return $errors;
    }
}
