<?php

/**
 * Emmanuel-Narrys
 *
 * @author Emmanuel-Narrys
 * @copyright Emmanuel-Narrys
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * Best In Shops eCommerce Solutions Inc.
 *
 */

namespace NarrysTech\Api_Rest\classes;

use Context;
use DateTime;
use Language;
use PrestaShop\Decimal\Number;
use PrestaShop\Decimal\Operation\Rounding;
use PrestaShop\PrestaShop\Adapter\Entity\Configuration;
use PrestaShop\PrestaShop\Adapter\Entity\Currency;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use PrestaShop\PrestaShop\Adapter\Entity\Tools;
use PrestaShop\PrestaShop\Adapter\HookManager;
use PrestaShop\PrestaShop\Adapter\Image\ImageRetriever;
use PrestaShop\PrestaShop\Adapter\Product\PriceFormatter;
use PrestaShop\PrestaShop\Core\Domain\Product\Stock\ValueObject\OutOfStockType;
use PrestaShop\PrestaShop\Core\Product\ProductPresentationSettings;
use Symfony\Component\Translation\TranslatorInterface;
use Validate;
use Viaziza\Smalldeals\Classes\Boutique;
use Viaziza\Smalldeals\Classes\ProductStore;
use WishList;

class RESTProductLazyArray
{
    /**
     * @var ProductPresentationSettings
     */
    protected $settings;

    /**
     * @var array
     */
    protected $product;

    /**
     * @var Language
     */
    private $language;

    /**
     * @var PriceFormatter
     */
    private $priceFormatter;

    /**
     * @var ImageRetriever
     */
    private $imageRetriever;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    /**
     * @var HookManager
     */
    private $hookManager;

    /**
     * @var ProductStore
     */
    private $productStore;

    /**
     * @var Context
     */
    private $context;

    public function __construct(
        ProductPresentationSettings $settings,
        array $product,
        Language $language,
        PriceFormatter $priceFormatter,
        ImageRetriever $imageRetriever,
        TranslatorInterface $translator,
        ProductStore $productStore = null
    ) {
        $this->settings = $settings;
        $this->product = $product;
        $this->language = $language;
        $this->priceFormatter = $priceFormatter;
        $this->imageRetriever = $imageRetriever;
        $this->translator = $translator;
        $this->hookManager = new HookManager();
        $this->productStore = $productStore;
        $this->context = Context::getContext();

        $this->addStore();

        $this->fillImages(
            $product,
            $language
        );

        $this->addPriceInformation(
            $settings,
            $product
        );

        $this->addQuantityInformation(
            $settings,
            $product,
            $language
        );

        $this->getFlags();

        $this->addDateAgo();

        $this->getWishlist();
    }

