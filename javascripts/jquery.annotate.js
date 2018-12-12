(function($){
	
	$.fn.annotatable = function(annotationCallback, options) {
		var defaults = {
				element: 'img',
				xPosition: 'middle',
				yPosition: 'middle'
			},
			options = $.extend(defaults, options),
			annotations = [],
			targetElement = (options.element == 'this') ? $(this)[0] : $(options.element, this)[0];
		
		$(this).dblclick(function(event){
			if (event.target == targetElement) {
				event.preventDefault();
				
				var element = annotationCallback(options);
				annotations.push(element);
				$(this).append(element);
				
				element.positionAtEvent(event, options.xPosition, options.yPosition);
			}
		});
	};

	$.fn.addAnnotations = function(annotationCallback, annotations, options) {
		var container = this,
			containerHeight = $(container).height(),
			defaults = {
				xPosition: 'middle',
				yPosition: 'middle',
				height: containerHeight
			},
			options = $.extend(defaults, options);
		
		$.each(annotations, function() {
			var element = annotationCallback(this);
			
			element.css({position: 'absolute'});
			
			$(container).append(element);
			
			var left = (this.x * $(container).width()) - ($(element).xOffset(options.xPosition)),
				top = (this.y * options.height) - ($(element).yOffset(options.yPosition));
			
			if (this.width && this.height) {
				var width = (this.width * $(container).width()),
					height = (this.height * $(container).height());
				
				element.css({width: width + 'px', height: height + 'px'});
			}
			
			element.css({ left: left + 'px', top: top + 'px'});
			
			$.each(this, function(key, val) {
				element.data(key, val);
			});
			
			element.attr('title', element.data('name') + ' // ' + element.data('setor'));
			if (element.data('img')) element.attr('data-img', element.data('img'));
			//if (top > containerHeight) element.hide();
		});
	};

	$.fn.positionAtEvent = function(event, xPosition, yPosition) {
		var container = $(this).parent('div');
		
		$(this).css('left', event.pageX - container.offset().left - ($(this).xOffset(xPosition)) + 'px');
		$(this).css('top', event.pageY - container.offset().top - ($(this).yOffset(yPosition)) + 'px');
		$(this).css('position', 'absolute');
	};

	$.fn.seralizeAnnotations = function(xPosition, yPosition) {
		var annotations = [];
		
		this.each(function(){
			var obj = {
				x: $(this).relativeX(xPosition),
				y: $(this).relativeY(yPosition),
				id: $(this).data('id'),
				name: $(this).data('name'),
				setor: $(this).data('setor'),
				img: $(this).data('img')
			};
			if ($(this).data('new_img') != undefined) {
				obj.new_img = $(this).data('new_img');
			}
			annotations.push(obj);
		});
		
		return annotations;
	};

	$.fn.relativeX = function(xPosition) {
		var left = $(this).coordinates().x + ($(this).xOffset(xPosition)),
			width = $(this).parent().width();
		
		return left / width;
	}

	$.fn.relativeY = function(yPosition) {
		var top = $(this).coordinates().y + ($(this).yOffset(yPosition)),
			height = $(this).parent().height();
			
		return top / height;
	}

	$.fn.relativeWidth = function() {
		return $(this).width() / $(this).parent().width();
	}

	$.fn.relativeHeight = function() {
		return $(this).height() / $(this).parent().height();
	}

	$.fn.xOffset = function(xPosition) {
		switch (xPosition) {
			case 'left': return 0; break;
			case 'right': return $(this).width(); break;
			default: return $(this).width() / 2; // middle
		}
	};

	$.fn.yOffset = function(yPosition) {
		switch (yPosition) {
			case 'top': return 0; break;
			case 'bottom': return $(this).height(); break;
			default: return $(this).height() / 2; // middle
		}
	};
	
	$.fn.coordinates = function() {
		return {x: parseInt($(this).css('left').replace('px', '')), y: parseInt($(this).css('top').replace('px', ''))};
	};

	$.fn.removeAnnotations = function(selector) {
		var selector = (selector == undefined) ? 'span' : selector;
		$(this).find(selector).remove();
	};
})(jQuery);