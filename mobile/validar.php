<?php

require_once('../settings/settings.php');
require_once('../settings/functions.php');
require_once('../settings/Log.class.php');
$mainConnection = mainConnection();
session_start();

function validateTicket($rs) {

        // // data confere?
    if (isset($_POST['cboApresentacao']) && $_POST['cboApresentacao'] != $rs["checkPresentation"]) {
        log_acesso("DATA INVÁLIDA", $mainConnection, $_POST['admin']);
        echo json_encode(array('class' => 'falha','mensagem' => "Data do ingresso inválida para a apresentação.\nIngresso válido para: ".$rs["date"]));
        die();
    }
    if (isset($_POST['cboHorario']) && $_POST['cboHorario'] != $rs["checkHour"]) {
        log_acesso("HORA INVÁLIDA", $mainConnection, $_POST['admin']);
        echo json_encode(array('class' => 'falha','mensagem' => "Este ingresso pertence a outro horário.\nIngresso válido para: ".$rs["checkHour"]));
        die();
    }
    if (isset($_POST['cboPeca']) && $_POST['cboPeca'] != $rs['CodPeca']) {
        log_acesso("EVENTO INVÁLIDO", $mainConnection, $_POST['admin']);
        echo json_encode(array('class' => 'falha','mensagem' => 'Este ingresso pertence a outro evento.'));
        die();
    }
    if (isset($_POST['cboSetor']) && $_POST["cboSetor"]!=0 && $_POST['cboSetor'] != $rs['codSala']) {
        log_acesso("SETOR INVÁLIDO", $mainConnection, $_POST['admin']);
        echo json_encode(array('class' => 'falha','mensagem' => 'Este ingresso pertence a outro setor.'));
        die();
    }
}

