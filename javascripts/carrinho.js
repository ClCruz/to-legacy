$(function() {
	$('#forma_entrega_right, #dados_entrega, #identificacao, .err_msg').hide();
	$('.number').onlyNumbers();
	
	var combo_requests = 0;
	var $combo_ingressos = $('[name="valorIngresso\\[\\]"]');
	var fadeAndDestroy = function() {
							$(this).remove();
							updateAllValues();
						};

	// complemento para etapa4
	$('[type="hidden"][name="valorIngresso\\[\\]"]').each(function(){
		var $this = $(this);
		$this.closest('tr').find('.valorIngresso').text($this.attr('valor'));

		if (!$this.is('select') && $this.closest('tr').next('.beneficio').find('.icone_validador').is('.valido')) {
			$this.closest('tr').addClass('complementar')
						.next('.beneficio').slideDown()
						.find('.img_complemento img').attr('src', $this.attr('img1')).end()
						.find('.container_validador img').attr('src', $this.attr('img2'));
		}
	});
	
	$('#cmb_entrega').on('change', function() {
		var bt_next = $('a[href*=etapa3]');

		if ($(this).val() == 'entrega') {
			$('.selecione_estado').slideDown('fast');
			$('#estado').trigger('change');
			bt_next.attr('href', bt_next.attr('href').replace('etapa4', 'etapa3_entrega'));
		} else {
			$('.selecione_estado').slideUp('slow');
			$('#frete').html('sem custo');
			$('#estado').selectbox('detach');
			$('#estado').val('');
			$('#estado').selectbox('attach');
			$('.endereco_radio :radio').removeAttr('checked');
			bt_next.attr('href', bt_next.attr('href').replace('etapa3_entrega', 'etapa4'));
		}
		updateAllValues();
	});
	
	$('#estado').on('change', function(event) {
		event.preventDefault();
		
		var estado = $(this);

		$('#frete').text('');

		if (estado.length != 0) {
			if (estado.val() == '') {
				return false;
			}
		}
		
		$.ajax({
			url: 'calculaFrete.php?action=verificatempo&etapa=2',
			data: 'idestado=' + estado.val(),
			type: 'post',
			success: function(data) {
				if (data == 'true') {
					$.ajax({
						url: 'calculaFrete.php',
						data: 'estado=' + estado.val(),
						success: function(data) {
							$('#frete').html('<span>R$</span> ' + data);
							calculaTotal();
						}
					});
				} else {
					data = $.parseJSON(data);
					$.confirmDialog({
						text: data.text,
						detail: data.detail,
						uiOptions: {
							buttons: {
								'Ok, entendi': ['', function(){
									estado.selectbox('detach')
									estado.val('');
									estado.selectbox('attach')
									fecharOverlay();
								}]
							}
						}
					});
				}
			}
		});
	});

	$('#pedido_resumo').on('keydown', 'input[name=bin\\[\\]]', function(e){
		if (e.keyCode == 13) {
			e.preventDefault();

			$(this).parent('.container_validador').find('a.validarBin').trigger('click');
		}
	});
	
	$combo_ingressos.on('focus', function() {
	    //Store old value
	    $(this).data('lastValue', $(this).val());
	}).on('change', function(e, trigger) {
		var $this = $(this),
			$target = $this.closest('tr').find('.valorConveniencia'),
			ids = [];

		// verifica se esta na etapa 2 ou etapa 4 (select = etapa 2)
		if ($this.is('select')) {
			$this.closest('tr').next('.beneficio').removeClass(function(i, css){ return (css.match(/promo_\d+/g) || []).join(' '); });

			if ($this.find('option:selected').attr('tipoPromo')) {
				$this.closest('tr').next('.beneficio').addClass('promo_'+$this.find('option:selected').attr('tipoPromo'));
			}

			// ingresso selecionado é bin itaucard? tem bin associado?
			if ($this.find('option:selected').attr('codeBin') != undefined) {
				$mesmoBinSelecionado = $('option:selected').filter(function(){
					return $(this).attr('codeBin') != undefined && $(this).attr('codeBin') == $this.find('option:selected').attr('codeBin');
				});

				qtBinSelecionado = $mesmoBinSelecionado.length;

				// ja existe algum ingresso promocional nao validado selecionado?
				// if (qtBinSelecionado > 1 && $('.beneficio:visible').filter(function(){return $(this).find("input[name='tipoBin\\[\\]']").val() == 'itau'}).find('.icone_validador:not(.valido)').length > 0) {
				// 	$this.selectbox('detach');
				// 	$this.val($this.data('lastValue'));
				// 	$this.selectbox('attach');
				// 	// IMPORTANT!: Firefox will not act properly without this:
				// 	$this.blur();

				// 	$.dialog({text: "É necessário efetuar a validação do primeiro ingresso participante da promoção antes de selecionar o segundo ingresso promocional."});
				// 	return false;
				// }

				// o limite de quantidade ainda nao foi atingido?
				if (qtBinSelecionado > $this.find('option:selected').attr('qtBin')) {
					$this.selectbox('detach');
					$this.val($this.data('lastValue'));
					$this.selectbox('attach');
					// IMPORTANT!: Firefox will not act properly without this:
					$this.blur();

					$.dialog({text: "A quantidade máxima de ingressos promocionais para esta apresentação foi atingida."});
					return false;
				} else {
					// se o combo foi alterado manualmente e ja existe um ingresso promocional selecionado, copiar o conteudo
					if (qtBinSelecionado > 1 && trigger != 'automatico') {
						$this.closest('tr').addClass('complementar').next('.beneficio')
							.html($mesmoBinSelecionado.filter(function(){return !$(this).parent().is($this)}).closest('tr.complementar').next('.beneficio').html())
							.slideDown();
						$this.closest('tr').next('.beneficio').find('[name="bin\\[\\]"]')
							.val($mesmoBinSelecionado.filter(function(){return !$(this).parent().is($this)}).closest('tr.complementar').next('.beneficio').find('[name="bin\\[\\]"]').val());
						window.validarBin = function(){$this.closest('tr').next('.beneficio').find('.validarBin').trigger('click', [true])};
					} else {
						sizeBin = $this.find('option:selected').attr('sizeBin');
						placeholder = sizeBin + ' primeiros números do seu cartão';

						$this.closest('tr').addClass('complementar')
							.next('.beneficio')
							.find('input[name=bin\\[\\]]').val('').attr('maxlength', sizeBin).attr('placeholder', placeholder).end()
							.find('.img_complemento img').attr('src', $this.find('option:selected').attr('img1')).end()
							.find('.container_validador img').attr('src', $this.find('option:selected').attr('img2')).end()
							.slideDown();

						if (trigger != 'automatico'
							&& $this.closest('tr').next('.beneficio').find('.icone_validador').is('.valido')
							&& $this.closest('tr').next('.beneficio').find('input[name=tipoBin\\[\\]]').val() != 'itau') {
							var	$tr = $this.closest('tr').next('.beneficio'),
								$hidden = $tr.find('.hidden'),
								$notHidden = $tr.find('.notHidden'),
								$bin = $tr.find('.validador_itau');

							$hidden.removeClass('hidden').addClass('notHidden');
							$notHidden.removeClass('notHidden').addClass('hidden');
							$tr.find('.icone_validador').removeClass('valido');
							$bin.val('').prop('readonly', false);
						}
					}

					$this.closest('tr').next('.beneficio').find('input[name=tipoBin\\[\\]]').val('itau').end();
				}

			// ingresso selecionado é promocional?
			} else if ($this.find('option:selected').attr('codpromocao') != undefined) {
				var evento_id = $this.closest('.resumo_espetaculo').data('evento');
				
				$promocoesSelecionadas = $('.resumo_espetaculo').filter(function(){
					return $(this).data('evento') == evento_id;
				}).find('option:selected').filter(function(){
					return $(this).attr('codpromocao') == $this.find('option:selected').attr('codpromocao');
				});

				qtPromocoesSelecionadas = $promocoesSelecionadas.length;

				if (qtPromocoesSelecionadas > $this.find('option:selected').attr('qtPromocao')) {
					$this.selectbox('detach');
					$this.val($this.data('lastValue'));
					$this.selectbox('attach');
					// IMPORTANT!: Firefox will not act properly without this:
					$this.blur();

					$.dialog({text: "A quantidade máxima de ingressos promocionais para esta apresentação foi atingida."});
					return false;
				}

				if (trigger != 'automatico'
					&& $this.closest('tr').next('.beneficio').find('.icone_validador').is('.valido')) {
					var	$tr = $this.closest('tr').next('.beneficio'),
						$hidden = $tr.find('.hidden'),
						$notHidden = $tr.find('.notHidden'),
						$bin = $tr.find('.validador_itau');

					$hidden.removeClass('hidden').addClass('notHidden');
					$notHidden.removeClass('notHidden').addClass('hidden');
					$tr.find('.icone_validador').removeClass('valido');
					$bin.val('').prop('readonly', false);
				}

				sizeBin = $this.find('option:selected').attr('sizeBin');
				placeholder = 'código promocional';

				$this.closest('tr').addClass('complementar')
					.next('.beneficio')
					.find('input[name=bin\\[\\]]').val('').attr('maxlength', sizeBin).attr('placeholder', placeholder).end()
					.find('.img_complemento img').attr('src', $this.find('option:selected').attr('img1')).end()
					.find('.container_validador img').attr('src', $this.find('option:selected').attr('img2')).end()
					.find('input[name=tipoBin\\[\\]]').val('promocao').end()
					.slideDown();
			} else {
				$this.closest('tr').next('.beneficio').find('[name="bin\\[\\]"]').val('');
				$this.closest('tr').removeClass('complementar').next('.beneficio').slideUp(function(){
					if ($this.closest('tr').next('.beneficio').find('.icone_validador').is('.valido')) {
						$this.closest('tr').next('.beneficio').find('.icone_validador').removeClass('valido')

						$hidden = $this.closest('tr').next('.beneficio').find('.hidden');
						$notHidden = $this.closest('tr').next('.beneficio').find('.notHidden');

						$hidden.removeClass('hidden').addClass('notHidden');
						$notHidden.removeClass('notHidden').addClass('hidden');

						$this.closest('tr').next('.beneficio').find('.validador_itau').prop('readonly', false);
					}
				});
			}

			$this.parent('td').parent('tr').find('span.valorIngresso').text($this.find('option:selected').attr('valor'));
		}

		if (trigger == 'automatico') $('#pedido').find('[name="trigger"]').val('automatico');

		carregarDadosGerais(function(){
			if ($('.alert .bilhete_lote_indisponivel').length > 0 && $this.data('lastValue')) {
				if ($this.data('lastValue') != $('.alert .bilhete_lote_indisponivel').text()) {
					$this.selectbox('detach');
					$this.val($this.data('lastValue'));
					$this.selectbox('attach');

					// IMPORTANT!: Firefox will not act properly without this:
					$this.blur();
				}

				$('.alert .bilhete_lote_indisponivel').remove();
			} else {
				if (!$this.data('lastValue') && trigger != 'automatico') {
					$this.selectbox('detach');
					$this.val($this.find('option:not(:checked):first').val());
					$this.selectbox('attach');
				}

				$this.data('lastValue', $this.val());
			}

			if ($this.find('option:selected').attr('codigo') != undefined && !$this.closest('tr').next('tr').find('.icone_validador').is('.valido')) {
				$this.closest('tr').next('.beneficio').find('.validador_itau').val($this.find('option:selected').attr('codigo'));
				$this.closest('tr').next('.beneficio').find('.validarBin').trigger('click');
			}

			updateValorServico($this.val(), $target);
		
			combo_requests++;

			$('#pedido :input[name="apresentacao\\[\\]"]').each(function(){
				if (combo_requests > $combo_ingressos.length - 1 && $.inArray(this.value, ids) == -1) {
					ids.push(this.value);
					atualizarCaixaMeiaEntrada(this.value);
				}
			});

			if (window.validarBin != undefined) window.validarBin();
		}, $('#pedido'));

		$('#pedido').find('[name="trigger"]').val('')
	}).trigger('change', ['automatico']);

	$('.beneficio').on('click', '.validarBin', function(e, skipChanges, selectLastValue) {
		e.preventDefault();

		var $tr = $(this).closest('tr'),
			$hidden = $tr.find('.hidden'),
			$notHidden = $tr.find('.notHidden'),
			$bin = $tr.find('.validador_itau')
			$tipoBin = $bin.next('input[name="tipoBin\\[\\]"]'),
			reserva = $tr.prev('tr').find('[name="reserva\\[\\]"]').val();

		if ((($tipoBin.val() == 'itau' && $bin.val().length < $bin.attr('maxlength')) || $bin.val().length == 0) && $tr.find('img').eq(0).attr('src') != '') {
			$bin.addClass('erro');
		} else {
			$bin.removeClass('erro');

			ajax = $.ajax({
				url: 'validarBin.php?carrinho=1',
				type: 'post',
				data: 'reserva=' + reserva + '&bin=' + $bin.val() + '&tipoBin=' + $tipoBin.val()
			});

			if (!skipChanges) {
				ajax.done(function(data){
					if (data == 'true') {
						$hidden.removeClass('hidden').addClass('notHidden');
						$notHidden.removeClass('notHidden').addClass('hidden');
						$tr.find('.icone_validador').addClass('valido');
						$bin.prop('readonly', true);
					} else {
						$.dialog({text: data});
					}
				});
			}
		}
	})
	
	$('.removerIngresso').on('click', function(event) {
		event.preventDefault();
		
		var $this = $(this),
			 resumo = $this.closest('div.resumo_espetaculo');
		
		$.ajax({
			url: $this.attr('href'),
			success: function(data) {
				if (data.substr(0, 4) == 'true') {
					retorno = data.split('?');
					idsLength = (retorno.length > 1) ? retorno[1].split('|').length : retorno.length;
					if (idsLength <= 1) {
						if (resumo.find('.totalIngressosApresentacao').val() == 1) {
							resumo.slideUp('fast', fadeAndDestroy);
							resumo.prev('.titulo').slideUp('slow', fadeAndDestroy);
						} else {
							$this.closest('tr').fadeOut('slow', fadeAndDestroy);
						}
					} else {
						if (resumo.find('.totalIngressosApresentacao').val() <= idsLength) {
							resumo.slideUp('fast', fadeAndDestroy);
							resumo.prev('.titulo').slideUp('slow', fadeAndDestroy);
						} else {
							ids = retorno[1].split('|');
							for (i = 0; i < idsLength; i++) {
								$(':input[name=cadeira\\[\\]][value='+ids[i]+']').closest('tr').fadeOut('slow', fadeAndDestroy);
							}
						}
					}
					window.location = window.location;
				}
			}
		});
	});
	
	function verificaTempoLimite(idEstado, idEtapa){
		var retornoFunc;
		$.ajax({
			url: 'calculaFrete.php?action=verificatempo&etapa='+ idEtapa,
			type: 'post',
			data: 'idestado=' + idEstado,
			async: false,
			success: function(data){
				if(data != "true"){
					retornoFunc = false;
				}else{
					retornoFunc = true;					
				}
			},
			error: function(){
				$.dialog({
					title: 'Erro...',
					text: 'Erro na chamada dos dados !!!'
				});	
				return false;
			}
		});
		return retornoFunc;
	};
	
	$('#botao_avancar, #botao_pagamento, .botao_avancar, .botao_pagamento').on('click', function(event) {
		event.preventDefault();
		
		var etapa = 0,
			 estado = $('#estado'),
			 $this = $(this),
			 url = $this.attr('href'),
			 form = $('#pedido');
			 
		if (estado.length != 0) {
			if ($('#cmb_entrega').val() == 'entrega' && estado.val() == '') {
				$.dialog({text: 'Selecione um estado para a entrega.'});
				return false;
			}
		}

		if ($('.beneficio:visible .icone_validador:not(.valido)').length > 0) {
			$.dialog({text: 'Todos os ingressos promocionais devem ser validados antes de continuar.'});
			return false;
		}

		estado = $('.endereco_radio :radio:checked');
		if((etapa == 4) && ($('#cmb_entrega').val() == 'entrega')){
			retornoVerifica = verificaTempoLimite(estado.val(), etapa);
			if(retornoVerifica == true){
				carregarDadosGerais($this, form);
			}else{
				$.dialog({
					title: 'Atenção!!!',
					text: 'Tempo não suficiente para entrega dos ingressos.<br>Favor alterar o tipo de forma de entrega.'
				});	
			}				
		}
		else{
			carregarDadosGerais($this, form);	
		}
	});
	
	updateAllValues();
});
	
