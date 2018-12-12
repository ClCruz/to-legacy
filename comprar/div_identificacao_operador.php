<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
?>
<span id="identificacao" style="display: inline-block">
	<form id="identificacaoForm" name="identificacao" method="post" action="busca.php">
		<div class="identificacao">
			<p class="frase"><b>Já sou</b> cliente</p>
			<p class="site"><?php echo multiSite_getName(); ?></p>
			<input name="nomeBusca" type="text" id="nomeBusca" size="30" maxlength="50" placeholder="Nome"/>
			<input name="sobrenomeBusca" type="text" id="sobrenomeBusca" size="30" maxlength="50" placeholder="Sobrenome"/>
			<input name="telefoneBusca" type="text" id="telefoneBusca" size="15" maxlength="15" placeholder="Telefone"/>
			<input name="cpfBusca" type="text" id="cpfBusca" size="15" maxlength="11" placeholder="CPF"/>
			<a id="buscar" href="etapa4.php">buscar</a>
			<a id="limpar" href="#">limpar</a>
		</div>
		<div class="identificacao">
			<p class="frase"><b>Não sou</b> cliente</p>
			<p class="site"><?php echo multiSite_getName(); ?></p>
			<a href="" class="botao cadastrar bt_cadastro">cadastrar</a>
		</div>
	</form>
</span>