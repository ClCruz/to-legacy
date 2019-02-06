<?php
// botao de cancelar para os ooperadores
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
$etapa_atual = basename($_SERVER['PHP_SELF'], '.php');
$etapas_para_exibir = array('etapa1', 'etapa2', 'etapa4', 'etapa5');

if (isset($_SESSION['operador']) and in_array($etapa_atual, $etapas_para_exibir)) {
?>
    <style>
    a.botao.voltar.passo0.cancelar {background-image: url('../images/bot_cancelar.png'); margin: 0 0 0 5px;}
    div.resumo_carrinho {width: 420px; margin: 0 195px 0 420px;}
    div.resumo_carrinho span.quantidade {width: 90px;}
    </style>
    <script type='text/javascript'>
        $(function(){
            $('a.botao.voltar.passo0.cancelar').appendTo('.container_botoes_etapas .centraliza');

            $('a.botao.voltar.passo0.cancelar').on('click', function(e){
                var $this = $(this);
                e.preventDefault();
                $.ajax({
                    url: 'pagamento_cancelado.php?tempoExpirado',
                    success: function(){
                        document.location = $this.attr('href');
                    }
                });
            });
        });
    </script>
    <a href="etapa0.php" class="botao voltar passo0 cancelar">cancelar</a>
<?php
}
?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.12.9/umd/popper.min.js" integrity="sha384-ApNbgh9B+Y1QKtv3Rn7W3mgPxhU9K/ScQsAP7hUibX39j7fakFPskvXusvfa0b4Q" crossorigin="anonymous"></script>
<script src="https://maxcdn.bootstrapcdn.com/bootstrap/4.0.0/js/bootstrap.min.js" integrity="sha384-JZR6Spejh4U02d8jOt6vLEHfe/JQGiRRSQQxSfFWpi1MquVdAyjUar5+76PVCmYl" crossorigin="anonymous"></script>

<style type="text/css">
    #comTomTicketChatWidget{bottom: 99px !important;}
    @media screen and (max-width: 600px) {
  #comTomTicketChatWidget {
    display: none;
  }
}
</style>

<script type="text/javascript">
</script>
<div id="footer">
    <div class="centraliza">
        <ul>
            <li class="title">Serviços</li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/6-Captacao_de_Patrocinio");?>">Captação de patrocínio</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/3-Catracas_Offline_e_Online");?>">Catracas online e offline</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/2-Central_de_Vendas");?>">Central de vendas</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/7-Credenciamento");?>">Credenciamento</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/8-Gestao_de_Bilheteria");?>">Gestão de bilheteria</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/4-Ingressos");?>">Ingressos</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "grupos");?>">Vendas para grupos</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/1-Vendas_pela_Internet");?>">Vendas pela internet</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "servicos/5-Vantagens_do_Sistema");?>">Vantagens do sistema</a></li>
        </ul>
        <ul>
            <li class="title">Ajuda</li>
            <?php if (multiSite_getTomTicket() != "") {?>
            <li><a href="<?php echo multiSite_getTomTicket(); ?>" target="_blank">Sac & Suporte</a></li>
            <?php }?>
            <li><a href="/comprar/loginBordero.php?redirect=..%2Fadmin%2F%3Fp%3DrelatorioBordero">Borderô web</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "institucional");?>">Institucional</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "especiais/3-Lei_6103-11");?>">Lei 6103/11</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "faqs");?>">Perguntas frequentes</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "politica");?>">Política de venda</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "privacidade");?>">Privacidade</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "meia_entrada.html");?>" rel="publisher" target="_blank">Política de Meia Entrada</a></li>
            <li><a href="<?php echo multiSite_getURI("URI_SSL", "pontosdevenda");?>" rel="publisher">Pontos de Venda</a></li>
            <li><a class="minha_conta_mobile" href="minha_conta.php">Minha conta</a>
        </ul>
        <ul class="midias_sociais">
            <li class="title">Mídias Sociais</li>
            <?php if (multiSite_getFacebook() != "") {?>
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
                <a href="<?php echo multiSite_getTwitter(); ?>" target="_blank" class="twitter"></a>
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
                <a href="<? echo multiSite_getInstagram(); ?>" target="_blank" class="instagram"></a>
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
            <div class="selos">
                <!-- selos -->
                <div id="selos2">
                    <!-- START ENTRUST.NET SEAL CODE -->
                    <script type="text/javascript">
                          (function(d, t) {
                            var s = d.createElement(t), options = {'domain':'<?php echo multiSite_getDomainCompra();?>','style':'9','container':'entrust-net-seal'};
                            s.src = 'https://seal.entrust.net/sealv2.js';
                            s.async = true;
                            var scr = d.getElementsByTagName(t)[0], par = scr.parentNode; par.insertBefore(s, scr);
                            s.onload = s.onreadystatechange = function() {
                            var rs = this.readyState; if (rs) if (rs != 'complete') if (rs != 'loaded') return;
                            try{goEntrust(options)} catch (e) {} };
                            })(document, 'script');
                    </script>
                    <div id="entrust-net-seal"><a href="https://www.entrust.com/ssl-certificates/">SSL Certificate</a></div>
                    <!-- END ENTRUST.NET SEAL CODE -->
                    <style type="text/css">
                        #selos2 table { margin-top: -3px;  }
                    </style>
                </div>
                <!-- selos -->
            </div>
        </ul>
    </div>
</div>