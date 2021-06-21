<?php

namespace \KitPHP\Google;

use \Exception;

/* ReCaptcha v3 */
class ReCaptcha
{
	static public function GetScriptUrl(string $public_key, string $callback = null)
	{
		$url = 'https://www.google.com/recaptcha/api.js?render=' . $public_key;

		if (isset($callback))
		{
			$url .= '&onload=' . $callback;
		}

		return $url;
	}

	static public function Verify(string $secret_key, string $token, string $hostname = null, string $validity = '-1 hour')
	{
		$url = 'https://www.google.com/recaptcha/api/siteverify?secret=' . $secret_key . '&response=' . $token;

		$data = file_get_contents($url);

		if (empty($data))
		{
			throw new Exception('Missing response');
		}

		$data = json_decode($data, true);

		if (empty($data['success']))
		{
			if (empty($data['error-codes']) || !is_array($data['error-codes']))
			{
				return false;
			}

			$error = reset($data['error-codes']);

			throw new Exception(self::GetErrorMessage($error));
		}

		if (empty($data['challenge_ts']) || strtotime($data['challenge_ts']) < strtotime($validity))
		{
			return false;
		}

		if (isset($hostname) && (empty($data['hostname']) || $data['hostname'] !== $hostname))
		{
			return false;
		}

		return true;
	}

	static private function GetErrorMessage(/*string*/ $error)
	{
		return match ($error)
		{
			'bad-request' => 'The request is invalid or malformed',
			'missing-input-secret' => 'The secret parameter is missing',
			'invalid-input-secret' => 'The secret parameter is invalid',
			'missing-input-response' => 'The response parameter is missing',
			'invalid-input-response' => 'The response parameter is invalid',
			'incorrect-captcha-sol' => 'The secret key is invalid',
			default => is_string($error) ? "Unknown error: {$error}" : 'Unknown error',
		}
	}
}
