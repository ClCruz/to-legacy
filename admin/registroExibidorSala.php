<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 520, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);

    } else {
?>

<html>
   <head>
       <meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1" >
       <link rel="stylesheet" href="https://cdn.datatables.net/1.10.15/css/jquery.dataTables.min.css" >
       <script type="text/javascript" src="https://cdn.datatables.net/1.10.15/js/jquery.dataTables.min.js"></script>
     <script>
        var table ;
        function initDatatable(){
                table = $('#datatable').DataTable( {
                    "language": {
                        "lengthMenu": "Exibir _MENU_ registros por página",
                        "zeroRecords": "Nada encontrado!",
                        // "info": "Exibindo pagina _PAGE_ de _PAGES_",
                        "info": "Mostrando _START_ para _END_ de _TOTAL_ entradas",
                        "infoEmpty": "Nenhum registro encontrado",
                        "infoFiltered": "(filtrado de _MAX_ registros totais)",
                        "infoSearching": "Pesquisar:",

                        "emptyTable":     "Sem dados disponíveis",
                        "infoPostFix":    "",
                        "thousands":      ",",
                        "loadingRecords": "Carregando...",
                        "processing":     "Processando...",
                        "search":         "Pesquisar:",
                        "zeroRecords":    "Nenhum registro correspondente encontrado",
                        "paginate": {
                            "first":      "Primeira",
                            "last":       "Última",
                            "next":       "Próxima",
                            "previous":   "Voltar"
                        },
                        "aria": {
                            "sortAscending":  ": ativado para ordernação crescente",
                            "sortDescending": ": ativado para ordernação decrescente"
                        }
                    },
                    "destroy":true
                } );
        }
        function destroyDatatable(){
          table.destroy();
        }

        $(document).ready(function(){
            var pagina = '<?php echo $pagina; ?>';
             $('.button').button();
          
              initDatatable();

             
             $('#cboTeatro').on('change',function(){
                var idBase = $(this).find(":selected").val();
                    
                    idBase = (idBase == '') ? 'null': idBase;

                    var actionData = 'Acao=1&idBase='+idBase;

                        $.ajax({
                            url: 'registroExibidorSalaActions.php',
                            method: 'post',
                            data: actionData,
                            success: function(data){
                                destroyDatatable();
                                $('#salas').html(data);
                                $('.button').button();
                                $('#msgResultado').html('');
                                initDatatable();
                            },
                            beforeSend: function(){
                                $('#salas').html('<tr><td colspan="4">Carregando...</td></tr>');
                            }
                        });
                    
             });
        

            

        });


        function clickEdit(inputBt,cancel){
             $('#msgResultado').html('');
             cancel = (cancel == 'undefined') ? false : cancel;

             var idBase = $('#cboTeatro').val();
                var item = $(inputBt).data('item');
                var codSala = $(inputBt).data('codsala');


               var nomeSala  = $('#nomeSalaIn_' + item);
                var nrRegExib = $('#nrRegExibIn_' + item);
                var nrRegSala = $('#nrRegSalaIn_' + item);
                var qtdAssentoPadrao = $('#qtdAssentoPadraoIn_' + item);
                var qtdAssentoEspecial = $('#qtdAssentoEspecialIn_' + item);


                // var nomeSalaTx  = $('#nomeSalaTx_' + item);
                var nrRegExibTx = $('#nrRegExibTx_' + item);
                var nrRegSalaTx = $('#nrRegSalaTx_' + item);
                var qtdAssentoPadraoTx = $('#qtdAssentoPadraoTx_' + item);
                var qtdAssentoEspecialTx = $('#qtdAssentoEspecialTx_' + item);

                

                if(nrRegExib.is(':hidden') || cancel){
                    
                    if(cancel)
                         $('.btEditar[data-item='+item+']').val('Editar');
                    else
                        $(inputBt).val('Salvar');


                    $('.btCancelar[data-item='+item+']').toggle();
                }else{

                    if(nrRegExib.val()=='' || nrRegExib.val() <= 0
                       || nrRegSala.val()=='' || nrRegSala.val() <= 0
                       || qtdAssentoEspecial.val() == '' || qtdAssentoEspecial.val() < 0
                       || qtdAssentoPadrao.val() == '' || qtdAssentoPadrao.val() < 0
                    ){
                        alert("Os valores para Nr. Reg.Exibidor na Ancine e Nr. Reg.da Sala na Ancine precisam ser maior que zero!");
                        return;
                    }

                    var actionData = 'Acao=2&nrRegExib='+nrRegExib.val()+'&nrRegSala='+nrRegSala.val()+'&idBase='+idBase+'&codSala='+codSala+'&qtdAssentoPadrao='+qtdAssentoPadrao.val()+'&qtdAssentoEspecial='+qtdAssentoEspecial.val();


                    $.ajax({
                        url: 'registroExibidorSalaActions.php',
                        method: 'post',
                        data: actionData,
                        success: function(data){
                            // nomeSalaTx.html(nomeSala.val());
                            nrRegExibTx.html(nrRegExib.val());
                            nrRegSalaTx.html(nrRegSala.val());
                            qtdAssentoPadraoTx.html(qtdAssentoPadrao.val());
                            qtdAssentoEspecialTx.html(qtdAssentoEspecial.val());


                            $('#msgResultado').html(data);
                        },
                        beforeSend: function(){
                            $('#msgResultado').html('Salvando...');
                        }
                    });

                    $(inputBt).val('Editar');
                    $('.btCancelar[data-item='+item+']').toggle();
                }

                // nomeSala.toggle();
                nrRegExib.toggle();
                nrRegSala.toggle();
                qtdAssentoPadrao.toggle();
                qtdAssentoEspecial.toggle();
           
                // nomeSalaTx.toggle();
                nrRegExibTx.toggle();
                nrRegSalaTx.toggle();
                qtdAssentoPadraoTx.toggle();
                qtdAssentoEspecialTx.toggle();



                // console.log(codSala);
        }

        function clickCancel(inputBt){
            clickEdit(inputBt,true);
        }


    </script>
    <style type="text/css">
        .inputhidden{
            display: none;
        }
    </style>
   
   
</head>
<body>
    <h2>Registros do Exibidor e Sala</h2>
       <form>
      <table border="0" cellpadding="2" cellspacing="2">
          <tr>
               <td><strong>Local:</strong><br>
                    <?php
                    $funcJavascript = '';
                    //echo comboTeatro("cboTeatro", "", $funcJavascript);

                    $result = executeSQL($mainConnection, 'SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO FROM MW_BASE B INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = B.ID_BASE WHERE AC.ID_USUARIO ='. $_SESSION['admin'] .'  AND B.IN_ATIVO = \'1\' ORDER BY B.DS_NOME_TEATRO');

                    $combo = '<select name="cboTeatro" ' . $funcJavascript . ' class="inputStyle" id="cboTeatro"><option value="">Selecione um local...</option>';
                    while ($rs = fetchResult($result)) {
                        $combo .= '<option value="' . $rs['ID_BASE'] . '"' . (($selected == $rs['ID_BASE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_NOME_TEATRO']) . '</option>';
                    }
                    $combo .= '</select>';

                    echo $combo;
                    ?>
                </td>
        
                 
          </tr>
      </table>
      </form><br>

      <div><strong id="msgResultado"></strong></div>
       
      <table class="ui-widget ui-widget-content" id="datatable">
      
      
        <br>
        <br>
          <thead>
              <tr class="ui-widget-header " style="text-align: center;">
                  <th style="width: 16%">Sala</th>
                  <th style="width: 16%">Nr. Reg.Exibidor na Ancine</th>
                  <th style="width: 16%">Nr. Reg.da Sala na Ancine</th>
                  <th style="width: 16%">Qtd. Assento Padrão</th>
                  <th style="width: 16%">Qtd. Assento Especial</th>
                  <th style="width: 20%">Ações</th>
              </tr>
          </thead>
         
          <tbody border="1" id="salas">
 <!--            <tr>
              <td style="text-align: center;"><label id="nomeSalaTx_1" data-codsala="1" >Sala 1</label>
                <input type="text" name="nomeSala" class="inputhidden" data-codsala="1" id="nomeSalaIn_1"  value="500">
              </td>
              <td style="text-align: center;"><label id="nrRegExibTx_1" data-codsala="1" >500</label> 
                <input type="text" name="nrRegExib" class="inputhidden" data-codsala="1" id="nrRegExibIn_1" value="500">
              </td>
              <td style="text-align: center;"><label id="nrRegSalaTx_1" data-codsala="1" >100</label>
                <input type="text" name="nrRegSala" class="inputhidden" data-codsala="1" id="nrRegSalaIn_1" value="100">
              </td>
              <td style="text-align: center;">
                <input type="button" class="button btEditar" data-codsala="1" data-item="1" value="Editar" />
              </td>
            </tr>       
               -->
          </tbody>
         
      
     </table>

</body>
</html>
<?php
    }
}
?>
