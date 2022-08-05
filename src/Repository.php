<?php

namespace NarrysTech\Api_Rest;

use PrestaShop\PrestaShop\Adapter\Entity\Db;

class Repository
{

    /**
     * Module
     *
     * @var \Module
     */
    public $module = null;

    /**
     * Undocumented function
     *
     * @param \Module $module
     */
    public function __construct($module)
    {
        $this->module = $module;
    }

    public function installDatabase(): bool
    {
        $sql = array();

        $sql[] = "DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "customer`;";

        $sql[] = "CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "customer` (
            `id_customer` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `id_shop_group` int(11) unsigned NOT NULL DEFAULT '1',
            `id_shop` int(11) unsigned NOT NULL DEFAULT '1',
            `id_gender` int(10) unsigned DEFAULT '1',
            `id_default_group` int(10) unsigned NOT NULL DEFAULT '1',
            `id_lang` int(10) unsigned DEFAULT NULL,
            `id_sponsorship` int(10) unsigned DEFAULT NULL,
            `id_risk` int(10) unsigned NOT NULL DEFAULT '1',
            `company` varchar(255) DEFAULT NULL,
            `siret` varchar(14) DEFAULT NULL,
            `ape` varchar(5) DEFAULT NULL,
            `firstname` varchar(255) DEFAULT NULL,
            `lastname` varchar(255) DEFAULT NULL,
            `username` varchar(255) NOT NULL,
            `email` varchar(255) NOT NULL,
            `passwd` varchar(255) NOT NULL,
            `sponsorship_code` varchar(10) NOT NULL,
            `last_passwd_gen` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `birthday` date DEFAULT NULL,
            `newsletter` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `ip_registration_newsletter` varchar(15) DEFAULT NULL,
            `newsletter_date_add` datetime DEFAULT NULL,
            `optin` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `website` varchar(128) DEFAULT NULL,
            `outstanding_allow_amount` decimal(20,6) NOT NULL DEFAULT '0.000000',
            `show_public_prices` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `max_payment_days` int(10) unsigned NOT NULL DEFAULT '60',
            `secure_key` varchar(32) NOT NULL DEFAULT '-1',
            `note` text,
            `active` tinyint(1) unsigned NOT NULL DEFAULT '0',
            `is_guest` tinyint(1) NOT NULL DEFAULT '0',
            `deleted` tinyint(1) NOT NULL DEFAULT '0',
            `date_add` datetime NOT NULL,
            `date_upd` datetime NOT NULL,
            `reset_password_token` varchar(40) DEFAULT NULL,
            `reset_password_validity` datetime DEFAULT NULL,
            PRIMARY KEY (`id_customer`),
            KEY `customer_email` (`email`),
            KEY `customer_login` (`email`,`passwd`),
            KEY `id_customer_passwd` (`id_customer`,`passwd`),
            KEY `id_gender` (`id_gender`),
            KEY `id_shop_group` (`id_shop_group`),
            KEY `id_shop` (`id_shop`,`date_add`)
        ) ENGINE = " . _MYSQL_ENGINE_ . " DEFAULT CHARSET = utf8;";

        foreach ($sql as $query) {
            if (Db::getInstance()->execute($query) == false) {
                return false;
            }
        }

        return true;
    }

    public function uninstallDatabase(): bool
    {
        return true;
    }

    public function install()
    {
        return $this->installDatabase() &&
            $this->registerHooks();
    }

    public function uninstall()
    {
        return $this->unregisterHooks() &&
            $this->uninstallDatabase();
    }

    public function registerHooks()
    {
        return $this->module->registerHook('header') &&
            $this->module->registerHook('backOfficeHeader') &&
            $this->module->registerHook('moduleRoutes');
    }

    public function unregisterHooks()
    {
        return $this->module->unregisterHook('moduleRoutes');
    }
}
