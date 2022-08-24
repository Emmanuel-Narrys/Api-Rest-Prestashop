<?php

use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Context;
use PrestaShop\PrestaShop\Adapter\Entity\Currency;
use PrestaShop\PrestaShop\Adapter\Entity\Group;
use PrestaShop\PrestaShop\Adapter\Entity\GroupReduction;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Presenter\Object\ObjectPresenter;
use PrestaShop\PrestaShop\Core\Product\ProductExtraContentFinder;

class ProductHelper
{


    /**
     * {@inheritdoc}
     *
     * Indicates if the provided combination exists and belongs to the product
     *
     * @param int $productAttributeId
     * @param int $productId
     *
     * @return bool
     */
    public static function isValidCombination($productAttributeId, $productId)
    {
        if ($productAttributeId > 0 && $productId > 0) {
            $combination = new Combination($productAttributeId);

            return
                Validate::isLoadedObject($combination)
                && $combination->id_product == $productId;
        }

        return false;
    }


    public static function transformDescriptionWithImg(Product $product, Context $context)
    {
        $desc = $product->description;
        $reg = '/\[img\-([0-9]+)\-(left|right)\-([a-zA-Z0-9-_]+)\]/';
        while (preg_match($reg, $desc, $matches)) {
            $link_lmg = $context->link->getImageLink($product->link_rewrite, $product->id . '-' . $matches[1], $matches[3]);
            $class = $matches[2] == 'left' ? 'class="imageFloatLeft"' : 'class="imageFloatRight"';
            $html_img = '<img src="' . $link_lmg . '" alt="" ' . $class . '/>';
            $desc = str_replace($matches[0], $html_img, $desc);
        }

        return $desc;
    }

    /**
     * Get vars related to category.
     *
     * @param Category $category
     * @param Context $context
     * @return array
     */
    public static function getVarsCategory(Category $category, Context $context): array
    {
        $sub_categories = [];
        if (Validate::isLoadedObject($category)) {
            $sub_categories = $category->getSubCategories($context->language->id, true);

            // various return before Hook::exec
            return [
                'category' => $category,
                'subCategories' => $sub_categories,
                'subcategories' => $sub_categories,
                'id_category_current' => (int) $category->id,
                'id_category_parent' => (int) $category->id_parent,
                'return_category_name' => Tools::safeOutput($category->getFieldByLang('name')),
                'categories' => Category::getHomeCategories($context->language->id, true, (int) $context->shop->id),
            ];
        }
        return [];
    }

    /**
     * Get price and tax .
     */
    public static function getPriceAndTax(Product $product, Context $context, $id_product_attribute = 0)
    {
        $id_customer = (isset($context->customer) ? (int) $context->customer->id : 0);
        $id_group = (int) Group::getCurrent()->id;
        $id_country = $id_customer ? (int) Customer::getCurrentCountry($id_customer) : (int) Tools::getCountry();

        // Tax
        $tax = (float) $product->getTaxesRate(new Address((int) $context->cart->{Configuration::get('PS_TAX_ADDRESS_TYPE')}));

        $product_price_with_tax = Product::getPriceStatic($product->id, true, null, 6);
        if (Product::$_taxCalculationMethod == PS_TAX_INC) {
            $product_price_with_tax = Tools::ps_round($product_price_with_tax, 2);
        }

        $id_currency = (int) $context->cookie->id_currency;
        $id_product = (int) $product->id;
        $id_product_attribute = self::getIdProductAttributeByGroupOrRequestOrDefault($product, $id_product_attribute);
        $id_shop = $context->shop->id;

        $quantity_discounts = SpecificPrice::getQuantityDiscounts($id_product, $id_shop, $id_currency, $id_country, $id_group, $id_product_attribute, false, (int) $context->customer->id);
        foreach ($quantity_discounts as &$quantity_discount) {
            if ($quantity_discount['id_product_attribute']) {
                $combination = new Combination((int) $quantity_discount['id_product_attribute']);
                $attributes = $combination->getAttributesName((int) $context->language->id);
                foreach ($attributes as $attribute) {
                    $quantity_discount['attributes'] = $attribute['name'] . ' - ';
                }
                $quantity_discount['attributes'] = rtrim($quantity_discount['attributes'], ' - ');
            }
            if ((int) $quantity_discount['id_currency'] == 0 && $quantity_discount['reduction_type'] == 'amount') {
                $quantity_discount['reduction'] = Tools::convertPriceFull($quantity_discount['reduction'], null, Context::getContext()->currency);
            }
        }
        unset($quantity_discount);

        $product_price = $product->getPrice(Product::$_taxCalculationMethod == PS_TAX_INC, $id_product_attribute, 6, null, false, false);

        $quantity_discounts = self::formatQuantityDiscounts($quantity_discounts, $product_price, (float) $tax, $product->ecotax, $context->currency);

        return [
            'tax_rate' => $tax,
            'quantity_discounts' => $quantity_discounts,
            'no_tax' => Tax::excludeTaxeOption() || !$tax,
            'tax_enabled' => Configuration::get('PS_TAX') && !Configuration::get('AEUC_LABEL_TAX_INC_EXC'),
            'customer_group_without_tax' => Group::getPriceDisplayMethod($context->customer->id_default_group),
        ];
    }

