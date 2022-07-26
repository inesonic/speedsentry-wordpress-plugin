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
 * JavaScript module that manages the site status page.
 */

/***********************************************************************************************************************
 * Parameters:
 */

/**
 * The period to wait between table/plot refreshes.
 */
const REFRESH_PERIOD = 5 * 60 * 1000;

/**
 * Window resize idle time before adjusting for window size changes.
 */
const RESIZE_UPDATE_DELAY = 500;

/**
 * Timeout for our AJAX requests.
 */
//const AJAX_TIMEOUT = 30 * 1000;

/***********************************************************************************************************************
 * Script Scope Globals:
 */

/**
 * Timer used to update the status page content at period intervals.
 */
let updateTimer = null;

/**
 * Timer used to update plots after a small delay.
 */
let resizeUpdateTimer = null;

/**
 * The last start timestamp entered by the user.  A value of null indicates no start timestamp.
 */
let startTimestamp = null;

/**
 * The last end timestamp entered by the user.  A value of null indicates no end timestamp.
 */
let endTimestamp = null;

/**
 * The last latency value selected by the user.  A value of 0 indicates auto-scaling.
 */
let latencyScale = 0;

/**
 * The last region ID entered by the user.  A value of null indicates all regions.
 */
let regionId = null;

/**
 * The last monitor ID entered by the user.  A value of null indicates all monitors.  A negative value indicates a
 * host/scheme ID and thus all monitors on that authority.  A positive value indicates a specific monitor.
 */
let monitorId = null;

/**
 * The last read list of regions by region ID.
 */
let customerRegionsById = null;

/**
 * The last read list of customer monitors, indexed by monitor ID.
 */
let customerMonitorsById = null;

/**
 * The last read list of customer monitors, indexed by user ordering.
 */
let customerMonitorsByUserOrder = null;

/**
 * Array of active user order values.
 */
let customerActiveUserOrderValues = null;

/**
 * The last read list of authorities, indexed by host/scheme ID.
 */
let customerAuthorities = null;

/**
 * The last read monitor status data.
 */
let customerStatus = null;

/**
 * The last read list of events.
 */
let customerEvents = null;

/**
 * The newest events found for each monitor.
 */
let newestEventByMonitorId = null;

/**
 * A dictionary of user strings from event types.  Values generated and localized by PHP.
 */
let userStringsFromEventTypes = ajax_object.user_strings_from_event_types;

/**
 * A dictionary of user strings from status values.  Values generated and localized by PHP.
 */
let userStringsFromStatus = ajax_object.user_strings_from_status;

/**
 * A dictionary of miscellaneous user strings.
 */
let userStrings = ajax_object.user_strings;

/**
 * The default image to use if we can not obtain a plot.
 */
let defaultPlotFile = ajax_object.default_plot_file;

/***********************************************************************************************************************
 * Functions:
 */

/**
 * Function that is triggered if we can't communicate with customer website.
 */
function inesonicSpeedSentryReportErrorCondition(errorMessage) {
    alert("Lost communication with your website:" + errorMessage);
}

/**
 * Function that updates the current monitor selector.
 */
function inesonicSpeedSentryUpdateRegionSelector() {
    let selectElement = document.getElementById("inesonic-speedsentry-region-select-control");
    while (selectElement.options.length > 1) {
        selectElement.options.remove(1);
    }

    let regionIds = Object.keys(customerRegionsById).sort();
    for (let i=0 ; i<regionIds.length ; ++i) {
        let regionId = regionIds[i];
        let regionName = customerRegionsById[regionId].description;

        let optionElement = document.createElement("option");
        optionElement.value = regionId;
        ('textContent' in optionElement)
            ? (optionElement.textContent = regionName)
            : (optionElement.innerText = regionName);

        selectElement.options.add(optionElement);
    }
}

/**
 * Function that updates our regions selector only if needed.
 */
function inesonicSpeedSentryUpdateRegions() {
    if (customerRegionsById === null && inesonicSpeedSentryLastCapabilities.multi_region_checking) {
        jQuery.ajax(
            {
                type: "POST",
                url: ajax_object.ajax_url,
                data: { "action" : "inesonic_speedsentry_regions_list" },
                dataType: "json",
                success: function(response) {
                    if (response !== null && response.status == 'OK') {
                        customerRegionsById = response.regions;
                        inesonicSpeedSentryUpdateRegionSelector();
                    } else {
                        inesonicSpeedSentryReportErrorCondition(response.status);
                    }
                }
            }
        );
    }
}

