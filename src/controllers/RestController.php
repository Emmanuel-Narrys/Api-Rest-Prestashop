<?php

namespace NarrysTech\Api_Rest\controllers;

use Category;
use NarrysTech\Api_Rest\classes\Helpers;
use NarrysTech\Api_Rest\classes\RESTProductLazyArray;
use PrestaShop\PrestaShop\Adapter\Category\CategoryProductSearchProvider;
use PrestaShop\PrestaShop\Adapter\Entity\Address;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\Group;
use PrestaShop\PrestaShop\Adapter\Entity\GroupReduction;
use PrestaShop\PrestaShop\Adapter\Entity\Language;
use PrestaShop\PrestaShop\Adapter\Entity\Link;
use PrestaShop\PrestaShop\Adapter\Entity\Manufacturer;
use PrestaShop\PrestaShop\Adapter\Entity\ModuleFrontController;
use PrestaShop\PrestaShop\Adapter\Entity\Pack;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\ProductAssembler;
use PrestaShop\PrestaShop\Adapter\Entity\ProductPresenterFactory;
use PrestaShop\PrestaShop\Adapter\Entity\SpecificPrice;
use PrestaShop\PrestaShop\Adapter\Entity\TaxConfiguration;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\Entity\Validate;
use PrestaShop\PrestaShop\Adapter\Entity\WebserviceKey;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Product\ProductExtraContentFinder;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchContext;
use PrestaShop\PrestaShop\Core\Product\Search\ProductSearchQuery;
use PrestaShop\PrestaShop\Core\Product\Search\SortOrder;
use Viaziza\Smalldeals\Classes\ProductStore;

define('PRICE_REDUCTION_TYPE_PERCENT', 'percentage');
class RestController extends ModuleFrontController
{

    /**
     * Fields for this classe
     *
     * @var array
     */
    public $params = [];
    /**
     * Datas to send in ajax
     *
     * @var array
     */
    public $datas = [];
    /**
     * success
     *
     * @var integer
     */
    public $codeSuccess = 200;
    /**
     * Errors method if not found for this route
     *
     * @var integer
     */
    public $codeMethod = 405;
    /**
     * Error internal serveur
     *
     * @var integer
     */
    public $codeServeur = 500;
    /**
     * Page or route if not exists
     *
     * @var integer
     */
    public $codeNotFound = 404;
    /**
     * Errors fields required or type if not correct
     *
     * @var integer
     */
    public $codeErrors = 400;
    /**
     * Error Authenticate
     *
     * @var integer
     */
    public $codeAuthenticate = 401;
    /**
     * Error Authenticate Customer
     *
     * @var integer
     */
    public $codeAuthenticateCustomer = 402;

    /**
     * Product
     *
     * @var Product
     */
    protected $product;

    protected $img1 = 'large';
    protected $img2 = 'medium';
    protected $img3 = '_default';

    public function init()
    {

        header("Content-type: application/json");
        parent::init();

        //Authenticate application with Bearer token
        $this->authenticate();

        //Update current language
        $id_lang = Tools::getValue('id_lang');
        if ($id_lang) {
            $language = new Language((int) $id_lang);
            if (Validate::isLoadedObject($language)) {
                $this->context->language = $language;
            }
        }

        //Check method is submit
        switch ($_SERVER['REQUEST_METHOD']) {
            case 'GET':
                $this->processGetRequest();
                break;
            case 'POST':
                $this->processPostRequest();
                break;
            case 'PUT':
                $this->processPutRequest();
                break;
            case 'DELETE':
                $this->processDeleteRequest();
                break;

            default:
                # code...
                break;
        }
    }

    protected function processGetRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processPostRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processPutRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function processDeleteRequest()
    {
        return $this->methodNotAllowed();
    }

    protected function methodNotAllowed()
    {
        $this->ajaxRender(Helpers::response_json([
            "message" => "Method not allowed"
        ], $this->codeMethod, false));
        die;
    }
    
