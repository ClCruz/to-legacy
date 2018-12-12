<?php
session_start();
require_once("../settings/multisite/tellmethesite.php");
require_once("../settings/multisite/layout.php");
$pdo = getConnectionHome();

if ($pdo !== false) {

	$query_mysql = "SELECT count(t.id) as total, c.id, c.nome
					FROM cidades as c, teatros as t, espetaculos as e
					WHERE t.cidade_id = c.id AND e.teatro_id = t.id AND e.ativo = 1
					GROUP BY t.cidade_id
					ORDER BY total DESC";

	$stmt = $pdo->prepare($query_mysql);
	$stmt->execute();

	$dados_cidade = $stmt->fetchAll();

	$query_mysql = "SELECT count(1) as total, g.nome
					FROM espetaculos as e, generos as g
					WHERE e.ativo = 1 AND e.genero_id = g.id
					GROUP BY g.id
					ORDER BY total DESC";

	$stmt = $pdo->prepare($query_mysql);
	$stmt->execute();

	$dados_genero = $stmt->fetchAll();

}

$rows = numRows($mainConnection, "SELECT 1 FROM MW_RESERVA WHERE ID_SESSION = ?", array(session_id()));

//$homeConn = getConnectionHome();
//$query = 'SELECT id, link FROM publicidades WHERE ( CURDATE() BETWEEN data_inicio AND data_fim) AND status = true ORDER BY RAND() LIMIT 1';
//$exe = $homeConn->prepare($query);
//$banner = $exe->execute();
//$banner = $exe->fetch();

?>

<link href="https://fonts.googleapis.com/css?family=Gothic+A1" rel="stylesheet">

<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/css/bootstrap.min.css" integrity="sha384-Gn5384xqQ1aoWXA+058RXPxPg6fy4IWvTNh0E263XmFcJlSAwiGgFAW/dAiS6JXm" crossorigin="anonymous">

<style> 

.alert {
	display: none;
}

a.botao {
	background-image: none !important;
	background-position: none !important;
}

.erro_help p.erro {
	display: none;
}

</style>

<link rel="stylesheet" type="text/css" href="../stylesheets/nova_home.css">
<link rel="stylesheet" type="text/css" href="../stylesheets/icons/socicon/styles.css">
<link rel="stylesheet" type="text/css" href="../stylesheets/icons/flaticon1/flaticon.css">
<?php require("desktopMobileVersion.php"); ?>

