<?php

namespace NarrysTech\Api_Rest\classes;

use Configuration;
use Exception;
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
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', false, true, null, true);
        return count($results);
    }

    public static function getNbCustomer():int
    {
        $results = Customer::getCustomers(true);
        return count($results);
    }
    
    public static function getNbProductsToCategory(int $id_category): int
    {
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', $id_category, true, Context::getContext(), true);
        return count($results);
    }

    public static function formatUrlWithParams (string $url, array $params = []):string
    {
        if(str_contains($url, '?')){
            foreach($params as $key => $param){
                $url .= '&'.$key.'='.$param;
            }
        }else{
            $url .= '?';
            $i = 0;
            foreach($params as $key => $param){
                $url .= ($i == 0 ? '' : '&').$key.'='.$param;
                $i++;
            }
        }
        return $url;
    }

    
    public static function request(string $url, bool $post = false, array $body = [], string  $authorization = null)
    {
        $ch = curl_init();
        try {
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_POST, $post);
            if ($post) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
            }
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: ' . ($authorization ? 'Bearer ' . $authorization : '')
            ));

            $response = curl_exec($ch);

            if (curl_errno($ch)) {
                echo curl_error($ch);
                die();
            }

            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            if ($http_code == intval(200)) {
                return json_decode($response, true);
            } else {
                return [];
            }
        } catch (Exception $e) {
            throw $e;
        } finally {
            curl_close($ch);
        }
    }

    public static function getOldCategoriesStores()
    {
        return self::request("https://small-deals.com/wp-json/api/get_stores_categories");
    }

    public static function getOldCategoriesAnnonces()
    {
        return self::request("https://small-deals.com/wp-json/api/api_categories");
    }

    public static function setClientGoogleApi (string $redirect_uri){
        $url = "https://accounts.google.com/o/oauth2/v2/auth?";
        $scope = urlencode("https://www.googleapis.com/auth/youtube.upload");
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $response_type = "code";
        $access_type = "online";
        $include_granted_scopes = "true";
        $redirect_uri = urlencode($redirect_uri);

        $new_url = $url."scope=$scope";
        $new_url.="&access_type=$access_type";
        $new_url.="&redirect_uri=$redirect_uri";
        $new_url.="&response_type=$response_type";
        $new_url.="&client_id=$client_id";
        $new_url.="&include_granted_scopes=$include_granted_scopes";

        return $new_url;
    }

    public static function getTokenGoogleApi (string $redirect_uri, string $code){
        $url = "https://oauth2.googleapis.com/token?";
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $client_secret = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_SECRET");
        $redirect_uri = urlencode($redirect_uri);
        $grant_type = "authorization_code";

        $new_url = $url."code=$code";
        $new_url.="&grant_type=$grant_type";
        $new_url.="&redirect_uri=$redirect_uri";
        $new_url.="&client_id=$client_id";
        $new_url.="&client_secret=$client_secret";

        return self::request($new_url);
    }

    public static function refreshTokenGoogleApi (string $refresh_token){
        $url = "https://oauth2.googleapis.com/token?";
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $client_secret = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_SECRET");
        $grant_type = "refresh_token";

        $new_url = $url."refresh_token=$refresh_token";
        $new_url.="&grant_type=$grant_type";
        $new_url.="&client_id=$client_id";
        $new_url.="&client_secret=$client_secret";

        return self::request($new_url);
    }

    public static function uploadMovieGoogleApi (string $token){
        $url = "POST https://www.googleapis.com/upload/youtube/v3/videos?";
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $client_secret = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_SECRET");
        $grant_type = "refresh_token";

        $new_url = $url."grant_type=$grant_type";
        $new_url.="&client_id=$client_id";
        $new_url.="&client_secret=$client_secret";

        return self::request($new_url, true, [], $token);
    }
}
