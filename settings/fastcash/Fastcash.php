<?php
    date_default_timezone_set('America/Sao_Paulo');
   
    //Guzzle Framework
    require_once "FastcashAPI/guzzle.phar";
    
    //Fastcash configuration data
    require_once "Config/FastcashIntegrationData.php";
    
    //API Models
    require_once "FastcashAPI/ClientData.php";    
    require_once "FastcashAPI/ClientTransactionData.php";
    require_once "FastcashAPI/ConfirmationData.php";
    require_once "FastcashAPI/TransactionData.php";
    require_once "FastcashAPI/ParameterData.php";
    require_once "FastcashAPI/ConfirmationRequirementField.php";
    require_once "FastcashAPI/ConfirmationRequirementData.php";
    require_once "FastcashAPI/ConfirmationRequest.php";
    require_once "FastcashAPI/CreditCardData.php";
    require_once "FastcashAPI/TransactionRequest.php";
    require_once "FastcashAPI/TransactionResponse.php";
    require_once "FastcashAPI/RefundRequest.php";
    require_once "FastcashAPI/PaymentMethods.php";
    require_once "FastcashAPI/PaymentMethodsOptions.php";
    require_once "FastcashAPI/PaymentData.php";

    //API
    require_once "FastcashAPI/BaseClient.php";
    require_once "FastcashAPI/TransactionClient.php";
    require_once "FastcashAPI/ConfirmationClient.php";
    require_once "FastcashAPI/RefundClient.php";

    //Receiver
    require_once "FastcashAPI/Security.php";
    require_once "FastcashAPI/BaseComponent.php";
    require_once "FastcashAPI/BaseReceiver.php";
    require_once "FastcashAPI/OnlineCredit.php";
    require_once "FastcashAPI/CreditConsult.php";
    require_once "FastcashAPI/Cancelation.php";
?>