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
 * \file status-page.js
 *
 * JavaScript module that manages manual configuration via the WordPress Plug-Ins page.
 */

/***********************************************************************************************************************
 * Functions:
 */

/**
 * Function that checks if an access code is valid.
 *
 * \param accessCode The access code to be checked.
 *
 * \return Returns true if the access code is valid, returns false if the access code is invalid.  Note that this
 *         method does not confirm that the access code will authenticate, only that the format is good.
 */
function inesonicSpeedSentryIsValidAccessCode(accessCode) {
    let isValid = false;

    let commaIndex = accessCode.indexOf(",");
    if (commaIndex > 0) {
        let cid    = accessCode.substring(0, commaIndex);
        let secret = accessCode.substring(commaIndex + 1);

        isValid = (cid.match(/^[a-zA-Z0-9]{16}$/) && secret.match(/^[a-zA-Z0-9+/]{75}=$/));
    }

    return isValid;
}

/**
 * Function that displays the manual configuration fields.
 */
function inesonicSpeedSentryToggleManualConfiguration() {
    let areaRow = jQuery("#inesonic-speedsentry-configuration-area-row");
    if (areaRow.hasClass("inesonic-row-hidden")) {
        areaRow.prop("class", "inesonic-speedsentry-configuration-area-row inesonic-row-visible");
    } else {
        areaRow.prop("class", "inesonic-speedsentry-configuration-area-row inesonic-row-hidden");
    }
}

/**
 * Function that is triggered whenever an access code changes to validate its content.
 */
function inesonicSpeedSentryValidateAccessCode() {
    let inputField = jQuery("#inesonic-speedsentry-mc-input");
    let submitButton = jQuery("#inesonic-speedsentry-mc-submit-button");

    let accessCode = inputField.val();

    if (inesonicSpeedSentryIsValidAccessCode(accessCode)) {
        inputField.prop("class", "inesonic-speedsentry-mc-input inesonic-speedsentry-input-valid");
        submitButton.removeClass("inesonic-speedsentry-anchor-disable");
        submitButton.addClass("inesonic-speedsentry-anchor-enable");
    } else {
        inputField.prop("class", "inesonic-speedsentry-mc-input inesonic-speedsentry-input-invalid");
        submitButton.addClass("inesonic-speedsentry-anchor-disable");
        submitButton.removeClass("inesonic-speedsentry-anchor-enable");
    }
}

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
 * Funciton that is triggered when the submit button is clicked.
 */
function inesonicSpeedSentryAccessCodeSubmit() {
    let inputField = jQuery("#inesonic-speedsentry-mc-input");
    let submitButton = jQuery("#inesonic-speedsentry-mc-submit-button");

    let accessCode = inputField.val();
    if (inesonicSpeedSentryIsValidAccessCode(accessCode)) {
        let commaIndex = accessCode.indexOf(",");
        if (commaIndex > 0) {
            let cid    = accessCode.substring(0, commaIndex);
            let secret = accessCode.substring(commaIndex + 1);

            inesonicSpeedSentryLastCapabilities = null;
            jQuery.ajax(
                {
                    type: "POST",
                    url: ajax_object.ajax_url,
                    data: {
                        "action" : "inesonic_speedsentry_access_codes",
                        "cid" : cid,
                        "secret" : secret
                    },
                    dataType: "json",
                    success: function(response) {
                        if (response !== null && response.status == 'OK') {
                            jQuery.ajax(
                                {
                                    type: "POST",
                                    url: ajax_object.ajax_url,
                                    data: { "action" : "inesonic_speedsentry_get_capabilities" },
                                    dataType: "json",
                                    success: function(response) {
                                        if (response != null && response.status == 'OK') {
                                            let capabilities = response.capabilities;
                                            inesonicSpeedSentryLastCapabilities = capabilities;
                                            inesonicSpeedSentryBroadcastCapabilityChange(capabilities);
                                            if (capabilities !== null) {
						window.location.reload();
                                            }
                                        }
                                    },
                                    error: function(jqXHR, textStatus, errorThrown) {
                                        inesonicSpeedSentryBroadcastCapabilityChange(null);
                                        console.log("Could not determine status: " + errorThrown);
                                    }
                                }
                            );
                        } else {
                            alert("Status failed");
                        }
                    },
                    error: function(jqXHR, textStatus, errorThrown) {
                        inesonicSpeedSentryBroadcastCapabilityChange(null);
                        console.log("Could not update access codes: " + errorThrown);
                    }
                }
            );
        }
    }
}

/***********************************************************************************************************************
 * Main:
 */

jQuery(document).ready(function($) {
    $("#inesonic-speedsentry-mc-link").click(function(event) {
        inesonicSpeedSentryToggleManualConfiguration();
    });

    $("#inesonic-speedsentry-mc-input").on("change keyup paste", function() {
        inesonicSpeedSentryValidateAccessCode();
    });

    let submitButton = $("#inesonic-speedsentry-mc-submit-button");
    submitButton.addClass("inesonic-speedsentry-anchor-disable");
    submitButton.click(function(event) {
        inesonicSpeedSentryAccessCodeSubmit();
    });

    inesonicSpeedSentryValidateAccessCode();
});