/**
 * Function that updates the current monitor selector.
 */
function inesonicSpeedSentryUpdateMonitorSelector() {
    let selectElement = document.getElementById("inesonic-speedsentry-monitor-select-control");
    while (selectElement.options.length > 1) {
        selectElement.options.remove(1);
    }

    let hostSchemeIds = Object.keys(customerAuthorities).sort();
    for (let i=0 ; i<hostSchemeIds.length ; ++i) {
        let hostSchemeId = hostSchemeIds[i];
        let authority = customerAuthorities[hostSchemeId];
        let optionText = userStrings.all_monitors_on.replace("{0}", authority.url);

        let optionElement = document.createElement("option");
        optionElement.value = -hostSchemeId;
        ('textContent' in optionElement)
            ? (optionElement.textContent = optionText)
            : (optionElement.innerText = optionText);

        selectElement.options.add(optionElement);
    }

    for (let i=0 ; i<customerActiveUserOrderValues.length ; ++i) {
        let userOrder = customerActiveUserOrderValues[i];
        let monitor = customerMonitorsByUserOrder[userOrder];
        let monitorId = monitor.monitor_id;

        let optionElement = document.createElement("option");
        optionElement.value = monitorId;
        ('textContent' in optionElement)
            ? (optionElement.textContent = monitor.url)
            : (optionElement.innerText = monitor.url);

        selectElement.options.add(optionElement);
    }
}

/**
 * Function that builds the status/alerts table and events table.  This function also builds the dictionary of newest
 * events by monitor ID.
 */
function inesonicSpeedSentryBuildEventsTable() {
    let eventsTableBody = jQuery("#inesonic-speedsentry-events-body");
    eventsTableBody.empty();

    newestEventByMonitorId = {};

    for (let index=0 ; index<customerEvents.length ; ++index) {
        let event = customerEvents[index];
        let monitorId = event.monitor_id;
        let eventType = event.event_type.toUpperCase();
        let eventTypeString = userStringsFromEventTypes[eventType];
        let eventTimestamp = event.timestamp;
        let eventMessage = event.message;
        let eventDateTime = moment.unix(eventTimestamp);
        let eventDateTimeString = eventDateTime.format("LLL");
        let monitor = (monitorId in customerMonitorsById) ? customerMonitorsById[monitorId] : null;

        let eventUrl = "???";
        if (monitor !== null) {
            if (eventType == "SSL_CERTIFICATE_EXPIRING" || eventType == "SSL_CERTIFICATE_RENEWED") {
                let hostSchemeId = monitor.host_scheme_id;
                if (hostSchemeId in customerAuthorities) {
                    let authority = customerAuthorities[hostSchemeId];
                    eventUrl = authority.url;
                }
            } else {
                eventUrl = monitor.url;
            }
        }

        let eventsTableRow = document.createElement("tr");
        eventsTableRow.className = "inesonic-speedsentry-events-row";

        let eventsTableTimestamp = document.createElement("td");
        eventsTableTimestamp.className = "inesonic-speedsentry-events-datetime";
        ('textContent' in eventsTableTimestamp)
            ? (eventsTableTimestamp.textContent = eventDateTimeString)
            : (eventsTableTimestamp.innerText = eventDataTimeString);

        let eventsTableType = document.createElement("td");
        eventsTableType.className = "inesonic-speedsentry-events-type";
        ('textContent' in eventsTableType)
            ? (eventsTableType.textContent = eventTypeString)
            : (eventsTableType.innerText = eventTypeString);

        let eventsTableUrl = document.createElement("td");
        eventsTableUrl.className = "inesonic-speedsentry-events-url";
        ('textContent' in eventsTableUrl)
            ? (eventsTableUrl.textContent = eventUrl)
            : (eventTableUrl.innerText = eventUrl);

        let eventsTableNotes = document.createElement("td");
        eventsTableNotes.className = "inesonic-speedsentry-events-notes";
        ('textContent' in eventsTableNotes)
            ? (eventsTableNotes.textContent = eventMessage)
            : (eventTableNotes.innerText = eventUrl);

        eventsTableRow.append(eventsTableTimestamp);
        eventsTableRow.append(eventsTableType);
        eventsTableRow.append(eventsTableUrl);
        eventsTableRow.append(eventsTableNotes);

        eventsTableBody.append(eventsTableRow);

        event["datetime"] = eventDateTimeString;
        event['event_type_string'] = eventTypeString;
        event['url'] = eventUrl;

        !(monitorId in newestEventByMonitorId) && (newestEventByMonitorId[monitorId] = event);
    }
}

