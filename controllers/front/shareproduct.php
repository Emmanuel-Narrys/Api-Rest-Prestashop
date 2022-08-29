<?php

use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Product;

class Api_RestShareproductModuleFrontController extends RestController
{

    public $params = [
        'table' => 'product',
        'fields' => [
            [
                'name' => 'id',
                'required' => true,
                'type' => 'text'
            ],
            [
                'name' => 'url_product',
                'required' => true,
                'type' => 'text'
            ],
        ]
    ];

    /**
     * Product
     *
     * @var Product
     */
    protected $product;

    protected $quantity_discounts = [];

    protected function processPostRequest()
    {
        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs = $this->checkErrorsRequiredOrType();
        $id_product = $inputs['id'];
        $url_product = $inputs['url_product'];

        if ((int) $id_product) {
            $id_product = (int) $id_product;
        } else {
            $product_explode = explode('-', $id_product);
            $id_product = (int) $product_explode[0];
        }

        $this->product = new Product($id_product, true, $this->context->language->id);
        if (!Validate::isLoadedObject($this->product)) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        if (!(bool)$this->product->active) {
            $this->renderAjaxErrors($this->trans('This product is not enable.', [], 'Shop.Notifications.Warning'));
        }

        $social_share_links = [];
        $sharing_url = urlencode(addcslashes($url_product, "'"));
        $sharing_name = urlencode(addcslashes($this->product->name, "'"));

        $image_cover_id = $this->product->getCover($this->product->id);
        if (is_array($image_cover_id) && isset($image_cover_id['id_image'])) {
            $image_cover_id = (int) $image_cover_id['id_image'];
        } else {
            $image_cover_id = 0;
        }

        $sharing_img = urlencode(addcslashes($this->context->link->getImageLink($this->product->link_rewrite, $image_cover_id), "'"));

        if (Configuration::get('PS_SC_FACEBOOK')) {
            $social_share_links[] = [
                'label' => $this->trans('Share', [], 'Modules.Sharebuttons.Shop'),
                'class' => 'facebook',
                'url' => 'https://www.facebook.com/sharer.php?u=' . $sharing_url,
            ];
        }

        if (Configuration::get('PS_SC_TWITTER')) {
            $social_share_links[] = [
                'label' => $this->trans('Tweet', [], 'Modules.Sharebuttons.Shop'),
                'class' => 'twitter',
                'url' => 'https://twitter.com/intent/tweet?text=' . $sharing_name . ' ' . $sharing_url,
            ];
        }

        if (Configuration::get('PS_SC_PINTEREST')) {
            $social_share_links[] = [
                'label' => $this->trans('Pinterest', [], 'Modules.Sharebuttons.Shop'),
                'class' => 'pinterest',
                'url' => 'https://www.pinterest.com/pin/create/button/?media=' . $sharing_img . '&url=' . $sharing_url,
            ];
        }

        $this->datas['social_share_links'] = $social_share_links;
        $this->renderAjax();
        parent::processPostRequest();
    }
}
