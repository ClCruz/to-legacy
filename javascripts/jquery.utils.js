function trimAll(text) {
	return text.replace(/[ \t\r\n\v\f]/g,"");
}

function trim(text) {
	return text.replace(/^\s+|\s+$/g,"");
}

function atualizarCaixaMeiaEntrada(id) {
	$('#loadingIcon').fadeIn('fast');

	$.ajax({
		url: 'atualizarPedido.php',
		data: 'action=atualizarCaixaMeiaEntrada&id=' + id,
		success: function(data) {
			var caixa = $('#cme-'+id).html($(data).html());

			if (caixa.find('.contagem-meia').text() == '0') {
				caixa.next('.resumo_pedido').find('.valorIngresso\\[\\] :not(:selected)[meia_estudante="1"]').attr('disabled','disabled');
			} else {
				caixa.next('.resumo_pedido').find('.valorIngresso\\[\\] [meia_estudante="1"]').removeAttr('disabled');
			}
		},
		complete: function() {
			$('#loadingIcon').fadeOut('slow');
		}
	});
}


function sortColumn(column, hasHeader){
    var table = $(column).parents('table').eq(0)
    var rows = table.find('tr' + (hasHeader ? ':gt(0)' : '')).toArray().sort(comparer($(column).index()))
    column.asc = !column.asc
    if (!column.asc){rows = rows.reverse()}
    for (var i = 0; i < rows.length; i++){table.append(rows[i])}
}
function comparer(index) {
    return function(a, b) {
        var valA = getCellValue(a, index), valB = getCellValue(b, index)
        return $.isNumeric(valA) && $.isNumeric(valB) ? valA - valB : valA.localeCompare(valB)
    }
}
function getCellValue(row, index){
	return $(row).children('td').eq(index).data("tosort") != undefined ? $(row).children('td').eq(index).data("tosort") : $(row).children('td').eq(index).html();
}

(function($){
	$.ajaxSetup({cache: false});

	$.fn.onlyNumbers = function(allowPunctuation, allowMinus) {
		$(this).keydown(function(event) {
			if ((event.keyCode >= 48 && event.keyCode <= 57) ||
				(event.keyCode >= 96 && event.keyCode <= 105) ||
				(event.keyCode >= 37 && event.keyCode <= 40) ||
				(event.keyCode == 8 || event.keyCode == 46 || event.keyCode == 9 || event.keyCode == 116) ||
				(allowPunctuation && event.keyCode == 188) ||
				(allowMinus && event.keyCode == 189)) {
			} else {
				event.preventDefault();
			}
		});
		return $(this);
	};
	
	$.fn.onlyAlpha = function() {
		$(this).keydown(function(event) {
			console.log(event.keyCode);
			if ((event.keyCode >= 65 && event.keyCode <= 90) ||
				(event.keyCode >= 37 && event.keyCode <= 40) ||
				(event.keyCode == 8 || event.keyCode == 46 || event.keyCode == 9 || event.keyCode == 116)) {
			} else {
				event.preventDefault();
			}
		});
		return $(this);
	};
	
	$.fn.findNextMsg = function(selector) {
		var selector = (selector == undefined) ? 'p.aviso,p.err_msg' : selector,
			 $this = $(this),
			 proximo = $this.next(selector);
		if (proximo.length > 0) {
			return proximo;
		} else {
			if ($this.parent().length > 0) {
				return $this.parent().findNextMsg();
			} else {
				return null;
			}
		}
	}
	
	$.extend({
		dialog: function(options) {
			var dialog,
				defaults = {
					title: 'Aviso...',
					iconClass: 'ui-icon ui-icon-alert',
					text: 'Ocorreu um erro durante o processo...<br><br>Favor informar o suporte!',
					uiOptions: {
						width: 500,
						resizable: false,
						modal: true,
						autoOpen: false,
						buttons: {
							'Ok': function() {
								$(this).dialog('close');
							}
						},
						close: function() {
							$(this).dialog('destroy').remove();
						}
					 }
				 },
				 options = $.extend(true, defaults, options),
				 element = $('<div />')
								.attr('title', options.title)
								.hide()
								.appendTo('body'),
				 p = $('<p />')
				 				.appendTo(element),
				 span = (options.iconClass != '') ? $('<span />')
				 										.addClass(options.iconClass)
														.css('float', 'left')
														.css('margin', '0 7px 20px 0')
													: '';
				 
			p.append(span).append(options.text);
			element.dialog(options.uiOptions);

			same_message = $('.ui-dialog-content').filter(function(i,e){
				return $(e).text() == element.text();
			}).length;

			if (same_message == 1) element.dialog('open');
			else element.dialog('destroy').remove();
		},
		
		confirmDialog: function(options) {
			var defaults = {
					title: 'Confirmação...',
					iconClass: 'ui-icon ui-icon-alert',
					text: 'Deseja concluir a operação?',
					uiOptions: {
						resizable: false,
						modal: true,
						buttons: {
							'Sim': function() {
								$(this).dialog('close');
							},
							'Cancelar': function() {
								$(this).dialog('close');
							}
						}
					 }
				 },
				 options = $.extend(true, defaults, options),
				 //uiOptions = $.extend(uiOptionsDefault, options.uiOptions),
				 element = $('<div />')
								.attr('title', options.title)
								.hide()
								.appendTo('body'),
				 p = $('<p />')
				 				.appendTo(element),
				 span = (options.iconClass != '') ? $('<span />')
				 										.addClass(options.iconClass)
														.css('float', 'left')
														.css('margin', '0 7px 20px 0')
													: '';
				 
			p.append(span).append(options.text);
			element.dialog(options.uiOptions);
		},
		
		busyCursor: function(options) {
			var defaults = {
					id: 'loadingIcon',
					image: '../images/loading.gif',
					appendTo: 'body',
					css: {},
					followMouse: true
				 },
				 options = $.extend(defaults, options),
				 element = $(document.createElement('img'))
								.attr('id', options.id)
								.attr('src', options.image)
								.hide()
								.css(options.css)
								.appendTo(options.appendTo);
			
			if (options.followMouse) {
				$(document).mousemove(function(event){
					element.css({
						top: (event.pageY + 15) + "px",
						left: (event.pageX + 15) + "px"
					});
				});
			}
			
			return element;
		},
		
		getUrlVars: function(url) {
			var vars = [],
				 hash,
				 url = (url == undefined) ?  window.location.href : url,
				 hashes = url.slice(url.indexOf('?') + 1).split('&');
			if (url.indexOf('?') == -1) return '';
			for(var i = 0; i < hashes.length; i++) {
				hash = hashes[i].split('=');
				vars.push(hash[0]);
				vars[hash[0]] = hash[1];
			}
			return vars;
		},
		
		getUrlVar: function(name, url) {
			return $.getUrlVars(url)[name];
		},
		
		serializeUrlVars: function(url) {
			var vars = '';
			$.each($.getUrlVars(url), function(key, val) {
				vars += val + '=' + $.getUrlVar(val, url) + '&';
			});
			return vars.substr(0, vars.length - 1);
		}
	});
})(jQuery)