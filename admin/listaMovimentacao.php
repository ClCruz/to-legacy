<?php
require_once('../settings/functions.php');
$mainConnection = mainConnection();
session_start();

if (acessoPermitido($mainConnection, $_SESSION['admin'], 12, true)) {

    require_once('../settings/Paginator.php');

    $pagina = basename(__FILE__);

    if (isset($_GET['action'])) {
      require('actions/' . $pagina);
      die();
    }

    if (isset($_GET["dt_inicial"]) && isset($_GET["dt_final"]) && isset($_GET["situacao"]) && isset($_GET["nm_cliente"]) && isset($_GET["cd_cpf"]) && isset($_GET["num_pedido"]) && isset($_GET["id_base"])) {

        $where = "WHERE e.id_base=? AND CONVERT(DATETIME,CONVERT(CHAR(8), PV.DT_PEDIDO_VENDA, 112)) BETWEEN CONVERT(DATETIME, ?, 103) AND CONVERT(DATETIME, ?, 103) AND PV.IN_SITUACAO = ?";

        $params = array($_GET["id_base"], $_GET["dt_inicial"], $_GET["dt_final"], $_GET["situacao"]);

        $paramsTotal = array($_GET["id_base"], $_GET["dt_inicial"], $_GET["dt_final"], $_GET["situacao"]);

        $select = "SELECT
                    'blc2' blc2,
                    (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)) AS DT_PEDIDO_VENDA,
                    PV.ID_PEDIDO_VENDA,
                    C.DS_NOME AS CLIENTE,
                    C.DS_SOBRENOME,
                    C.CD_EMAIL_LOGIN,
                    SUM(IPV.VL_UNITARIO) AS TOTAL_UNIT,
                    PV.IN_SITUACAO,
                    ROW_NUMBER() OVER(ORDER BY PV.ID_PEDIDO_VENDA DESC) AS 'LINHA',
                    COUNT(1) AS QUANTIDADE,
                    PV.IN_RETIRA_ENTREGA,
                    C.DS_DDD_TELEFONE,
                    C.DS_TELEFONE,
                    C.DS_DDD_CELULAR,
                    C.DS_CELULAR,
                    U.DS_NOME,
                    PV.ID_IP,
                    PV.ID_USUARIO_ESTORNO,
                    PV.DS_MOTIVO_CANCELAMENTO,
                    PV.DT_HORA_CANCELAMENTO ";

        $from = " FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                          INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                          INNER JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO
                              LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";

        $from2 = "FROM
                      MW_PEDIDO_VENDA PV
                      INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
                      INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                      INNER JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                      INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO
                      LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";

        $group = " GROUP BY
                      (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)),
                      PV.ID_PEDIDO_VENDA,
                      C.DS_NOME,
                      C.DS_SOBRENOME,
                      C.CD_EMAIL_LOGIN,
                      PV.IN_SITUACAO,
                      DT_PEDIDO_VENDA,
                      PV.IN_RETIRA_ENTREGA,
                      C.DS_DDD_TELEFONE,
                      C.DS_TELEFONE,
                      C.DS_DDD_CELULAR,
                      C.DS_CELULAR,
                      U.DS_NOME,
                      PV.ID_IP,
                      PV.ID_USUARIO_ESTORNO,
                      PV.DS_MOTIVO_CANCELAMENTO,
                      PV.VL_TOTAL_TAXA_CONVENIENCIA,
                      PV.DT_HORA_CANCELAMENTO";

        if (!empty($_GET["num_pedido"])) {
            $where .= " AND PV.ID_PEDIDO_VENDA = ?";

            $params[] = $_GET["num_pedido"];
            $paramsTotal[] = $_GET["num_pedido"];
        }
        if (!empty($_GET["nm_cliente"])) {
            $where .= " AND (C.DS_NOME LIKE '%" . utf8_decode(trim($_GET["nm_cliente"])) . "%' OR C.DS_SOBRENOME LIKE '%" . utf8_decode(trim($_GET["nm_cliente"])) . "%')";
            $join = true;

            //$params[] = $_GET["nm_cliente"];
        }

        // if (!empty($_GET["nm_evento"])) {
        //     $from .= "  LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
        //                 LEFT JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";
        // }

        // if (!empty($_GET["nm_operador"])) {
        //     if (strtolower($_GET["nm_operador"]) == 'web') {
        //         $select = "SELECT
        //                     (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)) AS DT_PEDIDO_VENDA,
        //                     PV.ID_PEDIDO_VENDA,
        //                     C.DS_NOME AS CLIENTE,
        //                     C.DS_SOBRENOME,
        //                     C.CD_EMAIL_LOGIN,
        //                     SUM(IPV.VL_UNITARIO) AS TOTAL_UNIT,
        //                     PV.IN_SITUACAO,
        //                     ROW_NUMBER() OVER(ORDER BY PV.ID_PEDIDO_VENDA DESC) AS 'LINHA',
        //                     COUNT(1) AS QUANTIDADE,
        //                     PV.IN_RETIRA_ENTREGA,
        //                     C.DS_DDD_TELEFONE,
        //                     C.DS_TELEFONE,
        //                     C.DS_DDD_CELULAR,
        //                     C.DS_CELULAR,
        //                     PV.ID_IP,
        //                     PV.ID_USUARIO_ESTORNO,
        //                     PV.DS_MOTIVO_CANCELAMENTO,
        //                     PV.DT_HORA_CANCELAMENTO ";

        //         $group = " GROUP BY
        //                       (CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) + ' - ' + CONVERT(VARCHAR(8), PV.DT_PEDIDO_VENDA, 114)),
        //                       PV.ID_PEDIDO_VENDA,
        //                       C.DS_NOME,
        //                       C.DS_SOBRENOME,
        //                       C.CD_EMAIL_LOGIN,
        //                       PV.IN_SITUACAO,
        //                       DT_PEDIDO_VENDA,
        //                       PV.IN_RETIRA_ENTREGA,
        //                       C.DS_DDD_TELEFONE,
        //                       C.DS_TELEFONE,
        //                       C.DS_DDD_CELULAR
        //                       C.DS_CELULAR,
        //                       PV.ID_IP,
        //                       PV.ID_USUARIO_ESTORNO,
        //                       PV.DS_MOTIVO_CANCELAMENTO,
        //                       PV.DT_HORA_CANCELAMENTO ";

        //         $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL
        //                   LEFT JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
        //         $join2 = true;
        //     } else {
        //         $where .= " AND U.DS_NOME LIKE '%" . utf8_decode(trim($_GET["nm_operador"])) . "%'";
        //         $from = "FROM MW_PEDIDO_VENDA PV INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE
        //                   LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
        //                   LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER 
        //                   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
        //                 INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";
        //         $join3 = true;
        //     }
        // }
        if (!empty($_GET["cd_cpf"])) {
            $where .= " AND C.CD_CPF = ?";
            $join = true;

            $params[] = $_GET["cd_cpf"];
            $paramsTotal[] = $_GET["cd_cpf"];
        }

        if (!empty($_GET["nm_evento"])) {
            $where .= " AND E.ID_EVENTO = ?";
            // $join4 = true;

            // $from2 .= " LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
            //             INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";

            $params[] = $_GET["nm_evento"];
            $paramsTotal[] = $_GET["nm_evento"];
        }

        $selectTr = "SELECT COUNT(PV.ID_PEDIDO_VENDA) QTD_PEDIDOS, COUNT(PV2.ID_PEDIDO_VENDA) QTD_PEDIDOS_PAIS FROM MW_PEDIDO_VENDA PV
                      LEFT JOIN MW_PEDIDO_VENDA PV2 ON PV2.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA AND PV2.IN_PACOTE = 'S' ";
        if (isset($join)) {
            $selectTr .= " INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $selectTr .= "   PV.ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA PV
                            INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $selectTr .= "   PV.ID_PEDIDO_VENDA FROM MW_PEDIDO_VENDA PV
                            LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $selectTr = " SELECT COUNT(DISTINCT PV.ID_PEDIDO_VENDA) QTD_PEDIDOS FROM MW_PEDIDO_VENDA PV
                        LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                        LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";
            $trAux = executeSQL($mainConnection, $selectTr . $where, $params, true);

            $tr['QTD_PEDIDOS'] = $trAux['QTD_PEDIDOS'];

            $selectTr = " SELECT COUNT(DISTINCT PV.ID_PEDIDO_VENDA) QTD_PEDIDOS_PAIS FROM MW_PEDIDO_VENDA PV
                        LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                        LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                        INNER JOIN MW_EVENTO E ON E.ID_EVENTO=A.ID_EVENTO ";
            $trAux = executeSQL($mainConnection, $selectTr . $where . " PV.IN_PACOTE = 'S' ", $params, true);

            $tr['QTD_PEDIDOS_PAIS'] = $trAux['QTD_PEDIDOS_PAIS'];
        }
        
        if (!$tr) {
          $tr = executeSQL($mainConnection, $selectTr . $where, $params, true);
        }
        $total_reg = (!isset($_GET["controle"])) ? 10 : $_GET["controle"];
        $offset = (isset($_GET["offset"])) ? $_GET["offset"] : 1;
        $final = ($offset + $total_reg) - 1;

        $params = array_merge($params, $params);

        $strSql = "WITH RESULTADO AS (" .
                $select .
                $from .
                $where .
                $group . "
                  
                  UNION ALL
                                  " .
                $select .
                $from2 .
                $where .
                $group . ")
                  SELECT * FROM RESULTADO WHERE LINHA BETWEEN " . $offset . " AND " . $final . " ORDER BY ID_PEDIDO_VENDA DESC";


        // EXECUTA QUERY PRINCIPAL PARA CONSULTAR PEDIDOS VENDIDOS
        $result = executeSQL($mainConnection, $strSql, $params);



        $paramsTotal = array_merge($paramsTotal, $paramsTotal, $paramsTotal, $paramsTotal);
        // ignora pedidos pais (assinaturas)
        $query = "SELECT
                      COUNT(1) AS QUANTIDADE,
                      SUM(IPV.VL_TAXA_CONVENIENCIA) AS TOTAL_SERVICO,
                      SUM (IPV.VL_UNITARIO) AS TOTAL_PEDIDO,
                      0 AS PEDIDO_PAI
                  FROM 
                      MW_PEDIDO_VENDA PV
                                          LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";

        if (isset($join)) {
            $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
        }
        $query .= $where . " AND (PV.IN_PACOTE <> 'S' OR PV.IN_PACOTE IS NULL)

                          UNION ALL

                          SELECT
                                  COUNT(1) AS QUANTIDADE,
                                  SUM(IPV.VL_TAXA_CONVENIENCIA) AS TOTAL_SERVICO,
                                  SUM (IPV.VL_UNITARIO) AS TOTAL_PEDIDO,
                                  0 AS PEDIDO_PAI
                          FROM
                                  MW_PEDIDO_VENDA PV
                                  INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
        if (isset($join)) {
            $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
        }
        $query .= $where . " AND (PV.IN_PACOTE <> 'S' OR PV.IN_PACOTE IS NULL)";

        // apenas pedidos pais (assinaturas)
        $query .= " UNION ALL

                      SELECT
                              COUNT(1) AS QUANTIDADE,
                              SUM(IPV.VL_TAXA_CONVENIENCIA) AS TOTAL_SERVICO,
                              SUM (IPV.VL_UNITARIO) AS TOTAL_PEDIDO,
                              1 AS PEDIDO_PAI
                      FROM 
                              MW_PEDIDO_VENDA PV
                              LEFT JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";

        if (isset($join)) {
            $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
        }
        $query .= $where . " AND PV.IN_PACOTE = 'S'

                          UNION ALL

                          SELECT
                                  COUNT(1) AS QUANTIDADE,
                                  SUM(IPV.VL_TAXA_CONVENIENCIA) AS TOTAL_SERVICO,
                                  SUM (IPV.VL_UNITARIO) AS TOTAL_PEDIDO,
                                  1 AS PEDIDO_PAI
                          FROM
                                  MW_PEDIDO_VENDA PV
                                  INNER JOIN MW_ITEM_PEDIDO_VENDA_HIST IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA ";
        if (isset($join)) {
            $query .= "INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = PV.ID_CLIENTE ";
        }
        if (isset($join2)) {
            $query .= "INNER JOIN MW_CLIENTE CL ON CL.ID_CLIENTE = PV.ID_CLIENTE AND PV.ID_USUARIO_CALLCENTER IS NULL ";
        }
        if (isset($join3)) {
            $query .= "LEFT JOIN MW_USUARIO U ON U.ID_USUARIO=PV.ID_USUARIO_CALLCENTER ";
        }
        if (isset($join4)) {
            $query .= "   LEFT JOIN MW_APRESENTACAO A ON IPV.ID_APRESENTACAO = A.ID_APRESENTACAO
                          LEFT JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO ";
        }
        $query .= $where . " AND PV.IN_PACOTE = 'S'";


        //Executa query para somar total de ingressos e calcular valor total dos serviços
        $result2 = executeSQL($mainConnection, $query, $paramsTotal);

        $total['QUANTIDADE'] = 0;
        $total['TOTAL_SERVICO'] = 0;
        $total['TOTAL_PEDIDO'] = 0;
        while ($rs = fetchResult($result2)) {
            $total['QUANTIDADE'] += $rs['PEDIDO_PAI'] ? 0 : $rs['QUANTIDADE'];
            $total['TOTAL_SERVICO'] += $rs['TOTAL_SERVICO'];
            $total['TOTAL_PEDIDO'] += $rs['PEDIDO_PAI'] ? 0 : $rs['TOTAL_PEDIDO'];
        }
    }
