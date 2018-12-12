<?php

require_once('functions.php');

/**
 * Classe para manter histórico de Log das aplicações
 * @author Jacqueline Barbosa <jacqueline.barbosa@cc.com.br>
 * @since 26/05/2011 10:05
 * @version 1.1.0 $03/06/2011 10:17
 * 
 */
class Log {

    private $dataOcorrencia;
    private $usuario;
    private $funcionalidade;
    private $log;
    private $parametros;

    /**
     * Classe para manter histórico de Log das aplicações
     * @param Integer $Pusuario Identificador do usuário no sistema
     * @param String $pFunc [optional]<br> Descrição da Funcionalidade
     * @param String $pLog [optional]<br> Descrição da ocorrência, e.g. SQL executado
     * @param mixed $pParam [optional]<br> Array de parametros usados na Query SQL
     * 
     */
    function __construct($Pusuario, $pFunc = null, $pLog = null, $pParam = null) {
        $this->dataOcorrencia = date("Y-m-d H:i:s");
        if (empty($Pusuario))
            throw new Exception('Identificador de usuário inválido.', 001);
        $this->usuario = $Pusuario;

        //Atributos adicionais para o objeto log
        $params = array($pFunc => "funcionalidade",
            $pLog => "log",
            $pParam => "parametros");

        foreach ($params as $key => $value) {
            if ($key != null)
                $this->__set($value, $key);
        }
    }

    /**
     * Método substitui string pela primeira ocorrência encontrada
     * @param String $search String a ser procurada
     * @param String $replace Nova string a ser substituida
     * @param String String completa onde será feita a busca
     * @return String Retorna a nova string substituida
     * @access private Método somente para uso interno
     */
    private function str_replace_once($search, $replace, $subject) {
        if (($pos = strpos($subject, $search)) !== false) {
            $ret = substr($subject, 0, $pos) . $replace . substr($subject, $pos + strlen($search));
        } else {
            $ret = $subject;
        }
        return($ret);
    }

    /**
     * Método para atribuir valor ao atributo da classe
     * @param String $key Nome do atributo na classe
     * @param mixed $value Valor do atributo a ser adicionado
     */
    function __set($key, $value) {
        if (empty($value) && $key != "parametros")
            throw new Exception($key . ' é inválida.', 002);
        if ($key == "funcionalidade")
            $this->funcionalidade = $value;
        else if ($key == "parametros")
            $this->parametros = $value;
        else if ($key == "log") {
            foreach ($this->parametros as $i => $v) {
                $value = $this->str_replace_once('?', $v, $value);
            }
            $this->log = $value;
        }
    }

    /**
     * Método retorna o valor do atributo
     * @param String $name Nome do atributo da classe
     * @return mixed Retorna o valor do atributo da classe
     */
    function __get($name) {
        return $this->$name;
    }

    /**
     * Salva o registro do log no banco de dados
     * @param resource $conn Ponteiro de conexão com o banco
     * @return boolean retorna true caso tenha salvo o log no banco
     */
    public function save($conn) {
        if (empty($conn)) {
            throw new Exception("O ponteiro de conexão não existe!", 003);
        } else {
            $query = "INSERT INTO ci_middleway.dbo.mw_log_middleway(dt_ocorrencia,
                                                               id_usuario,
                                                               ds_funcionalidade,
                                                               ds_log_middleway)
                                                        VALUES(?, ?, ?, ?)";
            $params = array($this->dataOcorrencia, $this->usuario, utf8_decode($this->funcionalidade), $this->log);
            if (executeSQL($conn, $query, $params))
                return true;
            else
                return false;
        }
    }

}
?>
