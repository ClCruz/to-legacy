<?php
  session_start();
  require_once ("../settings/multisite/tellmethesite.php");
  require_once ("../settings/multisite/layout.php");
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
<?php require ("desktopMobileVersion.php"); ?>
<link rel="stylesheet" type="text/css" href="<?php echo getwhitelabel("css") ?>">
<?php if (isset($_SESSION['operador']) and $rows == 0) { ?>
<?php
  } else { ?>
<?php if (!multiSite_isNewTemplate()) {
  ?>
<div id="myNav" class="overlay">
  <!-- Overlay content -->
  <div class="overlay-content">
    <a href="#">Seja um parceiro</a>
    <a href="#">Ajuda</a>
    <a href="#">Serviço</a>
    <a href="#">Política de meia entrada</a>
  </div>
</div>
<div class="container__header">
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
        <?php
          } ?>
        <a href="minha_conta.php">
        <?php
          if (isset($_SESSION["user"])) {
          		echo "Minha Conta";
          } else {
          		// echo "Entrar";
          }
          ?>
        </a>
      </div>
      <span class="visible-xs visible-sm header__signin-mobile" style="float: right; "><img src="../assets/icons/log-in.svg" alt=""></span>
    </div>
  </div>
</div>
<?php } else { ?>

<?php } ?>
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