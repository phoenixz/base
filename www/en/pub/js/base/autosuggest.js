(function($) {
$.fn.autosuggest = function(options) {
	var self     = this;
	var loading  = false;

	if (typeof options == "string") {
		selector = options;
		options  = {}
	}

	var defaults = {
		count       : 10,
		minLength   : 2,
		autoSubmit  : true
	};

	// Overwrite default options
	// with user provided ones
	// and merge them into "options".
	var options = $.extend({}, defaults, options);
	var lists   = this.find("div.autosuggest input").siblings("ul");

	$(lists).on("click", "li", function(e){
		self.val($(this).prop('title'));
		lists.hide();

		if(options.autoSubmit) {
			self.closest('form').submit();
		}
	 });

	//add onhovers
	$(lists).on("mouseenter", "li", function(e){
		$(this).addClass(options.returnClass + '_hover');
	});

	$(lists).on("mouseleave", "li", function(e){
		$(this).removeClass(options.returnClass + '_hover');
	});

	$(this).on("keyup", "div.autosuggest input", function(e){
		switch (e.keyCode) {
			case 38:
				goUp();
				break;

			case 40:
				goDown();
				break;

			case 13:
				goEnter();
				break;
		};

		var $this  = $(this),
		    url    = $this.data("source"),
		    value  = $this.val(),
		    target = $this.siblings("ul");
console.log(value.length);

		if(value.length >= options.minLength) {
			if(loading == false) {
				loading = true;

				$.post(url, { value: value, count: options.count })
					.success(function(data){
						if(typeof data == "string"){
							// Auto parse json
							data = $.parseJSON(data);
						}

						loading = false;

						if(data.result == "OK" && data.html) {
							target
								.html(data.html)
								.addClass("active");

						} else {
							target.removeClass("active");
						}
					});
			}

		} else {
			target.removeClass("active");
		}
	});

	return;

	//// Overwrite default options
	//// with user provided ones
	//// and merge them into "options".
	//var options = $.extend({}, defaults, options);
	//var lists  = $("." + options.returnClass);
	//
	//if (!options.url) {
	//	throw 'No autosuggest source URL specified';
	//}

	return this.keyup(function(e) {
        onChange(e);
	});

	function goEnter() {
		var obj=lists.find('.' + options.returnClass + '_hover');

		if(obj.length) {
			self.val(obj.prop('title'));
		}

		if(options.autoSubmit==true) {
			$.flashMessage(self.clostest('form').html(), "error");
			self.closest('form').submit();
		}
	}

	function goDown() {
		var obj=lists.find('.' + options.returnClass + '_hover');

		if(!obj.length) {
			var next=lists.find('li:first');

		} else {
			var next = obj.next();
		}

		if(next.length) {
			obj.removeClass(options.returnClass + '_hover');
			next.addClass(options.returnClass + '_hover');

		} else {
			next.addClass(options.returnClass + '_hover');
		}
	}

	function goUp() {
		var obj=lists.find('.' + options.returnClass + '_hover');

		if(!obj.length) {
			var obj = lists.find('li:first');
		}

		var next = obj.prev();

		if(next.length) {
			obj.removeClass(options.returnClass + '_hover');
			next.addClass(options.returnClass + '_hover');

		} else {
			obj.addClass(options.returnClass + '_hover');
		}
	}
}

}(jQuery));
