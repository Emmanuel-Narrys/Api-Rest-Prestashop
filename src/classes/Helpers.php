<?php

namespace NarrysTech\Api_Rest\classes;

use Configuration;
use DbQuery;
use Exception;
use PrestaShop\PrestaShop\Adapter\Entity\Category;
use PrestaShop\PrestaShop\Adapter\Entity\Context;
use PrestaShop\PrestaShop\Adapter\Entity\Customer;
use PrestaShop\PrestaShop\Adapter\Entity\Db;
use PrestaShop\PrestaShop\Adapter\Entity\Product;
use stdClass;

class Helpers
{
    const FB_APP_ID = "276908837493377"; //id de l'app small-deals sur fb
    const FB_ROOT = "https://graph.facebook.com/v13.0/276908837493377";
    const FB_TOKEN = "EABEMb6n9EuYBAPhZCZBHPwARq8pWOY76sKWvkqWhCRX7cacG3OakqVaVxib6toDm1ThXxZAIdHzGqwgeqNZBMAT1LFpRqsYa8LqerfURt5wKLazCKJ555ZBZCIZB29aDNHrUV75Ln84lHxiO0L5RY1q4qZAR5TZBl3Hjuv65hgtm8hOJ1Nih8a3ZCt";

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
    public static function getEmailByUsername(string $username)
    {
        $result = Db::getInstance()->executeS("SELECT email FROM `" . _DB_PREFIX_ . "customer` WHERE username = '$username'", false)->fetch();
        if ($result != false) {
            return $result['email'];
        } else {
            return false;
        }
    }


