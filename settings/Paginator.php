<?php
/*
 * Copyright (c) 2006-2008 Byrne Reese. All rights reserved.
 * 
 * This library is free software; you can redistribute it and/or modify it 
 * under the terms of the BSD License.
 *
 * This library is distributed in the hope that it will be useful, but 
 * WITHOUT ANY WARRANTY; without even the implied warranty of 
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. 
 *
 * @author Byrne Reese <byrne@majordojo.com>
 * @version 1.02
 */

class Paginator {
	static function paginate($offset,$total,$limit,$base = '', $controle = false)
	{
		$lastp = ceil($total / $limit);
		$thisp = ceil(($offset == 0 ? 1 : ($lastp / ($total / $offset))));
		print "    <div class=\"paginator\">\n";
		if ($thisp==1) { print "      <SPAN CLASS=\"atstart\">&lt Anterior</SPAN>\n"; }
		else { print "      <a href=\"".$base.((($thisp - 2) * $limit) + 1)."\" class=\"prev\">&lt; Anterior</a> \n"; }
		$page1 = $base . "1";
		$page2 = $base . ($limit + 1);
		if ($thisp <= 5) {
		  for ($p = 1;$p <= min( ($thisp<=3) ? 5 : $thisp+2,$lastp); $p++) {
		if ($p == $thisp) {
		  print "      <span class=\"this-page\">$p</span>\n ";
		} else {
		  $url = $base . (($limit * ($p - 1)) + 1);
		  print "      <a href=\"$url\">$p</a>\n ";
		}
		  }
		  if ($lastp > $p) {
		print "      <span class=\"break\">...</span>\n";
		print "      <a href=\"".$base.(($lastp - 2) * $limit)."\">".($lastp-1)."</a>\n";
		print "      <a href=\"".$base.(($lastp - 1) * $limit)."\">".$lastp."</a>\n";
		  }
		}
		else if ($thisp > 5) {
		  print "      <a href=\"".$page1."\">1</a> <a href=\"".$page2."\">2</a>";
		  if ($thisp != 6) { print " <span class=\"break\">...</span>\n "; }
		  for ($p = ($thisp == 6) ? 3 : min($thisp - 2,$lastp-4);$p <= (($lastp-$thisp<=5) ? $lastp:$thisp+2); $p++) {
		if ($p == $thisp) {
		  print "      <span class=\"this-page\">$p</span>\n ";
		} else if ($p <=$lastp) {
		  $url = $base . (($limit * ($p - 1)) + 1);
		  print "      <a href=\"$url\">$p</a>\n ";
		}
		  }
		  if ($lastp > $p+1) {
		print "      <span class=\"break\">...</span>\n";
		print "      <a href=\"".$base.(($lastp - 2) * $limit)."\">".($lastp-1)."</a>\n";
		print "      <a href=\"".$base.(($lastp - 1) * $limit)."\">".$lastp."</a>\n";
		  }
		}
		if ($thisp == $lastp) { print "      <SPAN CLASS=\"atend\"> Próxima &gt</SPAN>\n"; }
		else { print "      <a href=\"".$base.((($thisp + 0) * $limit) + 1)."\" class=\"next\">Próxima &gt;</a>\n"; }

		if($controle == true){
			$itens = array(10, 20, 30, 40, 50);
			echo "&nbsp;&nbsp;<select name=\"intervalo\" id=\"controle\">";
			foreach($itens as $item){
				if($item == $_GET["controle"])
					$selected = "selected";
				else
					$selected = "";

				echo "<option ". $selected ." value=\"". $item ."\">". $item ."</option>";
			}
			echo  "</select>";
		}
		print "    </div>\n";
	}

	static public $totalPages;
	static public $curPage;
	static public $perPage;

