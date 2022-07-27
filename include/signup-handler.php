<?php
/***********************************************************************************************************************
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
     * Class that provides functions and support to simplify the customer sign-up process.   The signup process
     * operates as shown below:
     *
     * Customer               Browser             PlugIn              Inesonic
     *
     * Customer clicks browser link.
     *                       \
     *                        Browser triggers AJAX request
     *                        via POST.         \
     *                        |                  \
     *                        |                   Plug-in calculates a nonce and
     *                        |                   sends it + API version + website
     *                        |                   URL to Inesonic infrastructure
     *                        |                   via POST.         \
     *                        |                   |                  \
     *                        |                   |                   Inesonic infrastructure generates
     *                        |                   |                   a temporary secret and stores off
     *                        |                   |                   all information.  Responds with
     *                        |                   |                   the temporary secret and the URL
     *                        |                   |                   to direct the customer to.
     *                        |                   |                  /
     *                        |                   Plug-in responds to browser with
     *                        |                   the nonce and redirect URL.
     *                        |                  /
     *                        AJAX function opens page in
     *                        another tab including the
     *                        nonce and website URL in the
     *                        query string.
     *                       /
     * Customer uses the page to sign-up
     * or log into Inesonic infrastructure
     *                                    \
     *                                     +------------------------+
     *                                                               \
     *                                                                Inesonic infrastructure configures
     *                                                                the customer account, if needed, and
     *                                                                triggers a REST API call to the
     *                                                                plug-in using the temporary key.  The
     *                                                                REST API call contains the REST API
     *                                                                key and customer identifier.
     *                                                               /
     *                                            The plug-in receives the REST API
     *                                            request authenticated using the
     *                                            temporary key and sets up the
     *                                            permanent REST API key.  This also
     *                                            activates the plug-in.  We finish
     *                                            by returning a success status and
     *                                            deleting our temporary key to
     *                                            block future calls into the REST
     *                                            API.
     */
    class SignupHandler extends Helpers {
        /**
         * The required length of the NONCE, in bytes.  The nonce needs to be usable in a query string.
         */
        const NONCE_LENGTH = 32;

        /**
         * The required length of the temporary secret, in bytes.
         */
        const TEMPORARY_SECRET_LENGTH = 64;

        /**
         * The required length of the final REST API secret, in bytes.
         */
        const REST_API_SECRET_LENGTH = 56;

        /**
         * The hash algorithm.
         */
        const HASH_ALGORITHM = 'sha256';

        /**
         * The signup ID expected by the JavaScript.
         */
        const SIGNUP_ID = "inesonic_speedsentry_signup_redirect";

        /**
         * Signup response endpoint.
         */
        const SIGNUP_ENDPOINT = 'signup';

        /**
         * The default REST API timeout.
         */
        const DEFAULT_TIMEOUT = 20;

        /**
         * Constructor
         *
         * \param $rest_namespace        The namespace we should use for our REST API routes.
         *
         * \param $signup_url            The URL to redirect to for sign-up.
         *
         * \param $registration_webhook  Webhook used to register the secret before we redirect.
         *
         * \param $rest_api_version      The REST API version number.
         *
         * \param $completed_anchor_text Text to show in the signup anchor upon completion of the sign-up process.
         *
         * \param $completed_archor_url  The URL to redirect the signup anchor to upon successful completion.
         *
         * \param $options               The options instance used to store persistent data.
         *
         * \param $rest_api              The outbound REST API to be updated.
         */
        public function __construct(
                string    $rest_namespace,
                string    $signup_url,
                string    $login_url,
                string    $registration_webhook,
                int       $rest_api_version,
                string    $completed_anchor_text,
                string    $completed_anchor_url,
                Options   $options,
                RestApiV1 $rest_api
            ) {
            $this->rest_namespace = $rest_namespace;
            $this->signup_url = $signup_url;
            $this->login_url = $login_url;
            $this->registration_webhook = $registration_webhook;
            $this->rest_api_version = $rest_api_version;
            $this->completed_anchor_text = $completed_anchor_text;
            $this->completed_anchor_url = $completed_anchor_url;
            $this->options = $options;
            $this->rest_api = $rest_api;

            add_action('init', array($this, 'on_initialization'));
            add_action('rest_api_init', array($this, 'rest_api_initialization'));
        }

        /**
         * Method you should call on initialization to setup our AJAX API.
         */
        public function on_initialization() {
            add_action('wp_ajax_inesonic_speedsentry_signup_generate_nonce', array($this, 'signup_generate_nonce'));
            add_action('wp_ajax_inesonic_speedsentry_signup_check_status', array($this, 'signup_check_status'));
        }

        /**
         * Method that initializes our REST API.
         */
        public function rest_api_initialization() {
            register_rest_route(
                $this->rest_namespace,
                self::SIGNUP_ENDPOINT,
                array(
                    'methods' => 'POST',
                    'callback' => array($this, 'process_signup_response'),
                    'permission_callback' => '__return_true'
                )
            );
        }

        /**
         * Method that provides the last nonce sent to the SpeedSentry servers.
         *
         * \return Returns the last nonce sent to the SpeedSentry servers.  A value of null is returned if no nonce has
         *         been provided.
         */
        public function nonce() {
            return $this->options->one_time_nonce();
        }

        /**
         * Method you can use to obtain the name of the ID used to redirect to the signup link.
         *
         * \return Returns the signup ID used to trigger the JavaScript.
         */
        static public function signup_id() {
            return self::SIGNUP_ID;
        }

        /**
         * Method you can use to determine if the user has signed up.
         *
         * \return Returns true if the customer has signed up and tied this site to Inesonic's infrastructure.  Returns
         *         false if the customer has not completed the sign-up process.
         */
        public function signup_completed() {
            return $this->options->rest_api_secret_v1() !== null;
        }

        /**
         * Method you can use to obtain the customer ID.
         *
         * \return Returns the customer ID for the customer.
         */
        public function customer_identifier() {
            return $this->options->customer_identifier();
        }

        /**
         * Method you can use to obtain the customer ID.
         *
         * \return Returns the customer ID for the customer.
         */
        public function customer_secret_v1() {
            return $this->options->rest_api_secret_v1();
        }

        /**
         * Method you can use to set the customer credentials.
         *
         * \param $identifier The customer identifier.
         *
         * \param $secret     The customer secret.  The secret should be base-64 encoded.
         */
        public function set_credentials(string $identifier, string $secret) {
            $this->options->set_customer_identifier($identifier);
            $this->options->set_rest_api_secret_v1($secret);
            
            // Disable the automatic connection API.
            $this->options->set_temporary_secret('');
        }

        /**
         * Method you can use to obtain an anchor tag used to trigger the signup.  You can optionally include a set of
         * CSS classes.
         *
         * \param $content The text content to be included in the link.
         *
         * \param $classes The classes to be included.  An empty string will cause the classes to be excluded.
         *
         * \return Returns a signup link.
         */
        public function signup_anchor_tag(string $content, string $classes = "") {
            if ($classes == "") {
                $result = "<a id=\"" . esc_attr(self::signup_id()) . "\" href=\"#inesonic-speedsentry-invalid\">" .
                              $content .
                          "</a>";
            } else {
                $result = "<a id=\"" . esc_attr(self::signup_id()) . "\" " .
                             "class=\"" . $classes . "\" " .
                             "href=\"#inesonic-speedsentry-invalid\">" .
                              $content .
                          "</a>";
            }

            return $result;
        }

        /**
         * Method you can use to enqueue the required JavaScript to process the signup link.
         */
        public function enqueue_javascript() {
            wp_enqueue_script('jquery');
            wp_enqueue_script(
                'inesonic-speedsentry-signup-handler',
                self::javascript_url('signup-handler', false),
                array('jquery'),
                null,
                true
            );
            wp_localize_script(
                'inesonic-speedsentry-signup-handler',
                'ajax_object',
                array(
                    'ajax_url' => admin_url('admin-ajax.php')
                )
            );
        }

        /**
         * Method that generates a new nonce and provides it to the JavaScript.
         */
        public function signup_generate_nonce() {
            if (current_user_can('activate_plugins')) {
                $nonce = self::generate_nonce();
                $site_url = get_site_url();

                $message = array(
                    'nonce' => $nonce,
                    'rest_api_version' => $this->rest_api_version,
                    'site_url' => $site_url
                );

                $response = wp_remote_post(
                    $this->registration_webhook,
                    array(
                        'body' => json_encode($message),
                        'timeout' => self::DEFAULT_TIMEOUT,
                        'redirection' => 5,
                        'httpversion' => 1,
                        'blocking' => true,
                        'headers' => array(
                            'content-type' => 'application/json',
                            'user-agent' => 'inesonic.com WordPress'
                        )
                    )
                );

                if (!is_wp_error($response)) {
                    if ($response['response']['code'] == 200) {
                        $response_data = json_decode($response['body'], true);

                        if (array_key_exists('status', $response_data)) {
                            if ($response_data['status'] == 'OK') {
                                if (array_key_exists('temporary_secret', $response_data) &&
                                    array_key_exists('signup_url', $response_data)          ) {
                                    $temporary_secret_str = $response_data['temporary_secret'];
                                    $signup_url = $response_data['signup_url'];

                                    $temporary_secret = base64_decode($temporary_secret_str);
                                    if (strlen($temporary_secret) == self::TEMPORARY_SECRET_LENGTH) {
                                        $this->options->set_temporary_secret($temporary_secret);
                                        $result = array(
                                            'status' => 'OK',
                                            'signup_url' => $signup_url,
                                            'site_url' => $site_url,
                                            'nonce' => $nonce
                                        );
                                    } else {
                                        $result = array('status' => 'failed, invalid temporary secret length');
                                    }
                                }
                            } else {
                                $result = array('status' => 'failed, received: ' . $response['status']);
                            }
                        } else {
                            $result = array('status' => 'failed, no status');
                        }
                    } else {
                        $result = array('status' => 'failed, received status code ' . $response['response']['code']);
                    }
                } else {
                    $result = array('status' => 'failed, could not negotiate');
                }
            } else {
                $result = array('status' => 'failed, insufficient permissions');
            }

            echo json_encode($result);
            wp_die();
        }

        /**
         * Method that checks if the customer has completed the sign-up process.
         */
        public function signup_check_status() {
            if (current_user_can('activate_plugins')) {
                $rest_api_key = $this->options->rest_api_secret_v1();
                if (strlen($rest_api_key) == self::REST_API_SECRET_LENGTH) {
                    $result = array(
                        'status' => 'OK',
                        'completed' => true,
                        'anchor_text' => $this->completed_anchor_text,
                        'anchor_url' => $this->completed_anchor_url
                    );

                    do_action('inesonic-speedsentry-signup-complete');
                } else {
                    $result = array('status' => 'OK', 'completed' => false);
                }
            } else {
                $result = array('status' => 'failed');
            }

            echo json_encode($result);
            wp_die();
        }

        /**
         * Method that is triggered when the Inesonic server sends the final REST API secret.
         *
         * \param $request Request data from this REST API handler.
         */
        public function process_signup_response(\WP_REST_Request $request) {
            $temporary_secret = $this->options->temporary_secret();
            if ($temporary_secret !== null && strlen($temporary_secret) == self::TEMPORARY_SECRET_LENGTH) {
                $params = $request->get_json_params();
                if (count($params) == 2 && array_key_exists('hash', $params) && array_key_exists('data', $params)) {
                    $raw_data = base64_decode($params['data'], true);
                    $raw_hash = base64_decode($params['hash'], true);

                    $expected_hash = hash_hmac(self::HASH_ALGORITHM, $raw_data, $temporary_secret, true);

                    if ($expected_hash == $raw_hash) {
                        $json_data = (array) json_decode($raw_data);
                        if (count($json_data) == 2                     &&
                            array_key_exists('identifier', $json_data) &&
                            array_key_exists('secret', $json_data)        ) {
                            $customer_identifier = $json_data['identifier'];
                            $rest_api_secret_str = $json_data['secret'];

                            $rest_api_secret = base64_decode($rest_api_secret_str);
                            if (strlen($rest_api_secret) == self::REST_API_SECRET_LENGTH) {
                                $this->rest_api->setCustomerIdentifier($customer_identifier);
                                $this->rest_api->setRestApiSecret($rest_api_secret, false);

                                $this->options->set_rest_api_secret_v1($rest_api_secret);
                                $this->options->set_customer_identifier($customer_identifier);

                                // Disable this API henceforth.
                                $this->options->set_temporary_secret('');

                                $response = array('status' => 'OK');
                            } else {
                                $response = new \WP_Error(
                                    'bad request',
                                    'Bad Request',
                                    array('status' => 400)
                                );
                            }
                        } else {
                            $response = new \WP_Error(
                                'bad request',
                                'Bad Request',
                                array('status' => 400)
                            );
                        }
                    } else {
                        $response = new \WP_Error(
                            'unauthorized',
                            'Unauthorized',
                            array('status' => 401)
                        );
                    }
                } else {
                    $response = new \WP_Error(
                        'missing content',
                        'Missing Content',
                        array('status' => 406)
                    );
                }
            } else {
                $response = new \WP_Error(
                    'bad request',
                    'Bad Request',
                    array('status' => 400)
                );
            }

            return $response;
        }

        /**
         * Method that generates a simple nonce.
         *
         * \return Returns a nonce containing only alphanumeric characters.
         */
        static public function generate_nonce() {
            $nonce = "";
            for ($i=0 ; $i<self::NONCE_LENGTH ; ++$i) {
                $v = random_int(0, 2 * 26 + 10);
                if ($v < 10) {
                    $nonce .= chr(0x30 + $v); // ASCII digits 0-9
                } else if ($v < 36) {
                    $nonce .= chr(0x41 + $v - 10); // ASCII characters A-Z
                } else {
                    $nonce .= chr(0x61 + $v - 36); // ASCII characters a-z
                }
            }

            return $nonce;
        }
    };
