<form name="pedido" id="pedido" method="post" action="atualizarPedido.php?action=update">
<input type="hidden" name="trigger" value="" />
<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
session_start();

$mainConnection = mainConnection();
$query = 'SELECT R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, R.ID_CADEIRA, R.DS_CADEIRA, R.DS_SETOR, E.ID_EVENTO, E.DS_EVENTO, B.DS_NOME_TEATRO, A.DT_APRESENTACAO, A.HR_APRESENTACAO, E.IN_ENTREGA_INGRESSO, R.ID_RESERVA, R.CD_BINITAU, R.NR_BENEFICIO
				FROM MW_RESERVA R
				INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO AND A.IN_ATIVO = \'1\'
				INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = \'1\'
				INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE AND B.IN_ATIVO = \'1\'
				WHERE R.ID_SESSION = ? AND R.DT_VALIDADE >= GETDATE()
				ORDER BY DS_EVENTO, ID_APRESENTACAO, DS_CADEIRA';
$params = array(session_id());
$result = executeSQL($mainConnection, $query, $params);

$eventoAtual = NULL;
$qtdIngressos = 0;
$_SESSION["dataEvento"] = "";
$habilitar_entrega = false;
while ($rs = fetchResult($result)) {
	
	$removeUrl = 'apresentacao='.$rs['ID_APRESENTACAO'].'&'.'id='.$rs['ID_CADEIRA'];
	$hora = explode('h', $rs['HR_APRESENTACAO']);
	$data = explode('/', $rs['DT_APRESENTACAO']->format('d/m/Y'));
	$tempo = mktime($hora[0], $hora[1], 0, $data[1], $data[0], $data[2]);

	$beneficio_size = $rs['NR_BENEFICIO'] ? '12' : '6';
	$beneficio_texto = $rs['NR_BENEFICIO'] ? 'número cartão/matrícula SESC' : $beneficio_size . ' primeiros números do seu cartão';

	if($_SESSION["dataEvento"] == "" || $tempo < $_SESSION["dataEvento"]) {
		$_SESSION["dataEvento"] = $tempo;
	}
	if ($rs['IN_ENTREGA_INGRESSO'] == 1) {
	    $habilitar_entrega = true;
	}
	
	if ($eventoAtual != $rs['ID_EVENTO'] . $rs['ID_APRESENTACAO']) {

		$evento_info = getEvento($rs['ID_EVENTO']);
  		$is_pacote = is_pacote($rs['ID_APRESENTACAO']);
		
		$valorConveniencia = '0,00';
		
		if ($eventoAtual != NULL) echo "</tbody></table></div>";
?>
		<div class="resumo_espetaculo" data-evento="<?php echo $rs['ID_EVENTO']; ?>">
      <div class="resumo">
				<p class="endereco title__resumo" style="">
						<?php echo utf8_encode2($rs['DS_EVENTO']); ?>
				</p>
        <p class="endereco endereco__resumo" style="">
                <?php echo utf8_encode2(strftime("%a", $tempo)); ?> <?php echo strftime("%d", $tempo); ?>/<?php echo strftime("%b", $tempo); ?> - <?php echo $rs['HR_APRESENTACAO']; ?> - <?php echo utf8_encode2($evento_info['nome_teatro']); ?> <?php echo utf8_encode2($evento_info['endereco'] . ' - ' . $evento_info['cidade'] . ', ' . $evento_info['sigla_estado']); ?>
        </p>
			</div>
			<button type="button" class="btn btn-primary botao btn__help" data-toggle="modal" data-target="#sideModalTR"></button>
		

		</div>

	</div>
	<div class="row container_pedido ">
	<table id="pedido_resumo">
		<thead>
			<tr>
				<td width="90"></td>
				<td width="208">Tipo de ingresso</td>
				<td width="130">Preço</td>
				<td width="130">Serviço</td>
				<td width="118">Total</td>
				<td width="32"></td>
			</tr>
		</thead>
		<tbody>
<?php
		$eventoAtual = $rs['ID_EVENTO'] . $rs['ID_APRESENTACAO'];
	}
?>
			<tr>
				<td>
					<div class="local">
						<table>
							<tbody><tr>
								<td>
									<?php echo utf8_encode2($rs['DS_SETOR']); ?><br>
									<?php echo $rs['DS_CADEIRA']; ?>

									<input type="hidden" name="apresentacao[]" value="<?php echo $rs['ID_APRESENTACAO']; ?>" />
									<input type="hidden" name="cadeira[]" value="<?php echo $rs['ID_CADEIRA']; ?>" />
									<input type="hidden" name="reserva[]" value="<?php echo $rs['ID_RESERVA']; ?>" />
								</td>
							</tr>
						</tbody></table>
					</div>
				</td>
				<td class="tipo">
					<?php
						if ($edicao) {
							echo comboPrecosIngresso('valorIngresso[]', $rs['ID_APRESENTACAO'], $rs['ID_CADEIRA'], $rs['ID_APRESENTACAO_BILHETE']);
						} else {
							echo comboPrecosIngresso('valorIngresso[]', $rs['ID_APRESENTACAO'], $rs['ID_CADEIRA'], $rs['ID_APRESENTACAO_BILHETE'], false);
						}
					?>
				</td>
				<td>R$ <span class="valorIngresso"></span></td>
				<td class="colunaServico">R$ <span class="valorConveniencia"><?php echo $valorConveniencia; ?></span></td>
				<td class="total">R$ <span class="valorTotalLinha"></span></td>
				<td class="remover">
					<?php if ($edicao) { ?>
					<a href="atualizarPedido.php?action=delete&<?php echo $removeUrl; ?>" class="remover removerIngresso"></a>
					<?php } ?>
				</td>
			</tr>
			<tr class="beneficio hidden">
				<td></td>
				<td colspan="5">
					<div class="container_beneficio">
						<div class="img_complemento">
							<img src="">
						</div>
						<div class="ajuda">
							<span class="frase1 <?php echo ($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'hidden' : 'notHidden'; ?>">valide o benefício</span>
							<span class="frase2 <?php echo ($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'hidden' : 'notHidden'; ?>">insira o número e clique validar</span>
							<span class="frase3 <?php echo !($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'hidden' : 'notHidden'; ?>">benefício válido</span>
						</div>
						<div class="icone_validador <?php echo ($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'valido' : ''; ?>"></div>
						<div class="container_validador">
							<input type="text" name="bin[]" class="validador_itau form-control  <?php echo ($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'hidden' : 'notHidden'; ?>" placeholder="<?php echo $beneficio_texto; ?>" maxlength="<?php echo $beneficio_size; ?>" value="<?php echo $rs['CD_BINITAU'].$rs['NR_BENEFICIO']; ?>" <?php echo ($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'readonly' : ''; ?>>
							<input type="hidden" name="tipoBin[]" value="<?php echo $rs['CD_BINITAU'] ? 'itau' : 'promocao'; ?>" />
							<a class="validarBin <?php echo ($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'hidden' : 'notHidden'; ?>" href="#">validar</a>
							<img class="<?php echo !($rs['CD_BINITAU'] || $rs['NR_BENEFICIO']) ? 'hidden' : 'notHidden'; ?>" src="">
						</div>
					</div>
				</td>
			</tr>
<?php
	$qtdIngressos++;
}

if (hasRows($result)) finalizar($qtdIngressos, $totalIngressos, $rs['IN_RETIRA_ENTREGA'], $rs['VL_FRETE'], $habilitar_entrega, $edicao);

function finalizar($qtdIngressos, $totalIngressos, $formaEntrega, $valorEntrega, $habilitar_entrega, $edicao) {
?>
		</tbody>
	</table>
	<table id="servico_por_pedido" class="hidden">
		<tbody>
			<tr>
				<td width="90"></td>
				<td width="208"></td>
				<td width="130"></td>
				<td width="130" class="colunaServico">R$ <span id="servico_pedido"><?php echo $valorConveniencia; ?></span></td>
				<td width="118"></td>
				<td width="32"></td>
			</tr>
		</tbody>
	</table>
	<div class="pedido_entrega">
		<br>
		<div class="descricao">Forma de entrega</div>
		<div class="tipo">
			<select id="cmb_entrega" <?php echo $edicao ? '' : 'disabled'; ?>>
				<option value="retirada">e-ticket</option>
				<?php if ($habilitar_entrega) { ?>
				<option value="entrega" <?php echo $_COOKIE['entrega'] ? 'selected' : ''; ?>>no endere&ccedil;o</option>
				<?php } ?>
			</select>
        </div>
		<div class="valor" id="frete"><?php echo ($valorEntrega == 0 or $valorEntrega == null) ? "" : number_format($valorEntrega, 2, ',', ''); ?></div>
	</div>
	<?php
	if ($_COOKIE['entrega'] and !$edicao) {
		$endereco = getEnderecoCliente($_SESSION['user'], $_COOKIE['entrega']);
	?>
		<div class="container_endereco">
			<input type="hidden" name="entrega" id="entrega" value="<?php echo $_COOKIE['entrega']; ?>" />
			<script>
			$(function(){
				$.ajax({
					url: 'calculaFrete.php',
					data: 'id=' + $('#entrega').val(),
					success: function(data) {
						$('#frete').html('<span>R$</span> ' + data);
						calculaTotal();
					}
				});
			});
			</script>
			<p class="titulo"><?php echo $endereco['nome']; ?></p>
			<p class="endereco">
				<?php echo $endereco['endereco']; ?><?php echo $endereco['complemento'] ? ' - '.$endereco['complemento'] : ''; ?><br>
				<?php echo $endereco['bairro']; ?>, <?php echo $endereco['cidade']; ?> - <?php echo comboEstado('estado', $endereco['estado'], false, false); ?><br>
				<?php echo substr($endereco['cep'], 0, 5).'-'.substr($endereco['cep'], -3); ?>
			</p>
	    </div>
	<?php
	} else {
	?>
		<div class="selecione_estado hidden">
			<div class="descricao">seu estado</div>
			<div class="estado">
				<?php echo comboEstado('estado', $_COOKIE['entrega'], false, true, '', true); ?>
			</div>
		</div>
	<?php
	}
	?>
	<span id="servico_pedido" class="hidden">0</span>
	<p class="pedido_total"><b><span class="totalIngressosApresentacao"><?php echo $qtdIngressos; ?></span> ingresso(s)</b> para esta apresentação <span class="total">total:</span><span class="cifrao">R$</span><span class="valor" id="totalIngressos"><?php echo number_format($totalIngressos, 2, ',', ''); ?></span></p>
</div>
</div>
</div>
<?php
}
?>
</form>