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
    require_once dirname(__FILE__) . '/rest-api-v1.php';

    /**
     * Class that manages options displayed within the WordPress Admin bar.
     */
    class AdminBar {
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
         * \param $options        The plug-in options API.
         *
         * \param $signup_handler The signup-redirect handler.
         *
         * \param $rest_api       The outbound REST API.
         */
        public function __construct(
                Options             $options,
                SignupHandler       $signup_handler,
                \Inesonic\RestApiV1 $rest_api

            ) {
            $this->options = $options;
            $this->signup_handler = $signup_handler;
            $this->rest_api = $rest_api;

            add_action('init', array($this, 'on_initialization'));
        }

        /**
         * Method that is triggered during initialization to bolt controls into the admin bar.
         */
        public function on_initialization() {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
            add_action('admin_bar_menu', array($this, 'add_controls'), 100);
            add_action(
                'wp_ajax_inesonic_speedsentry_maintenance_mode_change_status',
                array($this, 'maintenance_mode_change_status')
            );
        }

        /**
         * Method that adds scripts and styles to the admin page.
         */
        public function enqueue_scripts() {
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'inesonic-speedsentry-maintenance-mode-control',
                \Inesonic\javascript_url('maintenance-mode-control'),
                array('jquery'),
                null,
                true
            );
            wp_localize_script(
                'inesonic-speedsentry-maintenance-mode-control',
                'ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php')
                )
            );

            wp_enqueue_style(
                'inesonic-speedsentry-styles',
                \Inesonic\css_url('inesonic-speedsentry-styles'),
                array(),
                null
            );
        }

        /**
         * Method that optionally adds controls to the admin bar.  Controls may be deferred, if needed.
         *
         * \param $admin_bar The admin bar menu object.
         */
        public function add_controls(\WP_Admin_Bar $admin_bar) {
            if (current_user_can('manage_options') && is_admin_bar_showing()) {
                $admin_bar->add_menu(
                    array(
                        'id' => 'inesonic-speedsentry-maintenance-mode-control',
                        'title' => '<span id="inesonic-speedsentry-capabilities" style="display: none;"></span>' .
                                   '<label class="inesonic_speedsentry_maintenance_mode_switch">' .
                                     '<input id="inesonic-speedsentry-maintenance-mode-switch" ' .
                                            'class="inesonic_speedsentry_maintenance_mode_switch_input" '.
                                            'type="checkbox" ' .
                                            'onchange="inesonicSpeedSentrySwitchMode(this)"/>' .
                                     '<span class="inesonic_speedsentry_maintenance_mode_slider"></span>' .
                                   '</label>' .
                                   '<span class="inesonic_speedsentry_maintenance_mode_label">' .
                                     __("Site Monitoring") .
                                   '</span>',
                        'meta' => array(
                            'class' => 'inesonic-speedsentry-maintenance-mode-hidden'
                        )
                    )
                );
            }
        }

        /**
         * Method that is triggered when the user changes the current maintenance mode.
         */
        public function maintenance_mode_change_status() {
            if (array_key_exists('pause', $_POST)) {
                $pause = ($_POST['pause'] != 'false' && $_POST['pause'] != '0');
                if ($this->rest_api->customerPause($pause)) {
                    $response = array('status', 'OK', 'post' => $_POST);
                } else {
                    $response = array('status', 'failed');
                }
            }
            else {
                $response = array('status', 'failed, malformed request');
            }

            echo json_encode($response);
            wp_die();
        }
    };
