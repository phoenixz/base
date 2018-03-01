(function($){
    $(document).ajaxSuccess(function(event, jqXHR, settings){
//console.log(jqXHR.responseJSON);
//console.log(jqXHR.responseText);
        if (typeof jqXHR.responseJSON != 'object') {
            throw "failed to parse json for url \"" + settings.url + "\"";

        }

        if(typeof jqXHR.responseJSON.result == 'undefined') {
            throw "json contained no result text for url \"" + settings.url + "\"";

        }

        if(typeof jqXHR.responseJSON.data == 'undefined'){
            throw "json contained no data object for url \"" + settings.url + "\"";
        }

        switch (jqXHR.responseJSON.result) {
            case "RELOAD":
                location.reload();
                break;

            case "OK":
                break;

            default:
                throw "invalid json status \"" + jqXHR.responseJSON.result + "\" for url \"" + settings.url + "\"";
        }

        if(jqXHR.responseJSON.csrf){
            // Server sent updated CSRF code, update local value for next request
            $('#ajax_csrf').val(jqXHR.responseJSON.csrf);
        }
    });

    $(document).ajaxError(function(event, jqXHR, settings, thrownError){
        var message = tr("Something went wrong while trying to communicate with the server for url \"" + settings.url + "\". Please try again in a few moments");

        if (typeof jqXHR.responseJSON != 'object') {
            console.error("ERROR: Failed to parse result JSON for url \"" + settings.url + "\"");

        } else if(typeof jqXHR.responseJSON.result == 'undefined') {
            console.error("ERROR: Result JSON did not have a result text for url \"" + settings.url + "\"");

        } else if(typeof jqXHR.responseJSON.data == 'undefined') {
            console.error("ERROR: Result JSON did not have a data object for url \"" + settings.url + "\"");

        } else {
            switch (jqXHR.responseJSON.result) {
                case "OK":
                    // What went wrong?
                    // FALLTHROUGH

                case "RELOAD":
                    // What went wrong?
                    break;

                case 'SIGNIN':
                    // 302, though actually a 401 Unauthorized
                    return $.redirect(jqXHR.responseJSON.data.location);

                 case 'REDIRECT':
                    return $.redirect(jqXHR.responseJSON.data.location);

                case 'MAINTENANCE':
                    // Server is in maintenance mode
                    // FALLTHROUGH

                case 'FORBIDDEN':
                    // 403 Access denied
                    // FALLTHROUGH

                case 'NOT-FOUND':
                    // Server is in maintenance mode
                    // FALLTHROUGH

                case 'ERROR':
                    // Something crapped up, fallthrough to error handler
                    console.error("ERROR for url \"" + settings.url + "\" [ " + jqXHR.responseJSON.result + " ]: ");
                    console.error(jqXHR.responseJSON.data);
                    break;

                default:
                    console.error("UNKNOWN RESULT for url \"" + settings.url + "\" [ " + jqXHR.responseJSON.result + " ]: ");
                    console.error(jqXHR.responseJSON.data);
            }
        }

        /*
         * By default, try to show the js flash message
         */
        if(jqXHR.status == 404){
            if(settings.url.indexOf('/sweetalert.js')){
                console.error('Failed to load sweetalert');
                return false;
            }
        }

        $.flashMessage(message, "error", 0);
        return false;
    });

    jQuery.fn.extend({
        // Report if selected image is okay
        imageOk : function () {
            var $this = this.get(0);

            if (!$this.complete) {
                return false;
            }

            if (typeof $this.naturalWidth !== "undefined" && $this.naturalWidth === 0) {
                return false;
            }

            return true;
        }
    });

    /*
     * $.extensions
     */
    $.unloading = false;

    // Center in screen
    $.center = function () {
        return this.each(function() {
            var top  = ($(window).height() - $(this).outerHeight()) / 2;
            var left = ($(window).width()  - $(this).outerWidth())  / 2;

            $(this).css({position:'fixed', margin:0, top: (top > 0 ? top : 0)+'px', left: (left > 0 ? left : 0)+'px'});
        });
    };

// :OBSOLETE:
    $.handleDone = function (data, cb) {
        if (typeof cb == 'function') {
            return cb(data);
        }
    };

// :OBSOLETE:
    $.handleFail = function (data, cb) {
        if (typeof cb == 'function') {
            return cb(data);
        }
    };

    $.flashMessage = function(message, type, autoClose, selector, opacity){
        // Auto loader for flahs message library
        if (typeof cdnprefix === 'undefined') {
            cdnprefix = "/pub/";
        }

        $.getScript(cdnprefix+"js/base/flash.js")
            .done(function( ){ $.flashMessage(message, type, autoClose, selector, opacity); })
            .fail(function(e){
                alert("The JS flash message system seems not to be working because of \"" + e + "\", sorry for the alerts!");
                alert(message);
            });
    };

    // Redirect in the correct way
    $.redirect = function (data) {
        if (typeof data != "string") {
            throw "Invalid redirect data specified, should be string, is '" + (typeof data) + "'";
        }

        return window.location.replace(data);
    };

    $.createCookie = function (name, value, days) {
        var date, expires;

        if (days) {
            date = new Date();
            date.setTime(date.getTime() + (days * 24 * 60 * 60 * 1000));
            expires = "; expires=" + date.toGMTString();

        } else {
            expires = "";
        }

        document.cookie = escape(name) + "=" + escape(value) + expires + "; path=/";
    };

    $.urlQuery = function(obj) {
        var str = [];
        for(var p in obj)
            if (obj.hasOwnProperty(p)) {
                str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
            }
        return str.join("&");
    };

    $.readCookie = function(name) {
        var nameEQ = escape(name) + "=",
            ca     = document.cookie.split(';'),
            c, i;

        for (i = 0; i < ca.length; i++) {
            c = ca[i];

            while (c.charAt(0) == ' ') {
                c = c.substring(1, c.length);
            }

            if (c.indexOf(nameEQ) === 0) {
                return unescape(c.substring(nameEQ.length, c.length));
            }
        }

        return null;
    };

    $.eraseCookie = function(name) {
        createCookie(name, "", -1);
    };

    $.getCSS = function(url) {
        $('<link/>', {rel: 'stylesheet',
                      type: 'text/css',
                      href: url}).appendTo('head');
    };

    $.geoLocation = function(cb, cbe){
        try {
console.log("geoLocation");
            if (!cbe) {
                cbe = function(e){
                    $.flashMessage("'.tr('Something went wrong, and your location could not be auto detected').'", "error", 0);
                };
            }

            if(navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(cb, cbe);

            } else {
                // Build the URL to query
                var url = "http://www.geoplugin.net/json.gp?jsoncallback=?";

                // Utilize the JSONP API
                $.get(url)
                    .done(function(data){
                        cb(data);
                    })
                    .fail(function(a, b, e) {
                        $.handleFail(e, cbe);
                    });
            }

        } catch(e) {
            throw(e);
        }
    };

    /*
     * $(selector).extensions
     */
    $.fn.extend({
        // Center in screen
        center: function () {
            return this.each(function() {
                var top  = ($(window).height() - $(this).outerHeight()) / 2;
                var left = ($(window).width()  - $(this).outerWidth())  / 2;

                $(this).css({position:'fixed', margin:0, top: (top > 0 ? top : 0)+'px', left: (left > 0 ? left : 0)+'px'});
            });
        }
    });
})(jQuery);



