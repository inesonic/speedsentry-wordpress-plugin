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
 * \file maintenance-mode-control.js
 *
 * JavaScript module that manages the site maintenance checkbox.
 */

/***********************************************************************************************************************
 * Parameters:
 */

/**
 * Timeout for our AJAX requests.
 */
//const AJAX_TIMEOUT = 30 * 1000;

/***********************************************************************************************************************
 * Functions:
 */

/**
 * Function that handles a user driven change to the site monitoring checkbox.
 *
 * \param element The element that triggered this change.
 */
function inesonicSpeedSentrySwitchMode(element) {
    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            timeout: AJAX_TIMEOUT,
            data: {
                "action" : "inesonic_speedsentry_maintenance_mode_change_status",
                "pause" : !element.checked
            },
            dataType: "json",
            success: function(response) {
                if (response === null || response.status != 'OK') {
                    console.log("Failed to change maintenance mode status.");
                }
            },
            error: function(jqXHR, textStatus, errorThrown) {
                console.log("AJAX failed, maintenance mode change: " + errorThrown);
            }
        }
    );
}

/***********************************************************************************************************************
 * Main:
 */

jQuery(document).ready(function($) {
    jQuery("#inesonic-speedsentry-capabilities").on(
            "inesonic-speedsentry-capabilities-changed",
            function(event, capabilities) {
        let monitorControl = jQuery("#wp-admin-bar-inesonic-speedsentry-maintenance-mode-control");        
        if (capabilities === null || !capabilities.supports_maintenance_mode) {
            monitorControl.attr("class", "inesonic-speedsentry-maintenance-mode-hidden");
        } else {
            monitorControl.attr("class", "inesonic-speedsentry-maintenance-mode-visible");
            jQuery("#inesonic-speedsentry-maintenance-mode-switch").prop("checked", !capabilities.paused);
        }
    });
});
