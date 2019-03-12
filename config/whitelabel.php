<?php
    function getconf() {
        $jsonFile = $_SERVER['DOCUMENT_ROOT']."/jsons/conf/conf.json";
        $jsonFileLocal = $_SERVER['DOCUMENT_ROOT']."/jsons/conf/conf.json.local";

        if (file_exists($jsonFileLocal)) {
            $jsonFile = $jsonFileLocal;
        }
        
        if (!file_exists($jsonFile)) {
            die("Falha de configuração no JSON de configuração.");
        }
        
        $ret = json_decode(file_get_contents($jsonFile), true);
        //die("oi".json_encode($ret));
        return $ret;
    }
    function gethost() {
        $fullHost = $_SERVER["HTTP_HOST"];
        $jsonFile = $_SERVER['DOCUMENT_ROOT']."/jsons/domains.json";

        if (!file_exists($jsonFile)) {
            die("Falha de configuração no JSON de domains.");
        }

        $aux = json_decode(file_get_contents($jsonFile), true);
        //echo "fullhost: $fullHost";
        //echo "<br />";
        //echo "<br />";
        //echo "aux:".print_r($aux,true);
        
        $ret = $aux[$fullHost];
        //echo "<br />";
        //echo "<br />";
        //echo "ret:".json_encode($ret);
        //die("");
        if ($ret == "") {
            $ret = $aux["default"];
        }
        return $ret;
    }
    function getwhitelabelURI_home($next) {
        $uri = getwhitelabel("uri");

        if (startsWith($uri, "http") == false) {
            $uri = "https://".$uri;
        }
        if (endsWith($uri, "/") == false && startsWith($next, "/") == false) {
            $uri .= "/";
        }
        $uri.=$next;
        return $uri;
    }
    function getwhitelabelURI_home_forced($host,$next) {
        $forced = getwhitelabelobjforced($host);
        $uri = $forced["uri"];

        if (startsWith($uri, "http") == false) {
            $uri = "https://".$uri;
        }
        if (endsWith($uri, "/") == false && startsWith($next, "/") == false) {
            $uri .= "/";
        }
        $uri.=$next;
        return $uri;
    }
    function getwhitelabelobj() {
        $ret = array();
        $whitelabel = gethost();
        $jsonFile = $_SERVER['DOCUMENT_ROOT']."/jsons/".$whitelabel.".json";
        //die("jsonFile: $jsonFile");
        if (!file_exists($jsonFile)) {
            die("Falha de configuração no JSON.");
        }

        $ret = json_decode(file_get_contents($jsonFile), true);

        return $ret;
    }
    function getwhitelabeldb() {
        $ret = array();
        $whitelabel = gethost();
        $jsonFile = $_SERVER['DOCUMENT_ROOT']."/jsons/db/".$whitelabel.".json";

        if (!file_exists($jsonFile)) {
            die("Falha de configuração no JSON de base de dados.");
        }

        $ret = json_decode(file_get_contents($jsonFile), true);

//        echo "ret:".json_encode($ret);
//        die("");

        return $ret;
    }
    function getwhitelabel_gateway_pagarme() {
        $ret = array();
        $whitelabel = gethost();
        $jsonFile = $_SERVER['DOCUMENT_ROOT']."/jsons/gateways/pagarme/".$whitelabel.".json";

        if (!file_exists($jsonFile)) {
            die("Falha de configuração no JSON do gateway pagarme.");
        }

        $ret = json_decode(file_get_contents($jsonFile), true);

        return $ret;
    }
    function getwhitelabelemail() {
        $ret = array();
        $whitelabel = gethost();
        $jsonFile = $_SERVER['DOCUMENT_ROOT']."/jsons/emails/".$whitelabel.".json";

        if (!file_exists($jsonFile)) {
            die("Falha de configuração no JSON de e-mails.");
        }

        $ret = json_decode(file_get_contents($jsonFile), true);

        return $ret;
    }
    function getwhitelabeltemplate($property) {
        switch ($property) {
            case "print:voucher":
                return getwhitelabelobj()["templates"]["print"]["voucher"];
            break;
            case "print:gift":
                return getwhitelabelobj()["templates"]["print"]["gift"];
            break;
            case "email:gift":
                return getwhitelabelobj()["templates"]["email"]["gift"];
            break;
            case "email:buyer":
                return getwhitelabelobj()["templates"]["email"]["buyer"];
            break;
            case "email:recover":
                return getwhitelabelobj()["templates"]["email"]["recover"];
            break;
            case "email:confirmation":
                return getwhitelabelobj()["templates"]["email"]["confirmation"];
            break;
            case "email:signature":
                return getwhitelabelobj()["templates"]["email"]["signature"];
            break;
        }
    }
    function getwhitelabel($property) {
        //die(json_encode(getwhitelabelobj()));
        switch (@$property) {
            case "legacy":
                return getwhitelabelobj()["legacy"];
            break;
            case "css":
                return getwhitelabelobj()["css"];
            break;
            case "uri":
                return getwhitelabelobj()["uri"];
            break;
            case "api":
                return getwhitelabelobj()["api"];
            break;
            case "host":
                return getwhitelabelobj()["host"];
            break;
            case "title":
                return getwhitelabelobj()["info"]["title"];
            break;
            case "appName":
                return getwhitelabelobj()["info"]["siteName"];
            break;
            case "name":
                return getwhitelabelobj()["info"]["siteName"];
            break;
            case "host":
                return getwhitelabelobj()["host"];
            break;
            case "meta_description":
                return getwhitelabelobj()["meta"]["description"];
            break;
            case "meta_keywords":
                return getwhitelabelobj()["meta"]["keywords"];
            break;
            case "logo":
                return getwhitelabelobj()["logo"];
            break;
            case "ga":
                return getwhitelabelobj()["ga"];
            break;
            case "cnpj":
                return getwhitelabelobj()["info"]["CNPJ"];
            break;
            case "favico":
                return getwhitelabelobj()["favico"];
            break;
            case "recaptcha_private":
                return getwhitelabelobj()["recaptcha"]["private"];
            break;
            case "recaptcha_public":
                return getwhitelabelobj()["recaptcha"]["public"];
            break;
            case "isNewTemplate":
                return getwhitelabelobj()["info"]["isNewTemplate"];
            break;

        }
    }
?>