    protected function addPriceInformation(
        ProductPresentationSettings $settings,
        array $product
    ) {
        $this->product['has_discount'] = false;
        $this->product['discount_type'] = null;
        $this->product['discount_percentage'] = null;
        $this->product['discount_percentage_absolute'] = null;
        $this->product['discount_amount'] = null;
        $this->product['discount_amount_to_display'] = null;

        if ($settings->include_taxes) {
            $price = $regular_price = $this->productStore ? $this->productStore->price : $product['price'];
        } else {
            $price = $regular_price = $this->productStore ? $this->productStore->price : $product['price_tax_exc'];
        }

        if ($product['specific_prices']) {
            $this->product['has_discount'] = (0 != $product['reduction']);
            $this->product['discount_type'] = $product['specific_prices']['reduction_type'];

            $absoluteReduction = new Number($product['specific_prices']['reduction']);
            $absoluteReduction = $absoluteReduction->times(new Number('100'));
            $negativeReduction = $absoluteReduction->toNegative();
            $presAbsoluteReduction = $absoluteReduction->round(2, Rounding::ROUND_HALF_UP);
            $presNegativeReduction = $negativeReduction->round(2, Rounding::ROUND_HALF_UP);

            // TODO: add percent sign according to locale preferences
            $this->product['discount_percentage'] = Tools::displayNumber($presNegativeReduction) . '%';
            $this->product['discount_percentage_absolute'] = Tools::displayNumber($presAbsoluteReduction) . '%';
            if ($settings->include_taxes) {
                $regular_price = $product['price_without_reduction'];
                $this->product['discount_amount'] = $this->priceFormatter->format(
                    $product['reduction']
                );
            } else {
                $regular_price = $product['price_without_reduction_without_tax'];
                $this->product['discount_amount'] = $this->priceFormatter->format(
                    $product['reduction_without_tax']
                );
            }
            $this->product['discount_amount_to_display'] = '-' . $this->product['discount_amount'];
        }

        $this->product['price_amount'] = $price;
        $this->product['price'] = $this->priceFormatter->format($price);
        $this->product['regular_price_amount'] = $regular_price;
        $this->product['regular_price'] = $this->priceFormatter->format($regular_price);

        if ($product['reduction'] < $product['price_without_reduction']) {
            $this->product['discount_to_display'] = $this->product['discount_amount'];
        } else {
            $this->product['discount_to_display'] = $this->product['regular_price'];
        }

        if (isset($product['unit_price']) && $product['unit_price']) {
            $this->product['unit_price'] = $this->priceFormatter->format($product['unit_price']);
            $this->product['unit_price_full'] = $this->priceFormatter->format($product['unit_price'])
                . ' ' . $product['unity'];
        } else {
            $this->product['unit_price'] = $this->product['unit_price_full'] = '';
        }

        //Get prices with all currencies
        $currencies = Currency::getCurrencies(true, true, true);
        foreach ($currencies as $currency) {
            $amount = $currency->conversion_rate * $price;
            $this->product['price_currencies'][] = (object) [
                "price" => Helpers::formatPrice($amount, $currency->iso_code),
                "reduction" => $currency->reduction
            ];
        }
    }

    /**
     * @param array $product
     * @param Language $language
     */
    protected function fillImages(array $product, Language $language): void
    {
        // Get all product images, including potential cover
        $productImages = $this->imageRetriever->getAllProductImages(
            $product,
            $language
        );

        // Get filtered product images matching the specified id_product_attribute
        $this->product['images'] = $this->filterImagesForCombination($productImages, $product['id_product_attribute']);

        // Get default image for selected combination (used for product page, cart details, ...)
        $this->product['default_image'] = reset($this->product['images']);
        foreach ($this->product['images'] as $image) {
            // If one of the image is a cover it is used as such
            if (isset($image['cover']) && null !== $image['cover']) {
                $this->product['default_image'] = $image;

                break;
            }
        }

        // Get generic product image, used for product listing
        if (isset($product['cover_image_id'])) {
            // First try to find cover in product images
            foreach ($productImages as $productImage) {
                if ($productImage['id_image'] == $product['cover_image_id']) {
                    $this->product['cover'] = $productImage;
                    break;
                }
            }

            // If the cover is not associated to the product images it is fetched manually
            if (!isset($this->product['cover'])) {
                $coverImage = $this->imageRetriever->getImage(new Product($product['id_product'], false, $language->getId()), $product['cover_image_id']);
                $this->product['cover'] = array_merge($coverImage, [
                    'legend' => $coverImage['legend'],
                ]);
            }
        }

        // If no cover fallback on default image
        if (!isset($this->product['cover'])) {
            $this->product['cover'] = $this->product['default_image'];
        }
    }

