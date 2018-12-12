<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
$mainConnection = mainConnection();
$conn = getConnection($_POST['cboTeatro']);

$query = "SELECT 
                case when isnull(tabcliente.Nome, '') = '' then 'N/I' else upper(tabcliente.Nome) end AS Nome,
                tabSalDetalhe.NomObjeto,
                tabSetor.NomSetor,
                tabSala.NomSala,
                tabTipBilhete.TipBilhete,
                tabLancamento.ValPagto,
                tabcliente.CPF,
                tabCliente.RG,
                tabcliente.DDD,
                tabcliente.Telefone,
                CONVERT(VARCHAR(10), tabControleSeqVenda.DatHrEntrada, 103) +' - '+ CONVERT(VARCHAR(8), tabControleSeqVenda.DatHrEntrada, 114) as DatHrEntrada,
                tabControleSeqVenda.statusingresso
        FROM tabLancamento
        INNER JOIN tabTipBilhete
                ON tabLancamento.CodTipBilhete = tabTipBilhete.CodTipBilhete
        INNER JOIN tabApresentacao
                ON tabLancamento.CodApresentacao = tabApresentacao.CodApresentacao
        INNER JOIN tabSalDetalhe
                ON tabLancamento.Indice = tabSalDetalhe.Indice
        INNER JOIN tabSala
                ON tabApresentacao.CodSala = tabSala.CodSala
        INNER JOIN tabPeca
                ON tabApresentacao.CodPeca = tabPeca.CodPeca
        INNER JOIN tabTipLancamento
                ON tabLancamento.CodTipLancamento = tabTipLancamento.CodTipLancamento
        INNER JOIN tabSetor
                ON tabSalDetalhe.CodSala = tabSetor.CodSala AND dbo.tabSalDetalhe.CodSetor = dbo.tabSetor.CodSetor
        LEFT JOIN tabhiscliente
                ON tabhiscliente.numlancamento = tabLancamento.numlancamento AND tabhiscliente.codtipbilhete = tabLancamento.codtipbilhete AND tabhiscliente.codtiplancamento = tabLancamento.codtiplancamento AND tabhiscliente.codapresentacao = tabLancamento.codapresentacao AND tabhiscliente.indice = tabLancamento.indice
        LEFT JOIN tabcliente
                ON tabCliente.Codigo = tabHisCliente.Codigo
        INNER JOIN tabusuario
                ON tabusuario.codusuario = tablancamento.CodUsuario
        INNER JOIN tabforpagamento
                ON tabforpagamento.codforpagto = tablancamento.CodForPagto
        LEFT JOIN tabDetPagamento
                ON tabLancamento.NumLancamento = tabDetPagamento.NumLancamento
        LEFT JOIN tabControleSeqVenda 
                ON tabControleSeqVenda.CodApresentacao = tabApresentacao.CodApresentacao
                        AND tabControleSeqVenda.Indice = tabLancamento.Indice
        WHERE 
                ( (CONVERT(VARCHAR(10),DatApresentacao,112) BETWEEN ? AND ?))
                AND ( HorSessao BETWEEN ? AND ?)
                AND ( tabApresentacao.CodPeca = ?)
                AND ( tabLancamento.CodTipLancamento  in (1, 4) )
                AND	( not exists (Select 1 from tabLancamento bb
                                                        where tabLancamento.numlancamento = bb.numlancamento
                                                        and tabLancamento.codtipbilhete	= bb.codtipbilhete
                                                        and bb.codtiplancamento = 2
                                                        and tabLancamento.codapresentacao = bb.codapresentacao
                                                        and tabLancamento.indice = bb.indice) )
        ORDER BY tabCliente.Nome";
$params = array($_POST['cboApresentacao'], $_POST["cboApresentacao"], $_POST['cboHorario'], $_POST['cboHorario'], $_POST['cboPeca']);
$result = executeSQL($conn, $query, $params);

if (hasRows($result)) {
    while ($rs = fetchResult($result)) {        
        $json[] = array(
            "DSNOME" => utf8_encode2($rs['Nome']),
            "DSLUGAR" => $rs['NomObjeto'],
            "DSSETOR" => $rs['NomSetor'],
            "DSSALA" => $rs['NomSala'],
            "DSTIPOBILHETE" => $rs['TipBilhete'],
            "VALPAGTO" => "R$ " . number_format($rs['ValPagto'], 2, ',', '.'),
            "CDCPF" => $rs['CPF'],
            "CDRG" => $rs['RG'],
            "CDTELEFONE" => $rs['DDD'] ." ". $rs['Telefone'],
            "DATHRENTRADA" => $rs['DatHrEntrada'],
            "STATUS" => $rs['statusingresso']
        );        
    }
} else {
    $json = array("resultado" => "Nenhum acesso encontrado.");
}
echo json_encode($json);
?>