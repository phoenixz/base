(function($){
    $.flashMessage = function(message, type, autoClose, selector, opacity){
        var	animation;

        if ($.unloading) {
            // Don't show error when the window is being unloaded
            console.log('$.flashMessage(): Supressing messge "' + message + '" due to window unload');
        }

console.log("FLASHMESSAGE");
console.log("type " + type);
console.log("message " + message);
console.log("autoClose " + autoClose);
console.log("selector " + selector);

        switch(type){
            case "info":
                type = "information";
                // FALLTHROUGH
            case "error":
                if(type == "exception"){
                    type = "error";
                }
                // FALLTHROUGH
            case "information":
                // FALLTHROUGH
            case "success":
                // FALLTHROUGH
            case "attention":
                break;

            default:
console.log('Unknown flash message type');
console.log(type);
                throw '$.flashMessage(): Unknown message type "' + type + '" specified. Please specify either "error", "info", "syccess", or "attention"';
        }

        if (!selector) {
            selector = "#jsFlashMessage";
        }

        $selector = $(selector);

        if(!$selector.length){
            /*
             * There is no flash message element with the specified selector. Use alert() as alternative
             */
            console.log("****************************************************************");
            console.log(message);
            console.log("****************************************************************");

            if(!isFunction("swal")){
                $.getScript(cdnprefix+"js/sweetalert/sweetalert.js")
                    .done(function(){
                        $.getCSS(cdnprefix+"css/sweetalert/sweetalert.css");

                        swal({title: "Notice",
                              type: type,
                              html: message});
                    })
                    .fail(function(e){
                        console.log("****************************************************************");
                        console.log(e);
                        console.log("****************************************************************");
                    });

            }else{
                swal({title: "Notice",
                      type: type,
                      html: message});
            }

        }else{
            $selector
                .hide()
                .css("opacity", 0)
                .removeClass()
                .addClass("sys_msg")
                .addClass("sys_" + type)
                .html(message)
                .show();

            if (!autoClose) {
                // Click to close
                $selector.addClass("clickClose");
            }

            if (!opacity) {
                opacity = 100;
            }

            $selector.animate({opacity : opacity}, 300, function(){
                var close = function(){
                    $selector.animate({opacity : 0, height : 0}, 500, function(){
                        $(this).hide();
                    });
                }

                $(".clickClose").click(function(){
                    var $self = $(this);

                    $self.animate({opacity : 0, height : 0}, 500, function(){
                        $self.hide();
                    });
                });

                if (autoClose) {
                    // Negative autoclose will never close
                    if (autoClose > 0) {
                        setTimeout(close, autoClose);
                    }
                }

                if (autoClose > 0) {
                    // Positive autoclose will close with a click, or after timeout
                    $selector.click(function(){
                        close();
                    });
                }
            });
        }
    };

})(jQuery);
