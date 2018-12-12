<?php

/**
 * Conjunto de funções de uso geral.
 * @author Edicarlos Barbosa <edicarlosbarbosa@gmail.com>
 * @since 23-08-2011 14:00
 * @version 1.0.0
 * @license GNU GENERAL PUBLIC LICENSE
 */
function tratarData($data) {
  if ($data != "")
    $data = explode("/", $data);
  else
    $data = explode(date('d/m/Y'));
  return $data[2] . $data[1] . $data[0];
}

function formatarValor($value) {
  return (is_null($value)) ? '' : $value;
}

function formatarValorNumerico($value) {
  return (is_null($value)) ? '' : number_format($value, 2, ',', '.');
}

function gerarNotacaoIntervalo($arrayNums) {
  $str = '';
  $lastNum = '';

  foreach ($arrayNums as $i) {
    if ($lastNum === '') {
      $str .= $i;
    } else {
      if ($i === $lastNum + 1 && substr($str, -1) !== '-') {
        $str .= '-';
      } else if ($i !== $lastNum + 1 && substr($str, -1) === '-') {
        $str .= $lastNum . ', ' . $i;
      } else if ($i !== $lastNum + 1) {
        $str .= ', ' . $i;
      }
    }
    $lastNum = $i;
  }
  if (substr($str, -1) === '-')
    $str .= $lastNum;

  return $str;
}

function retornaData($Data) {
  if (!checkdate($Data))
    return "";
  else {
    $dia = $Data;
    $mes = $Data;
    $ano = $Data;
    return $ano . $mes . $dia;
  }
}

function textToDate($date) {
  $dia = substr($date, 6, 2);
  $mes = substr($date, 4, 2);
  $ano = substr($date, 0, 4);
  return $dia . "/" . $mes . "/" . $ano;
}

function DiaSemana($Data) {
  switch ($Data) {
    case 1:
      $DiaSemana = "SEGUNDA-FEIRA";
      break;
    case 2:
      $DiaSemana = "TERÇA-FEIRA";
      break;
    case 3:
      $DiaSemana = "QUARTA-FEIRA";
      break;
    case 4:
      $DiaSemana = "QUINTA-FEIRA";
      break;
    case 5:
      $DiaSemana = "SEXTA-FEIRA";
      break;
    case 6:
      $DiaSemana = "SÁBADO";
      break;
    case 7:
      $DiaSemana = "DOMINGO";
      break;
  }
  return $DiaSemana;
}

function formatarConteudoVazio($valor) {
  return empty($valor) ? '-' : $valor;
}

function search_value_presentation($apresentacoes, $date, $canal) {
  $resultado = array(0, 0);
  foreach ($apresentacoes as $key => $apresentacao) {
    $dateDb = $apresentacao->data . $apresentacao->hora;
    if ((strcmp($dateDb, $date) == 0) && (strcmp($apresentacao->canal, $canal) == 0)) {
      $resultado[0] = $apresentacao->qtde;
      $resultado[1] = $apresentacao->valor;
    }
  }
  return $resultado;
}

/**
 * Verifica se o valor é diferente de vazio.
 * @param String $value
 * @return String
 */
function chk_value($value) {
  if ((isset($value)) && (!empty($value))) {
    return $value;
  } else {
    return "";
  }
}

/**
 * Verifica se o valor é diferente de vazio. <br/>
 * Porém retorna uma string "null" caso seja.
 * @param String $value
 * @return String
 */
function chk_null($value) {
  if ((isset($value)) && (!empty($value))) {
    return $value;
  } else {
    return "null";
  }
}

function arrayCopy(array $array) {
  $result = array();
  foreach ($array as $key => $val) {
    if (is_array($val)) {
      $result[$key] = arrayCopy($val);
    } elseif (is_object($val)) {
      $result[$key] = clone $val;
    } else {
      $result[$key] = $val;
    }
  }
  return $result;
}

/**
 * Altera a formatação da data para o padrão "mm/dd/yyyy".
 * @param String $date
 * @return String
 */
