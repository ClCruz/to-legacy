<?php
	$paymentData = (object)array(
		'FieldCount' => 4,
		'PaymentMethod' => 1,
		'PaymentMethodOption' => 1,
		'F1Name' => 'Campo 1',
		'F1DataType' => 'text',
		'F2Name' => 'Campo 2',
		'F2DataType' => 'text',
		'F3Name' => 'Campo 3',
		'F3DataType' => 'text',
		'F4Name' => 'Campo 4',
		'F4DataType' => 'text'
	);
?>
<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <div>
            <form action="confirmationController.php" method="post">
                <fieldset>
					<legend>Confirmation</legend>
					<!--Campos preenchidos automaticamente para identificar qual transação está sendo confirmada(pode estar hardcoded)-->
					<input Value="110" id="Pid" name="Pid" type="hidden"/>
					<input Value="3577" id="ProdId" name="ProdId" type="hidden" />
					<input Value="5454544" id="Tid" name="Tid" type="hidden" />

					<ul>
						<li>
							<!--Data de pagamento (pode conter a data atual por padrão gerada dinamicamente)-->
							<label for="PaidDate">PaidDate</label>
							<input Value="05/22/2013 14:16:53" id="PaidDate" name="PaidDate" type="text" />
						</li>
						<!--Os fields de 1 a 4 devem ser exibidos de acordo com o fieldcount retornado no momento de gerar a compra-->
						<li>
							<label for="Value">Value</label>
							<input id="Value" name="Value" type="text" />
						</li>
						<?php
						if ($paymentData->FieldCount >= 1){
						?>
						<li>
							<label for="F1"><?php echo $paymentData->F1Name ?></label>
							<input id="F1" name="F1" type="text" />
						</li>
						<?php
						}
						if ($paymentData->FieldCount >= 2){
						?>
						<li>
							<label for="F1"><?php echo $paymentData->F2Name ?></label>
							<input id="F2" name="F2" type="text" />
						</li>
						<?php
						}
						if ($paymentData->FieldCount >= 3){
						?>
						<li>
							<label for="F1"><?php echo $paymentData->F3Name ?></label>
							<input id="F3" name="F3" type="text" />
						</li>
						<?php
						}
						if ($paymentData->FieldCount >= 4){
						?>
						<li>
							<label for="F1"><?php echo $paymentData->F4Name ?></label>
							<input id="F4" name="F4" type="text" />
						</li>
						<?php
						}
						?>
						<li>
							<!--Observações (preenchimento opcional)-->
							<label for="Observations">Observations</label>
						</li>
						<li>
							<textarea cols="20" id="Observations" name="Observations" rows="2"></textarea>
						</li>
					</ul>
					<input type="submit" />
				</fieldset>
            </form>
			<?php
				$path = "Resources/".$paymentData->PaymentMethod."/CS".$paymentData->PaymentMethod.$paymentData->PaymentMethodOption."1.jpg";
			?>
			<img src="<?php echo $path?>" />
        </div>
    </body>
</html>
