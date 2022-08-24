<?php

use NarrysTech\Api_Rest\classes\ProductHelper;
use NarrysTech\Api_Rest\controllers\RestController;
use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Manufacturer;
use PrestaShop\PrestaShop\Adapter\Entity\Pack;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Product\ProductListingPresenter;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Adapter\Product\ProductColorsRetriever;
use PrestaShop\PrestaShop\Core\Addon\Module\ModuleManagerBuilder;

class Api_RestProductModuleFrontController extends RestController
{

    public $params = [
        'table' => 'product',
        'fields' => [
            [
                'name' => 'id',
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

    /**
     * Product
     *
     * @var Category
     */
    protected $category;

    protected $quantity_discounts = [];

    protected function processGetRequest()
    {
        $inputs = $this->checkErrorsRequiredOrType();
        $id_product = $inputs['id'];

        if ((int) $id_product) {
            $id_product = (int) $id_product;
        } else {
            $product_explode = explode('-', $id_product);
            $id_product = (int) $product_explode[0];
            if((int) $product_explode[1]){
                $id_product_attribute = (int) $product_explode[1];
            }else{
                $id_product_attribute = 0;
            }
        }

        $this->product = new Product($id_product, true, $this->context->language->id);
        if (!Validate::isLoadedObject($this->product)) {
            $this->renderAjaxErrors($this->trans('This product is no longer available.', [], 'Shop.Notifications.Error'));
        }

        if (!$this->product->hasCombinations() || !ProductHelper::isValidCombination($id_product_attribute, $this->product->id)) {
            $id_product_attribute = 0;
        }

        if (!(bool)$this->product->active) {
            $this->renderAjaxErrors($this->trans('This product is not enable.', [], 'Shop.Notifications.Warning'));
        }

        $id_category = (int) $this->product->id_category_default;
        $this->category = new Category((int) $id_category, (int) $this->context->language->id);
        $moduleManagerBuilder = ModuleManagerBuilder::getInstance();
        $moduleManager = $moduleManagerBuilder->build();

        if (isset($this->context->cookie, $this->category->id_category) && !($moduleManager->isInstalled('ps_categorytree') && $moduleManager->isEnabled('ps_categorytree'))) {
            $this->context->cookie->last_visited_category = (int) $this->category->id_category;
        }

        if (
            Pack::isPack((int) $this->product->id)
            && !Pack::isInStock((int) $this->product->id, $this->product->minimal_quantity, $this->context->cart)
        ) {
            $this->product->quantity = 0;
        }

        $this->product->description = ProductHelper::transformDescriptionWithImg($this->product, $this->context);

        $priceDisplay = Product::getTaxCalculationMethod((int) $this->context->cookie->id_customer);
        $productPrice = 0;
        $productPriceWithoutReduction = 0;

        if (!$priceDisplay || $priceDisplay == 2) {
            $productPrice = $this->product->getPrice(true, null, 6);
            $productPriceWithoutReduction = $this->product->getPriceWithoutReduct(false, null);
        } elseif ($priceDisplay == 1) {
            $productPrice = $this->product->getPrice(false, null, 6);
            $productPriceWithoutReduction = $this->product->getPriceWithoutReduct(true, null);
        }

        //get vars related to the category + execute hooks related to the category
        $categorys = ProductHelper::getVarsCategory($this->category, $this->context);

        //get vars related to the price and tax
        $priceAndTax = ProductHelper::getPriceAndTax($this->product, $this->context, $id_product_attribute);
        $this->quantity_discounts = $priceAndTax['quantity_discounts'];

        //get attributes combinations
        $attributesCombinations = ProductHelper::getAttributesCombinations($this->product->id);

        $this->datas = array_merge($this->datas, $categorys, $priceAndTax, $attributesCombinations);

        // Pack management
        $pack_items = Pack::isPack($this->product->id) ? Pack::getItemTable($this->product->id, $this->context->language->id, true) : [];

        $assembler = new ProductAssembler($this->context);
        $presenter = new ProductListingPresenter(
            new ImageRetriever(
                $this->context->link
            ),
            $this->context->link,
            new PriceFormatter(),
            new ProductColorsRetriever(),
            $this->getTranslator()
        );
        $presentationSettings = ProductHelper::getProductPresentationSettings($this->context);

        $presentedPackItems = [];
        foreach ($pack_items as $item) {
            $presentedPackItems[] = $presenter->present(
                ProductHelper::getProductPresentationSettings($this->context),
                $assembler->assembleProduct($item),
                $this->context->language
            );
        }

        $this->datas['packItems'] = $presentedPackItems;
        $this->datas['noPackPrice'] = $this->product->getNoPackPrice();
        $this->datas['displayPackPrice'] = ($pack_items && $productPrice < $this->product->getNoPackPrice()) ? true : false;
        $this->datas['priceDisplay'] = $priceDisplay;
        $this->datas['packs'] = Pack::getPacksTable($this->product->id, $this->context->language->id, true, 1);

        $accessories = $this->product->getAccessories($this->context->language->id);
        if (is_array($accessories)) {
            foreach ($accessories as &$accessory) {
                $accessory = $presenter->present(
                    $presentationSettings,
                    Product::getProductProperties($this->context->language->id, $accessory, $this->context),
                    $this->context->language
                );
            }
            unset($accessory);
        }

        if ($this->product->customizable) {
            $customization_datas = $this->context->cart->getProductCustomization($this->product->id, null, true);
        }

        $product_for_template = ProductHelper::getTemplateVarProduct($this->product, $this->context, $id_product_attribute, $this->quantity_discounts);

        $filteredProduct = Hook::exec(
            'filterProductContent',
            ['object' => $product_for_template],
            null,
            false,
            true,
            false,
            null,
            true
        );
        if (!empty($filteredProduct['object'])) {
            $product_for_template = $filteredProduct['object'];
        }

        $productManufacturer = new Manufacturer((int) $this->product->id_manufacturer, $this->context->language->id);

        $manufacturerImageUrl = $this->context->link->getManufacturerImageLink($productManufacturer->id);
        $undefinedImage = $this->context->link->getManufacturerImageLink(null);
        if ($manufacturerImageUrl === $undefinedImage) {
            $manufacturerImageUrl = null;
        }

        $productBrandUrl = $this->context->link->getManufacturerLink($productManufacturer->id);

        $this->datas = array_merge($this->datas, [
            'priceDisplay' => $priceDisplay,
            'productPriceWithoutReduction' => $productPriceWithoutReduction,
            /* 'customizationFields' => $customization_fields, */
            'id_customization' => empty($customization_datas) ? null : $customization_datas[0]['id_customization'],
            'accessories' => $accessories,
            'product' => $product_for_template,
            'displayUnitPrice' => (!empty($this->product->unity) && $this->product->unit_price_ratio > 0.000000) ? true : false,
            'product_manufacturer' => $productManufacturer,
            'manufacturer_image_url' => $manufacturerImageUrl,
            'product_brand_url' => $productBrandUrl,
        ]);

        $attributesGroups = ProductHelper::getAttributesGroups($product_for_template, $this->product, $this->context);

        $this->datas = array_merge($this->datas, $attributesGroups);

        $this->renderAjax();
        parent::processGetRequest();
    }
}