//
$(document).ready(function(){
    $(document)
        .on("click", ".nolink", function(e){
            e.stopPropagation();
            return false;
        })

        .on("click", "td.checkbox", function(e){
            if ($(e.target)[0].nodeName != "INPUT") {
                $(this).find("input").trigger("click");
            }
        });



    // Allow that I can click anywhere on the row to select the link
    $("table.base-link tr")
        .click(function(e){
            if (e.target.nodeName != 'TD') {
                return true;
            }

            e.stopPropagation();

            if ($(e.target).hasClass("base-select")) {
                // This is a select box, (un) check it
                $(e.target).find("input[type=\"checkbox\"]").trigger("click");
                return false;
            }

            window.location = $(this).find("a").first().attr('href');
            return false;
        });



    // Allow the (de)select all checkbox
    $("table.base-select input[type=\"checkbox\"].all")
        .click(function(){
            $(this).closest("table").find("input[type=\"checkbox\"][name=\"" + $(this).prop("name") + "\"]").prop("checked", $(this).is(':checked'));
        });



    // Register that the page is unloading, and another page is being loaded
    $(window).unload(
        function() {$.unloading = true;}
    );
});



//
function isFunction(variable) {
    if(typeof variable === "function") {
        return true;
    }

    return false;
}



// Translation marker
function tr(text){
    return text;
}