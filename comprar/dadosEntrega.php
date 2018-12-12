<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
session_start();

$mainConnection = isset($mainConnection) ? $mainConnection : mainConnection();

$enderecoMain = getEnderecoCliente($_SESSION['user'], -1);
?>
<div class="container_enderecos">
	<?php if (!empty($enderecoMain)): ?>
	<table class="select_endereco">
		<tbody>
			<tr>
				<td class="input">
					<input id="radio_endereco_1" type="radio" name="radio_endereco" class="radio" value="-1" <?php echo ($_COOKIE['entrega'] == -1 ? 'checked' : ''); ?>>
					<label class="radio" for="radio_endereco_1"></label>
				</td>
				<td>
					<div class="container_endereco">
						<p class="titulo"><?php echo $enderecoMain['nome']; ?></p>
						<p class="endereco">
							<?php echo $enderecoMain['endereco']; ?>, <?php echo $enderecoMain['numero'] ?><?php echo $enderecoMain['complemento'] ? ' - '.$enderecoMain['complemento'] : ''; ?><br>
							<?php echo $enderecoMain['bairro']; ?>, <?php echo $enderecoMain['cidade']; ?> - <?php echo comboEstado('estado', $enderecoMain['estado'], false, false); ?><br>
							<?php echo $enderecoMain['estado'] != 28 ? substr($enderecoMain['cep'], 0, 5).'-'.substr($enderecoMain['cep'], -3) : $enderecoMain['cep']; ?>
						</p>
					</div>
				</td>
			</tr>
			<tr>
				<td class="input"></td>
				<td>&nbsp;</td>
			</tr>
		</tbody>
	</table>
	<?php endif; ?>
<?php
$query = 'SELECT ID_ENDERECO_CLIENTE, DS_ENDERECO, DS_COMPL_ENDERECO, DS_BAIRRO, DS_CIDADE, CD_CEP, ID_ESTADO, NM_ENDERECO, NR_ENDERECO
				FROM MW_ENDERECO_CLIENTE
				WHERE ID_CLIENTE = ?';
$params = array($_SESSION['user']);
$result = executeSQL($mainConnection, $query, $params);

while ($rs = fetchResult($result)) {
?>
	<table class="select_endereco">
		<tbody>
			<tr>
				<td class="input">
					<input id="radio_endereco_<?php echo $rs['ID_ENDERECO_CLIENTE']; ?>" type="radio" name="radio_endereco" class="radio" value="<?php echo $rs['ID_ENDERECO_CLIENTE']; ?>" <?php echo $_COOKIE['entrega'] == $rs['ID_ENDERECO_CLIENTE'] ? 'checked' : ''; ?>>
					<label class="radio" for="radio_endereco_<?php echo $rs['ID_ENDERECO_CLIENTE']; ?>"></label>
				</td>
				<td>
					<div class="container_endereco">
					<p class="titulo"><?php echo utf8_encode2($rs['NM_ENDERECO']); ?></p>
						<p class="endereco"> 
							<?php echo utf8_encode2($rs['DS_ENDERECO']).', '.$rs['NR_ENDERECO']; ?><?php echo $rs['DS_COMPL_ENDERECO'] ? ' - '.$rs['DS_COMPL_ENDERECO'] : ''; ?><br>
							<?php echo utf8_encode2($rs['DS_BAIRRO']); ?>, <?php echo utf8_encode2($rs['DS_CIDADE']); ?> - <?php echo comboEstado('estado', $rs['ID_ESTADO'], false, false); ?><br>
							<?php echo $rs['ID_ESTADO'] != 28 ? substr($rs['CD_CEP'], 0, 5).'-'.substr($rs['CD_CEP'], -3) : $rs['CD_CEP']; ?>
						</p>
					</div>
				</td>
			</tr>
		<tr>
			<td class="input"></td>
			<td>
				<a href="cadastro.php?action=manageAddresses&id=<?php echo $rs['ID_ENDERECO_CLIENTE']; ?>" class="end_apagar">apagar</a>
				<a href="cadastro.php?action=getAddresses&id=<?php echo $rs['ID_ENDERECO_CLIENTE']; ?>" class="end_editar">editar</a>
			</td>
		</tr>
		</tbody>
	</table>
<?php } ?>
</div>
	<?php if ( !empty($enderecoMain) ): ?>
		<a href="#" class="add_endereco" id="bt_novo_endereco">Cadastrar um novo endereço</a>
	<?php else: ?>
		<a style="margin-left: 120px;" href="#" onclick="gotoMainAddress()">Cadastre um endereço principal</a>
	<?php endif; ?>
<div class="coluna coluna_endereco hidden">
	<input type="hidden" id="id" value="">
	<div class="input_area endereco">
		<div class="icone"></div>
		<div class="inputs">
			<p class="titulo">Novo Endereço</p>
			<input type="text" class="form-control" name="titulo_endereco" placeholder="título do endereço" id="novo_titulo_endereco">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help">ex. casa, trabalho...</p>
			</div>
			<input type="text"  class="form-control" name="cep" placeholder="CEP" maxlength="8" autocomplete="off" id="novo_cep">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help"><a href="http://www.buscacep.correios.com.br/" target="_blank">não sabe seu CEP?</a></p>
			</div>
			<?php echo comboEstado('novo_estado', '', true); ?>
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help"></p>
			</div>
			<input type="text"  class="form-control" name="cidade" placeholder="cidade" id="novo_cidade">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help"></p>
			</div>
			<input type="text"  class="form-control" name="bairro" placeholder="bairro" id="novo_bairro">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help"></p>
			</div>
			<input type="text"  class="form-control" name="logradouro" placeholder="rua, avenida, praça..." id="novo_endereco">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help"></p>
			</div>
			<input type="text"  class="form-control" name="numero_endereco" placeholder="Número do Endereço" id="novo_numero_endereco">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help"></p>
			</div>
			<input type="text"  class="form-control" name="complemento" placeholder="complemento" id="novo_complemento">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help"></p>
			</div>
		</div>
	</div>
	<a href="#" class="end_cancelar">cancelar</a>
	<a href="#" class="end_salvar">salvar o endereço acima</a>
</div>