/**
 * Function that builds the status/alerts table.  Note that this function depends on the dictionary of newest events by
 * monitor ID that is built by the \ref inesonicSpeedSentryBuildEventsTable function.
 */
function inesonicSpeedSentryBuildStatusAlertsTable() {
    let showAllStatus = (jQuery("#inesonic-speedsentry-status-alerts-button").text() == userStrings.show_alerts_only);

    let statusAlertTableBody = jQuery("#inesonic-speedsentry-status-alerts-body");
    statusAlertTableBody.empty();

    for (let i=0 ; i<customerActiveUserOrderValues.length ; ++i) {
        let userOrder = customerActiveUserOrderValues[i];
        let monitor = customerMonitorsByUserOrder[userOrder];
        let monitorId = monitor.monitor_id;
        let lastAlert = "---";
        let statusValue = "UNKNOWN";
        let status = "";

        if (monitorId in newestEventByMonitorId) {
            lastAlert = newestEventByMonitorId[monitorId].event_type_string;
        }

        if (monitorId in customerStatus) {
            statusValue = customerStatus[monitorId].toUpperCase();
        }

        status = userStringsFromStatus[statusValue];

        if (showAllStatus || statusValue == "FAILED") {
            let statusTableRow = document.createElement("tr");
            statusTableRow.className = "inesonic-speedsentry-status-alerts-row";

            let statusUrl = document.createElement("td");
            statusUrl.className = "inesonic-speedsentry-status-alerts-url";
            ('textContent' in statusUrl)
                ? (statusUrl.textContent = monitor.url)
                : (statusUrl.innerText = monitor.url);

            let statusLastEvent = document.createElement("td");
            statusLastEvent.className = "inesonic-speedsentry-status-alerts-last-event";
            ('textContent' in statusLastEvent)
                ? (statusLastEvent.textContent = lastAlert)
                : (statusLastEvent.innerText = lastAlert);

            let statusCurrent = document.createElement("td");

            if (status != "Working") {
                statusCurrent.className = (
                      "inesonic-speedsentry-status-alerts-current-status "
                    + "inesonic-speedsentry-status-alerts-current-status-error"
                );
            } else {
                statusCurrent.className = "inesonic-speedsentry-status-alerts-current-status";
            }

            ('textContent' in statusCurrent)
                ? (statusCurrent.textContent = status)
                : (statusCurrent.innerText = status);

            statusTableRow.append(statusUrl);
            statusTableRow.append(statusLastEvent);
            statusTableRow.append(statusCurrent);

            statusAlertTableBody.append(statusTableRow);
        }
    }
}

/**
 * Function that builds our SSL table.
 */
function inesonicSpeedSentryBuildSslTable() {
    let sslTableBody = jQuery("#inesonic-speedsentry-ssl-body");
    sslTableBody.empty();

    for (let id in customerAuthorities) {
        let authority           = customerAuthorities[id];
        let url                 = authority.url;
        let expirationTimestamp = authority.ssl_expiration_timestamp;

        let expirationDateTimeString;
        if (expirationTimestamp != 0) {
            let expirationDateTime = moment.unix(expirationTimestamp);
            expirationDateTimeString = expirationDateTime.format("LLL");
        } else {
            expirationDateTimeString = "---";
        }

        let sslTableRow = document.createElement("tr");
        sslTableRow.className = "inesonic-speedsentry-ssl-row";

        let sslTableAuthority = document.createElement("td");
        sslTableAuthority.className = "inesonic-speedsentry-ssl-authority";
        ('textContent' in sslTableAuthority)
            ? (sslTableAuthority.textContent = url)
            : (sslTableAuthority.innerText = url);

        let sslTableExpirationDateTime = document.createElement("td");
        sslTableExpirationDateTime.className = "inesonic-speedsentry-ssl-expiration-datetime";
        ('textContent' in sslTableExpirationDateTime)
            ? (sslTableExpirationDateTime.textContent = expirationDateTimeString)
            : (sslTableExpirationDateTime.innerText = expirationDateTimeString);

        sslTableRow.append(sslTableAuthority);
        sslTableRow.append(sslTableExpirationDateTime);

        sslTableBody.append(sslTableRow);
    }
}

