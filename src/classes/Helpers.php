<?php

namespace NarrysTech\Api_Rest\classes;

use PrestaShop\PrestaShop\Adapter\Entity\Db;

class Helpers
{

    public static function response_json(array $datas = [], int $status = 200, bool $success = true): string
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
}
