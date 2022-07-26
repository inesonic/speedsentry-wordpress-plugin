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
 * \file broadcast-capabilities.js
 *
 * JavaScript module that checks customer capabilities and broadcasts those capabilities on change.
 */

/***********************************************************************************************************************
 * Parameters:
 */

/**
 * The capabilities polling interval, in mSec.
 */
const POLLING_INTERVAL = 20 * 1000;

/**
 * Timeout for our AJAX requests.
 */
const AJAX_TIMEOUT = 30 * 1000;

/***********************************************************************************************************************
 * Globals:
 */

/**
 * A global holding the last read capabilities instance.  A value of null indicates either that we haven't read any
 * capabilities yet or we have a communication error.
 */
var inesonicSpeedSentryLastCapabilities = null;

/***********************************************************************************************************************
 * Script Scope Globals:
 */

/**
 * Timer used to check capabilities.  We use a timeout rather than an interval so that we can adjust our timing on
 * first poll.
 */
var capabilitiesCheckTimer = null;

/**
 * A flag indicating that this is the first time we've read a value.  We always want to broadcast on the first pass.
 */
var firstBroadcast = true;

/***********************************************************************************************************************
 * Functions:
 */

/**
 * Function that broadcasts changes to the customer capabilities.
 *
 * \param capabilities The capabilities dictionary to be broadcast.
 */
function inesonicSpeedSentryBroadcastCapabilityChange(capabilities) {
    // For this to work we must have an element somewhere with the id 'inesonic-speedsentry-capabilities' that everyone
    // can listen to for events.  For now, this item is placed in the admin bar.
    jQuery("#inesonic-speedsentry-capabilities").trigger("inesonic-speedsentry-capabilities-changed", [ capabilities ]);
}

/**
 * Function that restarts our broadcast timer.
 */
function inesonicSpeedSentryRestartTimer() {
    capabilitiesCheckTimer = setTimeout(inesonicSpeedSentryCheckCapabilities, POLLING_INTERVAL);
}

/**
 * Function that compares two capabilities instances.  Exists because JavaScript's ability to compare objects is
 * brain-damaged and using a package like lodash seems like overkill.
 *
 * \param a The first value to compare.
 *
 * \param b The second value to compare.
 *
 * \return Returns true if the dictionaries are equivalent.  Returns false if the dictionaries are not equivalent.
 */
function inesonicSpeedSentryCompareCapabilities(a, b) {
    if (a === b) {
        return true;
    } else if (a === null || b === null) {
        return false;
    } else {
        return (
               (a.customer_active == b.customer_active)
            && (a.connected == b.connected)
            && (a.maximum_number_monitors == b.maximum_number_monitors)
            && (a.multi_region_checking == b.multi_region_checking)
            && (a.paused == b.paused)
            && (a.polling_interval == b.polling_interval)
            && (a.supports_content_checking == b.supports_content_checking)
            && (a.supports_keyword_checking == b.supports_keyword_checking)
            && (a.supports_latency_tracking == b.supports_latency_tracking)
            && (a.supports_maintenance_mode == b.supports_maintenance_mode)
            && (a.supports_ping_based_polling == b.supports_ping_based_polling)
            && (a.supports_post_method == b.supports_post_method)
            && (a.supports_rest_api == b.supports_rest_api)
            && (a.supports_ssl_expiration_checking == b.supports_ssl_expiration_checking)
            && (a.supports_wordpress == b.supports_wordpress)
        );
    }
}

/**
 * Function that is triggered periodically to check our capabilities.
 */
function inesonicSpeedSentryCheckCapabilities() {
    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            timeout: AJAX_TIMEOUT,
            data: { "action" : "inesonic_speedsentry_get_capabilities" },
            dataType: "json",
            success: function(response) {
                inesonicSpeedSentryRestartTimer();
                
                if (response != null && response.status == 'OK') {
                    let capabilities = response.capabilities;
                    if (firstBroadcast                                                                             ||
                        !inesonicSpeedSentryCompareCapabilities(capabilities, inesonicSpeedSentryLastCapabilities)    ) {
                        firstBroadcast = false;
                        inesonicSpeedSentryLastCapabilities = capabilities;
                        inesonicSpeedSentryBroadcastCapabilityChange(capabilities);
                    }
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                inesonicSpeedSentryRestartTimer();
                console.log("Could not determine status: " + errorThrown);
            }
        }
    );
}

/***********************************************************************************************************************
 * Main:
 */

jQuery(document).ready(function($) {
    capabilitiesCheckTimer = setTimeout(inesonicSpeedSentryCheckCapabilities, 100);
});
