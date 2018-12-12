<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 320, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);

    } else {
?>

<html>
    <script>
        $(document).ready(function(){
            var pagina = '<?php echo $pagina; ?>',
                $table_leitura = $('#table_leitura'),
                $play_stop = $('#play_stop'),
                $table_filtro = $('#table_filtro'),
                $dados = $('#dados'),
                $resultado_leitura = $('#resultado_leitura'),
                $codigo = $('#codigo'),
                $document = $(document),
                $cboTeatro = $('#cboTeatro'),
                $cboPeca = $('#cboPeca'),
                $cboApresentacao = $('#cboApresentacao'),
                $cboHorario = $('#cboHorario'),
                $cboSetor = $('#cboSetor'),
                $table_entrada_saida = $('#table_entrada_saida');

            $('.button, [type="button"]').button();

            $play_stop.on('click', function(){

                if ($cboTeatro.val() == '' || $cboPeca.val() == '' || $cboApresentacao.val() == '' || $cboHorario.val() == '' || $cboSetor.val() == '') {
                    $.dialog({
                            title: 'Alerta...',
                            text: 'Preencha todas as informações antes de iniciar a leitura.'
                        });
                    return false;
                }

                $codigo.val('');
                $resultado_leitura.removeClass('sucesso falha').html('');

                if ($table_leitura.is(':hidden')) {
                    $table_filtro.find('select, :radio').prop('disabled', true);

                    $table_leitura.show();
                    $table_entrada_saida.show();
                    $play_stop.val('Parar Leitura');

                    $dados.trigger('submit');

                    $document.on('click blur focus', function(){
                        $codigo.focus().select();
                    });

                    $codigo.val('').trigger('focus');
                } else {
                    $table_filtro.find('select, :radio').prop('disabled', false);

                    $table_leitura.hide();
                    $table_entrada_saida.hide();
                    $play_stop.val('Iniciar Leitura');

                    $document.off('click blur focus');
                }
            });

            $dados.on('submit', function(e){
                e.preventDefault();

                $codigo.prop('readonly', true);
                $disabled_fields = $dados.find(':disabled').prop('disabled', false);

                if ($codigo.val() != '') {
                    $resultado_leitura
                        .removeClass('sucesso falha')
                        .html('<img src="../images/catraca_loading.gif" />');

                    $.ajax({
                        url: pagina,
                        type: 'POST',
                        data: $dados.serialize(),
                        dataType: "json"
                    }).done(function(data){
                        $resultado_leitura
                            .addClass(data.class)
                            .html(data.mensagem);
                    }).fail(function(){
                        $resultado_leitura
                            .addClass('falha')
                            .html('Falha na conexão.<br /><br />Favor tentar novamente.');
                    }).always(function(){
                        $codigo.focus().select();
                    });
                }

                $.ajax({
                    url: pagina+'?action=pessoas',
                    type: 'POST',
                    data: $dados.serialize()+'&dataapresentacao='+ $('#cboApresentacao option:selected').text(),
                    dataType: "json"
                }).done(function(data){
                    console.log (JSON.stringify(data));
                    $table_entrada_saida
                        .find('.green .qtd').text(data.in).end()
                        .find('.yellow .qtd').text(data.on).end()
                        .find('.red .qtd').text(data.out).end();
                });                

                $disabled_fields.prop('disabled', true);
                $codigo.prop('readonly', false);
            });

            $.ajax({
                url: pagina + '?action=cboTeatro'
            }).done(function(html){
                $cboTeatro.html(html);
            });

            $cboTeatro.on('change', function(){
                $.ajax({
                    url: pagina + '?action=cboPeca&cboTeatro=' + $cboTeatro.val()
                }).done(function(html){
                    $cboPeca.html(html).trigger('change');
                });
            });

            $cboPeca.on('change', function(){
              // alert($cboPeca.val());
                $.ajax({
                    url: pagina + '?action=cboApresentacao&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val()
                }).done(function(html){
                    $cboApresentacao.html(html).trigger('change');
                });
            });

            $cboApresentacao.on('change', function(){
                $.ajax({
                    url: pagina + '?action=cboHorario&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val() + '&cboApresentacao=' + $cboApresentacao.val()
                }).done(function(html){
                    $cboHorario.html(html).trigger('change');
                });
            })

            $cboHorario.on('change', function(){
                $.ajax({
                    url: pagina + '?action=cboSetor&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val() + '&cboApresentacao=' + $cboApresentacao.val() + '&cboHorario=' + $cboHorario.val()
                }).done(function(html){
                    $cboSetor.html(html).trigger('change');
                });
            });

        });
    </script>
    <head>
        <style type="text/css">
            #table_leitura, #table_entrada_saida {
                display: none;
            }

            #codigo {
                border: none;
                border-bottom: 1px solid #000;
                font-size: 30px;
                line-height: 30px;
                text-align: center;
                width: 550px;
            }

            #codigo::selection {
                background: white;
            }

            #codigo::-moz-selection {
                background: white;
            }

            #resultado_leitura {
                font-size: 50px;
                padding: 20px;
            }

            .sucesso {
                color: darkgreen;
                background-color: lightgreen;
                border: 5px solid darkgreen;
            }

            .falha {
                color: darkred;
                background-color: lightpink;
                border: 5px solid darkred;
            }

            td.green, td.yellow, td.red {
                font-variant: small-caps;
                font-weight: bold;
                font-size: 140%;
            }

            span.quantidade {
                font-size: 150%;
            }

            td.green {
                color: #8DC42B;
            }
            td.yellow {
                color: #FFF100;
            }
            td.red {
                color: #F02517;
            }
        </style>
