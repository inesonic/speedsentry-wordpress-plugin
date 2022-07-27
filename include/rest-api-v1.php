<?php
/******************************************************************************
 * Copyright 2021, Inesonic, LLC
 *
 *   This program is free software; you can redistribute it and/or modify it
 *   under the terms of the GNU Lesser General Public License as published by
 *   the Free Software Foundation; either version 3 of the License, or (at your
 *   option) any later version.
 *
 *   This program is distributed in the hope that it will be useful, but
 *   WITHOUT ANY WARRANTY; without even the implied warranty of MERCHANTABILITY
 *   or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU Lesser General Public
 *   License for more details.
 *
 *   You should have received a copy of the GNU Lesser General Public License
 *   along with this program; if not, write to the Free Software Foundation,
 *   Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 ******************************************************************************
 */

namespace Inesonic\SpeedSentry;
    /**
     * Class that encapsulates the Inesonic REST API.
     */
    class RestApiV1
    {
        /**
         * The authority containing the customer REST APIs.
         */
        const INESONIC_AUTHORITY = "https://rest.1.speed-sentry.com";

        /**
         * The required length of the final REST API secret, in bytes.
         */
        const REST_API_SECRET_LENGTH = 56;

        /**
         * Timeout to apply to all requests, in seconds.
         */
        const DEFAULT_TIMEOUT = 20;

        /**
         * The hash algorithm.
         */
        const HASH_ALGORITHM = 'sha256';

        /**
         * The name of the option used to store off the time delta.
         */
        const TIME_DELTA_OPTION = 'inesonic_rest_time_delta';

        /**
         * Route to the time delta slug.
         */
        const TIME_DELTA_ROUTE = '/td/';

        /**
         * Route for the capabilities/get function.
         */
        const CAPABILITIES_GET_ROUTE = '/v1/capabilities/get';

        /**
         * Route for the hosts/get function.
         */
        const HOSTS_GET_ROUTE = '/v1/hosts/get';

        /**
         * Route for the hosts/list function.
         */
        const HOSTS_LIST_ROUTE = '/v1/hosts/list';

        /**
         * Route for the monitors/get function.
         */
        const MONITORS_GET_ROUTE = '/v1/monitors/get';

        /**
         * Route for the monitors/list function.
         */
        const MONITORS_LIST_ROUTE = '/v1/monitors/list';

        /**
         * Route for the monitors/update function.
         */
        const MONITORS_UPDATE_ROUTE = '/v1/monitors/update';

        /**
         * Route for the regions/get function.
         */
        const REGIONS_GET_ROUTE = '/v1/regions/get';

        /**
         * Route for the rgions/list function.
         */
        const REGIONS_LIST_ROUTE = '/v1/regions/list';

        /**
         * Route for the events/get function.
         */
        const EVENTS_GET_ROUTE = '/v1/events/get';

        /**
         * Route for the events/list function.
         */
        const EVENTS_LIST_ROUTE = '/v1/events/list';

        /**
         * Route for the events/create function.
         */
        const EVENTS_CREATE_ROUTE = '/v1/events/create';

        /**
         * Route for the status/get function.
         */
        const STATUS_GET_ROUTE = '/v1/status/get';

        /**
         * Route for the status/list function.
         */
        const STATUS_LIST_ROUTE = '/v1/status/list';

        /**
         * Route for the multiple/list function.
         */
        const MULTIPLE_LIST_ROUTE = '/v1/multiple/list';

        /**
         * Route for the latency/list function.
         */
        const LATENCY_LIST_ROUTE = '/v1/latency/list';

        /**
         * Route for the latency plot function.
         */
        const LATENCY_PLOT_ROUTE = '/v1/latency/plot';

        /**
         * Route for the customer/pause function.
         */
        const CUSTOMER_PAUSE_ROUTE = '/v1/customer/pause';

        /**
         * Route for the resource/available function.
         */
        const RESOURCE_AVAILABLE_ROUTE = '/v1/resource/available';

        /**
         * Route for the resource/create function.
         */
        const RESOURCE_CREATE_ROUTE = '/v1/resource/create';

        /**
         * Route for the resource/list function.
         */
        const RESOURCE_LIST_ROUTE = '/v1/resource/list';

        /**
         * Route for the resource/plot function.
         */
        const RESOURCE_PLOT_ROUTE = '/v1/resource/plot';

        /**
         * Event ID for transaction customer events.
         */
        const TRANSACTION_EVENT = 11;

        /**
         * Event ID for customer inquiry events.
         */
        const INQUIRY = 12;

        /**
         * Event ID for customer support request events.
         */
        const SUPPORT_REQUEST = 13;

        /**
         * Event ID for customer storage limit reached events.
         */
        const STORAGE_LIMIT_REACHED = 14;
        
        /**
         * CPU usage value type.
         */
        const CPU_VALUE_TYPE = 0;

        /**
         * Memory value type.
         */
        const MEMORY_VALUE_TYPE = 1;

        /**
         * Storage value type.
         */
        const STORAGE_VALUE_TYPE = 2;
        
        /**
         * Constructor
         *
         * \param $customer_identifier The identifier you should use as your
         *                             identity when communicating with
         *                             Inesonic infrastructure.  You can obtain
         *                             this setting from the Inesonic Account
         *                             Settings page.
         *
         * \param $rest_api_secret     The secret you should use to
         *                             authenticate against Inesonic
         *                             infrastructure.  You can obtain this
         *                             setting from the Inesonic Account
         *                             Settings page.  Note that the value must
         *                             be in raw binary format, not base-64
         *                             encoded.
         *
         * \param $default_time_delta  An integer value holding the last
         *                             calculated time delta.  The value is
         *                             used to generate authorization hashes.
         */
        public function __construct(
                string $customer_identifier = "",
                string $rest_api_secret = "",
                int    $default_time_delta = 0
            ) {
            $this->customer_identifier = $customer_identifier;
            $this->rest_api_secret = $rest_api_secret;
            $this->timeout = self::DEFAULT_TIMEOUT;
            $this->time_delta = $default_time_delta;
            $this->time_delta_callback = null;
        }

        /**
         * Method you can use to update your customer identifier.
         *
         * \param $customer_identifier The identifier you should use as your
         *                             identity when communicating with
         *                             Inesonic infrastructure.  You can obtain
         *                             this setting from the Inesonic Account
         *                             Settings page.
         */
        public function setCustomerIdentifier(string $customer_identifier) {
            $this->customer_identifier = $customer_identifier;
        }

        /**
         * Method you can use to obtain you currently set customer identifier.
         *
         * \return Returns the current customer identifier.
         */
        public function customerIdentifier() {
            return $this->customer_identifier;
        }

        /**
         * Method you can use to update your REST API secret.
         *
         * \param $rest_api_secret    The secret you should use to authenticate
         *                            against Inesonic infrastructure.  You
         *                            can obtain this setting from the Inesonic
         *                            Account Settings page.
         *
         * \param $is_base_64_encoded If true, the provided secret is currently
         *                            base-64 encoded.  If false, the secret
         *                            is a raw binary string.
         *
         * \return Returns true if the provided secret is valid.  Returns false
         *         if the provide secret's length is invalid.
         */
        public function setRestApiSecret(
                string $rest_api_secret,
                bool   $is_base_64_encoded
            ) {
            if ($is_base_64_encoded) {
                $raw_secret = base64_decode($rest_api_secret);
            } else {
                $raw_secret = $rest_api_secret;
            }

            if (strlen($raw_secret) == self::REST_API_SECRET_LENGTH) {
                $this->rest_api_secret = $raw_secret;
                $result = true;
            } else {
                $result = false;
            }

            return $result;
        }

        /**
         * Method you can use to update the timeout to be used.
         *
         * \param $timeout The new timeout value to be used.  Value is in
         *                 seconds.
         */
        public function setTimeout(string $timeout) {
            $this->timeout = $timeout;
        }

        /**
         * Method you can use to obtain the current timeout value.
         *
         * \return Returns the current timeout value.
         */
        public function timeout() {
            return $this->timeout;
        }

        /**
         * Method you can use to update the currently used time delta.
         *
         * \param $time_delta The time delta between this system and remote
         *                    Inesonic infrastructure.
         */
        public function setTimeDelta(string $time_delta) {
            $this->time_delta = $time_delta;
        }

        /**
         * Method you can use to obtain you currently employed time delta
         * value.
         *
         * \return Returns the current time delta value.
         */
        public function timeDelta() {
            return $this->time_delta;
        }

        /**
         * Method you can use to specify a callback function that can be used
         * to store off time delta values.  The function will be called
         * whenever the REST API detects a time delta error.
         *
         * The callback function is defined to be usable with the WordPress
         * update_option function.  Any function you employ should be of the
         * form:
         *
         * \rst:leading-asterisk
         * .. code-block:: php
         *
         *    callback(string $option, $new_value);
         * \endverbatim
         *
         * The option string is as specified by the
         * \ref TIME_DELTA_OPTION constant.
         *
         * \param $time_delta_callback The time delta callback to be used.  A
         *                             value of null (default) disables the use
         *                             of a callback function.
         */
        public function setTimeDeltaCallback($time_delta_callback) {
            $this->time_delta_callback = $time_delta_callback;
        }

        /**
         * Method you can use to obtain you currently employed time delta
         * callback function.
         *
         * \return Returns the current time delta callback function.  A value
         *         of null is returned if time delta callbacks are not enabled.
         */
        public function timeDeltaCallback() {
            return $this->time_delta_callback;
        }

        /**
         * Method you can use to determine your capabilities based on your
         * current subscription.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an associative array indicating what features you have
         *     access to.  The associative array will contain the fields
         *     listed in :numref:`PHP Capabilities Get Return Values`.  A null
         *     value is returned on error.
         *
         *     .. tabularcolumns:: |p{2.0 in}|p{0.4 in}|p{2.2 in}|
         *     .. _PHP Capabilities Get Return Values:
         *     .. table:: capabilitiesGet Return Values
         *        :align: center
         *        :widths: 25 10 80
         *
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | Key                              | Type | Holds                                                   |
         *        +==================================+======+=========================================================+
         *        | customer_active                  | bool | Boolean indicating if this customer has confirmed their |
         *        |                                  |      | email address.                                          |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | maximum_number_monitors          | int  | Integer value indicating the maximum number of monitors |
         *        |                                  |      | this customer can setup under their subscription.       |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | multi_region_checking            | bool | Boolean indicating if the customer's monitors are       |
         *        |                                  |      | tracked across multiple geographic regions.             |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | paused                           | bool | Boolean indicating true if maintenance mode is enabled  |
         *        |                                  |      | and monitoring is temporarily paused.                   |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | polling_interval                 | int  | Integer value indicating the seconds between test       |
         *        |                                  |      | messages to each server.                                |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_content_checking        | bool | Boolean value indicating if the customer can use        |
         *        |                                  |      | Inesonic's content checking feature.                    |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_keyword_checking        | bool | Boolean value indicating if the customer can use        |
         *        |                                  |      | Inesonic's keyword checking feature.                    |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_latency_tracking        | bool | Boolean value indicating if site reponse times are      |
         *        |                                  |      | measured and recorded.                                  |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_maintenance_mode        | bool | Boolean value indicating if the customer can use        |
         *        |                                  |      | maintenance mode.                                       |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_ping_based_polling      | bool | Boolean value indicating if echo ICMP frames will be    |
         *        |                                  |      | sent to the server to improve or ability to detect when |
         *        |                                  |      | a site goes down.                                       |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_post_method             | bool | Boolean value indicating if the customer can use all    |
         *        |                                  |      | supported HTTP methods/verbs to validate forms and REST |
         *        |                                  |      | APIs.                                                   |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_rest_api                | bool | Boolean value indicating if the customer can use the    |
         *        |                                  |      | full capabilities of the Inesonic REST API.             |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_ssl_expiration_checking | bool | Boolean value indicating if SSL expiration checking     |
         *        |                                  |      | will be performed.                                      |
         *        +----------------------------------+------+---------------------------------------------------------+
         *        | supports_wordpress               | bool | Boolean value indicating if the customer can use the    |
         *        |                                  |      | limited WordPress REST api features.                    |
         *        +----------------------------------+------+---------------------------------------------------------+
         *
         * \endrst
         */
        public function capabilitiesGet() {
            $response = $this->postMessage(
                array(),
                self::CAPABILITIES_GET_ROUTE
            );

            if ($response !== null                          &&
                array_key_exists('status', $response)       &&
                $response['status'] == 'OK'                 &&
                array_key_exists('capabilities', $response)    ) {
                $result = $response['capabilities'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain information about a specific host or
         * authority.
         *
         * \param $host_scheme_id The ID of the host to obtain information for.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an associative array holding information about this
         *     host.  The associative array will contains the members listed in
         *     :numref:`PHP Hosts Get Return Values`.  A null value is returned
         *     on error.
         *
         *     .. tabularcolumns:: |p{1.5 in}|p{0.4 in}|p{2.8 in}|
         *     .. _PHP Hosts Get Return Values:
         *     .. table:: hostsGet Return Values
         *        :align: center
         *        :widths: 20 10 80
         *
         *        +--------------------------+--------+---------------------------------------------------------------+
         *        | Key                      | Type   | Holds                                                         |
         *        +==========================+========+===============================================================+
         *        | host_scheme_id           | int    | The internal ID used to reference this host.                  |
         *        +--------------------------+--------+---------------------------------------------------------------+
         *        | ssl_expiration_timestamp | int    | The Unix timestamp indicating when the SSL certificate for    |
         *        |                          |        | this host is going to expire.  A value of 0 is provided if    |
         *        |                          |        | there is no certificate or the data has not yet been read.    |
         *        +--------------------------+--------+---------------------------------------------------------------+
         *        | url                      | string | The authority for this host.                                  |
         *        +--------------------------+--------+---------------------------------------------------------------+
         *
         * \endrst
         */
        public function hostsGet(int $host_scheme_id) {
            $response = $this->postMessage(
                array('host_scheme_id' => $host_scheme_id),
                self::HOSTS_GET_ROUTE
            );

            if ($response !== null                         &&
                array_key_exists('status', $response)      &&
                $response['status'] == 'OK'                &&
                array_key_exists('host_scheme', $response)    ) {
                $result = $response['host_scheme'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain a list of hosts/authorities for the
         * current customer.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an associative array indexed by host/scheme ID.  Each
         *     array entry is also an associative array holding values
         *     documented in :numref:`PHP Hosts Get Return Values`.  A null
         *     value is returned on error.
         *
         * \endrst
         */
        public function hostsList() {
            $response = $this->postMessage(array(), self::HOSTS_LIST_ROUTE);
            if ($response !== null                          &&
                array_key_exists('status', $response)       &&
                $response['status'] == 'OK'                 &&
                array_key_exists('host_schemes', $response)    ) {
                $result = $response['host_schemes'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain information about a specific monitor.
         *
         * \param $monitor_id The ID of the monitor to retrieve information
         *                    for.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an associative array indexed by host/scheme ID.  Each
         *     array entry is an associative array holding the key/value pairs
         *     listed in :numref:`PHP Monitors Get Return Values`.  A null
         *     value is returned on error.
         *
         *     .. tabularcolumns:: |p{1.3 in}|p{0.4 in}|p{3.0 in}|
         *     .. _PHP Monitors Get Return Values:
         *     .. table:: monitorsGet Return Values
         *        :align: center
         *        :widths: 20 10 80
         *
         *        +--------------------+--------+-----------------------------+
         *        | Key                | Type   | Holds                       |
         *        +====================+========+=============================+
         *        | monitor_id         | int    | The ID of the requested     |
         *        |                    |        | monitor.                    |
         *        +--------------------+--------+-----------------------------+
         *        | host_scheme_id     | int    | The host/scheme ID of the   |
         *        |                    |        | server associated with this |
         *        |                    |        | monitor.                    |
         *        +--------------------+--------+-----------------------------+
         *        | path               | string | The path under the          |
         *        |                    |        | host/scheme to this         |
         *        |                    |        | monitor                     |
         *        +--------------------+--------+-----------------------------+
         *        | user_ordering      | int    | An integer value starting   |
         *        |                    |        | from 0 indicating the       |
         *        |                    |        | position of this monitor in |
         *        |                    |        | the Inesonic Account        |
         *        |                    |        | Settings page.              |
         *        +--------------------+--------+-----------------------------+
         *        | method             | string | A string holding the HTTP   |
         *        |                    |        | method or verb used to      |
         *        |                    |        | access the page or          |
         *        |                    |        | endpoint supported values   |
         *        |                    |        | are:                        |
         *        |                    |        |                             |
         *        |                    |        | * ``get``                   |
         *        |                    |        | * ``head``                  |
         *        |                    |        | * ``post``                  |
         *        |                    |        | * ``put``                   |
         *        |                    |        | * ``delete``                |
         *        |                    |        | * ``options``               |
         *        |                    |        | * ``patch``                 |
         *        +--------------------+--------+-----------------------------+
         *        | content_check_mode | string | A string holding the        |
         *        |                    |        | desired content check mode. |
         *        |                    |        | Supported values are:       |
         *        |                    |        |                             |
         *        |                    |        | * ``no_check``              |
         *        |                    |        | * ``content_match``         |
         *        |                    |        | * ``all_keywords``          |
         *        |                    |        | * ``any_keywords``          |
         *        +--------------------+--------+-----------------------------+
         *        | keywords           | array  | An array holding the        |
         *        |                    |        | keywords to check for. Each |
         *        |                    |        | keyword will be base-64     |
         *        |                    |        | encoded as per RFC 4648.    |
         *        +--------------------+--------+-----------------------------+
         *        | user_agent         | string | Value used with POST, PUT,  |
         *        |                    |        | PATCH, and DELETE.          |
         *        |                    |        | Indicates the User-Agent    |
         *        |                    |        | string to report in the     |
         *        |                    |        | request header.             |
         *        +--------------------+--------+-----------------------------+
         *        | content_type       | string | Value used only with POST,  |
         *        |                    |        | PUT, PATCH, and DELETE.     |
         *        |                    |        | Value indicates the         |
         *        |                    |        | Content-Type to report in   |
         *        |                    |        | the request header.         |
         *        |                    |        | Supported values are listed |
         *        |                    |        | in                          |
         *        |                    |        | :numref:`PHP Content Type`  |
         *        +--------------------+--------+-----------------------------+
         *        | post_content       | string | Value used only with POST,  |
         *        |                    |        | PUT, PATCH, and DELETE.     |
         *        |                    |        | Value contains the content  |
         *        |                    |        | sent in the request body.   |
         *        |                    |        | The supplied data is        |
         *        |                    |        | base-64 encoded as per      |
         *        |                    |        | RFC-4648.                   |
         *        +--------------------+--------+-----------------------------+
         *
         *     .. tabularcolumns:: |p{0.4 in}|p{1.8 in}|
         *     .. _PHP Content Type:
         *     .. table:: PHP Content-Type Values
         *        :align: center
         *
         *        +----------+---------------------------+
         *        | Value    | Header Content-Type Value |
         *        +==========+===========================+
         *        | ``text`` | text/plain                |
         *        +----------+---------------------------+
         *        | ``json`` | application/json          |
         *        +----------+---------------------------+
         *        | ``xml``  | application/xml           |
         *        +----------+---------------------------+
         *
         * \endrst
         */
        public function monitorsGet(int $monitor_id) {
            $response = $this->postMessage(
                array('monitor_id' => $monitor_id),
                self::MONITORS_GET_ROUTE
            );

            if ($response !== null                     &&
                array_key_exists('status', $response)  &&
                $response['status'] == 'OK'            &&
                array_key_exists('monitor', $response)    ) {
                $result = $response['monitor'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain a list of monitors.
         *
         * \rst:leading-asterisk
         *
         * :param $order_by:
         *     A value indicating the desired format of the return data.
         *     Accepted values are listed in
         *     :numref:`PHP Monitor List Order Values`.
         *
         *     .. tabularcolumns:: |p{1.0 in}|p{3.8 in}|
         *     .. _PHP Monitor List Order Values:
         *     .. table:: PHP Monitor List $order_by Values
         *        :align: center
         *        :widths: 15 80
         *
         *        +-------------------+---------------------------------------+
         *        | Value             | Returned Ordering                     |
         *        +===================+=======================================+
         *        | ``monitor_id``    | The returned list will be indexed by  |
         *        |                   | The internal monitor ID.              |
         *        +-------------------+---------------------------------------+
         *        | ``user_ordering`` | The returned list will be indexed by  |
         *        |                   | the user ordering in the Account      |
         *        |                   | settings page.   The first entry will |
         *        |                   | be "0".                               |
         *        +-------------------+---------------------------------------+
         *        | ``url``           | The returned list will be indexed by  |
         *        |                   | the full URL of the monitor.          |
         *        +-------------------+---------------------------------------+
         *
         * :return:
         *     Returns an associative array indexed by either the monitor ID,
         *     the user ordering, or URL.  Each array entry is an associative
         *     holding the values listed in
         *     :numref:`PHP Monitors List Return Values`.
         *
         *     .. tabularcolumns:: |p{1.3 in}|p{0.4 in}|p{3.0 in}|
         *     .. _PHP Monitors List Return Values:
         *     .. table:: monitorsList Return Values
         *        :align: center
         *        :widths: 20 10 80
         *
         *        +--------------------+--------+-----------------------------+
         *        | Key                | Type   | Holds                       |
         *        +====================+========+=============================+
         *        | monitor_id         | int    | The ID of the requested     |
         *        |                    |        | monitor.                    |
         *        +--------------------+--------+-----------------------------+
         *        | host_scheme_id     | int    | The host/scheme ID of the   |
         *        |                    |        | server associated with this |
         *        |                    |        | monitor.                    |
         *        +--------------------+--------+-----------------------------+
         *        | path               | string | The path under the          |
         *        |                    |        | host/scheme to this         |
         *        |                    |        | monitor.                    |
         *        +--------------------+--------+-----------------------------+
         *        | url                | string | The full URL used to access |
         *        |                    |        | the monitor.                |
         *        +--------------------+--------+-----------------------------+
         *        | user_ordering      | int    | An integer value starting   |
         *        |                    |        | from 0 indicating the       |
         *        |                    |        | position of this monitor in |
         *        |                    |        | the Inesonic Account        |
         *        |                    |        | Settings page.              |
         *        +--------------------+--------+-----------------------------+
         *        | method             | string | A string holding the HTTP   |
         *        |                    |        | method or verb used to      |
         *        |                    |        | access the page or          |
         *        |                    |        | endpoint supported values   |
         *        |                    |        | are:                        |
         *        |                    |        |                             |
         *        |                    |        | * ``get``                   |
         *        |                    |        | * ``head``                  |
         *        |                    |        | * ``post``                  |
         *        |                    |        | * ``put``                   |
         *        |                    |        | * ``delete``                |
         *        |                    |        | * ``options``               |
         *        |                    |        | * ``patch``                 |
         *        +--------------------+--------+-----------------------------+
         *        | content_check_mode | string | A string holding the        |
         *        |                    |        | desired content check mode. |
         *        |                    |        | Supported values are:       |
         *        |                    |        |                             |
         *        |                    |        | * ``no_check``              |
         *        |                    |        | * ``content_match``         |
         *        |                    |        | * ``all_keywords``          |
         *        |                    |        | * ``any_keywords``          |
         *        +--------------------+--------+-----------------------------+
         *        | keywords           | array  | An array holding the        |
         *        |                    |        | keywords to check for. Each |
         *        |                    |        | keyword will be base-64     |
         *        |                    |        | encoded as per RFC-4648.    |
         *        +--------------------+--------+-----------------------------+
         *        | user_agent         | string | Value used with POST, PUT,  |
         *        |                    |        | PATCH, and DELETE.          |
         *        |                    |        | Indicates the User-Agent    |
         *        |                    |        | string to report in the     |
         *        |                    |        | request header.             |
         *        +--------------------+--------+-----------------------------+
         *        | content_type       | string | Value used only with POST,  |
         *        |                    |        | PUT, PATCH, and DELETE.     |
         *        |                    |        | Value indicates the         |
         *        |                    |        | Content-Type to report in   |
         *        |                    |        | the request header.         |
         *        |                    |        | Supported values are listed |
         *        |                    |        | in                          |
         *        |                    |        | :numref:`PHP Content Type`  |
         *        +--------------------+--------+-----------------------------+
         *        | post_content       | string | Value used only with POST,  |
         *        |                    |        | PUT, PATCH, and DELETE.     |
         *        |                    |        | DELETE.  Value contains the |
         *        |                    |        | content sent in the request |
         *        |                    |        | body.  The supplied data is |
         *        |                    |        | base-64 encoded as per      |
         *        |                    |        | RFC-4648.                   |
         *        +--------------------+--------+-----------------------------+
         *
         * \endrst
         */
        public function monitorsList(string $order_by = 'monitor_id') {
            $response = $this->postMessage(
                array('order_by' => $order_by),
                self::MONITORS_LIST_ROUTE
            );

            if ($response !== null                      &&
                array_key_exists('status', $response)   &&
                $response['status'] == 'OK'             &&
                array_key_exists('monitors', $response)    ) {
                $result = $response['monitors'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to update the customer's monitor settings.
         *
         * \rst:leading-asterisk
         *
         * :param $monitor_data:
         *     An associative array indexed by a zero based user ordering
         *     value.  Each array entry should be an associative array
         *     containing the parameters listed in
         *     :numref:`PHP Monitor Update Parameter Values`.  Note that
         *     most parameters have default values and are optional.
         *
         *     .. tabularcolumns:: |p{1.3 in}|p{0.4 in}|p{0.7 in}|p{2.1 in}|
         *     .. _PHP Monitor Update Parameter Values:
         *     .. table:: monitorsUpdate Parameter Values
         *        :align: center
         *        :widths: 20 10 20 80
         *
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *        | Key                | Type   | Default    | Function                                               |
         *        +====================+========+============+========================================================+
         *        | uri                | string | -required- | Either a full URL with a host and scheme or a path     |
         *        |                    |        |            | indicating the location to be monitored.  If just a    |
         *        |                    |        |            | path is provided then the host/scheme from the         |
         *        |                    |        |            | previous monitor, based on user ordering, will be      |
         *        |                    |        |            | used.                                                  |
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *        | method             | string | 'get'      | The HTTP method used to access the endpoint.  Specify  |
         *        |                    |        |            | one of:                                                |
         *        |                    |        |            |                                                        |
         *        |                    |        |            | * ``get``                                              |
         *        |                    |        |            | * ``head``                                             |
         *        |                    |        |            | * ``post``                                             |
         *        |                    |        |            | * ``put``                                              |
         *        |                    |        |            | * ``delete``                                           |
         *        |                    |        |            | * ``options``                                          |
         *        |                    |        |            | * ``patch``                                            |
         *        |                    |        |            |                                                        |
         *        |                    |        |            | If not specified, then 'get' is assumed.               |
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *        | content_check_mode | string | 'no_check' | The desired content check mode.  Supported values are: |
         *        |                    |        |            |                                                        |
         *        |                    |        |            | * ``no_check``                                         |
         *        |                    |        |            | * ``content_match``                                    |
         *        |                    |        |            | * ``all_keywords``                                     |
         *        |                    |        |            | * ``any_keywords``                                     |
         *        |                    |        |            |                                                        |
         *        |                    |        |            | If not specified, then 'no_check' is assumed.          |
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *        | keywords           | array  | array()    | A list of keywords to check for.  Each keyword should  |
         *        |                    |        |            | be base-64 encoded as per RFC 4648.                    |
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *        | post_content_type  | string | 'text'     | The post content type to provide in the POST header.   |
         *        |                    |        |            | Supported values are listed in                         |
         *        |                    |        |            | :numref:`PHP Content Type`.  If not specified, then    |
         *        |                    |        |            | 'text' is assumed.                                     |
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *        | post_user_agent    | string | ''         | The user agent to report in the post header.  If not   |
         *        |                    |        |            | specified or an empty string, then an default          |
         *        |                    |        |            | User-Agent string will be reported.                    |
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *        | post_content       | string | ''         | The post content to be sent.  Value should be base-64  |
         *        |                    |        |            | encoded as per RFC 4648.                               |
         *        +--------------------+--------+------------+--------------------------------------------------------+
         *
         * :return:
         *     Return an empty array on success.  On error, this method returns
         *     an array of error where each entry includes a user ordering
         *     value and an error message.   A null value is returned if a
         *     communications error occurred.
         *
         * \endrst
         */
        public function monitorsUpdate(array $monitor_data) {
            $response = $this->postMessage(
                $monitor_data,
                self::MONITORS_UPDATE_ROUTE
            );

            if ($response !== null && array_key_exists('status', $response)) {
                $status = $response['status'];
                if ($status == 'OK') {
                    $result = array();
                } else {
                    $result = $response['errors'];
                }
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain a textual description of a geographic
         * region where latency measurements were taken.
         *
         * \param $region_id The ID of the region to obtain a textual
         *                   description for.
         *
         * \return Returns a textual description of the requested region.  A
         *         null value is returned on error.
         */
        public function regionsGet(int $region_id) {
            $response = $this->postMessage(
                array('region_id' => $region_id),
                self::REGIONS_GET_ROUTE
            );

            if ($response !== null                    &&
                array_key_exists('status', $response) &&
                $response['status'] == 'OK'           &&
                array_key_exists('region', $response)    ) {
                $result = $response['region']['description'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain an array of regions indexed by region
         * ID.
         *
         * \return Returns an array of regions indexed by region ID.  Each
         *         entry is an array containing a 'region_id' entry and
         *         'description' entry.  A null value is returned on error.
         */
        public function regionsList() {
            $response = $this->postMessage(array(), self::REGIONS_LIST_ROUTE);
            if ($response !== null                     &&
                array_key_exists('status', $response)  &&
                $response['status'] == 'OK'            &&
                array_key_exists('regions', $response)    ) {
                $result = $response['regions'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain event data by event ID.
         *
         * \param $event_id The ID of the event to obtain information on.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an array providing information on the event.  The array
         *     will contain the members listed in
         *     :numref:`PHP Events Get Return Values`.
         *
         *     .. tabularcolumns:: |p{0.7 in}|p{0.4 in}|p{3.3 in}|
         *     .. _PHP Events Get Return Values:
         *     .. table:: eventsGet Return Values
         *        :align: center
         *        :widths: 15 10 80
         *
         *        +------------+--------+-------------------------------------+
         *        | Key        | Type   | Function                            |
         *        +============+========+=====================================+
         *        | event_id   | int    | The ID used to identify this event. |
         *        +------------+--------+-------------------------------------+
         *        | message    | string | The type of event that occurred.    |
         *        |            |        | Value will be one of:               |
         *        |            |        |                                     |
         *        |            |        | * ``no_response``                   |
         *        |            |        | * ``working``                       |
         *        |            |        | * ``content_changed``               |
         *        |            |        | * ``keywords``                      |
         *        |            |        | * ``ssl_certificate_expiring``      |
         *        |            |        | * ``ssl_certificate_renewed``       |
         *        |            |        | * ``custom_1``                      |
         *        |            |        | * ``custom_2``                      |
         *        |            |        | * ``custom_3``                      |
         *        |            |        | * ``custom_4``                      |
         *        |            |        | * ``custom_5``                      |
         *        |            |        | * ``custom_6``                      |
         *        |            |        | * ``custom_7``                      |
         *        |            |        | * ``custom_8``                      |
         *        |            |        | * ``custom_9``                      |
         *        |            |        | * ``custom_10``                     |
         *        +------------+--------+-------------------------------------+
         *        | monitor_id | int    | The monitor ID of the monitor that  |
         *        |            |        | triggered the event.                |
         *        +------------+--------+-------------------------------------+
         *        | timestamp  | int    | The Unix timestamp indicating when  |
         *        |            |        | the event occurred.                 |
         *        +------------+--------+-------------------------------------+
         *
         * \endrst
         */
        public function eventsGet(int $event_id) {
            $response = $this->postMessage(
                array('event_id' => $event_id),
                self::EVENTS_GET_ROUTE
            );

            if ($response !== null                    &&
                array_key_exists('status', $response) &&
                $response['status'] == 'OK'           &&
                array_key_exists('event', $response)     ) {
                $result = $response['event'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain a list of events.
         *
         * \param $start_timestamp The starting timestamp for the events to be
         *                         returned.  Value is inclusive.  A value of
         *                         zero means no start time.
         *
         * \param $end_timestamp   The ending timestmap for the events to be
         *                         returned.  Value is inclusive.  A value of
         *                         0 means "now".
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an array of events in time order with each entry
         *     containing the values listed in :numref:
         *     :numref:`PHP Events List Return Values`.
         *
         *     .. tabularcolumns:: |p{0.7 in}|p{0.4 in}|p{3.3 in}|
         *     .. _PHP Events List Return Values:
         *     .. table:: eventsList Return Values
         *        :align: center
         *        :widths: 15 10 80
         *
         *        +------------+--------+-------------------------------------+
         *        | Key        | Type   | Function                            |
         *        +============+========+=====================================+
         *        | event_id   | int    | The ID used to identify this event. |
         *        +------------+--------+-------------------------------------+
         *        | message    | string | The type of event that occurred.    |
         *        |            |        | Value will be one of:               |
         *        |            |        |                                     |
         *        |            |        | * ``no_response``                   |
         *        |            |        | * ``working``                       |
         *        |            |        | * ``content_changed``               |
         *        |            |        | * ``keywords``                      |
         *        |            |        | * ``ssl_certificate_expiring``      |
         *        |            |        | * ``ssl_certificate_renewed``       |
         *        |            |        | * ``custom_1``                      |
         *        |            |        | * ``custom_2``                      |
         *        |            |        | * ``custom_3``                      |
         *        |            |        | * ``custom_4``                      |
         *        |            |        | * ``custom_5``                      |
         *        |            |        | * ``custom_6``                      |
         *        |            |        | * ``custom_7``                      |
         *        |            |        | * ``custom_8``                      |
         *        |            |        | * ``custom_9``                      |
         *        |            |        | * ``custom_10``                     |
         *        +------------+--------+-------------------------------------+
         *        | monitor_id | int    | The monitor ID of the monitor that  |
         *        |            |        | triggered the event.                |
         *        +------------+--------+-------------------------------------+
         *        | timestamp  | int    | The Unix timestamp indicating when  |
         *        |            |        | the event occurred.                 |
         *        +------------+--------+-------------------------------------+
         *
         * \endrst
         */
        public function eventsList(
                int $start_timestamp = 0,
                int $end_timestamp = 0
            ) {
            $request = array('start_timestamp' => $start_timestamp);
            if ($end_timestamp != 0) {
                $request['end_timestamp'] = $end_timestamp;
            }

            $response = $this->postMessage($request, self::EVENTS_LIST_ROUTE);
            if ($response !== null                    &&
                array_key_exists('status', $response) &&
                $response['status'] == 'OK'           &&
                array_key_exists('events', $response)    ) {
                $result = $response['events'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to create a new customer event.
         *
         * \param $event_type An integer value between 1 and 10 indicating the
         *                    desired custom event type.
         *
         * \param $message    The message to be sent.
         *
         * \param $monitor_id The ID of the monitor to tie to this event.  A
         *                    value of 0 (default) will cause the event to be
         *                    tied to the first monitor.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function eventsCreate(
                int    $event_type,
                string $message,
                int    $monitor_id = 0
            ) {
            $request = array(
                'type' => $event_type,
                'message' => $message
            );

            if ($monitor_id) {
                $request['monitor_id'] = $monitor_id;
            }

            $response = $this->postMessage(
                $request,
                self::EVENTS_CREATE_ROUTE
            );

            return (
                   $response !== null
                && array_key_exists('status', $response)
                && $response['status'] == 'OK'
            );
        }

        /**
         * Method you can use to obtain the last reported status of a monitor.
         *
         * \param $monitor_id The ID of the monitor being queried.
         *
         * \return Returns one of 'failed', 'working', 'unknown'.  An unknown
         *         status indicates either the endpoint has never been tested
         *         or no data has been reported yet.  A null value is returned
         *         on error.
         */
        public function statusGet(int $monitor_id) {
            $response = $this->postMessage(
                array('monitor_id' => $monitor_id),
                self::STATUS_GET_ROUTE
            );

            if ($response !== null                            &&
                array_key_exists('status', $response)         &&
                $response['status'] == 'OK'                   &&
                array_key_exists('monitor_status', $response)    ) {
                $result = $response['monitor_status'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain the last reported status of all
         * monitors.
         *
         * \return Returns an array indexed by monitor ID.  Each entry will
         *         contain one of 'failed', 'working', or 'unknown'.  An
         *         unknown status indicates either the endpoint has never been
         *         tested or no data has been reported yet.  A null value is
         *         returned on error.
         */
        public function statusList() {
            $response = $this->postMessage(array(), self::STATUS_LIST_ROUTE);
            if ($response !== null                            &&
                array_key_exists('status', $response)         &&
                $response['status'] == 'OK'                   &&
                array_key_exists('monitor_status', $response)    ) {
                $result = $response['monitor_status'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain the multiple sets of values
         * simultaneously.  The function exists specifically to support
         * WordPress.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an array containing the entries listed in
         *     :numref:`PHP Multiple List Return Values`.
         *
         *     .. tabularcolumns:: |p{0.6 in}|p{4.2 in}|
         *     .. _PHP Multiple List Return Values:
         *     .. table:: multipleList Return Values
         *        :align: center
         *        :widths: 15 80
         *
         *        +-------------+---------------------------------------------+
         *        | Key         | Contains                                    |
         *        +=============+=============================================+
         *        | monitors    | A list of monitors by user order.  Each     |
         *        |             | entry will be a dictionary containing the   |
         *        |             | key-value pairs documented in               |
         *        |             | :numref:`PHP Monitors List Return Values`.  |
         *        +-------------+---------------------------------------------+
         *        | authorities | A list of host/scheme entries indexed by    |
         *        |             | host_scheme_id.  Each entry will be a       |
         *        |             | dictionary containing the key-value pairs   |
         *        |             | documented in                               |
         *        |             | :numref:`PHP Hosts Get Return Values`.      |
         *        +-------------+---------------------------------------------+
         *        | events      | A list of events in time order.  Each entry |
         *        |             | will be a dictionary containing the         |
         *        |             | key-value pairs documented in               |
         *        |             | :numref:`PHP Events Get Return Values`.     |
         *        +-------------+---------------------------------------------+
         *        | status      | A dictionary of status values by monitor    |
         *        |             | ID.  Each entry will contain one of         |
         *        |             |                                             |
         *        |             | * ``unknown``                               |
         *        |             | * ``working``                               |
         *        |             | * ``failed``                                |
         *        +-------------+---------------------------------------------+
         *
         * \endrst
         */
        public function multipleList() {
            $response = $this->postMessage(array(), self::MULTIPLE_LIST_ROUTE);
            if ($response !== null                            &&
                array_key_exists('status', $response)         &&
                $response['status'] == 'OK'                   &&
                array_key_exists('monitors', $response)       &&
                array_key_exists('host_schemes', $response)   &&
                array_key_exists('events', $response)         &&
                array_key_exists('monitor_status', $response)    ) {
                $result = array(
                    'monitors' => $response['monitors'],
                    'authorities' => $response['host_schemes'],
                    'events' => $response['events'],
                    'status' => $response['monitor_status']
                );
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain latency data.  Be aware that this
         * method can return a very large amount of data.
         *
         * \param $monitor_id      The monitor to retrieve data over.  A value
         *                         of 0 indicates all monitors.
         *
         * \param $region_id       The region to retrieve data over.  A value
         *                         of 0 indicates all regions.
         *
         * \param $start_timestamp The starting timestamp to retrieve data
         *                         over.  A value of 0 indicates no start
         *                         timestamp.
         *
         * \param $end_timestamp   The ending timestamp to retrieve data over.
         *                         A value of 0 indicates no end timestamp.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an array containing two entries as listed in
         *     :numref:`PHP Latency List Return Values`.
         *
         *     .. tabularcolumns:: |p{0.7 in}|p{4.1 in}|
         *     .. _PHP Latency List Return Values:
         *     .. table:: latencyList Return Values
         *        :align: center
         *        :widths: 15 80
         *
         *        +------------+----------------------------------------------+
         *        | Key        | Contains                                     |
         *        +============+==============================================+
         *        | latency    | A list of recent raw latency values.  Values |
         *        |            | will be over roughly the last 30 days.  Each |
         *        |            | entry will be a dictionary of key-value      |
         *        |            | pairs as documented in                       |
         *        |            | :numref:`PHP Raw Latency Return Values`.     |
         *        +------------+----------------------------------------------+
         *        | aggregated | A list of aggregated latency values.  Values |
         *        |            | will represent collections of older latency  |
         *        |            | data taken more than roughly 30 days ago.    |
         *        |            | Each entry will be a dictionary of key-value |
         *        |            | pairs as documented in                       |
         *        |            | :numref:`PHP Aggr Latency Return Values`.    |
         *        +------------+----------------------------------------------+
         *
         *     .. tabularcolumns:: |p{0.7 in}|p{0.4 in}|p{3.5 in}|
         *     .. _PHP Raw Latency Return Values:
         *     .. table:: Raw Latency Return Values
         *        :align: center
         *        :widths: 15 10 80
         *
         *        +------------+-------+--------------------------------------+
         *        | Key        | Type  |Contains                              |
         *        +============+=======+======================================+
         *        | timestamp  | int   | The Unix timestamp when this value   |
         *        |            |       | was collected.                       |
         *        +------------+-------+--------------------------------------+
         *        | monitor_id | int   | The monitor ID of the monitor        |
         *        |            |       | measured for this value.             |
         *        +------------+-------+--------------------------------------+
         *        | region_id  | int   | The region ID of the region where    |
         *        |            |       | the measurement was taken from.      |
         *        +------------+-------+--------------------------------------+
         *        | latency    | float | The raw latency value, in seconds.   |
         *        +------------+-------+--------------------------------------+
         *
         *     .. tabularcolumns:: |p{1.0 in}|p{0.4 in}|p{3.2 in}|
         *     .. _PHP Aggr Latency Return Values:
         *     .. table:: Aggregated Latency Return Values
         *        :align: center
         *        :widths: 20 10 80
         *
         *        +-----------------+-------+---------------------------------+
         *        | Key             | Type  | Contains                        |
         *        +=================+=======+=================================+
         *        | monitor_id      | int   | The monitor ID of the monitor   |
         *        |                 |       | measured for this value.        |
         *        +-----------------+-------+---------------------------------+
         *        | region_id       | int   | The region ID of the region     |
         *        |                 |       | where this measurement was      |
         *        |                 |       | taken.                          |
         *        +-----------------+-------+---------------------------------+
         *        | timestamp       | int   | A timestamp associated with a   |
         *        |                 |       | randomly selected value within  |
         *        |                 |       | the aggregation set.            |
         *        +-----------------+-------+---------------------------------+
         *        | latency         | float | A randomly selected latency     |
         *        |                 |       | value within the aggregation    |
         *        |                 |       | set.  Value is in seconds.      |
         *        +-----------------+-------+---------------------------------+
         *        | start_timestamp | int   | The timestamp of the first      |
         *        |                 |       | latency value used to create    |
         *        |                 |       | this aggregation set.           |
         *        +-----------------+-------+---------------------------------+
         *        | end_timestamp   | int   | The timestamp of the last       |
         *        |                 |       | latency value used to create    |
         *        |                 |       | this aggregation.               |
         *        +-----------------+-------+---------------------------------+
         *        | average         | float | The average value of the raw    |
         *        |                 |       | latency values used to create   |
         *        |                 |       | this aggregation.  Value is in  |
         *        |                 |       | seconds.                        |
         *        +-----------------+-------+---------------------------------+
         *        | variance        | float | The population variance of the  |
         *        |                 |       | raw latency values used to      |
         *        |                 |       | create this aggregation.  Value |
         *        |                 |       | is in seconds-squared.          |
         *        +-----------------+-------+---------------------------------+
         *        | minimum         | float | The mimumum raw latency value   |
         *        |                 |       | found when creating this        |
         *        |                 |       | aggregation.  Value is in       |
         *        |                 |       | seconds.                        |
         *        +-----------------+-------+---------------------------------+
         *        | maximum         | float | The maximum raw latency value   |
         *        |                 |       | found when creating this        |
         *        |                 |       | aggregation.  Value is in       |
         *        |                 |       | seconds.                        |
         *        +-----------------+-------+---------------------------------+
         *        | number_samples  | int   | The number of raw latency       |
         *        |                 |       | measurements that were used to  |
         *        |                 |       | create this aggregation set.    |
         *        +-----------------+-------+---------------------------------+
         *
         * \endrst
         */
        public function latencyList(
                int $monitor_id = 0,
                int $region_id = 0,
                int $start_timestamp = 0,
                int $end_timestamp = 0
            ) {
            $request = array('start_timestamp' => $start_timestamp);

            if ($end_timestamp != 0) {
                $request['end_timestamp'] = $end_timestamp;
            }

            if ($monitor_id != 0) {
                $request['monitor_id'] = $monitor_id;
            }

            if ($region_id != 0) {
                $request['region_id'] = $region_id;
            }

            $response = $this->postMessage(
                $request,
                self::LATENCY_LIST_ROUTE
            );

            if ($response !== null                        &&
                array_key_exists('status', $response)     &&
                $response['status'] == 'OK'               &&
                array_key_exists('recent', $response)     &&
                array_key_exists('aggregated', $response)    ) {
                $result = array(
                    'recent' => $response['recent'],
                    'aggregated' => $response['aggregated']
                );
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain a plot of latency data.
         *
         * \rst:leading-asterisk
         *
         * :param $settings:
         *     The plot settings array.  This value supports a large number of
         *     possible fields listed in :numref:`PHP Latency Plot Parameters`.
         *     Note that most parameters are optional.  Reasonable default
         *     values will be used for omitted parameters.
         *
         *     .. tabularcolumns:: |p{1.1 in}|p{0.4 in}|p{1.4 in}|p{1.6 in}|
         *     .. _PHP Latency Plot Parameters:
         *     .. table:: latencyPlot Parameters
         *        :align: center
         *        :widths: 20 10 45 80
         *
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | Key             | Type   | Default Value        | Function                                        |
         *        +=================+========+======================+=================================================+
         *        | plot_type       | string | 'history'            | Indicates the desired type of plot.  Supported  |
         *        |                 |        |                      | values are 'history' and 'histogram'.           |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | host_scheme_id  | int    | -all-                | Indicates the plot should be limited to         |
         *        |                 |        |                      | monitors on this server.  This value is         |
         *        |                 |        |                      | mutually exclusive with 'monitor_id'.           |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | monitor_id      | int    | -all-                | Indicates the plot should be limited to this    |
         *        |                 |        |                      | monitor.  This value is mutually exclusive with |
         *        |                 |        |                      | 'host_scheme_id'.                               |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | region_id       | int    | -all-                | Indicates the plot should be limited to one or  |
         *        |                 |        |                      | more monitors in one region.                    |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | start_timestamp | int    | 0                    | Indicates the plot should exclude values before |
         *        |                 |        |                      | this time.                                      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | end_timestamp   | int    | -now-                | Indicates the plot should exclude values after  |
         *        |                 |        |                      | this time.                                      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | title           | string | 'Latency Over Time'  | A UTF-8 encoded title to include in the plot.   |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | x_axis_label    | string | 'Date/Time'          | The UTF-8 encoded text for the X axis label.    |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | y_axis_label    | string | 'Latency (seconds)'  | The UTF-8 encoded text for the Y axis label.    |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | date_format     | string | 'MMM dd yyy - hh:mm' | The date format to apply to date/time fields.   |
         *        |                 |        |                      | For details, see :ref:`Date Format Codes`.      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | title_font      | string | -dafault-            | The title font to be used.   For details, see   |
         *        |                 |        |                      | :ref:`Font Encoding`.                           |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | axis_title_font | string | -default-            | The font to use for axis title text.  For       |
         *        |                 |        |                      | details, see :ref:`Font Encoding`.              |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | axis_label_font | string | -default-            | The font to use for axis labels.  For details,  |
         *        |                 |        |                      | see :ref:`Font Encoding`.                       |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | minimum_latency | float  | 0                    | The minimum latency value to show.  Value is in |
         *        |                 |        |                      | seconds.                                        |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | maximum_latency | float  | auto-scale           | The maximum latency value to show.  Value is in |
         *        |                 |        |                      | seconds.                                        |
         *        +-----------------+------ --+----------------------+------------------------------------------------+
         *        | log_scale       | bool   | false                | A boolean value indicating if log scale should  |
         *        |                 |        |                      | be used for the Y axis.  Only works for history |
         *        |                 |        |                      | plots.                                          |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | width           | int    | 1024                 | The plot width, in pixels.                      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | height          | int    | 768                  | The plot height, in pixels.                     |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | format          | string | 'PNG'                | The returned data format, supported values are  |
         *        |                 |        |                      | 'PNG', and 'JPG'.                               |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *
         * :return:
         *     Returns an array holding the values listed in
         *     :numref:`PHP Latency Plot Return Values`.  A null value will be
         *     returned if a communication error occurs.
         *
         *     .. tabularcolumns:: |p{0.8 in}|p{0.4 in}|p{3.5 in}|
         *     .. _PHP Latency Plot Return Values:
         *     .. table:: latencyPlot Return Values
         *        :align: center
         *        :widths: 18 10 80
         *
         *        +--------------+--------+-----------------------------------+
         *        | Key          | Type   | Contains                          |
         *        +==============+========+===================================+
         *        | content_type | string | The returned content type.  On    |
         *        |              |        | success, 'image/png' or           |
         *        |              |        | 'image/jpg' is returned.  On      |
         *        |              |        | failure, 'application/json' is    |
         *        |              |        | returned.                         |
         *        +--------------+--------+-----------------------------------+
         *        | body         | string | The returned payload.  On         |
         *        |              |        | success, this will contain raw    |
         *        |              |        | byte data holding the image.  On  |
         *        |              |        | failure, the body will contain a  |
         *        |              |        | JSON encoded response explaining  |
         *        |              |        | the failure.                      |
         *        +--------------+--------+-----------------------------------+
         *
         * \endrst
         */
        public function latencyPlot(array $settings) {
            return $this->postBinaryMessage(
                $settings,
                self::LATENCY_PLOT_ROUTE
            );
        }

        /**
         * Method you can use to enter or exit maintenance mode.
         *
         * \param $pause If true, then all monitoring of your infrastructure
         *               will stop and SpeedSentry will enter maintenance mode.
         *               If false, monitoring will resume and SpeedSentry will
         *               exit maintenance mode.
         *
         * \return Returns true on success.  Returns false on error.
         */
        public function customerPause(bool $pause) {
            $response = $this->postMessage(
                array('pause' => $pause),
                self::CUSTOMER_PAUSE_ROUTE
            );

            return (
                   $response !== null
                && array_key_exists('status', $response)
                && $response['status'] == 'OK'
            );
        }

        /**
         * Method you can use to determine what resource data is available.
         *
         * \return Returns an array of value types holding data.  On failure,
         *         null is returned.
         */
        public function resourceAvailable() {
            $response = $this->postMessage(
                array(),
                self::RESOURCE_AVAILABLE_ROUTE
            );

            if ($response !== null                         &&
                array_key_exists('status', $response)      &&
                $response['status'] == 'OK'                &&
                array_key_exists('value_types', $response)    ) {
                $result = $response['value_types'];
            } else {
                $result = null;
            }


            return $result;
        }

        /**
         * Method you can use to determine what resource data is available.
         *
         * \param[in] $value      The floating point value to be recorded.
         *
         * \param[in] $value_type The value type to record this value under.
         *
         * \param[in] $timestamp  The Unix timestamp indicating when the value
         *                        was recorded.  A value of 0 indicates the
         *                        current server timestamp.

         * \return Returns true on success.  Returns false on error.
         */
        public function resourceCreate(
                float $value,
                int   $value_type = 0,
                int   $timestamp = 0
            ) {
            $message = array('value' => $value);

            if ($value_type > 0) {
                $message['value_type'] = $value_type;
            }


            if ($timestamp != 0) {
                $message['timestamp'] = $timestamp;
            }

            $response = $this->postMessage(
                $message,
                self::RESOURCE_CREATE_ROUTE
            );

            return (
                   $response !== null
                && array_key_exists('status', $response)
                && $response['status'] == 'OK'
            );
        }

        /**
         * Method you can use to obtain resource data.
         *
         * \param[in] $value_type      The value type to obtain entries for.
         *
         * \param[in] $start_timestamp The starting Unix timestamp for the
         *                             resource data.  A value of 0 indicates
         *                             no starting timestamp.
         *
         * \param[in] $end_timestamp   The ending Unix timestamp for the
         *                             resource data.  A value of 0 indicates
         *                             no ending timestamp.
         *
         * \rst:leading-asterisk
         *
         * :return:
         *     Returns an array holding the values listed in
         *     :numref:`PHP Resource List Return Values`.  A null value will be
         *     returned if a communication error occurs.
         *
         *     .. tabularcolumns:: |p{0.8 in}|p{0.4 in}|p{3.5 in}|
         *     .. _PHP Resource List Return Values:
         *     .. table:: resourceList Return Values
         *        :align: center
         *        :widths: 18 10 80
         *
         *        +--------------+-------+---------------------------------------+
         *        | Key          | Type  | Contains                              |
         *        +==============+=======+=======================================+
         *        | value_type   | int   | The supplied value type.              |
         *        +--------------+-------+---------------------------------------+
         *        | resources    | array | An array holding the values stored    |
         *        |              |       | for this resource.  Each entry is a   |
         *        |              |       | dictionary holding the fields listed  |
         *        |              |       | listed in                             |
         *        |              |       | :numref:`PHP Resource List Resources` |
         *        +--------------+-------+---------------------------------------+
         *
         *     .. tabularcolumns:: |p{0.8 in}|p{0.4 in}|p{3.5 in}|
         *     .. _PHP Resource List Resources:
         *     .. table:: resourceList Resource Values
         *        :align: center
         *        :widths: 18 10 80
         *
         *        +-----------+-------+--------------------------------------+
         *        | Key       | Type  | Contains                             |
         *        +===========+=======+======================================+
         *        | value     | float | A previously recorded value.         |
         *        +-----------+-------+--------------------------------------+
         *        | timestamp | int   | The Unix timestamp indicating when   |
         *        |           |       | the value was recorded.              |
         *        +-----------+-------+--------------------------------------+
         *
         * \endrst
         */
        public function resourceList(
                int $value_type,
                int $start_timestamp = 0,
                int $end_timestamp = 0
            ) {
            $message = array('value_type' => $value);

            if ($start_timestmap > 0) {
                $message['start_timestamp'] = $start_timestmap;
            }

            if ($end_timestmap > 0) {
                $message['end_timestamp'] = $end_timestmap;
            }

            $response = $this->postMessage(
                $message,
                self::RESOURCE_LIST_ROUTE
            );

            if ($response !== null                    &&
                array_key_exists('status', $response) &&
                $response['status'] == 'OK'           &&
                array_key_exists('data', $response)      ) {
                $result = $response['data'];
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method you can use to obtain a plot of resource data.
         *
         * \rst:leading-asterisk
         *
         * :param $settings:
         *     The plot settings array.  This value supports a large number of
         *     possible fields listed in
         *     :numref:`PHP Resource Plot Parameters`.  Note that most
         *     parameters are optional.  Reasonable default values will be used
         *     for omitted parameters.
         *
         *     .. tabularcolumns:: |p{1.1 in}|p{0.4 in}|p{1.4 in}|p{1.6 in}|
         *     .. _PHP Resource Plot Parameters:
         *     .. table:: resourcePlot Parameters
         *        :align: center
         *        :widths: 20 10 45 80
         *
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | Key             | Type   | Default Value        | Function                                        |
         *        +=================+========+======================+=================================================+
         *        | value_type      | int    | 0                    | Indicates the resource value type to be         |
         *        |                 |        |                      | plotted.                                        |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | start_timestamp | int    | 0                    | Indicates the plot should exclude values before |
         *        |                 |        |                      | this time.                                      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | end_timestamp   | int    | -now-                | Indicates the plot should exclude values after  |
         *        |                 |        |                      | this time.                                      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | scale_factor    | float  | 1                    | A scale factor to apply to the Y axis.          |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | title           | string | 'Resource Over Time' | A UTF-8 encoded title to include in the plot.   |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | x_axis_label    | string | 'Date/Time'          | The UTF-8 encoded text for the X axis label.    |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | y_axis_label    | string | 'Value'              | The UTF-8 encoded text for the Y axis label.    |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | date_format     | string | 'MMM dd yyy - hh:mm' | The date format to apply to date/time fields.   |
         *        |                 |        |                      | For details, see :ref:`Date Format Codes`.      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | title_font      | string | -dafault-            | The title font to be used.   For details, see   |
         *        |                 |        |                      | :ref:`Font Encoding`.                           |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | axis_title_font | string | -default-            | The font to use for axis title text.  For       |
         *        |                 |        |                      | details, see :ref:`Font Encoding`.              |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | axis_label_font | string | -default-            | The font to use for axis labels.  For details,  |
         *        |                 |        |                      | see :ref:`Font Encoding`.                       |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | width           | int    | 1024                 | The plot width, in pixels.                      |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | height          | int    | 768                  | The plot height, in pixels.                     |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *        | format          | string | 'PNG'                | The returned data format, supported values are  |
         *        |                 |        |                      | 'PNG', and 'JPG'.                               |
         *        +-----------------+--------+----------------------+-------------------------------------------------+
         *
         * :return:
         *     Returns an array holding the values listed in
         *     :numref:`PHP Resource Plot Return Values`.  A null value will be
         *     returned if a communication error occurs.
         *
         *     .. tabularcolumns:: |p{0.8 in}|p{0.4 in}|p{3.5 in}|
         *     .. _PHP Latency Plot Return Values:
         *     .. table:: latencyPlot Return Values
         *        :align: center
         *        :widths: 18 10 80
         *
         *        +--------------+--------+-----------------------------------+
         *        | Key          | Type   | Contains                          |
         *        +==============+========+===================================+
         *        | content_type | string | The returned content type.  On    |
         *        |              |        | success, 'image/png' or           |
         *        |              |        | 'image/jpg' is returned.  On      |
         *        |              |        | failure 'application/json' is     |
         *        |              |        | returned.                         |
         *        +--------------+--------+-----------------------------------+
         *        | body         | string | The returned payload.  On         |
         *        |              |        | success, this will contain raw    |
         *        |              |        | byte data holding the image.  On  |
         *        |              |        | failure, the body will contain a  |
         *        |              |        | JSON encoded response explaining  |
         *        |              |        | the failure.                      |
         *        +--------------+--------+-----------------------------------+
         *
         * \endrst
         */
        public function resourcePlot(array $settings) {
            return $this->postBinaryMessage(
                $settings,
                self::RESOURCE_PLOT_ROUTE
            );
        }

        /**
         * Method that posts a message and waits for a response.
         *
         * \param $message The message to be sent.
         *
         * \param $route   The route to send the message to.
         *
         * \return Returns an array holding the response body.  A value of null
         *         is returned on error.
         */
        private function postMessage(array $message, string $route) {
            $response = $this->__postMessage($message, $route);
            if ($response['status'] == 401) {
                if ($this->__updateTimeDelta()) {
                    $response = $this->__postMessage($message, $route);
                }
            }

            if ($response['status'] == 200) {
                $result = json_decode($response['reply'], true);
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method that posts a message and waits for a response in a binary
         * format.
         *
         * \param $message The message to be sent.
         *
         * \param $route   The route to send the message to.
         *
         * \return Returns an array holding the response body.  A value of null
         *         is returned on error.
         */
        private function postBinaryMessage(array $message, string $route) {
            $response = $this->__postMessage($message, $route);
            if ($response['status'] == 401) {
                if ($this->__updateTimeDelta()) {
                    $response = $this->__postMessage($message, $route);
               }
            }

            if ($response['status'] == 200) {
                $result = array(
                    'body' => $response['reply'],
                    'content_type' => $response['content_type']
                );
            } else {
                $result = null;
            }

            return $result;
        }

        /**
         * Method that posts a message and waits for a response.  This function
         * does no retries and does not adjust the time delta on an
         * UNAUTHORIZED (401) error.
         *
         * \param $message The message to be sent.
         *
         * \param $route   The route to send the message to.
         *
         * \return Returns an array holding the status code followed by the
         *         response body.  A status code of 0 is returned if the
         *         message could not be sent.
         */
        private function __postMessage(array $message, string $route) {
            $json_encoded = json_encode($message);

            $hash_time_value = (int) ((time() + $this->time_delta) / 30);

            // Because PHP's pack function is horked for 64-bit integers.
            // This assumes a 64-bit system, at least after January 19, 2038.
            $key = $this->rest_api_secret . pack(
                'VV',
                 $hash_time_value        & 0xFFFFFFFF,
                ($hash_time_value >> 32) & 0xFFFFFFFF
            );

            $hash = hash_hmac(self::HASH_ALGORITHM, $json_encoded, $key, true);

            $encoded_data = base64_encode($json_encoded);
            $encoded_hash = base64_encode($hash);

            $payload = json_encode(
                array(
                    'cid' => $this->customer_identifier,
                    'data' => $encoded_data,
                    'hash' => $encoded_hash
                )
            );

            $headers = array(
                'content-type' => 'application/json',
                'user-agent' => 'PHP API ' . $this->customer_identifier,
                'content-length' => strlen($payload)
            );

            $response = wp_remote_post(
                self::INESONIC_AUTHORITY . $route,
                array(
                    'body' => $payload,
                    'timeout' => $this->timeout,
                    'redirection' => 5,
                    'httpversion' => 1,
                    'blocking' => true,
                    'headers' => $headers
                )
            );

            if (!is_wp_error($response)) {
                $status_code = $response['response']['code'];
                if (array_key_exists('body', $response)) {
                    $response_data = $response['body'];
                } else {
                    $response_data = null;
                }

                $content_type = $response['headers']['content-type'];
                $result = array(
                    'status' => $status_code,
                    'reply' => $response_data,
                    'content_type' => $content_type
                );
            } else {
                $result = array(
                    'status' => 0,
                    'reply' => null,
                    'content_type' => null
                );
            }

            return $result;
        }

        /**
         * Method that determines the current time delta between the client
         * and Inesonic infrastructure.
         *
         * \return Returns true on success.  Returns false on error.
         */
        private function __updateTimeDelta() {
            $success = false;

            $payload = array('timestamp' => time());
            $json_payload = json_encode($payload);

            $headers = array(
                'content-type' => 'application/json',
                'user-agent' => 'PHP API ' . $this->customer_identifier,
                'content-length' => strlen($json_payload)
            );

            $response_data = null;
            $url = self::INESONIC_AUTHORITY . self::TIME_DELTA_ROUTE;

            $response = wp_remote_post(
                $url,
                array(
                    'body' => $json_payload,
                    'timeout' => $this->timeout,
                    'redirection' => 5,
                    'httpversion' => 1,
                    'blocking' => true,
                    'headers' => $headers
                )
            );

            if (!is_wp_error($response)) {
                if ($response['response']['code'] == 200) {
                    $response_data = json_decode($response['body'], true);
                }
            }

            if ($response_data !== null                        &&
                array_key_exists('status', $response_data)     &&
                $response_data['status'] == 'OK'               &&
                array_key_exists('time_delta', $response_data)    ) {
                $time_delta = $response_data['time_delta'];
                if (is_int($time_delta)) {
                    $success = true;
                    $this->time_delta = intval($time_delta);

                    if ($this->time_delta_callback !== null) {
                        call_user_func(
                            $this->time_delta_callback,
                            self::TIME_DELTA_OPTION,
                            $this->time_delta
                        );
                    }
                }
            }

            return $success;
        }
    };
