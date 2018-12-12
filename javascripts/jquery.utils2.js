TimeoutToHideDialog = undefined;

if ( typeof simples == 'undefined' ) {
	$.getScript('../javascripts/simpleFunctions.js', function () {
		simples.init();
	});
}

$(document).ready(function () {
	enterSubmit();
	function enterSubmit()
	{
		try{
			cfgForm();
		}catch (E){
			console.log(E);
		}

		function cfgForm()
		{
			var form = document.forms.identificacao;
			var iptPass 	= null;
			var iptSubmit 	= null;

			var i = 0;
			while (i < form.length)
			{
				var input = form[i];
				if (input.getAttribute('name') == 'senha')
				{
					iptPass = input;
				}

				if (input.getAttribute('type') == 'button' && input.getAttribute('id') == 'logar')
				{
					iptSubmit = input;
				}
				i++;
			}

			if (iptPass != null && iptSubmit != null) {
				setDynamicClick(iptPass, iptSubmit);
			}

			function setDynamicClick(pass, submit)
			{
				pass.onkeyup = function (key) {
					if (key.keyCode == 13) {
						submit.click();
					}
				}
			}
		}
	}
});

function trimAll(text) {
	return text.replace(/[ \t\r\n\v\f]/g,"");
}

function trim(text) {
	return text.replace(/^\s+|\s+$/g,"");
}

function atualizarCaixaMeiaEntrada(id) {
	$.ajax({
		url: 'atualizarPedido.php',
		data: 'action=atualizarCaixaMeiaEntrada&id=' + id,
		success: function(data) {
			var caixa = $('input[name=apresentacao\\[\\]][value='+id+']').closest('div.resumo_espetaculo').find('span.meia_entrada').html(data);

			if (caixa.find('.contagem-meia').text() == '0') {
				$('#pedido_resumo').find('.valorIngresso\\[\\] :not(:selected)[meia_estudante="1"]').each(function(){
					$(this).parent().selectbox('detach');
					$(this).attr('disabled','disabled');
					$(this).parent().selectbox('attach');
				});
			} else {
				$('#pedido_resumo').find('.valorIngresso\\[\\] [meia_estudante="1"]').each(function(){
					$(this).parent().selectbox('detach');
					$(this).removeAttr('disabled');
					$(this).parent().selectbox('attach');
				});
			}
		}
	});
}

(function($){
	$.ajaxSetup({cache: false});

	$.fn.onlyNumbers = function() {
		$(this).keydown(function(event) {
			if ((event.keyCode >= 48 && event.keyCode <= 57) ||
				(event.keyCode >= 96 && event.keyCode <= 105) ||
				(event.keyCode >= 37 && event.keyCode <= 40) ||
				(event.keyCode == 8 || event.keyCode == 46 || event.keyCode == 9 || event.keyCode == 116)) {
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
		var selector = (selector == undefined) ? '.erro_help:first' : selector,
			 $this = $(this),
			 proximo = $this.nextAll(selector).find('.erro');
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
			var $dialog = $('div.alert'),
				defaults = {
					text: 'Ocorreu um erro durante o processo...<br><br>Favor informar o suporte!',
					autoHide: { set: false, time: 3000 },
					onOverlay: { set: false, div: null }
				 },
				 options = $.extend(true, defaults, options),
				 element = $dialog.find('div.container_erros'),
				 p = $('<p />');
			p.html(options.text);
			element.html(p);

			if ($dialog.is(':hidden')) {
				$dialog.slideDown('fast');
			} else {
				$dialog.fadeTo(100, .6).fadeTo(100, 1).fadeTo(100, .6).fadeTo(100, 1);
			}

			if (options.icon == undefined || options.icon == 'erro') {
				options.icon = 'ico_erro_notificacao.png';
			}else if(options.icon == 'ok'){
				options.icon = 'ico_ok.png';
			}

			$dialog.find('img').attr('src', '../images/'+options.icon);

			if (options.autoHide.set) {

				//limpar timeout caso tenha sido executaod recentemente com alguma informação
				if ( TimeoutToHideDialog != undefined ) {
					clearTimeout(TimeoutToHideDialog);
					TimeoutToHideDialog = undefined;
				}

				TimeoutToHideDialog = setTimeout(function() {
					$dialog.slideUp('slow');
				},options.autoHide.time);
			}
		},
		
		confirmDialog: function(options) {
			var defaults = {
					text: 'Deseja concluir a operação',
					detail: '?',
					uiOptions: {
						buttons: {
							'Ok': ['', function () {
							}]
						}
					}
				};

				$overlay = $('#overlay')[0]
							? $('#overlay')
							: $('<div id="overlay"></div>').appendTo('#pai'),
				$element = $overlay.find('#resposta')[0]
							? $overlay.find('#resposta')
							: $('<div class="centraliza" id="resposta">'+
									'<img src="../images/ico_white.png">'+
									'<p class="frase"></p>'+
									'<p class="aviso"></p>'+
									'<a class="opcao a">'+
										'<span class="opcao"></span>'+
										'<span class="descricao"></span>'+
									'</a>'+
									'<a class="opcao b">'+
										'<span class="opcao"></span>'+
										'<span class="descricao"></span>'+
									'</a>'+
								'</div>').appendTo($overlay);

			bt_keys = Object.keys(options.uiOptions.buttons);
			
			$element.hide()
				.find('.frase').html(options.text).end()
				.find('.aviso').html(options.detail).end()
				.find('a.opcao').remove();
			if (bt_keys.length > 1) {
				$element.append('<a class="opcao a">'+
									'<span class="opcao">'+bt_keys[0]+'</span>'+
									'<span class="descricao">'+options.uiOptions.buttons[bt_keys[0]][0]+'</span>'+
								'</a>');
				$element.find('.opcao.a').off('click').on('click', options.uiOptions.buttons[bt_keys[0]][1]);

				$element.append('<a class="opcao b">'+
									'<span class="opcao">'+bt_keys[1]+'</span>'+
									'<span class="descricao">'+options.uiOptions.buttons[bt_keys[1]][0]+'</span>'+
								'</a>');
				$element.find('.opcao.b').off('click').on('click', options.uiOptions.buttons[bt_keys[1]][1]);
			} else {
				$element.append('<a class="opcao unica">'+
									'<span class="opcao">'+bt_keys[0]+'</span>'+
									'<span class="descricao">'+options.uiOptions.buttons[bt_keys[0]][0]+'</span>'+
								'</a>');
				$element.find('.opcao.unica').off('click').on('click', options.uiOptions.buttons[bt_keys[0]][1]);
			}

			abreOverlay('resposta');
		},
		
		getUrlVars: function(url) {
			var vars = [],
				 hash,
				 url = (url == undefined) ?  window.location.href : url,
				 hashes;
			if (url.indexOf('?') == -1) return '';
			if (url.indexOf('#') !== -1) {
				url = url.substr(0, url.indexOf('#'));
			}
			hashes = url.slice(url.indexOf('?') + 1).split('&');
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
})(jQuery);

// adiciona eventos para os metodos do jquery listados abaixo
(function ($) {
    var methods = ['addClass', 'toggleClass', 'removeClass'];

    $.map(methods, function (method) {
        // store the original handler function
        var originalMethod = $.fn[method];

        $.fn[method] = function () {
            // execute the original hanlder
            var result = originalMethod.apply(this, arguments);

            // trigger the custom event
            this.trigger(method, arguments);

            // return the original handler
            return result;
        };
    });
})(jQuery);