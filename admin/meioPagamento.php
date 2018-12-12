<?php
require_once('../settings/functions.php');
include('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 260, true)) {

  $pagina = basename(__FILE__);

  if (isset($_GET['action'])) {

    require('actions/' . $pagina);
  } else {

    $result = executeSQL($mainConnection, 'SELECT
                                              M.ID_MEIO_PAGAMENTO,
                                              M.DS_MEIO_PAGAMENTO,
                                              M.IN_ATIVO,
                                              M.NM_CARTAO_EXIBICAO_SITE,
                                              M.QT_HR_ANTECED,
                                              B.DS_CLEARSALE_BANDEIRA,
                                              AMP.IN_ATIVO AS IN_ASSINATURA
                                          FROM MW_MEIO_PAGAMENTO M
                                          LEFT JOIN MW_CLEARSALE_BANDEIRA_MEIO X ON X.ID_MEIO_PAGAMENTO = M.ID_MEIO_PAGAMENTO
                                          LEFT JOIN MW_CLEARSALE_BANDEIRA B ON B.ID_CLEARSALE_BANDEIRA = X.ID_CLEARSALE_BANDEIRA
                                          LEFT JOIN MW_ASSINATURA_MEIO_PAGAMENTO AMP ON AMP.ID_MEIO_PAGAMENTO = M.ID_MEIO_PAGAMENTO
                                          ORDER BY IN_ATIVO DESC, DS_MEIO_PAGAMENTO');
?>

    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script>
      $(function() {
        var pagina = '<?php echo $pagina; ?>';

        $('tr:not(.ui-widget-header)').hover(function() {
          $(this).addClass('ui-state-hover');
        }, function() {
          $(this).removeClass('ui-state-hover');
        });

        $('#app table').delegate('a', 'click', function(event) {
          event.preventDefault();

          var $this = $(this),
          href = $this.attr('href'),
          id = 'idMeioPagamento=' + $.getUrlVar('idMeioPagamento', href),
          tr = $this.closest('tr');

          if (href.indexOf('?action=add') != -1 || href.indexOf('?action=update') != -1) {
            if (!validateFields()) return false;

            $.ajax({
              url: href,
              type: 'post',
              data: $('#dados').serialize(),
              success: function(data) {
                if (data.substr(0, 4) == 'true') {
                  var id = $.serializeUrlVars(data);

                  tr.find('td:not(.button):eq(1)').html($('#nm_cartao_site').val());
                  tr.find('td:not(.button):eq(2)').html($('#hr_anteced').val());
                  tr.find('td:not(.button):eq(4)').html($('#in_ativo_assinatura').is(':checked') ? 'Sim' : 'Não');
                  tr.find('td:not(.button):eq(5)').html($('#in_ativo').is(':checked') ? 'Sim' : 'Não');

                  $this.text('Editar').attr('href', pagina + '?action=edit&' + id);
                  tr.removeAttr('id');
                  location.reload();
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

            tr.find('td:not(.button):eq(1)').html('<input id="nm_cartao_site" name="nm_cartao_site" maxlength="25" value="'+ values[1] +'" type="textbox" />');
            tr.find('td:not(.button):eq(2)').html('<input id="hr_anteced" name="hr_anteced" maxlength="3" value="'+ values[2] +'" type="textbox" />');
            tr.find('td:not(.button):eq(4)').html('<input id="in_ativo_assinatura" name="in_ativo_assinatura" type="checkbox" />');
            tr.find('td:not(.button):eq(5)').html('<input id="in_ativo" name="in_ativo" type="checkbox" />');
            if (values[4] == 'Sim') $('#in_ativo_assinatura').attr('checked', 'checked');
            if (values[5] == 'Sim') $('#in_ativo').attr('checked', 'checked');

            $this.text('Salvar').attr('href', pagina + '?action=update&' + id);
          }
        });
      });

      function validateFields() {
        var campos = $(':input:not(button, #hr_anteced)'),
        nm_cartao_site = $('#nm_cartao_site'),
        valido = true;

        if (nm_cartao_site.val() == '') {
          nm_cartao_site.parent().addClass('ui-state-error');
          valido = false;
        } else {
          nm_cartao_site.parent().removeClass('ui-state-error');
        }

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
    </script>
    <style type="text/css">
      .left{text-align: left;}
    </style>
    <h2>Habilitar meio de pagamento para WEB</h2>
    <form id="dados" name="dados" method="post">
      <table class="ui-widget ui-widget-content">
        <thead>
          <tr class="ui-widget-header ">
            <th class="left">Meio de Pagamento</th>
            <th class="left">Nome do cartão para exibição no site</th>
            <th class="left">Horas antecedentes para exibição</th>
            <th class="left">Bandeira ClearSale</th>
            <th class="left">Assinatura</th>
            <th class="left">Ativo</th>
            <th>A&ccedil;&otilde;es</th>
          </tr>
        </thead>
        <tbody>
      <?php
      while ($rs = fetchResult($result)) {
      ?>
        <tr>
          <td><?php echo utf8_encode2($rs['DS_MEIO_PAGAMENTO']); ?></td>
          <td><?php echo utf8_encode2($rs["NM_CARTAO_EXIBICAO_SITE"]); ?></td>
          <td><?php echo $rs["QT_HR_ANTECED"]; ?></td>
          <td><?php echo utf8_encode2($rs["DS_CLEARSALE_BANDEIRA"]); ?></td>
          <td><?php echo $rs['IN_ASSINATURA'] ? 'Sim' : 'Não'; ?></td>
          <td><?php echo $rs['IN_ATIVO'] ? 'Sim' : 'Não'; ?></td>
          <td class="button"><a href="<?php echo $pagina; ?>?action=edit&idMeioPagamento=<?php echo $rs['ID_MEIO_PAGAMENTO']; ?>">Editar</a></td>
        </tr>
      <?php
      }
      ?>
    </tbody>
  </table>
</form>
<?php
    }
  }
?>