<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 330, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {
        
        require('actions/'.$pagina);

    } else {
?>

<html>
    <script>
        $(document).ready(function(){
            var pagina = '<?php echo $pagina; ?>',
                $table_leitura = $('#table_leitura'),
                $refresh = $('#refresh'),
                $table_filtro = $('#table_filtro'),
                $dados = $('#dados'),
                $cboTeatro = $('#cboTeatro'),
                $cboPeca = $('#cboPeca'),
                $cboApresentacao = $('#cboApresentacao'),
                $cboHorario = $('#cboHorario'),
                $cboSala = $('#cboSala'),
                $print = $('#print'),
                $action = $('#action'),
                autoRefresh = false,
                defaultInterval = 30;

            $('.button, [type="button"]').button();

            function countdown(s) {
                if (autoRefresh) {
                    $refresh.val('Parar ou próxima atualização em '+s+' segundos');

                    if (s == 0) {
                        $dados.trigger('submit');
                    } else {
                        setTimeout(function(){countdown(s-1)}, 1000);
                    }
                } else {
                    $refresh.val('Atualizar');
                }
            }

            $refresh.on('click', function(){
                if ($cboTeatro.val() == '' || $cboPeca.val() == '' || $cboApresentacao.val() == '' || $cboHorario.val() == '') {
                    $.dialog({
                            title: 'Alerta...',
                            text: 'Preencha todas as informações antes de iniciar o monitoramento.'
                        });
                    return false;
                }

                if (autoRefresh) {
                    autoRefresh = false;
                    $table_filtro.find('select').prop('disabled', false);
                } else {
                    autoRefresh = true;
                    $table_filtro.find('select').prop('disabled', true);
                    $dados.trigger('submit');
                }
            });

            $print.on('click', function(){
                print();
            });

            $dados.on('submit', function(e){
                e.preventDefault();

                $disabled_fields = $dados.find(':disabled').prop('disabled', false);

                $table_leitura.html('<tr><td align="center"><img src="../images/catraca_loading.gif" /></td></tr>');

                $.ajax({
                    url: pagina + '?action=getTable',
                    type: 'POST',
                    data: $dados.serialize(),
                    dataType: "html"
                }).done(function(html){
                    html = html.replace('"EVENTO TAL"', $('#cboPeca option:selected').text())
                                .replace('"LOCAL TAL"', $('#cboTeatro option:selected').text())
                                .replace('"DIA TAL"', $('#cboApresentacao option:selected').text())
                                .replace('"HORARIO TAL"', $('#cboHorario option:selected').text())
                                .replace('"SETOR TAL"', $('#cboSala option:selected').text());

                    $table_leitura.html(html);
                }).always(function(){
                    setTimeout(function(){countdown(defaultInterval)}, 1000);
                });

                $disabled_fields.prop('disabled', true);
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
            });

            $cboHorario.on('change', function(){
                $.ajax({
                    url: pagina + '?action=cboSala&cboTeatro=' + $cboTeatro.val() + '&cboPeca=' + $cboPeca.val() + '&cboApresentacao=' + $cboApresentacao.val() + '&cboHorario=' + $cboHorario.val()
                }).done(function(html){
                    $cboSala.html(html).trigger('change');
                });
            });

            $table_leitura.on('mouseenter mouseleave', 'tr:not(.ui-widget-header)', function() {
                $(this).toggleClass('ui-state-hover');
            });
        });
    </script>
    <head>
        <style type="text/css">
            .print_only {
                display: none;
            }

            @media print {
                #holder > * {
                    display: none;
                }

                #content {
                    display: block;
                }

                #refresh, #print, #table_filtro {
                    display: none;
                }

                .print_only {
                    display: table-row;
                }
            }
        </style>
</head>
<body>
    <h2>Monitoramento do Controle de Entrada</h2>
    <form id="dados" action="<?php echo $pagina; ?>" target="_blank" method="POST">
        <table id="table_filtro">
            <tr>
                <td colspan="2">
                    <strong>Local:</strong><br>
                    <select name="cboTeatro" id="cboTeatro"><option value="">Carregando...</option></select>
                </td>
                <td>
                    <strong>Evento:</strong><br>
                    <select name="cboPeca" id="cboPeca"><option value="">Selecione um Local...</option></select>
                </td>
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
                    <select name="cboSala" id="cboSala"><option value="">Selecione um Hor&aacute;rio...</option></select>
                </td>
            </tr>
            <tr><td colspan="3"></td></tr>
            <tr>
                <td colspan="3">
                    <input id="refresh" type="button" value="Atualizar" /> <input id="print" type="button" value="imprimir" />
                </td>
            </tr>
        </table>

        <br />

        <table id="table_leitura" class="ui-widget ui-widget-content printable"></table>
    </form>
</BODY>
</html>
<?php
    }
}
?>
