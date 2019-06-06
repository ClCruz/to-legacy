<?php
require_once('../settings/functions.php');
require_once('../settings/settings.php');
session_start();
$mainConnection = mainConnection();

$query = 'EXEC dbo.pr_purchase_summary ?';
$params = array(session_id());
$result = executeSQL($mainConnection, $query, $params);
$evento_info = null;
$total = "";

while ($rs = fetchResult($result)) {
	if ($evento_info == null) {
		$evento_info = getEvento($rs['id_evento']);
		$total = $rs["totalWithService_formatted"];
		?>
		<div class="resumo_espetaculo" data-evento="<?php echo $evento_info['ID_EVENTO']; ?>">
			<div class="resumo">
				<p class="endereco title__resumo" style="">
						<?php echo utf8_encode2($evento_info['DS_EVENTO']); ?>
				</p>
				<p class="endereco">
							<span style="text-transform: capitalize">
							<img class="endereco__icon" src="../images/icons/calendar.svg" alt="">
								<?php echo getDateToString($tempo,"week-small"); ?> <?php echo strftime("%d", $tempo); ?>/<?php echo getDateToString($tempo,"month-small"); ?> - <?php echo $rs['HR_APRESENTACAO']; ?> 
								<br /> 
							</span>
							<span style="text-transform: capitalize">
								<span style="margin-top: 10px"></span>
							<img class="endereco__icon" src="../images/icons/map-pin-white.svg" alt="">
								<?php echo utf8_encode2($evento_info['nome_teatro'] . ' - ' . $evento_info['cidade'] . ', ') . utf8_encode2($evento_info['sigla_estado']); ?>
								<br />
							</span>
							<span>
								<?php if ($evento_info["show_partner_info"] == 1) {
											?>
											<img class="endereco__icon" src="../images/icons/handshake-regular.svg" alt="">
											<?php echo "Vendido e entregue por ".utf8_encode2($evento_info['name_site']); ?>
											<br />
											<?php
										}?>
							</span>
				</p>
			</div>
			<button type="button" class="btn btn-primary botao btn__help" data-toggle="modal" data-target="#sideModalTR"></button>
		</div>
		<div class="row centraliza" style="padding-right:20px;">
			<table id="pedido_resumo" style="font-size:10px !important;">
				<thead>
					<tr>
						<td width="90"></td>
						<td width="208">Tipo</td>
						<td width="130">Preço</td>
						<td width="130">Serviço</td>
						<td width="118">Total</td>
					</tr>
				</thead>
				<tbody>
		<?php
	}
		// </tbody></table></div>
?>


					<tr>
						<td>
							<div class="local" style="text-align: center">
								<?php echo utf8_encode2($rs['NomSetor']); ?><br>
								<?php echo $rs['NomObjeto']; ?>

							</div>
						</td>
						<td>
							<?php echo $rs['TipBilhete']; ?>
						</td>
						<td>R$ <span class="valorIngresso"><?php echo $rs["amount_formatted"] ?></span></td>
						<td class="colunaServico">R$ <span class="valorConveniencia"><?php echo $rs["amount_service_formatted"] ?></span></td>
						<td class="total">R$ <span class="valorTotalLinha"><?php echo $rs["amount_total_formatted"] ?></span></td>
					</tr>

		
<?php
}
?>
<tr>
	<td></td>
	<td></td>
	<td></td>
	<td></td>
	<td class="total"><span class="valorTotalLinha" style="font-weight: bold">R$ <?php echo $total ?></span></td>
</tr>
</tbody></table></div>