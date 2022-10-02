<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\Boutique;
use Viaziza\Smalldeals\Classes\ProductStore;

class Api_RestAdminproductModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Product',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => false,
                'default' => 0
            ],
            [
                'name' => 'id_sd_store',
                'type' => 'number',
                'required' => false,
                'default' => 0
            ],
        ]
    ];

    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $inputs = $this->checkErrorsRequiredOrType();
        $id_product = $inputs['id'];
        $id_sd_store = $inputs['id_sd_store'];

        if ($id_product) {
            if ((int) $id_product) {
                $id_product = (int) $id_product;
                $product = new Product($id_product, false, $id_lang);
                if (Validate::isLoadedObject($product)) {
                    $productStore = ProductStore::getProductStore($product->id, $id_sd_store, $id_lang, $customer->id, false);
                    if (Validate::isLoadedObject($productStore)) {
                        $this->datas['product'] = $this->getFullProduct($product->id, $id_lang, $productStore);
                        $this->renderAjax();
                    } else {
                        $this->renderAjaxErrors($this->trans($this->trans('This product has not shop is no longer available.', [], 'Shop.Notifications.Error')));
                    }
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error')));
                }
            } else {
                $product_explode = explode('-', $id_product);
                $id_product = (int) $product_explode[0];
                $product = new Product($id_product, false, $id_lang);
                if (Validate::isLoadedObject($product)) {
                    $productStore = ProductStore::getProductStore($product->id, $id_sd_store, $id_lang, $customer->id, false);
                    if (Validate::isLoadedObject($productStore)) {
                        $this->datas['product'] = $this->getFullProduct($product->id, $id_lang, $productStore);
                        $this->renderAjax();
                    } else {
                        $this->renderAjaxErrors($this->trans($this->trans('This product has not shop is no longer available.', [], 'Shop.Notifications.Error')));
                    }
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error')));
                }
            }
        }

        $products = [];
        $productStores = ProductStore::getProductStores(null, $id_sd_store, $id_lang, $customer->id, false);
        foreach ($productStores as $productStore) {
            $products[] = $this->getFullProduct($productStore->id_product, $id_lang, $productStore);
        }

        $this->datas['products'] = $products;

        $this->renderAjax();

        parent::processGetRequest();
    }

    protected function processPostRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $this->params = [
            'table' => 'Product',
            'fields' => [
                /* Fields required */
                [
                    'name' => 'id_product',
                    'type' => 'number',
                    'required' => true,
                ],
                [
                    'name' => 'id_sd_store',
                    'type' => 'number',
                    'required' => true,
                    'datas' => Boutique::getStores($id_lang, null, $customer->id)
                ],
                [
                    'name' => 'price',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0.00
                ],
                [
                    'name' => 'wholesale_price',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0.00
                ],
                [
                    'name' => 'quantity',
                    'type' => 'number',
                    'required' => false,
                    'default' => 1000
                ],
                [
                    'name' => 'additional_shipping_cost',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0.00
                ],
                [
                    'name' => 'active',
                    'type' => 'number',
                    'required' => false,
                    'default' => 1,
                ],
                [
                    'name' => 'show_price',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0,
                ],
                /* [
                    'name' => 'reference',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'id_category_default',
                    'type' => 'number',
                    'required' => true,
                ],
                // Lang
                [
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'description',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'description_short',
                    'type' => 'text',
                    'required' => true,
                ],
                //Fields no required 
                [
                    'name' => 'id_manufacturer',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'id_supplier',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'id_tax_rules_group',
                    'type' => 'number',
                    'required' => false,
                    'default' => 1
                ],
                [
                    'name' => 'on_sale',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'online_only',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'minimal_quantity',
                    'type' => 'number',
                    'required' => false,
                    'default' => 1
                ],
                [
                    'name' => 'ecotax',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'unity',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'unit_price',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0.00
                ],
                [
                    'name' => 'unit_price_ratio',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0.00
                ],
                [
                    'name' => 'redirect_type',
                    'type' => 'text',
                    'required' => false,
                    'default' => '404'
                ],
                [
                    'name' => 'location',
                    'type' => 'text',
                    'required' => false,
                    'default' => ''
                ],
                [
                    'name' => 'quantity_discount',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'is_virtual',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'product_type',
                    'type' => 'text',
                    'required' => false,
                    'default' => "standard"
                ],
                [
                    'name' => 'condition',
                    'type' => 'text',
                    'required' => false,
                    'default' => "new"
                ],
                [
                    'name' => 'visibility',
                    'type' => 'text',
                    'required' => false,
                    'default' => "both"
                ],
                // Lang
                [
                    'name' => 'meta_title',
                    'type' => 'text',
                    'required' => false,
                    'default' => ''
                ],
                [
                    'name' => 'meta_description',
                    'type' => 'text',
                    'required' => false,
                    'default' => ''
                ],
                [
                    'name' => 'meta_keywords',
                    'type' => 'text',
                    'required' => false,
                    'default' => ''
                ],
                [
                    'name' => 'available_now',
                    'type' => 'text',
                    'required' => false,
                    'default' => ''
                ],
                [
                    'name' => 'available_later',
                    'type' => 'text',
                    'required' => false,
                    'default' => ''
                ],
                [
                    'name' => 'width',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'height',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'depth',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'weight',
                    'type' => 'number',
                    'required' => false,
                    'default' => 0
                ],
                [
                    'name' => 'categories',
                    'type' => 'array',
                    'required' => true,
                    'exemple' => [1, 2, 3, 4, 5],
                ], */
            ],
            /* "files_fields" => [
                [
                    'name' => 'cover',
                    'type' => 'file',
                    'required' => true,
                    'extensions' => ['png', 'jpg', 'jpeg'],
                    'min_width' => 800,
                    'min_hieght' => 800,
                ],
                [
                    'name' => 'video',
                    'type' => 'file',
                    'required' => false,
                    'extensions' => ['mp4', 'avi', 'mkv', 'mov']
                ],
            ],
            "images_files_field" => [
                [
                    'name' => 'images',
                    'type' => 'array',
                    'required' => true,
                    'extensions' => ['png', 'jpg', 'jpeg'],
                    'min_width' => 800,
                    'min_hieght' => 800,
                ],
            ] */
        ];

        if (Tools::getValue('schema', false)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        /* $inputs_1 = $this->checkErrorsRequiredOrType();
        $inputs_2 = $this->checkFilesErrorsRequiredOrType();
        $inputs_3 = $this->checkProductImagesErrorsRequiredOrType();
        $inputs = array_merge($inputs_1, $inputs_2, $inputs_3); */

        $inputs = $this->checkErrorsRequiredOrType();

        /* $q = new DbQuery();
        $q->select('a.id_product')->from('product', 'a')->where("a.reference = '" . $inputs['reference'] . "'");
        $result = Db::getInstance()->getValue($q);
        if ($result != false) {
            $this->renderAjaxErrors($this->trans("A product exist with this reference."));
        } */

        //Check if store exist
        $store = new Boutique((int) $inputs['id_sd_store'], $id_lang);
        if (!Validate::isLoadedObject($store)) {
            $this->renderAjaxErrors($this->trans("Store do not exist."));
        }

        //Check if product exist
        $product = new Product((int) $inputs['id_product'], $id_lang);
        if (!Validate::isLoadedObject($product)) {
            $this->renderAjaxErrors($this->trans("Store do not exist."));
        }

        $productStore = ProductStore::getProductStore($product->id, $store->id, $id_lang, $customer->id, false);
        if (Validate::isLoadedObject($productStore)) {
            $this->renderAjaxErrors($this->trans("This product exist on this Store."));
        }

        /* //Check if category exist
        $cat_default = new Category((int) $inputs['id_category_default'], $id_lang);
        if (!Validate::isLoadedObject($cat_default)) {
            $this->renderAjaxErrors($this->trans("Category default do not exist."));
        } */

        /* //Check if categories exist
        foreach ($inputs['categories'] as $key => $id_category) {
            $cat = new Category((int) $id_category, $id_lang);
            if (!Validate::isLoadedObject($cat)) {
                $this->renderAjaxErrors($this->trans("Category id $id_category do not exist."));
            }
        } */

        /* $product = new Product(null, $id_lang);
        $product->id_category_default = $inputs['id_category_default'];
        $product->price = $inputs['price'];
        $product->wholesale_price = $inputs['wholesale_price'];
        $product->reference = $inputs['reference'];
        $product->name = $inputs['name'];
        $product->link_rewrite = Tools::link_rewrite($product->name);
        $product->description = $inputs['description'];
        $product->description_short = $inputs['description_short'];
        $product->id_manufacturer = $inputs['id_manufacturer'];
        $product->id_supplier = $inputs['id_supplier'];
        $product->id_tax_rules_group = $inputs['id_tax_rules_group'];
        $product->on_sale = $inputs['on_sale'];
        $product->online_only = $inputs['online_only'];
        $product->minimal_quantity = $inputs['minimal_quantity'];
        $product->ecotax = $inputs['ecotax'];
        $product->unity = $inputs['unity'];
        $product->unit_price = $inputs['unit_price'];
        $product->unit_price_ratio = $inputs['unit_price_ratio'];
        $product->additional_shipping_cost = $inputs['additional_shipping_cost'];
        $product->redirect_type = $inputs['redirect_type'];
        $product->is_virtual = $inputs['is_virtual'];
        $product->product_type = $inputs['product_type'];
        $product->condition = $inputs['condition'];
        $product->visibility = $inputs['visibility'];
        $product->meta_title = $inputs['meta_title'];
        $product->meta_description = $inputs['meta_description'];
        $product->available_now = $inputs['available_now'];
        $product->available_later = $inputs['available_later'];
        $product->weight = $inputs['weight'];
        $product->height = $inputs['height'];
        $product->depth = $inputs['depth'];
        $product->width = $inputs['width'];
        $product->active = $inputs['active'];

        if (!$product->save()) {
            $this->renderAjaxErrors($this->trans("The product has not been saved."));
        } */
        
        $productStore = new ProductStore(null, $id_lang);
        $productStore->id_sd_store = $store->id;
        $productStore->id_product = $product->id;
        $productStore->price = $inputs['price'];
        $productStore->wholesale_price = $inputs['wholesale_price'];
        $productStore->additional_shipping_cost = $inputs['additional_shipping_cost'];
        $productStore->active = $inputs['active'];
        $productStore->show_price = $inputs['show_price'];
        $productStore->quantity = (int) $inputs['quantity'];
        
        if (!$productStore->save()) {
            $product->delete();
            $this->renderAjaxErrors($this->trans("The product store has not been saved."));
        }

        /* $result = $product->addToCategories($inputs['categories']);
        if (!$result) {
            $product->deleteCategories();
            $product->delete();
            $this->renderAjaxErrors($this->trans("Error of association between the product and the categories."));
        } */

        /* StockAvailable::setQuantity($product->id, 0, (int) $inputs['quantity']); */
        StockAvailable::updateQuantity($product->id, 0, $productStore->quantity);
        
        /* $errors = [];
        $image = new Image();
        $image->id_product = $product->id;
        $image->cover = true;
        $image->position = Image::getHighestPosition($product->id) + 1;
        if ($image->validateFields(false, true) === true && ($image->validateFieldsLang(false, true) === true &&
            $image->add())) {
            $image->associateTo($this->context->shop->id);
            if (!AdminImportController::saveImage($product->id, $image->id, $inputs['cover']['tmp_name'], 'products', true)) {
                $image->delete();
                $errors[] = 'cover';
            }
        }

        foreach ($inputs['images'] as $key => $file) {
            $img = new Image();
            $img->id_product = $product->id;
            $img->position = Image::getHighestPosition($product->id) + 1;
            if ($img->validateFields(false, true) === true && ($img->validateFieldsLang(false, true) === true &&
                $img->add())) {
                $img->associateTo($this->context->shop->id);
                if (!AdminImportController::saveImage($product->id, $img->id, $file['tmp_name'], 'products', true)) {
                    $img->delete();
                    $errors[] = $key + 1;
                }
            }
        }

        if (!empty($errors)) {
            $this->datas['error']['message'] = $this->trans("Image(s) " . implode(',', $errors) . " do not save.");
        } */

        $products = [];
        $productStores = ProductStore::getProductStores(null, null, $id_lang, $customer->id, false);
        foreach ($productStores as $productStore) {
            $products[] = $this->getFullProduct($productStore->id_product, $id_lang, $productStore);
        }

        $this->datas['message'] = $this->trans("The product has been saved.");
        $this->datas['products'] = $products;

        $this->renderAjax();
        parent::processPostRequest();
    }
}
