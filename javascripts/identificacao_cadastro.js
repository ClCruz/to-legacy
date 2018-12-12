Cadastro = {
	showForm: function(wich)
	{
		var esqueci = $('#esqueciForm');
		var login 	= $('#loginForm');

		if ( wich == 'esqueci' )
		{
			$(login).slideUp(function () {
				$(esqueci).slideDown();
			});
		}
		else if('login')
		{
			$(esqueci).slideUp(function () {
				$(login).slideDown();
			})
		}

	}
};

$(function() {
	$('#dados_conta, #esqueciForm, p.erro').hide();
	
	$('.number').onlyNumbers();

	var email_pattern = /\b[\w\.-]+@[\w\.-]+\.\w{2,4}\b/i;

	$('#logar').click(function(event) {
		event.preventDefault();
		var $this = $(this),
			 form = $('#identificacaoForm'),
			 email = $('#login'),
			 email_txt = email.val(),
			 senha = $('#senha'),
			 senha_txt = senha.val(),
			 valido = true;
		
		if (!email_pattern.test(email_txt)) {
			email.addClass('erro').findNextMsg().slideDown('fast');
			valido = false;
		} else email.removeClass('erro').findNextMsg().slideUp('slow');
		
		if (senha_txt.length < 6) {
			senha.addClass('erro').findNextMsg().slideDown('fast');
			valido = false;
		} else senha.removeClass('erro').findNextMsg().slideUp('slow');
		
		if (valido) {
			$.ajax({
				url: form.attr('action') + '?' + $.serializeUrlVars(),
				data: form.serialize(),
				type: form.attr('method'),
				success: function(data) {
					try
					{
						var obj = JSON.parse(data);
						if (obj.status) {
							ciPopup.hide(function () {
								$.dialog({text:'Login efetuado com sucesso!', icon: 'ok'});
							}, 1000);
							if ($('#mapa_de_plateia_geral, #numIngressos').length == 0)
								document.location.reload();
						}else{
							ciPopup.msgDialog('Combinação de usuário e senha incorreta')
						}
					}
					catch (Ex)
					{
						if (data.substr(0, 4) == 'redi') {
							document.location = data;
						} else {
							$.dialog({text:'Combinação de usuário e senha incorreta'});
						}
					}
				}
			});
		}
	});

	$('#lembrei_senha').click(function () {
		Cadastro.showForm('login');
	});



	$('#esqueci').click(function(event) {

		if (simples.serverURL() == 'etapa1.php')
		{
			Cadastro.showForm('esqueci');
		}
		else
		{
			event.preventDefault();
			if ($('#esqueciForm').is(':hidden')) {
				$('#esqueciForm').slideDown('slow');
			} else {
				$('#esqueciForm').slideUp('slow');
			}
		}
	});
	
	$('#enviar_senha').click(function(event) {
		event.preventDefault();
		var $this = $(this),
			 email = $('#recupera_por_email'),
			 email_txt = email.val();
		
		if (!email_pattern.test(email_txt)) {
			email.addClass('erro').findNextMsg().slideDown('fast');
			return false;
		} else email.removeClass('erro').findNextMsg().slideUp('slow');
		
		$.ajax({
			url: $this.attr('href'),
			data: 'email=' + email_txt,
			success: function(data) {

				try{
					var obj = JSON.parse(data);
					if ( obj.status )
					{
						ciPopup.msgDialog('Um e-mail com instruções para recuperar sua senha foi enviado para '+email_txt,
							{
								autoHide: { set: true }
							});
						Cadastro.showForm('login');
					}
					else
					{
						ciPopup.msgDialog(obj.msg, {
							options: { autoHide: { set:true } }
						});
					}
				}catch (Ex){
					if (data == 'true') {
						successOnRedefine();
					} else {
						$.dialog({title: 'Aviso...', text: data});
					}
				}

			}
		});

		//Exibe mesnagem de sucesso abaixo do form
		function successOnRedefine() {
			email.val('');
			$this.next('.resultado').find('span').text(email_txt).end()
				.slideDown('fast')
				.delay(6000)
				.slideUp('slow');
			$('#esqueciForm').slideDown().delay(6500).slideUp('slow');
		}
	});
	
	$('a.bt_cadastro').click(function(event) {
		event.preventDefault();
		
		if ($('#dados_conta').is(':hidden')) {
			$('#identificacao').slideUp('slow');
			$('#dados_conta').slideDown('slow');
		} else {
			$('#dados_conta').slideUp('slow');
			$('#identificacao').slideDown('slow');
		}
	});
	
	// comum para minhaconta
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
			if ($('#cpf').val() == 'não se aplica') {
				$('#cpf').val('').prop('disabled', false).removeClass('disabled').slideDown('fast');
			}

			simples.preventGetCEP = false;
		}
	}).trigger('change');

	$('#checkbox_estrangeiro').on('change', function(){
		$('#estado').selectbox('detach');
		if ($(this).is(':checked')) {
			$('#cpf').val('não se aplica').prop('disabled', true).addClass('disabled').slideUp('slow').findNextMsg().slideUp('slow');
			$('#estado').append('<option value="28">Exterior</option>').val(28);
			//$('#estado').selectbox('attach').selectbox('disable');
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
	
	$('.salvar_dados').click(function(event) {
		event.preventDefault();
		
		//alteração de senha
		if ($.cookie('user') != null && $('#dados_conta').is(':hidden')) {
			var form = $('#trocar_senha'),
				 campos = form.find(':input:not([type="button"])'),
				 valido = true,
				 $this = $(this);
			
			campos.each(function() {
				if (($(this).is("[id*='senha']") && $(this).val().length < 6) || ($(this).val() == '')) {
					$(this).addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
				} else {
					$(this).removeClass('erro').findNextMsg().slideUp('slow');
				}
			});
			
			if ($('#senha1').val() != $('#senha2').val() || $('#senha1').val() == '') {
				$('#senha2').addClass('erro').findNextMsg().slideDown('fast');
				valido = false;
			} else {
				$('#senha2').removeClass('erro').findNextMsg().slideUp('slow');
			}
			
			if (valido) {
				$.ajax({
					url: form.attr('action') + '?action=passChange',
					data: form.serialize(),
					type: form.attr('method'),
					success: function(data) {
						if (data == 'true') {
							$this.next('.erro_help').find('.help').slideDown('fast').delay(3500).slideUp('slow');
							campos.val('');
						} else {
							$(("[id='senha']")).addClass('erro').findNextMsg().slideDown('fast');
						}
					}
				});
			}
			
			return;

		} else {
			var $this = $(this),
				 naoRequeridos = '#email,[id^=nascimento],[name=sexo],#complemento,#checkbox_guia,#checkbox_sms,#checkbox_estrangeiro',
				 especiais = '#ddd_fixo,#fixo,#email1,#email2,#senha1,#senha2,[name="tag"],.recaptcha :input,[type="button"],#cpf,#tipo_documento,#rg,#ddd_celular,#celular',
				 formulario = $('#form_cadastro'),
				 campos,
				 valido = true;
			
			if ($('body').is('.mini')) {
				naoRequeridos += ',.endereco :input';
			}

			campos = formulario.find(':input:not(' + naoRequeridos + ',' + especiais +')');

			campos.each(function() {
				var $this = $(this);
				
				if ($this.is(':radio')) {
					var radio = '[name=' + $this.attr('name') + ']';
					
					if (!$(radio).is(':checked')) {
						$this.addClass('erro').findNextMsg().slideDown('fast');
						valido = false;
						console.log(this);
					} else {
						$this.removeClass('erro').findNextMsg().slideUp('slow');
					}
				} else if ((($this.is(':text') || $this.is('select')) && ($this.val() == '' || $this.val() == undefined)) ||
					($this.is(':checkbox') && !$this.is(':checked'))) {
					$this.addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
					console.log(this);
				} else $this.removeClass('erro').findNextMsg().slideUp('slow');
			});

			if ($('body').is('.mini')) {
				if ($('#celular').val().length < $('#celular').attr('maxlength') || $('#ddd_celular').val() == ''){
					$('#ddd_celular,#celular').addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
				} else $('#ddd_celular,#celular').removeClass('erro').findNextMsg().slideUp('slow');
			} else {
				// estado != exterior?
				if ($('#estado').val() != 28) {
					if ($('#fixo').val().length < $('#fixo').attr('maxlength')-1 || $('#ddd_fixo').val() == ''){
						$('#ddd_fixo,#fixo').addClass('erro').findNextMsg().slideDown('fast');
						valido = false;
					} else $('#ddd_fixo,#fixo').removeClass('erro').findNextMsg().slideUp('slow');

					if ($('#celular').val() != '' && ($('#celular').val().length < $('#celular').attr('maxlength') || $('#ddd_celular').val() == '')){
						$('#ddd_celular,#celular').addClass('erro').findNextMsg().slideDown('fast');
						valido = false;
					} else $('#ddd_celular,#celular').removeClass('erro').findNextMsg().slideUp('slow');
				} else {
					if ($('#fixo').val() == '' || $('#ddd_fixo').val() == ''){
						$('#ddd_fixo,#fixo').addClass('erro').findNextMsg().slideDown('fast');
						valido = false;
					} else $('#ddd_fixo,#fixo').removeClass('erro').findNextMsg().slideUp('slow');
				}
			}
			
			if ($.cookie('user') == null) {
				if (!email_pattern.test($('#email1').val())) {
					$('#email1').addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
				} else $('#email1').removeClass('erro').findNextMsg().slideUp('slow');
				
				if ($('#email2').val() != $('#email1').val()) {
					$('#email2').addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
				} else $('#email2').removeClass('erro').findNextMsg().slideUp('slow');
				
				if ($('#senha1').val().length < 6) {
					$('#senha1').addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
				} else $('#senha1').removeClass('erro').findNextMsg().slideUp('slow');
				
				if ($('#senha2').val() != $('#senha1').val()) {
					$('#senha2').addClass('erro').findNextMsg().slideDown('fast');
					valido = false;
				} else $('#senha2').removeClass('erro').findNextMsg().slideUp('slow');
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
					url: formulario.attr('action') + '?action=' + (($.cookie('user') == null) ? 'add' : 'update') + '&' + $.serializeUrlVars(),
					data: formulario.serialize(),
					type: formulario.attr('method'),
					success: function(data) {
						if (data != 'true') {
							formulario.trigger('dados_salvos');
							if (data == 'Seus dados foram atualizados com sucesso!') {
								$this.next('.erro_help').find('.help').slideDown('fast').delay(3000).slideUp('slow');
							} else {
								$.dialog({text: data});
							}
						} else {
							$.ajax({
								url: 'autenticacao.php?&isRegister=true' + $.serializeUrlVars(),
								data: 'email=' + $('#email1').val() + '&senha=' + $('#senha1').val() + '&from=cadastro',
								type: 'POST',
								success: function(data) {
									document.location = data;
								}
							});
						}
					}
				});
			} else {
				$.dialog({text: 'Preencha os campos em vermelho' + (!$('#checkbox_politica')[0] || $('#checkbox_politica').is(':checked') ? '' : '<br>Para se cadastrar você deve estar de acordo com nossa política de privacidade')});
			}

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
		}

		if ($area.find(':input.erro').length > 0) {
			$area.addClass('erro')
		} else {
			$area.removeClass('erro')
		}
	});

	//evitar erro ao carregar modal do assinante A por causa de tempo de execução
	if (simples != undefined) {
		simples.getCEP($('#cep'));
	}
});