/**
 * Function that retrieves alerts, events, monitors, and authorities from Inesonic infrastructure and then updates the
 * customer visible tables.
 */
function inesonicSpeedSentryUpdateTables() {
    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            data: { "action" : "inesonic_speedsentry_multiple_list" },
            dataType: "json",
            success: function(response) {
                if (response !== null) {
                    customerAuthorities = response.authorities;
                    customerStatus = response.status;
                    customerMonitorsByUserOrder = response.monitors;
                    customerEventsUnordered = response.events;

                    customerMonitorsById = {};
                    for (let userOrder in customerMonitorsByUserOrder) {
                        let monitor = customerMonitorsByUserOrder[userOrder];
                        let monitorId = monitor.monitor_id;
                        let hostSchemeId = monitor.host_scheme_id;

                        let url = "???";
                        if (hostSchemeId in customerAuthorities) {
                            let authority = customerAuthorities[hostSchemeId];
                            url = authority.url;
                            if (monitor.path.startsWith("/")) {
                                if (url.endsWith("/")) {
                                    url = url.slice(0, -1);
                                }

                                url += monitor.path;
                            } else {
                                if (url.endsWith("/")) {
                                    url += monitor.path;
                                } else {
                                    url += "/" + monitor.path;
                                }
                            }
                        }

                        monitor.url = url;
                        customerMonitorsById[monitor.monitor_id] = monitor;
                    }

                    customerActiveUserOrderValues = Object.keys(customerMonitorsByUserOrder).sort();

                    customerEvents = Object.values(customerEventsUnordered);
                    customerEvents.sort(function(a, b) { return b.timestamp - a.timestamp; });

                    if (inesonicSpeedSentryLastCapabilities.supports_latency_tracking) {
                        inesonicSpeedSentryUpdateMonitorSelector();
                    }

                    inesonicSpeedSentryBuildEventsTable();
                    inesonicSpeedSentryBuildStatusAlertsTable();

                    if (inesonicSpeedSentryLastCapabilities.supports_ssl_expiration_checking) {
                        inesonicSpeedSentryBuildSslTable();
                    }
                } else {
                    inesonicSpeedSentryReportErrorCondition(response.status);
                }
            }
        }
    );
}

/**
 * Function that issues a request for a plot.
 *
 * \param imageId     The ID of the element to contain the plot image.
 *
 * \param plotType    The type of plot we desire.  Value should be 'histogram' or 'history'.
 *
 * \param imageWidth  The required image width.
 *
 * \param imageHeight The required image height.
 *
 * \param request     Additional plot request parameters.
 */
function inesonicSpeedSentryPlotRequest(imageId, plotType, imageWidth, imageHeight, request) {
    request["action"] = "inesonic_speedsentry_latency_plot";
    request["plot_type"] = plotType;
    request["title"] = "";
    request["x_axis_label"] = "";
    request["y_axis_label"] = "";
    request["width"] = imageWidth;
    request["height"] = imageHeight;
    request["format"] = "PNG";

        if (latencyScale > 0) {
                request["maximum_latency"] = 1.0 * latencyScale;
        }

    if (startTimestamp !== null) {
        request["start_timestamp"] = startTimestamp;
    }

    if (endTimestamp !== null) {
        request["end_timestamp"] = endTimestamp;
    }

    if (regionId !== null) {
        request["region_id"] = regionId;
    }

    if (monitorId !== null) {
        if (monitorId < 0) {
            request["host_scheme_id"] = -monitorId;
        } else {
            request["monitor_id"] = monitorId;
        }
    }

    jQuery.ajax(
        {
            type: "POST",
            url: ajax_object.ajax_url,
            data: request,
            dataType: "json",
            success: function(response) {
                let image = jQuery("#" + imageId);
                if (response["status"] == "OK") {
                    base64Image = response["image"]
                    image.attr(
                        "src",
                        "data:image/png;base64," + base64Image
                    );
                } else {
                    image.attr("src", defaultPlotFile);
                }

                let currentTimestamp = (new Date()).getTime();
                document.getElementById(
                    imageId
                ).className = "inesonic-speedsentry-image dummy_class_" + currentTimestamp;
            }
        }
    );
}

