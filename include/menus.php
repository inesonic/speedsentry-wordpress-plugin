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
     * Class that manages the plug-in admin panel menus.
     */
    class Menus extends Helpers {
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
         * \param $short_plugin_name A short version of the plug-in name to be used in the menus.
         *
         * \param $plugin_name       The user visible name for this plug-in.
         *
         * \param $plugin_slug       The slug used for the plug-in.  We use this slug as a prefix for slugs this class
         *                           may also require.
         *
         * \param $options           The options handler.
         *
         * \param $signup_handler    The signup handler used to connect users.
         */
        public function __construct(
                string        $short_plugin_name,
                string        $plugin_name,
                string        $plugin_slug,
                Options       $options,
                SignupHandler $signup_handler
            ) {
            $this->short_plugin_name = $short_plugin_name;
            $this->plugin_name = $plugin_name;
            $this->plugin_slug = $plugin_slug;
            $this->plugin_prefix = str_replace('-', '_', $plugin_slug);

            $this->options = $options;
            $this->signup_handler = $signup_handler;

            $this->active_plugins = $this->options->active_plugins();
            add_action('init', array($this, 'on_initialization'));
        }

        /**
         * Method that is triggered during initialization to bolt the plug-in settings UI into WordPress.
         */
        public function on_initialization() {
            add_action('admin_menu', array($this, 'add_menu'));
        }

        /**
         * Method that adds the menu to the dashboard.
         */
        public function add_menu() {
            add_menu_page(
                $this->plugin_name,
                $this->short_plugin_name,
                'manage_options',
                $this->plugin_prefix,
                array($this, 'build_main_page'),
                plugin_dir_url(__FILE__) . 'assets/img/menu_icon.png',
                30
            );

            do_action(
                'inesonic-speedsentry-add-submenus',
                $this->plugin_prefix,
                $this->active_plugins
            );
        }

        /**
         * Method that renders the site monitoring page.
         */
        public function build_main_page() {
            $this->signup_handler->enqueue_javascript();

            echo '<div class="inesonic-speedsentry-active inesonic-speedsentry-hidden">';
            do_action('inesonic_speedsentry_status_panel_enqueue_scripts', $this->plugin_slug, $this->active_plugins);            
            do_action('inesonic_speedsentry_status_panel_add_content', $this->plugin_slug, $this->active_plugins);
            
            echo '</div>' .
                 '<div class="inesonic-speedsentry-inactive inesonic-speedsentry-hidden">' .
                   '<div class="inesonic-speedsentry-inactive-message-area">' .
                     '<p class="inesonic-speedsentry-inactive-message"> ' .
                       __(
                           "Before you can use Inesonic SpeedSentry Site Monitoring, you must attach your site to " .
                           "Inesonic's site monitoring infrastructure.",
                           'inesonic-speedsentry'
                       ) .
                     '</p>' .
                     '<p class="inesonic-speedsentry-inactive-message">' .
                       __("You may also need to create an account.", 'inesonic-speedsentry') .
                     '</p>' .
                     '<p class="inesonic-speedsentry-inactive-message">' .
                       __(
                           "Don't worry, the process is simple and secure. Click on the button below to get started.",
                           'inesonic-speedsentry'
                       ) .
                     '</p>' .
                     '<p class="inesonic-speedsentry-signup-button-wrapper">' .
                        $this->signup_handler->signup_anchor_tag(
                            __("Activate Inesonic SpeedSentry", 'inesonic-speedsentry'),
                            'inesonic-speedsentry-signup-button'
                        ) .
                     '</p>' .
                   '</div>' .
                 '</div>' .
                 '<div class="inesonic-speedsentry-unconfirmed inesonic-speedsentry-hidden">' .
                   '<div class="inesonic-speedsentry-inactive-message-area">' .
                     '<p class="inesonic-speedsentry-inactive-message"> ' .
                       __(
                           "Before you can use Inesonic SpeedSentry Site Monitoring, you must confirm your email " .
                           "address. Please click on the link in the confirmation email we sent you.",
                           'inesonic-speedsentry'
                       ) .
                     '</p>' .
                     '<p class="inesonic-speedsentry-inactive-message">' .
                       __(
                           "You can request a new email by logging into the Inesonic SpeedSentry Control Panel.  To " .
                           "do so, please click on the link below.",
                           'inesonic-speedsentry'                           
                       ) .
                     '<p class="inesonic-speedsentry-signup-button-wrapper">' .
                       '<a href="https://speed-sentry.com/customer-sign-in/" ' .
                          'class="inesonic-speedsentry-signup-button" '.
                          'target="_blank">' .
                         __("Inesonic SpeedSentry Control Panel", 'inesonic-speedsentry') .
                       '</a>' .
                     '</p>' .
                   '</div>' .
                 '</div>' .
                 '<div class="inesonic-speedsentry-connection-issue inesonic-speedsentry-hidden">' .
                   '<div class="inesonic-speedsentry-inactive-message-area">' .
                     '<p class="inesonic-speedsentry-inactive-message"> ' .
                       __(
                           "Your website is currently unable to connect to Inesonic Infrastructure.  Please check " .
                           "back later.",
                           'inesonic-speedsentry'
                       ) .
                     '</p>' .
                   '</div>' .
                 '</div>';
        }
    };
