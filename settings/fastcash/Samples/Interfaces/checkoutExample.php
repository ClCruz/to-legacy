<!DOCTYPE html>
<html>
    <head>
        <title></title>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    </head>
    <body>
        <div>
            <form action="checkoutController.php" method="post">
                <fieldset>
					<legend>Checkout</legend>
					<!--Campos preenchidos automaticamente para identificar qual transação está sendo confirmada(pode estar hardcoded)-->
					<input Value="110" id="Pid" name="Pid" type="hidden"/>
					<input Value="3577" id="ProdId" name="ProdId" type="hidden" />
					<input Value="<?php echo date('Yhis') ?>" id="Tid" name="Tid" type="hidden" />

					<ul>
						<li>
							<label for="Custom">Custom</label>
							<input id="Custom" name="Custom" type="text" />
						</li>
						<li>
							<label for="Price">Price</label>
							<input id="Price" name="Price" type="text" />
						</li>
						<li>
							<label for="ItemDescription">ItemDescription</label>
							<input id="ItemDescription" name="ItemDescription" type="text" />
						</li>
						<li>
							<label for="PaymentMethod">PaymentMethod</label>
							<select id="PaymentMethod" name="PaymentMethod">
								<option selected="selected" value="1">Dinheiro</option>
								<option value="2">Transferência</option>
								<option value="3">Telefone</option>
							</select>
						</li>
						<li>
							<label for="SubPaymentMethod">PaymentSubMethod</label>
							<select id="SubPaymentMethod" name="SubPaymentMethod">
								<option selected="selected" value="1">Banco do Brasil</option>
								<option value="2">Bradesco</option>
								<option value="3">Caixa</option>
								<option value="4">Itau</option>
								<option value="5">Santander</option>
								<option value="6">Hsbc</option>
								<option value="7">Citibank</option>
								<option value="8">Banrisul</option>
								<option value="9">Lot&eacute;rica</option>
								<option value="10">Correios</option>
							</select>
						</li>
						<li>
							<label for="Name">Name</label>
							<input id="Name" name="Name" type="text" />
						</li>
						<li>
							<label for="Email">Email</label>
							<input id="Email" name="Email" type="text" />
						</li>
						<li>
							<label for="Cpf">Cpf</label>
							<input id="Cpf" name="Cpf" type="text" />
						</li>
						<li>
							<label for="MobilePhoneNumber">MobilePhoneNumber</label>
							<input id="MobilePhoneNumber" name="MobilePhoneNumber" type="text" />
						</li>
					</ul>
					<input type="submit" />
				</fieldset>
            </form>
        </div>
    </body>
</html>
