<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 19, true)) {

  $pagina = basename(__FILE__);

  if (isset($_GET['action'])) {
    require('actions/' . $pagina);
  } else {
    $result = executeSQL($mainConnection, 'SELECT ID_USUARIO, CD_LOGIN, DS_NOME, DS_EMAIL, IN_ATIVO, IN_ADMIN, IN_TELEMARKETING FROM MW_USUARIO');
    $result = executeSQL($mainConnection, 'SELECT ID_APRESENTACAO, CONVERT(CHAR(10), DT_APRESENTACAO,103) AS DT_APRESENTACAO, HR_APRESENTACAO, DS_PISO, IN_ATIVO FROM MW_APRESENTACAO WHERE ID_EVENTO = ?
	AND CONVERT(CHAR(8), DT_APRESENTACAO,112) >= CONVERT(CHAR(8), GETDATE(),112) ORDER BY MW_APRESENTACAO.DT_APRESENTACAO
	', array($_GET['evento']));

    $resultTeatros = executeSQL($mainConnection, 'SELECT ID_BASE, DS_NOME_TEATRO FROM MW_BASE WHERE IN_ATIVO = \'1\'');
  }
  if (!sqlErrors()) {
?>

    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script>
      $(function() {
        var pagina = '<?php echo $pagina; ?>'

        $('#teatro').change(function() {
          document.location = '?p=' + pagina.replace('.php', '') + '&teatro=' + $(this).val();
        });

        $('#evento').change(function() {
          document.location = '?p=' + pagina.replace('.php', '') + '&teatro=' + $('#teatro').val() + '&evento='+ $(this).val() ;
        });

        // Monta nova tabela por jquery
        $('#app table').delegate('a', 'click', function(event) {
          event.preventDefault();

          var $this = $(this),
          href = $this.attr('href'),
          id = 'codevento=' + $.getUrlVar('codevento', href),
          tr = $this.closest('tr');

          if (href.indexOf('?action=update') != -1) {
            if (!validateFields()) return false;

            $.ajax({
              url: href,
              type: 'post',
              data: $('#dados').serialize(),
              success: function(data) {
                if (data.substr(0, 4) == 'true') {
                  var id = $.serializeUrlVars(data);

                  tr.find('td:not(.button):eq(0)').html($('#codevento').val());
                  tr.find('td:not(.button):eq(1)').html($('#dt_apresentacao').val());
                  tr.find('td:not(.button):eq(2)').html($('#hr_apresentacao').val());
                  tr.find('td:not(.button):eq(3)').html($('#ds_piso').val());
                  tr.find('td:not(.button):eq(4)').html($('#in_ativo').is(':checked') ? 'Ativo' : 'Inativo');

                  $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
                  tr.removeAttr('id');
                } else {
                  $.dialog({text: data});
                }
              }
            });
          } else if (href.indexOf('?action=edit') != -1) {
            if(!hasNewLine()) return false;

            var values = new Array();

            tr.attr('id', 'newLine');

            $.each(tr.find('td:not(.button)'), function() {
              values.push($(this).text());
            });

            tr.find('td:not(.button):eq(0)').html('<input name="codevento" readonly type="text" class="readonly inputStyle" id="codevento" maxlength="100" value="' + values[0] + '" />');
            tr.find('td:not(.button):eq(1)').html('<input name="dt_apresentacao" readonly type="text" class="inputStyle" id="dt_apresentacao" maxlength="100" value="' + values[1] + '" />');
            tr.find('td:not(.button):eq(2)').html('<input name="hr_apresentacao" readonly type="text" class="inputStyle" id="hr_apresentacao" maxlength="100" value="' + values[2] + '" />');
            tr.find('td:not(.button):eq(3)').html('<input name="ds_piso" readonly type="text" class="inputStyle" id="ds_piso" maxlength="100" value="' + values[3] + '" />');
            tr.find('td:not(.button):eq(4)').html('<input name="in_ativo" type="checkbox" value="on" class="inputStyle" id="in_ativo" ' + (values[4] == 'Ativo' ? 'checked' : ''  ) + ' />');

            $this.text('Salvar').attr('href', pagina + '?action=update&' + id);

            setDatePickers();
          }
        });

        function validateFields() {
          var campos = $(':text'),
          valido = true;

          $.each(campos, function() {
            var $this = $(this);

            if ($this.val() == '') {
              $this.parent().addClass('ui-state-error');
              valido = false;
            } else {
              $this.parent().removeClass('ui-state-error');
            }
          });
          return valido;
        }

        $('tr:not(.ui-widget-header)').hover(function() {
          $(this).addClass('ui-state-hover');
        }, function() {
          $(this).removeClass('ui-state-hover');
        });
      });
    </script>
    <style type="text/css">
      table td, th{
        text-align: center;
      }
    </style>
    <h2>Eventos Ativos e Inativos</h2>
    <p style="width:600px;"><?php echo comboTeatro('teatro', $_GET['teatro']); ?>&nbsp;&nbsp;&nbsp;Eventos <?php echo comboEvento("evento", $_GET["teatro"], $_GET["evento"]); ?>
    </p>
    <form id="dados" name="dados" method="post">
      <table class="ui-widget ui-widget-content">
        <thead>
          <tr class="ui-widget-header">
            <th>ID</th>
            <th>Data da Apresentação</th>
            <th>Hora da Apresentação</th>
            <th>Piso</th>
            <th>Status</th>
            <th>Ações</th>
          </tr>
        </thead>
        <tbody>
<?php while ($rs = fetchResult($result)) { ?>
            <tr>
              <td><?php echo $rs['ID_APRESENTACAO']; ?></td>
        <td><?php echo $rs['DT_APRESENTACAO']; ?></td>
        <td><?php echo $rs['HR_APRESENTACAO']; ?></td>
        <td><?php echo utf8_encode2($rs['DS_PISO']); ?></td>
        <td><?php echo ($rs['IN_ATIVO'] ? 'Ativo' : 'Inativo'); ?></td>
        <td class="button center"><a href="<?php echo $pagina; ?>?action=edit&codevento=<?php echo $rs['ID_APRESENTACAO']; ?>">Editar</a></td>
      </tr>
<?php } ?>
    </tbody>
  </table>
</form>
<?php
  } else {
    print_r(sqlErrors());
  }
}
?>