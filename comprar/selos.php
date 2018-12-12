<?php
require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
?>
<div id="selos">
    <div class="centraliza">
        <!-- Site Seguro - CERTISIGN -->
        <div class="selo">
            <a href='javascript:vopenw()'>
                <img src='../images/100x46_fundo_branco.gif' alt='Certisign'>
            </a>
        </div>
    </div>
    <a class="link_adptativo" href="">visualizar na versão desktop</a>
</div>

<script language='javascript'>
    function vopenw() {
        tbar = 'location=no,status=yes,resizable=yes,scrollbars=yes,width=560,height=535';
        sw = window.open('https://www.certisign.com.br/seal/splashcerti.htm', 'CRSN_Splash', tbar);
        sw.focus();
    }
</script>
<script src="<?php echo multiSite_seloCertificado(); ?>"></script>
