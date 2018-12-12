<?php
require_once('../settings/functions.php');

$mainConnection = mainConnection();

session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 530, true)) {

    $pagina = basename(__FILE__);

    if (isset($_GET['action']) or isset($_POST['codigo'])) {
        
        require('actions/'.$pagina);

    } else {
?>

<html>
   <head>
       <meta http-equiv="Content-Type" content="text/html;charset=ISO-8859-1" >

    <link rel="stylesheet" href="../stylesheets/jquery.dataTables.min.css" >
    <script type="text/javascript" src="../javascripts/jquery.dataTables.min.js"></script>       
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
                    },
                   "destroy":true
                } );
        }
        function destroyDatatable(){
          table.destroy();
          table = '';
          console.log("datatable Off!");
        }    

         function toggleDatatable(){
          if(typeof table == 'undefined' || table == '' )
              initDatatable();
          else 
              destroyDatatable();  
        }

       $(document).ready(function(){
            var pagina = '<?php echo $pagina; ?>';
            $('.button').button();
             
            $('input.datepicker').datepicker({
                changeMonth: true,
                changeYear: true,
                onSelect: function(date, e) {
                    if ($(this).is('#dt_inicial')) {
                        $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                    }
                }
            }).datepicker('option', $.datepicker.regional['pt-BR']);

            $('#btCadastrar').click(function(){
                toggleFormCadastro();
            });

            $('#btListar').click(function(){
                toggleListar();
            });

              initDatatable();
            

        });
        

          function CarregaStatusBilheteria()
          {
                     
          };
          function hideOpcoes(){
            $('#formCadastro').hide();
            $('#gridListagem').hide();
          }

          function toggleListar(){
            
            $('#gridListagem').show();
            $('#formCadastro').hide();
            buscarNegociacoes();
          }
          function toggleFormCadastro(){
            // toggleDatatable();
            $('#formCadastro').show();
            $('#gridListagem').hide();

          }
          function exibeOpcao(){
            $('#opcao').show();
            toggleListar();

            if( $('#cboPeca').find(":selected").val()=='null'){
                hideOpcoes();
                $('#opcao').hide();
               
            }else{
                buscarNegociacoes();
                carregarDistruibuidores();
            }
          }

          function getValNegocToForm(id){

                var IdNegociacao = id;    

                $.ajax({
                    url: 'negociacaoExibidorDistribuidorActions.php',
                    type: 'post',
                    dataType: "json",
                    data: 'Acao=3&IdNegociacao=' + IdNegociacao,
                    success: function(data){
                        var json = data;
                        // console.log(json.ds_evento);
                        $('input[name=id_negociacao]').val(json.id_negoc_obra_distribuidor).change();
                        $('input[name=numeroObra]').val(json.numeroObra);
                        $('select[name=id_distribuidor]').val(json.id_distribuidor).change();
                        $('select[name=tipoTela]').val(json.tipoTela).change();
                        $('select[name=digital]').val(json.digital).change();
                        $('select[name=tipoProjecao]').val(json.tipoProjecao).change();
                        $('select[name=audio]').val(json.audio).change();
                        $('select[name=legenda]').val(json.legenda).change();
                        $('select[name=libras]').val(json.libras).change();
                        $('select[name=legendagemDescritiva]').val(json.legendagemDescritiva).change();
                        $('select[name=audioDescricao]').val(json.audioDescricao).change();

            

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

          function buscarNegociacoes(){
                      
                var cod = $('#cboPeca').find(":selected").val();  

                $.ajax({
                    url: 'negociacaoExibidorDistribuidorActions.php',
                    type: 'post',
                    data: 'Acao=2&CodPeca='+cod,
                    success: function(data){
                       toggleDatatable();
                        $('#negociacoes').html(data);
                        $('.button').button();
                       toggleDatatable(); 
                    },
                    error: function(){
                        $.dialog({
                            title: 'Erro...',
                            text: 'Erro na chamada dos dados.'
                        });
                    }
                });
          }

          //DataBase, Tipo, Procedure
            function ExibePeca(NmDB, Tipo, Procedure)
            {
                //limpar();

                if (NmDB != "")
                {
                    switch(Tipo)
                    {
                        case 'Peca':
                            $.ajax({
                                url: 'negociacaoExibidorDistribuidorActions.php',
                                type: 'post',
                                data: 'NomeBase='+ NmDB +'&Proc='+ Procedure,
                                success: function(data){
                                    $('#divPeca').html(data);
                                },
                                error: function(){
                                    $.dialog({
                                        title: 'Erro...',
                                        text: 'Erro na chamada dos dados.'
                                    });
                                }
                            });
                            break;
                    }
                }
                else
                {
                    switch(Tipo)
                    {
                        case 'Peca':
                            document.getElementById("divPeca").innerHTML = '<SELECT disabled id="cboPeca" name="cboPeca" style="width: 250px;"><option value="">Não Selecionado</option></select>';
                            break;
                    }
                }
            };
            function PreencheDescricao(){
                var_descTeatro = $('#cboTeatro').val();
                var_descPeca = $('#cboPeca').val();
            };

            function valideForm(){


                if( $('input[name=numeroObra]').val() == ''||
                    $('select[name=id_distribuidor]').val() == '' ||
                    $('select[name=tipoTela]').val() == '' || 
                    $('select[name=digital]').val() == '' ||
                    $('select[name=tipoProjecao]').val() == '' ||
                    $('select[name=audio]').val() == '' ||
                    $('select[name=legenda]').val() == '' ||
                    $('select[name=libras]').val() == '' || 
                    $('select[name=legendagemDescritiva]').val() == '' ||
                    $('select[name=audioDescricao]').val() == '' 
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
                   var var_descPeca = $('#cboPeca').val();

                   if(!valideForm())
                        return;


                    $.ajax({
                        url: 'negociacaoExibidorDistribuidorActions.php',
                        type: 'post',
                        data: 'Acao=1&' + $('#formCadastro').serialize() + '&idBase=' + var_descTeatro + '&CodPeca=' + var_descPeca,
                        success: function(data){
                            // $('#msgResultado').html(data);
                             $.dialog({
                                title: 'Cadastro Negociação',
                                text: data
                            });
                             cancelSubmit();
                             toggleListar();
                        },
                        error: function(){
                            $.dialog({
                                title: 'Erro...',
                                text: 'Erro na chamada dos dados.'
                            });
                        }
                    });
            }

            function carregarDistruibuidores(){
                  var var_descTeatro = $('#cboTeatro').val();
                

                    $.ajax({
                        url: 'negociacaoExibidorDistribuidorActions.php',
                        type: 'post',
                        data: 'Acao=4&' +'&idBase=' + var_descTeatro,
                        success: function(data){
                             $('select[name=id_distribuidor]').html(data);
                            
                        },
                        error: function(){
                            $.dialog({
                                title: 'Erro...',
                                text: 'Erro na chamada dos dados.'
                            });
                        }
                    });
            }
            function cancelSubmit(){
                $('input[name=id_negociacao]').val('');
                $('#formCadastro')[0].reset();
            }

           

    </script>
    <style type="text/css">
        form table{width: 600px !important;}
    </style>
   
   
</head>
<body>
    <h2>Negociação Exibidor e Distribuidor</h2>
       <form>
      <table border="0" cellpadding="2" cellspacing="2">
          <tr>
               <td><strong>Local:</strong><br>
                    <?php
                     $funcJavascript = 'onChange="ExibePeca(this.value, \'Peca\', \'SP_PEC_CON009;8\');PreencheDescricao()"';
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
                <td>
                    <strong>Evento:</strong><br>
                    <div name="divPeca" Id="divPeca">&nbsp;
                    </div>
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
      <!-- Listagem --> 
      <div id="gridListagem"  style="display: none;">
      <table  class="ui-widget ui-widget-content datatable">
      
      
        <br>
        <br>
          <thead>
              <tr class="ui-widget-header " style="text-align: center;">
                  <th style="width: 40%">Código CPB ou ROE da obra objeto de negociação</th>
                  <th style="width: 30%">Número do registro do distribuidor na ANCINE</th>
                  <th style="width: 30%">Ações</th>
              </tr>
          </thead>
         
          <tbody border="1" id="negociacoes"s>
           <!--  <tr>
              <td style="text-align: center;">
                <label>Sala 1</label>
              </td>
              <td style="text-align: center;">
                <label>501212120</label> 
              </td>
              <td style="text-align: center;">
                <label>10026653</label>
              </td>
              <td style="text-align: center;">
                <input type="button" class="button btEditar" data-id-negociacao="1" data-item="1" value="Alterar" />
              </td>
            </tr>        -->
              
          </tbody>
         
      
     </table>
     </div>
     <!--Fim Listagem-->
     <form id="formCadastro" style="display: none;">
         <table border="0" cellpadding="2" cellspacing="2">
              <tr>
                 <td>
                    <input type="hidden" name="id_negociacao" value="">
                    <strong>Código CPB ou ROE da obra objeto de negociação</strong>
                    <input type="text" maxlength="14" name="numeroObra"><br><br>
                  </td>
              </tr>
              <tr>
                  <td>  
                    <strong>Distribuidor:</strong>
                    <select name="id_distribuidor"></select><br><br>
                  </td>
              </tr>
              <tr>    
                  <td>  
                    <strong>Tipo da Tela:</strong>
                    <select name="tipoTela">
                      <option value="P">Padrão</option>
                      <option value="A">Ampliada</option>
                    </select><br><br>
                  </td>
              </tr>
              <tr>    
                  <td>
                     <strong>Digital:</strong>
                    <select name="digital">
                      <option value="S">SIM</option>
                      <option value="N">NÃO</option>
                    </select><br><br>
                  </td>  
              </tr>
              <tr>    
                  <td>
                    <strong>Tipo de Projeção:</strong>
                    
                    <select name="tipoProjecao">
                      <option value="2">Projeção em 2 dimensões (2D)</option>
                      <option value="3">Projeção em 2 dimensões (3D)</option>
                    </select><br><br>
                  </td>
              </tr>
              <tr>    
                  <td>
                    <strong>Forma do Áudio:</strong>
                    <select name="audio">
                      <option value="O">Original</option>
                      <option value="D">Dublado</option>
                    </select><br><br>
                  </td>
              </tr>
              <tr>    
                  <td>
                    <strong>Legenda:</strong>
                    <select name="legenda">
                      <option value="S">SIM</option>
                      <option value="N">NÃO</option>
                    </select><br><br>
                  </td>
              </tr>
              <tr>    
                  <td>
                    <strong>Libras:</strong>
                    <select name="libras">
                      <option value="S">SIM</option>
                      <option value="N">NÃO</option>
                    </select><br><br>
                  </td>
              </tr>
              <tr>    
                  <td>
                    <strong>Legendagem Descritiva (closed caption) :</strong>
                    <select name="legendagemDescritiva">
                      <option value="S">SIM</option>
                      <option value="N">NÃO</option>
                    </select><br><br>                    
                  </td>
              </tr>
              <tr>      
                  <td>
                    <strong> Audiodescrição (narração de elementos sonoros e visuais) :</strong>
                    <select name="audioDescricao">
                      <option value="S">SIM</option>
                      <option value="N">NÃO</option>
                    </select><br><br>                                        
                  </td>   
               </tr> 
       <!--              <strong>Código de negociação da forma de remuneração do distribuidor</strong><br>
                    <select name="tipoNegociacao">
                        <option>Selecione o tipo da negociação</option>
                        <option value="1">Participação na Receita de Bilheteria</option>
                        <option value="2">Preço Fixo</option>
                        <option value="3">Valor mínimo Garantido</option>
                        <option value="4">Remuneração ao exibidor pela exibição da obra audiovisual</option>
                    </select><br><br>
                    
                    <strong>Percentual de participação do distribuidor na receita líquida de bilheteria (no caso de negociação por participação sobre a RLB)</strong><br>
                    <input type="text" name="vlPercDist"><br><br>
                    
                    <strong>Valor pago pelo exibidor ao distribuidor a título de licenciamento da obra audiovisual para comunicação pública em salas de exibição (no caso de negociação a preço fixo)</strong><br>
                    <input type="text" name="vlPrecoFixo"><br><br>
                    
                    <strong>Valor pago pelo exibidor ao distribuidor a título de licenciamento da obra audiovisual (no caso de negociação a mínimo garantido)</strong><br>
                    <input type="text" name="vlMin"><br><br>
                    
                    <strong>Valor pago pelo distribuidor ao exibidor (no caso de remuneração ao exibidor pela exibição da obra audiovisual)</strong><br> -->
                    <!-- <input type="text" name="vlRemunExib"><br><br> -->
                   
                      <input type="button" class="button" name="" onclick="confirmSubmit();" value="Salvar" />
                      <input type="button" class="button" name="" onclick="cancelSubmit();" value="Cancelar" />
                </td>
              </tr>                         
         </table>
     </form>



<script>
    ExibePeca('','Peca','');
</script>
</body>
</html>
<?php
        if (sqlErrors ())
            echo sqlErrosr();
    }
}
?>
