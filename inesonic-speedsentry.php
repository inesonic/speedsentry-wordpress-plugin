<?php
/**
 * Plugin Name:       Inesonic SpeedSentry
 * Plugin URI:        http://speed-sentry.com
 * Description:       Site uptime and performance monitoring for WordPress.
 * Version:           1e
 * Author: Inesonic,  LLC
 * Author URI:        http://speed-sentry.com
 * License:           GPLv3 and LGPLv3
 * License URI:       https://downloads.inesonic.com/speedsentry_plugin_license.txt
 * Requires at least: 5.7
 * Requires PHP:      7.4
 * Text Domain:       inesonic-speedsentry
 * Domain Path:       /locale
 ***********************************************************************************************************************
 * Inesonic SpeedSentry - Site Performance Monitoring For Wordpress
 *
 * Copyright 2021, Inesonic, LLC
 *
 * This program is free software: you can redistribute it and/or modify it under the terms of the GNU General Public
 * License as published by the Free Software Foundation, either version 3 of the License, or (at your option) any later
 * version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU General Public License for more
 * details.
 *
 * You should have received a copy of the GNU General Public License along with this program.  If not, see
 * <https://www.gnu.org/licenses/>.
 ***********************************************************************************************************************
 * \file uninstall.php
 *
 * Main plug-in file.
 */

require_once dirname(__FILE__) . '/include/rest-api-v1.php';
require_once dirname(__FILE__) . '/include/options.php';
require_once dirname(__FILE__) . '/include/capabilities.php';
require_once dirname(__FILE__) . '/include/signup-handler.php';
require_once dirname(__FILE__) . '/include/menus.php';
require_once dirname(__FILE__) . '/include/plugin-page.php';
require_once dirname(__FILE__) . '/include/admin-bar.php';

/**
 * Main class for the SpeedSentry plug-in.  This class does the work needed to instantiate the plug-in within the larger
 * WordPress application.  Note that this file contains the only content not containing within the Inesonic\SpeedSentry
 * namespace.
 */
class InesonicSpeedSentry {
    /**
     * Plug-in version.
     */
    const VERSION = '1e';

    /**
     * Plug-in slug.
     */
    const SLUG = 'inesonic-speedsentry';

    /**
     * The REST API namespace.
     */
    const REST_API_NAMESPACE = 'inesonic-speedsentry/v1';

    /**
     * The current REST API version.
     */
    const REST_API_VERSION = 1;

    /**
     * Plug-in descriptive name.
     */
    const NAME = 'Inesonic SpeedSentry';

    /**
     * Shorter plug-in descriptive name.
     */
    const SHORT_NAME = 'SpeedSentry';

    /**
     * Plug-in author.
     */
    const AUTHOR = 'Inesonic, LLC';

    /**
     * Plug-in prefix.
     */
    const PREFIX = 'InesonicSpeedSentry';

    /**
     * Options prefix.
     */
    const OPTIONS_PREFIX = 'inesonic_speedsentry';

    /**
     * The SpeedSentry site name to present to the user.
     */
    const SPEED_SENTRY_SITE_NAME = "SpeedSentry";

    /**
     * The URL to redirect to for customer login.  Do not include an ending /.
     */
    const LOGIN_URL = 'https://speed-sentry.com/customer-sign-in';

    /**
     * The URL to redirect to for customer sign-up.  Do not include an ending /.
     */
    const SIGNUP_URL = 'https://speed-sentry.com/register';

    /**
     * Webhook used to provide secrets from this site during sign-up.
     */
    const REGISTRATION_WEBHOOK = 'https://autonoma.speed-sentry.com/v1/wp_registration';

    /**
     * The minimum supported PHP version.
     */
    const MINIMUM_PHP_VERSION = '7.4';

    /**
     * The maximum supported PHP version.
     */
    const MAXIMUM_PHP_VERSION = '8.0.14';

    /**
     * The minimum supported WordPress version.
     */
    const MINIMUM_WORDPRESS_VERSION = '5.7';

    /**
     * The maximum supported WordPress version.
     */
    const MAXIMUM_WORDPRESS_VERSION = '5.8.3';

    /**
     * Array of required PHP modules.
     */
    const REQUIRED_EXTENSIONS = array();

