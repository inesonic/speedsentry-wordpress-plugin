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
 * \file signup-handler.js
 *
 * JavaScript module that manages the customer sign-up process.
 */

/***********************************************************************************************************************
 * Parameters:
 */

/**
 * Timeout for our AJAX requests.
 */
//const AJAX_TIMEOUT = 30 * 1000;

/***********************************************************************************************************************
 * Globals:
 */

/**
 * The URL we redirect to.  We delay redirect to work around issues with the Chrome browser.
 */
let redirectUrl = null;

/***********************************************************************************************************************
 * Functions:
 */

function inesonicSpeedSentrySignupRedirect() {
    console.log(redirectUrl);
    window.location.href = redirectUrl
}

/***********************************************************************************************************************
 * Main:
 */

jQuery(document).ready(function($) {
    jQuery("#inesonic_speedsentry_signup_redirect").click(function(event) {
        jQuery.ajax(
            {
                type: "POST",
                url: ajax_object.ajax_url,
                timeout: 21000,
                data: { "action" : "inesonic_speedsentry_signup_generate_nonce" },
                dataType: "json",
                async: false,
                success: function(response) {
                    if (response !== null) {
                        if (response.status == "OK") {
                            let signupUrl = response.signup_url;
                            let nonce = response.nonce;
                            let siteUrl = response.site_url;
        
                            let query = new URLSearchParams(
                                {
                                    'sn' : nonce,
                                    'url' : siteUrl,
                                    'redirect' : window.location.href
                                }
                            );
                            
                            redirectUrl = signupUrl + "?" + query.toString();
                            setTimeout(inesonicSpeedSentrySignupRedirect, 500);
                        } else {
                            alert("Error: " + response.status);
                        }
                    } else {
                        alert("Error: Empty response");
                    }                       
                },
                error: function(jqXHR, textStatus, errorThrown) {
                    alert("Error: " + errorThrown);
                }
            }
        );
    });
});

