$(function(){
	$('.qtd, input[name="cpf"], input[name="ddd"], input[name="telefone"], input[name="ramal"], input[name="ncartao"]').numeric(false);
	$('#res_recebido').numeric(',');

	if (!$('#overlay')[0]) {
		var $document = $(document),
			$window = $(window),
			$overlay = $('<div id="overlay" class="ui-widget-overlay ui-helper-hidden"></div>').appendTo('body'),
			$loading = $('<div id="ajaxLoadingMsg" class="ui-corner-all ui-state-default ui-helper-hidden" ' +
						'style="width:200px; padding:15px; position:absolute; text-align:center;z-index:1999">' +
						'<img src="../images/ajaxLoading.gif" style="vertical-align:middle;" /> Aguarde, processando...</div>').appendTo('body');
		
		$document.ajaxStart(function() {
			$overlay.width($document.width()).height($document.height()).fadeIn('fast');
			$loading.css({
							'top': ($window.height()/2 - $loading.height()/2)+'px',
							'left': ($window.width()/2 - $loading.width()/2)+'px'
						}).fadeIn('fast');
		}).ajaxStop(function() {
			$overlay.fadeOut('fast');
			$loading.fadeOut('fast');
		});
	}
	
	$('#login, #senha').submit(function(event) {
		event.preventDefault();
		var $this = $(this);
	
		$.ajax({
			url: $this.attr('action'),
			type: $this.attr('method'),
			data: $this.serialize(),
			datatype: 'json',
			success: function(data) {
				if (data.error !== undefined) {
					$('#errorBox').text(data.error).addClass('ui-state-error ui-corner-all').css({'padding':'10px'});
				} else if (data.redirect !== undefined) {
					document.location = data.redirect;
				}
			}
		});
	});
	
	//sistema
	$('#evento').change(function() {
		var $evento = $(this),
			$cbApresentacoes, $options;
		
		$.ajax({
			url: 'sistema.php?action=evento_combo',
			type: 'post',
			data: 'evento=' + $evento.val(),
			dataType: 'json',
			success: function(data) {
				if (data.error === undefined) {
					$cbApresentacoes = $('#apresentacao');
					
					$options = $(data.html).find('option');
					$cbApresentacoes.find('option').remove();
					$cbApresentacoes.append($options);
					
					$('.qtd').val('0').blur();
					$('#apresentacao').change();
					$('input[name="cpf"]').val('').blur();
				} else {
					confirmar_enabled(false);
					displayError(data.error);
				}
			}
		});
	});
	
	$('#apresentacao').change(function() {
		var $apresentacao = $(this);
		
		$.ajax({
			url: 'sistema.php?action=apresentacao_combo',
			type: 'post',
			data: 'apresentacao=' + $apresentacao.val(),
			dataType: 'json',
			success: function(data) {
				if (data.error === undefined) {
					$('table.ing_qtd tbody').html(data.html);
					$('.qtd').numeric(false).blur();
					displayError(false);
				} else {
					confirmar_enabled(false);
					displayError(data.error);
				}
			}
		});
	});
	
	$('body').delegate('.qtd, #res_recebido', 'keyup blur', function() {
		var total = 0,
			recebido = 0;
			
		$('.qtd').each(function(i, e) {
			total += $(e).val() * parseFloat($('.val:eq('+i+')').text().replace(',', '.'));
		});
		
		//para cr�dito manter sempre o total e o recebido iguais (.val(total.toFixed(2).toString().replace('.', ',')))
		recebido = parseFloat($('#res_recebido').val(total.toFixed(2).toString().replace('.', ',')).val().replace(',', '.'));
		
		$('#res_total').text(total.toFixed(2).toString().replace('.', ','));
		$('#res_troco').text((recebido - total).toFixed(2).toString().replace('.', ','));
	});
	
	$('input[name="cpf"]').blur(function() {
		var $cpf = $(this),
			$rg = $('input[name="rg"]'),
			$ddd = $('input[name="ddd"]'),
			$telefone = $('input[name="telefone"]'),
			$ramal = $('input[name="ramal"]'),
			$nome = $('input[name="nome"]'),
			$email = $('input[name="email"]'),
			$ncartao = $('input[name="ncartao"]');
		
		if ($cpf.val() != '') {
			$.ajax({
				url: 'sistema.php?action=cpf_search',
				type: 'post',
				data: 'cpf=' + $cpf.val(),
				dataType: 'json',
				success: function(data) {
					if (data.error === undefined) {
						if (data.nome !== undefined) {
							$rg.val(data.rg);
							$ddd.val(data.ddd);
							$telefone.val(data.telefone);
							$ramal.val(data.ramal);
							$nome.val(data.nome);
							$email.val(data.email);
							$ncartao.val(data.ncartao).focus();
						} else {
							$rg.val('').focus();
							$ddd.val('');
							$telefone.val('');
							$ramal.val('');
							$nome.val('');
							$email.val('');
							$ncartao.val('');
						}
					} else {
						displayError(data.error);
					}
				}
			});
		} else {
			$rg.val('');
			$ddd.val('');
			$telefone.val('');
			$ramal.val('');
			$nome.val('');
			$email.val('');
			$ncartao.val('');
		}
	});
	
	$('#compra').submit(function(e) {
		e.preventDefault();
		var $form = $(this);

		if (confirmar_enabled()) {
			$('#dialog-confirm').dialog({
				resizable: false,
				modal: true,
				width: 500,
				buttons: {
					'Sim': function() {
						$.ajax({
							url: $form.attr('action'),
							type: $form.attr('method'),
							data: $form.serialize(),
							dataType: 'json',
							success: function(data) {
								if (data.error === undefined) {
									document.location = 'redirecionamento.php?evento=' + $('#evento').val() + '&apresentacao=' + $('#apresentacao').val();
								} else {
									displayError(data.error);
								}
							}
						});
						$(this).dialog( "close" );
					},
					'Cancelar': function() {
						$( this ).dialog( "close" );
					}
				}
			});
		}
	});
	
	$('.bt.confirmar').click(function(e) {
		e.preventDefault();
		$('#compra').submit();
	});
	
	$('body').delegate('#evento, #apresentacao, .qtd, .dados_cli input', 'change keyup blur', function() {
		if (validation()) {
			confirmar_enabled(true);
		} else {
			confirmar_enabled(false);
		}
	});
	
	$('body').delegate('input', 'click', function(event) {
		this.focus();
		this.select()
	});
	
	$('input[name="ncartao"]').keyup(function(e){
		var $ncartao = $(this)
			$qtd = $('.qtd'),
			qtd = 0;
		if ($ncartao.val().length == $ncartao.attr('maxlength')) {
			$qtd.each(function(i, e){
				qtd += $(e).val();
			});
			if (qtd == 0) {
				displayError("Pelo menos 1 ingresso promocional deve ser selecionado para participar da promo\u00e7\u00e3o.");
			}
		}
	});

	//functions
	
	function displayError(text) {
		if (text !== false) {
			if (!$('#errorBox').length) {
				$('<div title="Aten&ccedil;&atilde;o!" class="ui-helper-hidden ui-state-error ui-corner-all" id="errorBox"></div>').appendTo('body');
			}
			$('#errorBox').html(text).dialog({
				resizable: false,
				modal: true,
				width: 500,
				buttons: {
					Ok: function() {
						$(this).dialog("close");
					}
				}
			});
		}
	}

	function confirmar_enabled(bool) {
		if (bool === undefined) return !$('.bt.confirmar').is('.disable');
		if (bool) {
			$('.bt.confirmar').removeClass('disable');
		} else {
			$('.bt.confirmar').addClass('disable');
		}
	}

	function validation() {
		var evento = $('#evento').val(),
			apresentacao = $('#apresentacao').val(),
			$ingressos_qtd = $('.qtd').filter(function(){return parseInt($(this).val())}).length,
			$dados_cliente = $('.dados_cli input:not([name="rg"], [name="ddd"], [name="telefone"], [name="ramal"], [name="email"])'),
			valid = true;

		if (evento == '' || apresentacao == '') return false;
		
		if ($ingressos_qtd < 1) return false
		
		$dados_cliente.each(function(i, e) {
			if ($(e).val() == false) valid = false;
		});
		
		return valid
	}

});