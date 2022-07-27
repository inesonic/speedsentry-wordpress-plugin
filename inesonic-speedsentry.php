<?php
/**
 * Plugin Name:       Inesonic SpeedSentry
 * Description:       Site uptime and performance monitoring for WordPress.
 * Version:           1.7
 * Author:            Inesonic,  LLC
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
 * Copyright 2021-2022, Inesonic, LLC
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

/**
 * Main class for the SpeedSentry plug-in.  This class does the work needed to instantiate the plug-in within the larger
 * WordPress application.  Note that this file contains the only content not containing within the Inesonic\SpeedSentry
 * namespace.
 */
class InesonicSpeedSentry {
    /**
     * Plug-in version.
     */
    const VERSION = '1.7';

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
     * Class name.
     */
    const CLASS_NAME = 'InesonicSpeedSentry';

    /**
     * The namespace that we need to perform auto-loading for.
     */
    const PLUGIN_NAMESPACE = 'Inesonic\\SpeedSentry\\';

    /**
     * The plug-in include path.
     */
    const INCLUDE_PATH = __DIR__ . '/include/';
    
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
    const MAXIMUM_WORDPRESS_VERSION = '5.9';

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

    /**
     * Plug-in instance.
     */
    private static $instance;

    /**
     * Static method we use to create a single private instance of this plug-in.
     *
     * \return Returns our static plug-in instance.
     */
    public static function instance() {
        if (!isset(self::$instance) || !(self::$instance instanceof InesonicSpeedSentry)) {
            spl_autoload_register(array(self::class, 'autoloader'));
            
            self::$instance = new InesonicSpeedSentry();
            self::$dir      = plugin_dir_path(__FILE__);
            self::$url      = plugin_dir_url(__FILE__);
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    public function __construct() {
        $slug = dirname(plugin_basename(__FILE__));            
        $this->options = new Inesonic\SpeedSentry\Options(self::OPTIONS_PREFIX, $slug);

        $customer_identifier = $this->options->customer_identifier();
        $rest_api_secret = $this->options->rest_api_secret_v1();

        $this->rest_api_v1 = new \Inesonic\SpeedSentry\RestApiV1(
            $customer_identifier === null ? "" : $customer_identifier,
            $rest_api_secret === null ? "" : $rest_api_secret,
            \get_option(Inesonic\SpeedSentry\RestApiV1::TIME_DELTA_OPTION, 0)
        );
        $this->rest_api_v1->setTimeDeltaCallback('update_option');

        $installed_plugins = $this->options->installed_plugins();

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

        $this->specialization = new Inesonic\SpeedSentry\Specialization(
            self::SHORT_NAME,
            self::NAME,
            $slug,
            self::LOGIN_URL,
            $this->rest_api_v1,
            $this->options,
            $this->signup_handler
        );

        $primary_plugin = $this->options->primary_plugin();
        $is_primary_plugin = $primary_plugin == $slug;
        if ($is_primary_plugin) {
            $this->capabilities = new Inesonic\SpeedSentry\Capabilities(
                $this->rest_api_v1,
                $this->signup_handler
            );
    
            $this->admin_menus = new Inesonic\SpeedSentry\Menus(
                self::SHORT_NAME,
                self::NAME,
                $slug,
                $this->options,
                $this->signup_handler
            );
        }

        $this->plugin_page = new Inesonic\SpeedSentry\PlugInsPage(
            plugin_basename(__FILE__),
            self::NAME,
            $slug,
            self::SPEED_SENTRY_SITE_NAME,
            $this->signup_handler,
            $is_primary_plugin
        );
        
        add_action('init', array($this, 'on_initialization'));
    }

    /**
     * Autoloader callback.
     *
     * \param[in] class_name The name of this class.
     */
    static public function autoloader($class_name) {
        if (!class_exists($class_name) && str_starts_with($class_name, self::PLUGIN_NAMESPACE)) {
            $class_basename = str_replace(self::PLUGIN_NAMESPACE, '', $class_name);
            $last_was_lower = false;
            $filename = "";
            for ($i=0 ; $i<strlen($class_basename) ; ++$i) {
                $c = $class_basename[$i];
                if (ctype_upper($c)) {
                    if ($last_was_lower) {
                        $filename .= '-' . strtolower($c); 
                        $last_was_lower = false;
                    } else {
                        $filename .= strtolower($c);
                    }
                } else {
                    $filename .= $c;
                    $last_was_lower = true;
                }
            }

            $filename .= '.php';
            $filepath = self::INCLUDE_PATH . $filename;
            if (file_exists($filepath)) {
                include $filepath;
            } else {
                $filepath = __DIR__ . DIRECTORY_SEPARATOR . $filename;
                if (file_exists($filepath)) {
                    include $filepath;
                }
            }
        }
    }

    /**
     * Static method that is triggered when the plug-in is activated.
     */
    public static function plugin_activated() {
        if (defined('ABSPATH') && current_user_can('activate_plugins')) {
            $plugin = isset($_REQUEST['plugin']) ? sanitize_text_field($_REQUEST['plugin']) : '';
            if (check_admin_referer('activate-plugin_' . $plugin)) {
                $slug = dirname(plugin_basename(__FILE__));            
                $options = new Inesonic\SpeedSentry\Options(self::OPTIONS_PREFIX, $slug);

                Inesonic\SpeedSentry\Menus::plugin_activated($options);
                Inesonic\SpeedSentry\PlugInsPage::plugin_activated($options);
                Inesonic\SpeedSentry\AdminBar::plugin_activated($options);

                $options->plugin_activated();
            }
        }
    }

    /**
     * Static method that is triggered when the plug-in is deactivated.
     */
    public static function plugin_deactivated() {
        if (defined('ABSPATH') && current_user_can('activate_plugins')) {
            $plugin = isset($_REQUEST['plugin']) ? sanitize_text_field($_REQUEST['plugin']) : '';
            if (check_admin_referer('deactivate-plugin_' . $plugin)) {
                $slug = dirname(plugin_basename(__FILE__));            
                $options = new Inesonic\SpeedSentry\Options(self::OPTIONS_PREFIX, $slug);

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
register_activation_hook(__FILE__, array(InesonicSpeedSentry::class, 'plugin_activated'));
register_deactivation_hook(__FILE__, array(InesonicSpeedSentry::class, 'plugin_deactivated'));