function log_acesso($msg, $mainConnection, $admin){
    try {
        $log = new Log($admin);
        $log->__set('funcionalidade', 'Controle de Acesso');
        $log->__set('log', $msg);
        $log->save($mainConnection);
    } catch (Exception $e) {
        //echo $e->getMessage();
    }
}
log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - LEITURA", $mainConnection, $_POST['admin']);
if (isset($_POST['codigo'])) { /* ------------ CHECAR BILHETE ------------ */
    
    if (strlen($_POST['codigo']) != 21) {
        log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - CÓD. INVÁLIDO", $mainConnection, $_POST['admin']);
        echo json_encode(array(
            'class' => 'falha',
            'mensagem' => "Código inválido."
        ));
        die();
    }

    $conn = getConnection($_POST['cboTeatro']);


    $query = "SELECT TOP 1 csv.NUMSEQ
    ,csv.CODAPRESENTACAO
    ,csv.INDICE
    ,csv.STATUSINGRESSO
    ,csv.DATHRENTRADA
    ,csv.DATHRSAIDA
    ,CONVERT(VARCHAR(4),DATEPART(year,a.DatApresentacao))+RIGHT('00'+CONVERT(VARCHAR(2),DATEPART(month,a.DatApresentacao)),2)+RIGHT('00'+CONVERT(VARCHAR(2),DATEPART(day,a.DatApresentacao)),2) checkPresentation
    ,CONVERT(VARCHAR(10), a.DatApresentacao, 103) [date]
    ,a.CodApresentacao
    ,a.HorSessao checkHour
    ,a.CodPeca
    ,a.CodSala
    FROM tabControleSeqVenda csv
    INNER JOIN tabApresentacao a ON csv.CodApresentacao = a.CodApresentacao
    INNER JOIN tabPeca p ON a.CodPeca=p.CodPeca
    WHERE csv.codbar=?
    ORDER BY numseq DESC";
    $params = array($_POST['codigo']);
    $result = executeSQL($conn, $query, $params);

    if (hasRows($result)) {
        // pode retornar 2 linhas no caso de complemento de ingressos, mas como sao o mesmo ingresso podem ser tratados como 1 so
        if ($_POST['sentido'] == 'entrada') {
		
			while ($rs = fetchResult($result)) {
                validateTicket($rs);
                // die("passou");
				if ($rs['STATUSINGRESSO'] == 'L') {
					$query = "UPDATE TABCONTROLESEQVENDA SET 	DATHRENTRADA = GETDATE(), STATUSINGRESSO = 'U'
							  WHERE NUMSEQ = ? AND CODAPRESENTACAO =? AND INDICE = ?";
					$params = array($rs['NUMSEQ'], $rs['CODAPRESENTACAO'], $rs['INDICE']);
					executeSQL($conn, $query, $params);

                    $query = "SELECT TOP 1 ISNULL(ci.Nome, cli.ds_nome + ' ' + cli.ds_sobrenome) AS cliente
                              FROM tabControleSeqVenda csv
                              INNER JOIN tabLugSala ls
                                  ON ls.CodApresentacao = csv.CodApresentacao AND ls.Indice = csv.Indice
                              LEFT JOIN CI_MIDDLEWAY..mw_item_pedido_venda ipv
                                  ON ipv.CodVenda = ls.CodVenda COLLATE Latin1_General_CI_AS AND ipv.Indice = ls.Indice
                              LEFT JOIN CI_MIDDLEWAY..mw_pedido_venda pv
                                  ON pv.id_pedido_venda = ipv.id_pedido_venda
                              LEFT JOIN CI_MIDDLEWAY..mw_cliente cli
                                  ON cli.id_cliente = pv.id_cliente
                              LEFT JOIN tabComprovante c
                                  ON c.CodVenda = ls.CodVenda AND c.CodApresentacao = ls.CodApresentacao
                              LEFT JOIN tabCliente ci
                                  ON ci.Codigo = c.CodCliente
                              WHERE csv.CodApresentacao = ? AND csv.Indice = ? AND csv.numseq = ?";
                    $params = array($rs['CODAPRESENTACAO'], $rs['INDICE'], $rs['NUMSEQ']);
                    $rs = executeSQL($conn, $query, $params, true);
                    $cliente = $rs['cliente'];
                    log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - ENTRADA AUTORIZADA", $mainConnection, $_POST['admin']);
					$retorno = array('class' => 'sucesso', 'mensagem' => "$cliente\nAcesso autorizado.");
				} elseif ($rs['STATUSINGRESSO'] == 'U') {
                    log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - REUTILIZADO ENTRADA INVÁLIDA", $mainConnection, $_POST['admin']);
					$retorno = array('class' => 'falha', 'mensagem' => "Este ingresso já foi processado em " . $rs['DATHRENTRADA']->format("d/m/Y H:i:s") . ".\nAcesso não permitido.");
				} elseif ($rs['STATUSINGRESSO'] == 'E') {
					log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - ESTORNADO ENTRADA INVÁLIDA", $mainConnection, $_POST['admin']);
                    $retorno = array('class' => 'falha', 'mensagem' => "Ingresso estornado.\nAcesso não permitido.");
				} else {
                    log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - DESCONHECIDO ENTRADA INVÁLIDA", $mainConnection, $_POST['admin']);
					$retorno = array('class' => 'falha', 'mensagem' => "Ingresso com status desconhecido.\nAcesso não permitido.");
				}
			}
		} elseif ($_POST['sentido'] == 'saida') {
				while ($rs = fetchResult($result)) {
                    validateTicket($rs);
					if ($rs['STATUSINGRESSO'] == 'L') {
						log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - ENTRADA NÃO REGISTRADA SAÍDA INVÁLIDA", $mainConnection, $_POST['admin']);
                        $retorno = array('class' => 'falha', 'mensagem' => "Entrada não Registrada.\nOperação de Saída não realizada.");
					} elseif ($rs['STATUSINGRESSO'] == 'U') {
						if ($rs['DATHRSAIDA'] != NULL) {
                            log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - REUTILIZADO SAÍDA INVÁLIDA", $mainConnection, $_POST['admin']);
							$retorno = array('class' => 'falha', 'mensagem' => "Saída já foi registrada.\nOperação em duplicidade não permitida.");
						} else {
							$query = "UPDATE TABCONTROLESEQVENDA SET
										DATHRSAIDA = GETDATE()
										WHERE NUMSEQ = ?
										AND CODAPRESENTACAO =?
										AND INDICE = ?";
							$params = array($rs['NUMSEQ'], $rs['CODAPRESENTACAO'], $rs['INDICE']);
							executeSQL($conn, $query, $params);
                            log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - SAÍDA AUTORIZADA", $mainConnection, $_POST['admin']);
							$retorno = array('class' => 'sucesso', 'mensagem' => 'Saída autorizada.');
						}
					} elseif ($rs['STATUSINGRESSO'] == 'E') {
                        log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - ESTORNADO SAÍDA INVÁLIDA", $mainConnection, $_POST['admin']);
						$retorno = array('class' => 'falha', 'mensagem' => "Ingresso estornado.\nAcesso não permitido.");
					} else {
                        log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." - DESCONHECIDO SAÍDA INVÁLIDA", $mainConnection, $_POST['admin']);
						$retorno = array('class' => 'falha', 'mensagem' => "Ingresso com status desconhecido.\nAcesso não permitido.");
					}
				}
			} else {
				while ($rs = fetchResult($result)) {
                    validateTicket($rs);
					if ($rs['STATUSINGRESSO'] == 'L') {
						$query = "UPDATE TABCONTROLESEQVENDA SET 	DATHRENTRADA = GETDATE(), STATUSINGRESSO = 'U'
								  WHERE NUMSEQ = ? AND CODAPRESENTACAO =? AND INDICE = ?";
						$params = array($rs['NUMSEQ'], $rs['CODAPRESENTACAO'], $rs['INDICE']);
						executeSQL($conn, $query, $params);
						$retorno = array('class' => 'sucesso', 'mensagem' => 'Acesso autorizado.');
					} elseif ($rs['STATUSINGRESSO'] == 'U') {
						$retorno = array('class' => 'falha', 'mensagem' => "Este ingresso já foi processado em " . $rs['DATHRENTRADA']->format("d/m/Y H:i:s") . ".\nAcesso não permitido.");
					} elseif ($rs['STATUSINGRESSO'] == 'E') {
						$retorno = array('class' => 'falha', 'mensagem' => "Ingresso estornado.\nAcesso não permitido.");
					} else {
						$retorno = array('class' => 'falha', 'mensagem' => "Ingresso com status desconhecido.\nAcesso não permitido.");
					}
				}
			}
		} else {
            log_acesso("Cód. ". $_POST['codigo'] ." p/ Apre. ". substr($_POST['codigo'], 0, 5) ." ENTRADA INVÁLIDA", $mainConnection, $_POST['admin']);
			$retorno = array('class' => 'falha', 'mensagem' => "Código do ingresso não existe.\nAcesso não permitido.");
		}
    echo json_encode($retorno);
}
?>