<?php
/**
 * @author Penny Black <engineers@pennyblack.io>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PennyBlackWoo\Admin;

use PennyBlack\Exception\PennyBlackException;
use PennyBlackWoo\Api\ClientFactory;

defined( 'ABSPATH' ) || exit;

class Settings
{
    const ENVIRONMENT_LIVE = 'live';
    const ENVIRONMENT_TEST = 'test';

    const FIELD_API_KEY = 'pb_api_key';
    const FIELD_ENABLE_TRANSMIT = 'pb_enable_transmit';
    const FIELD_ENVIRONMENT = 'pb_environment';
    const FIELD_ENABLE_ORDER_EXTENSIONS = 'pb_enable_order_extensions';

    /**
     * Bootstraps the class and hooks required actions & filters.
     */
    public static function register()
    {
        add_filter('woocommerce_settings_tabs_array', __CLASS__ . '::addSettingsTab', 50);
        add_action('woocommerce_settings_tabs_settings_penny_black', __CLASS__ . '::renderSettingsTab');
        add_action('woocommerce_update_options_settings_penny_black', __CLASS__ . '::updateSettings');
    }

    /**
     * @param array $settings_tabs Array of WooCommerce setting tabs & their labels
     *
     * @return array $settings_tabs Array of WooCommerce setting tabs & their labels
     */
    public static function addSettingsTab($settings_tabs)
    {
        $settings_tabs['settings_penny_black'] = 'Penny Black';

        return $settings_tabs;
    }

    /**
     * Get all the settings for this plugin
     *
     * @see woocommerce_admin_fields() function.
     * @return array Array of settings
     */
    public static function getSettings()
    {
        $settings = array(
            'pb_section_general' => array(
                'id' => 'pb_section_general',
                'name' => 'Penny Black Integration Settings',
                'type' => 'title',
                'desc' => '',
            ),
            self::FIELD_ENVIRONMENT => array(
                'id' => self::FIELD_ENVIRONMENT,
                'name' => 'Environment',
                'desc' => 'Normally Live, unless you have a test account',
                'type' => 'select',
                'options' => [
                    self::ENVIRONMENT_LIVE => 'Live',
                    self::ENVIRONMENT_TEST => 'Test',
                ]
            ),
            self::FIELD_API_KEY => array(
                'id' => self::FIELD_API_KEY,
                'name' => 'API Key',
                'type' => 'text',
                'desc' => 'Contact Penny Black support for your API key',
            ),
            self::FIELD_ENABLE_TRANSMIT => array(
                'id' => self::FIELD_ENABLE_TRANSMIT,
                'name' => 'Enable order transmission',
                'type' => 'checkbox',
                'desc' => 'Automatically send orders to Penny Black',
            ),
            self::FIELD_ENABLE_ORDER_EXTENSIONS => array(
                'id' => self::FIELD_ENABLE_ORDER_EXTENSIONS,
                'name' => 'Enable order admin extensions',
                'type' => 'checkbox',
                'desc' => 'Add actions to the Woocommerce order admin pages to trigger prints',
            ),
            'pb_section_printing_end' => array(
                'type' => 'sectionend',
                'id' => 'pb_section_printing',
            ),
        );

        return apply_filters('wc_settings_penny_black_settings', $settings);
    }

    /**
     * Uses the WooCommerce admin fields API to output settings via the @see woocommerce_admin_fields() function.
     *
     * @uses woocommerce_admin_fields()
     * @uses self::get_settings()
     */
    public static function renderSettingsTab()
    {
        woocommerce_admin_fields(self::getSettings());
    }

    /**
     * Persists the settings updated to wp options, validating the API key and disabling transmit if invalid
     */
    public static function updateSettings()
    {
        woocommerce_update_options(self::getSettings());

        $newApiKey = \WC_Admin_Settings::get_option(self::FIELD_API_KEY);

        if ($newApiKey) {
            try {
                self::install();
            } catch (PennyBlackException $e) {
                \WC_Admin_Settings::add_error($e->getMessage());
                self::disableTransmitIfEnabled();
            }
        } else {
            self::disableTransmitIfEnabled();
        }
    }

    public static function disableTransmitIfEnabled()
    {
        $transmitEnabled = \WC_Admin_Settings::get_option(self::FIELD_ENABLE_TRANSMIT);

        if ($transmitEnabled !== 'yes') {
            return;
        }

        $allSettings = self::getSettings();
        $justTransmit = array_filter($allSettings, function ($setting) {
            return $setting['id'] === self::FIELD_ENABLE_TRANSMIT;
        });
        \WC_Admin_Settings::save_fields($justTransmit, [self::FIELD_ENABLE_TRANSMIT => 'off']);

        \WC_Admin_Settings::add_error("You need a valid Penny Black connection to enable order transmission");
    }

    /**
     * @throws PennyBlackException
     */
    public static function install()
    {
        $clientFactory = new ClientFactory();
        $api = $clientFactory->getApiClient();
        $hostname = parse_url(\get_site_url(), PHP_URL_HOST );
        $api->install($hostname);
    }
}