	static function __paginate($link, $queryBaseNumRows, $controle = true, $selectPages = true, $bLength = 5)
	{
		$total = numRows( mainConnection(), $queryBaseNumRows['query'], $queryBaseNumRows['paramns'] );

		self::$curPage 	= ( !isset($_GET['page']) ) ? 1 : (int)$_GET['page'];
		self::$perPage 	= ( !isset($_GET['controle']) ) ? 10 : (int)$_GET['controle'];
		self::$totalPages =	ceil($total / self::$perPage);

		if (self::$curPage > ceil($total / self::$perPage)) { self::$curPage = 1; $_GET['page'] = 1; }

		//Define o BETWEEN da query
		$resp['end'] 		= (self::$curPage * self::$perPage);
		$resp['start'] 		= ($resp['end'] - self::$perPage) + 1;

		$link .= ( $_GET['controle'] ) ? '&controle='.$_GET['controle'] : '';

		define('BLOCK_LENGTH', $bLength);//define quantas páginas serão exibidas para navegação

		//Exibir Pagina atual -1, para poder voltar
		$startNumber = ( self::$curPage <= BLOCK_LENGTH ) ? 1 : self::$curPage - (BLOCK_LENGTH-1) ;

		//Pagina atual + qtde a ser exibida para poder navegar nas páginas
		$finalNumber = ( ($startNumber + BLOCK_LENGTH) > self::$totalPages ) ? self::$totalPages : ($startNumber + BLOCK_LENGTH);

		$html = '<div class="paginas">';

		//Não exibir 'anterior' se for a primeira página
		if(self::$curPage != 1) { $html .= '<a href="'.$link.'&page='.(self::$curPage-1).'">Anterior</a>'; };

		for($i = $startNumber; $i <= $finalNumber; $i++)
		{
			$html .= self::createLink($i, $link, self::$curPage);
		}

		/*
		 * Se houver + do que 2 paginas restantes, exibir somente as últimas.
		 * */
		$restante = self::$totalPages-self::$curPage;
		if ($restante > 3)
		{
			$html .= '...';
			$lastPage = self::$totalPages-2;
			$i = 2;
			while ($i >= 0)
			{
				$html .= self::createLink($lastPage, $link, null);
				$lastPage++;
				$i--;
			}
		}

		if ( self::$curPage != self::$totalPages ) { $html .= '<a href="'.$link.'&page='.(self::$curPage+1).'">Próxima</a>'; }; //Não exibir 'próximo' se for a ultima página
		if ( $selectPages )  { $html .= self::createSelectPages(); } //Select de páginas por numero
		if ( $controle ) { $html .= self::createSelectPerPage(); } //Select para quantidade de registros por página

		$html .= '</div>';

		$resp['htmlpages'] 	= $html;

		return $resp;
	}

	function createLink($page, $link, $curPage)
	{
		$html = '';
		if ( $page != $curPage )
		{
			$href = $link.'&page='.$page;
			$html .= '<a href="'.$href.'">'.$page.'</a>';
		}
		else
		{
			$html .= '<span class="ativo">'.$page.'</span>';
		}
		return $html;
	}

	static function createSelectPages()
	{
		$select = '<select onchange="simples.selectPage(this)">';
		$i = 1;
		while ($i <= self::$totalPages)
		{
			$key = ( $i == self::$curPage ) ? 'selected="selected"' : '';
			$select .= '<option '.$key.'>'.$i.'</option>';
			$i++;
		}
		$select .= '</select>';

		$label = '<label class="pages">Ir para página: ';
		$label .= $select;
		$label .= '</label>';

		return $label;
	}

	static function createSelectPerPage()
	{
		$itens = array(10, 20, 30, 40, 50);
		$select =  '<label class="qtt">Quantidade por Página<select name="intervalo" id="controle" onchange="simples.setParamns(\'controle\', this.value, { reload: true })">';
		foreach($itens as $item)
		{
			$selected = ($item == self::$perPage) ? 'selected="selected"' : '';
			$select .= '<option '.$selected.' value="'. $item .'">'. $item .'</option>';
		}
		$select .=  "</label></select>";

		return $select;
	}
}
?>
