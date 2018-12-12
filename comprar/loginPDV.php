<?php
require_once('../settings/functions.php');
require_once('../settings/multisite/unique.php');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html>
    <head>
    <!-- Google Tag Manager -->
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','GTM-WNN2XTF');</script>
    <!-- End Google Tag Manager -->

        <meta http-equiv="Content-type" content="text/html; charset=utf-8" />
        <meta name="robots" content="noindex,nofollow"/>
        <link href="<?php echo multiSite_getFavico()?>" rel="shortcut icon"/>
        <link href='https://fonts.googleapis.com/css?family=Paprika|Source+Sans+Pro:200,400,400italic,200italic,300,900' rel='stylesheet' type='text/css'/>
        <link rel="stylesheet" href="../stylesheets/cicompra.css"/>
        <?php require("desktopMobileVersion.php"); ?>
        <link rel="stylesheet" href="../stylesheets/ajustes2.css"/>

        <script src="../javascripts/jquery.2.0.0.min.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.placeholder.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.selectbox-0.2.min.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.mask.min.js" type="text/javascript"></script>
        <script src="../javascripts/cicompra.js" type="text/javascript"></script>

        <script src="../javascripts/jquery.cookie.js" type="text/javascript"></script>
        <script src="../javascripts/jquery.utils2.js" type="text/javascript"></script>
        <script src="../javascripts/common.js" type="text/javascript"></script>
        <script type="text/javascript">
            $(function() {
                $('p.erro').hide();			
                $('#logar').click(function(event) {
                    event.preventDefault();
                    var $this = $(this),
                    form = $('#identificacaoForm'),
                    senha = $('#senha'),
                    senha_txt = senha.val(),
                    valido = true;				
                    if (senha_txt.length < 6) {
                        senha.findNextMsg().slideDown('fast');
                        valido = false;
                    } else senha.findNextMsg().slideUp('slow');
				
                    if (valido) {
                        $.ajax({
                            url: form.attr('action') + '?' + $.serializeUrlVars(),
                            data: form.serialize(),
                            type: form.attr('method'),
                            success: function(data) {
                                if (data.substr(0, 4) == 'redi') {
                                    $this.findNextMsg().slideUp('slow');
                                    document.location = data;
                                } else {
                                    $this.findNextMsg().slideUp('fast', function() {
                                        $(this).html(data).slideDown('fast')
                                    });
                                }
                            }
                        });
                    }
                });
            });
        </script>
        <title><?php echo multiSite_getTitle()?></title>
    </head>
    <body>

    <!-- Google Tag Manager (noscript) -->
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=GTM-WNN2XTF" 
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <!-- End Google Tag Manager (noscript) -->
            
        <div id="pai">
            <?php require "header.php"; ?>
            <div id="content">
                <div class="alert">
                    <div class="centraliza">
                        <img alt="" src="../images/ico_erro_notificacao.png"/>
                        <div class="container_erros"></div>
                        <a>fechar</a>
                    </div>
                </div>

                <div class="centraliza">
                    <div class="descricao_pag">
                        <div class="img">
                            <img src=""/>
                        </div>
                        <div class="descricao">
                            <p class="nome">Identificação</p>
                            <p class="descricao">identifique-se com seu login e senha de acesso</p>
                            <div class="sessao">
                                <p class="tempo" id="tempoRestante"></p>
                                <p class="mensagem"></p>
                            </div>
                        </div>
                    </div>

                    <form id="identificacaoForm" name="identificacao" method="post" action="autenticacaoOperador.php">
                        <input type="hidden" name="pdv" id="pdv" value="1"/>
                        <div class="identificacao">
                            <p class="frase"><b>Operador PDV</b></p>
                            <p class="site"><?php echo multiSite_getName(); ?></p>
                            <input name="login" type="text" id="login" size="30" maxlength="100" placeholder="Login"/>
                            <div class="erro_help">
                                <p class="erro">insira seu login</p>
                                <p class="help"></p>
                            </div>
                            <input type="password" name="senha" placeholder="digite sua senha" id="senha" maxlength="30"/>
                            <div class="erro_help">
                                <p class="erro">senha inválida</p>
                                <p class="help"></p>
                            </div>
                            <input type="button" class="submit avancar passo4" id="logar" href="etapa4.php"/>
                            <div class="erro_help" style="height:25px">
                                <p class="erro">Combinação de login/senha inválida<br/>Por favor tente novamente.</p>
                                <p class="help"></p>
                            </div>
                        </div>
                    </form>

                </div>
            </div>

            <div id="texts">
                <div class="centraliza"></div>
            </div>

            <?php include "footer.php"; ?>

            <?php //include "selos.php"; ?>
        </div>
    </body>
</html>