?>
    <script type="text/javascript" src="../javascripts/jquery.ui.datepicker-pt-BR.js"></script>
    <script type="text/javascript" src="../javascripts/jquery.combobox-autocomplete.js"></script>
    <script type="text/javascript" src="../javascripts/simpleFunctions.js"></script>
    <script>

        function executechangewith(id) {
            if (id=="" || id == 'undefined') {
                return;
            }
            var pagina = '<?php echo $pagina; ?>';
            const cboPeca = $('#cboPeca');
            const id_evento = '<?php echo $_GET["nm_evento"] ?>';
            $.ajax({
                url: pagina+'?action=cboPeca&cboTeatro=' + id + "&id_evento="+id_evento
            }).done(function(html){
                cboPeca.html(html);
            });
        }
        function localchange(obj) {
            var pagina = '<?php echo $pagina; ?>';
            const aux = $(obj);
            executechangewith(aux.val());
        }

        $(function() {
            var pagina = '<?php echo $pagina; ?>';

            executechangewith(<?php echo $_GET["id_base"]; ?>);

            $('.button').button();
            //$(".datepicker").datepicker();
            $('input.datepicker').datepicker({
                changeMonth: true,
                changeYear: true,
                onSelect: function(date, e) {
                    if ($(this).is('#dt_inicial')) {
                        $('#dt_final').datepicker('option', 'minDate', $(this).datepicker('getDate'));
                    }
                }
            }).datepicker('option', $.datepicker.regional['pt-BR']);

            $("#btnRelatorio").click(function(){
                if(!verificaCPF($('#cd_cpf').val()))
                {
                    $.dialog({title: 'Alerta...', text: 'CPF inválido.'});
                    return;
                }
                
                if($('#situacao').val() == "V"){
                    $.dialog({title: 'Alerta...', text: 'Selecione a situação'});
                    return;
                }
                if($('#cboLocal').val() == ""){
                    $.dialog({title: 'Alerta...', text: 'Selecione o local'});
                    return;
                }
                

                document.location = '?p=' + pagina.replace('.php', '') + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final='+ $("#dt_final").val() + '&situacao=' + $("#situacao").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() +'&nm_evento=' + $("#cboPeca").val() +'&id_base=' + $("#cboLocal").val();
            });

            $('#tabPedidos tr:not(.ui-widget-header, .estorno)').hover(function() {
                $(this).addClass('ui-state-hover').next('.estorno').addClass('ui-state-hover');
            }, function() {
                $(this).removeClass('ui-state-hover').next('.estorno').removeClass('ui-state-hover');
            });

            $('#tabPedidos tr:not(.ui-widget-header, .total, .estorno)').click(function() {
                $('loadingIcon').fadeIn('fast');
                var $this = $(this),
                url = $this.find('a').attr('destino');
                $.ajax({
                    url: url,
                    success: function(data) {
                        $('#tabPedidos').find('.itensDoPedido').hide();

                        if ($this.next('.estorno').length > 0) {
                            $this.next('.estorno').after('<tr class="itensDoPedido"><td colspan="11">' + data + '</td></tr>');
                        } else {
                            $this.after('<tr class="itensDoPedido"><td colspan="11">' + data + '</td></tr>');
                        }
                    },
                    complete: function() {
                        $('loadingIcon').fadeOut('slow');
                    }
                });
            });

            $("#controle").change(function(){
                document.location = '?p=' + pagina.replace('.php', '') + '&controle=' + $("#controle").val() + '&dt_inicial=' + $("#dt_inicial").val() + '&dt_final=' + $("#dt_final").val() + '&situacao=' + $("#situacao").val() + '&nm_cliente=' + $("#nm_cliente").val() + '&cd_cpf=' + $("#cd_cpf").val() + '&num_pedido=' + $("#num_pedido").val() + '&nm_operador=' + $("#nm_operador").val() + '&nm_evento=' + $("#evento").val() + '';
            });

            $("a.reemail").click(function(e){
                e.preventDefault();

                $('#reenvio input').val($.getUrlVar('emailAtual', $(this).attr('href')));
                $('#reenvio').attr('action', $(this).attr('href'));

                $('#reenvio').dialog('open');
            });

            $('#reenvio').dialog({
                autoOpen: false,
                modal: true,
                width: 400,
                height: 200,
                buttons: {
                    'Reenviar': function() {
                      var $this = $(this),
                          email = $this.find('input[name="emailInformado"]'),
                          email_txt = email.val(),
                          email_pattern = /\b[\w\.-]+@[\w\.-]+\.\w{2,4}\b/i,
                          valido = true;

                      if (!email_pattern.test(email_txt)) {
                        email.css({'border-color':'#F55'});
                        valido = false;
                      } else email.css({'border-color':'initial'});

                      if (valido) {
                        $("#loadingIcon").fadeIn('fast');

                        $.ajax({
                          url: $this.attr('action'),
                          type: 'post',
                          data: $this.serialize(),
                          success: function(data) {
                            if (data == 'ok') {
                              $('#reenvio').dialog('close');
                              $.dialog({title: 'Sucesso...', text: 'E-mail reenviado.'});
                            } else {
                              $.dialog({title: 'Alerta...', text: data});
                            }
                          }
                        });
                      }
                    }
                }
            });

            $.ajax({
              url: pagina + '?action=load_evento_combo&nm_evento=<?php echo $_GET['nm_evento']; ?>',
              success: function(data) {
                $('#evento').html(data).combobox();
              }
            });
        });    
    </script>
    <style type="text/css">
        #paginacao{
            width: 100%;
            text-align: center;
            margin-top: 10px;
        }
    </style>
    <h2>Consulta de Pedidos</h2>
