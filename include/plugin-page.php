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
    require_once dirname(__FILE__) . '/helpers.php';
    require_once dirname(__FILE__) . '/signup-handler.php';
    require_once dirname(__FILE__) . '/options.php';

    /**
     * Class that manages options displayed within the WordPress Plugins page.
     */
    class PlugInsPage {
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
         * \param $speedsentry_site_name The user friendly name of the SpeedSentry site.
         *
         * \param $login_url             The URL to redirect to in order to login.
         *
         * \param $options               The plug-in options API.
         *
         * \param $signup_handler        The signup-redirect handler.
         */
        public function __construct(
                string        $plugin_basename,
                string        $plugin_name,
                string        $speedsentry_site_name,
                string        $login_url,
                Options       $options,
                SignupHandler $signup_handler
            ) {
            $this->plugin_basename = $plugin_basename;
            $this->plugin_name = $plugin_name;
            $this->speedsentry_site_name = $speedsentry_site_name;
            $this->login_url = $login_url;
            $this->options = $options;
            $this->signup_handler = $signup_handler;

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
        }

        /**
         * Method that adds links to the plug-ins page for this plug-in.
         */
        public function add_plugin_page_links(array $links) {
            $this->signup_handler->enqueue_javascript();

            $manual_configuration = "<a href=\"###\" id=\"inesonic-speedsentry-mc-link\">" .
                                      __("Manual Configure", 'inesonic-speedsentry') .
                                    "</a>";
            array_unshift($links, $manual_configuration);

            $rest_api_secret = $this->options->rest_api_secret_v1();
            if ($this->signup_handler->signup_completed()) {
                $control_link = "<a href=\"" . $this->login_url . "\" target=\"_blank\">" .
                                  __("Account", 'inesonic-speedsentry') .
                                "</a>";
                array_unshift($links, $control_link);

                $signup_description = __("Reconnect", 'inesonic-speedsentry');
            } else {
                $signup_description = __("Connect", 'inesonic-speedsentry');
            }

            $signup_link = $this->signup_handler->signup_anchor_tag($signup_description);
            array_unshift($links, $signup_link);

            return $links;
        }

        /**
         * Method that adds links to the plug-ins page for this plug-in.
         */
        public function add_plugin_configuration_fields(string $plugin_file, array $plugin_data, string $status) {
            if ($this->signup_handler->signup_completed()) {
                $current_access_code = $this->signup_handler->customer_identifier() .
                                       "," .
                                       base64_encode($this->signup_handler->customer_secret_v1());
            } else {
                $current_access_code = "";
            }

            echo '<tr id="inesonic-speedsentry-configuration-area-row"
                      class="inesonic-speedsentry-configuration-area-row inesonic-row-hidden">
                    <th></th> .
                    <td class="inesonic-speedsentry-configuration-area-column" colspan="2">
                      <div class="inesonic-speedsentry-mc-field">
                        <label class="inesonic-speedsentry-mc-label">' .
                          __("Enter your <strong>SpeedSentry</strong> access code below", 'inesonic-speedsentry') . '
                        </label>
                        <input type="text"
                               class="inesonic-speedsentry-mc-input"
                               value="' . $current_access_code . '"
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

            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'inesonic-speedsentry-plugins-page',
                \Inesonic\javascript_url('plugins-page'),
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
                \Inesonic\css_url('inesonic-speedsentry-styles'),
                array(),
                null
            );
        }

        /**
         * Method that is triggered to update our access codes based on customer input.
         */
        public function update_access_codes() {
            if (current_user_can('activate_plugins')) {
                if (array_key_exists('cid', $_POST) && array_key_exists('secret', $_POST)) {
                    $cid = $_POST['cid'];
                    $secret = $_POST['secret'];

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
