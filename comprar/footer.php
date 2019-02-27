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
        
    </div>
</div>