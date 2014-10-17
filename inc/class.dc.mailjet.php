<?php

/*
 * LICENSE BLOCK
 * 
 * This program is free software. It comes without any warranty, to the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 * 
 */

class Mailjet
{
	protected static $mailer = null;

	protected static function initMailer()
	{
		global $core;

		$blog = $core->blog;

		if (is_null($blog))
			return false;

		$settings = &$blog->settings->mailjet;

		$_mailer = new PHPMailer(true);

		$_mailer->Mailer = 'smtp';
		$_mailer->SMTPSecure = $settings->mj_ssl;
		
		// Get some settings
		$blog = $core->blog;
		$settings = $blog->settings->mailjet;
		
		// Connect to the API
		$api = new Mailjet_Api($settings->mj_username, $settings->mj_password);
		
		$_mailer->Host = $api->mj_host;
		$_mailer->Port = $settings->mj_port;

		$_mailer->SMTPAuth = true;
		$_mailer->Username = $settings->mj_username;
		$_mailer->Password = $settings->mj_password;

		$_mailer->SetFrom ($settings->mj_sender_address);

		self::$mailer = $_mailer;

		return true;
	}

	public static function sendMail($to, $subject, $message, $headers)
	{
		if (is_null (self::$mailer) && !self::initMailer())
			return @mail($to, $subject, $message, $headers);

		self::$mailer->ClearAllRecipients();
		self::$mailer->ClearCustomHeaders();

		self::$mailer->Subject = $subject;
		self::$mailer->Body = $message;

		self::$mailer->AddAddress($to);

		if (!is_null($headers))
		{
			if (is_array($headers))
			{
				foreach ($headers as $value)
					self::$mailer->AddCustomHeader($value);
			}
			else
				self::$mailer->AddCustomHeader($headers);
		}

		self::$mailer->AddCustomHeader('X-Mailer:Mailjet-for-Dotclear/1.0');

		try
		{
			return self::$mailer->Send();
		}
		catch (phpmailerException $exc)
		{
			echo $exc->getMessage();
		}
	}
}

?>