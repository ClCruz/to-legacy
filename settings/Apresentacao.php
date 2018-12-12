<?php
/**
 * Description of Canal
 *
 * @author edicarlos.barbosa
 */
class Apresentacao {
    public $canal;
    public $qtde;
    public $valor;
    public $data;
    public $hora;

    public function __construct($canal, $qtde, $valor, $data, $hora) {
      $this->canal = $canal;
      $this->qtde = $qtde;
      $this->valor = $valor;
      $this->data = $data;
      $this->hora = $hora;
    }
}
?>
