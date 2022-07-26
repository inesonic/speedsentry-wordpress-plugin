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
    /**
     * Trivial class that provides an API to plug-in specific options.
     */
    class Options {
        /**
         * Static method that is triggered when the plug-in is activated.
         */
        public function plugin_activated() {}

        /**
         * Static method that is triggered when the plug-in is deactivated.
         */
        public function plugin_deactivated() {}

        /**
         * Static method that is triggered when the plug-in is uninstalled.
         */
        public function plugin_uninstalled() {
            $this->delete_option('version');
            $this->delete_option('temporary_secret');
            $this->delete_option('rest_api_secret_v1');
            $this->delete_option('customer_identifier');
        }

        /**
         * Constructor
         *
         * \param $options_prefix The options prefix to apply to plug-in specific options.
         */
        public function __construct(string $options_prefix) {
            $this->options_prefix = $options_prefix . '_';
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
         * Method you can use to obtain the current plugin version.
         *
         * \return Returns the current plugin version.  Returns null if the version has not been set.
         */
        public function version() {
            return $this->get_option('version', null);
        }

        /**
         * Method you can use to set the current plugin version.
         *
         * \param $version The desired plugin version.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function set_version(string $version) {
            return $this->update_option('version', $version);
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
        private function get_option(string $option, $default = false) {
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
        private function update_option(string $option, $value = '') {
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
        private function delete_option(string $option) {
            return \delete_option($this->options_prefix . $option);
        }
    }