<link rel="stylesheet" type="text/css" href="<?php echo getwhitelabel("css") ?>">
	<?php if (isset($_SESSION['operador']) and $rows == 0) { ?>
			<div id="novo_menu">
					
				<div class="centraliza">

					<?php //if( !empty($banner) ): ?>
					<!-- <div class="publicidade">
						<div class="anuncio">
								<a href="<?php //echo $banner['link'] ?>" target="_self">
									<img src="http://www.compreingressos.com/images/publicidades/<?php //echo $banner['id'] ?>/publicidade.jpg" style="width: 960px; height: 75px">
								</a>
						</div>
					</div>-->
					<?php //endif; ?>

					<div class="itens">
						<div class="primeira">
							<div class="logo">
								<a href="<?php echo multiSite_getURI("URI_SSL")?>">
									<img src="<?php echo multiSite_getLogo()?>">
								</a>
							</div>
						</div>
						<div class="meio">
							<div class="div_header">
								<ul class="opcoes">
									<?php if (isset($_SESSION['operador']) and $rows == 0) { ?>
									<li><a href="pesquisa_usuario.php">Pesquisar Cliente</a></li>
									<?php } ?>
									<li><a href="minha_conta.php">Minha Conta</a></li>
									<li><a href="<?php echo multiSite_getURI("URI_SSL","espetaculos")?>">Todos os Espetáculos</a></li>
									<li><a href="<?php echo multiSite_getURI("URI_SSL", "teatros")?>">Teatros e Casas de Show</a></li>
									<!-- <li><a href="https://compreingressos.tomticket.com/kb/" >SAC & Suporte</a></li> -->
								</ul>
							</div>

							<div class="cleaner"></div>
							<div class="bottom">
								<div id="btnbuscaCidade" class="local container geral cidade">
									<div class="icone flaticon-placeholder-for-map"></div>
									<div class="txt">
										<span>Cidade</span>
									</div>
								</div>
								<div id="btnbuscaGenero" class="container geral genero">
									<div class="icone flaticon-office-list"></div>
									<div class="txt">
										<span>Gênero</span>
									</div>
								</div>
								<div class="cleaner"></div>
							</div>
						</div>
						<div class="clearfix"></div>
						<div class="fim">
							<div class="div_header">
								<ul class="midias_sociais">
									<?php if (multiSite_getFacebook() != "") {
									?>
									<li class="midia">
										<a href="<?php echo multiSite_getFacebook(); ?>" target="_blank" class="facebook"></a>
										<div class="icone">
											<span class="icon socicon-facebook" A style="cursor:pointer"> </span>
										</div>
									</li>
									<?php 
									}
									if (multiSite_getTwitter() != "") {
									?>
									<li class="midia">
										<a href="<?php echo multiSite_getTwitter();?>" target="_blank" class="twitter"></a>
										<div class="icone">
											<span class="icon socicon-twitter" A style="cursor:pointer"> </span>
										</div>
									</li>
									<?php 
									}
									if (multiSite_getBlog() != "") {
									?>
									<li class="midia">
										<a href="<?php echo multiSite_getBlog(); ?>" target="_blank" class="wordpress"></a>
										<div class="icone">
											<span class="icon socicon-wordpress" A style="cursor:pointer"> </span>
										</div>
									</li>
									<?php 
									}
									if (multiSite_getInstagram() != "") {
									?>
									<li class="midia">
										<a href="<?php echo multiSite_getInstagram(); ?>" target="_blank" class="instagram"></a>
										<div class="icone">
											<span class="icon socicon-instagram" A style="cursor:pointer"> </span>
										</div>
									</li>
									<?php 
									}
									if (multiSite_getYoutube() != "") {
									?>
									<li class="midia">
										<a href="<?php echo multiSite_getYoutube(); ?>" target="_blank" class="youtube"></a>
										<div class="icone">
											<span class="icon socicon-youtube" A style="cursor:pointer"> </span>
										</div>
									</li>
									<?php 
									}
									if (multiSite_getGooglePlus() != "") {
									?>
									<li class="midia">
										<a href="<?php echo multiSite_getGooglePlus(); ?>" target="_blank" class="google"></a>
										<div class="icone">
											<span class="icon socicon-googleplus" A style="cursor:pointer"> </span>
										</div>
									</li>
									<?php 
									}
									?>
								</ul>
							</div>

							<script>
								function buscaEspetaculos(){
									$busca = $('input[name="busca"]');
									$btn = $('#busca-espetaculos');

									if ($busca.val() == '' || $busca.val().length < 4)
									{
										return false;
									}else{
										$btn.click();
									}
								}
							</script>
							<div class="bottom">
								<div class="search">
									<form method="get" action="<?php echo multiSite_getSearch(); ?>">
										<span class="flaticon-magnifier" onclick="buscaEspetaculos();"></span>
										<input type="submit" id="busca-espetaculos" class="hidden" />
										<span><input name="busca" type="text" placeholder="Espetáculo, diretor, teatro, elenco"></span>
									</form>
								</div>
							</div>
						</div>
						<div class="cleaner"></div>
					</div>
				</div>

				<!-- container hidden -->
				<div id="buscaCidade" class="menu_busca container cidade">
					<div class="centraliza">
						<a href="<?php echo multiSite_getSearch(); ?>" class="ativo">Todas as cidades</a>
						<?php foreach ($dados_cidade as $cidade) {
							$cidade['nome'] = utf8_encode2($cidade['nome']);
							?><a href="<?php echo multiSite_getSearch("?cidade=".$cidade['nome']); ?>"><?php echo $cidade['nome']; ?> <span>(<?php echo $cidade['total']; ?>)</span></a><?php
						}?>
					</div>
				</div>
				<div id="buscaGenero" class="menu_busca container genero">
					<div class="centraliza">
						<a href="<?php echo multiSite_getSearch("?cidade="); ?>" class="ativo">Todos os gêneros</a>
						<?php foreach ($dados_genero as $genero) {
							$genero['nome'] = utf8_encode2($genero['nome']);
							?><a href="<?php echo multiSite_getSearch("?cidade=&genero=" .$genero['nome']); ?>"><?php echo $genero['nome']; ?> <span>(<?php echo $genero['total']; ?>)</span></a><?php
						}?>
					</div>
				</div>
				<!-- container hidden -->
			</div> 
	<?php } else { ?>
		<div id="myNav" class="overlay">

					<!-- Overlay content -->
					<div class="overlay-content">
						<a href="#">Seja um parceiro</a>
						<a href="#">Ajuda</a>
						<a href="#">Serviço</a>
						<a href="#">Política de meia entrada</a>
					</div>
</div>
		<nav class="navbar header__mobile navbar-dark bg-dark">
   <div id="myNavMobile" class="overlay">
      <div class="overlay-content"><a href="#">Seja um parceiro</a><a href="#">Ajuda</a><a href="#">Serviço</a><a href="#">Política de meia entrada</a></div>
   </div>
   <div class="col-3 nav__mobile">
	 <div id="nav-icon3" class="toggle nav__hamburger" onclick="toggleNavMobile()">
					<span></span>
					<span></span>
					<span></span>
					<span></span>
				</div>
	</div>
   <div class="col-6 text-center mx-0 mx-auto"><a href="/" target="_self" class="navbar-brand">
      TIXS.ME
      </a>
   </div>
   <div class="col-3">
      <div class="icon"></div>
      <!----><!---->
      <div id="ddown1" class="m-md-2 btn-group b-dropdown dropdown">
         <!----><button id="ddown1__BV_toggle_" aria-haspopup="true" aria-expanded="false" type="button" class="btn btn-secondary"><img src="../../images/user.svg" alt=""><span class="sr-only">Search</span></button>
         <div role="menu" aria-labelledby="ddown1__BV_toggle_" class="dropdown-menu"><a role="menuitem" href="#" target="_self" class="dropdown-item">Minha Conta</a><a role="menuitem" href="#" target="_self" class="dropdown-item">Sair</a></div>
      </div>
   </div>
   <div class="col-12">
      <div class="v-suggestions">
         <input type="text" placeholder="" class="inputautocomplete"> 
         <div class="suggestions">
            <ul class="items" style="display: none;"></ul>
         </div>
      </div>
   </div>
   <div id="nav_collapse" class="navbar-collapse collapse" style="display: none;">
      <ul class="navbar-nav">
         <li class="nav-item"><a href="#" target="_self" class="nav-link">Link</a></li>
         <li class="nav-item"><a href="#" target="_self" tabindex="-1" aria-disabled="true" class="nav-link disabled">Disabled</a></li>
      </ul>
      <ul class="navbar-nav ml-auto">
         <form class="form-inline"><input id="__BVID__16" type="text" placeholder="Search" class="mr-sm-2 form-control form-control-sm"><button type="submit" class="btn my-2 my-sm-0 btn-secondary btn-sm">Search</button></form>
         <li id="__BVID__17" class="nav-item b-nav-dropdown dropdown">
            <a href="#" id="__BVID__17__BV_button_" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><span>Lang</span></a>
            <div aria-labelledby="__BVID__17__BV_button_" class="dropdown-menu dropdown-menu-right"><a role="menuitem" href="#" target="_self" class="dropdown-item">EN</a><a role="menuitem" href="#" target="_self" class="dropdown-item">ES</a><a role="menuitem" href="#" target="_self" class="dropdown-item">RU</a><a role="menuitem" href="#" target="_self" class="dropdown-item">FA</a></div>
         </li>
         <li class="nav-item b-nav-dropdown dropdown">
            <a href="#" id="__BVID__18__BV_button_" aria-haspopup="true" aria-expanded="false" class="nav-link dropdown-toggle"><em>User</em></a>
            <div aria-labelledby="__BVID__18__BV_button_" class="dropdown-menu dropdown-menu-right"><a role="menuitem" href="#" target="_self" class="dropdown-item">Profile</a><a role="menuitem" href="#" target="_self" class="dropdown-item">Signout</a></div>
         </li>
      </ul>
   </div>
</nav>
	
		<div class="header">
		
		
		<div class="header__logo text-right">
			<a href="<?php echo multiSite_getURI("URI_SSL") ?>">
				<div class="logo-header"></div>
			</a>
		</img>
			<div id="nav-icon3" class="toggle nav__hamburger" onclick="toggleNav()">
					<span></span>
					<span></span>
					<span></span>
					<span></span>
				</div>
		
			<div class="search">
									<form method="get" action="<?php echo multiSite_getSearch(); ?>" style="
    text-align: center;
    align-self: center;
    margin: 0 auto;
">
										<span class="flaticon-magnifier" onclick="buscaEspetaculos();"></span>
										<input type="submit" id="busca-espetaculos" class="hidden" />
										<span><input name="busca" type="text" placeholder=""></span>
									</form>
								</div>
				<span class="header__signin hidden-xs hidden-sm" style="float: right">
				
				</span>
				<div class="opcoes"  style="float: right; color: white">
									<?php if (isset($_SESSION['operador']) and $rows == 0) { ?>
<a href="pesquisa_usuario.php">Pesquisar Cliente</a>
									<?php } ?>
<a href="minha_conta.php">
<?php 
	if (isset($_SESSION["user"])) {
		echo "Minha Conta";
	}
	else {
		echo "Entrar";
	}
?>
</a>
								</div>
				<span class="visible-xs visible-sm header__signin-mobile" style="float: right; "><img src="../assets/icons/log-in.svg" alt=""></span>
	
				</div>
	</div>



<script>
function toggleNav() {
      document.getElementById("myNav").style.width = "0%";
      $(".toggle").removeClass("open");

      if (!this.menuOpen) {
          document.getElementById("myNav").style.width = "100%";
          $(".toggle").addClass("open");
      }
      else {
          document.getElementById("myNav").style.width = "0%";
          $(".toggle").removeClass("open");
      }
      this.menuOpen = !this.menuOpen;
		}
function toggleNavMobile() {
      document.getElementById("myNav").style.width = "0%";
      $(".toggle").removeClass("open");

      if (!this.menuOpen) {
          document.getElementById("myNav").style.width = "100%";
          $(".toggle").addClass("open");
      }
      else {
          document.getElementById("myNav").style.width = "0%";
          $(".toggle").removeClass("open");
      }
      this.menuOpen = !this.menuOpen;
		}
</script>

<?php
		}
?>