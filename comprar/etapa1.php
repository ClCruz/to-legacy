<?php
  session_start();
//die("Oi....".print_r($_SESSION,true));
include_once($_SERVER['DOCUMENT_ROOT'].'/settings/functions.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/settings/settings.php');
include_once($_SERVER['DOCUMENT_ROOT'].'/settings/multisite/unique.php');

if (isset($_GET['apresentacao']) and is_numeric($_GET['apresentacao'])) {

  // se o usuario estiver em um processo de renovacao redirecionar imediatamente para a etapa 2, evitando a possibilidade de selecao de lugares
  if ($_SESSION['assinatura']['tipo'] == 'renovacao') header("Location: etapa2.php?eventoDS=" . $_SESSION['assinatura']['evento']);
  
  require_once('../settings/Template.class.php');  
  

  if ($is_manutencao === true) {
    header("Location: manutencao.php");
    die();
  }

  require_once('origem.php');
  
  $mainConnection = mainConnection();

  $query = 'SELECT A.CODAPRESENTACAO, E.ID_BASE, E.ID_EVENTO,E.DS_EVENTO,
              B.DS_NOME_TEATRO, CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) DT_APRESENTACAO,
              A.HR_APRESENTACAO, LE.DS_LOCAL_EVENTO, M.DS_MUNICIPIO, ES.SG_ESTADO, A.DS_PISO,
              E.QT_INGR_POR_PEDIDO, E.in_exibe_tela_assinante
            FROM MW_APRESENTACAO A
            INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = \'1\'
            INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE AND B.IN_ATIVO = \'1\'
            LEFT JOIN MW_LOCAL_EVENTO LE ON LE.ID_LOCAL_EVENTO = E.ID_LOCAL_EVENTO
            LEFT JOIN MW_MUNICIPIO M ON M.ID_MUNICIPIO = LE.ID_MUNICIPIO
            LEFT JOIN MW_ESTADO ES ON ES.ID_ESTADO = M.ID_ESTADO
            WHERE A.ID_APRESENTACAO = ? AND A.IN_ATIVO = \'1\'';
  $params = array($_GET['apresentacao']);
  $rs = executeSQL($mainConnection, $query, $params, true);

  $maxIngressos = $rs['QT_INGR_POR_PEDIDO'];

  $exibePopUpAssinante = ($rs['in_exibe_tela_assinante'] == '1' AND !isset($_SESSION['operador']) AND !isset($_SESSION['user']));

  $evento_info = getEvento($rs['ID_EVENTO']);
  $is_pacote = is_pacote($_GET['apresentacao']);

  //setlocale(LC_ALL, "pt_BR", "pt_BR.iso-8859-1", "pt_BR.utf-8", "portuguese");
  $hora = explode('h', $rs['HR_APRESENTACAO']);
  $data = explode('/', $rs['DT_APRESENTACAO']);
  $tempo = mktime($hora[0], $hora[1], 0, $data[1], $data[0], $data[2]);
  $setor_atual = utf8_encode2($rs['DS_PISO']);

  if (count($rs) < 2 and !isset($_GET['teste'])) {
    header("Location: " . multiSite_getURI("URI_SSL"));
  } else {
    setcookie('lastEvent', 'apresentacao=' . $_GET['apresentacao'] . '&eventoDS=' . $rs['DS_EVENTO']);
    $vars = 'teatro=' . $rs['ID_BASE'] . '&codapresentacao=' . $rs['CODAPRESENTACAO'];

    $conn = getConnection($rs['ID_BASE']);

    //verifica se o evento é numerado e se pode ser vendido pelo site
    $query = 'SELECT
             INGRESSONUMERADO,
             DATEDIFF(HH, DATEADD(HH, (ISNULL(P.QT_HR_ANTECED, 24) * -1), CONVERT(DATETIME, CONVERT(VARCHAR, A.DATAPRESENTACAO, 112) + \' \' + LEFT(HORSESSAO,2) + \':\' + RIGHT(HORSESSAO,2) + \':00\')) ,GETDATE() ) AS TELEFONE,
             S.TAMANHOLUGAR,
             CASE WHEN S.FOTOIMAGEMSITE IS NOT NULL THEN 1 ELSE 0 END AS TEM_MAPA
             FROM
             TABAPRESENTACAO A
             INNER JOIN TABSALA S ON S.CODSALA = A.CODSALA
             INNER JOIN TABPECA P ON P.CODPECA = A.CODPECA
             WHERE CODAPRESENTACAO = ? AND P.STAPECA = \'A\' AND CONVERT(CHAR(8), P.DATFINPECA,112) >= CONVERT(CHAR(8), GETDATE(),112) AND P.IN_VENDE_SITE = 1';
    $params = array($rs['CODAPRESENTACAO']);
    $rs2 = executeSQL($conn, $query, $params, true);

    if (!empty($rs2)) {
      $numerado = $rs2[0];
      $tem_mapa = $rs2['TEM_MAPA'];
      $vendasPorTelefone = $rs2['TELEFONE'];
    } else {
      $vendaNaoLiberada = true;
    }


    // verifica se a apresentacao atual pertence a um pacote de assinatura e se esta dentro do periodo de assinatura
    $query = 'SELECT 1 FROM MW_PACOTE_APRESENTACAO A
              INNER JOIN MW_PACOTE P ON P.ID_PACOTE = A.ID_PACOTE
              WHERE A.ID_APRESENTACAO = ? AND DT_FIM_FASE3 >= GETDATE()';
    $params = array($_GET['apresentacao']);
    $apresentacao_filha_pacote = executeSQL($mainConnection, $query, $params);

    // verifica se a apresentacao atual é um pacote de assinatura e se esta dentro do periodo de assinatura
    $query = 'SELECT 1 FROM MW_PACOTE P
              INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
              INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO AND A2.IN_ATIVO = 1
              WHERE A2.ID_APRESENTACAO = ? AND 
              (
                CONVERT(VARCHAR, GETDATE(), 112) <= DATEADD(DAY, -1, DT_INICIO_FASE3)
                OR
                CONVERT(VARCHAR, GETDATE(), 112) > DT_FIM_FASE3
              )';
    $params = array($_GET['apresentacao']);
    $assinatura = executeSQL($mainConnection, $query, $params);


    // checagem de pacote para teatro municipal
    if ($is_pacote AND $rs['ID_BASE'] == 139) {

      $query = 'SELECT
                  CASE WHEN CONVERT(VARCHAR, GETDATE(), 112) BETWEEN DATEADD(DAY, -5, DT_INICIO_FASE3) AND DATEADD(DAY, -1, DT_INICIO_FASE3) THEN 1 ELSE 0 END AS EXIBIR_LOGIN,
                  CASE WHEN CONVERT(VARCHAR, GETDATE(), 112) BETWEEN DATEADD(DAY, -5, DT_INICIO_FASE3) AND DT_FIM_FASE3 THEN 1 ELSE 0 END AS PODE_COMPRAR
                FROM MW_PACOTE P
                INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO AND A2.IN_ATIVO = 1
                WHERE A2.ID_APRESENTACAO = ?';
      $assinatura_antecipada = executeSQL($mainConnection, $query, array($_GET['apresentacao']), true);

      $query = "SELECT TOP 1 1
                FROM MW_PACOTE_RESERVA R
                INNER JOIN MW_PACOTE P ON P.ID_PACOTE = R.ID_PACOTE
                INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                WHERE R.IN_ANO_TEMPORADA = YEAR(GETDATE())-1
                AND R.IN_STATUS_RESERVA = 'R'
                AND E.ID_BASE = 139
                AND R.ID_CLIENTE = ?";
      $assinante_municipal = executeSQL($mainConnection, $query, array($_SESSION['user']), true);

      if ($assinante_municipal[0] AND $assinatura_antecipada['PODE_COMPRAR']) {
        $venda_antecipada_para_assinantes_municipal = true;
      }

      if ($assinatura_antecipada['EXIBIR_LOGIN'] AND !isset($_SESSION['user'])) {
        $exibePopUpAssinante = true;
        $texto_theatro_municipal_login = "Se você foi assinante do Theatro Municipal em 2016 faça o login e compre os novos pacotes antecipadamente.";
      }
    }
    // ----------------------------------------


    if (hasRows($apresentacao_filha_pacote)) {
      $pacoteNaoLiberado = true;
      $vendaNaoLiberada = true;
    } else if (hasRows($assinatura)) {
      if ($_SESSION['assinatura']['tipo'] != 'troca' AND !$venda_antecipada_para_assinantes_municipal) {
        $pacoteNaoLiberado = true;
        $vendaNaoLiberada = true;
      }
    }


    if (isset($_GET['teste'])) {
      $numerado = false;
    }

    if (!$numerado) {
      $query = 'SELECT ISNULL(SUM(1), 0) FROM TABSALDETALHE D
                INNER JOIN TABAPRESENTACAO A ON A.CODSALA = D.CODSALA
                WHERE D.TIPOBJETO = \'C\' AND A.CODAPRESENTACAO = ?
                AND NOT EXISTS (SELECT 1 FROM TABLUGSALA L
                                WHERE L.INDICE = D.INDICE
                                AND L.CODAPRESENTACAO = A.CODAPRESENTACAO)';
      $params = array($rs['CODAPRESENTACAO']);
      $ingressosDisponiveis = executeSQL($conn, $query, $params, true);
      $ingressosDisponiveis = $ingressosDisponiveis[0];

      $query = 'SELECT SUM(1) FROM MW_RESERVA WHERE ID_APRESENTACAO = ? AND ID_SESSION = ?';
      $params = array($_GET['apresentacao'], session_id());
      $ingressosSelecionados = executeSQL($mainConnection, $query, $params, true);
      $ingressosSelecionados = $ingressosSelecionados[0];
    }

    if ($isContagemAcessos) {
      //Carregar xml para evento
      $xml = simplexml_load_file("campanha.xml");
      foreach ($xml->item as $item) {
        if ($rs["ID_EVENTO"] == $item->id) {
          $idcampanha = $item->idcampanha;
        }
      }

      $campanha = get_campanha_etapa(basename(__FILE__, '.php'));
    } else {
      $idcampanha = 0;
    }
  }

  // campanha mail_mkt
  if ($_GET['mc_eid'] and $_GET['mc_cid']) {
    setcookie('mc_cid', $_GET['mc_cid'], $cookieExpireTime);
    setcookie('mc_eid', $_GET['mc_eid'], $cookieExpireTime);
  }

  // veio de hotsite? se sim criar um cookie com todos os hotsite acessados ultimamente
  // (para o caso de uma compra de eventos diferentes vindo de hotsites diferentes)
  if ($_GET['hs']) {
    if ($_COOKIE['hotsite']) {
      $ids_evento_hotsite = explode(',', $_COOKIE['hotsite']);
      
      if (!in_array($rs['ID_EVENTO'], $ids_evento_hotsite))
        $ids_evento_hotsite[] = $rs['ID_EVENTO'];

      $ids_evento_hotsite = implode(',', $ids_evento_hotsite);
    } else {
      $ids_evento_hotsite = $rs['ID_EVENTO'];
    }

    setcookie('hotsite', $ids_evento_hotsite, $cookieExpireTime);
  }

} else
  header("Location: " . multiSite_getURI("URI_SSL"));
//echo session_id();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">

<html style="overflow: visible;">
  <head>
      <!-- Google Tag Manager -->
  <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
  new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
  j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
  'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
  })(window,document,'script','dataLayer','GTM-WNN2XTF');</script>
  <!-- End Google Tag Manager -->

    <meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
    <meta name="robots" content="noindex,nofollow" />

    <link href="<?php echo multiSite_getFavico();?>" rel="shortcut icon"/>
    <link href='https://fonts.googleapis.com/css?family=Paprika|Source+Sans+Pro:200,400,400italic,200italic,300,900' rel='stylesheet' type='text/css' />
    <link rel="stylesheet" href="../stylesheets/cicompra.css"/>
    <?php require("desktopMobileVersion.php"); ?>

    <link rel="stylesheet" href="../stylesheets/annotations.css"/>
    <link rel="stylesheet" href="../stylesheets/ajustes.css"/>
    <link rel="stylesheet" href="../stylesheets/smoothness/jquery-ui-1.10.3.custom.css"/>
    <link rel="stylesheet" href="../stylesheets/ajustes2.css"/>

    <script src="../javascripts/ga.js" async="" type="text/javascript"></script>

    <script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
    <script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
    <script src="../javascripts/modernizr.js" type="text/javascript"></script>
    <script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
    <script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
    <script src="../javascripts/cicompra.js" type="text/javascript"></script>

    <script src="../javascripts/jquery.cookie.js" type="text/javascript"></script>
    <script src="../javascripts/jquery-ui.js" type="text/javascript"></script>
    <script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
    <script src="../javascripts/common.js" type="text/javascript"></script>
    <script src="../javascripts/jquery.annotate.js" type="text/javascript"></script>

    <script type="text/javascript" src="../javascripts/plateia.js?<?php echo $vars; ?>"></script>
    <script type="text/javascript" src="../javascripts/overlay_datas.js?evento=<?php echo $rs['ID_EVENTO']; ?>"></script>

    <title><?php echo multiSite_getTitle()?></title>
    <!-- SCRIPT TAG -->
    <script type="text/JavaScript">
      var idcampanha = <?php echo ($idcampanha != "") ? $idcampanha : 0; ?>;
      if(idcampanha != 0){
        var ADM_rnd_<?php echo $idcampanha; ?> = Math.round(Math.random() * 9999);
        var ADM_post_<?php echo $idcampanha; ?> = new Image();
        ADM_post_<?php echo $idcampanha; ?>.src = 'https://ia.nspmotion.com/ptag/?pt=<?php echo $idcampanha; ?>&r='+ADM_rnd_<?php echo $idcampanha; ?>;
      }
    </script>
    <!-- END SCRIPT TAG -->
    <?php echo $campanha['script']; ?>

    <script type="text/javascript">
      var _gaq = _gaq || [];
      _gaq.push(['_setAccount', '<?php echo multiSite_getGoogleAnalytics(); ?>']);
	    _gaq.push(['_setDomainName', '<?php echo multiSite_getName(); ?>']);
      _gaq.push(['_setAllowLinker', true]);
      _gaq.push(['_trackPageview']);

      (function() {
        var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true;
        ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js';
        var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s);
      })();
    </script>
    <?php if(!empty($rs2) && $rs2["TAMANHOLUGAR"] != 0){ ?>
      <style type="text/css">
        .diametro{
          width: <?php echo $rs2["TAMANHOLUGAR"]; ?>px;
          height: <?php echo $rs2["TAMANHOLUGAR"]; ?>px;
        }
      </style>
    <?php } ?>

    <?php if ($exibePopUpAssinante) { ?>
      <script src="../javascripts/simpleFunctions.js" type="text/javascript"></script>
      <script src="../javascripts/identificacao_cadastro.js" type="text/javascript"></script>
      <script src="../javascripts/cipopup.js" type="text/javascript"></script>
      <script type="text/javascript">
        $(document).ready(function (){
          ciPopup.init('login_assinante');
          <?php
          if ($texto_theatro_municipal_login) {
            echo "$('div.identificacao.cliente div:first').text('$texto_theatro_municipal_login');";
            echo "$('div.identificacao.cliente p.site:first').html('<br>');";
          }
          ?>
        });
      </script>
    <?php } ?>
    
      </head>
  <body style="height: 0px; overflow: visible; position: static;">
  <!-- Google Tag Manager (noscript) -->
  <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNN2XTF" 
  height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
  <!-- End Google Tag Manager (noscript) -->
  <div class="bg__main" style=""></div>
  <!-- conteudo para exibir em popup -->
  <div class="hidden">
    <div id="login_assinante">
      
      <?php require_once ('div_identificacao.php'); ?>
    
      <div class="ui-helper-clearfix"></div>
    </div>
  </div>
  <!-- conteudo para exibir em popup -->

  <div style="margin-top: 0px;" id="pai">
    <?php include_once("header.php"); ?>
    <div id="content">
        <div class="alert">
          <div class="centraliza">
            <img src="../images/ico_erro_notificacao.png" alt="Notificação" />
            <div class="container_erros"><?php
              if ($vendasPorTelefone >= 0) {
                echo "<p>Vendas autorizadas somente nas bilheterias.</p>";
              }
              if ($vendaNaoLiberada and !$pacoteNaoLiberado) {
                echo "<p>Sem apresenta&ccedil;&otilde;es cadastradas.</p>";
              }
              if ($pacoteNaoLiberado) {
                echo "<p>Apresentação vinculada à venda de assinatura. Venda avulsa não permitida na data atual.</p>";
              }
              if ($numerado == false && $ingressosDisponiveis == 0) {
                echo "<p>Não há lugares disponíveis no momento para este setor.</p>";
              }
              ?></div>
            <a>fechar</a>
          </div>
        </div>
        <div class="centraliza">
          <div class="descricao_pag">
            <div class="img">
              <img src="../images/ico_black_passo1.png">
            </div>
            <div class="descricao">
              
              <p class="title__page"><?php echo utf8_encode2($rs['DS_EVENTO']); ?></p>
            </div>
          <div class="resumo_espetaculo">

            <a id="info" name="info"></a>
           
            <div class="resumo">
            <button type="button" class="btn btn-primary botao btn__help" data-toggle="modal" data-target="#sideModalTR"></button>
              <p class="endereco" style="text-transform: capitalize">
              <img class="endereco__icon" src="../images/icons/calendar.svg" alt="">
                <?php echo getDateToString($tempo,"week-small"); ?> <?php echo strftime("%d", $tempo); ?>/<?php echo getDateToString($tempo,"month-small"); ?> - <?php echo $rs['HR_APRESENTACAO']; ?> 
                <br /> 
                <span style="margin-top: 13px"></span>
              <img class="endereco__icon" src="../images/icons/map-pin-white.svg" alt="">
                <?php echo utf8_encode2($evento_info['nome_teatro'] . ' - ' . $evento_info['cidade'] . ', ') . utf8_encode2($evento_info['sigla_estado']); ?>
                <br />
              <div class="outras_datas <?php echo $is_pacote ? ' hidden' : ''; ?>">
                <a href="#" class="other__dates-btn">Ver outras datas</a>
              </div>

            </div>
            </div>
            <div class="container_escolha_ingresso">

              <div class="container_setores hidden">
                <p class="descricao_fase">Escolha o setor</p>
                <?php
                  $result = executeSQL($mainConnection, "SELECT ID_APRESENTACAO, DS_PISO, DS_EVENTO FROM MW_APRESENTACAO A
                                                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO AND E.IN_ATIVO = '1'
                                                        WHERE A.ID_EVENTO = (SELECT ID_EVENTO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                                                        AND DT_APRESENTACAO = (SELECT DT_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                                                        AND HR_APRESENTACAO = (SELECT HR_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                                                        AND A.IN_ATIVO = '1'
                                                        ORDER BY DS_PISO", array($_GET['apresentacao'], $_GET['apresentacao'], $_GET['apresentacao']));

                  while ($rs = fetchResult($result)) {
                    ?>
                      <div class="container_setor<?php echo ($_GET['apresentacao'] == $rs['ID_APRESENTACAO']) ? ' ativo' : ''; ?>">
                        <div class="nome_setor">
                          <?php echo utf8_encode2($rs['DS_PISO']); ?>
                        </div>
                        <a href="etapa1.php?apresentacao=<?php echo $rs['ID_APRESENTACAO']; ?>&eventoDS=<?php echo urlencode(utf8_encode2($rs['DS_EVENTO'])); ?>"><span>selecionar</span></a>
                      </div>
                    <?php
                  }
                ?>
              </div>
              
              <?php if ($vendasPorTelefone < 0 && $vendaNaoLiberada == false && $ingressosDisponiveis != 0) { ?>
              <p class="descricao_fase">Escolha seus ingressos</p>
              <div class="container_ingressos">
                <div class="container_ingresso">
                  <div class="ingresso quantidade">
                    <div class="icone assinaturas"></div>
                    <div class="descricao">
                      <?php if(!$numerado){ ?>
                        <?php } ?>
                        <p class="nome"><?php echo $setor_atual; ?></p>
                        <?php if($numerado){ ?>
                          <p class="help">escolha no mapa abaixo seus assentos</p>
                          <?php }else{ ?>
                            <p class="help">Escolha ao lado a quantidade de ingressos</p>
                      <?php } ?>
                    </div>
                  </div>

                  <?php $maxIngressos = ($ingressosDisponiveis < $maxIngressos) ? $ingressosDisponiveis : $maxIngressos; ?>
                  <select id="numIngressos" style="display: none;">
                    <?php for ($i = 1; $i <= $maxIngressos; $i++) { ?>
                    <option value="<?php echo $i ?>"><?php echo $i ?></option>
                    <?php } ?>
                  </select>
                </div>
              </div>
              <?php } ?>

              <?php if ($vendasPorTelefone < 0 && $vendaNaoLiberada == false && $numerado) { ?>
                <p class="descricao_fase">Escolha no mapa abaixo seus assentos</p>
              <?php } ?>

            </div>
          </div>
            <?php if ($vendasPorTelefone < 0 && $vendaNaoLiberada == false) { ?>
              <?php if ($numerado OR $tem_mapa) { ?>
              <div id="mapa_de_plateia_geral">
                <?php require_once("mapaPlateia.php"); ?>              
              </div>
              <?php } ?>
            <?php } ?>
            </div>
            </div>
            <div class="container_botoes_etapas">
              <div class="centraliza">
                <?php if ($_SESSION['assinatura']['tipo'] == 'troca') { ?>
                <a href="selecionarTroca.php" class="botao voltar passo0">voltar</a>
                <?php } elseif (isset($_SESSION['operador'])) { ?>
                <a href="etapa0.php" class="botao voltar passo0">voltar</a>
                <?php } ?>
                <div class="resumo_carrinho">
                  <span class="quantidade"></span>
                  <span class="frase">ingresso(s) selecionado(s) <br>para essa apresentação</span>
                </div>
                <a href="etapa2.php?eventoDS=<?php echo $_GET['eventoDS']; ?><?php echo $campanha['tag_avancar']; ?>" class="botao avancar passo2 botao_avancar" id="botao_avancar">Avançar</a>
              </div>
            </div>
            </div>
            <div id="texts">
            <div class="centraliza">
             
            </div>
          </div>
            </div><!-- FECHA CONTENT -->

      <?php include "footer.php"; ?>
      <?php //include "selos.php"; ?>

      <div id="overlay">
        <div class="centraliza hidden" id="outras_datas">
          <div class="top">
            <div class="fechar">Voltar</div>
            <div class="cont_gen_class_dura">
              <p>
                <span class="classificacao"></span>
                <span class="duracao"></span>
              </p>
            </div>
            <h1>Carregando...</h1>
            <div class="cont_teatro">
              <p class="teatro"></p>
              <p class="teatro_info"></p>
            </div>
            <div class="datas"></div>
          </div>
        </div>
      </div>

    </div>

    

<div class="modal fade right" id="sideModalTR" tabindex="-1" role="dialog" aria-labelledby="myModalLabel" aria-hidden="true">

<div class="modal-dialog modal-side modal-top-right" role="document">

<div class="modal-content">
<div class="modal-header">
      <h4 class="modal-title w-100" id="myModalLabel">
        Passo <b>1 de 5</b> escolha de setor, lugares e quantidades
      </h4>
      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
        <span aria-hidden="true">&times;</span>
      </button>
    </div>
    <div class="modal-body">
    <p>Escolha até <?php echo $maxIngressos; ?> lugares ou ingressos desejados e clique em avançar para continuar o processo de compra de ingressos.</p>
      
    </div>
  </div>
</div>
</div>
  </body>
</html>