// Função para quando usuário clicar no botão avançar das etapas
function carregarDadosGerais($this, form){
	$.ajax({
		url: form.attr('action'),
		data: form.serialize(),
		type: form.attr('method'),
		success: function(data) {
			if (data == 'true') {
				if (typeof($this) == 'function') {
					$this();
				} else {
					document.location = $this.attr('href');
				}
			} else {
				$.dialog({text: data});
				if (typeof($this) == 'function') $this();
			}
		}
	});	
}

function calculaTotalLinha() {
	$('.valorTotalLinha').each(function() {
		var val = 0;
		$(this).parent('td').parent('tr').find('span.valorIngresso').each(function() {
			val += parseFloat($(this).text().replace(',', '.'));
		});
		$(this).parent('td').parent('tr').find('span.valorConveniencia').each(function() {
			val += parseFloat($(this).text().replace(',', '.'));
		});
		$(this).text(val.toFixed(2).replace('.', ','));
	});
};

function calculaQuantidadeIngressos() {
	$('.totalIngressosApresentacao').each(function() {
		$(this).text($('.valorIngresso').length);
	});
};

function calculaQuantidadeTotalIngressos() {
	var val = 0;
	$('.totalIngressosApresentacao').each(function() {
		val += parseInt($(this).text().replace(',', '.'));
	});
	$('#quantidadeIngressos').text(val.toFixed(0).replace('.', ','));
	setQuantidadeResumo(val);
};

