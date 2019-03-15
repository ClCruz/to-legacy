<?php
require_once('../settings/settings.php');
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
session_start();

header("Location: ".getwhitelabelURI_home(""));

?>
<style type="text/css">
	.dia div.erro_help p.help,
	.mes div.erro_help p.help,
	.ano div.erro_help p.help,
	.dia div.erro_help,
	.mes div.erro_help,
	.ano div.erro_help {
	    width: auto;
	}

	.dia {
	    width: 90px;
	}

	.mes {
	    width: 100px;
	}

	.ano {
	    width: 120px;
	}

	.dia, .mes, .ano {
	    margin-right: 10px;
	    float: left;
	}

	.sbHolder.sbHolderDisabled {
	    z-index: 0;
	}
</style>

<?php $exibir_msg_obrigatorio = isset($_SESSION['operador']); ?>

<div id="dados_conta" style="display:none">
	<form id="form_cadastro" name="form_cadastro" method="POST" action="cadastro.php">
		<?php if (isset($_GET['tag'])) { ?>
		<input type="hidden" name="tag" value="<?php echo $_GET['tag']; ?>" />
		<?php } ?>
		<div class="coluna coluna_tixs">

			<?php if (!(isset($_SESSION['user']) and is_numeric($_SESSION['user']))) { ?>
			<p class="frase">3.1 Dados pessoais</p>
			<?php } else { ?>
			<p class="frase">Seus dados</p>
			<?php } ?>

			<div class="input_area nome">
				<div class="icone"></div>
				<div class="inputs">
					<div class="form-group">
					<p class="titulo">Qual o seu nome?</p>
					<input type="text" class="form-control" name="nome" id="nome" maxlength="50" placeholder="nome/name/nombre<?php echo ($exibir_msg_obrigatorio ? ' (*)' : '')?>" pattern=".{1,50}" value="<?php echo utf8_encode2($rs['DS_NOME']); ?>">
					<div class="erro_help">
						<p class="erro">informe seu nome</p>
						<p class="help"></p>
					</div>
					</div>
					<div class="form-group">
					
					<input type="text" class="form-control" name="sobrenome" id="sobrenome" maxlength="50" placeholder="sobrenome/last name/apellido<?php echo ($exibir_msg_obrigatorio ? ' (*)' : '')?>" pattern=".{1,50}" value="<?php echo utf8_encode2($rs['DS_SOBRENOME']); ?>">
					<div class="erro_help">
						<p class="erro">informe seu sobrenome</p>
						<p class="help"></p>
					</div>
				</div>
				</div>
			</div>

			<div class="input_area sexo">
				<div class="icone"></div>
				<div class="inputs">
					
					<p class="titulo">Sexo</p>
					<input id="radio_masculino" type="radio" name="sexo" class="radio" value="M" <?php echo ($rs['IN_SEXO'] == 'M') ? 'checked ' : ''; ?>>
					<label class="radio" for="radio_masculino">masculino</label>
					<input id="radio_feminino" type="radio" name="sexo" class="radio" value="F" <?php echo ($rs['IN_SEXO'] == 'F') ? 'checked ' : ''; ?>>
					<label class="radio" for="radio_feminino">feminino</label>
				</div>
				<div class="erro_help">
					<p class="erro">informe seu sexo</p>
					<p class="help"></p>
				</div>
			</div>

			<div class="input_area nascimento">
				<div class="icone"></div>
				<div class="inputs">
					<p class="titulo">
						Data de nascimento
					</p>
					<div class="dia">
						<?php echo comboDia('nascimento_dia', $rs['DT_NASCIMENTO'][0], true); ?>
						<div class="erro_help">
							<p class="help">day / día</p>
						</div>
					</div>
					<div class="mes">
						<?php echo comboMeses('nascimento_mes', $rs['DT_NASCIMENTO'][1], false, true); ?>
						<div class="erro_help">
							<p class="help">month / mes</p>
						</div>
					</div>
					<div class="ano">
						<?php echo comboAnos('nascimento_ano', $rs['DT_NASCIMENTO'][2], date('Y')-100, date('Y'), true); ?>
						<div class="erro_help">
							<p class="help">year / año</p>
						</div>
					</div>
					<div class="erro_help">
						<p class="erro">informe a data de nascimento</p>
						<p class="help"></p>
					</div>
				</div>
			</div>

			<div class="input_area identificacao">
				<div class="icone"></div>
				<div class="inputs">
					<p class="titulo">Identificação</p>

					<input id="checkbox_estrangeiro" type="checkbox" name="checkbox_estrangeiro" class="checkbox" value="true" <?php echo $rs['ID_DOC_ESTRANGEIRO'] ? 'checked' : ''; ?>>
					<label class="checkbox" for="checkbox_estrangeiro">
						Não sou brasileiro e não tenho CPF<br/>
					</label>
				
					<span>
					<?php echo comboTipoDocumento('tipo_documento', $rs['ID_DOC_ESTRANGEIRO']); ?>
					</span>
					<div class="erro_help" style="height: 32px;">
						<p class="erro">
							select the document type<br/>
							seleccione el tipo de documento
						</p>
						<p class="help"></p>
					</div>
					<div class="form-group">

					<input  class="form-control" type="text" name="rg" id="rg" placeholder="R.G./Document/Documento" maxlength="11" pattern=".{1,11}" value="<?php echo utf8_encode2($rs['CD_RG']); ?>"/>
					<div class="erro_help" style="height: 32px;">
						<p class="erro">
							Type your document<br/>
							Escriba su documento
						</p>
						<p class="help"></p>
					</div>
					</div>
					<div class="form-group">
					<input class="form-control" type="text" name="cpf" id="cpf" placeholder="C.P.F<?php echo ($exibir_msg_obrigatorio ? ' (*)' : '')?>" maxlength="14" autocomplete="off" maxlength="11" pattern=".{14}" value="<?php echo utf8_encode2($rs['CD_CPF']); ?>">
					<div class="erro_help">
						<p class="erro">informe seu CPF</p>
						<p class="help"></p>
					</div>
					</div>
				</div>
			</div>

			<div class="input_area telefones">
				<div class="icone"></div>
				<div class="inputs">
				<div class="form-group">
					<p class="titulo">Telefones de contato</p>
					<input type="text" class="form-control" name="ddd_fixo" id="ddd_fixo" class="number" placeholder="ddd" maxlength="2" autocomplete="off" value="<?php echo $rs['DS_DDD_TELEFONE']; ?>"><input type="text" class="form-control" name="fixo" id="fixo" class="number" placeholder="fixo/phone number/teléfono" maxlength="9" autocomplete="off" value="<?php echo $rs['DS_TELEFONE']; ?>">
					<div class="erro_help">
						<p class="erro">insira o telefone fixo</p>
						<p class="help">(ddd + nº)</p>
					</div>
			</div>
			<div class="form-group">
					<input type="text" class="form-control" name="ddd_celular" id="ddd_celular" class="number" placeholder="ddd<?php echo ($exibir_msg_obrigatorio ? ' (*)' : '')?>" maxlength="2" autocomplete="off" value="<?php echo $rs['DS_DDD_CELULAR']; ?>"><input class="form-control" type="text" name="celular" id="celular" class="number" placeholder="celular/mobile number<?php echo ($exibir_msg_obrigatorio ? ' (*)' : '')?>" maxlength="9" autocomplete="off" value="<?php echo $rs['DS_CELULAR']; ?>">
					<div class="erro_help">
						<p class="erro"></p>
						<p class="help">opcional</p>
					</div>
			</div>
				</div>
			</div>

			<div class="input_area endereco">
				<div class="icone"></div>
				<div class="inputs">
					<p class="titulo">Endereço</p>
					<input type="text" class="form-control" name="cep" id="cep" class="number" placeholder="CEP/ZipCode" maxlength="8" autocomplete="off" value="<?php echo utf8_encode2($rs['CD_CEP']); ?>">
					<div class="erro_help">
						<p class="erro">informe seu CEP</p>
						<p class="help"><a href="http://www.buscacep.correios.com.br/" target="_blank">não sabe seu CEP?</a></p>
					</div>
					<span>
					<?php echo comboEstado('estado', $rs['ID_ESTADO'], true); ?>
					</span>
					<div class="erro_help">
						<p class="erro">selecione o estado</p>
						<p class="help"></p>
					</div>
					<input type="text" class="form-control" name="cidade" id="cidade" placeholder="cidade/city/ciudad" maxlength="50" pattern=".{1,50}" value="<?php echo utf8_encode2($rs['DS_CIDADE']); ?>">
					<div class="erro_help">
						<p class="erro">informe sua cidade</p>
						<p class="help"></p>
					</div>
					
					<input type="text" class="form-control" name="bairro" id="bairro" placeholder="bairro/district/barrio" maxlength="70" pattern=".{1,70}" value="<?php echo utf8_encode2($rs['DS_BAIRRO']); ?>">
					<div class="erro_help">
						<p class="erro">informe seu bairro</p>
						<p class="help"></p>
					</div>
					<input type="text" class="form-control" name="endereco" id="endereco" placeholder="logradouro/address/calle" maxlength="150" pattern=".{1,150}" value="<?php echo utf8_encode2($rs['DS_ENDERECO']); ?>">
					<div class="erro_help">
						<p class="erro">informe seu logradouro</p>
						<p class="help"></p>
					</div>
					<input type="text" class="form-control" name="numero_endereco" id="numero_endereco" placeholder="Número do Endereço/Address Number" maxlength="15" pattern=".{1,150}" value="<?php echo utf8_encode2($rs['NR_ENDERECO']); ?>">
					<div class="erro_help">
						<p class="erro">Número</p>
						<p class="help"></p>
					</div>
					<input type="text" class="form-control" name="complemento" id="complemento" placeholder="complemento/complement" maxlength="50" value="<?php echo utf8_encode2($rs['DS_COMPL_ENDERECO']); ?>">
					<div class="erro_help">
						<p class="erro"></p>
						<p class="help"></p>
					</div>
				</div>
			</div>
		</div>
		<div class="coluna coluna_tixs">

			<?php if (!(isset($_SESSION['user']) and is_numeric($_SESSION['user']))) { ?>
			<p class="frase">3.2 Dados da conta</p>
			<?php } else { ?>
			<p class="frase">Dados de acesso</p>
			<?php } ?>

			<?php if (!(isset($_SESSION['user']) and is_numeric($_SESSION['user'])) or preg_match('/etapa/', basename($_SERVER['SCRIPT_FILENAME']))) { ?>
			<div class="input_area login">
				<div class="icone"></div>
				<div class="inputs">
					<p class="titulo">Login</p>
					<input type="text"class="form-control" name="email1" id="email1" pattern=".{1,200}" placeholder="digite seu e-mail<?php echo ($exibir_msg_obrigatorio ? ' (*)' : '')?>">
					<div class="erro_help">
						<p class="erro">informe seu e-mail</p>
						<p class="help"></p>
					</div>
					<input type="text"class="form-control" name="email2" id="email2" pattern=".{1,200}" placeholder="confirme seu e-mail<?php echo ($exibir_msg_obrigatorio ? ' (*)' : '')?>">
					<div class="erro_help">
						<p class="erro">confirmação de e-mail não confere</p>
						<p class="help"></p>
					</div>
					<?php if (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) { ?>
					<input type="password"class="form-control" name="senha1" id="senha1" pattern=".{1,200}" placeholder="digite sua senha">
					<div class="erro_help">
						<p class="help senha">mínimo 6 caracteres com letras e números</p>
					</div>
					<input type="password"class="form-control" name="senha2" id="senha2" pattern=".{1,200}" placeholder="confirme sua senha">
					<div class="erro_help">
						<p class="erro">confirmação de senha não confere</p>
						<p class="help"></p>
					</div>
					<?php } ?>
				</div>
			</div>
			<?php } else { ?>
			<div class="input_area login">
				<div class="icone"></div>
				<div class="inputs">
					<p class="titulo">Login</p>
					<input type="text" class="form-control"name="email" id="email" value="<?php echo utf8_encode2($rs['CD_EMAIL_LOGIN']); ?>" <?php echo (!(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) ? 'disabled' : ''; ?>/>
					<div class="erro_help">
						<p class="erro"></p>
						<p class="help"></p>
					</div>
				</div>
			</div>
			<?php } ?>

			<?php if (isset($_SESSION['user']) and is_numeric($_SESSION['user'])) { ?>
			<p class="frase">Guia de Espetáculos</p>
			<?php } ?>
			<div class="input_area guia_sms">
				<input id="checkbox_guia" type="checkbox" name="extra_info" class="checkbox" value="S" <?php echo ($rs['IN_RECEBE_INFO'] == 'S' or (!(isset($_SESSION['user']) and is_numeric($_SESSION['user'])))) ? 'checked' : ''; ?>>
				<label class="checkbox" for="checkbox_guia">quero receber o guia de espetáculos, com atrações específicas para a minha localidade</label>
				<input id="checkbox_sms" type="checkbox" name="extra_sms" class="checkbox" value="S" <?php echo ($rs['IN_RECEBE_SMS'] == 'S') ? 'checked' : ''; ?>>
				<label class="checkbox" for="checkbox_sms" id="label_sms">autorizo o envio de mensagens SMS</label>
				<?php
					$url = explode("/", $_SERVER['PHP_SELF']);
				?>	
				<input id="checkbox_politica" type="checkbox" name="concordo" class="checkbox" value="S" <?php echo (($url[2] != "login.php") and isset($_SESSION['user']) and is_numeric($_SESSION['user'])) ? ' checked disabled' : ''; ?>>
				<label class="checkbox" for="checkbox_politica" id="label_politica">
					concordo com os <a href="" target="_blank" class="termos_de_uso">termos de uso</a>, a 
					<a href="" target="_blank" class="politica_de_privacidade">política de privacidade</a> e a <a href="<?php echo multiSite_getURI("URI_SSL", "politica"); ?>" target="_blank" class="politica_de_venda">política de venda</a>
				</label>
			</div>

			<?php if (!(isset($_SESSION['user']) and is_numeric($_SESSION['user'])) and !(isset($_SESSION['operador']) and is_numeric($_SESSION['operador']))) { ?>

			<?php } ?>

			<?php if ($exibir_msg_obrigatorio) { ?>
			<div class="input_area">
				<div class="icone"></div>
				<div class="inputs">
					<p>(*) Campos obrigatórios</p>
				</div>
			</div>
			<?php } ?>
			
			<input type="button" class="submit salvar_dados" value="Cadastrar" style="margin-top: 36px">
			<div class="erro_help">
				<p class="erro"></p>
				<p class="help senha hidden">Seus dados foram atualizados com sucesso!</p>
			</div>
		</div>
	</form>
</div>