    /**
     * Return id_product_attribute by id_product_attribute group parameter,
     * or request parameter, or the default attribute as a fallback.
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public static function getIdProductAttributeByGroupOrRequestOrDefault(Product $product, $id_product_attribute = 0)
    {
        $idProductAttribute = self::getIdProductAttributeByGroup($product->id);
        if (null === $idProductAttribute) {
            $idProductAttribute = $id_product_attribute;
        }

        if (0 === $idProductAttribute) {
            $idProductAttribute = (int) Product::getDefaultAttribute($product->id);
        }

        return self::tryToGetAvailableIdProductAttribute($idProductAttribute, $product);
    }


    /**
     * Return id_product_attribute by the group request parameter.
     *
     * @return int|null
     *
     * @throws PrestaShopException
     */
    public static function getIdProductAttributeByGroup(int $id_product)
    {
        $groups = Tools::getValue('group');
        if (empty($groups)) {
            return null;
        }

        return (int) Product::getIdProductAttributeByIdAttributes(
            $id_product,
            $groups,
            true
        );
    }

    /**
     * If the PS_DISP_UNAVAILABLE_ATTR functionality is enabled, this method check
     * if $checkedIdProductAttribute is available.
     * If not try to return the first available attribute, if none are available
     * simply returns the input.
     *
     * @param int $checkedIdProductAttribute
     *
     * @return int
     */
    public static function tryToGetAvailableIdProductAttribute($checkedIdProductAttribute, Product $product)
    {
        if (!Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) {
            $productCombinations = $product->getAttributeCombinations();
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock)) {
                $availableProductAttributes = array_filter(
                    $productCombinations,
                    function ($elem) {
                        return $elem['quantity'] > 0;
                    }
                );
            } else {
                $availableProductAttributes = $productCombinations;
            }

            $availableProductAttribute = array_filter(
                $availableProductAttributes,
                function ($elem) use ($checkedIdProductAttribute) {
                    return $elem['id_product_attribute'] == $checkedIdProductAttribute;
                }
            );

