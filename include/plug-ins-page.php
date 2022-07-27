<?php
 /**********************************************************************************************************************
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
 */

namespace Inesonic\SpeedSentry;

    /**
     * Class that manages options displayed within the WordPress Plugins page.
     */
    class PlugInsPage extends Helpers {
        /**
         * The URL to redirect to for customer login.  Do not include an ending /.
         */
        const LOGIN_URL = 'https://speed-sentry.com/customer-sign-in';
    
        /**
         * The URL to redirect to for customer sign-up.  Do not include an ending /.
         */
        const SIGNUP_URL = 'https://speed-sentry.com/register';
    
        /**
         * Static method that is triggered when the plug-in is activated.
         *
         * \param $options The plug-in options instance.
         */
        public static function plugin_activated(Options $options) {}

        /**
         * Static method that is triggered when the plug-in is deactivated.
         *
         * \param $options The plug-in options instance.
         */
        public static function plugin_deactivated(Options $options) {}

        /**
         * Constructor
         *
         * \param $plugin_basename       The base name for the plug-in.
         *
         * \param $plugin_name           The user visible name for this plug-in.
         *
         * \param $plugin_slug           The slug assigned to this plug-in.
         *
         * \param $speedsentry_site_name The user friendly name of the SpeedSentry site.
         *
         * \param $signup_handler        The signup-redirect handler.
         *
         * \param $is_primary_plugin     If true, then this plug-in is the primary plug-in.  If false, then this
         *                               plug-in is secondary.
         */
        public function __construct(
                string        $plugin_basename,
                string        $plugin_name,
                string        $plugin_slug,
                string        $speedsentry_site_name,
                SignupHandler $signup_handler,
                bool          $is_primary_plugin
            ) {
            $this->plugin_basename = $plugin_basename;
            $this->plugin_name = $plugin_name;
            $this->plugin_slug = $plugin_slug;
            $this->speedsentry_site_name = $speedsentry_site_name;
            $this->signup_handler = $signup_handler;
            $this->is_primary_plugin = $is_primary_plugin;
            
            add_action('init', array($this, 'on_initialization'));
        }

        /**
         * Method that is triggered during initialization to bolt the plug-in settings UI into WordPress.
         */
        public function on_initialization() {
            add_filter('plugin_action_links_' . $this->plugin_basename, array($this, 'add_plugin_page_links'));
            add_action(
                'after_plugin_row_' . $this->plugin_basename,
                array($this, 'add_plugin_configuration_fields'),
                10,
                3
            );

            add_action('wp_ajax_inesonic_speedsentry_access_codes' , array($this, 'update_access_codes'));
            
            $this->additional_initialization();
        }

        /**
         * Method that is called to perform additional initialization.
         */
        public function additional_initialization() {}

        /**
         * Method that adds links to the plug-ins page for this plug-in.
         *
         * \param $links The links to be updated.
         *
         * \return Returns updated links.         
         */
        public function add_plugin_page_links(array $links) {
            if ($this->is_primary_plugin) {
                $links = $this->add_additional_plugin_page_links($links, $this->is_primary_plugin);
                
                $manual_configuration = "<a href=\"###\" id=\"inesonic-speedsentry-mc-link\">" .
                                          __("Manual Connect", 'inesonic-speedsentry') .
                                        "</a>";
                array_unshift($links, $manual_configuration);
    
                if ($this->signup_handler->signup_completed()) {
                    $control_link = "<a href=\"" . self::LOGIN_URL . "\" target=\"_blank\">" .
                                      __("Account", 'inesonic-speedsentry') .
                                    "</a>";
                    array_unshift($links, $control_link);
    
                    $signup_description = __("Reconnect", 'inesonic-speedsentry');
                } else {
                    $signup_description = __("Connect", 'inesonic-speedsentry');
                }
    
                $signup_link = $this->signup_handler->signup_anchor_tag($signup_description);
                array_unshift($links, $signup_link);
            } else {
                $links = $this->add_additional_plugin_page_links($links, $this->is_primary_plugin);
            }

            return $links;            
        }

        /**
         * Method that can be overloaded to update additional links.
         *
         * \param $links             The links to be updated.
         *
         * \param $is_primary_plugin If true, then this plug-in is the primary plug-in.  If false, then this plugin is
         *                           not the primary plugin.
         *
         * \return Returns the updated links.
         */
        public function add_additional_plugin_page_links(array $links, bool $is_primary_plugin) {
            return $links;
        }
         
        /**
         * Method that adds content to the plug-ins page for this plug-in.
         *
         * \param $plugin_file The plugin file.
         *
         * \param $plugin_data The plugin data.
         *
         * \param $status      The plugin status.
         */
        public function add_plugin_configuration_fields(string $plugin_file, array $plugin_data, string $status) {
            if ($this->signup_handler->signup_completed()) {
                $current_access_code = $this->signup_handler->customer_identifier() .
                                       "," .
                                       base64_encode($this->signup_handler->customer_secret_v1());
            } else {
                $current_access_code = "";
            }

            $this->add_additional_configuration_fields($plugin_file, $plugin_data, $status, $this->is_primary_plugin);
            
            echo '<tr id="inesonic-speedsentry-configuration-area-row"
                      class="inesonic-speedsentry-configuration-area-row inesonic-row-hidden">
                    <th></th>
                    <td class="inesonic-speedsentry-configuration-area-column" colspan="2">
                      <div class="inesonic-speedsentry-mc-field">
                        <label class="inesonic-speedsentry-mc-label">' .
                          __("Enter your <strong>SpeedSentry</strong> access code below", 'inesonic-speedsentry') . '
                        </label>
                        <input type="text"
                               class="inesonic-speedsentry-mc-input"
                               value="' . esc_html($current_access_code) . '"
                               id="inesonic-speedsentry-mc-input"/>
                        <div class="inesonic-speedsentry-mc-button-wrapper">
                          <div class="inesonic-speedsentry-button-wrapper">
                            <a id="inesonic-speedsentry-mc-submit-button" class="inesonic-speedsentry-button-anchor">' .
                              __("Submit", 'inesonic-speedsentry') . '
                            </a>
                          </div>
                        </div>
                      </div>
                      <div class="inesonic-speedsentry-mc-documentation-wrapper">
                        <a href="https://speed-sentry.com/connecting-wordpress-manual/"
                           class="inesonic-speedsentry-mc-documentation-anchor"
                           target="_blank">' .
                          __("Click here for instructions", 'inesonic-speedsentry') . '
                        </a>
                      </div>
                    </td>" .
                  </tr>';
                              
            $this->signup_handler->enqueue_javascript();
            
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'inesonic-speedsentry-plugins-page',
                self::javascript_url('speedsentry-plugins-page', false),
                array('jquery'),
                null,
                true
            );
            wp_localize_script(
                'inesonic-speedsentry-plugins-page',
                'ajax_object',
                array('ajax_url' => admin_url('admin-ajax.php'))
            );

            wp_enqueue_style(
                'inesonic-speedsentry-styles',
                self::css_url('inesonic-speedsentry-styles', false),
                array(),
                null
            );
        }
         
        /**
         * Method that adds additional content to the plug-ins page for this plug-in.
         *
         * \param $plugin_file       The plugin file.
         *
         * \param $plugin_data       The plugin data.
         *
         * \param $status            The plugin status.
         *
         * \param $is_primary_plugin If true, then this plug-in is the primary plug-in.  If false, then this plug-in is
         *                           not the primary plug-in.
         */
        public function add_additional_configuration_fields(string $plugin_file, array $plugin_data, string $status) {
        }

        /**
         * Method that is triggered to update our access codes based on customer input.
         */
        public function update_access_codes() {
            if (current_user_can('activate_plugins')) {
                if (array_key_exists('cid', $_POST) && array_key_exists('secret', $_POST)) {
                    $cid = sanitize_text_field($_POST['cid']);
                    $secret = sanitize_text_field($_POST['secret']);

                    $decoded_secret = base64_decode($secret);
                    $this->signup_handler->set_credentials($cid, $decoded_secret);

                    $result = array('status' => 'OK');
                } else {
                    $result = array('status' => 'failed, bad payload');
                }
            } else {
                $result = array('status' => 'failed, insufficient privileges');
            }

            echo json_encode($result);
            wp_die();
        }        
    };