</head>
<body>
    <h2>Controle de Entrada e Saída</h2>
    <form id="dados" action="" method="POST">
        <table id="table_filtro">
            <tr>
                <td>
                    <strong>Local:</strong><br>
                    <select name="cboTeatro" id="cboTeatro"><option value="">Carregando...</option></select>
                </td>
                <td>
                    <strong>Evento:</strong><br>
                    <select name="cboPeca" id="cboPeca"><option value="">Selecione um Local...</option></select>
                </td>
                <td>
                    <div id="radio">
                        <input type="radio" id="radio1" name="sentido" value="entrada" checked="checked"><label for="radio1">Entrada</label>
                        <input type="radio" id="radio2" name="sentido" value="saida"><label for="radio2">Saída</label>
                    </div>
                </td>
                <td>&nbsp;</td>
            </tr>
            <tr>
                <td>
                    <br>
                    <strong>Apresenta&ccedil;&atilde;o:</strong><br>
                    <select name="cboApresentacao" id="cboApresentacao"><option value="">Selecione um Evento...</option></select>
                </td>
                <td>
                    <br>
                    <strong>Hor&aacute;rio:</strong><br>
                    <select name="cboHorario" id="cboHorario"><option value="">Selecione uma Apresentação...</option></select>
                </td>
                <td>
                    <br>
                    <strong>Setor:</strong><br>
                    <select name="cboSetor" id="cboSetor"><option value="">Selecione um Hor&aacute;rio...</option></select>
                </td>
                <td style="vertical-align: bottom;">
                    <input id="play_stop" type="button" value="Iniciar Leitura" />
                </td>
            </tr>
        </table>

        <table id="table_entrada_saida">
            <tr>
                <td colspan="3">
                    <h2>Entrada/Saída</h2>
                </td>
            </tr>

            <tr>
                <td align="center" class="green">
                    <img src="../images/person_in.jpg" /><br/>
                    Check In (entrada): <span class="qtd">000</span>
                </td>
                <td align="center" class="yellow">
                    <img src="../images/person_on.jpg" /><br/>
                    Check Online (dentro): <span class="qtd">000</span>
                </td>
                <td align="center" class="red">
                    <img src="../images/person_out.jpg" /><br/>
                    Check Out (saída): <span class="qtd">000</span>
                </td>
            </tr>
        </table>

        <table id="table_leitura">
            <tr>
                <td>
                    <h2>Leitura</h2>
                </td>
            </tr>

            <tr>
                <td align="center">
                    <input type="text" name="codigo" id="codigo" />
                </td>
            </tr>

            <tr>
                <td align="center">
                    <div id="resultado_leitura"><img src="../images/catraca_loading.gif" /></div>
                </td>
            </tr>
        </table>
    </form>
</BODY>
</html>
<?php
    }
}
?>
