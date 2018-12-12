<?php

/**
 * Rotina para gerar codigos de barra padrao 2of5 ou 25.
 * @author Edicarlos Barbosa <edicarlosbarbosa@gmail.com>
 * @since 23-08-2011 10:00
 * @version 1.0.0 $23/08/2011 10:00
 * @license GNU GENERAL PUBLIC LICENSE
 */
class WBarCode {

    //variaveis privadas
    var $_fino;
    var $_largo;
    var $_altura;
    //variaveis publicas
    var $BarCodes = array();
    var $texto;
    var $matrizimg;
    var $f1;
    var $f2;
    var $f;
    var $i;
    var $local;

    function WBarCode($Valor, $local) {
        $this->fino = 1;
        $this->largo = 3;
        $this->altura = 30;
        $this->local = $local;

        if (empty($this->BarCodes[0])) {

            $this->BarCodes[0] = "00110";
            $this->BarCodes[1] = "10001";
            $this->BarCodes[2] = "01001";
            $this->BarCodes[3] = "11000";
            $this->BarCodes[4] = "00101";
            $this->BarCodes[5] = "10100";
            $this->BarCodes[6] = "01100";
            $this->BarCodes[7] = "00011";
            $this->BarCodes[8] = "10010";
            $this->BarCodes[9] = "01010";


            for ($this->f1 = 9; $this->f1 >= 0; $this->f1 = $this->f1 - 1) {
                for ($this->f2 = 9; $this->f2 >= 0; $this->f2 = $this->f2 - 1) {
                    $this->f = $this->f1 * 10 + $this->f2;
                    $this->texto = "";
                    for ($this->i = 1; $this->i <= 5; $this->i = $this->i + 1) {
                        $this->texto = $this->texto . substr($this->BarCodes[$this->f1], $this->i - 1, 1) .
                                substr($this->BarCodes[$this->f2], $this->i - 1, 1);
                    }
                    $this->BarCodes[$this->f] = $this->texto;
                }
            }
        }

//Desenho da barra
// Guarda inicial
        $this->matrizimg.= "
<img src=". $this->local ."p.gif width=$this->fino height=$this->altura border=0><img
src=". $this->local ."b.gif width=$this->fino height=$this->altura border=0><img
src=".$this->local."p.gif width=$this->fino height=$this->altura border=0><img
src=".$this->local."b.gif width=$this->fino height=$this->altura border=0><img
";

        $this->texto = $Valor;
        if (strlen($this->texto) % 2 <> 0) {
            $this->texto = "0" . $this->texto;
        }
// Draw dos dados
        while (strlen($this->texto) > 0) {
            $this->i = intval(substr($this->texto, 0, 2));
            $this->texto = substr($this->texto, strlen($this->texto) - (strlen($this->texto) - 2));
            $this->f = $this->BarCodes[$this->i];
            for ($this->i = 1; $this->i <= 10; $this->i = $this->i + 2) {
                if (substr($this->f, $this->i - 1, 1) == "0") {
                    $this->f1 = $this->fino;
                } else {

                    $this->f1 = $this->largo;
                }

                $this->matrizimg.="src=".$this->local."p.gif width=$this->f1 height=$this->altura border=0><img ";
                if (substr($this->f, $this->i + 1 - 1, 1) == "0") {

                    $this->f2 = $this->fino;
                } else {

                    $this->f2 = $this->largo;
                }

                $this->matrizimg.= "src=".$this->local."b.gif width=$this->f2 height=$this->altura border=0><img ";
            }
        }

        $this->matrizimg.= "src=".$this->local."p.gif width=$this->largo height=$this->altura border=0><img src=".$this->local."b.gif width=$this->fino height=$this->altura border=0><img
src=".$this->local."p.gif width=1 height=$this->altura border=0>";
    }
}
?>




