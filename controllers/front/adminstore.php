<?php

use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\controllers\AuthRestController;
use Viaziza\Smalldeals\Classes\AddressStore;
use Viaziza\Smalldeals\Classes\Boutique;
use Viaziza\Smalldeals\Classes\CategoryStore;
use Viaziza\Smalldeals\Classes\OpeningDay;
use Viaziza\Smalldeals\Classes\SocialNetworks;
use Viaziza\Smalldeals\Classes\TypeStore;

class Api_RestAdminstoreModuleFrontController extends AuthRestController
{

    public $params = [
        'table' => 'Store',
        'fields' => [
            [
                'name' => 'id',
                'type' => 'number',
                'required' => false,
                'default' => 0
            ]
        ]
    ];

    protected function processGetRequest()
    {
        $customer = $this->context->customer;
        $id_lang = $this->context->language->id;

        $inputs = $this->checkErrorsRequiredOrType();
        $id_store = $inputs['id'];

        if ($id_store) {
            if ((int) $id_store) {
                $id_store = (int) $id_store;
                $store = Boutique::getStore($id_store, $id_lang ?? null);
                if (Validate::isLoadedObject($store)) {
                    if ($store->id_customer != $customer->id) {
                        $this->renderAjaxErrors($this->trans("This shop is not for this customer.", [], "Shop.Notifications.Error"));
                    }
                    $this->datas['store'] = $store;
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
                }
            } else {
                $store_explode = explode('-', $id_store);
                $id_store = (int) $store_explode[0];
                $store = Boutique::getStore($id_store, $id_lang ?? null);
                if (Validate::isLoadedObject($store)) {
                    if ($store->id_customer != $customer->id) {
                        $this->renderAjaxErrors($this->trans("This shop is not for this customer.", [], "Shop.Notifications.Error"));
                    }
                    $this->datas['store'] = $store;
                    $this->renderAjax();
                } else {
                    $this->renderAjaxErrors($this->trans($this->trans('This shop is no longer available.', [], 'Shop.Notifications.Error')));
                }
            }
        }

        $this->datas['stores'] = Boutique::getFullStores($id_lang ?? null, $customer->id, false);

        $this->renderAjax();

        parent::processGetRequest();
    }

    protected function processPostRequest()
    {
        $id_lang = $this->context->language->id;
        $customer = $this->context->customer;

        $this->params = [
            'table' => 'Store',
            'fields' => [
                [
                    'name' => 'id_sd_type_store',
                    'type' => 'number',
                    'required' => true,
                    "data" => TypeStore::getTypeStores($id_lang)
                ],
                [
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                ],
                [
                    'name' => 'name',
                    'type' => 'text',
                    'required' => true,
                ],
                /* [
                    'name' => 'slug',
                    'type' => 'text',
                    'required' => true,
                ], */
                [
                    'name' => 'meta_title',
                    'type' => 'text',
                    'required' => false,
                    'default' => ''
                ],
                /* [
                    'name' => 'link_rewrite',
                    'type' => 'text',
                    'required' => true,
                ], */
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
                    "data" => CategoryStore::getCategoryStore($id_lang)
                ],
                [
                    'name' => 'addresses',
                    'type' => 'array',
                    'required' => true,
                    'exemple' => [1, 2, 3, 4, 5],
                    "data" => AddressStore::getAddressStores($id_lang, $customer->id, null, false)
                ],
                [
                    'name' => 'currencies',
                    'type' => 'array',
                    'required' => true,
                    'exemple' => [1, 2, 3, 4, 5],
                    "data" => Currency::getCurrencies(true)
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
                    'default' => [],
                    "data" => OpeningDay::getOpeningDays($id_lang)
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
                    'default' => [],
                    "data" => SocialNetworks::getSocialNetworks($id_lang)
                ],
            ],
            "files_fields" => [
                [
                    'name' => 'image',
                    'type' => 'file',
                    'required' => true,
                    'extensions' => ['png', 'jpg', 'jpeg', 'svg']
                ],
                [
                    'name' => 'logo',
                    'type' => 'file',
                    'required' => true,
                    'extensions' => ['png', 'jpg', 'jpeg', 'svg']
                ],
                [
                    'name' => 'video',
                    'type' => 'file',
                    'required' => false,
                    'extensions' => ['mp4', 'avi', 'mkv', 'mov']
                ],
            ]
        ];

