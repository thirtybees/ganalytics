<?php
/**
 * Copyright (C) 2017-2024 thirty bees
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.md.
 * It is also available through the world-wide-web at this URL:
 * https://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@thirtybees.com so we can send you a copy immediately.
 *
 * @author    thirty bees <modules@thirtybees.com>
 * @copyright 2017-2024 thirty bees
 * @license   Academic Free License (AFL 3.0)
 */

if (!defined('_TB_VERSION_')) {
    exit;
}

/**
 * Class GanalyticsTrackModuleFrontController
 */
class GanalyticsTrackModuleFrontController extends ModuleFrontController
{

    const URL = 'https://www.google-analytics.com/collect';

    /**
     * Initialize the content of this controller
     *
     * @throws PrestaShopException
     */
    public function initContent()
    {
        $accountId = Configuration::get('GA_ACCOUNT_ID');
        $clientId = $this->getClientId();
        $page = Tools::getValue('page');
        if ($accountId && $clientId && $page) {
            $guzzle = new \GuzzleHttp\Client([
                'verify' => false,
                'timeout' => 15,
            ]);
            try {
                $guzzle->post(static::URL, [
                    'form_params' => [
                        'v' => '1',
                        't' => 'pageview',
                        'tid' => $accountId,
                        'cid' => $clientId,
                        'dp' => $page
                    ]
                ]);
            } catch (\GuzzleHttp\Exception\GuzzleException $ignored) {
            }
            die('ok');
        } else {
            die('failure');
        }
    }

    /**
     * Returns unique session id
     *
     * @return string
     */
    public function getClientId()
    {
        $cookie = $this->context->cookie;
        if (isset($cookie->gaClientId)) {
            $clientId = $cookie->gaClientId;
        } else {
            $clientId = Tools::passwdGen(12, 'ALPHANUMERIC');
            $cookie->gaClientId  = $clientId;
        }
        return $clientId;
    }
}
