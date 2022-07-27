<?php
/***********************************************************************************************************************
 * Copyright 2021, Inesonic, LLC
 *
 * This program is free software; you can redistribute it and/or modify it under the terms of the GNU Lesser General
 * Public License as published by the Free Software Foundation; either version 3 of the License, or (at your option)
 * any later version.
 *
 * This program is distributed in the hope that it will be useful, but WITHOUT ANY WARRANTY; without even the implied
 * warranty of MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public License for
 * more details.
 *
 * You should have received a copy of the GNU Lesser General Public License along with this program; if not, write to
 * the Free Software Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 ***********************************************************************************************************************
 */

namespace Inesonic\SpeedSentry;

    require_once ABSPATH . 'wp-admin/includes/plugin.php';

    /**
     * Trivial class that provides an API to plug-in specific options.
     */
    class Options {
        /**
         * Name of the preferred primary plug-in.
         */
        const PREFERRED_PLUGIN = "inesonic-speedsentry";
        
        /**
         * Constructor
         *
         * \param $options_prefix The options prefix to apply to plug-in specific options.
         *
         * \param $plugin_name    A name used to identify the plug-in.
         */
        public function __construct(string $options_prefix, string $plugin_name) {
            $this->options_prefix = $options_prefix . '_';
            $this->plugin_name = $plugin_name;
        }

        /**
         * Method that is triggered when the plug-in is activated.
         */
        public function plugin_activated() {
            $this->identify_primary_plugin($this->plugin_name, false);
        }

        /**
         * Method that is triggered when the plug-in is deactivated.
         */
        public function plugin_deactivated() {
            $this->identify_primary_plugin($this->plugin_name, true);
        }
        
        /**
         * Method that is triggered when the plug-in is uninstalled.
         */
        public function plugin_uninstalled() {
            $plugins_str = $this->get_option('plugins', null);
            
            if ($plugins_str === null) {
                $plugins_list = array($this->plugin_name);
            } else {
                $plugins_list = explode(',', $plugins_str);
            }

            $index = array_search($this->plugin_name, $plugins_list);
            if ($index !== false) {
                unset($plugins_list[$index]);
            }

            if (empty($plugins_list)) {
                $this->delete_option('plugins');

                $this->delete_option('temporary_secret');
                $this->delete_option('rest_api_secret_v1');
                $this->delete_option('customer_identifier');
            } else {
                $this->update_option('plugins', implode(',', $plugins_list));
            }

            $this->addtional_plugin_uninstalled();
        }

        /**
         * Method that is triggered to perform additional uninstallation steps.
         */
        public function additional_plugin_uninstalled() {}

        /**
         * Method that obtains a list of installed Inesonic plug-ins.
         *
         * \return Returns a list of all installed Inesonic plug-ins.
         */
        public function installed_plugins() {
            $plugins_str = $this->get_option('plugins', null);
            if ($plugins_str === null) {
                $this->update_option('plugins', $this->plugin_name);
                $plugins_list = array($this->plugin_name);
            } else {
                $plugins_list = explode(',', $plugins_str);

                if (!in_array($this->plugin_name, $plugins_list)) {
                    $plugins_list[] = $this->plugin_name;
                    $this->update_option('plugins', implode(',', $plugins_list));
                }
            }

            return $plugins_list;
        }

        /**
         * Method that obtains a list of installed and active Inesonic plug-ins.
         *
         * \return Returns a list of all installed and active Inesonic plug-ins.
         */
        public function active_plugins() {
            $installed_plugins = $this->installed_plugins();
            foreach ($installed_plugins as $plugin_name) {
                $plugin_main_file = $plugin_name . DIRECTORY_SEPARATOR . $plugin_name . ".php";
                if (is_plugin_active($plugin_main_file)) {
                    $result[] = $plugin_name;
                }
            }

            return $result;
        }

        /**
         * Method you can use to register settings with WordPress.
         *
         * \param $options_group     The name of the options group to tie to the settings.
         *
         * \param $username_callback The callback used to sanitize the username.
         *
         * \param $password_callback The callback used to sanitize the password.
         */
        public function register_settings(
                string $options_group,
                callable $username_callback,
                callable $password_callback
            ) {
            register_setting($options_group, $this_options_prefix + 'username', $username_callback);
            register_setting($options_group, $this_options_prefix + 'password', $username_password);
        }

        /**
         * Method you can use to obtain the current primary plugin.
         *
         * \return Returns a string holding the name of the current primary plugin.  The value null is returned if
         *         there is no primary plugin.
         */
        public function primary_plugin() {
            return $this->get_option('primary_plugin', null);
        }

        /**
         * Method you can use to obtain the last provided temporary secret.
         *
         * \return Returns the last provided temporary secret.  A value of null is returned if no temporary secret has
         *         been set.
         */
        public function temporary_secret() {
            $value = $this->get_option('temporary_secret', null);
            if ($value !== null) {
                $result = base64_decode($value);
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to set the current plugin temporary secret.
         *
         * \param $temporary_secret The temporary secret used to get the real key.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_temporary_secret(string $temporary_secret) {
            return $this->update_option('temporary_secret', base64_encode($temporary_secret));
        }

        /**
         * Method you can use to obtain the current REST API secret (version 1)
         *
         * \return Returns the current REST API secret.  Returns null if the REST API secret has not been set.
         */
        public function rest_api_secret_v1() {
            $value = $this->get_option('rest_api_secret_v1', null);
            if ($value !== null) {
                $result = base64_decode($value, true);
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to set the current REST API secret (version 1).
         *
         * \param $rest_api_secret The REST API to be used for all requests into Inesonic infrastructure.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_rest_api_secret_v1(string $rest_api_secret) {
            return $this->update_option('rest_api_secret_v1', base64_encode($rest_api_secret));
        }

        /**
         * Method you can use to obtain the customer identifier.
         *
         * \return Returns the current customer identifier.  Returns null if the customer identifier has not been set.
         */
        public function customer_identifier() {
            return $this->get_option('customer_identifier', null);
        }

        /**
         * Method you can use to set the current customer identifier.
         *
         * \param $customer_identifier The customer identifier used to request data from Inesonic infrastructure.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_customer_identifier(string $customer_identifier) {
            return $this->update_option('customer_identifier', $customer_identifier);
        }

        /**
         * Method you can use to obtain a specific option.  This function is a thin wrapper on the WordPress get_option
         * function.
         *
         * \param $option  The name of the option of interest.
         *
         * \param $default The default value.
         *
         * \return Returns the option content.  A value of false is returned if the option value has not been set and
         *         the default value is not provided.
         */
        protected function get_option(string $option, $default = false) {
            return \get_option($this->options_prefix . $option, $default);
        }

        /**
         * Method you can use to add a specific option.  This function is a thin wrapper on the WordPress update_option
         * function.
         *
         * \param $option The name of the option of interest.
         *
         * \param $value  The value to assign to the option.  The value must be serializable or scalar.
         *
         * \return Returns true on success.  Returns false on error.
         */
        protected function update_option(string $option, $value = '') {
            return \update_option($this->options_prefix . $option, $value);
        }

        /**
         * Method you can use to delete a specific option.  This function is a thin wrapper on the WordPress
         * delete_option function.
         *
         * \param $option The name of the option of interest.
         *
         * \return Returns true on success.  Returns false on error.
         */
        protected function delete_option(string $option) {
            return \delete_option($this->options_prefix . $option);
        }

        /**
         * Method that determines which plug-in should be the primary plug-in.
         *
         * \param $plugin The name of the plug-in we're actively modifying.
         *
         * \param $now_deactivating If true, then the plugin is being deactivated.  If false, then the plugin is being
         *        activated.
         */
        private function identify_primary_plugin(string $plugin, bool $now_deactivating) {
            $installed_plugins = $this->installed_plugins();
            foreach ($installed_plugins as $plugin_name) {
                if ($plugin == $plugin_name) {
                    if (!$now_deactivating) {
                        $active_plugins[] = $plugin_name;
                    }
                } else {
                    $plugin_main_file = $plugin_name . DIRECTORY_SEPARATOR . $plugin_name . ".php";
                    if (is_plugin_active($plugin_main_file)) {
                        $active_plugins[] = $plugin_name;
                    }
                }
            }

            if (in_array(self::PREFERRED_PLUGIN, $active_plugins)) {
                $this->update_option('primary_plugin', self::PREFERRED_PLUGIN);
            } else if (count($active_plugins) > 0) {
                $this->update_option('primary_plugin', $active_plugins[0]);
            } else {
                $this->delete_option('primary_plugin');
            }
        }
    }