    /**
     * @param ProductPresentationSettings $settings
     * @param array $product
     * @param Language $language
     */
    public function addQuantityInformation(
        ProductPresentationSettings $settings,
        array $product,
        Language $language
    ) {
        $show_price = $this->shouldShowPrice($settings, $product);
        $show_availability = $show_price && $settings->stock_management_enabled;
        $this->product['show_availability'] = $show_availability;
        $product['quantity_wanted'] = $this->getQuantityWanted();

        if (isset($product['available_date']) && '0000-00-00' == $product['available_date']) {
            $product['available_date'] = null;
        }

        if ($show_availability) {
            if ($product['quantity'] - $product['quantity_wanted'] >= 0) {
                $this->product['availability_date'] = $product['available_date'];

                if ($product['quantity'] < $settings->lastRemainingItems) {
                    $this->applyLastItemsInStockDisplayRule();
                } else {
                    $this->product['availability_message'] = $product['available_now'] ? $product['available_now']
                        : Configuration::get('PS_LABEL_IN_STOCK_PRODUCTS', $language->id);
                    $this->product['availability'] = 'available';
                }
            } elseif ($product['allow_oosp']) {
                $this->product['availability_message'] = $product['available_later'] ? $product['available_later']
                    : Configuration::get('PS_LABEL_OOS_PRODUCTS_BOA', $language->id);
                $this->product['availability_date'] = $product['available_date'];
                $this->product['availability'] = 'available';
            } elseif ($product['quantity_wanted'] > 0 && $product['quantity'] > 0) {
                $this->product['availability_message'] = $this->translator->trans(
                    'There are not enough products in stock',
                    [],
                    'Shop.Notifications.Error'
                );
                $this->product['availability'] = 'unavailable';
                $this->product['availability_date'] = null;
            } elseif (!empty($product['quantity_all_versions']) && $product['quantity_all_versions'] > 0) {
                $this->product['availability_message'] = $this->translator->trans(
                    'Product available with different options',
                    [],
                    'Shop.Theme.Catalog'
                );
                $this->product['availability_date'] = $product['available_date'];
                $this->product['availability'] = 'unavailable';
            } else {
                $this->product['availability_message'] =
                    Configuration::get('PS_LABEL_OOS_PRODUCTS_BOD', $language->id);
                $this->product['availability_date'] = $product['available_date'];
                $this->product['availability'] = 'unavailable';
            }
        } else {
            $this->product['availability_message'] = null;
            $this->product['availability_date'] = null;
            $this->product['availability'] = null;
        }
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
     * Prices should be shown for products with active "Show price" option
     * and customer groups with active "Show price" option.
     *
     * @param ProductPresentationSettings $settings
     * @param array $product
     *
     * @return bool
     */
    private function shouldShowPrice(
        ProductPresentationSettings $settings,
        array $product
    ) {
        return $settings->shouldShowPrice() && (bool)$product['show_price'];
    }

    /**
     * @return int Quantity of product requested by the customer
     */
    private function getQuantityWanted()
    {
        return (int)Tools::getValue('quantity_wanted', 1);
    }

    /**
     * Override availability message.
     */
    protected function applyLastItemsInStockDisplayRule()
    {
        $this->product['availability_message'] = $this->translator->trans(
            'Last items in stock',
            [],
            'Shop.Theme.Catalog'
        );
        $this->product['availability'] = 'last_remaining_items';
    }

    /**
     * @return array
     */
    public function getProduct(): array
    {
        return $this->product;
    }

    /**
     * @arrayAccess
     *
     * @return array
     *
     * @throws InvalidArgumentException
     */
    public function getFlags()
    {
        $flags = [];

        $show_price = $this->shouldShowPrice($this->settings, $this->product);

        if ($show_price && $this->product['online_only']) {
            $flags['online-only'] = [
                'type' => 'online-only',
                'label' => $this->translator->trans('Online only', [], 'Shop.Theme.Catalog'),
            ];
        }

        if ($show_price && $this->product['on_sale'] && !$this->settings->catalog_mode) {
            $flags['on-sale'] = [
                'type' => 'on-sale',
                'label' => $this->translator->trans('On sale!', [], 'Shop.Theme.Catalog'),
            ];
        }

        if ($show_price && $this->product['reduction']) {
            if ($this->product['discount_type'] === 'percentage') {
                $flags['discount'] = [
                    'type' => 'discount',
                    'label' => $this->product['discount_percentage'],
                ];
            } elseif ($this->product['discount_type'] === 'amount') {
                $flags['discount'] = [
                    'type' => 'discount',
                    'label' => $this->product['discount_amount_to_display'],
                ];
            } else {
                $flags['discount'] = [
                    'type' => 'discount',
                    'label' => $this->translator->trans('Reduced price', [], 'Shop.Theme.Catalog'),
                ];
            }
        }

        if ($this->product['new']) {
            $flags['new'] = [
                'type' => 'new',
                'label' => $this->translator->trans('New', [], 'Shop.Theme.Catalog'),
            ];
        }

        if ($this->product['pack']) {
            $flags['pack'] = [
                'type' => 'pack',
                'label' => $this->translator->trans('Pack', [], 'Shop.Theme.Catalog'),
            ];
        }

        if ($this->shouldShowOutOfStockLabel($this->settings, $this->product)) {
            $config = COnfiguration::get('PS_LABEL_OOS_PRODUCTS_BOD');
            $flags['out_of_stock'] = [
                'type' => 'out_of_stock',
                'label' => $config[$this->language->getId()] ?? null,
            ];
        }

        $this->hookManager->exec('actionProductFlagsModifier', [
            'flags' => &$flags,
            'product' => $this->product,
        ]);

        $this->product['flags'] = $flags;
    }


    /**
     * @param array $product
     *
     * @return bool
     */
    private function shouldShowOutOfStockLabel(ProductPresentationSettings $settings, array $product): bool
    {
        if (!$settings->showLabelOOSListingPages) {
            return false;
        }

        if (!(bool)Configuration::get('PS_STOCK_MANAGEMENT')) {
            return false;
        }

        // Displayed only if the order of out of stock product is denied.
        if (
            $product['out_of_stock'] == OutOfStockType::OUT_OF_STOCK_AVAILABLE
            || ($product['out_of_stock'] == OutOfStockType::OUT_OF_STOCK_DEFAULT
                && (bool)Configuration::get('PS_ORDER_OUT_OF_STOCK')
            )
        ) {
            return false;
        }

        if ($product['id_product_attribute']) {
            // Displayed only if all combinations are out of stock (stock is <= 0)
            $product = new Product((int) $product['id_product']);
            if (empty($product->id)) {
                return false;
            }

            foreach ($product->getAttributesResume($this->language->getId()) as $combination) {
                if ($combination['quantity'] > 0) {
                    return false;
                }
            }
        } elseif ($product['quantity'] > 0) {
            // Displayed only if the product stock is <= 0
            return false;
        }

        return true;
    }

    //Add store into product
    public function addStore()
    {
        $id_lang = Context::getContext()->language->id;
        if (!$this->productStore) {
            $this->productStore = ProductStore::getProductStoreWithMinPrice((int)$this->product['id_product'], $id_lang);
            if (!$this->productStore && !Validate::isLoadedObject($this->productStore)) {
                return false;
            }
        }

        $this->product['id_sd_store'] = $this->productStore->id_sd_store;
        $this->product['quantity'] = $this->productStore->quantity;
        $this->product['active'] = $this->productStore->active;
        $this->product['show_price'] = $this->productStore->show_price;

        $store = Boutique::getStore((int) $this->product['id_sd_store'], $id_lang);
        $this->product['store'] = $store;
        $this->product["stores"] = Boutique::getStoresSameProduct(
            (int)$this->product['id_product'],
            (int) $this->product['id_sd_store'],
            $id_lang
        );
        return true;
    }

    public function addDateAgo()
    {
        $now = new DateTime();
        $creat = new DateTime($this->product['date_add']);
        $diff = $now->diff($creat);
        $nb_day = $diff->days;
        $ago = "";
        if ($nb_day) {
            if ($nb_day > 7 && $nb_day <= 30) {
                $nb_week = floor($nb_day / 7);
                $ago = "$nb_week semaines passé";
            } else if ($nb_day > 30 && $nb_day <= 365) {
                $nb_month = floor($nb_day / 30);
                $ago = "$nb_month mois passé";
            } else if ($nb_day > 365) {
                $nb_yesr = floor($nb_day / 365);
                $ago = "$nb_yesr années passé";
            } else {
                $ago = "$nb_day jours passé";
            }
        }
        $this->product['date_ago'] = $ago;
    }

    public function getWishlist()
    {
        $customer = $this->context->customer;
        if ($customer && Validate::isLoadedObject($customer) && $customer->isLogged()) {
            $products = WishList::getAllProductByCustomer($customer->id, $this->context->shop->id);
            $id_wishlist = null;
            if ($products) {
                array_map(function ($prod) use (&$id_wishlist) {
                    if (((int)$this->product["id_product"] == (int)$prod["id_product"]) /* &&
                        ((int)$this->product["id_product_attribute"] == (int) $prod["id_product_attribute"]) */) {
                        $id_wishlist = (int) $prod["id_wishlist"];
                    }
                }, $products);
            }
            $this->product["id_wishlist"] = $id_wishlist;
        } else {
            $this->product["id_wishlist"] = null;
        }
    }
}
