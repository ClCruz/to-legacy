<?php
session_start();
require_once('../settings/settings.php');
require_once('../settings/functions.php');

    function keepalive_legacy() {
        //sleep(5);
        if ($_SESSION['user'] == '' || $_SESSION['user'] == null) {
            return;
        }
        $mainConnection = mainConnection();
        $query = "EXEC pr_login_legacy_keepalive ?";
        $params = array($_SESSION['user']);
        $rs = executeSQL($mainConnection, $query, $params, true);
    }

//keepalive_legacy();

?>