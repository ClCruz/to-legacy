function gotoMainAddress()
{
	$('#form_cadastro').on('dados_salvos', function(){
		document.location.reload();
	});

	$(".botao.dados_conta").trigger('click');

	$('html, body').animate({
		scrollTop: $(".endereco").offset().top
	}, 1000, function () {
		$('#cep').focus();
	});
}

$(function() {
    var estado = $('#novo_estado'),
		cidade = $('#novo_cidade'),
		bairro = $('#novo_bairro'),
		endereco = $('#novo_endereco'),
		numero_endereco = $('#novo_numero_endereco'),
		complemento = $('#novo_complemento'),
		cep = $('#novo_cep'),
		nome = $('#novo_titulo_endereco'),
		id = $('#id'),
		entrega_permitida = false;
	
	$('#bt_novo_endereco').on('click', function(e){
		e.preventDefault();

		$(this).next('div').slideToggle();
	});

	simples.getCEP($('#novo_cep'), { prefix: 'novo_' });

	$('.container_enderecos').on('change', 'input.radio', function(){
		$.ajax({
			url: 'calculaFrete.php?action=verificatempo&etapa=4',
			data: 'idestado=' + $(this).val(),
			type: 'post',
			success: function(data) {
				if (data == 'true') {
					entrega_permitida = true;
				} else {
					entrega_permitida = false;
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

	$('.end_salvar').on('click', function(e) {
		e.preventDefault();

		var $this = $(this),
			valido = true;
		
		var	allFields = $([]).add(endereco)
			.add(numero_endereco)
			.add(bairro)
			.add(cidade)
			.add(estado)
			.add(cep)
			.add(nome);
				
		allFields.each(function() {
			if (!$(this).is(':hidden')) {
			    if ($(this).val() == '') {
					$(this).addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
			    } else {
					$(this).removeClass('erro').findNextMsg().slideUp('slow');
			    }
			}
		});

	    if (estado.val() == '') {
			estado.addClass('erro').findNextMsg().slideDown('fast');
			valido = false;
	    } else {
			estado.removeClass('erro').findNextMsg().slideUp('slow');
	    }

		allFields = allFields.add(complemento).add(id);
								
		if (valido) {
		    $.ajax({
				url: 'cadastro.php?action=manageAddresses',
				type: 'post',
				data: 'endereco=' + endereco.val() +
						'&numero_endereco=' + numero_endereco.val() +
						'&complemento=' + complemento.val() +
						'&bairro=' + bairro.val() +
						'&cidade=' + cidade.val() +
						'&estado=' + estado.val() +
						'&cep=' + cep.val() +
						'&nome=' + nome.val() +
						'&id=' + id.val(),
				success: function(data) {
				    if (data.substr(0, 4) == 'true') {
				    	var new_id = data.split('?')[1];
						$('#enderecos').trigger('endereco_salvo');

				    	if (id) {
				    		$('#radio_endereco_'+id.val()).closest('.select_endereco').remove();
				    	}

						$('<table class="select_endereco">'+
							'<tbody>'+
								'<tr>'+
									'<td class="input">'+
										'<input id="radio_endereco_'+new_id+'" type="radio" name="radio_endereco" class="radio" value="'+new_id+'">'+
										'<label class="radio" for="radio_endereco_'+new_id+'"></label>'+
									'</td>'+
									'<td>'+
										'<div class="container_endereco">'+
										'<p class="titulo">' + nome.val() + '</p>'+
											'<p class="endereco">'+
												endereco.val() + ', ' + numero_endereco.val() + ' - ' + (complemento.val() ? complemento.val() : '') + '<br>'+
												bairro.val() + ', ' + cidade.val() + ' - ' + estado.find(':selected').text() + '<br>'+
												cep.val() +
											'</p>'+
										'</div>'+
									'</td>'+
								'</tr>'+
							'<tr>'+
								'<td class="input"></td>'+
								'<td>'+
									'<a href="cadastro.php?action=manageAddresses&id='+new_id+'" class="end_apagar">apagar</a>'+
									'<a href="cadastro.php?action=getAddresses&id='+new_id+'" class="end_editar">editar</a>'+
								'</td>'+
							'</tr>'+
							'</tbody>'+
						'</table>').insertAfter('table.select_endereco:last');
						$('.end_cancelar').trigger('click');
				    } else {
						$.dialog({text: data});
				    }
				}
			});
		}
	});

	$('.end_cancelar').on('click', function(e) {
		e.preventDefault();
		estado.selectbox('detach');
		$([]).add(endereco)
			.add(numero_endereco)
			.add(bairro)
			.add(cidade)
			.add(estado)
			.add(cep)
			.add(nome)
			.add(complemento)
			.add(id)
			.val('');
		estado.selectbox('attach');
		$('#bt_novo_endereco').next('div').slideToggle();
	});

    $('.container_enderecos').on('click', '.end_apagar', function(event) {
		event.preventDefault();

		var $this = $(this);

		$.confirmDialog({
		    text: 'Tem certeza que deseja<br>apagar o endereço com o título',
		    detail: $this.closest('.select_endereco').find('.titulo').text(),
		    uiOptions: {
				buttons: {
				    'Não': ['Quero continuar usando esse endereço', function() {
						fecharOverlay();
				    }],
				    'Sim': ['Quero apagar esse endereço', function() {
						$.ajax({
						    url: $this.attr('href'),
						    success: function(data) {
								if (data == 'true') {
								    $this.closest('table.select_endereco').remove();
								} else {
								    $.dialog({text: data});
								}
						    }
						});
						fecharOverlay();
				    }]
				}
			}
		});
    });

    $('.container_enderecos').on('click', '.end_editar', function(event) {
		event.preventDefault();

		var $this = $(this);

		$.ajax({
		    url: $this.attr('href'),
		    dataType: 'json',
		    success: function(data) {
				endereco.val(data.endereco);
				numero_endereco.val(data.numero);
				bairro.val(data.bairro);
				cidade.val(data.cidade);
				estado.selectbox('detach');
				estado.val(data.estado);
				estado.selectbox('attach');
				cep.val(data.cep);
				nome.val(data.nome);
				complemento.val(data.complemento);
				id.val(data.id);

				$('#bt_novo_endereco').next('div').slideDown();
		    }
		});
    });

    $('.etapa3 a.botao.avancar').on('click', function(e){
    	e.preventDefault();

		var $this = $(this);

		if ($('input[name=radio_endereco]:checked').val() && entrega_permitida) {
	    	$.ajax({
			    url: 'atualizarPedido.php?action=update',
				type: 'post',
			    data: 'entrega='+$('input[name=radio_endereco]:checked').val(),
			    success: function(data) {
					if (data == 'true') {
						document.location = $this.attr('href');
					} else {
					    $.dialog({text: data});
					}
			    }
			});
	    } else {
	    	$.dialog({text: 'Selecione um endereço válido para a entrega.'});
	    }
    });
	
});