    /**
     * Undocumented function
     *
     * @param string $username
     * @return bool
     */
    public static function validateUsername(string $username)
    {
        $allowed = array(".", "-", "_"); // you can add here more value, you want to allow.
        if (ctype_alnum(str_replace($allowed, '', $username))) {
            if (is_numeric($username)) {
                return false;
            }
            return true;
        } else {
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
    public static function formatPrice(float $price, string $iso_code): string
    {
        $context = Context::getContext();
        return $context->currentLocale->formatPrice($price, $iso_code);
    }

    public static function getNbCategory(): int
    {
        $results = Category::getCategories();
        return count($results);
    }

    public static function getNbProduct(): int
    {
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', false, true, null, true);
        return count($results);
    }

    public static function getNbCustomer(): int
    {
        $results = Customer::getCustomers(true);
        return count($results);
    }

    public static function getNbProductsToCategory(int $id_category): int
    {
        $results = Product::getProducts(Context::getContext()->language->id, 0, 0, 'id_product', 'DESC', $id_category, true, Context::getContext(), true);
        return count($results);
    }

    public static function formatUrlWithParams(string $url, array $params = []): string
    {
        if (str_contains($url, '?')) {
            foreach ($params as $key => $param) {
                $url .= '&' . $key . '=' . $param;
            }
        } else {
            $url .= '?';
            $i = 0;
            foreach ($params as $key => $param) {
                $url .= ($i == 0 ? '' : '&') . $key . '=' . $param;
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

    public static function getOldCustomer()
    {
        return self::request("https://small-deals.com/wp-json/api/get_non_parrain_users");
    }

    public static function getOldStores(int $user_id)
    {
        return self::request("https://small-deals.com/wp-json/api/api_get_stores?user_id=$user_id");
    }

    public static function setClientGoogleApi(string $redirect_uri)
    {
        $url = "https://accounts.google.com/o/oauth2/v2/auth?";
        $scope = urlencode("https://www.googleapis.com/auth/youtube.upload");
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $response_type = "code";
        $access_type = "online";
        $include_granted_scopes = "true";
        $redirect_uri = urlencode($redirect_uri);

        $new_url = $url . "scope=$scope";
        $new_url .= "&access_type=$access_type";
        $new_url .= "&redirect_uri=$redirect_uri";
        $new_url .= "&response_type=$response_type";
        $new_url .= "&client_id=$client_id";
        $new_url .= "&include_granted_scopes=$include_granted_scopes";

        return $new_url;
    }

    public static function getTokenGoogleApi(string $redirect_uri, string $code)
    {
        $url = "https://oauth2.googleapis.com/token?";
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $client_secret = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_SECRET");
        $redirect_uri = urlencode($redirect_uri);
        $grant_type = "authorization_code";

        $new_url = $url . "code=$code";
        $new_url .= "&grant_type=$grant_type";
        $new_url .= "&redirect_uri=$redirect_uri";
        $new_url .= "&client_id=$client_id";
        $new_url .= "&client_secret=$client_secret";

        return self::request($new_url);
    }

    public static function refreshTokenGoogleApi(string $refresh_token)
    {
        $url = "https://oauth2.googleapis.com/token?";
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $client_secret = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_SECRET");
        $grant_type = "refresh_token";

        $new_url = $url . "refresh_token=$refresh_token";
        $new_url .= "&grant_type=$grant_type";
        $new_url .= "&client_id=$client_id";
        $new_url .= "&client_secret=$client_secret";

        return self::request($new_url);
    }

    public static function uploadMovieGoogleApi(string $token)
    {
        $url = "POST https://www.googleapis.com/upload/youtube/v3/videos?";
        $client_id = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_ID");
        $client_secret = Configuration::get("SMALLDEALS_OAUTH2_CLIENT_SECRET");
        $grant_type = "refresh_token";

        $new_url = $url . "grant_type=$grant_type";
        $new_url .= "&client_id=$client_id";
        $new_url .= "&client_secret=$client_secret";

        return self::request($new_url, true, [], $token);
    }

    public static function uploadImageToFacebook(string $url_image)
    {
        try {
            $curl = curl_init();
            $cUR = self::FB_ROOT . "/photos?published=false&url=$url_image&access_token=" . self::FB_TOKEN;
            curl_setopt_array($curl, array(
                CURLOPT_URL => $cUR,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "access_token: " . self::FB_TOKEN
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);
            $obj = json_decode($response);
            $id = isset($obj->id) ? $obj->id : -1;

            return $id;
        } catch (Exception $th) {
            return -1;
        }
    }

    public static function postListingToFacebook(string $message, array $images)
    {
        try {
            $data = new stdClass();
            $message = strip_tags($message);
            $data->message = "$message";
            $data->attached_media = array();

            if (sizeof($images) > 0) {
                foreach ($images as $image) {
                    $id = self::uploadImageToFacebook($image);
                    if ($id != -1) {
                        $media = new stdClass();
                        $media->media_fbid = $id;
                        array_push($data->attached_media, $media);
                    }
                }
            }

            $data->attached_media = json_encode($data->attached_media);
            $curl = curl_init();
            $url = self::FB_ROOT . "/feed?published=true&access_token=" . self::FB_TOKEN;

            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "access_token: " . self::FB_TOKEN
                ),
            ));
            CURL_SETOPT($curl, CURLOPT_POSTFIELDS, $data);

            $response = curl_exec($curl);

            curl_close($curl);

            $id = isset(json_decode($response)->id) ? json_decode($response)->id : -1;

            return $id;
        } catch (Exception $e) {
            return -1;
        }
    }

    public static function addCommentToFacebookPost(string $post_id, string $comment)
    {
        try {
            $data = new stdClass();
            $data->message = "$comment";
            $curl = curl_init();
            $url = "https://graph.facebook.com/v13.0/$post_id/comments?access_token=" . self::FB_TOKEN;
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'POST',
                CURLOPT_HTTPHEADER => array(
                    "access_token: " . self::FB_TOKEN
                ),
            ));
            CURL_SETOPT($curl, CURLOPT_POSTFIELDS, $data);

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;
        } catch (Exception $th) {
        }
    }

    public static function deleteFacebookPost(string $post_id)
    {
        try {
            $curl = curl_init();
            $url = self::FB_ROOT . "/$post_id?access_token=" . self::FB_TOKEN;
            curl_setopt_array($curl, array(
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_ENCODING => '',
                CURLOPT_MAXREDIRS => 10,
                CURLOPT_TIMEOUT => 0,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
                CURLOPT_CUSTOMREQUEST => 'DELETE',
                CURLOPT_HTTPHEADER => array(
                    "access_token: " . self::FB_TOKEN
                ),
            ));

            $response = curl_exec($curl);

            curl_close($curl);

            return $response;
        } catch (Exception $th) {
        }
    }

    public static function addFacebookPost(string $post_id, int $id, bool $is_store = true): bool
    {
        if ($is_store) {
            return Db::getInstance()->insert("sd_facebook", [
                "id_sd_store" => $id,
                "post_id" => $post_id
            ]);
        } else {
            return Db::getInstance()->insert("sd_facebook", [
                "id_product" => $id,
                "post_id" => $post_id
            ]);
        }
    }

    public function getFacebookPostId(int $id, bool $is_store = true)
    {
        $q = new DbQuery();
        $q->select("post_id")->from("sd_facebook");

        if($is_store){
            $q->where("id_sd_store = $id");
        }else{
            $q->where("id_product = $id");
        }
        $result = Db::getInstance()->executeS($q, true)->fetch();
        if($result && !empty($result)){
            return $result["post_id"];
        }
        return null;
    }

    public function deleteFacebookPostId(int $id, bool $is_store = true)
    {
        if ($is_store) {
            return Db::getInstance()->delete("sd_facebook", "id_sd_store = $id");
        } else {
            return Db::getInstance()->delete("sd_facebook", "id_product = $id");
        }
    }
}