/**
 * Function that updates the latency histogram plot.
 */
function inesonicSpeedSentryUpdateLatencyPlot() {
    let totalWidth = jQuery("#inesonic-speedsentry-monitor-status-top").width();
    let xAxisLabelWidth = jQuery("#inesonic-speedsentry-latency-histogram-x-axis-label").height(); // label is rotated.

    let statusTableWidth = jQuery("#inesonic-speedsentry-status-alerts").width();
    let imageWidth = Math.min(2047, totalWidth - xAxisLabelWidth - 40 - statusTableWidth);

    let imageHeight = 0;
    let maximumHeight = Math.min(1536, Math.floor((3 * window.screen.availHeight) / 4));
    if (imageWidth < 300) {
        imageWidth = Math.min(2047, totalWidth - xAxisLabelWidth);
        imageHeight = Math.min(maximumHeight, Math.max(512, Math.ceil(imageWidth / 2)));
    } else {
        imageHeight = Math.min(maximumHeight, Math.max(512, Math.ceil((3 * imageWidth) / 4)));
    }

    inesonicSpeedSentryPlotRequest(
        "inesonic-speedsentry-latency-histogram-image",
        "histogram",
        imageWidth,
        imageHeight,
        {}
    );
}

/**
 * Function that updates the latency history plot.
 */
function inesonicSpeedSentryUpdateHistoryPlot() {
    let availableWidth = (
          jQuery("#inesonic-speedsentry-history-inner").width()
        - jQuery("#inesonic-speedsentry-history-y-axis-label").width()
    );
    let imageWidth = Math.min(2047, Math.max(300, Math.floor(availableWidth)));
    let maximumHeight = Math.min(1536, Math.floor((3 * window.screen.availHeight) / 4));
    let imageHeight = Math.min(maximumHeight, Math.max(400, Math.ceil(imageWidth / 3)));

    inesonicSpeedSentryPlotRequest(
        "inesonic-speedsentry-history-image",
        "history",
        imageWidth,
        imageHeight,
        {}
    );
}

/**
 * Function that is triggered to update the plots.
 */
function inesonicSpeedSentryUpdatePlots() {
    if (inesonicSpeedSentryLastCapabilities.supports_latency_tracking) {
        inesonicSpeedSentryUpdateLatencyPlot();
        inesonicSpeedSentryUpdateHistoryPlot();
    }
}

/**
 * Function that is triggered at periodic intervals to update the page content.
 */
function inesonicSpeedSentryUpdateContent() {
    inesonicSpeedSentryUpdateRegions();
    inesonicSpeedSentryUpdateTables();
    inesonicSpeedSentryUpdatePlots();
}

/**
 * Function that enables/disables elements by class name.
 *
 * \param className  The class name used to locate elements to be updated.
 *
 * \param nowVisible A flag holding true if elements of the class should be made visible.  A value of false will hide
 *                   the elements.
 */
function inesonicSpeedSentryEnableByClass(className, nowVisible) {
    if (nowVisible) {
        jQuery("." + className).attr("class", className + " inesonic-speedsentry-visible");
    } else {
        jQuery("." + className).attr("class", className + " inesonic-speedsentry-hidden");
    }
}

/**
 * Function that updates timezone data.
 */
function inesonicShowTimezone() {
    jQuery(".timezone").text(moment.tz.guess());
}

/***********************************************************************************************************************
 * Main:
 */

jQuery(window).resize(
    function() {
        if (resizeUpdateTimer !== null) {
            clearTimeout(resizeUpdateTimer);
        }

        resizeUpdateTimer = setTimeout(function() { jQuery(this).trigger('windowResized'); }, RESIZE_UPDATE_DELAY);
    }
);

