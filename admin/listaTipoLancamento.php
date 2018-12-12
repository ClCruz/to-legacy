<?php
require_once('../settings/settings.php');
require_once('../settings/functions.php');
session_start();
$mainConnection = mainConnection();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 270, true)) {

    $pagina = basename(__FILE__);

    if ($_POST) {

        $data_inicial = substr($_POST['dt_inicial'], 6, 4) . substr($_POST['dt_inicial'], 3, 2) . substr($_POST['dt_inicial'], 0, 2);
        $data_final = substr($_POST['dt_final'], 6, 4) . substr($_POST['dt_final'], 3, 2) . substr($_POST['dt_final'], 0, 2);
        $lancamento = $_POST['lancamento'] == 'todos' ? NULL : $_POST['lancamento'];
        $evento = $_POST['evento'] == 'todos' ? NULL : $_POST['evento'];
        $usuario = $_POST['usuario'] == 'todos' ? NULL : $_POST['usuario'];

        $conn = getConnection($_GET['teatro']);
        $query = "exec SP_LAN_CON002 ?, ?, ?, ?, ?, ?, ?";
        $params = array($data_inicial, $data_final, $lancamento, $evento, $usuario, $_SESSION['admin'], $_GET['teatro']);
        $result = executeSQL($conn, $query, $params);
        
    }

    if (!$_GET['excel']) {
?>
    <script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script>
        $(function() {
            var pagina = '<?php echo $pagina; ?>'
            $('input[type="submit"], input[type="button"], .button').button();

            $('input.datepicker').datepicker({
                changeMonth: true,
                changeYear: true,
                onSelect: function(date, e) {
                    if ($(this).is('#dt_inicial')) {
                        $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                    }
                }
            }).datepicker('option', $.datepicker.regional['pt-BR']);

            $('#dt_final').datepicker('option', 'minDate', $('#dt_inicial').datepicker('getDate'));

            $("#btnRelatorio").click(function(){
                if(!verificaCPF($('#cd_cpf').val()))
                {
                    $.dialog({title: 'Alerta...', text: 'CPF inválido.'});
                }else{ if($('#situacao').val() == "V"){
                        $.dialog({title: 'Alerta...', text: 'Selecione a situação'});
                    }else{
                        document.location = '?p=' + pagina.replace('.php', '') + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+ $("#dt_final").val() + '&situacao=' + $("#situacao").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() + '&nm_operador='+ $("#nm_operador").val() +'&nm_evento=' + $("#evento").val();
                    }}
            });

            $('#teatro').change(function(){
                document.location = '?' + $('#form_filtros').serialize();
            });

            if ($('#teatro').val() != '') {
                $('select:not(#teatro)').find('option:first').attr('value', 'todos').text('TODOS');//.attr('selected', 'selected')
                $('#lancamento').find('option[value="3"]').remove();
            }

            $('#submit').click(function(){
                $('#form_filtros').attr('action', '?' + $('#form_filtros').serialize());
            });

            $('#excel').click(function(){
                $('#form_filtros').attr('action', pagina + '?' + $('#form_filtros').serialize() + '&excel=1');
            });

            $('table:not(#filtros) tr:not(.ui-widget-header)').hover(function() {
                $(this).addClass('ui-state-hover').next('.estorno').addClass('ui-state-hover');
            }, function() {
                $(this).removeClass('ui-state-hover').next('.estorno').removeClass('ui-state-hover');
            });
        });    
    </script>
    <h2>Tipo de Lançamentos</h2>

<form id="form_filtros" style="text-align:left" method="post">
  <input type="hidden" name="p" value="<?php echo basename($pagina, '.php'); ?>">
  <table id="filtros" style="width:auto">
    <tr>
      <td>
        Local<br/>
        <?php echo comboTeatroPorUsuario('teatro', $_SESSION['admin'], $_GET['teatro']) ?>
      </td>
      <td>
        Tipo de Lançamento<br/>
        <?php echo comboTipoLancamento('lancamento', $_GET['teatro'], $_GET['lancamento']) ?>
      </td>
      <td>
        Evento<br/>
        <?php echo comboEventoPorUsuario('evento', $_GET['teatro'], $_SESSION['admin'], $_GET['evento']) ?>
      </td>
    </tr>
    <tr>
      <td>
        Data Inicial<br/>
        <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d/m/Y") ?>" id="dt_inicial" name="dt_inicial" readonly/>
      </td>
      <td>
        Data Final<br/>
        <input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" readonly/>
      </td>
      <td>
        Usuário<br/>
        <?php echo comboUsuariosPorBase('usuario', $_GET['teatro'], $_GET['usuario']) ?>
      </td>
    </tr>
  </table>
  <br/>
  <input type="submit" id="submit" value="Listar" /> <input type="submit" id="excel" value="Exportar para o excel" />
</form>

<br/><br/>

<?php
    } else {
        header("Content-type: application/vnd.ms-excel");
        header("Content-type: application/force-download");
        header("Content-Disposition: attachment; filename=movimentacao.xls");
        ?><meta http-equiv="Content-Type" content="text/html; charset=utf-8"><?php
    }
if (isset($result)) {
?>

<!-- Tabela de pedidos -->
<table class="ui-widget ui-widget-content" id="resultado">
    <thead>
        <tr class="ui-widget-header">
            <th>Data</th>
            <th>Movimentação</th>
            <th>Usuário</th>
            <th>Espetáculo</th>
            <th>Apresentação</th>
            <th>Lugares</th>
            <th>Tipo de Ingresso</th>
            <th>Valor</th>
            <th>Forma Pagamento</th>
            <th>Nome Cliente</th>
            <th>Telefone</th>
            <th>CPF</th>
        </tr>
    </thead>
    <tbody>

<?php while ($rs = fetchResult($result)) { ?>
              <tr>
                  <td><?php echo $rs['Data da Venda']->format("d/m/y G:i:s") ?></td>
                  <td><?php echo utf8_encode2($rs['Lancamento']) ?></td>
                  <td><?php echo utf8_encode2($rs['Nome Usuario']) ?></td>
                  <td><?php echo utf8_encode2($rs['Nome da Peca']) ?></td>
                  <td><?php echo $rs['Data da Apresentacao'] . ' às ' . $rs['Hora da Sessao'] ?></td>
                  <td><?php echo utf8_encode2($rs['Poltrona']) ?></td>
                  <td><?php echo utf8_encode2($rs['Ingresso']) ?></td>
                  <td><?php echo $_GET['excel'] ? $rs['Valor Liquido'] : number_format($rs['Valor Liquido'], 2, ",", ".") ?></td>
                  <td><?php echo utf8_encode2($rs['Forma de Pagamento']) ?></td>
                  <td><?php echo utf8_encode2($rs['Nome do Cliente']) ?></td>
                  <td><?php echo utf8_encode2($rs['Telefone']) ?></td>
                  <td><?php echo utf8_encode2($rs['CPF']) ?></td>
              </tr>
<?php
        if ($rs['Justificativa']) { ?>
             <tr>
                  <td colspan="2" align="right">Justificativa: </td>
                  <td colspan="10"><?php echo $rs['Justificativa'] ?></td>
              </tr>
        <?php }
    }
}
?>
    </tbody>
</table>

<?php
if ($_GET['excel']) die();
}
?>