    /**
     * Undocumented function
     *
     * @param string $type
     * @return boolean
     */
    public function isValideType(string $type, $value): bool
    {
        switch ($type) {
            case 'text':
                return Validate::isString($value);
                break;
            case 'number':
                return Validate::isInt((int) $value) || Validate::isFloat((float) $value);
                break;
            case 'tel':
                return Validate::isPhoneNumber($value);
                break;
            case 'email':
                return Validate::isEmail($value);
                break;
            case 'file':
                return Validate::isFileName($value);
                break;
            case 'password':
                return Validate::isPlaintextPassword($value);
                break;
            case 'url':
                return Validate::isUrl($value);
                break;
            case 'date':
                return Validate::isDate($value);
                break;
            case 'array':
                return is_array($value);
                break;
            default:
                return true;
                break;
        }
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function checkErrorsRequiredOrType(): array
    {
        $inputs = array();

        foreach ($this->params['fields'] as $key => $a) {
            //Get Name
            $name = $a['name'];
            //Get Required
            $required = (bool) $a['required'];
            //Get Type
            $type = $a['type'];
            //Get Value
            $value = Tools::getValue($name);

            if($type == 'array'){
                $value = json_decode($value) ? json_decode($value) : ($value == false || $value == null ? [] : $value);
            }

            //Field is required and null
            if (($required === true) && (($value === false || is_null($value)))) {
                $this->errors["required"][] = $a;
            }
            //Field type if not valide
            if (($required === true) && $this->isValideType($type, $value) == false) {
                $this->errors["type"][] = $a;
            }
            //If field is not required and if not submit
            if ($required === false && (($value === false || is_null($value)))) {
                $value = isset($a["default"]) ? $a["default"] : "null";
            }

            $inputs[$name] = $value;
        }

        //If has errors required
        if (isset($this->errors["required"]) && !empty($this->errors["required"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is required!");
            foreach ($this->errors["required"] as $field) {
                $errors["fields"][] = $field["name"];
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        //If has errors type
        if (isset($this->errors["type"]) && !empty($this->errors["type"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is not correct!");
            foreach ($this->errors["type"] as $field) {
                $errors["fields"][$field["name"]] = Tools::getValue($field["name"]);
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        return $inputs;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function checkFilesErrorsRequiredOrType(): array
    {
        $inputs = array();

        foreach ($this->params['files_fields'] as $key => $a) {
            //Get Name
            $name = $a['name'];
            //Get Required
            $required = (bool) $a['required'];
            //Get Type
            $type = $a['type'];
            //Get Value
            $value = isset($_FILES[$name]) ? $_FILES[$name] : false;
            //Get Extentions
            $extensions = $a['extensions'];

            //Field is required and null
            if (($required === true) && (($value == false || is_null($value) || empty($value)))) {
                $this->errors["required"][] = $a;
            }
            //If field is not required and if not submit
            if ($required === false && (($value == false || is_null($value) || empty($value)))) {
                $value = isset($a["default"]) ? $a["default"] : [];
            }

            $extension = ($value && !empty($value)) ? pathinfo($value['name'], PATHINFO_EXTENSION) : "";
            if(!in_array($extension, $extensions) && ($required === true) && ($value && !empty($value))){
                $this->errors['extensions'][] = $a;
            }

            $inputs[$name] = $value;
        }

        //If has errors required
        if (isset($this->errors["required"]) && !empty($this->errors["required"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is required!");
            foreach ($this->errors["required"] as $field) {
                $errors["fields"][] = $field["name"];
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        //If has errors extensions
        if (isset($this->errors["extensions"]) && !empty($this->errors["extensions"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Extention is not correct!");
            foreach ($this->errors["extensions"] as $field) {
                $errors["fields"][$field["name"]] = isset($_FILES[$field['name']]) ? $_FILES[$field['name']] : false;
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        return $inputs;
    }

    /**
     * Undocumented function
     *
     * @return array
     */
    public function checkProductImagesErrorsRequiredOrType(): array
    {
        $inputs = array();

        foreach ($this->params['images_files_field'] as $key => $a) {
            //Get Name
            $name = $a['name'];
            //Get Required
            $required = (bool) $a['required'];
            //Get Type
            $type = $a['type'];
            //Get Value
            $value = $_FILES;
            $new_value = [];
            foreach ($value as $key => $v) {
                if($key == 'cover'){
                    continue;
                }
                $new_value[$key] = $v;
            }
            $value = $new_value;
            //Get Extentions
            $extensions = $a['extensions'];

            //Field is required and null
            if (($required === true) && (($value == false || is_null($value) || empty($value)))) {
                $this->errors["required"][] = $a;
            }
            //Field type if not valide
            if (($required === true) && $this->isValideType($type, $value) == false) {
                $this->errors["type"][] = $a;
            }
            //If field is not required and if not submit
            if ($required === false && (($value == false || is_null($value) || empty($value)))) {
                $value = isset($a["default"]) ? $a["default"] : [];
            }

            if($value && !empty($value)){
                foreach($value as $key => $v){
                    $extension = pathinfo($v['name'], PATHINFO_EXTENSION);
                    if(!in_array($extension, $extensions) && ($required === true) && ($v && !empty($v))){
                        $this->errors['extensions'][] = $a;
                    }
                }
            }

            $inputs[$name] = $value;
        }

        //If has errors required
        if (isset($this->errors["required"]) && !empty($this->errors["required"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Fields is required!");
            foreach ($this->errors["required"] as $field) {
                $errors["fields"][] = $field["name"];
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        //If has errors extensions
        if (isset($this->errors["extensions"]) && !empty($this->errors["extensions"])) {
            $errors = [];
            $errors["message"] = $this->getTranslator()->trans("Extention is not correct!");
            foreach ($this->errors["extensions"] as $field) {
                $errors["fields"][$field["name"]] = isset($_FILES[$field['name']]) ? $_FILES[$field['name']] : false;
            }
            $this->datas["errors"] = $errors;
            $this->renderAjax($this->codeErrors, false);
        }

        return $inputs;
    }

    public function renderAjax(int $status = 200, bool $success = true)
    {
        $this->ajaxRender(Helpers::response_json($this->datas, $status, $success));
        die;
    }

    public function renderAjaxErrors($message, int $status = null)
    {
        $this->datas = [];
        $this->datas["errors"]["message"] = $message;
        $this->renderAjax($status === null ? $this->codeErrors : $status, false);
    }

    public function authenticate()
    {
        //Check if Bearer Token passing in header
        if (isset($_SERVER['HTTP_AUTHORIZATION']) && preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $matches)) {
            $token = $matches[1];
            if (WebserviceKey::keyExists($token)) { //If Bearer token exists
                if (!WebserviceKey::isKeyActive($token)) { //If Bearer token if active
                    $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is not active");
                    $this->renderAjax($this->codeAuthenticate, false);
                }
            } else {
                $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is not correct");
                $this->renderAjax($this->codeAuthenticate, false);
            }
        } else {
            $this->datas["errors"]["message"] = $this->getTranslator()->trans("Authentication bearer token is empty");
            $this->renderAjax($this->codeAuthenticate, false);
        }
    }

    public function getImage($object, $id_image)
    {
        $retriever = new ImageRetriever($this->context->link);
        return $retriever->getImage($object, $id_image);
    }

    public function formatPrice($price)
    {
        return Tools::displayPrice(
            $price,
            $this->context->currency,
            false,
            $this->context
        );
    }

    public function getImageType($type = 'large')
    {
        if ($type == 'large') {
            return $this->img1 . $this->img3;
        } elseif ($type == 'medium') {
            return $this->img2 . $this->img3;
        } else {
            return $this->img1 . $this->img3;
        }
    }

    /**
     * Get Product details
     *
     * @return array product data
     */
    public function getProduct()
    {
        $product = array();
        $product['id_product'] = $this->product->id;
        $product['id_product_attribute'] = Tools::getValue('id_product_attribute');
        $product['name'] = $this->product->name;
        $product['available_for_order'] = $this->product->available_for_order;
        $product['show_price'] = $this->product->show_price;
        $product['new_products'] = (isset($this->product->new) && $this->product->new == 1) ? "1" : "0";
        $product['on_sale_products'] = $this->product->on_sale;
        $product['quantity'] = $this->product->quantity;
        $product['minimal_quantity'] = $this->product->minimal_quantity;
        if ($this->product->out_of_stock == 1) {
            $product['allow_out_of_stock'] = "1";
        } elseif ($this->product->out_of_stock == 0) {
            $product['allow_out_of_stock'] = "0";
        } elseif ($this->product->out_of_stock == 2) {
            $out_of_stock = Configuration::get('PS_ORDER_OUT_OF_STOCK');
            if ($out_of_stock == 1) {
                $product['allow_out_of_stock'] = "1";
            } else {
                $product['allow_out_of_stock'] = "0";
            }
        }


        $priceDisplay = Product::getTaxCalculationMethod(0); //(int)$this->context->cookie->id_customer
        if (!$priceDisplay || $priceDisplay == 2) {
            $price = $this->product->getPrice(true, false);
            $price_without_reduction = $this->product->getPriceWithoutReduct(false);
        } else {
            $price = $this->product->getPrice(false, false);
            $price_without_reduction = $this->product->getPriceWithoutReduct(true);
        }
        if ($priceDisplay >= 0 && $priceDisplay <= 2) {
            if ($price_without_reduction <= 0 || !$this->product->specificPrice) {
                $product['float_price'] = $price;
                $product['price'] = $this->formatPrice($price);
                $product['discount_price'] = '';
                $product['discount_percentage'] = '';
            } else {
                if (
                    $this->product->specificPrice
                    && $this->product->specificPrice['reduction_type'] == PRICE_REDUCTION_TYPE_PERCENT
                ) {
                    $product['discount_percentage'] = $this->product->specificPrice['reduction'] * 100;
                } elseif (
                    $this->product->specificPrice
                    && $this->product->specificPrice['reduction_type'] == 'amount'
                    && $this->product->specificPrice['reduction'] > 0
                ) {
                    $temp_price = (float)($this->product->specificPrice['reduction'] * 100);
                    $percent = (float)($temp_price / $price_without_reduction);
                    $product['discount_percentage'] = Tools::ps_round($percent);
                    unset($temp_price);
                }
                $product['price'] = $this->formatPrice($price_without_reduction);
                $product['float_price'] = $price_without_reduction;
                $product['discount_price'] = $this->formatPrice($price);
                $product['discount_float_price'] = $price;
            }
        } else {
            $product['price'] = '';
            $product['discount_price'] = '';
            $product['discount_percentage'] = '';
        }

        $product = $this->fillImages($product, $this->context->language);
        /* $temp_images = $this->product->getImages((int)$this->context->language->id);
        $cover = false;
        $images = array();
        foreach ($temp_images as $image) {
            if ($image['cover']) {
                $cover = $image;
            } else {
                $images[] = $image;
            }
        }

        if ($cover) {
            $images = array_merge(array($cover), $images);
        }
        foreach ($images as $image) {
            $product['images'][]['src'] = $this->context->link->getImageLink(
                urlencode($this->product->link_rewrite),
                ($this->product->id . '-' . $image['id_image']),
                $this->getImageType(Tools::getValue('image_type', 'large'))
            );
        }

        //product cover
        $product['cover_image'] = $this->context->link->getImageLink(

            urlencode($this->product->link_rewrite),
            ($this->product->id . '-' . $cover['id_image']),
            $this->getImageType(Tools::getValue('image_type', 'large'))
        ); */


        $options = array();
        $combinations = array();
        $attributes = $this->getProductAttributesGroups();
        if (!empty($attributes['groups'])) {
            $index = 0;
            foreach ($attributes['groups'] as $grp_id => $grp) {
                $options[$index]['id'] = $grp_id;
                $options[$index]['title'] = $grp['name'];
                if ($grp['group_type'] == 'color') {
                    $options[$index]['is_color_option'] = 1;
                } else {
                    $options[$index]['is_color_option'] = 0;
                }
                $item = array();
                foreach ($grp['attributes'] as $key => $group_item) {
                    if ($grp['group_type'] == 'color') {
                        $hex_value = '';
                        if (isset($attributes['colors'][$key]['value'])) {
                            $hex_value = $attributes['colors'][$key]['value'];
                        }
                        $item[] = array(
                            'id' => $key,
                            'value' => $group_item,
                            'hex_value' => $hex_value
                        );
                    } else {
                        $item[] = array(
                            'id' => $key,
                            'value' => $group_item
                        );
                    }
                }
                $options[$index]['items'] = $item;
                $index++;
            }
        }
        if (!empty($attributes['combinations'])) {
            $index = 0;
            foreach ($attributes['combinations'] as $attr_id => $attr) {
                $combinations[$index]['id_product_attribute'] = $attr_id;
                $combinations[$index]['quantity'] = $attr['quantity'];
                $combinations[$index]['price'] = $attr['price'];
                $combinations[$index]['float_price'] = $attr['float_price'];
                $combinations[$index]['minimal_quantity'] = $attr['minimal_quantity'];
                $attribute_list = '';
                foreach ($attr['attributes'] as $attribute_id) {
                    $attribute_list .= (int)$attribute_id . '_';
                }
                $attribute_list = rtrim($attribute_list, '_');
                $combinations[$index]['combination_code'] = $attribute_list;
                $index++;
            }
        }
        $product['combinations'] = $combinations;
        $product['options'] = $options;

        $product['description'] = preg_replace('/<iframe.*?\/iframe>/i', '', $this->product->description);
        $product['description_short'] = preg_replace('/<iframe.*?\/iframe>/i', '', $this->product->description_short);

        $product['reference'] = $this->product->reference;
        $product['category_name'] = $this->product->category;
        $product['manufacturer_name'] = $this->product->manufacturer_name;

        /*end:changes made by aayushi on 1 DEC 2018 to add Short Description on product page*/
        $product_info = array();
        if ($this->product->id_manufacturer) {
            $product_info[] = array(
                'name' => $this->l('Brand'),
                'value' => Manufacturer::getNameById($this->product->id_manufacturer)
            );
        }

        $product_info[] = array(
            'name' => $this->l('SKU'),
            'value' => $this->product->reference
        );
        $product_info[] = array(
            'name' => $this->l('Condition'),
            'value' => Tools::ucfirst($this->product->condition)
        );

        $features = $this->product->getFrontFeatures($this->context->language->id);
        if (!empty($features)) {
            foreach ($features as $f) {
                $product_info[] = array('name' => $f['name'], 'value' => $f['value']);
            }
        }

        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->getTranslator()
        );

        $product['product_info'] = $product_info;
        $product['accessories'] = $this->getProductAccessories($presenter);
        $product['customization_fields'] = $this->getCustomizationFields();
        $product['pack_products'] = $this->getPackProducts($presenter);
        $product['seller_info'] = array();

        //Add seller Information if Marketplace is installed and feature is enable
        $product['seller_info'] = array();

        $product['product_attachments_array'] = $this->getProductAttachmentURLs($this->product->id);

        $link = new Link();
        $url = $link->getProductLink($product);
        $product['product_url'] = $url;

        return $product;
    }

    public function getFullProduct(int $id_product, int $id_lang = null, ProductStore $productStore = null){

        $this->product = new Product($id_product, true, $id_lang);

        $product = $this->getTemplateVarProduct($productStore);
        if(empty($product)){
            return $product;
        }

        $product['groups'] = $this->assignAttributesGroups($product);

        //Get products accessory with this product
        $product['accessories'] = $this->product->getAccessories($this->context->language->id);
        $retriever = new ImageRetriever(
            $this->context->link
        );
        $settings = $this->getProductPresentationSettings();
        foreach ($product['accessories'] as $key => $p) {
            $populated_product = (new ProductAssembler($this->context))
                ->assembleProduct($p);

            $lazy_product = new RESTProductLazyArray(
                $settings,
                $populated_product,
                $this->context->language,
                new PriceFormatter(),
                $retriever,
                $this->context->getTranslator(),
                $productStore
            );

            $product['accessories'][$key] = $lazy_product->getProduct();
        }

        return $product;
    }

    private function fillImages(
        array $product,
        Language $language
    ) {
        // Get all product images, including potential cover
        $productImages = (new ImageRetriever(
            $this->context->link
        ))->getAllProductImages(
            $product,
            $language
        );

        // Get filtered product images matching the specified id_product_attribute
        if (Tools::getValue('with_all_images')) {
            $product['images'] = $this->filterImagesForCombination($productImages, $product['id_product_attribute']);

            // Get default image for selected combination (used for product page, cart details, ...)
            $product['default_image'] = reset($product['images']);
            foreach ($product['images'] as $image) {
                // If one of the image is a cover it is used as such
                if (isset($image['cover']) && null !== $image['cover']) {
                    $product['default_image'] = $image;

                    break;
                }
            }
        } else {
            $images = $this->filterImagesForCombination($productImages, $product['id_product_attribute']);

            // Get default image for selected combination (used for product page, cart details, ...)
            $tmp = reset($images);
            $product['default_image'] = $tmp['bySize'][Tools::getValue('image_size', "home_default")];
            foreach ($images as $image) {
                // If one of the image is a cover it is used as such
                if (isset($image['cover']) && null !== $image['cover']) {
                    $product['default_image'] = $image['bySize'][Tools::getValue('image_size', "home_default")];

                    break;
                }
            }
        }

        // Get generic product image, used for product listing
        if (isset($product['cover_image_id'])) {
            // First try to find cover in product images
            foreach ($productImages as $productImage) {
                if ($productImage['id_image'] == $product['cover_image_id']) {
                    if (Tools::getValue('with_all_images')) {
                        $product['cover'] = $productImage;
                    } else {
                        $product['cover'] = $productImage['bySize'][Tools::getValue('image_size', "home_default")];
                    }
                    break;
                }
            }

            // If the cover is not associated to the product images it is fetched manually
            if (!isset($product['cover'])) {
                $coverImage = $this->imageRetriever->getImage(new Product($product['id_product'], false, $language->getId()), $product['cover_image_id']);
                $product['cover'] = array_merge($coverImage, [
                    'legend' => $coverImage['legend'],
                ]);
            }
        }

        // If no cover fallback on default image
        if (!isset($product['cover'])) {
            $product['cover'] = $product['default_image'];
        }

        return $product;
    }

    /**
     * @param array $images
     * @param int $productAttributeId
     *
     * @return array
     */
    private function filterImagesForCombination(array $images, int $productAttributeId)
    {
        $filteredImages = [];

        foreach ($images as $image) {
            if (in_array($productAttributeId, $image['associatedVariants'])) {
                $filteredImages[] = $image;
            }
        }

        return (0 === count($filteredImages)) ? $images : $filteredImages;
    }

    /**
     * Get Virtual product attchements URLS
     *
     * @param int $id_product product id
     * @return array product attachment data
     */
    public function getProductAttachmentURLs($id_product)
    {
        $final_attachment_data = array();
        $attachments = Product::getAttachmentsStatic((int)$this->context->language->id, $id_product);
        $count = 0;
        foreach ($attachments as $attachment) {
            $final_attachment_data[$count]['download_link'] = $this->context->link->getPageLink('attachment', true, null, "id_attachment=" . $attachment['id_attachment']);
            $final_attachment_data[$count]['file_size'] = Tools::formatBytes($attachment['file_size'], 2);
            $final_attachment_data[$count]['description'] = $attachment['description'];
            $final_attachment_data[$count]['file_name'] = $attachment['file_name'];
            $final_attachment_data[$count]['mime'] = $attachment['mime'];
            $final_attachment_data[$count]['display_name'] = $attachment['name'];
            $count++;
        }
        return $final_attachment_data;
    }

    /**
     * Get details of product attributes groups
     *
     * @return array product attribute group data
     */
    public function getProductAttributesGroups()
    {
        $colors = array();
        $groups = array();
        $combinations = array();

        $attributes_groups = $this->product->getAttributesGroups($this->context->language->id);

        if (is_array($attributes_groups) && $attributes_groups) {
            foreach ($attributes_groups as $row) {
                // Color management
                if (
                    isset($row['is_color_group'])
                    && $row['is_color_group']
                    && (isset($row['attribute_color']) && $row['attribute_color'])
                    || (file_exists(_PS_COL_IMG_DIR_ . $row['id_attribute'] . '.jpg'))
                ) {
                    $colors[$row['id_attribute']]['value'] = $row['attribute_color'];
                    $colors[$row['id_attribute']]['name'] = $row['attribute_name'];
                    if (!isset($colors[$row['id_attribute']]['attributes_quantity'])) {
                        $colors[$row['id_attribute']]['attributes_quantity'] = 0;
                    }
                    $colors[$row['id_attribute']]['attributes_quantity'] += (int)$row['quantity'];
                }
                if (!isset($groups[$row['id_attribute_group']])) {
                    $groups[$row['id_attribute_group']] = array(
                        'group_name' => $row['group_name'],
                        'name' => $row['public_group_name'],
                        'group_type' => $row['group_type'],
                        'default' => -1,
                    );
                }

                $attr_g = $row['id_attribute_group'];
                $groups[$attr_g]['attributes'][$row['id_attribute']] = $row['attribute_name'];
                if ($row['default_on'] && $groups[$row['id_attribute_group']]['default'] == -1) {
                    $groups[$row['id_attribute_group']]['default'] = (int)$row['id_attribute'];
                }
                if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']])) {
                    $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
                }
                $r_attr = $row['id_attribute_group'];
                $groups[$r_attr]['attributes_quantity'][$row['id_attribute']] += (int)$row['quantity'];

                $combinations[$row['id_product_attribute']]['attributes'][] = (int)$row['id_attribute'];

                //calculate full price for combination
                $priceDisplay = Product::getTaxCalculationMethod(0); //(int)$this->context->cookie->id_customer
                if (!$priceDisplay || $priceDisplay == 2) {
                    $combination_price = $this->product->getPrice(true, $row['id_product_attribute']);
                } else {
                    $combination_price = $this->product->getPrice(false, $row['id_product_attribute']);
                }
                $combinations[$row['id_product_attribute']]['price'] = $this->formatPrice($combination_price);
                $combinations[$row['id_product_attribute']]['float_price'] = $combination_price;
                $combinations[$row['id_product_attribute']]['quantity'] = (int)$row['quantity'];
                $combinations[$row['id_product_attribute']]['minimal_quantity'] = (int)$row['minimal_quantity'];
            }

            // wash attributes list (if some attributes are unavailables and if allowed to wash it)
            if (
                !Product::isAvailableWhenOutOfStock($this->product->out_of_stock)
                && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0
            ) {
                foreach ($groups as &$group) {
                    foreach ($group['attributes_quantity'] as $key => &$quantity) {
                        if ($quantity <= 0) {
                            unset($group['attributes'][$key]);
                        }
                    }
                }

                foreach ($colors as $key => $color) {
                    if ($color['attributes_quantity'] <= 0) {
                        unset($colors[$key]);
                    }
                }
            }
            foreach ($combinations as $id_product_attribute => $comb) {
                $attribute_list = '';
                foreach ($comb['attributes'] as $id_attribute) {
                    $attribute_list .= '\'' . (int)$id_attribute . '\',';
                }
                $attribute_list = rtrim($attribute_list, ',');
                $combinations[$id_product_attribute]['list'] = $attribute_list;
            }
        }

        return array(
            'groups' => $groups,
            'colors' => (count($colors)) ? $colors : false,
            'combinations' => $combinations
        );
    }

    /**
     * Get details of accessories products
     *
     * @return array product accessories information
     */
    public function getProductAccessories($presenter)
    {
        $accessories = $this->product->getAccessories($this->context->language->id);

        if (is_array($accessories)) {
            foreach ($accessories as &$accessory) {
                $accessory = $presenter->present(
                    $this->getProductPresentationSettings(),
                    Product::getProductProperties($this->context->language->id, $accessory, $this->context),
                    $this->context->language
                );
            }
            unset($accessory);
        }

        return $accessories;
    }

    /**
     * Get details of customzable fields of customized product
     *
     * @return array product customized data
     */
    public function getCustomizationFields()
    {
        $customization_fields = array();
        $customization_data = $this->product->getCustomizationFields($this->context->language->id);
        $is_customizable = "0";

        if ($customization_data && is_array($customization_data)) {
            $index = 0;
            foreach ($customization_data as $data) {
                if ($data['type'] == 1) {
                    $is_customizable = "1";
                    $customization_fields[$index] = array(
                        'id_customization_field' => $data['id_customization_field'],
                        'required' => $data['required'],
                        'title' => $data['name'],
                        'type' => 'text'
                    );
                    $index++;
                } elseif ($data['type'] == 0 && $data['required'] == 1) {
                    $this->has_file_field = 1;
                }
            }
        }

        return array('is_customizable' => $is_customizable, 'customizable_items' => $customization_fields);
    }

    /**
     * Get details of pack products
     *
     * @return array pick items information
     */
    public function getPackProducts($presenter)
    {
        $pack_items = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : [];
        $assembler = new ProductAssembler($this->context);

        $presentedPackItems = [];
        foreach ($pack_items as $item) {
            $presentedPackItems[] = $presenter->present(
                $this->getProductPresentationSettings(),
                $assembler->assembleProduct($item),
                $this->context->language
            );
        }

        return $presentedPackItems;
    }

    public function getTemplateVarProduct(ProductStore $productStore = null)
    {
        $factory = new ProductPresenterFactory($this->context, new TaxConfiguration());
        $productSettings = $factory->getPresentationSettings();
        // Hook displayProductExtraContent
        $extraContentFinder = new ProductExtraContentFinder();
        $objectPresenter = new ObjectPresenter();

        $product = $objectPresenter->present($this->product);
        $product['id_product'] = (int)$this->product->id;
        $product['out_of_stock'] = (int)$this->product->out_of_stock;
        $product['new'] = (int)$this->product->new;
        $product['id_product_attribute'] = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        //$product['minimal_quantity'] = $this->getProductMinimalQuantity($product);
        //$product['quantity_wanted'] = $this->getRequiredQuantity($product);
        $product['extraContent'] = $extraContentFinder->addParams(array('product' => $this->product))->present();
        $product['ecotax'] = Tools::convertPrice((float)$product['ecotax'], $this->context->currency, true, $this->context);

        $product_full = Product::getProductProperties($this->context->language->id, $product, $this->context);

        $product_full = $this->addProductCustomizationData($product_full);

        $product_full['show_quantities'] = (bool)(Configuration::get('PS_DISPLAY_QTIES')
            && Configuration::get('PS_STOCK_MANAGEMENT')
            && $this->product->quantity > 0
            && $this->product->available_for_order
            && !Configuration::isCatalogMode()
        );

        $id_product_attribute = $this->getIdProductAttributeByGroupOrRequestOrDefault();
        $product_price = $this->product->getPrice(Product::$_taxCalculationMethod == PS_TAX_INC, $id_product_attribute);

        $id_customer = (isset($this->context->customer) ? (int)$this->context->customer->id : 0);
        $id_group = (int)Group::getCurrent()->id;
        $id_country = $id_customer ? (int)Customer::getCurrentCountry($id_customer) : (int)Tools::getCountry();
        $id_currency = (int)$this->context->cookie->id_currency;
        $id_product = (int)$this->product->id;
        $id_shop = $this->context->shop->id;

        $quantity_discounts = SpecificPrice::getQuantityDiscounts($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_product_attribute, false, (int)$this->context->customer->id);

        $tax = (float)$this->product->getTaxesRate(new Address((int)$this->context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));

        $this->quantity_discounts = $this->formatQuantityDiscounts($quantity_discounts, $product_price, (float)$tax, $this->product->ecotax);

        $product_full['quantity_label'] = ($this->product->quantity > 1) ? $this->trans('Items', array(), 'Shop.Theme.Catalog') : $this->trans('Item', array(), 'Shop.Theme.Catalog');
        $product_full['quantity_discounts'] = $this->quantity_discounts;

        if ($product_full['unit_price_ratio'] > 0) {
            $unitPrice = ($productSettings->include_taxes) ? $product_full['price'] : $product_full['price_tax_exc'];
            $product_full['unit_price'] = $unitPrice / $product_full['unit_price_ratio'];
        }

        $group_reduction = GroupReduction::getValueForProduct($this->product->id, (int)Group::getCurrent()->id);
        if ($group_reduction === false) {
            $group_reduction = Group::getReduction((int)$this->context->cookie->id_customer) / 100;
        }
        $product_full['customer_group_discount'] = $group_reduction;
        $presenter = $factory->getPresenter();

        $populated_product = (new ProductAssembler($this->context))
            ->assembleProduct($product_full);

        $lazy_product = new RESTProductLazyArray(
            $productSettings,
            $populated_product,
            $this->context->language,
            new PriceFormatter(),
            new ImageRetriever($this->context->link),
            $this->context->getTranslator(),
            $productStore
        );
        return $lazy_product->getProduct();

        /* return $presenter->present(
            $productSettings,
            $product_full,
            $this->context->language
        ); */
    }


    /**
     * Get Virtual product attchements 
     *
     * @param int $id_product product id
     * @return array product attachment data
     */
    public function getProductAttachment($id_product)
    {
        $final_attachment_data = array();
        $attachments = Product::getAttachmentsStatic((int)$this->context->language->id, $id_product);
        $count = 0;
        foreach ($attachments as $attachment) {
            $final_attachment_data[$count]['download_link'] = $this->context->link->getPageLink('attachment', true, null, "id_attachment=" . $attachment['id_attachment']);
            $final_attachment_data[$count]['file_size'] = Tools::formatBytes($attachment['file_size'], 2);
            $final_attachment_data[$count]['description'] = $attachment['description'];
            $final_attachment_data[$count]['file_name'] = $attachment['file_name'];
            $final_attachment_data[$count]['mime'] = $attachment['mime'];
            $final_attachment_data[$count]['display_name'] = $attachment['name'];
            $count++;
        }
        return $attachments;
    }

    public function getIdProductAttributeByGroupOrRequestOrDefault()
    {
        $idProductAttribute = $this->getIdProductAttributeByGroup();
        if (null === $idProductAttribute) {
            $idProductAttribute = (int)Tools::getValue('id_product_attribute');
        }

        if (0 === $idProductAttribute) {
            $idProductAttribute = (int)Product::getDefaultAttribute($this->product->id);
        }

        return $this->tryToGetAvailableIdProductAttribute($idProductAttribute);
    }

    public function getIdProductAttributeByGroup()
    {
        $groups = Tools::getValue('group');
        if (empty($groups)) {
            return null;
        }

        return (int) Product::getIdProductAttributeByIdAttributes(
            $this->product->id,
            $groups,
            true
        );
    }

    protected function addProductCustomizationData(array $product_full)
    {
        if ($product_full['customizable']) {
            $customizationData = array(
                'fields' => array(),
            );

            $customized_data = array();

            $already_customized = $this->context->cart->getProductCustomization(
                $product_full['id_product'],
                null,
                true
            );

            $id_customization = 0;
            foreach ($already_customized as $customization) {
                $id_customization = $customization['id_customization'];
                $customized_data[$customization['index']] = $customization;
            }

            $customization_fields = $this->product->getCustomizationFields($this->context->language->id);
            if (is_array($customization_fields)) {
                foreach ($customization_fields as $customization_field) {
                    // 'id_customization_field' maps to what is called 'index'
                    // in what Product::getProductCustomization() returns
                    $key = $customization_field['id_customization_field'];

                    $field = array();
                    $field['label'] = $customization_field['name'];
                    $field['id_customization_field'] = $customization_field['id_customization_field'];
                    $field['required'] = $customization_field['required'];

                    switch ($customization_field['type']) {
                        case Product::CUSTOMIZE_FILE:
                            $field['type'] = 'image';
                            $field['image'] = null;
                            $field['input_name'] = 'file' . $customization_field['id_customization_field'];

                            break;
                        case Product::CUSTOMIZE_TEXTFIELD:
                            $field['type'] = 'text';
                            $field['text'] = '';
                            $field['input_name'] = 'textField' . $customization_field['id_customization_field'];

                            break;
                        default:
                            $field['type'] = null;
                    }

                    if (array_key_exists($key, $customized_data)) {
                        $data = $customized_data[$key];
                        $field['is_customized'] = true;
                        switch ($customization_field['type']) {
                            case Product::CUSTOMIZE_FILE:
                                $imageRetriever = new ImageRetriever($this->context->link);
                                $field['image'] = $imageRetriever->getCustomizationImage(
                                    $data['value']
                                );
                                $field['remove_image_url'] = $this->context->link->getProductDeletePictureLink(
                                    $product_full,
                                    $customization_field['id_customization_field']
                                );

                                break;
                            case Product::CUSTOMIZE_TEXTFIELD:
                                $field['text'] = $data['value'];

                                break;
                        }
                    } else {
                        $field['is_customized'] = false;
                    }

                    $customizationData['fields'][] = $field;
                }
            }
            $product_full['customizations'] = $customizationData;
            $product_full['id_customization'] = $id_customization;
            $product_full['is_customizable'] = true;
        } else {
            $product_full['customizations'] = array(
                'fields' => array(),
            );
            $product_full['id_customization'] = 0;
            $product_full['is_customizable'] = false;
        }

        return $product_full;
    }

    public function tryToGetAvailableIdProductAttribute($checkedIdProductAttribute)
    {
        if (!Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) {
            $availableProductAttributes = array_filter(
                $this->product->getAttributeCombinations(),
                function ($elem) {
                    return $elem['quantity'] > 0;
                }
            );

            $availableProductAttribute = array_filter(
                $availableProductAttributes,
                function ($elem) use ($checkedIdProductAttribute) {
                    return $elem['id_product_attribute'] == $checkedIdProductAttribute;
                }
            );

            if (empty($availableProductAttribute) && count($availableProductAttributes)) {
                return (int)array_shift($availableProductAttributes)['id_product_attribute'];
            }
        }

        return $checkedIdProductAttribute;
    }

    protected function formatQuantityDiscounts($specific_prices, $price, $tax_rate, $ecotax_amount)
    {
        $priceFormatter = new PriceFormatter();

        foreach ($specific_prices as $key => &$row) {
            $row['quantity'] = &$row['from_quantity'];
            if ($row['price'] >= 0) {
                // The price may be directly set

                /** @var float $currentPriceDefaultCurrency current price with taxes in default currency */
                $currentPriceDefaultCurrency = (!$row['reduction_tax'] ? $row['price'] : $row['price'] * (1 + $tax_rate / 100)) + (float)$ecotax_amount;
                // Since this price is set in default currency,
                // we need to convert it into current currency
                $row['id_currency'];
                $currentPriceCurrentCurrency = Tools::convertPrice($currentPriceDefaultCurrency, $this->context->currency, true, $this->context);

                if ($row['reduction_type'] == 'amount') {
                    $currentPriceCurrentCurrency -= ($row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100));
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100);
                } else {
                    $currentPriceCurrentCurrency *= 1 - $row['reduction'];
                }
                $row['real_value'] = $price > 0 ? $price - $currentPriceCurrentCurrency : $currentPriceCurrentCurrency;
                $discountPrice = $price - $row['real_value'];

                if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                    if ($row['reduction_tax'] == 0 && !$row['price']) {
                        $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                    } else {
                        $row['discount'] = $priceFormatter->format($price - $row['real_value']);
                    }
                } else {
                    $row['discount'] = $priceFormatter->format($row['real_value']);
                }
            } else {
                if ($row['reduction_type'] == 'amount') {
                    if (Product::$_taxCalculationMethod == PS_TAX_INC) {
                        $row['real_value'] = $row['reduction_tax'] == 1 ? $row['reduction'] : $row['reduction'] * (1 + $tax_rate / 100);
                    } else {
                        $row['real_value'] = $row['reduction_tax'] == 0 ? $row['reduction'] : $row['reduction'] / (1 + $tax_rate / 100);
                    }
                    $row['reduction_with_tax'] = $row['reduction_tax'] ? $row['reduction'] : $row['reduction'] + ($row['reduction'] * $tax_rate) / 100;
                    $discountPrice = $price - $row['real_value'];
                    if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                        if ($row['reduction_tax'] == 0 && !$row['price']) {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                        } else {
                            $row['discount'] = $priceFormatter->format($price - $row['real_value']);
                        }
                    } else {
                        $row['discount'] = $priceFormatter->format($row['real_value']);
                    }
                } else {
                    $row['real_value'] = $row['reduction'] * 100;
                    $discountPrice = $price - $price * $row['reduction'];
                    if (Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')) {
                        if ($row['reduction_tax'] == 0) {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction_with_tax']));
                        } else {
                            $row['discount'] = $priceFormatter->format($price - ($price * $row['reduction']));
                        }
                    } else {
                        $row['discount'] = $row['real_value'] . '%';
                    }
                }
            }

            $row['save'] = $priceFormatter->format((($price * $row['quantity']) - ($discountPrice * $row['quantity'])));
            $row['nextQuantity'] = (isset($specific_prices[$key + 1]) ? (int)$specific_prices[$key + 1]['from_quantity'] : -1);
        }

        return $specific_prices;
    }

    protected function getProductPresentationSettings()
    {
        $settings = new ProductPresentationSettings();

        $settings->catalog_mode = Configuration::isCatalogMode();
        $settings->catalog_mode_with_prices = (int) Configuration::get('PS_CATALOG_MODE_WITH_PRICES');
        $settings->include_taxes = (new TaxConfiguration)->includeTaxes();
        $settings->allow_add_variant_to_cart_from_listing = (int) Configuration::get('PS_ATTRIBUTE_CATEGORY_DISPLAY');
        $settings->stock_management_enabled = Configuration::get('PS_STOCK_MANAGEMENT');
        $settings->showPrices = Configuration::showPrices();
        $settings->lastRemainingItems = Configuration::get('PS_LAST_QTIES');

        return $settings;
    }

    protected function assignAttributesGroups($product_for_template = null)
    {
        $colors = [];
        $groups = [];
        $this->combinations = [];

        /** @todo (RM) should only get groups and not all declination ? */
        $attributes_groups = $this->product->getAttributesGroups($this->context->language->id);
        if (is_array($attributes_groups) && $attributes_groups) {
            $combination_images = $this->product->getCombinationImages($this->context->language->id);
            $combination_prices_set = [];
            foreach ($attributes_groups as $k => $row) {
                // Color management
                if (isset($row['is_color_group']) && $row['is_color_group'] && (isset($row['attribute_color']) && $row['attribute_color']) || (file_exists(_PS_COL_IMG_DIR_ . $row['id_attribute'] . '.jpg'))) {
                    $colors[$row['id_attribute']]['value'] = $row['attribute_color'];
                    $colors[$row['id_attribute']]['name'] = $row['attribute_name'];
                    if (!isset($colors[$row['id_attribute']]['attributes_quantity'])) {
                        $colors[$row['id_attribute']]['attributes_quantity'] = 0;
                    }
                    $colors[$row['id_attribute']]['attributes_quantity'] += (int) $row['quantity'];
                }
                if (!isset($groups[$row['id_attribute_group']])) {
                    $groups[$row['id_attribute_group']] = [
                        'group_name' => $row['group_name'],
                        'name' => $row['public_group_name'],
                        'group_type' => $row['group_type'],
                        'default' => -1,
                    ];
                }

                $groups[$row['id_attribute_group']]['attributes'][$row['id_attribute']] = [
                    'name' => $row['attribute_name'],
                    'html_color_code' => $row['attribute_color'],
                    'texture' => (@filemtime(_PS_COL_IMG_DIR_ . $row['id_attribute'] . '.jpg')) ? _THEME_COL_DIR_ . $row['id_attribute'] . '.jpg' : '',
                    'selected' => (isset($product_for_template['attributes'][$row['id_attribute_group']]['id_attribute']) && $product_for_template['attributes'][$row['id_attribute_group']]['id_attribute'] == $row['id_attribute']) ? true : false,
                ];

                //$product.attributes.$id_attribute_group.id_attribute eq $id_attribute
                if ($row['default_on'] && $groups[$row['id_attribute_group']]['default'] == -1) {
                    $groups[$row['id_attribute_group']]['default'] = (int) $row['id_attribute'];
                }
                if (!isset($groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']])) {
                    $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] = 0;
                }
                $groups[$row['id_attribute_group']]['attributes_quantity'][$row['id_attribute']] += (int) $row['quantity'];

                $this->combinations[$row['id_product_attribute']]['attributes_values'][$row['id_attribute_group']] = $row['attribute_name'];
                $this->combinations[$row['id_product_attribute']]['attributes'][] = (int) $row['id_attribute'];
                $this->combinations[$row['id_product_attribute']]['price'] = (float) $row['price'];

                // Call getPriceStatic in order to set $combination_specific_price
                if (!isset($combination_prices_set[(int) $row['id_product_attribute']])) {
                    $combination_specific_price = null;
                    Product::getPriceStatic((int) $this->product->id, false, $row['id_product_attribute'], 6, null, false, true, 1, false, null, null, null, $combination_specific_price);
                    $combination_prices_set[(int) $row['id_product_attribute']] = true;
                    $this->combinations[$row['id_product_attribute']]['specific_price'] = $combination_specific_price;
                }
                $this->combinations[$row['id_product_attribute']]['ecotax'] = (float) $row['ecotax'];
                $this->combinations[$row['id_product_attribute']]['weight'] = (float) $row['weight'];
                $this->combinations[$row['id_product_attribute']]['quantity'] = (int) $row['quantity'];
                $this->combinations[$row['id_product_attribute']]['reference'] = $row['reference'];
                $this->combinations[$row['id_product_attribute']]['unit_impact'] = $row['unit_price_impact'];
                $this->combinations[$row['id_product_attribute']]['minimal_quantity'] = $row['minimal_quantity'];
                if ($row['available_date'] != '0000-00-00' && Validate::isDate($row['available_date'])) {
                    $this->combinations[$row['id_product_attribute']]['available_date'] = $row['available_date'];
                    $this->combinations[$row['id_product_attribute']]['date_formatted'] = Tools::displayDate($row['available_date']);
                } else {
                    $this->combinations[$row['id_product_attribute']]['available_date'] = $this->combinations[$row['id_product_attribute']]['date_formatted'] = '';
                }

                if (!isset($combination_images[$row['id_product_attribute']][0]['id_image'])) {
                    $this->combinations[$row['id_product_attribute']]['id_image'] = -1;
                } else {
                    $this->combinations[$row['id_product_attribute']]['id_image'] = $id_image = (int) $combination_images[$row['id_product_attribute']][0]['id_image'];
                    /*if ($row['default_on']) {
                        foreach ($this->context->smarty->tpl_vars['product']->value['images'] as $image) {
                            if ($image['cover'] == 1) {
                                $current_cover = $image;
                            }
                        }
                        if (!isset($current_cover)) {
                            $current_cover = array_values($this->context->smarty->tpl_vars['product']->value['images'])[0];
                        }

                        if (is_array($combination_images[$row['id_product_attribute']])) {
                            foreach ($combination_images[$row['id_product_attribute']] as $tmp) {
                                if ($tmp['id_image'] == $current_cover['id_image']) {
                                    $this->combinations[$row['id_product_attribute']]['id_image'] = $id_image = (int) $tmp['id_image'];

                                    break;
                                }
                            }
                        }

                        if ($id_image > 0) {
                            if (isset($this->context->smarty->tpl_vars['images']->value)) {
                                $product_images = $this->context->smarty->tpl_vars['images']->value;
                            }
                            if (isset($product_images) && is_array($product_images) && isset($product_images[$id_image])) {
                                $product_images[$id_image]['cover'] = 1;
                                $this->context->smarty->assign('mainImage', $product_images[$id_image]);
                                if (count($product_images)) {
                                    $this->context->smarty->assign('images', $product_images);
                                }
                            }

                            $cover = $current_cover;

                            if (isset($cover) && is_array($cover) && isset($product_images) && is_array($product_images)) {
                                $product_images[$cover['id_image']]['cover'] = 0;
                                if (isset($product_images[$id_image])) {
                                    $cover = $product_images[$id_image];
                                }
                                $cover['id_image'] = (Configuration::get('PS_LEGACY_IMAGES') ? ($this->product->id . '-' . $id_image) : (int) $id_image);
                                $cover['id_image_only'] = (int) $id_image;
                                $this->context->smarty->assign('cover', $cover);
                            }
                        }
                    }*/
                }
            }

            // wash attributes list depending on available attributes depending on selected preceding attributes
            $current_selected_attributes = [];
            $count = 0;
            foreach ($groups as &$group) {
                ++$count;
                if ($count > 1) {
                    //find attributes of current group, having a possible combination with current selected
                    $id_product_attributes = [0];
                    $query = 'SELECT pac.`id_product_attribute`
                        FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac
                        INNER JOIN `' . _DB_PREFIX_ . 'product_attribute` pa ON pa.id_product_attribute = pac.id_product_attribute
                        WHERE id_product = ' . $this->product->id . ' AND id_attribute IN (' . implode(',', array_map('intval', $current_selected_attributes)) . ')
                        GROUP BY id_product_attribute
                        HAVING COUNT(id_product) = ' . count($current_selected_attributes);
                    if ($results = Db::getInstance()->executeS($query)) {
                        foreach ($results as $row) {
                            $id_product_attributes[] = $row['id_product_attribute'];
                        }
                    }
                    $id_attributes = Db::getInstance()->executeS('SELECT `id_attribute` FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac2
                        WHERE `id_product_attribute` IN (' . implode(',', array_map('intval', $id_product_attributes)) . ')
                        AND id_attribute NOT IN (' . implode(',', array_map('intval', $current_selected_attributes)) . ')');
                    foreach ($id_attributes as $k => $row) {
                        $id_attributes[$k] = (int) $row['id_attribute'];
                    }
                    foreach ($group['attributes'] as $key => $attribute) {
                        if (!in_array((int) $key, $id_attributes)) {
                            unset(
                                $group['attributes'][$key],
                                $group['attributes_quantity'][$key]
                            );
                        }
                    }
                }
                //find selected attribute or first of group
                $index = 0;
                $current_selected_attribute = 0;
                foreach ($group['attributes'] as $key => $attribute) {
                    if ($index === 0) {
                        $current_selected_attribute = $key;
                    }
                    if ($attribute['selected']) {
                        $current_selected_attribute = $key;

                        break;
                    }
                }
                if ($current_selected_attribute > 0) {
                    $current_selected_attributes[] = $current_selected_attribute;
                }
            }

            // wash attributes list (if some attributes are unavailables and if allowed to wash it)
            if (!Product::isAvailableWhenOutOfStock($this->product->out_of_stock) && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0) {
                foreach ($groups as &$group) {
                    foreach ($group['attributes_quantity'] as $key => &$quantity) {
                        if ($quantity <= 0) {
                            unset($group['attributes'][$key]);
                        }
                    }
                }

                foreach ($colors as $key => $color) {
                    if ($color['attributes_quantity'] <= 0) {
                        unset($colors[$key]);
                    }
                }
            }
            foreach ($this->combinations as $id_product_attribute => $comb) {
                $attribute_list = '';
                foreach ($comb['attributes'] as $id_attribute) {
                    $attribute_list .= '\'' . (int) $id_attribute . '\',';
                }
                $attribute_list = rtrim($attribute_list, ',');
                $this->combinations[$id_product_attribute]['list'] = $attribute_list;
            }

            return $groups;
        } else {
            return [];
        }
    }

    public function getFeaturedProducts()
    {
        $category = new Category((int) Configuration::get('HOME_FEATURED_CAT'));

        $searchProvider = new CategoryProductSearchProvider(
            $this->context->getTranslator(),
            $category
        );

        $context = new ProductSearchContext($this->context);

        $query = new ProductSearchQuery();
        $nProducts = Configuration::get('HOME_FEATURED_NBR');
        if ($nProducts < 0) {
            $nProducts = 12;
        }

        $query
            ->setResultsPerPage($nProducts)
            ->setPage(1);

        if (Configuration::get('HOME_FEATURED_RANDOMIZE')) {
            $query->setSortOrder(SortOrder::random());
        } else {
            $query->setSortOrder(new SortOrder('product', 'position', 'asc'));
        }

        $result = $searchProvider->runQuery(
            $context,
            $query
        );

        $products_for_template = [];
        $settings = $this->getProductPresentationSettings();
        $retriever = new ImageRetriever(
            $this->context->link
        );

        foreach ($result->getProducts() as $rawProduct) {
            $populated_product = (new ProductAssembler($this->context))
                ->assembleProduct($rawProduct);
            $lazy_product = new RESTProductLazyArray(
                $settings,
                $populated_product,
                $this->context->language,
                new PriceFormatter(),
                $retriever,
                $this->context->getTranslator()
            );

            $products_for_template[] = $lazy_product->getProduct();
        }

        return $products_for_template;
    }

    public function getConfigFieldsValues()
    {
        return array(
            'HOME_FEATURED_NBR' => Tools::getValue('HOME_FEATURED_NBR', (int) Configuration::get('HOME_FEATURED_NBR')),
            'HOME_FEATURED_CAT' => Tools::getValue('HOME_FEATURED_CAT', (int) Configuration::get('HOME_FEATURED_CAT')),
            'HOME_FEATURED_RANDOMIZE' => Tools::getValue('HOME_FEATURED_RANDOMIZE', (bool) Configuration::get('HOME_FEATURED_RANDOMIZE')),
        );
    }

    protected function getTemplateVarCategory(Category $category)
    {
        $category_array = $this->objectPresenter->present($category);
        $category_array['image'] = $this->getImage(
            $category,
            $category->id_image
        );

        $category_array['number_of_ads'] = Helpers::getNbProductsToCategory($category->id);


        $category_array['subcategories'] = $this->getTemplateVarSubCategories($category);

        return $category_array;
    }

    protected function getTemplateVarSubCategories(Category $_category)
    {
        return array_map(function (array $category) {
            $object = new Category(
                $category['id_category'],
                $this->context->language->id
            );

            $category['image'] = $this->getImage(
                $object,
                $object->id_image
            );

            $category['url'] = $this->context->link->getCategoryLink(
                $category['id_category'],
                $category['link_rewrite']
            );

            $category['number_of_ads'] = Helpers::getNbProductsToCategory($object->id);

            $category['subcategories'] = $this->getTemplateVarSubCategories($object);

            return $category;
        }, $_category->getSubCategories($this->context->language->id));
    }

    protected function getAllCategoriesParent(): array
    {
        $rout_category  = Category::getRootCategory($this->context->language->id);
        return $this->getTemplateVarSubCategories($rout_category);
    }

}