        $schema = Tools::getValue('schema');
        if ($schema && !is_null($schema)) {
            $this->datas = $this->params;
            $this->renderAjax();
        }

        $inputs_1 = $this->checkErrorsRequiredOrType();
        $inputs_2 = $this->checkFilesErrorsRequiredOrType();
        $inputs = array_merge($inputs_1, $inputs_2);


        //Check if categories exist
        $typestore = new TypeStore((int) $inputs['id_sd_type_store'], $id_lang);
        if (!Validate::isLoadedObject($typestore)) {
            $this->renderAjaxErrors($this->trans("Type store do not exist."));
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
            } else if ($add->id_sd_store != 0) {
                $this->renderAjaxErrors($this->trans("Address id $id_address is already used."));
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

        $store = new Boutique(null, $id_lang);
        $store->id_customer = $customer->id;
        $store->name = $inputs['name'];
        $store->link_rewrite = Tools::link_rewrite($inputs['name']);
        /* $store->slug = $inputs['slug']; */
        $store->meta_title = $inputs['meta_title'];
        /* $store->link_rewrite = $inputs['link_rewrite']; */
        $store->description = $inputs['description'];
        $store->email = $inputs['email'];
        $store->web_site = $inputs['web_site'];
        $store->published = $inputs['published'];
        $store->active = $inputs['active'];
        $store->id_sd_type_store = $inputs['id_sd_type_store'];

        if (!$store->save()) {
            $this->renderAjaxErrors($this->trans("The store has not been saved."));
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
        $logo_extension = pathinfo($logo['name'], PATHINFO_EXTENSION);
        $logo_filename = Boutique::getPathLogo(false) . DIRECTORY_SEPARATOR . $store->id . '.jpg';
        $result = move_uploaded_file($logo['tmp_name'], $logo_filename);
        if (!$result) {
            $this->renderAjaxErrors($this->trans("Logo has not been saved."));
        }

        $image = $inputs['image'];
        $image_extension = pathinfo($image['name'], PATHINFO_EXTENSION);
        $image_filename = Boutique::getPathImage(false) . DIRECTORY_SEPARATOR . $store->id . '.jpg';
        $result = move_uploaded_file($image['tmp_name'], $image_filename);
        if (!$result) {
            $this->renderAjaxErrors($this->trans("Image has not been saved."));
        }

        $video = $inputs['video'];
        if (!empty($video)) {
            $video_extension = pathinfo($video['name'], PATHINFO_EXTENSION);
            $video_filename = Boutique::getPathVideo() . DIRECTORY_SEPARATOR . $store->id . '.' . $video_extension;
            if ((int) $video["size"] < 274877906944) {
                if (file_exists($video_filename)) {
                    unlink($video_filename);
                }
                $result = move_uploaded_file($video['tmp_name'], $video_filename);
                $params = array(
                    'part' => '',
                    'postBody' => [
                        'snippet' => [
                            'title' => $store->name,
                            'description' => $store->description,
                            'categoryId' => 22,
                            'thumbnails' => [
                                'default' => [
                                    'url' => $this->context->link->getBoutiqueLogoLink($store->id)
                                ],
                                'standard' => [
                                    'url' => $this->context->link->getBoutiqueImageLink($store->id)
                                ],
                            ]
                        ]
                    ],
                    'data' => file_get_contents($video_filename),
                    'mimeType' => 'application/octet-stream',
                    'uploadType' => 'multipart',
                );
                $reponse = Helpers::uploadMovieGoogleApi($params);
                if (file_exists($video_filename)) {
                    unlink($video_filename);
                }
                var_dump($reponse);
                die;
            } else if ($video["error"]) {
                $this->renderAjaxErrors($this->trans("Une erreure est survenu lors du chargement de la vidéo."));
            } else {
                $this->renderAjaxErrors($this->trans("La taille maximale du fichier est de 274877906944 Bytes."));
            }
            if (!$result) {
                $this->renderAjaxErrors($this->trans("Image has not been saved."));
            }
        }

        $this->datas['message'] = $this->trans("The store has been saved.");
        $this->datas['stores'] = Boutique::getFullStores($id_lang, $customer->id, false);

        $this->renderAjax();
        parent::processPostRequest();
    }
}
