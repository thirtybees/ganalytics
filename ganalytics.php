<?php
/**
 * 2007-2016 PrestaShop
 *
 * thirty bees is an extension to the PrestaShop e-commerce software developed by PrestaShop SA
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2017-2024 thirty bees
 * @copyright 2007-2016 PrestaShop SA
 * @license   https://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 * PrestaShop is an internationally registered trademark & property of PrestaShop SA
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class Ganalytics
 */
class Ganalytics extends Module
{
    /**
     * @var int[] $products
     */
    protected static $products = [];

    /**
     * @var int $js_state
     */
    protected $js_state = 0;

    /**
     * @var int $eligible
     */
    protected $eligible = 0;

    /**
     * @var int $filterable
     */
    protected $filterable = 1;

    /**
     * @var int $debugAnalytics
     */
    protected $debugAnalytics = 0;

    /**
     * Ganalytics constructor.
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function __construct()
    {
        $this->name = 'ganalytics';
        $this->tab = 'analytics_stats';
        $this->version = '3.4.1';
        $this->author = 'thirty bees';
        $this->bootstrap = true;
        $this->need_instance = false;

        parent::__construct();

        $this->displayName = $this->l('Google Analytics');
        $this->description = $this->l('Gain clear insights into important metrics about your customers, using Google Analytics');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall Google Analytics? You will lose all the data related to this module.');
        $this->controllers = ['ajax', 'track'];
    }

    /**
     * Install this module
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function install()
    {
        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install()
            || !$this->installTab()
            || !$this->registerHook('header')
            || !$this->registerHook('adminOrder')
            || !$this->registerHook('footer')
            || !$this->registerHook('home')
            || !$this->registerHook('productfooter')
            || !$this->registerHook('orderConfirmation')
            || !$this->registerHook('backOfficeHeader')
            || !$this->registerHook('actionProductCancel')
            || !$this->registerHook('actionCartSave')
        ) {
            return false;
        }

        Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ganalytics`');

        if (!Db::getInstance()->Execute(
            '
			CREATE TABLE IF NOT EXISTS `'._DB_PREFIX_.'ganalytics` (
				`id_google_analytics` INT(11) NOT NULL AUTO_INCREMENT,
				`id_order` INT(11) NOT NULL,
				`id_customer` INT(10) NOT NULL,
				`id_shop` INT(11) NOT NULL,
				`sent` TINYINT(1) DEFAULT NULL,
				`date_add` DATETIME DEFAULT NULL,
				PRIMARY KEY (`id_google_analytics`),
				KEY `id_order` (`id_order`),
				KEY `sent` (`sent`)
			) ENGINE='._MYSQL_ENGINE_.' DEFAULT CHARSET=utf8 AUTO_INCREMENT=1'
        )
        ) {
            return $this->uninstall();
        }

        Configuration::updateValue('GA_OPTIMIZE_TIMER', 4000);

        return true;
    }

    /**
     * Install a tab for this module
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function installTab()
    {
        $tab = new Tab();
        $tab->active = 0;
        $tab->class_name = 'AdminGanalyticsAjax';
        $tab->name = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tab->name[$lang['id_lang']] = 'Google Analytics Ajax';
        }
        $tab->id_parent = -1; //(int)Tab::getIdFromClassName('AdminAdmin');
        $tab->module = $this->name;

        return $tab->add();
    }

    /**
     * Uninstall this module
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstall()
    {
        if (!$this->uninstallTab() || !parent::uninstall()) {
            return false;
        }

        return Db::getInstance()->Execute('DROP TABLE IF EXISTS `'._DB_PREFIX_.'ganalytics`');
    }

    /**
     * Uninstall tabs for this module
     *
     * @return bool
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function uninstallTab()
    {
        $idTab = (int) Tab::getIdFromClassName('AdminGanalyticsAjax');
        if ($idTab) {
            $tab = new Tab($idTab);

            return $tab->delete();
        }

        return true;
    }

    /**
     * back office module configuration page content
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function getContent()
    {
        $output = '';
        if (Tools::isSubmit('submit'.$this->name)) {
            $gaAccountId = Tools::getValue('GA_ACCOUNT_ID');
            $gaOptimizeId = Tools::getValue('GA_OPTIMIZE_ID');
            $gaOptimizeTimer = Tools::getValue('GA_OPTIMIZE_TIMER');
            if (!empty($gaAccountId)) {
                Configuration::updateValue('GA_ACCOUNT_ID', $gaAccountId);
                Configuration::updateValue('GA_OPTIMIZE_ID', $gaOptimizeId);
                Configuration::updateValue('GA_OPTIMIZE_TIMER', $gaOptimizeTimer);
                Configuration::updateValue('GANALYTICS_CONFIGURATION_OK', true);
                $output .= $this->displayConfirmation($this->l('Account ID updated successfully'));
            }
            $gaUseridEnabled = Tools::getValue('GA_USERID_ENABLED');
            if (null !== $gaUseridEnabled) {
                Configuration::updateValue('GA_USERID_ENABLED', (bool) $gaUseridEnabled);
                $output .= $this->displayConfirmation($this->l('Settings for User ID updated successfully'));
            }
            $gaIPEnabled = Tools::getValue('GA_IP_ENABLED');
            if (null !== $gaIPEnabled) {
                Configuration::updateValue('GA_IP_ENABLED', (bool) $gaIPEnabled);
                $output .= $this->displayConfirmation($this->l('Settings for IP collection updated successfully'));
            }
        }

        $output .= $this->displayForm();

        return $this->display(__FILE__, 'views/templates/admin/configuration.tpl').$output;
    }

    /**
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function displayForm()
    {
        // Get default language
        $defaultLang = (int) Configuration::get('PS_LANG_DEFAULT');

        $helper = new HelperForm();

        // Module, token and currentIndex
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex.'&configure='.$this->name;

        // Language
        $helper->default_form_language = $defaultLang;
        $helper->allow_employee_form_lang = $defaultLang;

        // Title and toolbar
        $helper->title = $this->displayName;
        $helper->show_toolbar = true;        // false -> remove toolbar
        $helper->toolbar_scroll = true;      // yes - > Toolbar is always visible on the top of the screen.
        $helper->submit_action = 'submit'.$this->name;
        $helper->toolbar_btn = [
            'save' => [
                'desc' => $this->l('Save'),
                'href' => AdminController::$currentIndex.'&configure='.$this->name.'&save'.$this->name.'&token='.Tools::getAdminTokenLite('AdminModules'),
            ],
            'back' => [
                'href' => AdminController::$currentIndex.'&token='.Tools::getAdminTokenLite('AdminModules'),
                'desc' => $this->l('Back to list'),
            ],
        ];

        $fieldsForm = [];
        // Init Fields form array
        $fieldsForm[0]['form'] = [
            'legend' => [
                'title' => $this->l('Settings'),
            ],
            'input'  => [
                [
                    'type'     => 'text',
                    'label'    => $this->l('Google Analytics Tracking ID'),
                    'name'     => 'GA_ACCOUNT_ID',
                    'size'     => 20,
                    'required' => true,
                    'hint'     => $this->l('This information is available in your Google Analytics account'),
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Enable User ID tracking'),
                    'name'   => 'GA_USERID_ENABLED',
                    'hint'   => $this->l('The User ID is set at the property level. To find a property, click Admin, then select an account and a property. From the Property column, click Tracking Info then User ID'),
                    'values' => [
                        [
                            'id'    => 'ga_userid_enabled',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'ga_userid_disabled',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'   => 'radio',
                    'label'  => $this->l('Anonymize IP addresses'),
                    'name'   => 'GA_IP_ENABLED',
                    'hint'   => $this->l('This is used to comply with EU laws on data collection'),
                    'values' => [
                        [
                            'id'    => 'ga_ip_enabled',
                            'value' => 1,
                            'label' => $this->l('Enabled'),
                        ],
                        [
                            'id'    => 'ga_ip_disabled',
                            'value' => 0,
                            'label' => $this->l('Disabled'),
                        ],
                    ],
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Google Optimize Testing ID'),
                    'name'     => 'GA_OPTIMIZE_ID',
                    'size'     => 20,
                    'required' => false,
                    'hint'     => $this->l('This is given to you in your Google Optimize account and is used for A/B testing.'),
                ],
                [
                    'type'     => 'text',
                    'label'    => $this->l('Google Optimize Timer'),
                    'name'     => 'GA_OPTIMIZE_TIMER',
                    'size'     => 20,
                    'required' => false,
                    'hint'     => $this->l('Time after which the script is loaded asynchronously'),
                ],                
            ],
            'submit' => [
                'title' => $this->l('Save'),
            ],
        ];

        // Load current value
        $helper->fields_value['GA_ACCOUNT_ID'] = Configuration::get('GA_ACCOUNT_ID');
        $helper->fields_value['GA_OPTIMIZE_ID'] = Configuration::get('GA_OPTIMIZE_ID');
        $helper->fields_value['GA_USERID_ENABLED'] = Configuration::get('GA_USERID_ENABLED');
        $helper->fields_value['GA_IP_ENABLED'] = Configuration::get('GA_IP_ENABLED');
        $helper->fields_value['GA_OPTIMIZE_TIMER'] = Configuration::get('GA_OPTIMIZE_TIMER');

        return $helper->generateForm($fieldsForm);
    }

    /**
     * Hook to `head` tags
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookHeader()
    {
        if (Configuration::get('GA_ACCOUNT_ID')) {
            $this->context->controller->addJs($this->_path.'views/js/GoogleAnalyticActionLib.js');

            return $this->getGoogleAnalyticsTag();
        }

        return '';
    }

    /**
     * @param bool $backOffice
     *
     * @return string
     *
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function getGoogleAnalyticsTag($backOffice = false)
    {
        $userId = null;
        if (Configuration::get('GA_USERID_ENABLED') &&
            $this->context->customer && $this->context->customer->isLogged()
        ) {
            $userId = (int) $this->context->customer->id;
        }

        $this->context->smarty->assign([
            'GA_ACCOUNT_ID' => Configuration::get('GA_ACCOUNT_ID'),
            'userId'        => $userId,
            'IP_ENABLED'    => Configuration::get('GA_IP_ENABLED'),
            'backOffice'    => $backOffice,
            'serverTrackUrl'=> Context::getContext()->link->getModuleLink($this->name, 'track'),
        ]);

        return $this->display(__FIlE__, 'views/templates/hook/analyticsjs.tpl');
    }

    /**
     * To track transactions
     *
     * @param array $params
     *
     * @return string|null
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookOrderConfirmation($params)
    {
        /** @var Order $order */
        $order = $params['objOrder'];
        if (Validate::isLoadedObject($order) && $order->getCurrentState() != (int) Configuration::get('PS_OS_ERROR')) {
            $gaOrderSent = Db::getInstance()->getValue('SELECT id_order FROM `'._DB_PREFIX_.'ganalytics` WHERE id_order = '.(int) $order->id);
            if ($gaOrderSent === false) {
                Db::getInstance()->Execute('INSERT INTO `'._DB_PREFIX_.'ganalytics` (id_order, id_shop, sent, date_add) VALUES ('.(int) $order->id.', '.(int) $this->context->shop->id.', 0, NOW())');
                if ($order->id_customer == $this->context->cookie->id_customer) {

                    $gaScripts = '';

                    if ((int)$order->id_carrier > 0) { // Not a order with virtual products only
                        $carrierName = Db::getInstance()->getValue('SELECT name FROM ' . _DB_PREFIX_ . 'carrier WHERE id_carrier = ' . (int)$order->id_carrier);
                        if ($carrierName) {
                            $gaScripts .= 'MBG.addCheckoutOption(2, \'' . $carrierName . '\');' . "\n";
                        }
                    }

                    $orderProducts = [];
                    $cart = new Cart($order->id_cart);
                    foreach ($cart->getProducts() as $orderProduct) {
                        $orderProducts[] = $this->wrapProduct($orderProduct, [], 0, true);
                    }

                    $gaScripts .= 'MBG.addCheckoutOption(3, \''.$order->payment.'\');' . "\n";

                    $transaction = [
                        'id'          => $order->id,
                        'affiliation' => (Shop::isFeatureActive()) ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
                        'revenue'     => $order->total_paid,
                        'shipping'    => $order->total_shipping,
                        'tax'         => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                        'url'         => $this->context->link->getModuleLink('ganalytics', 'ajax', [], true),
                        'customer'    => $order->id_customer,
                    ];
                    $gaScripts .= $this->addTransaction($orderProducts, $transaction);

                    $this->js_state = 1;

                    return $this->generateAnayticsJs($gaScripts);
                }
            }
        }
        return null;
    }

    /**
     * wrap product to provide a standard product information for google analytics script
     *
     * @param array $product
     * @param array|null $extras
     * @param int $index
     * @param bool $full
     *
     * @return array
     *
     * @throws PrestaShopException
     */
    public function wrapProduct($product, $extras, $index = 0, $full = false)
    {
        $variant = null;
        if (isset($product['attributes_small'])) {
            $variant = $product['attributes_small'];
        } elseif (isset($extras['attributes_small'])) {
            $variant = $extras['attributes_small'];
        }

        $productQty = 1;
        if (isset($extras['qty'])) {
            $productQty = (int)$extras['qty'];
        } elseif (isset($product['cart_quantity'])) {
            $productQty = (int)$product['cart_quantity'];
        }

        $productId = 0;
        if (!empty($product['id_product'])) {
            $productId = (int)$product['id_product'];
        } else {
            if (!empty($product['id'])) {
                $productId = (int)$product['id'];
            }
        }

        if (!empty($product['id_product_attribute'])) {
            $productId .= '-' . (int)$product['id_product_attribute'];
        }

        $productType = 'typical';
        if (isset($product['pack']) && $product['pack'] == 1) {
            $productType = 'pack';
        } elseif (isset($product['virtual']) && $product['virtual'] == 1) {
            $productType = 'virtual';
        }

        if ($full) {
            $gaProduct = [
                'id'       => $productId,
                'name'     => Tools::str2url($product['name']),
                'category' => isset($product['category']) ? Tools::str2url($product['category']) : '',
                'brand'    => isset($product['manufacturer_name']) ? Tools::str2url($product['manufacturer_name']) : '',
                'variant'  => Tools::str2url($variant),
                'type'     => $productType,
                'position' => (int)$index,
                'quantity' => $productQty,
                'list'     => $this->getControllerName(),
                'url'      => isset($product['link']) ? urlencode($product['link']) : '',
                'price'    => number_format((float)$product['price'], 2, '.', ''),
            ];
        } else {
            $gaProduct = [
                'id'   => $productId,
                'name' => Tools::str2url($product['name']),
            ];
        }

        return $gaProduct;
    }

    /**
     * add order transaction
     *
     * @param array $products
     * @param array $order
     *
     * @return string
     */
    public function addTransaction($products, $order)
    {
        if (!is_array($products)) {
            return '';
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).');';
        }

        return $js.'MBG.addTransaction('.json_encode($order).');';
    }

    /**
     * Generate Google Analytics js
     *
     * @param string $jsCode
     * @param int $backoffice
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    protected function generateAnayticsJs($jsCode, $backoffice = 0)
    {
        if (Configuration::get('GA_ACCOUNT_ID')) {
            $generatedJsCode = '';

            if (!empty($jsCode)) {
                if ($backoffice) {
                    $jsCode .= 'MBG.setCampaign(\'backoffice-orders\',\'backoffice\',\'cms\');';
                }

                $this->context->smarty->assign('jsCode', $jsCode);

                $generatedJsCode .= $this->display(__FILE__, 'views/templates/hook/currencyjs.tpl');
            }

            if ((int) $this->js_state !== 1 && (int) $backoffice === 0) {
                $generatedJsCode .= '<script type="text/javascript">ga(\'send\', \'pageview\');</script>';
            }

            return $generatedJsCode;
        }

        return '';
    }

    /**
     * hook footer to load JS script for standards actions such as product clicks
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookFooter()
    {
        $gaScripts = '';
        $this->js_state = 0;

        if (isset($this->context->cookie->ga_cart)) {
            $this->filterable = 0;

            $gacarts = @json_decode($this->context->cookie->ga_cart, true);
            if (is_array($gacarts)) {
                foreach ($gacarts as $gacart) {
                    if ($gacart['quantity'] > 0) {
                        $gaScripts .= 'MBG.addToCart('.json_encode($gacart).');';
                    } elseif ($gacart['quantity'] < 0) {
                        $gacart['quantity'] = abs($gacart['quantity']);
                        $gaScripts .= 'MBG.removeFromCart('.json_encode($gacart).');';
                    }
                }
            }
            unset($this->context->cookie->ga_cart);
        }

        $controllerName = $this->getControllerName();
        $templateVars = $this->context->smarty->getTemplateVars('products');
        if (is_string($templateVars)) {
            $templateVars = [$templateVars];
        }
        $products = $this->wrapProducts($templateVars, [], true);

        if ($controllerName == 'order' || $controllerName == 'orderopc') {
            $this->js_state = 1;
            $this->eligible = 1;
            $step = (int)Tools::getValue('step');
            $gaScripts .= $this->addProductFromCheckout($products);
            $gaScripts .= 'MBG.addCheckout(\''.(int) $step.'\');';
        }

        $confirmationHookId = (int) Hook::getIdByName('orderConfirmation');
        if (isset(Hook::$executed_hooks[$confirmationHookId])) {
            $this->eligible = 1;
        }

        if (isset($products) && count($products) && $controllerName != 'index') {
            if ($this->eligible == 0) {
                $gaScripts .= $this->addProductImpression($products);
            }
            $gaScripts .= $this->addProductClick($products);
        }

        return $this->generateAnayticsJs($gaScripts);
    }

    /**
     * wrap products to provide a standard products information for google analytics script
     *
     * @param array $products
     * @param array $extras
     * @param bool $full
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function wrapProducts($products, $extras = [], $full = false)
    {
        $resultProducts = [];
        if (!is_array($products)) {
            return [];
        }

        $currency = new Currency($this->context->currency->id);
        $usetax = (Product::getTaxCalculationMethod((int) $this->context->customer->id) != PS_TAX_EXC);

        if (count($products) > 20) {
            $full = false;
        } else {
            $full = true;
        }

        foreach ($products as $index => $product) {
            if ($product instanceof Product) {
                $product = (array) $product;
            }

            if (!isset($product['price'])) {
                $product['price'] = (float) Tools::displayPrice(Product::getPriceStatic((int) $product['id_product'], $usetax), $currency);
            }
            $resultProducts[] = $this->wrapProduct($product, $extras, $index, $full);
        }

        return $resultProducts;
    }

    /**
     * Add product checkout info
     *
     * @param array $products
     *
     * @return string
     */
    public function addProductFromCheckout($products)
    {
        if (!is_array($products)) {
            return '';
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).');';
        }

        return $js;
    }

    /**
     * add product impression js and product click js
     *
     * @param array $products
     *
     * @return string
     */
    public function addProductImpression($products)
    {
        if (!is_array($products)) {
            return '';
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.add('.json_encode($product).",'',true);";
        }

        return $js;
    }

    /**
     * @param array $products
     *
     * @return string
     */
    public function addProductClick($products)
    {
        if (!is_array($products)) {
            return '';
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.addProductClick('.json_encode($product).');';
        }

        return $js;
    }

    /**
     * hook home to display generate the product list associated to home featured, news products and best sellers Modules
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookHome()
    {
        $gaScripts = '';

        // Home featured products
        if ($this->isModuleEnabled('homefeatured')) {
            $category = new Category($this->context->shop->getCategory(), $this->context->language->id);
            $homeFeaturedProducts = $this->wrapProducts(
                $category->getProducts(
                    (int) Context::getContext()->language->id,
                    1,
                    (Configuration::get('HOME_FEATURED_NBR') ? (int) Configuration::get('HOME_FEATURED_NBR') : 8),
                    'position'
                ),
                [],
                true
            );
            $gaScripts .= $this->addProductImpression($homeFeaturedProducts).$this->addProductClick($homeFeaturedProducts);
        }

        // New products
        if ($this->isModuleEnabled('blocknewproducts') && (Configuration::get('PS_NB_DAYS_NEW_PRODUCT')
                || Configuration::get('PS_BLOCK_NEWPRODUCTS_DISPLAY'))
        ) {
            $newProducts = Product::getNewProducts((int) $this->context->language->id, 0, (int) Configuration::get('NEW_PRODUCTS_NBR'));
            $newProductsList = $this->wrapProducts($newProducts, [], true);
            $gaScripts .= $this->addProductImpression($newProductsList).$this->addProductClick($newProductsList);
        }

        // Best Sellers
        if ($this->isModuleEnabled('blockbestsellers') && (!Configuration::get('PS_CATALOG_MODE')
                || Configuration::get('PS_BLOCK_BESTSELLERS_DISPLAY'))
        ) {
            $gaHomebestsellProductList = $this->wrapProducts(ProductSale::getBestSalesLight((int) $this->context->language->id, 0, 8), [], true);
            $gaScripts .= $this->addProductImpression($gaHomebestsellProductList).$this->addProductClick($gaHomebestsellProductList);
        }

        $this->js_state = 1;

        return $this->generateAnayticsJs($this->filter($gaScripts));
    }

    /**
     * hook home to display generate the product list associated to home featured, news products and best sellers Modules
     *
     * @param string $name
     *
     * @return false|int
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function isModuleEnabled($name)
    {

        if (Module::isEnabled($name)) {
            $module = Module::getInstanceByName($name);

            return $module->isRegisteredInHook('home');
        } else {
            return false;
        }
    }

    /**
     * @param string $gaScripts
     *
     * @return string
     */
    protected function filter($gaScripts)
    {
        if ($this->filterable = 1) {
            return implode(';', array_unique(explode(';', $gaScripts)));
        }

        return $gaScripts;
    }

    /**
     * hook product page footer to load JS for product details view
     *
     * @param array $params
     *
     * @return string
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookProductFooter($params)
    {
        $controllerName = $this->getControllerName();
        if ($controllerName === 'product') {
            // Add product view
            $gaProduct = $this->wrapProduct((array) $params['product'], null, 0, true);
            $js = 'MBG.addProductDetailView('.json_encode($gaProduct).');';

            if (isset($_SERVER['HTTP_REFERER']) && strpos($_SERVER['HTTP_REFERER'], $_SERVER['HTTP_HOST']) > 0) {
                $js .= $this->addProductClickByHttpReferal([$gaProduct]);
            }

            $this->js_state = 1;

            return $this->generateAnayticsJs($js);
        }
        return null;
    }

    /**
     * @param array $products
     *
     * @return string|void
     */
    public function addProductClickByHttpReferal($products)
    {
        if (!is_array($products)) {
            return;
        }

        $js = '';
        foreach ($products as $product) {
            $js .= 'MBG.addProductClickByHttpReferal('.json_encode($product).');';
        }

        return $js;
    }

    /**
     * Hook admin order to send transactions and refunds details
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookAdminOrder()
    {
        echo $this->generateAnayticsJs($this->context->cookie->ga_admin_refund, 1);
        unset($this->context->cookie->ga_admin_refund);
    }

    /**
     * admin office header to add google analytics js
     *
     * @return string
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     * @throws SmartyException
     */
    public function hookBackOfficeHeader()
    {
        Media::addJsDef(['baseDir' => Tools::getShopProtocol().Tools::getHttpHost().__PS_BASE_URI__]);

        $js = '';
        /** @var AdminController $controller */
        $controller = $this->context->controller;
        if (strcmp(Tools::getValue('configure'), $this->name) === 0) {
            $controller->addCSS($this->_path.'views/css/ganalytics.css');
        }

        $gaAccountId = Configuration::get('GA_ACCOUNT_ID');

        if (!empty($gaAccountId) && $this->active) {
            $controller->addJs($this->_path.'views/js/GoogleAnalyticActionLib.js');
            $this->context->smarty->assign('GA_ACCOUNT_ID', $gaAccountId);

            $gaScripts = '';
            if ($controller->controller_name == 'AdminOrders') {
                $orderId = (int)Tools::getValue('id_order');
                if ($orderId) {
                    $order = new Order($orderId);
                    if (Validate::isLoadedObject($order) && strtotime('+1 day', strtotime($order->date_add)) > time()) {
                        $gaOrderSent = Db::getInstance()->getValue('SELECT id_order FROM `'._DB_PREFIX_.'ganalytics` WHERE id_order = '. $orderId);
                        if ($gaOrderSent === false) {
                            Db::getInstance()->Execute('INSERT IGNORE INTO `'._DB_PREFIX_.'ganalytics` (id_order, id_shop, sent, date_add) VALUES ('. $orderId .', '.(int) $this->context->shop->id.', 0, NOW())');
                        }
                    }
                } else {
                    $gaOrderRecords = Db::getInstance()->ExecuteS('SELECT * FROM `'._DB_PREFIX_.'ganalytics` WHERE sent = 0 AND id_shop = \''.(int) $this->context->shop->id.'\' AND DATE_ADD(date_add, INTERVAL 30 MINUTE) < NOW()');

                    if ($gaOrderRecords) {
                        foreach ($gaOrderRecords as $row) {
                            $transaction = $this->wrapOrder($row['id_order']);
                            if (!empty($transaction)) {
                                Db::getInstance()->execute('UPDATE `'._DB_PREFIX_.'ganalytics` SET date_add = NOW(), sent = 1 WHERE id_order = '.(int) $row['id_order'].' AND id_shop = \''.(int) $this->context->shop->id.'\'');
                                $transaction = json_encode($transaction);
                                $gaScripts .= 'MBG.addTransaction('.$transaction.');';
                            }
                        }
                    }

                }
            }

            return $js.$this->getGoogleAnalyticsTag(true).$this->generateAnayticsJs($gaScripts, 1);
        } else {
            return $js;
        }
    }

    /**
     * Return a detailed transaction for Google Analytics
     *
     * @param int $idOrder
     *
     * @return array
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function wrapOrder($idOrder)
    {
        $order = new Order((int) $idOrder);

        if (Validate::isLoadedObject($order)) {
            return [
                'id'          => $idOrder,
                'affiliation' => Shop::isFeatureActive() ? $this->context->shop->name : Configuration::get('PS_SHOP_NAME'),
                'revenue'     => $order->total_paid,
                'shipping'    => $order->total_shipping,
                'tax'         => $order->total_paid_tax_incl - $order->total_paid_tax_excl,
                'url'         => $this->context->link->getAdminLink('AdminGanalyticsAjax'),
                'customer'    => $order->id_customer,
            ];
        }

        return [];
    }

    /**
     * Hook admin office header to add google analytics js
     *
     * @param array $params
     *
     * @throws PrestaShopDatabaseException
     * @throws PrestaShopException
     */
    public function hookActionProductCancel($params)
    {
        $quantityRefunded = Tools::getValue('cancelQuantity');
        $gaScripts = '';
        foreach ($quantityRefunded as $idOrderDetail => $qty) {
            // Display GA refund product
            $orderDetail = new OrderDetail($idOrderDetail);
            if (Validate::isLoadedObject($orderDetail)) {
                $gaScripts .= 'MBG.add(' . json_encode(
                        [
                            'id' => empty($orderDetail->product_attribute_id) ? $orderDetail->product_id : $orderDetail->product_id . '-' . $orderDetail->product_attribute_id,
                            'quantity' => (int)$qty,
                        ]
                    ) . ');';
            }
        }
        $this->context->cookie->ga_admin_refund = $gaScripts.'MBG.refundByProduct('.json_encode(['id' => $params['order']->id]).');';
    }

    /**
     * hook save cart event to implement addtocart and remove from cart functionality
     *
     * @throws PrestaShopException
     */
    public function hookActionCartSave()
    {
        if (!isset($this->context->cart)) {
            return;
        }

        $productIdParameter = (int)Tools::getValue('id_product');

        if (! $productIdParameter) {
            return;
        }

        $cart = [
            'controller'   => $this->getControllerName(),
            'addAction'    => Tools::getValue('add') ? 'add' : '',
            'removeAction' => Tools::getValue('delete') ? 'delete' : '',
            'extraAction'  => Tools::getValue('op'),
            'qty'          => (int) Tools::getValue('qty', 1),
        ];

        $cartProducts = $this->context->cart->getProducts();
        if (isset($cartProducts) && count($cartProducts)) {
            foreach ($cartProducts as $cartProduct) {
                if ((int)$cartProduct['id_product'] === $productIdParameter) {
                    $addProduct = $cartProduct;
                }
            }
        }

        if ($cart['removeAction'] == 'delete') {
            $addProductObject = new Product($productIdParameter, true, (int) Configuration::get('PS_LANG_DEFAULT'));
            if (Validate::isLoadedObject($addProductObject)) {
                $addProduct['name'] = $addProductObject->name;
                $addProduct['manufacturer_name'] = $addProductObject->manufacturer_name;
                $addProduct['category'] = $addProductObject->category;
                $addProduct['reference'] = $addProductObject->reference;
                $addProduct['link_rewrite'] = $addProductObject->link_rewrite;
                $addProduct['link'] = $addProductObject->link_rewrite;
                $addProduct['price'] = $addProductObject->price;
                $addProduct['ean13'] = $addProductObject->ean13;
                $addProduct['id_product'] = (int)$addProductObject->id;
                $addProduct['id_category_default'] = (int)$addProductObject->id_category_default;
                $addProduct['out_of_stock'] = $addProductObject->out_of_stock;
                $addProduct = Product::getProductProperties((int) Configuration::get('PS_LANG_DEFAULT'), $addProduct);
            }
        }

        if (isset($addProduct) && !in_array($productIdParameter, self::$products)) {
            self::$products[] = $productIdParameter;
            $gaProducts = $this->wrapProduct($addProduct, $cart, 0, true);

            if (array_key_exists('id_product_attribute', $gaProducts) && $gaProducts['id_product_attribute'] != 0) {
                $idProduct = $gaProducts['id_product_attribute'];
            } else {
                $idProduct = $productIdParameter;
            }

            if (isset($this->context->cookie->ga_cart)) {
                $gacart = @json_decode($this->context->cookie->ga_cart, true);
            } else {
                $gacart = [];
            }

            if ($cart['removeAction'] == 'delete') {
                $gaProducts['quantity'] = -1;
            } elseif ($cart['extraAction'] == 'down') {
                if (array_key_exists($idProduct, $gacart)) {
                    $gaProducts['quantity'] = $gacart[$idProduct]['quantity'] - $cart['qty'];
                } else {
                    $gaProducts['quantity'] = $cart['qty'] * -1;
                }
            } elseif ((int)Tools::getValue('step') <= 0) {
                // Sometimes cartsave is called in checkout
                if (array_key_exists($idProduct, $gacart)) {
                    $gaProducts['quantity'] = $gacart[$idProduct]['quantity'] + $cart['qty'];
                }
            }

            $gacart[$idProduct] = $gaProducts;
            $this->context->cookie->ga_cart = json_encode($gacart);
        }
    }

    /**
     * @param string $function
     * @param string $log
     *
     * @return void
     */
    protected function debugLog($function, $log)
    {
        if (!$this->debugAnalytics) {
            return;
        }

        $myFile = _PS_MODULE_DIR_.$this->name.'/logs/analytics.log';
        $fh = fopen($myFile, 'a');
        fwrite($fh, date('F j, Y, g:i a').' '.$function."\n");
        fwrite($fh, print_r($log, true)."\n\n");
        fclose($fh);
    }

    /**
     * Returns controller name
     *
     * @return string
     */
    protected function getControllerName()
    {
        $controller = Tools::getValue('controller');
        if (Validate::isControllerName($controller)) {
            return $controller;
        }
        return '';
    }
}
