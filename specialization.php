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
     * Class that performs specialization of this plug-in.  This class will either build the entire plug-in framework
     * or bolt onto an already created framework from another SpeedSentry plug-in.
     */
    class Specialization extends Helpers {
        /**
         * Table of plot parameters and required types.
         */
        const PLOT_PARAMETER_CONVERTERS = array(
            'plot_type'       => 'string',
            'host_scheme_id'  => 'integer',
            'monitor_id'      => 'integer',
            'region_id'       => 'integer',
            'start_timestamp' => 'double',
            'end_timestamp'   => 'double',
            'title'           => 'string',
            'x_axis_label'    => 'string',
            'y_axis_label'    => 'string',
            'date_format'     => 'string',
            'title_font'      => 'string',
            'axis_title_font' => 'string',
            'axis_label_font' => 'string',
            'minimum_latency' => 'double',
            'maximum_latency' => 'double',
            'log_scale'       => 'boolean',
            'width'           => 'integer',
            'height'          => 'integer',
            'format'          => 'string',
        );

        /**
         * Plug-in priority value.  Used to determine which plug-in renders key content.
         * A lower priority indicates a higher chance of being the key plug-in.
         */
        const PLUGIN_PRIORITY = 10;

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
         * \param $login_url         The URL to redirect to in order to login.
         *
         * \param $rest_api          The outbound REST API.
         *
         * \param $options           The plug-in options API.
         *
         * \param $signup_handler    The signup-redirect handler.
         */
        public function __construct(
                string        $short_plugin_name,
                string        $plugin_name,
                string        $plugin_slug,
                string        $login_url,
                RestApiV1     $rest_api,
                Options       $options,
                SignupHandler $signup_handler
            ) {
            $this->short_plugin_name = $short_plugin_name;
            $this->plugin_name = $plugin_name;
            $this->plugin_slug = $plugin_slug;
            $this->plugin_prefix = str_replace('-', '_', $plugin_slug);
            $this->login_url = $login_url;

            $this->rest_api = $rest_api;
            $this->options = $options;
            $this->signup_handler = $signup_handler;
    
            $this->admin_bar = new AdminBar(
                $options,
                $signup_handler,
                $rest_api
            );
            
            add_action('init', array($this, 'on_initialization'));
        }

        /**
         * Method that is triggered during initialization to bolt the plug-in settings UI into WordPress.
         */
        public function on_initialization() {
            add_action(
                'inesonic_speedsentry_status_panel_enqueue_scripts',
                array($this, 'enqueue_scripts'),
                self::PLUGIN_PRIORITY,
                2
            );
            add_action(
                'inesonic_speedsentry_status_panel_add_content',
                array($this, 'add_content'),
                self::PLUGIN_PRIORITY,
                2
            );

            add_action('wp_ajax_inesonic_speedsentry_regions_list' , array($this, 'regions_list'));
            add_action('wp_ajax_inesonic_speedsentry_hosts_list' , array($this, 'hosts_list'));
            add_action('wp_ajax_inesonic_speedsentry_monitors_list', array($this, 'monitors_list'));
            add_action('wp_ajax_inesonic_speedsentry_events_list', array($this, 'events_list'));
            add_action('wp_ajax_inesonic_speedsentry_status_list', array($this, 'status_list'));
            add_action('wp_ajax_inesonic_speedsentry_multiple_list' , array($this, 'multiple_list'));
            add_action('wp_ajax_inesonic_speedsentry_latency_plot', array($this, 'latency_plot'));
        }

        /**
         * Method that adds scripts and styles to the admin page.
         *
         * \param $plugin_slug    The main plugin page prefix.  Used to create submenus.
         *
         * \param $active_plugins A list of currently active plugins.
         */
        public function enqueue_scripts(string $plugin_slug, array $active_plugins) {
            $user_strings_from_event_types = array(
                'INVALID' => __( "Invalid", 'inesonic-speedsentry'),
                'WORKING' => __( "Working", 'inesonic-speedsentry'),
                'NO_RESPONSE' => __( "No Response", 'inesonic-speedsentry'),
                'CONTENT_CHANGED' => __( "Content Changed", 'inesonic-speedsentry'),
                'KEYWORDS' => __( "Keyword(s) Changed", 'inesonic-speedsentry'),
                'SSL_CERTIFICATE_EXPIRING' => __( "SSL Certificate Expiring", 'inesonic-speedsentry'),
                'SSL_CERTIFICATE_RENEWED' => __( "SSL Certificate Renewed", 'inesonic-speedsentry'),
                'CUSTOMER_1' => __("Custom 1", 'inesonic-speedsentry'),
                'CUSTOMER_2' => __("Custom 2", 'inesonic-speedsentry'),
                'CUSTOMER_3' => __("Custom 3", 'inesonic-speedsentry'),
                'CUSTOMER_4' => __("Custom 4", 'inesonic-speedsentry'),
                'CUSTOMER_5' => __("Custom 5", 'inesonic-speedsentry'),
                'CUSTOMER_6' => __("Custom 6", 'inesonic-speedsentry'),
                'CUSTOMER_7' => __("Custom 7", 'inesonic-speedsentry'),
                'CUSTOMER_8' => __("Custom 8", 'inesonic-speedsentry'),
                'CUSTOMER_9' => __("Custom 9", 'inesonic-speedsentry'),
                'CUSTOMER_10' => __("Custom 10", 'inesonic-speedsentry')
            );

            $user_strings_from_status = array(
                'UNKNOWN' => __("", 'inesonic-speedsentry'),
                'WORKING' => __("", 'inesonic-speedsentry'),
                'FAILED' => __("No Response", 'inesonic-speedsentry')
            );

            $user_strings = array(
                'show_all_monitors' => __("Show All Monitors", 'inesonic-speedsentry'),
                'show_alerts_only' => __("Show Alerts Only", 'inesonic-speedsentry'),
                'all_monitors_on' => __("All Monitors On {0}", 'inesonic-speedsentry'),
                'all_monitors' => __("All Monitors", 'inesonic-speedsentry')
            );

            $default_plot_file = plugin_dir_url(__FILE__) . 'assets/img/default_plot.png';

            wp_enqueue_script('jquery');
            wp_enqueue_script('jquery-ui-datepicker');
            wp_enqueue_script(
                'moment-with-locales',
                self::javascript_url('moment-with-locales', true)
            );
            wp_enqueue_script(
                'moment-timezone-with-data-1970-2030',
                self::javascript_url('moment-timezone-with-data-1970-2030', true)
            );
            wp_enqueue_script(
                'inesonic-speedsentry-status-page',
                self::javascript_url('speedsentry-status-page', true),
                array(
                    'jquery',
                    'jquery-ui-datepicker',
                    'moment-with-locales',
                    'moment-timezone-with-data-1970-2030'
                ),
                null,
                true
            );
            wp_localize_script(
                'inesonic-speedsentry-status-page',
                'ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'user_strings_from_event_types' => $user_strings_from_event_types,
                    'user_strings_from_status' => $user_strings_from_status,
                    'user_strings' => $user_strings,
                    'default_plot_file' => $default_plot_file
                )
            );

            wp_enqueue_style(
                'inesonic-speedsentry-styles',
                self::css_url('inesonic-speedsentry-styles', false),
                array(),
                null
            );
        }

        /**
         * Method that adds content to the status page.
         *
         * \param $primary_plugin The slug of the primary plugin.
         *
         * \param $active_plugins The list of active plugins.
         */
        public function add_content(string $primary_plugin, array $active_plugins) {
            echo '<div class="inesonic-speedsentry-page-title"><h1 class="inesonic-speedsentry-header">' .
                    esc_html(sprintf(__("Site Status (%s)", 'inesonic-speedsentry'), $this->plugin_name)) .
                 '</h1></div>' .
                 '<div id="inesonic-speedsentry-status-main" class="inesonic-speedsentry-status-main">' .
                   '<div class="inesonic-speedsentry-supports-logging">' .
                     '<p class="control_area_note">' .
                       __(
                           "* Note: Date/time values are <span class=\"timezone\">??</span> local",
                           'inesonic-speedsentry'
                       ) .
                     '</p>' .
                     '<div class="inesonic-speedsentry-controls">' .
                       '<div class="inesonic-speedsentry-time-controls">' .
                         '<div class="inesonic-speedsentry-start-date-control">' .
                           '<span class="inesonic-speedsentry-control-name">' .
                             __("Start:", 'inesonic-speedsentry') .
                           '</span>' .
                           '<div class="inesonic-speedsentry-control-area">' .
                             '<span class="inesonic-speedsentry-start-control-span">' .
                               '<input type="text" class="inesonic-speedsentry-start-control" ' .
                                      'id="inesonic-speedsentry-start-date"/>' .
                             '</span>' .
                             '<div class="inesonic-speedsentry-button-wrapper">' .
                               '<a id="inesonic-speedsentry-start-clear-button" ' .
                                  'class="inesonic-speedsentry-button-anchor">' .
                                 __("Clear", 'inesonic-speedsentry') .
                               '</a>' .
                             '</div>' .
                           '</div>' .
                         '</div>' .
                         '<div class="inesonic-speedsentry-end-date-control">' .
                           '<span class="inesonic-speedsentry-control-name">' .
                             __("End", 'inesonic-speedsentry') .
                           '</span>' .
                           '<div class="inesonic-speedsentry-control-area">' .
                             '<span class="inesonic-speedsentry-end-control-span">' .
                               '<input type="text" class="inesonic-speedsentry-end-control" ' .
                                      'id="inesonic-speedsentry-end-date"' .
                               '/>' .
                             '</span>' .
                             '<div class="inesonic-speedsentry-button-wrapper">' .
                               '<a id="inesonic-speedsentry-end-clear-button" ' .
                                  'class="inesonic-speedsentry-button-anchor"' .
                               '>' .
                                 __("Clear", 'inesonic-speedsentry') .
                               '</a>' .
                             '</div>' .
                           '</div>' .
                         '</div>' .
                       '</div>' .
                       '<div class="inesonic-speedsentry-latency-control">' .
                         '<span class="inesonic-speedsentry-control-name">' .
                           __("Latency", 'inesonic-speedsentry') .
                         '</span>' .
                         '<div class="inesonic-speedsentry-control-area">' .
                           '<select class="inesonic-speedsentry-latency-select-control" ' .
                                   'id="inesonic-speedsentry-latency-select-control"' .
                           '>' .
                             '<option value="0">' . __("Auto-scale", 'inesonic-speedsentry') . '</option>' .
                             '<option value="1">' . __("0 - 1 second", 'inesonic-speedsentry') . '</option>' .
                             '<option value="2">' . __("0 - 2 seconds", 'inesonic-speedsentry') . '</option>' .
                             '<option value="5">' . __("0 - 5 seconds", 'inesonic-speedsentry') . '</option>' .
                             '<option value="10">' . __("0 - 10 seconds", 'inesonic-speedsentry') . '</option>' .
                             '<option value="30">' . __("0 - 30 seconds", 'inesonic-speedsentry') . '</option>' .
                           '</select>' .
                         '</div>' .
                       '</div>' .
                       '<div class="inesonic-speedsentry-select-controls">' .
                         '<div class="inesonic-speedsentry-supports-multi-region">' .
                           '<div class="inesonic-speedsentry-region-control">' .
                             '<span class="inesonic-speedsentry-control-name">' .
                               __("Region:", 'inesonic-speedsentry') .
                             '</span>' .
                             '<div class="inesonic-speedsentry-control-area">' .
                               '<select class="inesonic-speedsentry-region-select-control" ' .
                                       'id="inesonic-speedsentry-region-select-control"' .
                               '>' .
                                 '<option value="0">' . __("All Regions", 'inesonic-speedsentry') . '</option>' .
                               '</select>' .
                             '</div>' .
                           '</div>' .
                         '</div>' .
                         '<div class="inesonic-speedsentry-monitor-control">' .
                           '<span class="inesonic-speedsentry-control-name">' .
                             __("Monitor/Authority:", 'inesonic-speedsentry') .
                           '</span>' .
                           '<div class="inesonic-speedsentry-control-area">' .
                             '<select class="inesonic-speedsentry-monitor-select-control" ' .
                                     'id="inesonic-speedsentry-monitor-select-control"' .
                             '>' .
                               '<option value="0">' . __("All Monitors", 'inesonic-speedsentry') . '</option>' .
                             '</select>' .
                           '</div>' .
                         '</div>' .
                       '</div>' .
                     '</div>' .
                   '</div>' .
                   '<div id="inesonic-speedsentry-monitor-status-top" ' .
                        'class="inesonic-speedsentry-monitor-status-top"' .
                   '>' .
                     '<div id="inesonic-speedsentry-status-alerts" class="inesonic-speedsentry-status-alerts">' .
                       '<div class="inesonic-speedsentry-status-alerts-top">' .
                         '<div class="inesonic-speedsentry-status-alerts-title-line">' .
                           '<p class="inesonic-speedsentry-status-alerts-title">Status</p>' .
                             '<div class="inesonic-speedsentry-button-wrapper">' .
                               '<a id="inesonic-speedsentry-status-alerts-button" ' .
                                  'class="inesonic-speedsentry-button-anchor"' .
                               '>' .
                                 __("Show All Monitors", 'inesonic-speedsentry') .
                               '</a>' .
                             '</div>' .
                           '</p>' .
                         '</div>' .
                       '</div>' .
                       '<table class="inesonic-speedsentry-status-alerts-table" ' .
                              'id="inesonic-speedsentry-status-alerts-table"' .
                       '>' .
                         '<thead class="inesonic-speedsentry-status-alerts-header">' .
                           '<tr class="inesonic-speedsentry-status-alerts-header-row">' .
                             '<td class="inesonic-speedsentry-status-alerts-header-url">' .
                               __("URL", 'inesonic-speedsentry') .
                             '</td>' .
                             '<td class="inesonic-speedsentry-status-alerts-header-last-event">' .
                               __("Last Event" , 'inesonic-speedsentry') .
                             '</td>' .
                             '<td class="inesonic-speedsentry-status-alerts-header-current-status">' .
                               __("Current Status", 'inesonic-speedsentry') .
                             '</td>' .
                           '</tr>' .
                         '</thead>' .
                         '<tbody class="inesonic-speedsentry-status-alerts-body" ' .
                                'id="inesonic-speedsentry-status-alerts-body"' .
                         '>' .
                           '<tr class="inesonic-speedsentry-status-alerts-row">' .
                             '<td class="inesonic-speedsentry-status-alerts-url"></td>' .
                             '<td class="inesonic-speedsentry-status-alerts-last-event"></td>' .
                             '<td class="inesonic-speedsentry-status-alerts-current-status"></td>' .
                           '</tr>' .
                         '</tbody>' .
                       '</table>' .
                     '</div>' .
                     '<div class="inesonic-speedsentry-supports-logging">' .
                       '<div class="inesonic-speedsentry-latency-histogram-outer">' .
                         '<p class="inesonic-speedsentry-latency-histogram-title">' .
                           __("Latency Histogram" , 'inesonic-speedsentry') .
                         '</p>' .
                         '<div class="inesonic-speedsentry-latency-histogram-inner">' .
                           '<div class="inesonic-speedsentry-latency-histogram-y-axis">' .
                             '<p class="inesonic-speedsentry-latency-histogram-y-axis-label">' .
                               __("Counts", 'inesonic-speedsentry') .
                             '</p>' .
                           '</div>' .
                           '<div class="inesonic-speedsentry-latency-histogram-right">' .
                             '<div class="inesonic-speedsentry-latency-histogram-image-wrapper">' .
                               '<img class="inesonic-speedsentry-image" ' .
                                    'id="inesonic-speedsentry-latency-histogram-image" ' .
                                    'src="' . plugin_dir_url(__FILE__) . 'assets/img/default_plot.png"' .
                               '/>' .
                             '</div>' .
                             '<div class="inesonic-speedsentry-latency-histogram-x-axis">' .
                               '<p id="inesonic-speedsentry-latency-histogram-x-axis-label" ' .
                                  'class="inesonic-speedsentry-latency-histogram-x-axis-label"' .
                               '>' .
                                 __("Latency (Seconds)", 'inesonic-speedsentry') .
                               '</p>' .
                             '</div>' .
                           '</div>' .
                         '</div>' .
                       '</div>' .
                     '</div>' .
                   '</div>' .
                   '<div class="inesonic-speedsentry-supports-logging">' .
                     '<div class="inesonic-speedsentry-history-graph">' .
                       '<p class="inesonic-speedsentry-history-title">' .
                         __("Latency Over Time" , 'inesonic-speedsentry') .
                       '</p>' .
                       '<div class="inesonic-speedsentry-history-inner" id="inesonic-speedsentry-history-inner">' .
                         '<div class="inesonic-speedsentry-history-y-axis" ' .
                              'id="inesonic-speedsentry-history-y-axis-label"' .
                         '>' .
                           '<p class="inesonic-speedsentry-history-y-axis-label">' .
                             __("Latency (Seconds)", 'inesonic-speedsentry') .
                           '</p>' .
                         '</div>' .
                         '<div class="inesonic-speedsentry-history-right">' .
                           '<div class="inesonic-speedsentry-history-image-wrapper">' .
                             '<img class="inesonic-speedsentry-image" ' .
                                  'id="inesonic-speedsentry-history-image" ' .
                                  'src="' . plugin_dir_url(__FILE__) . 'assets/img/default_plot.png"' .
                             '/>' .
                           '</div>' .
                           '<div class="inesonic-speedsentry-history-x-axis">' .
                             '<p class="inesonic-speedsentry-history-x-axis-label">' .
                               __("Date/Time (UTC)", 'inesonic-speedsentry') .
                             '</p>' .
                           '</div>' .
                         '</div>' .
                       '</div>' .
                     '</div>' .
                   '</div>' .
                   '<div class="inesonic-speedsentry-supports-ssl-monitoring">' .
                     '<div class="inesonic-speedsentry-ssl">' .
                       '<div class="inesonic-speedsentry-ssl_top">' .
                         '<p class="inesonic-speedsentry-ssl-title">' .
                            __("SSL Certificates:", 'inesonic-speedsentry') .
                         '</p>' .
                       '</div>' .
                       '<table class="inesonic-speedsentry-ssl-table">' .
                         '<thead class="inesonic-speedsentry-ssl-header">' .
                           '<tr class="inesonic-speedsentry-ssl-header-row">' .
                             '<td class="inesonic-speedsentry-ssl-header-authority">' .
                               __("Server", 'inesonic-speedsentry') .
                             '</td>' .
                             '<td class="inesonic-speedsentry-ssl-header-expiration-datetime">' .
                               __(
                                   "Expiration Date/Time (<span class=\"timezone\">??</span>)",
                                   'inesonic-speedsentry'
                               ) .
                             '</td>' .
                           '</tr>' .
                         '</thead>' .
                         '<tbody class="inesonic-speedsentry-ssl-body" id="inesonic-speedsentry-ssl-body">' .
                           '<tr class="inesonic-speedsentry-ssl-row">' .
                             '<td class="inesonic-speedsentry-ssl-authority"></td>' .
                             '<td class="inesonic-speedsentry-ssl-expiration-datetime"></td>' .
                           '</tr>' .
                         '</tbody>' .
                       '</table>' .
                     '</div>' .
                   '</div>' .
                   '<div class="inesonic-speedsentry-events">' .
                     '<div class="inesonic-speedsentry-events-top">' .
                       '<p class="inesonic-speedsentry-events-title">' .
                         __("Events", 'inesonic-speedsentry') .
                       '</p>' .
                     '</div>' .
                     '<table class="inesonic-speedsentry-events-table">' .
                       '<thead class="inesonic-speedsentry-events-header">' .
                         '<tr class="inesonic-speedsentry-events-header-row">' .
                           '<td class="inesonic-speedsentry-events-header-datetime">' .
                             __("Date/Time (<span class=\"timezone\">??</span>)", 'inesonic-speedsentry') .
                           '</td>' .
                           '<td class="inesonic-speedsentry-events-header-type">' .
                             __("Type", 'inesonic-speedsentry') .
                           '</td>' .
                           '<td class="inesonic-speedsentry-events-header-url">' .
                             __("URL", 'inesonic-speedsentry') .
                           '</td>' .
                           '<td class="inesonic-speedsentry-events-header-notes">' .
                             __("Notes", 'insonic-speedsentry') .
                           '</td>' .
                         '</tr>' .
                       '</thead>' .
                       '<tbody class="inesonic-speedsentry-events-body" id="inesonic-speedsentry-events-body">' .
                         '<tr class="inesonic-speedsentry-events-row">' .
                           '<td class="inesonic-speedsentry-events-datetime"></td>' .
                           '<td class="inesonic-speedsentry-events-type"></td>' .
                           '<td class="inesonic-speedsentry-events-url"></td>' .
                           '<td class="inesonic-speedsentry-events-notes"></td>' .
                         '</tr>' .
                       '</tbody>' .
                     '</table>' .
                   '</div>' .
                 '</div>';
        }

        /**
         * Method that is triggered by AJAX to obtain a list of regions.
         */
        public function regions_list() {
            $regions_data = $this->rest_api->regionsList();
            if ($regions_data !== null) {
                $response = array('status' => 'OK', 'regions' => $regions_data);
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }

        /**
         * Method that is triggered by AJAX to obtain a list of customer hosts.
         */
        public function hosts_list() {
            $hosts_data = $this->rest_api->hostsList();
            if ($hosts_data !== null) {
                $response = array('status' => 'OK', 'hosts' => $hosts_data);
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }

        /**
         * Method that is triggered by AJAX to obtain a list of monitors.
         */
        public function monitors_list() {
            if (array_key_exists('order_by', $_POST)) {
                $order_by = sanitize_text_field($_POST['order_by']);
            } else {
                $order_by = 'monitor_id';
            }

            $monitors_data = $this->rest_api->monitorsList($order_by);
            if ($monitors_data !== null) {
                $response = array('status' => 'OK', 'monitors' => $monitors_data);
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }

        /**
         * Method that is triggered by AJAX to obtain a list of events.
         */
        public function events_list() {
            if (array_key_exists('start_timestamp', $_POST)) {
                $start_timestamp = intval($_POST);
            } else {
                $start_timestamp = 0;
            }

            if (array_key_exists('end_timestamp', $_POST)) {
                $end_timestamp = intval($_POST);
            } else {
                $end_timestamp = 0;
            }

            $events_data = $this->rest_api->eventsList($start_timestamp, $end_timestamp);
            if ($events_data !== null) {
                $response = array('status' => 'OK', 'events' => $events_data);
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }

        /**
         * Method that is triggered by AJAX to obtain status on our monitors.
         */
        public function status_list() {
            $status_data = $this->rest_api->statusList();
            if ($status_data !== null) {
                $response = array('status' => 'OK', 'status' => $status_data);
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }

        /**
         * Method that is triggered by AJAX to obtain all table data in a single request.
         */
        public function multiple_list() {
            $response = $this->rest_api->multipleList();
            echo json_encode($response);
            wp_die();
        }

        /**
         * Method that is triggered by AJAX to obtain a plot.
         */
        public function latency_plot() {
            $request = array('plot_type' => 'histogram');
            foreach (self::PLOT_PARAMETER_CONVERTERS as $key => $type) {
                if (array_key_exists($key, $_POST)) {
                    $value = sanitize_text_field($_POST[$key]);
                    if (settype($value, $type)) {
                        $request[$key] = $value;
                    }
                }
            }

            $response = $this->rest_api->latencyPlot($request);
            if ($response !== null) {
                $content_type = $response['content_type'];
                if ($content_type == 'application/json') {
                    $response = array(
                        'status' => 'failed',
                        'response' => json_decode($response['body'])
                    );
                } else {
                    $response = array(
                        'status' => 'OK',
                        'image' => base64_encode($response['body']),
                        'content_type' => $content_type
                    );
                }
            } else {
                $response = array('status' => 'failed');
            }

            echo json_encode($response);
            wp_die();
        }
    };
