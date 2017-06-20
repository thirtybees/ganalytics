<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

function upgrade_module_2_0_4($object)
{
    Configuration::updateValue('GANALYTICS', '2.0.4');

    return (Db::getInstance()->execute(
            '
                CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ganalytics` (
                  `id_google_analytics` INT(11)            NOT NULL AUTO_INCREMENT,
                  `id_order`            INT(11)            NOT NULL,
                  `sent`                TINYINT(1) DEFAULT NULL,
                  `date_add`            DATETIME   DEFAULT NULL,
                  PRIMARY KEY (`id_google_analytics`),
                  KEY `id_order` (`id_order`),
                  KEY `sent` (`sent`)
                ) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'
        )
        && $object->registerHook('adminOrder')
        && $object->registerHook('footer')
        && $object->registerHook('home')
        && $object->registerHook('backOfficeHeader')
        && $object->registerHook('productfooter'));
}