function calculaTotalIngressos() {
	var val = 0;
	$('.valorTotalLinha').each(function() {
		val += parseFloat($(this).text().replace(',', '.'));
	});
	$('#totalIngressos').text(val.toFixed(2).replace('.', ','));
};

function calculaTotal() {
	calculaTotalIngressos();
	$('#totalIngressos').text(
		(
		parseFloat($('#totalIngressos').text().replace(',', '.'))
		+
		parseFloat($('#servico_pedido').text().replace(',', '.').replace('', '0'))
		+
		parseFloat((($('#cmb_entrega').val() == 'entrega') ? $('#frete').text().replace(',', '.').replace('R$ ', '').replace('', '0') : 0))
		).toFixed(2).replace('.', ','));
};

function updateAllValues() {
	calculaTotalLinha();
	calculaQuantidadeIngressos();
	calculaQuantidadeTotalIngressos();
	calculaTotalIngressos();
	calculaTotal();

	$.cookie('total_exibicao', $('#totalIngressos').text());
	
	if ($('#quantidadeIngressos').val() == 0) {
		$('#botao_avancar, #botao_pagamento, .botao_avancar, .botao_pagamento').fadeOut('slow', fadeAndDestroy);
	}
}

if ($.cookie('entrega') != null) {
	$('#cmb_entrega').val('entrega');
	$('#cmb_entrega').change();
}

