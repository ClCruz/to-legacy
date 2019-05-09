$(function(){
	var titular = $('input[name="nomeCartao"]'),
		nomePresente = $('input[name=nomePresente]'),
		emailPresente = $('input[name=emailPresente]');

	$('#dadosPagamento').areYouSure({
		message: 'Seu pedido está fase de aprovação, aguarde sua finalização para não ocorrer inconsistências no processo de pagamento.',
		fieldSelector: '.nothing'
	});

	$('#dadosPagamento').on('submit', function(e) {
		if ($("#loaded_pagarme").length>0 && $("#loaded_pagarme").val() == "1" && $(".card_hash").length==0) {
			console.log("forcing...");
			pagarmeToken();
		}


		valido = true;
	    e.preventDefault();

	    var $this = $(this),
	    	valido = true;

        if ($('input[name="usuario_pdv"]').val() == 0){
            if ($('[name=codCartao]:checked').val() === undefined) {
                $.dialog({text: 'Selecione o cartão desejado.'});
                return false;
            }
        }

        if ($('[name=codCartao]:checked').next('label').next('p.nome').text().toLowerCase().indexOf('fastcash') == -1
			&& $('[name=codCartao]:checked').next('label').next('p.nome').text().toLowerCase().indexOf('boleto') == -1
			&& ($('[name=codCartao]:checked').next('label').next('p.nome').text().toLowerCase().indexOf('débito') == -1 && $('[name=codCartao]:checked').val() != '921')) {
		    $this.find(':input:not(.compra_captcha :input, [name=nomePresente], [name=emailPresente], .pagseguro :input, .botao)').each(function(i,e) {
				var e = $(e);
				if (e.attr("id")!="paypal_data" && e.attr("id")!="paypal_payment") {
					if (e.val().length < e.attr('maxlength')/2 || e.val() == '') {
						e.addClass('erro');
						valido = false;
					} else e.removeClass('erro');
				}
		    });
		}

        if ($('input[name="usuario_pdv"]').val() == 0) {
            if (trim(titular.val()).length < 3 && !titular.is(':hidden')) {
                titular.addClass('erro');
                valido = false;
            } else titular.removeClass('erro');

            if (nomePresente[0] && !nomePresente.is(':hidden')) {
	            if (trim(nomePresente.val()).length < 3) {
	                nomePresente.addClass('erro');
	                valido = false;
	            } else nomePresente.removeClass('erro');

	            emailPresente.removeClass('erro');
	            if (emailPresente.val() != '') {
	            	var email_pattern = /\b[\w\.-]+@[\w\.-]+\.\w{2,4}\b/i;

		            if (!email_pattern.test(emailPresente.val())) {
		                emailPresente.addClass('erro');
		                valido = false;
		            }
		        }
	        }
		}
		
		if ($("[name=codCartao]:checked").length > 0 && $($("[name=codCartao]:checked")[0]).val() == "101") {
			valido = false;
			//paypal
			if ($("#paypal_data").val()!="" && $("#paypal_payment").val() !="") {
				var obj = JSON.parse($("#paypal_payment").val());
				if (obj!=null && obj!=undefined) {
					if (obj.state == "approved" && obj.transactions.length>0 
					&& obj.transactions[0].related_resources.length>0 
					&& obj.transactions[0].related_resources[0].sale.state == "completed") {
						valido = true;
					}
				}
			}

			if (!valido) {
				$.dialog({text: 'Realize o pagamento usando o botão do paypal.'});
				return;
			}
		}

    	if (valido) {
    		// parar contagem regressiva
				CountStepper = 0;
				
				$(".botao_pagamento").hide();

    		$('#dadosPagamento').addClass('dirty');

    		$.confirmDialog({
				text: 'O seu pedido está sendo processado e isso pode levar alguns segundos.<br/>Por favor, não feche ou atualize seu navegador. Em instantes você será redirecionado(a) a página de confirmação.<br/><br/><img src="../images/ico_loading.gif">',
				detail: '',
				uiOptions: {buttons: {'': ['']}}
			});

    		$.ajax({
    			url: $this.attr('action'),
				type: $this.attr('method'),
				data: $this.serialize()
    		}).done(function(data){
				// console.log("ret of...");
				// console.log(data);
				$('#dadosPagamento').removeClass('dirty');

				if (data.substr(0, 7) == 'myobj::') {
					let objAux = JSON.parse(data.substr(7, data.length));
					if (objAux.success) {
						fecharOverlay();
						$.dialog({text: "Compra efetuada com sucesso!"});	
						if (objAux.printisafter == true || objAux.printisafter == "true" || objAux.printisafter == 1 || objAux.printisafter == "1") {
							document.location = "pagamento_ok.php?pedido="+objAux.id_pedido_venda;
							
						}
						else {
							document.location = "pagamento_pagarme.php?pedido="+objAux.id_pedido_venda;
						}
					}
					else {
						$(".botao_pagamento").show();
						CountStepper = -1;
						fecharOverlay();
						$.dialog({text: objAux.msg});	
					}
					return;
				}

				if (data.substr(0, 8) == 'redirect') {
					document.location = data;
				} else if (data == 'valorDiferentePaypal') {
					$.dialog("Os valores pagos não conferem por favor entre em contato.");
				} else if (data == 'valorDiferente') {
					$.confirmDialog({
						text: "Os dados referentes ao seu pedido foram alterados e o valor total pode ser diferente do valor exibido anteriormente.<br/>Por favor, revise o pedido antes de continuar.",
						detail: '',
						uiOptions: {
							buttons: {
								'Ok, entendi': ['Leve-me de volta para a<br>etapa anterior', null]
							}
						}
					});
					$('#resposta .opcao.unica').attr('href', './etapa2.php');
				} else {
					$('select').selectbox('detach')
					$('[name=nomeCartao], [name=numCartao], [name=codSeguranca], #validadeMes, #validadeAno').val('');
					$('select').selectbox('attach')

					fecharOverlay();
					$.dialog({text: data});
		    		// continuar contagem regressiva
		    		CountStepper = -1;
		    		$(":input[name=numCartao]").trigger('pagarmeToken');
    			}
    		});

    		fechaLoading();

    		if (typeof(grecaptcha) !== 'undefined') grecaptcha.reset();
    		if (typeof(BrandCaptcha) !== 'undefined') BrandCaptcha.reload();
	    } else {
	    	$.dialog({text: 'Preencha os campos em vermelho'});
	    }
	});

	$('a.meu_codigo_cartao').on('click',function(e){
		e.preventDefault();

		if ($('div.img_cod_cartao').is(':hidden')) {
			var $cartao = $('input[name=codCartao]:checked');
			var img = $cartao.attr('imgHelp');
			$('div.img_cod_cartao img').attr('src',img);
	        $('div.img_cod_cartao').fadeIn(500);
		} else {
			$('div.img_cod_cartao').fadeOut(200);
		}
	});

	$('input[name=codCartao]').on('change', function(){
		var $cartao = $('input[name=codCartao]:checked');

		$('[name=nomeCartao], [name=numCartao], [name=codSeguranca], #validadeMes, #validadeAno').val('');

		if ($cartao.next('label').next('p.nome').text().toLowerCase().indexOf('boleto') > -1) {
			$(".isCard").slideUp().end();
		}
		else if ($cartao.next('label').next('p.nome').text().toLowerCase().indexOf('fastcash') > -1) {
			$(".isCard").slideDown().end();
			$('.container_dados').find('.linha:not(#bancos)').eq(0).slideUp().end()
												.eq(1).slideUp().end().end()
								.find('#bancos').slideUp().end()
								.find('.frase .alt').eq(0).text('Presente');
		} else if ($cartao.next('label').next('p.nome').text().toLowerCase().indexOf('débito') > -1 && $('[name=codCartao]:checked').val() != '921') {
			$(".isCard").slideDown().end();
			$('.container_dados').find('.linha:not(#bancos)').eq(0).slideUp().end()
												.eq(1).slideUp().end().end()
								.find('#bancos').slideDown().end()
								.find('.frase .alt').eq(0).text('Dados do Banco');
		} else if ($cartao.next('label').next('p.nome').text().toLowerCase().indexOf('paypal') > -1) {
			
		} else {
			$(".isCard").slideDown().end();
			$('.container_dados').find('.linha:not(#bancos)').eq(0).slideDown().end()
												.eq(1).slideDown().end().end()
								.find('#bancos').slideUp().end()
								.find('.frase .alt').eq(0).text('Dados do cartão');

			if (!$('div.img_cod_cartao').is(':hidden')) {
				$('div.img_cod_cartao').fadeOut(200, function(){
					$('a.meu_codigo_cartao').trigger('click');
				});
			}

			$('#validadeMes').selectbox('detach');
			$('#validadeAno').selectbox('detach');
			$('select[name=qt_parcelas]').selectbox('detach');
			$('.container_dados :input:not(:radio)').val('');
			$('select[name=qt_parcelas]').val(1)
			$('#validadeMes').selectbox('attach');
			$('#validadeAno').selectbox('attach');
			$('select[name=qt_parcelas]').selectbox('attach');

			$('input[name=numCartao]').mask($cartao.attr('formatoCartao'));
			$('input[name=numCartao]').next('.erro_help').find('.help').text($cartao.attr('formatoCartao').replace(/0/g, 'X'));

	    	if ($cartao.attr('formatoCodigo')) $('input[name=codSeguranca]').mask($cartao.attr('formatoCodigo'));
	    }
	});

	$('a.presente_toggle').on('click', function(e){
		e.preventDefault();

		$('.presente').slideToggle(function(){
			$(this).find(':input').val('');
		});

		$('.explicacao_envio_presente').fadeOut();
	});

	$('a.envio_presente_explicao').on('click',function(e){
		e.preventDefault();
		$('.explicacao_envio_presente').fadeToggle();
	});

	$('.botao_pagamento').on('click', function(e){
		e.preventDefault();

		if ($(this).is('.disabled')) return false;
		
		$('#dadosPagamento').submit();
	});
});