<?php
require 'logado.php';

if ($_SESSION['mensagens']) {
?>

<div id="mensagens" class="ui-helper-hidden" title="Avisos...">
	<p>
		[28/03/2011]<br/>
		Aten&ccedil;&atilde;o operador de bilheteria, quanto mais cart&otilde;es voc&ecirc; cadastrar,
		mais chances voc&ecirc; tem de ganhar pr&ecirc;mios semanais.
		clique <a href="#" target="_blank">aqui</a> e veja a promo&ccedil;&atilde;o da semana.
	</p>
</div>
<script>
$(function(){
	$('#mensagens').dialog({
		modal: true,
		resizable: false,
		width: 500,
		buttons: {
			Ok: function() {
				$(this).dialog("close");
			}
		}
	});
});
</script>
<?php
	unset($_SESSION['mensagens']);
}
?>