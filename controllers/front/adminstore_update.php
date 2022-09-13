<?php

use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\AddressStore;
use Viaziza\Smalldeals\Classes\Boutique;
use Viaziza\Smalldeals\Classes\CategoryStore;
use Viaziza\Smalldeals\Classes\OpeningDay;
use Viaziza\Smalldeals\Classes\SocialNetworks;

class Api_RestAdminstore_updateModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Store',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => true,
            ],
            [
                'name' => 'name',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'slug',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'meta_title',
                'type' => 'text',
                'required' => false,
                'default' => ''
            ],
            [
                'name' => 'link_rewrite',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'description',
                'type' => 'text',
                'required' => true,
            ],
            [
                'name' => 'email',
                'type' => 'email',
                'required' => true,
            ],
            [
                'name' => 'web_site',
                'type' => 'text',
                'required' => false,
                'default' => '',
            ],
            [
                'name' => 'published',
                'type' => 'number',
                'required' => false,
                'default' => 1,
            ],
            [
                'name' => 'active',
                'type' => 'number',
                'required' => false,
                'default' => 1,
            ],
            [
                'name' => 'categories',
                'type' => 'array',
                'required' => true,
                'exemple' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'addresses',
                'type' => 'array',
                'required' => true,
                'exemple' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'currencies',
                'type' => 'array',
                'required' => true,
                'exemple' => [1, 2, 3, 4, 5],
            ],
            [
                'name' => 'opening_days',
                'type' => 'array',
                'required' => false,
                'exemple' => [
                    [
                        'id' => 1,
                        'from' => '7h00',
                        'to' => '16h00'
                    ],
                    [
                        'id' => 2,
                        'from' => '7h00',
                        'to' => '16h00'
                    ],
                ],
                'default' => []
            ],
            [
                'name' => 'social_networks',
                'type' => 'array',
                'required' => false,
                'exemple' => [
                    [
                        'id' => 1,
                        'link' => 'https://m.facebook.com/nom-de-ma-page',
                    ],
                    [
                        'id' => 2,
                        'link' => 'https://m.linkedin.com/nom-de-mon-profil',
                    ],
                ],
                'default' => []
            ],
        ],
        "files_fields" => [
            [
                'name' => 'image',
                'type' => 'file',
                'required' => false,
                'default' => [],
                'extensions' => ['png', 'jpg', 'jpeg', 'svg']
            ],
            [
                'name' => 'logo',
                'type' => 'file',
                'required' => false,
                'default' => [],
                'extensions' => ['png', 'jpg', 'jpeg', 'svg']
            ],
            [
                'name' => 'video',
                'type' => 'file',
                'required' => false,
                'default' => [],
                'extensions' => ['mp4', 'avi', 'mkv', 'mov']
            ],
        ]
    ];

    protected function processPostRequest()
    {

        $inputs_1 = $this->checkErrorsRequiredOrType();
        $inputs_2 = $this->checkFilesErrorsRequiredOrType();
        $inputs = array_merge($inputs_1, $inputs_2);
        $id_store = $inputs['id'];

        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        if ((int) $id_store) {
            $id_store = (int) $id_store;
            $store = new Boutique($id_store, $id_lang);
            if (Validate::isLoadedObject($store)) {
                if ($store->id_customer != $customer->id) {
                    $this->renderAjaxErrors($this->trans("This shop is not for this customer.", [], "Shop.Notifications.Error"));
                }
            } else {
                $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
            }
        } else {
            $store_explode = explode('-', $id_store);
            $id_store = (int) $store_explode[0];
            $store = new Boutique($id_store, $id_lang);
            if (Validate::isLoadedObject($store)) {
                if ($store->id_customer != $customer->id) {
                    $this->renderAjaxErrors($this->trans("This shop is not for this customer.", [], "Shop.Notifications.Error"));
                }
            } else {
                $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
            }
        }

        //Check if categories exist
        foreach ($inputs['categories'] as $key => $id_category) {
            $cat = new CategoryStore((int) $id_category, $id_lang);
            if (!Validate::isLoadedObject($cat)) {
                $this->renderAjaxErrors($this->trans("Category id $id_category do not exist."));
            }
        }

        //Check if addresses exist
        foreach ($inputs['addresses'] as $key => $id_address) {
            $add = new AddressStore((int) $id_address, $id_lang);
            if (!Validate::isLoadedObject($add)) {
                $this->renderAjaxErrors($this->trans("Address id $id_address do not exist."));
            }
        }

        //Check if currencies exist
        foreach ($inputs['currencies'] as $key => $id_currency) {
            $cur = new Currency((int) $id_currency, $id_lang);
            if (!Validate::isLoadedObject($cur)) {
                $this->renderAjaxErrors($this->trans("Currency id $id_currency do not exist."));
            }
        }

        //Check if opening days exist
        foreach ($inputs['opening_days'] as $key => $value) {
            $opening_day = new OpeningDay((int) $value['id'], $id_lang);
            if (!Validate::isLoadedObject($opening_day)) {
                $this->renderAjaxErrors($this->trans("Opening day id " . $value['id'] . " do not exist."));
            }
        }

        //Check if social networks exist
        foreach ($inputs['social_networks'] as $key => $value) {
            $social_networks = new SocialNetworks((int) $value['id'], $id_lang);
            if (!Validate::isLoadedObject($social_networks)) {
                $this->renderAjaxErrors($this->trans("Social networks id " . $value['id'] . " do not exist."));
            }
        }

        $store->id_customer = $customer->id;
        $store->name = $inputs['name'];
        $store->slug = $inputs['slug'];
        $store->meta_title = $inputs['meta_title'];
        $store->link_rewrite = $inputs['link_rewrite'];
        $store->description = $inputs['description'];
        $store->email = $inputs['email'];
        $store->web_site = $inputs['web_site'];
        $store->published = $inputs['published'];
        $store->active = $inputs['active'];

        if (!$store->save()) {
            $this->renderAjaxErrors($this->trans("The store has not been update."));
        }

        $result = Boutique::attachCategories($store->id, $inputs['categories']);
        if (!$result) {
            Boutique::attachCategories($store->id, []);
            $store->delete();
            $this->renderAjaxErrors($this->trans("Error of association between the store and the categories."));
        }

        $result = Boutique::attachAddresses($store->id, $inputs['addresses']);
        if (!$result) {
            Boutique::attachAddresses($store->id, []);
            Boutique::attachCategories($store->id, []);
            $store->delete();
            $this->renderAjaxErrors($this->trans("Error of association between the store and the addresses."));
        }

        $result = Boutique::attachCurrencies($store->id, $inputs['currencies']);
        if (!$result) {
            Boutique::attachCurrencies($store->id, []);
            Boutique::attachAddresses($store->id, []);
            Boutique::attachCategories($store->id, []);
            $store->delete();
            $this->renderAjaxErrors($this->trans("Error of association between the store and the currencies."));
        }

        $result = Boutique::attachOpeningDays($store->id, $inputs['opening_days']);
        if (!$result) {
            Boutique::attachOpeningDays($store->id, []);
            Boutique::attachCurrencies($store->id, []);
            Boutique::attachAddresses($store->id, []);
            Boutique::attachCategories($store->id, []);
            $store->delete();
            $this->renderAjaxErrors($this->trans("Error of association between the store and the opening days."));
        }

        $result = Boutique::attachSocialNetWorks($store->id, $inputs['social_networks']);
        if (!$result) {
            Boutique::attachSocialNetWorks($store->id, []);
            Boutique::attachOpeningDays($store->id, []);
            Boutique::attachCurrencies($store->id, []);
            Boutique::attachAddresses($store->id, []);
            Boutique::attachCategories($store->id, []);
            $store->delete();
            $this->renderAjaxErrors($this->trans("Error of association between the store and the social networks."));
        }

        $logo = $inputs['logo'];
        if (!empty($logo)) {
            $logo_extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
            $logo_filename = Boutique::getPathLogo(false) . DIRECTORY_SEPARATOR . $store->id . '.jpg';
            if (file_exists($logo_filename)) {
                unlink($logo_filename);
            }
            $result = move_uploaded_file($logo['tmp_name'], $logo_filename);
            if (!$result) {
                $this->renderAjaxErrors($this->trans("Logo has not been update."));
            }
        }

        $image = $inputs['image'];
        if (!empty($image)) {
            $image_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
            $image_filename = Boutique::getPathImage(false) . DIRECTORY_SEPARATOR . $store->id . '.jpg';
            if (file_exists($image_filename)) {
                unlink($image_filename);
            }
            $result = move_uploaded_file($image['tmp_name'], $image_filename);
            if (!$result) {
                $this->renderAjaxErrors($this->trans("Image has not been update."));
            }
        }

        $this->datas['message'] = $this->trans("The store has been update.");
        $this->datas['store'] = Boutique::getStore($store->id, $id_lang);

        $this->renderAjax();
        parent::processPostRequest();
    }
}
