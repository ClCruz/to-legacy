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
                table = $('.datatable').DataTable( {
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
                    }
                } );
        }
        function destroyDatatable(){
          table.destroy();
        }


        $(document).ready(function(){
            var pagina = '<?php echo $pagina; ?>';
             $('.button').button();
             
             $('#cboTeatro').on('change',function(){
                buscarDistribuidor();
                    
             });
        
           $('#btCadastrar').click(function(){
                toggleFormCadastro();
            });

            $('#btListar').click(function(){
                toggleListar();
            });
            
            initDatatable();

        });

        function buscarDistribuidor(){
                   var idBase = $("#cboTeatro").find(":selected").val();
                    
                    idBase = (idBase == '') ? 0: idBase;

                    var actionData = 'Acao=2&idBase='+idBase;

                        $.ajax({
                            url: 'cadastroDistribuidorActions.php',
                            method: 'post',
                            data: actionData,
                            success: function(data){
                              destroyDatatable();
                                $('#distribuidores').html(data);
                                $('.button').button();
                                $('#msgResultado').html('');
                                $('#opcao').show();
                                initDatatable();
                            },
                            beforeSend: function(){
                                $('#salas').html('<tr><td colspan="4">Carregando...</td></tr>');
                            }
                        });
        }

       function hideOpcoes(){
            $('#formCadastro').hide();
            $('#gridListagem').hide();
          }

          function toggleListar(){
            initDatatable();
            $('#gridListagem').show();
            $('#formCadastro').hide();
            buscarDistribuidor();
          }
          function toggleFormCadastro(){
            $('#formCadastro').show();
            $('#gridListagem').hide();
            destroyDatatable();

          }
          function exibeOpcao(){
            $('#opcao').show();
            toggleListar();

            if( $('#cboPeca').find(":selected").val()=='null'){
                hideOpcoes();
                $('#opcao').hide();
               
            }else{
                buscarDistribuidor();
            }
          }


          function getDistribuidorToForm(id){

                var IdDistribuidor = id;    

                $.ajax({
                    url: 'cadastroDistribuidorActions.php',
                    type: 'post',
                    dataType: "json",
                    data: 'Acao=3&IdDistribuidor=' + IdDistribuidor,
                    success: function(data){
                        var json = data;
                        // console.log(json.ds_evento);

                        $('input[name=cnpj]').val(json.cnpj);
                        $('input[name=razaoSocial]').val(json.razao_social);
                        $('input[name=id_distribuidor]').val(json.id_distribuidor);

                        toggleFormCadastro();
                                                   
                    },
                    error: function(){
                        $.dialog({
                            title: 'Erro...',
                            text: 'Erro na chamada dos dados.'
                        });
                    }
                }); 



          }

           function valideForm(){
                if( $('input[name=cnpj]').val()=='' || 
                    $('input[name=razaoSocial]').val()==''
                    ){
                    $.dialog({
                        title: 'Atenção',
                        text: 'Todos os campos devem ser preenchidos!'
                    });
                    return false;
                }
                return true;
            }

            function confirmSubmit(){
                //alert(JSON.stringify($('#formCadastro').serialize()));
                   var var_descTeatro = $('#cboTeatro').val();
                  
                   if(!valideForm())
                        return;


                    $.ajax({
                        url: 'cadastroDistribuidorActions.php',
                        type: 'post',
                        data: 'Acao=1&' + $('#formCadastro').serialize() + '&idBase=' + var_descTeatro,
                        success: function(data){
                            // $('#msgResultado').html(data);
                              cancelSubmit();
                             toggleListar();

                             $.dialog({
                                title: 'Cadastro Distribuidor',
                                text: data
                            });
                             
                        },
                        error: function(){
                            $.dialog({
                                title: 'Erro...',
                                text: 'Erro na chamada dos dados.'
                            });
                        }
                    });
            }

        function clickCancel(inputBt){
            clickEdit(inputBt,true);
        }
         function cancelSubmit(){
            $('input[name=id_distribuidor]').val('');
            $('#formCadastro')[0].reset();
        }


    </script>
    <style type="text/css">
        .inputhidden{
            display: none;
        }
    </style>
   
   
</head>
<body>
    <h2>Cadastro Distribuidor</h2>
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
      <div id="opcao" style="display: none;">
          <table>
              <tr>
                  <td>
                      <input type="button" class="button" id="btCadastrar" value="Cadastrar Negociação">
                      <input type="button" class="button" id="btListar" value="Listar Negociações">
                  </td>
              </tr>
          </table>
      </div>

      <table class="ui-widget ui-widget-content datatable" id="gridListagem">
      
      
        <br>
        <br>
          <thead>
              <tr class="ui-widget-header " style="text-align: center;">
                  <th style="width: 40%">Cnpj</th>
                  <th style="width: 40%">Razão Social</th>
                  <th style="width: 20s%">Ações</th>
              </tr>
          </thead>
         
          <tbody border="1" id="distribuidores">
          </tbody>
         
      
     </table>

        <form id="formCadastro" style="display: none;">
         <table border="0" cellpadding="2" cellspacing="2">
              <tr>
                 <td>
                    <input type="hidden" name="id_distribuidor" value="">
                    <strong>CNPJ</strong><br>
                    <input type="text" name="cnpj"><br><br>
                    <strong>Razão Social</strong><br>
                    <input type="text" name="razaoSocial"><br><br>
                 
                      <input type="button" class="button" name="" onclick="confirmSubmit();" value="Salvar" />
                      <input type="button" class="button" name="" onclick="cancelSubmit();" value="Cancelar" />
                </td>
              </tr>                         
         </table>
     </form>

</body>
</html>
<?php
    }
}
?>
