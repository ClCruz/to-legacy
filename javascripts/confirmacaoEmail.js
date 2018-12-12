$(function() {
	$('p.erro').hide();
	
	$('#confirmar').on('click', function(event) {
		event.preventDefault();
		var form = $('#confirmacaoForm');
		
		$.ajax({
			url: form.attr('action') + '?action=confirmar&' + $.serializeUrlVars(),
			data: form.serialize(),
			type: form.attr('method'),
			success: function(data) {
				if (data.substr(0, 4) == 'redi') {
					document.location = data;
				} else {
					$('#codigo').addClass('erro').findNextMsg().slideDown('fast');
				}
			}
		});
	});
	
	$('#reenviar').on('click', function(event) {
		event.preventDefault();
		var form = $('#confirmacaoForm');

		$.ajax({
			url: form.attr('action') + '?action=reenviar&' + $.serializeUrlVars(),
			data: form.serialize(),
			type: form.attr('method'),
			dataType: 'json',
			success: function(data) {
				$.confirmDialog({
					text: data.text,
					detail: data.detail,
					uiOptions: {
						buttons: {
							'Ok, entendi': ['', function(){
								fecharOverlay();
							}]
						}
					}
				});
			}
		});
	});

});