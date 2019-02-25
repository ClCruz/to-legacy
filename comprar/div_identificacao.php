<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");

	//printr($_SERVER);
	function is_etapa1(){
		$url = $_SERVER['URL'];
		$url = explode('/',$url);
		$last = end($url);

		return ( $last == 'etapa1.php' ) ? true : false;
	}

	if ( is_etapa1() ) {
		$titulo = 'Assinante';
		$assinante = true;
	}else{
		$titulo = 'Cliente';
		$assinante = false;
	}
?>

<span id="identificacao">
	<form id="identificacaoForm" name="identificacao" method="post" action="autenticacao.php">
		<?php if (isset($_GET["tag"])) { ?>
		<input type="hidden" name="tag" value="<?php echo $_GET["tag"]; ?>" />
		<input type="hidden" name="from" value="cadastro" />
		<?php } ?>
		<div class="identificacao cliente">
			<p class="frase"><b>Já sou</b> <?php echo $titulo ?></p>
			<?php if ( $assinante ): ?>
				<div>
					Se você já é cadastrado na <?php echo multiSite_getName(); ?> ou já possui os benefícios do clube Assinante A ,faça o seu login.
				</div>
			<?php endif; ?>
			<p class="site"><?php echo multiSite_getName(); ?></p>
			<div id="loginForm">
				<div class="form-group">
				<label for="login">Insira seu e-mail</label>
				<input type="text" class="form-control" name="email" placeholder="digite seu e-mail" id="login" maxlength="100">
				<div class="erro_help">
					<p class="erro">insira seu e-mail</p>
					<p class="help"></p>
				</div>
				</div>
				<div class="form-group">
				<label for="senha">Insira sua senha</label>
				<input type="password" class="form-control" name="senha" placeholder="digite sua senha" id="senha" maxlength="30">
				<div class="erro_help">
					<p class="erro">no mínimo 6 caracteres</p>
					<p class="help"></p>
				</div>
				</div>
				<br>
				
				<input type="button" class="submit avancar passo4" id="logar" href="etapa4.php" value="Acessar"></input>
				<a id="esqueci" href="#esqueci" class="esqueci_senha">Esqueci minha senha</a>
				<div class="erro_help">
					<p class="erro" style="width:200px">Combinação de E-mail/senha inválida<br>Por favor tente novamente.</p>
					<p class="help"></p>
				</div>
			</div>
			<div id="esqueciForm" style="display: none" class="container_esqueci_senha">
				<div class="form-group">
				<label for="recupera_por_email">Seu e-mail</label>
				<input type="text" class="form-control" name="email_esqueci_senha" placeholder="digite seu e-mail cadastrado" id="recupera_por_email" maxlength="100">
				<div class="erro_help">
					<p class="erro">e-mail inválido</p>
					<p class="help"></p>
				</div>
				</div>
				<input type="button" class="submit trocar_senha" id="enviar_senha" style="padding: 0" value="Recuperar senha" href="esqueciSenha.php">
				<?php if ( $assinante ): ?>
				<a id="lembrei_senha" href="#">Voltar para Login</a>
				<?php endif; ?>
				<br>
				<div class="nome" style="float: left; margin-top: 30px">
					Um email com instruções para recuperar<br>
					sua senha será enviado para o seu email.
					Se não encontrar o e-mail verifique sua caixa de spam
				</div>
			</div>
		</div>
		<div class="identificacao cadastro">
			<p class="frase"><b>Não sou</b> cliente</p>
			<p class="site"><?php echo multiSite_getName(); ?></p>
			<a href="" class="botao cadastrar bt_cadastro">cadastrar</a>
		</div>
	</form>
</span>