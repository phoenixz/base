(function($){
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

    // Handle AJAX JSON done events
    $.handleDone = function (data, cb, cbe) {
//console.log("$.handleDone()");
//console.log(data);
        try {
            if(typeof data == "string"){
                // Auto parse json
                data = $.parseJSON(data);
            }

            // Either data.result or data.status may contain ok / error codes
            if (data.result == undefined) {
                if (data.status == undefined) {
                    throw 'No result found';
                }

                data.result = data.status;
            }

            if (data.result != 'OK') {
                return $.handleFail(data, cbe);
            }

            if (typeof cb == 'function') {
                return cb(data);
            }

            // If no done callback function was specified then just leave it as is.
            return false;

        } catch(e) {
            return $.handleFail({result  : 'EXCEPTION',
                                 message : data}, cbe);
        }
    };

    // Handle AJAX JSON fail events
    $.handleFail = function (data, cb) {
console.log("$.handleFail()");
console.log(data);
        try {
            if (typeof data == 'object') {
                // This is an error / NOT OK notification from data.result from server. errorThrown contains the data
                switch (data.result) {
                    case 'LOGIN':
                        // DEPRECATED! Redirect to the specified login page
                        // FALLTHROUGH

                    case 'SIGNIN':
                        // Redirect to the specified sign in page

// :TODO:SVEN:20130717: Maybe for login redirects it should be possible to have a callback function where we -for example- could display a popup?

                     case 'REDIRECT':
                        // Redirect to the specified page
console.log(data.result + ' > ' + data.redirect);
                        return $.redirect(data.redirect);

                    case 'MAINTENANCE':
                        // Server is in maintenance mode
                        // FALLTHROUGH

                    case 'EXCEPTION':
                        // Something went wrong in the done handler
                        // FALLTHROUGH

                    case 'ERROR':
                        // Something crapped up, fallthrough to error handler
                        // FALLTHROUGH

                    default:
                        // WTF? Unknown result status.

                        if (data.responseText) {
                            data = $.parseJSON(data.responseText);
                        }

                        result = data.result;
                        data = data.message;
                }

            }else{
                result = "ERROR";
            }

            // Something crapped up
            if (typeof cb == 'function') {
                return cb(data, result);
            }

            console.log("ERROR: " + data);

            /*
             * By default, try to show the js flash message
             */
            $.flashMessage(data, "error", 0);
            return false;

        } catch(e) {
            console.log("FAIL HANDLER ERROR");
            console.log(e);

            return false;
        }
    };

    $.flashMessage = function(message, type, autoClose, selector, opacity){
        // Auto loader for flahs message library
        $.getScript("/pub/js/base/flash.js")
            .done(function( ){ $.flashMessage(message, type, autoClose, selector, opacity); })
            .fail(function(e){ alert("The JS flash message system seems not to be working because of \"" + e + "\", sorry for the alerts!");
                               alert(message);});
    }

    // Redirect in the correct way
// :TODO: Document why is this the correct way to redirect in JS
    $.redirect = function (data) {
        if (typeof data == 'object') {
            if (data.redirect == undefined) {
                if (data.message == undefined) {
                    throw "No redirect specified";
                }

                data.redirect = data.message;
            }

            data = data.redirect;
        }

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

    $.readCookie = function(name) {
        var nameEQ = escape(name) + "=",
            ca     = document.cookie.split(';'),
            c, i;

        for (i = 0; i < ca.length; i++) {
            c = ca[i];

            while (c.charAt(0) == ' ') {
                c = c.substring(1, c.length);
            }

            if (c.indexOf(nameEQ) == 0) {
                return unescape(c.substring(nameEQ.length, c.length));
            }
        }

        return null;
    };

    $.eraseCookie = function(name) {
        createCookie(name, "", -1);
    };

    $.geoLocation = function(cb, cbe){
        try {
console.log("geoLocation");
            if (!cbe) {
                cbe = function(e, result){
                    $.flashMessage("'.tr('Something went wrong, and your location could not be auto detected').'", "error", 0);
                }
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
    $("table.link tr")
        .click(function(e){
            if (e.target.nodeName != 'TD') {
                return true;
            }

            e.stopPropagation();

            if ($(e.target).hasClass("select")) {
                // This is a select box, (un) check it
                $(e.target).find("input[type=\"checkbox\"]").trigger("click");
                return false;
            }

            window.location = $(this).find("a").first().attr('href');
            return false;
        });



    // Allow the (de)select all checkbox
    $("table.select input[type=\"checkbox\"].all")
        .click(function(){
            $(this).closest("table").find("input[type=\"checkbox\"][name=\"" + $(this).prop("name") + "\"]").prop("checked", $(this).is(':checked'));
        });



    // Register that the page is unloading, and another page is being loaded
    $(window).unload(
        function() {$.unloading = true;}
    );
});



// Translation marker
function tr(text){
    return text;
}
