<?php
    require_once "..\Fastcash.php";
    
    use Fastcash\TransactionClient;
    use Fastcash\ConfirmationClient;
    use Fastcash\RefundClient;
    use Fastcash\TransactionRequest;
    use Fastcash\ConfirmationRequest;
    use Fastcash\RefundRequest;
    use Fastcash\IntegrationData;
    use Fastcash\PaymentMethods;
    use Fastcash\PaymentMethodsOptions;
    use Fastcash\ClientTransactionData;
    use Fastcash\PaymentData;
    use Fastcash\CreditCardData;
    
    $transactionClient = new TransactionClient();
    
    $req = new TransactionRequest();
    $req->Transaction->Tid = uniqid('', true);
    $req->Transaction->Pid = IntegrationData::Pid;
    $req->Transaction->ProdId = IntegrationData::$ProductIds["Default"];
    $req->Transaction->ItemDescription = "Teste API 2.0-php";
    $req->Transaction->Price = 100.00;
    $req->Transaction->PaymentMethod = PaymentMethods::Deposit;
    $req->Transaction->SubPaymentMethod = PaymentMethodsOptions::$Deposit["Banco do Brasil"];
    $req->Client->Name = "Test API PHP";
    $req->Client->Email = "teste@fastcash.com.br";
    $req->Client->MobilePhoneNumber = "011999999999";
    $req->Client->Cpf = "89629182009";
    
    //If Client bank account is provided, initialize an instance of ClientTransactionData.
    //Otherwise, leave it null.
    $req->ClientTransactionData = new ClientTransactionData();
    $req->ClientTransactionData->BankAgency = "10000";
    $req->ClientTransactionData->BankAccountNumber = "123456";
    
    //Credit Card Sample
    //$req->Transaction->PaymentMethod = PaymentMethods::CreditCard;
    //$req->Transaction->SubPaymentMethod = PaymentMethodsOptions::$CreditCard["Visa"];
    //$req->PaymentData = new PaymentData();
    //$req->PaymentData->Installments = 1; //Parcels/Quotes
    //$req->PaymentData->CreditCard = new CreditCardData();
    //$req->PaymentData->CreditCard->Number = "4929271227037550";
    //$req->PaymentData->CreditCard->NameOnCard = "JON D DOE";
    //$req->PaymentData->CreditCard->ValidThru = "12/18"; //MM/YY
    //$req->PaymentData->CreditCard->CVC = "123";
    //$req->PaymentData->CreditCard->BankIssuer = "Banco do Brasil"; //optional
    
    //Fastcash Wallet Balance Sample
    //$req->Transaction->PaymentMethod = PaymentMethods::FastcashWallet;
    //$req->Transaction->SubPaymentMethod = PaymentMethodsOptions::$FastcashWallet["Balance"];
    
    $res = $transactionClient->Send($req);

    if ($res == null)
    {
        echo "Transaction Error: " . $transactionClient->Error;
        echo "Transaction Error Body: " . $transactionClient->ErrorBody;
    }
    else
    {
        echo "Transaction:" .$res->Transaction;
        echo "Confirmation:" . $res->Confirmation;
        
        echo "Parameters:";
        
        foreach($res->Parameters as $p)
        {
            echo $p;
        }
    }
    
    $transactionConsulted = $transactionClient->Consult($req->Transaction->Tid, IntegrationData::Pid);
    
    if ($transactionConsulted != null)
    {
		echo "<br />Current Transaction Status:". $transactionConsulted->Transaction->Status;
    }
    else
    {
		echo "<br />Transaction not found on Consult!";
    }
    
    //$confirmationClient = new ConfirmationClient();
    
    //$cReq = new ConfirmationRequest();
    //$cReq->Confirmation->Tid = $req->Transaction->Tid;
    //$cReq->Confirmation->Pid = IntegrationData::Pid;
    //$cReq->Confirmation->ProdId = IntegrationData::$ProductIds["Default"];
    //$cReq->Confirmation->F1 = "1234";
    //$cReq->Confirmation->F2 = "5678";
    //$cReq->Confirmation->F3 = null;
    //$cReq->Confirmation->F4 = null;
    //$cReq->Confirmation->PaidDate = date("Y-m-d H:i:s");
    //$cReq->Confirmation->Value = 100.00;
    //$cReq->Confirmation->Observations = null;
    
    //$cRes = $confirmationClient->Send($cReq);
    
    //if ($cRes == true)
    //{
    //    echo "TID: " . $cReq->Confirmation->Tid . " is confirmed!";
    //}
    //else
    //{
    //    echo "Confirmation Error: " . $confirmationClient->Error;
    //    echo "Confirmation Error Body: " . $confirmationClient->ErrorBody;
    //}
    
    //$refundClient = new RefundClient();
    
    //$rReq = new RefundRequest();
    //$rReq->Tid = $req->Transaction->Tid;
    //$rReq->Pid = IntegrationData::Pid;
    //$rReq->PartialRefund = false;
    //$rReq->IncludesIndenization = false;
    //$rReq->RefundValue = 100.00;
    //$rReq->RefundValueDescription = "Item total price";
    //$rReq->Reason = "Teste API 2.0-php";
    
    //$rRes = $refundClient->Send($rReq);
    
    //if ($rRes == true)
    //{
    //    echo "Refunded! ". $refundClient->Description;
    //}
    //else
    //{
    //    echo "Refund Error: " . $confirmationClient->Error;
    //    echo "Refund Error Body: " . $confirmationClient->ErrorBody;
    //}
    
    
?>