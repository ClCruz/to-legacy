<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
?>
<hr/>
							<div id="seloCertificado">
								<script language='javascript'>function vopenw() {	tbar='location=no,status=yes,resizable=yes,scrollbars=yes,width=560,height=535';	sw =  window.open('https://www.certisign.com.br/seal/splashcerti.htm','CRSN_Splash',tbar);	sw.focus();}</script>
								<table border='0' cellpadding='0' cellspacing='0'>
								<tr>
									<td align='center' valign='middle'><a href='javascript:vopenw()'>
										<img src='../images/100x46_fundo_branco.gif' border='0' align='center' alt='Um site validado pela Certisign indica que nossa empresa concluiu satisfatoriamente todos os procedimentos para determinar que o domínio validado é de propriedade ou se encontra registrado por uma empresa ou organização autorizada a negociar por ela ou exercer qualquer atividade lícita em seu nome.'></a>
									</td>
									<td>
										<script src='<?php echo multiSite_seloCertificado(); ?>'></script>
									</td>
								</tr>
								</table>
							</div>