<?php
require_once($_SERVER['DOCUMENT_ROOT']."/config/whitelabel.php");
?>

<script language="javascript">
    const environment = '<?php echo getwhitelabelobj()["env"] ?>';

    const config = {
    api: '<?php echo getwhitelabelobj()["api"] ?>',
    apikey: '<?php echo getwhitelabelobj()["apikey"] ?>',
    setapikey,
    }

    function setapikey() {
    Vue.http.interceptors.push((request, next) => {
        if (request.url.startsWith(config.api)) {
            if (request.url.indexOf("apikey=")==-1) {
            if (request.url.indexOf("?")>-1) {
                    request.url+="&apikey="+config.apikey;
                }
                else {
                    request.url+="?apikey="+config.apikey;
                }
            }
        }
        next();
    });

    }
</script>