    /**
     * Plug-in directory.
     */
    public static $dir = '';

    /**
     * Plug-in URL.
     */
    public static $url = '';

    private static $instance;  /* Plug-in instance */

    /**
     * Static method we use to create a single private instance of this plug-in.
     *
     * \return Returns our static plug-in instance.
     */
    public static function instance() {
        if (!isset(self::$instance) || !(self::$instance instanceof InesonicSpeedSentry)) {
            self::$instance = new InesonicSpeedSentry();
            self::$dir      = plugin_dir_path(__FILE__);
            self::$url      = plugin_dir_url(__FILE__);

            spl_autoload_register(array(self::$instance, 'autoloader'));
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $this->options = new Inesonic\SpeedSentry\Options(self::OPTIONS_PREFIX);

        $customer_identifier = $this->options->customer_identifier();
        $rest_api_secret = $this->options->rest_api_secret_v1();

        $this->rest_api_v1 = new \Inesonic\RestApiV1(
            $customer_identifier === null ? "" : $customer_identifier,
            $rest_api_secret === null ? "" : $rest_api_secret,
            \get_option(Inesonic\RestApiV1::TIME_DELTA_OPTION, 0)
        );
        $this->rest_api_v1->setTimeDeltaCallback('update_option');

        $this->signup_handler = new Inesonic\SpeedSentry\SignupHandler(
            self::REST_API_NAMESPACE,
            self::SIGNUP_URL,
            self::LOGIN_URL,
            self::REGISTRATION_WEBHOOK,
            self::REST_API_VERSION,
            sprintf(__("%s Control Panel"), self::NAME),
            self::LOGIN_URL,
            $this->options,
            $this->rest_api_v1
        );

        $this->capabilities = new Inesonic\SpeedSentry\Capabilities(
            $this->rest_api_v1,
            $this->signup_handler
        );

        $this->admin_menus = new Inesonic\SpeedSentry\Menus(
            self::SHORT_NAME,
            self::NAME,
            self::SLUG,
            $this->rest_api_v1,
            $this->signup_handler
        );

        $this->plugin_page = new Inesonic\SpeedSentry\PlugInsPage(
            plugin_basename(__FILE__),
            self::NAME,
            self::SPEED_SENTRY_SITE_NAME,
            self::LOGIN_URL,
            $this->options,
            $this->signup_handler
        );

        $this->admin_bar = new Inesonic\SpeedSentry\AdminBar(
            $this->options,
            $this->signup_handler,
            $this->rest_api_v1
        );

        add_action('init', array($this, 'on_initialization'));
    }

    /**
     * Autoloader callback.
     *
     * \param[in] class_name The name of this class.
     */
    public function autoloader($class_name) {
        if (!class_exists($class_name) and (FALSE !== strpos($class_name, self::PREFIX))) {
            $class_name = str_replace(self::PREFIX, '', $class_name);
            $classes_dir = realpath(plugin_dir_path(__FILE__)) . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR;
            $class_file = str_replace('_', DIRECTORY_SEPARATOR, $class_name) . '.php';

            if (file_exists($classes_dir . $class_file)) {
                require_once $classes_dir . $class_file;
            }
        }
    }

    /**
     * Static method that is triggered when the plug-in is activated.
     */
    public static function plugin_activated() {
        if (defined('ABSPATH') && current_user_can('activate_plugins')) {
            $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
            if (check_admin_referer('activate-plugin_' . $plugin)) {
                $options = new Inesonic\SpeedSentry\Options(self::OPTIONS_PREFIX);

                Inesonic\SpeedSentry\Menus::plugin_activated($options);
                Inesonic\SpeedSentry\PlugInsPage::plugin_activated($options);
                Inesonic\SpeedSentry\AdminBar::plugin_activated($options);

                // Last thing we do is set the version.
                $options->plugin_activated();
                $options->set_version(self::VERSION);
            }
        }
    }

    /**
     * Static method that is triggered when the plug-in is deactivated.
     */
    public static function plugin_deactivated() {
        if (defined('ABSPATH') && current_user_can('activate_plugins')) {
            $plugin = isset($_REQUEST['plugin']) ? $_REQUEST['plugin'] : '';
            if (check_admin_referer('deactivate-plugin_' . $plugin)) {
                $options = new Inesonic\SpeedSentry\Options(self::OPTIONS_PREFIX);

                Inesonic\SpeedSentry\Menus::plugin_deactivated($options);
                Inesonic\SpeedSentry\PlugInsPage::plugin_deactivated($options);
                Inesonic\SpeedSentry\AdminBar::plugin_deactivated($options);

                // Last thing we do is handle our options.
                $options->plugin_deactivated();
            }
        }
    }

    /**
     * Static method that generates a list of compatibility errors.
     *
     * \param[in] current_issues The current list of issues to append to.
     *
     * \return Returns a list of known compatibility issues.
     */
    public static function check_compatibility(array $current_issues) {
        global $wp_version;

        $current_php_version = phpversion();
        if (version_compare(self::MINIMUM_PHP_VERSION, $current_php_version, '>')) {
            $current_issues[] = sprintf(
                __(
                    "This plug-in requires PHP version %s; however your server is currently running PHP version " .
                    "%s.  Please upgrade.",
                    'inesonic-speedsentry'
                ),
                self::MINIMUM_PHP_VERSION,
                $current_php_version
            );
        }

        if (version_compare(self::MAXIMUM_PHP_VERSION, $current_php_version, '<')) {
            $current_issues[] = sprintf(
                __(
                    "This plug-in has been tested against PHP versions up-to %s and has not been tested against the " .
                    "version of PHP you are using (%s).",
                    'inesonic-speedsentry'
                ),
                self::MAXIMUM_PHP_VERSION,
                $current_php_version
            );
        }

        if (version_compare(self::MINIMUM_WORDPRESS_VERSION, $wp_version, '>')) {
            $current_issues[] = sprintf(
                __("This plug-in requires WordPress version %s.  Please upgrade.", 'inesonic-speedsentry'),
                self::MINIMUM_WORDPRESS_VERSION
            );
        }

        if (version_compare(self::MAXIMUM_WORDPRESS_VERSION, $wp_version, '<')) {
            $current_issues[] = sprintf(
                __(
                    "This plug-in has been tested against WordPress versions up-to %s and has not been tested " .
                    "against version %s.",
                    'inesonic-speedsentry'
                ),
                self::MAXIMUM_WORDPRESS_VERSION,
                $wp_version
            );
        }

        $missing_extensions = array();
        foreach (self::REQUIRED_EXTENSIONS as $extension) {
            if (!extension_loaded($extension)) {
                $missing_extensions[] = $extension;
            }
        }

        if (!empty($missing_extensions)) {
            $current_issues[] = "You are missing the following required extensions: " .
                                join(", ", $missing_extensions);
        }

        return $current_issues;
    }

    /**
     * Static method that handle administrative notices.
     */
    public static function check_administrative_notices() {
        $issues = self::check_compatibility(array());
        if (!empty($issues)) {
            $message = sprintf(
                __(
                    "<div class=\"error\">" .
                    "<p>Warning, The %s plug-in is untested with your current installation:</p>" .
                    "<ul>",
                    'inesonic-speedsentry'
                ),
                esc_html(self::NAME)
            );

            foreach($issues as $issue) {
                $message .= sprintf(
                    __("<li>%s</li>", 'inesonic-speedsentry'),
                    esc_html($issue)
                );
            }

            $message .= __("</ul></div>", 'inesonic-speedsentry');
            echo $message;

            // For now we don't block activation, only warn the user.
            // unset($_GET['activate']);
            // deactivate_plugins(plugin_basename(__FILE__));
        }
    }

    /**
     * Method that performs normal initialization of this plug-in.
     */
    function on_initialization() {
    }
};

/* Instantiate our plug-in. */
InesonicSpeedSentry::instance();

/* Define critical global hooks. */
register_activation_hook(__FILE__, array('InesonicSpeedSentry', 'plugin_activated'));
register_deactivation_hook(__FILE__, array('InesonicSpeedSentry', 'plugin_deactivated'));

if (!empty($GLOBALS['pagenow']) && 'plugins.php' === $GLOBALS['pagenow']) {
    add_action('admin_notices', array('InesonicSpeedSentry', 'check_administrative_notices'), 0);
}
