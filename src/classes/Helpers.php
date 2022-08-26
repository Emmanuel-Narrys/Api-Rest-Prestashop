<?php

namespace NarrysTech\Api_Rest\classes;

use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Context;
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\Product;

class Helpers
{

    public static function response_json($datas = [], int $status = 200, bool $success = true): string
    {
        return json_encode([
            "success" => $success,
            "status" => $status,
            "datas" => $datas
        ]);
    }

    /**
     * Generete sponsorship code
     *
     * @return string
     */
    public static function generateSponsorshipCode()
    {
        $length = 10;
        $characters = '0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length - 1; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        $exist = Db::getInstance()->executeS("SELECT * FROM `" . _DB_PREFIX_ . "customer` WHERE `sponsorship_code` = '$randomString'");

        if (!empty($exist)) {
            return self::generateSponsorshipCode();
        }
        return $randomString;
    }

    /**
     * Undocumented function
     *
     * @param string $username
     * @return string|false
     */
    public static function getEmailByUsername(string $username){
        $result = Db::getInstance()->executeS("SELECT email FROM `"._DB_PREFIX_."customer` WHERE username = '$username'", false)->fetch();
        if($result != false){
            return $result['email'];
        }else {
            return false;
        }
    }
 
    /**
     * Get Format Price
     *
     * @param float $price
     * @param string $iso_code
     * @return string
     */
    public static function formatPrice(float $price, string $iso_code):string
    {
        $context = Context::getContext();
        return $context->currentLocale->formatPrice($price, $iso_code);
    }
    
    public static function getNbCategory():int
    {
        $results = Category::getCategories();
        return count($results);
    }

    public static function getNbProduct():int
    {
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', false, true);
        return count($results);
    }

    public static function getNbCustomer():int
    {
        $results = Customer::getCustomers(true);
        return count($results);
    }
    
    public static function getNbProductsToCategory(int $id_category): int
    {
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', $id_category, true, Context::getContext());
        return count($results);
    }
}
