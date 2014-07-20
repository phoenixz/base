(function($){
	$.popup = {};

	// Center in screen
	$.popup.create = function(url, options, cb){
        $.ajax(url)
            .done(function(data , textStatus, jqXHR) {
                // Do some crap here, show the popup, center it, popup background, etc
                data = $(data);
                $("body").append(data);
                $.popup.center(data);

                if(typeof cb == "function"){
                    cb(url);
                }
                //disable scrolling
                $("html").css({
                    "overflow-y": "hidden"
                })
            })

            .fail(function(jqXHR, textStatus, errorThrown){
                // Well implemented fail functions!
                $.flashMessage(errorThrown + '' + url, "error");
            });
    };

    $.popup.destroy = destroy = function(obj){
        obj.closest('.popup.container').remove();
        //restore scrolling
        $('html').css({
            'overflow-y': 'auto'
        })
    };

    $.popup.center = center = function(obj){
        obj.closest('.popup.window').find('.popup.inner').center();
    };

    $.popup.updateHTML = function(html){
        var content = $('.popup.content');
        content.html(html);
        $.popup.center(content);
    };

})(jQuery);


//close on background and closebutton
$(document).on("click", ".popup.close", function(e){
	if(e.target != this) return; // only continue if the target itself has been clicked
	base_popup_destroy($(this));
});