<?php
    $mes = date("m") - 1;
?>
    <table>
        <tr>
            <td>Local</td>
            <td><?php echo comboTeatroPorUsuario2('cboLocal', $_SESSION["admin"], $_GET["id_base"],"localchange(this)"); ?></td>
            <td>Nome do Cliente</td>
        <td><input size="40" type="text" value="<?php echo (isset($_GET["nm_cliente"])) ? $_GET["nm_cliente"] : "" ?>" id="nm_cliente" name="nm_cliente" /></td>
        </tr>
      <tr>
        <td>Pedido nº</td>
        <td><input size="10" type="text" value="<?php echo (isset($_GET["num_pedido"])) ? $_GET["num_pedido"] : "" ?>" id="num_pedido" name="num_pedido" /></td>
        <td>CPF</td>
        <td><input type="text" value="<?php echo (isset($_GET["cd_cpf"])) ? $_GET["cd_cpf"] : "" ?>" id="cd_cpf" name="cd_cpf" maxlength="13" /></td>
      </tr>
      <tr>
        <td>Data Inicial</td>
        <td><input type="text" value="<?php echo (isset($_GET["dt_inicial"])) ? $_GET["dt_inicial"] : date("d/m/Y") ?>" class="datepicker" id="dt_inicial" readonly name="dt_inicial" /></td>
        <td>Data Final</td>
        <td><input type="text" class="datepicker" value="<?php echo (isset($_GET["dt_final"])) ? $_GET["dt_final"] : date("d/m/Y") ?>" id="dt_final" name="dt_final" readonly/></td>
      </tr>
      <tr>
        <td>Situação</td>
        <td><?php echo combosituacao('situacao', $_GET["situacao"]); ?></td>
        <td>Nome do Evento</td>
        <td><select name="cboPeca" class="inputStyle" id="cboPeca"><option value="">...</option></select></td>
        <td align="center"><input type="submit" class="button" id="btnRelatorio" value="Buscar" /></td>
        <td align="center">
          <?php if (isset($result) && hasRows($result)) { ?>
              <a class="button" href="gerarExcel.php?dt_inicial=<?php echo $_GET["dt_inicial"]; ?>&dt_final=<?php echo $_GET["dt_final"]; ?>&situacao=<?php echo $_GET["situacao"]; ?>&num_pedido=<?php
              if (isset($_GET["num_pedido"])) {
                  echo $_GET["num_pedido"];
              } else {
                  echo "";
              } ?>&nm_cliente=<?php echo $_GET["nm_cliente"]; ?>&nm_operador=<?php echo $_GET["nm_operador"] ?>&cd_cpf=<?php echo $_GET["cd_cpf"]; ?>&nm_evento=<?php echo $_GET["nm_evento"]; ?>&ds_evento=<?php echo $dsEvento; ?>">Exportar Excel</a>
          <?php } ?>
        </td>
      </tr>
    </table><br/>

