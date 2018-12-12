$(function(){
	PagSeguroDirectPayment.setSessionId(pagseguro.sessionId);

	PagSeguroDirectPayment.getPaymentMethods({
		success: function(data) {
			// se boleto pagseguro estiver disponivel
			if (data.paymentMethods.BOLETO.options.BOLETO.status != 'AVAILABLE' && $(':radio[value=900]').length == 1) {
				$(':radio[value=900]').parent().hide();
			}
			// se debito pagseguro estiver disponivel
			if ($(':radio[value=901]').length == 1) {
				var $bancos = $('<div id="bancos" class="linha hidden container_cartoes pagseguro">').insertAfter('.container_dados p.frase'),
					qtBancos = 0;

				$.each(data.paymentMethods.ONLINE_DEBIT.options, function(i,e) {
					if (e.status == 'AVAILABLE') {
						qtBancos++;
						$bancos.append('\
						<div class="container_cartao">\
		    				<input id="'+e.code+'" type="radio" name="bankName" class="radio" value="'+e.name+'">\
		    				<label class="radio" for="'+e.code+'">\
		    					<img src="../images/cartoes/ico_'+e.name+'.png"><br>\
		    				</label>\
		    				<p class="nome">'+e.displayName+'</p>\
		    			</div>');
					}
				});

				if (qtBancos == 0) $(':radio[value=901]').parent().hide();
			}
			// se credito pagseguro estiver disponivel
			if ($(':radio[value=902]').length == 1) {
				var $inputs = $(":input[name=numCartao], :input[name=cardBrand], :input[name=codSeguranca], :input[name=validadeMes], :input[name=validadeAno]"),
					qtBandeiras = 0;

				$.each(data.paymentMethods.CREDIT_CARD.options, function(i,e) {
					if (e.status == 'AVAILABLE') qtBandeiras++;
				});

				if (qtBandeiras > 0) {
					$('input[name=codCartao]').on('change', function(){
						var $this = $(this);
						if ($this.is(':radio[value=902]')) {
							$(":input[name=numCartao]").on('pagseguroBrand', pagseguroBrand);
							$inputs.on('pagseguroToken', pagseguroToken);
							$('.botao_pagamento').addClass('disabled');
						} else {
							$(":input[name=numCartao]").off('pagseguroBrand');
							$inputs.off('pagseguroToken');
							$(":input[name=parcelas] option").prop('disabled', false);
							$('.botao_pagamento').removeClass('disabled');
						}
					});
					$(":input[name=numCartao]").on('change', function(){$(this).trigger('pagseguroBrand')});
					$inputs.on('change', function(){
						var valido = true;
						
						$inputs.each(function(){
							if ($(this).val() == '') valido = false;
						});
						
						if (valido) $(this).trigger('pagseguroToken');
					});
				} else {
					$(':radio[value=902]').parent().hide();
				}
			}
		},
		error: function() {
			$.dialog({text: 'Ocorreu um erro ao obter os dados do PagSeguro.<br/><br/>Se o erro persistir favor informar o suporte.'});
		},
		complete: function(data) {
			$('<input type="hidden" name="senderHash" class="pagseguro" />').val(PagSeguroDirectPayment.getSenderHash()).appendTo('#dadosPagamento');
		}
	});

	function pagseguroBrand(){
		PagSeguroDirectPayment.getBrand({
			cardBin: $(":input[name=numCartao]").val(),
			success: function(data) {
				var $cardBrand = $(':input[name=cardBrand]').length == 1
						? $(':input[name=cardBrand]')
						: $('<input type="hidden" name="cardBrand" class="pagseguro" />').appendTo('#dadosPagamento');

				$cardBrand.val(data.brand.name);
				pagseguroParcelas();
			},
			error: function(){
				$.dialog({text:'Não foi possível identificar a bandeira do cartão.<br><br>Por favor, confira os dados informados.'});
			}
		});
	}

	function pagseguroToken() {
		PagSeguroDirectPayment.createCardToken({
			cardNumber: $(":input[name=numCartao]").val(),
			brand: $(":input[name=cardBrand]").val(),
			cvv: $(":input[name=codSeguranca]").val(),
			expirationMonth: $(":input[name=validadeMes]").val(),
			expirationYear: $(":input[name=validadeAno]").val(),
			success: function(data){
				var $cardToken = $(':input[name=cardToken]').length == 1
						? $(':input[name=cardToken]')
						: $('<input type="hidden" name="cardToken" class="pagseguro" />').appendTo('#dadosPagamento');

				$cardToken.val(data.card.token);
				$('.botao_pagamento').removeClass('disabled');
			},
			error: function() {
				$.dialog({text:'Dados do cartão inválidos. Favor conferir as informações fornecidas.'});
			}
		});
	}

	function pagseguroParcelas() {
		var maxParcelas = $(":input[name=parcelas] option:last").val(),
			cardBrand = $(":input[name=cardBrand]").val();

		PagSeguroDirectPayment.getInstallments({
			amount: pagseguro.amount,
			brand: cardBrand,
			maxInstallmentNoInterest: maxParcelas,
			success: function(data){
				data.installments[cardBrand] = data.installments[cardBrand].slice(0,4);
				$(":input[name=parcelas]").selectbox('detach');
				$(":input[name=parcelas] option").each(function(i,e){
					if (data.installments[cardBrand][i] == undefined)
						$(e).prop('disabled', true);
					else
						$(e).prop('disabled', false);
				}).end()

				if ($(":input[name=parcelas]").val() == null)
					$(":input[name=parcelas]").val($(":input[name=parcelas] option:not(:disabled):last").val());

				$(":input[name=parcelas]").selectbox('attach');
			}
		});
	}
});