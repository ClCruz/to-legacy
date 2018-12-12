<?php
 /**
  * @author Fastcash <cash@fastcash.com.br>
  * @copyright 2014 Fastcash
  * @license MIT
  */
  /*
  * DO NOT MODIFY THIS CLASS. 
  * This class may be updated in the future by us.
  */
namespace Fastcash
{
	class PaymentMethods
	{
		/**
		*   A deposit/cash payment method.
		*/
		const Deposit = 1;

		/**
		*   A online transfer/ibank payment method.
		*/
		const Transference = 2;

		/**
		*   A telephone operation payment method.
		*/
		const Telephone = 3;
        
		/**
		*   A credit card payment method.
		*/
        const CreditCard = 4;
        
        /**
		*   Fastcash Wallet/Balance payment method.
		*/
        const FastcashWallet = 5;
	}
}
?>