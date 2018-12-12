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
	class PaymentMethodsOptions
	{
		/**
		*  An array with all valid options for PaymentMethods::Deposit.
		*/
		public static $Deposit = array("Banco do Brasil" => 1, "Bradesco" => 2, "Caixa" => 3, "Itau" => 4, "Santander" => 5, "HSBC" => 6, "Citibank" => 7, "Banrisul" => 8, "Correios" => 10, "Loterica" => 11);

		/**
		*  An array with all valid options for PaymentMethods::Transference.
		*/
		public static $Transference = array("Banco do Brasil" => 1, "Bradesco" => 2, "Caixa" => 3, "Itau" => 4, "Santander" => 5, "HSBC" => 6, "Citibank" => 7, "Banrisul" => 8);

		/**
		*  An array with all valid options for PaymentMethods::Telephone.
		*/
		public static $Telephone = array("Banco do Brasil" => 1, "Bradesco" => 2, "Itau" => 3, "Santander" => 4, "HSBC" => 5, "Citibank" => 6);
        
        /**
         *  An array with all valid options for PaymentMethods::CreditCard.
         */
		public static $CreditCard = array("Visa" => 12, "Mastercard" => 13, "Amex" => 14, "Diners" => 15, "Elo" => 16, "Discover" => 17);
        
        /**
         *  An array with all valid options for PaymentMethods::FastcashWallet.
         */
		public static $FastcashWallet = array("Balance" => 18);
	}
}
?>