function getDateF($date) {
  if ($date != "") {
    $data = explode("/", $date);
  } else {
    $data = explode("/", date('y/m/d'));
  }
  $retorno = $data[1] . "/" . $data[2] . "/" . $data[0];
  return $retorno;
}

/**
 * Remove os caracteres "." e "-" do valor passado por parametro.<br/> 
 * @param String $doc
 * @return String
 */
function cleanDocuments($doc) {
  return str_replace(array(".", "-"), "", $doc);
}

function logQuery($strQuery, array $params) {
  $result = substr($strQuery, 0, strpos($strQuery, "?"));
  foreach ($params as $key => $value) {
    if (is_string($value))
      $result .= "'" . $value . "',";
    else
      $result .= $value . ",";
  }
  return substr($result, 0, strlen($result) - 1);
}

function getDay($date) {
  if ($date != '--') {
    $data = $date->format("d/m/Y");
    $datas = explode("/", $data);
    $weekDay = date("N", mktime(0, 0, 0, $datas[1], $datas[0], $datas[2]));
    return DiaSemana($weekDay);
  } else {
    return "TODOS";
  }
}

function generateCodVenda($conn){
    $pSenReserva = "";
    $pCodCaixa = 255;

    while($pSenReserva == ""){
        //Codifica o Código do Caixa
        $arr = array("","A", "K", "B", "Z", "C", "X", "D", "W", "E", "Y", "F", "H");
        $pSenReserva = ($pCodCaixa == 0) ? "O" : $arr[substr($pCodCaixa, 0, 1)];
        $pSenReserva .= ($pCodCaixa == 0) ? "O" : $arr[substr($pCodCaixa, 1, 1)];
        //print "Caixa:".$pSenReserva."<br>";

        //CODIFICA O ANO
        $arr = array("","A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L");
        $pSenReserva .= $arr[date("y")];
        //print "Ano:".$pSenReserva."<br>";

        //CODIFICA O MES
        $arr = array("","A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "K", "L");
        $pSenReserva .= $arr[date("n")];
        //print "Mes:".$pSenReserva."<br>";

        //CODIFICA O DIA
        $arr = array("","A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "H", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "X", "W", "Y", "Z", "9", "7", "5", "3", "1");
        $pSenReserva .= $arr[date("d")];
        //print "Dia:".$pSenReserva."<br>";

        //CODIFICA A HORA
        $arr = array("","A", "B", "C", "D", "E", "F", "G", "H", "I", "J", "H", "L", "M", "N", "O", "P", "Q", "R", "S", "T", "U", "V", "X", "W");
        $pSenReserva .= (date("H") == 0) ? "O" : $arr[date("H")];
        //print "Hora:".$pSenReserva."<br>";

        //CODIFICA O MINUTO
        $arr = array("","A", "B", "C", "D", "E");
        $pSenReserva .= (substr(date("i"),0,1) == 0) ? "O" : $arr[substr(date("i"),0,1)];
        $arr = array("","A", "B", "C", "D", "E", "F", "G", "H", "I");
        $pSenReserva .= (substr(date("i"),1,1) == 0) ? "O" : $arr[substr(date("i"),1,1)];
        //print "Minuto:".$pSenReserva."<br>";

        //CODIFICA O SEGUNDO
        $arr = array("","A", "B", "C", "D", "E", "F", "G", "H", "I");
        $pSenReserva .= (substr(date("s"),0,1) == 0) ? "O" : $arr[substr(date("s"),0,1)];
        $arr = array("","A", "B", "C", "D", "E", "F", "G", "H", "I");
        $pSenReserva .= (substr(date("s"),1,1) == 0) ? "O" : $arr[substr(date("s"),1,1)];
        //print "Segundo:".$pSenReserva."<br>";
        
        //VERIFCA SE O CODIGO JA EXISTE
        $query = "SP_COD_CON002 ". $pSenReserva;
        $pRs = executeSQL($conn, $query, array(), true);
        if(hasRows($rs)){
            $pSenReserva = "";
        }else{
          return $pSenReserva;
        }
    }
}

?>