jQuery(document).ready(function($) {
    // Redirect to the signup page via POST so we don't expose our temporary key.

    $("#inesonic-speedsentry-start-date").datepicker();
    $("#inesonic-speedsentry-end-date").datepicker();

    jQuery("#inesonic-speedsentry-capabilities").on(
            "inesonic-speedsentry-capabilities-changed",
            function(event, capabilities) {
        let connectionIssue = true;
        let customerActive = false;
        let multiRegionChecking = false;
        let supportsLatencyTracking = true;
        let supportsMaintenanceMode = true;
        let supportsSSLExpirationChecking = false;
        let connected = false;

        if (capabilities !== null) {
            connectionIssue = false;
            customerActive = capabilities.customer_active;
            multiRegionChecking = capabilities.multi_region_checking;
            supportsLatencyTracking = capabilities.supports_latency_tracking;
            supportsMaintenanceMode = capabilities.supports_maintenance_mode;
            supportsSSLExpirationChecking = capabilities.supports_ssl_expiration_checking;
            connected = capabilities.connected;
        }

        let isActive = !connectionIssue && connected && customerActive;
        let isUnconfirmed = !connectionIssue && connected && !customerActive;
        let isInactive = !connectionIssue && !connected;

        inesonicSpeedSentryEnableByClass("inesonic-speedsentry-active", isActive);
        inesonicSpeedSentryEnableByClass("inesonic-speedsentry-unconfirmed", isUnconfirmed);
        inesonicSpeedSentryEnableByClass("inesonic-speedsentry-inactive", isInactive);
        inesonicSpeedSentryEnableByClass("inesonic-speedsentry-connection-issue", connectionIssue);

        // Controlling appearance in the JS is a but ugly but I don't really see another great choice.
        let statusAlertsClass = "inesonic-speedsentry-status-alerts";
        if (!supportsLatencyTracking) {
            statusAlertsClass = "inesonic-speedsentry-status-alerts-full-width";
        }
        jQuery("#inesonic-speedsentry-status-alerts").attr("class", statusAlertsClass);

        if (isActive) {
            inesonicSpeedSentryEnableByClass("inesonic-speedsentry-supports-logging", supportsLatencyTracking);
            inesonicSpeedSentryEnableByClass("inesonic-speedsentry-supports-multi-region", multiRegionChecking);
            inesonicSpeedSentryEnableByClass(
                "inesonic-speedsentry-supports-ssl-monitoring",
                supportsSSLExpirationChecking
            );

            if (updateTimer === null) {
                inesonicSpeedSentryUpdateContent();
                updateTimer = setInterval(inesonicSpeedSentryUpdateContent, REFRESH_PERIOD);
            }
        } else {
            if (updateTimer !== null) {
                clearInterval(updateTimer);
                updateTimer = null;
            }
        }
    });

    jQuery("#inesonic-speedsentry-status-alerts-button").text(userStrings.show_alerts_only);

    jQuery("#inesonic-speedsentry-status-alerts-button").click(function(event) {
        let b = jQuery("#inesonic-speedsentry-status-alerts-button");
        b.text(b.text() == userStrings.show_alerts_only ? userStrings.show_all_monitors : userStrings.show_alerts_only);
        inesonicSpeedSentryBuildStatusAlertsTable();
    });

    $("#inesonic-speedsentry-start-date").on("change paste", function() {
        let newValue = $(this).val();
        startTimestamp = moment(moment(newValue).tz(moment.tz.guess()).utc().format()).unix();
        inesonicSpeedSentryUpdatePlots();
    });

    $("#inesonic-speedsentry-end-date").on("change paste", function() {
        let newValue = $(this).val();
        endTimestamp = moment(moment(newValue).tz(moment.tz.guess()).utc().format()).unix();
        inesonicSpeedSentryUpdatePlots();
    });

    $("#inesonic-speedsentry-start-clear-button").click(function(event) {
        startTimestamp = null;
        $("#inesonic-speedsentry-start-date").val("");
        inesonicSpeedSentryUpdatePlots();
    });

    $("#inesonic-speedsentry-end-clear-button").click(function(event) {
        endTimestamp = null;
        $("#inesonic-speedsentry-end-date").val("");
        inesonicSpeedSentryUpdatePlots();
    });

    $("#inesonic-speedsentry-latency-select-control").change(function() {
        latencyScale = $(this).val();
        inesonicSpeedSentryUpdatePlots();
    });

    $("#inesonic-speedsentry-region-select-control").change(function() {
        regionId = $(this).val();
        regionId = (regionId != 0) ? regionId : null;
        inesonicSpeedSentryUpdatePlots();
    });

    $("#inesonic-speedsentry-monitor-select-control").change(function() {
        monitorId = $(this).val();
        monitorId = (monitorId != 0) ? monitorId : null;

        inesonicSpeedSentryUpdatePlots();
    });

    jQuery(window).bind("windowResized", inesonicSpeedSentryUpdatePlots);

    inesonicShowTimezone();
});