            if (empty($availableProductAttribute) && count($availableProductAttributes)) {
                // if selected combination is NOT available ($availableProductAttribute) but they are other alternatives ($availableProductAttributes), then we'll try to get the closest.
                if (!Product::isAvailableWhenOutOfStock($product->out_of_stock)) {
                    // first lets get information of the selected combination.
                    $checkProductAttribute = array_filter(
                        $productCombinations,
                        function ($elem) use ($checkedIdProductAttribute) {
                            return $elem['id_product_attribute'] == $checkedIdProductAttribute || (!$checkedIdProductAttribute && $elem['default_on']);
                        }
                    );
                    if (count($checkProductAttribute)) {
                        // now lets find other combinations for the selected attributes.
                        $alternativeProductAttribute = [];
                        foreach ($checkProductAttribute as $key => $attribute) {
                            $alternativeAttribute = array_filter(
                                $availableProductAttributes,
                                function ($elem) use ($attribute) {
                                    return $elem['id_attribute'] == $attribute['id_attribute'] && !$elem['is_color_group'];
                                }
                            );
                            foreach ($alternativeAttribute as $key => $value) {
                                $alternativeProductAttribute[$key] = $value;
                            }
                        }

                        if (count($alternativeProductAttribute)) {
                            // if alternative combination is found, order the list by quantity to use the one with more stock.
                            usort($alternativeProductAttribute, function ($a, $b) {
                                if ($a['quantity'] == $b['quantity']) {
                                    return 0;
                                }

                                return ($a['quantity'] > $b['quantity']) ? -1 : 1;
                            });

                            return (int) array_shift($alternativeProductAttribute)['id_product_attribute'];
                        }
                    }
                }

                return (int) array_shift($availableProductAttributes)['id_product_attribute'];
            }
        }

        return $checkedIdProductAttribute;
    }

    /**
     * Calculation of currency-converted discounts for specific prices on product.
     *
     * @param array $specific_prices array of specific prices definitions (DEFAULT currency)
     * @param float $price current price in CURRENT currency
     * @param float $tax_rate in percents
     * @param float $ecotax_amount in DEFAULT currency, with tax
     *
     * @return array
     */
    public static function formatQuantityDiscounts($specific_prices, $price, $tax_rate, $ecotax_amount, Currency $currency)
    {
        $priceCalculationMethod = Group::getPriceDisplayMethod(Group::getCurrent()->id);
        $isTaxIncluded = false;

        if ($priceCalculationMethod !== null && (int) $priceCalculationMethod === PS_TAX_INC) {
            $isTaxIncluded = true;
        }

        foreach ($specific_prices as $key => &$row) {
            $specificPriceFormatter = new SpecificPriceFormatter(
                $row,
                $isTaxIncluded,
                $currency,
                Configuration::get('PS_DISPLAY_DISCOUNT_PRICE')
            );
            $row = $specificPriceFormatter->formatSpecificPrice($price, $tax_rate, $ecotax_amount);
            $row['nextQuantity'] = (isset($specific_prices[$key + 1]) ? (int) $specific_prices[$key + 1]['from_quantity'] : -1);
        }

        return $specific_prices;
    }


    /**
     * Get attributes combinations informations.
     */
    public static function getAttributesCombinations(int $id_product)
    {
        $attributes_combinations = Product::getAttributesInformationsByProduct($id_product);
        if (is_array($attributes_combinations) && count($attributes_combinations)) {
            foreach ($attributes_combinations as &$ac) {
                foreach ($ac as &$val) {
                    $val = str_replace(Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR'), '_', Tools::link_rewrite(str_replace([',', '.'], '-', $val)));
                }
            }
        } else {
            $attributes_combinations = [];
        }
        return [
            'attributesCombinations' => $attributes_combinations,
            'attribute_anchor_separator' => Configuration::get('PS_ATTRIBUTE_ANCHOR_SEPARATOR'),
        ];
    }

    public static function getFactory(Context $context)
    {
        return new ProductPresenterFactory($context, new TaxConfiguration());
    }

    public static function getProductPresentationSettings(Context $context)
    {
        return self::getFactory($context)->getPresentationSettings();
    }

    public static function getProductPresenter(Context $context)
    {
        return self::getFactory($context)->getPresenter();
    }

    /**
     * Undocumented function
     *
     * @param Product $product
     * @param Context $context
     * @param integer $id_product_attribute
     * @param array $quantity_discounts
     * @return void
     */
    public static function getTemplateVarProduct(Product $product, Context $context, int $id_product_attribute = 0, array $quantity_discounts = [])
    {
        $productSettings = self::getProductPresentationSettings($context);
        // Hook displayProductExtraContent
        $extraContentFinder = new ProductExtraContentFinder();

        $product_row = (new ObjectPresenter())->present($product);
        $product_row['id_product'] = (int) $product->id;
        $product_row['out_of_stock'] = (int) $product->out_of_stock;
        $product_row['new'] = (int) $product->new;
        $product_row['id_product_attribute'] = self::getIdProductAttributeByGroupOrRequestOrDefault($product);
        $product_row['minimal_quantity'] = self::getProductMinimalQuantity($product, $context, $id_product_attribute);
        $product_row['quantity_wanted'] = self::getRequiredQuantity($product_row);
        $product_row['extraContent'] = $extraContentFinder->addParams(['product' => $product])->present();

        $product_row['ecotax'] = Tools::convertPrice(self::getProductEcotax($product_row, $product, $context), $context->currency, true, $context);

        $product_full = Product::getProductProperties($context->language->id, $product_row, $context);

        $product_full = self::addProductCustomizationData($product_full, $product, $context);

        $product_full['show_quantities'] = (bool) (Configuration::get('PS_DISPLAY_QTIES')
            && Configuration::get('PS_STOCK_MANAGEMENT')
            && $product->quantity > 0
            && $product->available_for_order
            && !Configuration::isCatalogMode()
        );
        $product_full['quantity_label'] = ($product->quantity > 1) ? $context->getTranslator()->trans('Items', [], 'Shop.Theme.Catalog') : $context->getTranslator()->trans('Item', [], 'Shop.Theme.Catalog');
        $product_full['quantity_discounts'] = $quantity_discounts;

        if ($product_full['unit_price_ratio'] > 0) {
            $unitPrice = ($productSettings->include_taxes) ? $product_full['price'] : $product_full['price_tax_exc'];
            $product_full['unit_price'] = $unitPrice / $product_full['unit_price_ratio'];
        }

        $group_reduction = GroupReduction::getValueForProduct($product->id, (int) Group::getCurrent()->id);
        if ($group_reduction === false) {
            $group_reduction = Group::getReduction((int) $context->cookie->id_customer) / 100;
        }
        $product_full['customer_group_discount'] = $group_reduction;
        $product_full['title'] = self::getProductPageTitle(null, $product, $context, $id_product_attribute);

        // round display price (without formatting, we don't want the currency symbol here, just the raw rounded value
        $product_full['rounded_display_price'] = Tools::ps_round(
            $product_full['price'],
            Context::getContext()->currency->precision
        );

        $presenter = self::getProductPresenter($context);

        return $presenter->present(
            $productSettings,
            $product_full,
            $context->language
        );
    }


    /**
     * Undocumented function
     *
     * @param Product $product
     * @param integer $id_product_attribute
     * @return int
     */
    public static function getProductMinimalQuantity(Product $product, Context $context, int $id_product_attribute)
    {
        $minimal_quantity = 1;

        if ($id_product_attribute) {
            $combination = self::findProductCombinationById($id_product_attribute, $product, $context);
            if ($combination['minimal_quantity']) {
                $minimal_quantity = $combination['minimal_quantity'];
            }
        } else {
            $minimal_quantity = $product->minimal_quantity;
        }

        return $minimal_quantity;
    }

    /**
     * @param $combinationId
     *
     * @return ProductController|null
     */
    public static function findProductCombinationById($combinationId, Product $product, Context $context)
    {
        $combinations = $product->getAttributesGroups($context->language->id, $combinationId);

        if ($combinations === false || !is_array($combinations) || empty($combinations)) {
            return null;
        }

        return reset($combinations);
    }

    /**
     * @param $product
     *
     * @return int
     */
    public static function getRequiredQuantity($product_row)
    {
        $requiredQuantity = (int) Tools::getValue('quantity_wanted', 0);
        if ($requiredQuantity < $product_row['minimal_quantity']) {
            $requiredQuantity = $product_row['minimal_quantity'];
        }

        return $requiredQuantity;
    }


    /**
     * Undocumented function
     *
     * @param array $product_row
     * @param Product $product
     * @param Context $context
     * @return float
     */
    public static function getProductEcotax(array $product_row, Product $product, Context $context): float
    {
        $ecotax = $product_row['ecotax'];

        if ($product_row['id_product_attribute']) {
            $combination = self::findProductCombinationById((int)$product_row['id_product_attribute'], $product, $context);
            if (isset($combination['ecotax']) && $combination['ecotax'] > 0) {
                $ecotax = $combination['ecotax'];
            }
        }
        if ($ecotax) {
            // Try to get price display from already assigned smarty variable for better performance
            $priceDisplay = $context->smarty->getTemplateVars('priceDisplay');
            if (null === $priceDisplay) {
                $priceDisplay = Product::getTaxCalculationMethod((int) $context->cookie->id_customer);
            }

            $useTax = $priceDisplay == 0;
            if ($useTax) {
                $ecotax *= (1 + Tax::getProductEcotaxRate() / 100);
            }
        }

        return (float) $ecotax;
    }


    public static function addProductCustomizationData(array $product_full, Product $product, Context $context)
    {
        if ($product_full['customizable']) {
            $customizationData = [
                'fields' => [],
            ];

            $customized_data = [];

            $already_customized = $context->cart->getProductCustomization(
                $product_full['id_product'],
                null,
                true
            );

            $id_customization = 0;
            foreach ($already_customized as $customization) {
                $id_customization = $customization['id_customization'];
                $customized_data[$customization['index']] = $customization;
            }

            $customization_fields = $product->getCustomizationFields($context->language->id);
            if (is_array($customization_fields)) {
                foreach ($customization_fields as $customization_field) {
                    // 'id_customization_field' maps to what is called 'index'
                    // in what Product::getProductCustomization() returns
                    $key = $customization_field['id_customization_field'];

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
                                $imageRetriever = new ImageRetriever($context->link);
                                $field['image'] = $imageRetriever->getCustomizationImage(
                                    $data['value']
                                );
                                $field['remove_image_url'] = $context->link->getProductDeletePictureLink(
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
            $product_full['customizations'] = [
                'fields' => [],
            ];
            $product_full['id_customization'] = 0;
            $product_full['is_customizable'] = false;
        }

        return $product_full;
    }

    
    /**
     * @param array|null $meta
     *
     * @return string
     */
    public static function getProductPageTitle(array $meta = null, Product $product, Context $context, int $id_product_attribute = 0)
    {
        $title = $product->name;
        if (isset($meta['title'])) {
            $title = $meta['title'];
        } elseif (isset($meta['meta_title'])) {
            $title = $meta['meta_title'];
        }
        if (!Configuration::get('PS_PRODUCT_ATTRIBUTES_IN_TITLE')) {
            return $title;
        }

        $idProductAttribute = self::getIdProductAttributeByGroupOrRequestOrDefault($product, $id_product_attribute);
        if ($idProductAttribute) {
            $attributes = $product->getAttributeCombinationsById($idProductAttribute, $context->language->id);
            if (is_array($attributes) && count($attributes) > 0) {
                foreach ($attributes as $attribute) {
                    $title .= ' ' . $attribute['group_name'] . ' ' . $attribute['attribute_name'];
                }
            }
        }

        return $title;
    }

    
    /**
     * get vars related to attribute groups and colors.
     */
    public static function getAttributesGroups($product_for_template = null, Product $product, Context $context)
    {
        $colors = [];
        $groups = [];
        $combinations = [];
        $return = [];

        /** @todo (RM) should only get groups and not all declination ? */
        $attributes_groups = $product->getAttributesGroups($context->language->id);
        if (is_array($attributes_groups) && $attributes_groups) {
            $combination_images = $product->getCombinationImages($context->language->id);
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

                $combinations[$row['id_product_attribute']]['attributes_values'][$row['id_attribute_group']] = $row['attribute_name'];
                $combinations[$row['id_product_attribute']]['attributes'][] = (int) $row['id_attribute'];
                $combinations[$row['id_product_attribute']]['price'] = (float) $row['price'];

                // Call getPriceStatic in order to set $combination_specific_price
                if (!isset($combination_prices_set[(int) $row['id_product_attribute']])) {
                    $combination_specific_price = null;
                    Product::getPriceStatic((int) $product->id, false, $row['id_product_attribute'], 6, null, false, true, 1, false, null, null, null, $combination_specific_price);
                    $combination_prices_set[(int) $row['id_product_attribute']] = true;
                    $combinations[$row['id_product_attribute']]['specific_price'] = $combination_specific_price;
                }
                $combinations[$row['id_product_attribute']]['ecotax'] = (float) $row['ecotax'];
                $combinations[$row['id_product_attribute']]['weight'] = (float) $row['weight'];
                $combinations[$row['id_product_attribute']]['quantity'] = (int) $row['quantity'];
                $combinations[$row['id_product_attribute']]['reference'] = $row['reference'];
                $combinations[$row['id_product_attribute']]['ean13'] = $row['ean13'];
                $combinations[$row['id_product_attribute']]['mpn'] = $row['mpn'];
                $combinations[$row['id_product_attribute']]['upc'] = $row['upc'];
                $combinations[$row['id_product_attribute']]['isbn'] = $row['isbn'];
                $combinations[$row['id_product_attribute']]['unit_impact'] = $row['unit_price_impact'];
                $combinations[$row['id_product_attribute']]['minimal_quantity'] = $row['minimal_quantity'];
                if ($row['available_date'] != '0000-00-00' && Validate::isDate($row['available_date'])) {
                    $combinations[$row['id_product_attribute']]['available_date'] = $row['available_date'];
                    $combinations[$row['id_product_attribute']]['date_formatted'] = Tools::displayDate($row['available_date']);
                } else {
                    $combinations[$row['id_product_attribute']]['available_date'] = $combinations[$row['id_product_attribute']]['date_formatted'] = '';
                }

                if (!isset($combination_images[$row['id_product_attribute']][0]['id_image'])) {
                    $combinations[$row['id_product_attribute']]['id_image'] = -1;
                } else {
                    $combinations[$row['id_product_attribute']]['id_image'] = $id_image = (int) $combination_images[$row['id_product_attribute']][0]['id_image'];
                    if ($row['default_on']) {
                        foreach ($context->smarty->tpl_vars['product']->value['images'] as $image) {
                            if ($image['cover'] == 1) {
                                $current_cover = $image;
                            }
                        }
                        if (!isset($current_cover)) {
                            $current_cover = array_values($context->smarty->tpl_vars['product']->value['images'])[0];
                        }

                        if (is_array($combination_images[$row['id_product_attribute']])) {
                            foreach ($combination_images[$row['id_product_attribute']] as $tmp) {
                                if ($tmp['id_image'] == $current_cover['id_image']) {
                                    $combinations[$row['id_product_attribute']]['id_image'] = $id_image = (int) $tmp['id_image'];

                                    break;
                                }
                            }
                        }

                        if ($id_image > 0) {
                            if (isset($context->smarty->tpl_vars['images']->value)) {
                                $product_images = $context->smarty->tpl_vars['images']->value;
                            }
                            if (isset($product_images) && is_array($product_images) && isset($product_images[$id_image])) {
                                $product_images[$id_image]['cover'] = 1;
                                $return['mainImage'] = $product_images[$id_image];
                                if (count($product_images)) {
                                    $return['images'] = $product_images;
                                }
                            }

                            $cover = $current_cover;

                            if (isset($cover) && is_array($cover) && isset($product_images) && is_array($product_images)) {
                                $product_images[$cover['id_image']]['cover'] = 0;
                                if (isset($product_images[$id_image])) {
                                    $cover = $product_images[$id_image];
                                }
                                $cover['id_image'] = (Configuration::get('PS_LEGACY_IMAGES') ? ($product->id . '-' . $id_image) : (int) $id_image);
                                $cover['id_image_only'] = (int) $id_image;
                                $return['cover'] = $cover;
                            }
                        }
                    }
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
                        WHERE id_product = ' . $product->id . ' AND id_attribute IN (' . implode(',', array_map('intval', $current_selected_attributes)) . ')
                        GROUP BY id_product_attribute
                        HAVING COUNT(id_product) = ' . count($current_selected_attributes);
                    if ($results = Db::getInstance()->executeS($query)) {
                        foreach ($results as $row) {
                            $id_product_attributes[] = $row['id_product_attribute'];
                        }
                    }
                    $id_attributes = Db::getInstance()->executeS('SELECT pac2.`id_attribute` FROM `' . _DB_PREFIX_ . 'product_attribute_combination` pac2' .
                        ((!Product::isAvailableWhenOutOfStock($product->out_of_stock) && 0 == Configuration::get('PS_DISP_UNAVAILABLE_ATTR')) ?
                        ' INNER JOIN `' . _DB_PREFIX_ . 'stock_available` pa ON pa.id_product_attribute = pac2.id_product_attribute
                        WHERE pa.quantity > 0 AND ' :
                        ' WHERE ') .
                        'pac2.`id_product_attribute` IN (' . implode(',', array_map('intval', $id_product_attributes)) . ')
                        AND pac2.id_attribute NOT IN (' . implode(',', array_map('intval', $current_selected_attributes)) . ')');
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
            if (!Product::isAvailableWhenOutOfStock($product->out_of_stock) && Configuration::get('PS_DISP_UNAVAILABLE_ATTR') == 0) {
                foreach ($groups as &$group) {
                    foreach ($group['attributes_quantity'] as $key => $quantity) {
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
                    $attribute_list .= '\'' . (int) $id_attribute . '\',';
                }
                $attribute_list = rtrim($attribute_list, ',');
                $combinations[$id_product_attribute]['list'] = $attribute_list;
            }
            unset($group);

            $return = array_merge($return, [
                'groups' => $groups,
                'colors' => (count($colors)) ? $colors : false,
                'combinations' => $combinations,
                'combinationImages' => $combination_images,
            ]);

        } else {
            $return = array_merge($return, [
                'groups' => [],
                'colors' => false,
                'combinations' => [],
                'combinationImages' => [],
            ]);
        }

        return $return;
    }
}