function updateValorServico(bilhete, target) {
	$.ajax({
		url: 'valorServico.php',
		type: 'POST',
		data: 'id_bilhete=' + bilhete,
		success: function(data) {
			target.text(data.valor);
			if (data.valor == '0,00') {
				updateValorServicoPorPedido(bilhete, target);
				target.parent().html(' - <span class="valorConveniencia hidden">0,00</span>');
			} else {
				target.parent().html('R$ '+data.valor+'<span class="valorConveniencia hidden">'+data.valor+'</span>');
				$('#servico_pedido').text('0');
				$('#servico_por_pedido').slideUp('fast');
			}
			updateAllValues();
		}
	});
}

function updateValorServicoPorPedido(bilhete, target) {
	$.ajax({
		url: 'valorServico.php',
		type: 'POST',
		data: 'id_bilhete=' + bilhete + '&servicoPorPedido=1',
		success: function(data) {
			if (data.valor != '0,00') {
				$('#servico_pedido').text(data.valor);
				$('#servico_por_pedido').slideDown('fast');
			} else {
				$('#servico_por_pedido').slideUp('fast');
				$('#servico_pedido').text(data.valor);
			}
			updateAllValues();
		}
	});
}

//Função de testes para exibir imagem do bin validado
function validamanual()
{
	$this = $(document.forms.pedido['4']);

	var	$tr = $this.closest('tr').next('.beneficio'),
		$hidden = $tr.find('.hidden'),
		$notHidden = $tr.find('.notHidden'),
		$bin = $tr.find('.validador_itau');

	$hidden.removeClass('hidden').addClass('notHidden');
	$notHidden.removeClass('notHidden').addClass('hidden');
	$tr.find('.icone_validador').addClass('valido');
	$bin.prop('readonly', true);
}