<!-- Tabela de pedidos -->
<table class="ui-widget ui-widget-content" id="tabPedidos">
    <thead>
        <tr class="ui-widget-header">
            <th style="text-align: center; width: 90px;">Visualizar</th>
            <th>Pedido nº</th>
            <th>Operador</th>
            <th>Data do Pedido</th>
            <th>IP</th>
            <th>Cliente e Telefone</th>
            <th>Valor total</th>
            <th>Qtde Ingressos</th>
            <th>Situação</th>
            <th>Forma de Entrega</th>
            <th>Ações</th>
        </tr>
    </thead>
    <tbody>
        <?php
               if (isset($result)) {
                   while ($rs = fetchResult($result)) {
        ?>
                       <tr>
                           <td style="text-align: center;"><a style="cursor: pointer;" destino="listaItens.php?pedido=<?php echo $rs['ID_PEDIDO_VENDA']; ?>&evento=<?php echo $_GET["nm_evento"]; ?>">+</a></td>
                           <td><?php echo $rs['ID_PEDIDO_VENDA']; ?></td>
                           <td>
                <?php
                       if (empty($rs['DS_NOME'])) {
                           echo 'Web';
                       } else {
                           echo $rs['DS_NOME'];
                       }
                ?>
                   </td>
                   <td><?php echo $rs['DT_PEDIDO_VENDA'] ?></td>
                   <td><?php echo $rs['ID_IP'] ?></td>
                   <td><?php echo utf8_encode2($rs['CLIENTE'] . " " . $rs['DS_SOBRENOME']) . "<br/>" . $rs['DS_DDD_TELEFONE'] . " " . $rs['DS_TELEFONE'] . " " . $rs['DS_DDD_CELULAR'] . " " . $rs['DS_CELULAR']; ?></td>
                   <td><?php echo number_format($rs['TOTAL_UNIT'], 2, ",", "."); ?></td>
                   <td><?php echo $rs['QUANTIDADE']; ?></td>
                   <td><?php echo comboSituacao('situacao', $rs['IN_SITUACAO'], false); ?></td>
                   <td><?php echo comboFormaEntrega($rs['IN_RETIRA_ENTREGA']); ?></td>
                   <td>
                        <?php if ($rs['IN_SITUACAO'] == 'F') { ?>
                            <a href="<?php echo $pagina; ?>?action=reemail&emailAtual=<?php echo $rs['CD_EMAIL_LOGIN']; ?>&pedido=<?php echo $rs['ID_PEDIDO_VENDA']; ?>" class="reemail">Reenvio de e-mail</a>
                        <?php } ?>
                   </td>
               </tr>
        <?php
                        if ($rs['IN_SITUACAO'] == 'S') {
                          if($rs['DT_HORA_CANCELAMENTO'] != NULL || $rs['DT_HORA_CANCELAMENTO'] != "")
                          {
                            $dt_estorno = date_format($rs['DT_HORA_CANCELAMENTO'], 'd/m/Y').' às '.date_format($rs['DT_HORA_CANCELAMENTO'], 'H:i').'hs';
                          }
                          else
                          {
                            $dt_estorno = 'Sem informação';
                          }
        ?>

                    <tr class="estorno">
                        <td colspan="4">Estornado pelo usuário: <?php echo comboAdmins('admin', $rs['ID_USUARIO_ESTORNO'], false); ?></td>
                        <td colspan="4">Motivo: <?php echo $rs['DS_MOTIVO_CANCELAMENTO']; ?></td>
                        <td colspan="3">Data de estorno: <?php echo $dt_estorno ?></td>
                    </tr>
        <?php
                        }
                   }
        ?>
                   <tr class="total">
                       <td><strong>Qtd. Pedidos</strong></td>
                       <td><?php echo $tr['QTD_PEDIDOS'] - $tr['QTD_PEDIDOS_PAIS']; ?></td>
                       <td></td>
                       <td align="right"><strong>Total Geral</strong></td>
                       <td><?php echo number_format($total['TOTAL_PEDIDO'] + $total['TOTAL_SERVICO'], 2, ",", "."); ?></td>
                       <td align="right"><strong>Ingressos</strong></td>
                       <td><?php echo number_format($total['TOTAL_PEDIDO'], 2, ",", "."); ?></td>
                       <td><?php echo $total['QUANTIDADE']; ?></td>
                       <td colspan="2"><strong>Taxa de Serviço</strong></td>
                       <td><?php echo number_format($total['TOTAL_SERVICO'], 2, ",", "."); ?></td>
                   </tr>
        <?php
               }
        ?>
           </tbody>
       </table>
       <div id="paginacao">
    <?php
           if ($tr['QTD_PEDIDOS']) {
               //paginacao($pc, $intervalo, $tp, true);
               $link = "?p=" . basename($pagina, '.php') . "&dt_inicial=" . $_GET["dt_inicial"] . "&dt_final=" . $_GET["dt_final"] . "&situacao=" . $_GET["situacao"] . "&num_pedido=" . $_GET["num_pedido"] . "&nm_cliente=" . $_GET["nm_cliente"] . "&nm_operador=" . $_GET["nm_operador"] . "&cd_cpf=" . $_GET["cd_cpf"] . "&nm_evento=" . $_GET["nm_evento"] . "&controle=" . $total_reg . "&bar=2&baz=3&offset=";
               //$link = "?p=listaMovimentacao&dt_inicial=" . $_GET["dt_inicial"] . "&dt_final=" . $_GET["dt_final"] . "&situacao=" . $_GET["situacao"] . "&controle=" . $total_reg . "&bar=2&baz=3&offset=";
               Paginator::paginate($offset, $tr['QTD_PEDIDOS'], $total_reg, $link, true);
           }
?>
       </div>
        <form action="<?php echo $pagina; ?>?action=reemail" id="reenvio" title="Reenvio de e-mail">
            <p>O email de Confirmação do Pedido será reenviado para o email abaixo.</p>
            <p>Clique no botão "Reenviar" para confirmar o reenvio.</p>
            <br/>
            <p><input type="text" name="emailInformado" maxlength="100" style="width:100%" /></p>
        </form>

<?php
           }
?>
