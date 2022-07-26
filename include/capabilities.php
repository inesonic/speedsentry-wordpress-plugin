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
    require_once dirname(__FILE__) . '/rest-api-v1.php';

    /**
     * Class that broadcasts capabilities information to the application at periodic intervals.
     */
    class Capabilities {
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
         * \param $rest_api       The outbound REST API.
         *
         * \param $signup_handler The customer signup handler.
         */
        public function __construct(
                \Inesonic\RestApiV1                 $rest_api,
                \Inesonic\SpeedSentry\SignupHandler $signup_handler
            ) {
            $this->rest_api = $rest_api;
            $this->signup_handler = $signup_handler;

            add_action('init', array($this, 'on_initialization'));
        }

        /**
         * Method that is triggered during initialization to bolt controls into the admin bar.
         */
        public function on_initialization() {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('wp_ajax_inesonic_speedsentry_get_capabilities', array($this, 'get_capabilities'));
        }

        /**
         * Method that adds scripts and styles to the admin page.
         */
        public function enqueue_scripts() {
            wp_enqueue_script('jquery');

            wp_enqueue_script(
                'inesonic-speedsentry-broadcast-capabilities',
                \Inesonic\javascript_url('broadcast-capabilities'),
                array('jquery'),
                null,
                true
            );

            wp_localize_script(
                'inesonic-speedsentry-broadcast-capabilities',
                'ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php')
                )
            );
        }

        /**
         * Method that is triggered when the JavaScript requests new capabilities information.
         */
        public function get_capabilities() {
            if ($this->signup_handler->signup_completed()) {
                $capabilities = $this->rest_api->capabilitiesGet();
                if ($capabilities !== null) {
                    $capabilities['connected'] = true;
                }
            } else {
                $capabilities = array(
                    'customer_active' => false,
                    'maximum_number_monitors' => 0,
                    'multi_region_checking' => false,
                    'paused' => false,
                    'polling_interval' => false,
                    'supports_content_checking' => false,
                    'supports_keyword_checking' => false,
                    'supports_latency_tracking' => false,
                    'supports_maintenance_mode' => false,
                    'supports_ping_based_polling' => false,
                    'supports_post_method' => false,
                    'supports_rest_api' => false,
                    'supports_ssl_expiration_checking' => false,
                    'supports_wordpress' => false,
                    'connected' => false
                );
            }

            $response = array(
                'status' => 'OK',
                'capabilities' => $capabilities
            );

            echo json_encode($response);
            wp_die();
        }
    };
