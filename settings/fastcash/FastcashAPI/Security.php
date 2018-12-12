<?php
 /**
  * @author Fastcash <cash@fastcash.com.br>
  * @copyright 2012 Fastcash
  * @license MIT
  */
  /*
  * DO NOT MODIFY THIS CLASS. 
  * This class may be updated in the future by us.
  */
namespace Fastcash
{
	/**
	*   Security class includes static methods only, for Rijndael encryption and integrity hash.
	*/
	class Security
	{
		/**
		*   Decrypts the Base64 Rijndael data. Uses the IntegrationData::PaymentDataPushKey as key and IntegrationData::PaymentDataPushIV as the initialization vector. 
		*   Implements the PKCS7 padding removal.
		*   @param $base64Data The encrypted data to decrypt, encoded in Base64.
		*   @returns The decrypted string.
		*/
		public static function DecryptRijndael($base64Data)
		{
			$key = base64_decode(IntegrationData::PaymentDataPushKey);
			$iv = base64_decode(IntegrationData::PaymentDataPushIV);

			$decoded = base64_decode($base64Data);
			$decrypted = mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $decoded, MCRYPT_MODE_CBC, $iv);

			$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
			$pad = ord($decrypted[($len = strlen($decrypted)) - 1]);

			return substr($decrypted, 0, strlen($decrypted) - $pad);
		}

		/**
		*   Encrypts a string data with Rijndael, using the IntegrationData::PaymentDataPushKey as key and IntegrationData::PaymentDataPushIV as the initialization vector.
		*   Adds the PKCS7 padding to the data.
		*   @param $data The string data to be encrypted.
		*   @returns The encrypted data encoded in Base64.
		*/
		public static function EncryptRijndael($data)
		{
			$key = base64_decode(IntegrationData::PaymentDataPushKey);
			$iv = base64_decode(IntegrationData::PaymentDataPushIV);

			$block = mcrypt_get_block_size(MCRYPT_RIJNDAEL_256, MCRYPT_MODE_CBC);
			$pad = $block - (strlen($data) % $block);
			$data .= str_repeat(chr($pad), $pad);

			$crypt = mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $data, MCRYPT_MODE_CBC, $iv);
			$encode = base64_encode($crypt);

			return $encode;
		}

		/**
		*   Generates the MD5 hash for all provided parameters. Any number of parameters can be sent.
		*   Uses the PHP function func_get_args to get all the input parameters.
		*   @returns The MD5 hash of all inputed parameters.
		*/
		public static function IntegrityHash()
		{
			$numargs = func_num_args();
			$arg_list = func_get_args();
			$strParams = "";

			for ($i = 0; $i < $numargs; $i++) {
				$strParams .= (string)$arg_list[$i];
			}

			return md5($strParams);
		}

		/**
		*   Compares two string hashes with case-insensitive.
        *   @param $a The first hash to compare.
        *   @param $b The second hash to compare.
		*   @returns true or false
		*/
		public static function CompareHash($a, $b)
		{
			if (strtolower($a) === strtolower($b))
			{
				return true;
			}
			else
			{
				return false;
			}
		}

		/**
		*   Verifies if the provided ip address matches the authorized Fastcash servers ip addresses. Only our servers should be able to call the Receiver components.
		*   @param $senderAddress The remote ip address of the caller.
		*   @returns true or false
		*/
		public static function VerifyIP($senderAddress)
		{
			static $ipWhitelist = array("70.38.71.240", "40.84.226.163", "104.214.117.215", "191.235.94.173", "52.67.136.39", "52.67.225.6", "169.46.3.21", "169.46.76.37");

			if (in_array($senderAddress, $ipWhitelist))
			{
				return true;
			}
			else
			{
				return false;
			}
		}
	}
}
?>