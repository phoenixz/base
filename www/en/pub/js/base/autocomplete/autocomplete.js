(function($) {
$.fn.autocomplete = function(options) {
	var self = this;
	var loading = false;
	// set up default options
	var defaults = {
		url:		'/ajax/search_suggest.php',
		returnClass:	'search_suggest',
		count:		10,
		minLength:	2,
		autoSubmit:	true
	};

	// Overwrite default options
	// with user provided ones
	// and merge them into "options".
	var options = $.extend({}, defaults, options);

	var target = $("." + options.returnClass);

	this.keydown(function(event) {
                switch (event.keyCode) {
			case 38: go_up(); break;
			case 40: go_down(); break;
			case 13: go_enter(); break;
                };
	});

	return this.keyup(function(event) {
                onChange(event);
	});

	function go_enter() {
		var obj=target.find('.' + options.returnClass + '_hover');
		if(obj.length>0) {
			self.val(obj.prop('title'));
		}
		if(options.autoSubmit==true) {
			$.flashMessage(self.clostest('form').html(), "error");
			self.closest('form').submit();
		}
	}

	function go_down() {
		var obj=target.find('.' + options.returnClass + '_hover');
		if(obj.length==0) {
			var next=target.find('li:first');
		} else {
			var next = obj.next();
		}
		if(next.length>0) {
			obj.removeClass(options.returnClass + '_hover');
			next.addClass(options.returnClass + '_hover');
		} else {
			next.addClass(options.returnClass + '_hover');
		}

		//obj.
	}

	function go_up() {
		//console.log('up');
		var obj=target.find('.' + options.returnClass + '_hover');
		if(obj.length==0) {
			var obj=target.find('li:first');
		}
		var next = obj.prev();
		if(next.length>0) {
			obj.removeClass(options.returnClass + '_hover');
			next.addClass(options.returnClass + '_hover');
		} else {
			obj.addClass(options.returnClass + '_hover');
		}
	}

	function onChange(event){
		var value = self.val();
		if(value.length>=options.minLength && event.keyCode!=40 && event.keyCode!=38 && event.keyCode!=13) {
			if(loading == false) {
				loading = true;
				$.ajax({
					type: 'POST',
					url: options.url,
					data: { value: value, count: options.count },
					dataType: 'json',
					success: function(data) {
						loading = false;
						if(data.result=='OK' && data.count>0) {
							target.html(data.html).show();
							//add onclicks
							$(document).on("click","." + options.returnClass + " li", function(event){
								self.val($(this).prop('title'));
								target.hide();
								if(options.autoSubmit==true) {
									self.closest('form').submit();
								}
							 });
							//add onhovers
							$(document).on("mouseenter","." + options.returnClass + " li", function(event){
								$(this).addClass(options.returnClass + '_hover');
							});
							$(document).on("mouseleave","." + options.returnClass + " li", function(event){
								$(this).removeClass(options.returnClass + '_hover');
							});
						} else {
							target.hide();
						}
					}
				});
			}
		}
	}
}

}(jQuery));