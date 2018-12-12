<?php

require_once($_SERVER['DOCUMENT_ROOT']."/settings/multisite/unique.php");
header("location:".multiSite_getURI("URI_SSL"));
?>