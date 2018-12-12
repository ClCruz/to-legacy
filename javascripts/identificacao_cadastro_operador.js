/*
* Função vai no onclick do nome do usuário quando for POS POS
* */
cpfpos = undefined;
gotoEtapa4 = undefined;
function finalizaCadastroPOS(e)
{
	$('.bt_cadastro').click();
	cpfpos = $('input[name="cpfBusca"]').val();
	cpfpos = ( cpfpos != '' && cpfpos != undefined ) ? $('input[name="cpf"]').val(cpfpos) : false;
}

$(function() {

	simples.getCEP($('#cep'));
	$('#dados_conta, p.erro').hide();
	$('#cadastro').slideUp(1);//IE7 FIX
	
	$('.number').onlyNumbers();
	
	$('#buscar').click(function(event) {
		event.preventDefault();
		
		var form = $('#identificacaoForm'),
			 valido = true;

		form.find(':input').each(function() {
			if ($(this).val().length < 3 && $(this).val() != '') valido = false;
		});
		
		if (!valido) {
			$('#resultadoBusca').slideUp('fast', function() {
				$(this).html('<p>Os campos preenchidos devem ter, pelo menos, 3 caractéres para efetuar a busca.</p>');
			}).slideDown('fast');
			return false;
		}
		
		$.ajax({
			url: form.attr('action') + '?' + $.serializeUrlVars(),
			data: form.serialize(),
			type: form.attr('method'),
			success: function(data) {
				$('#resultadoBusca').slideUp('fast', function() {
					$(this).html(data);
				}).slideDown('fast');

				if (gotoEtapa4) {
					$('a.cliente').click();
				}
			}
		});
	});

	$('#estado').on('change', function(){
		// estado == exterior?
		if ($(this).val() == 28) {
			$cep = $('#cep');
			$cep.attr('maxlength',17);

			$cep.on('blur', function(){
				$this = $(this);
				if ($this.val().length == 8) 
				{
					simples.preventGetCEP = false;
					simples.getCEP($this, { getnow: true });
				}
			})
		} else {
			simples.preventGetCEP = false;
		}
	}).trigger('change');

	$('#checkbox_estrangeiro').on('change', function(){
		$('#estado').selectbox('detach');
		if ($(this).is(':checked')) {
			$('#cpf').val('não se aplica').prop('disabled', true).addClass('disabled').slideUp('slow').findNextMsg().slideUp('slow');
			$('#estado').append('<option value="28">Exterior</option>').val(28);
			$('#estado').selectbox('attach');
			$('#tipo_documento').parent('span').slideDown('fast');
			$('#tipo_documento').parent('span').next('div').slideDown('fast');
			simples.preventGetCEP = true;
		} else {
			if ($('#cpf').val() == 'não se aplica') {
				$('#cpf').val('').prop('disabled', false).removeClass('disabled').slideDown('fast');
			}
			simples.preventGetCEP = false;
			$('#estado').find('option[value=28]').remove();
			$('#estado').selectbox('attach').selectbox('enable');
			$('#tipo_documento').parent('span').slideUp('slow', function(){$('#tipo_documento').selectbox('detach').val('').selectbox('attach');});
			$('#tipo_documento').parent('span').next('div').slideUp('slow');
		}

		$('#estado').trigger('change');
	}).trigger('change');
	
	$('#limpar').click(function(event) {
		event.preventDefault();
		
		$('#resultadoBusca').slideUp('fast');
		
		$('#sobrenomeBusca').val('');
		$('#telefoneBusca').val('');
		$('#cpfBusca').val('');
		$('#nomeBusca').val('').focus();
	});
	
	$('#resultadoBusca').on('click', '.cliente', function(event) {
		event.preventDefault();
		
		var $this = $(this);
		
		$.ajax({
			url: $this.attr('href'),
			success: function(data) {
				$('#resultadoBusca').slideUp('fast');
				document.location = data;
			}
		});
	});
	
	$('.bt_cadastro').on('click', function(event) {
		event.preventDefault();
		
		//if ($.browser.msie && $.browser.version.substr(0, 1) == 7) $('#dados_conta > *').show();//IE7 FIX
		
		if ($('#dados_conta').is(':hidden')) {
			$('#resultadoBusca').hide();
			$('#identificacao').hide();
			$('#dados_conta').show();
		} else {
			$('#resultadoBusca').show();
			$('#identificacao').show();
			$('#dados_conta').hide();
		}
	});
	
	$('.salvar_dados').click(function(event) {
		event.preventDefault();	
		var $this = $(this),
			 naoRequeridos = '#senha1,#senha2,#ddd_fixo,#fixo,#complemento,#checkbox_guia,#checkbox_sms,#cep,#checkbox_estrangeiro,[name=sexo],#nascimento_dia,#nascimento_mes,#nascimento_ano,#numero_endereco',
			 especiais = ',#email1,#email2,#rg,#estado,#cidade,#bairro,#endereco,#cpf,#tipo_documento,#ddd_celular,#celular',
			 formulario = $('#form_cadastro'),
			 campos = formulario.find(':input:not(' + naoRequeridos + especiais +')'),
			 valido = true,
			 email_pattern = /\b[\w\.-]+@[\w\.-]+\.\w{2,4}\b/i,
			 email = $('#email1'),
			 email_txt = email.val();

		campos.each(function() {
			var $this = $(this);
			
			if ($this.is(':radio')) {
				var radio = '[name=' + $this.attr('name') + ']';
				
				if (!$(radio).is(':checked')) {
					$this.addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
				} else {
					$this.removeClass('erro').findNextMsg().slideUp('slow');
				}
			} else if ((($this.is(':text') || $this.is('select')) && ($this.val() == '' || $this.val() == undefined)) ||
				($this.is(':checkbox') && !$this.is(':checked'))) {
				$this.addClass('erro').findNextMsg().slideDown('fast');
				valido = false;
			} else $this.removeClass('erro').findNextMsg().slideUp('slow');
		});

		if (!email_pattern.test(email_txt)) {
			email.addClass('erro').findNextMsg().slideDown('fast');
			valido = false;
		} else email.removeClass('erro').findNextMsg().slideUp('slow');

		// estado != exterior?
		if ($('#estado').val() != 28) {
			if ($('#celular').val().length < $('#celular').attr('maxlength') || $('#ddd_celular').val() == ''){
				$('#ddd_celular,#celular').addClass('erro').findNextMsg().slideDown('fast');
				valido = false;
			} else $('#ddd_celular,#celular').removeClass('erro').findNextMsg().slideUp('slow');
		} else {
			if ($('#fixo').val() == '' || $('#ddd_fixo').val() == ''){
				$('#ddd_fixo,#fixo').addClass('erro').findNextMsg().slideDown('fast');
				valido = false;
			} else $('#ddd_fixo,#fixo').removeClass('erro').findNextMsg().slideUp('slow');
		}

		if ($('#checkbox_estrangeiro').is(':checked')) {
			if ($('#tipo_documento').val() == '') {
				$('#tipo_documento').addClass('erro').findNextMsg().slideDown('fast');
				valido = false;
			} else $('#tipo_documento').removeClass('erro').findNextMsg().slideUp('slow');

			if ($('#rg').val() == '') {
				$('#rg').addClass('erro').findNextMsg().slideDown('fast');
				valido = false;
			} else $('#rg').removeClass('erro').findNextMsg().slideUp('slow');
		} else $('#rg').removeClass('erro').findNextMsg().slideUp('slow');

		if (valido) {
			$.ajax({
				url: formulario.attr('action') + '?action=add',
				data: formulario.serialize(),
				type: formulario.attr('method'),
				success: function(data) {
					$('#loadingIcon').fadeIn('fast');
					if (data != 'true') {
						if ($.cookie('user') == null) {
							$.dialog({text: data});
						} else {
							$.dialog({title: 'Aviso...', text: data, iconClass: ''});
						}
					} else {
						gotoEtapa4 = true;

						$('#nomeBusca').val($('#nome').val());
						$('#sobrenomeBusca').val($('#sobrenome').val());
						$('#telefoneBusca').val($('#fixo').val());
						$('#cpfBusca').val($('#cpf').val());
						$('#buscar').click();
					}
				}
			});
		} else {
			$.dialog({text: 'Preencha os campos em vermelho' + (!$('#checkbox_politica')[0] || $('#checkbox_politica').is(':checked') ? '' : '<br>Para se cadastrar você deve estar de acordo com nossa política de privacidade')});
		}
	});

	$('div.input_area').on('change blur', ':input', function(e){
		var $area = $(e.delegateTarget),
			$this = $(this),
			pattern = $this.attr('pattern') ? new RegExp($this.attr('pattern')) : null;

		if (pattern != null) {
			if (pattern.test($this.val())) {
				$this.removeClass('erro').findNextMsg().slideUp('slow');
			} else {
				$this.addClass('erro').findNextMsg().slideDown('fast');
			}
		} else if ($this.is(':radio')) {
			var $radio = $('[name=' + $this.attr('name') + ']');
			
			if (!$radio.is(':checked')) {
				$radio.addClass('erro').findNextMsg().slideDown('fast');
			} else {
				$radio.removeClass('erro').findNextMsg().slideUp('slow');
			}
		}

		if ($area.find(':input.erro').length > 0) {
			$area.addClass('erro')
		} else {
			$area.removeClass('erro')
		}
	});
});