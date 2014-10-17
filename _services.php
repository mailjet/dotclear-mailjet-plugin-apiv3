<?php

/*
 * LICENSE BLOCK
 * 
 * This program is free software. It comes without any warranty, to the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 * 
 */

class mailjetPluginRestMethods
{
	public static function getPostListSubscribe($core, $get)
	{
		global $core;

		// Extract POST variables in case of form submition
		$list_id = isset($_GET['list']) && $_GET['list'] ? $_GET['list'] : null;

		if ($list_id === null)
			throw new Exception(__('No list ID given'));

		$email = isset($_GET['email']) && $_GET['email'] ? $_GET['email'] : null;

		if ($email === null)
			throw new Exception(__('Please enter your email'));

		// Get some settings information
		$blog = $core->blog;
		$settings = $blog->settings->mailjet;
		
		// Connect to the API
		$api = new Mailjet_Api($settings->mj_username, $settings->mj_password);
			
		// Add a contact to the default contact list
		$response = $api->addContact(array(
			'Email'		=> $email,
			'ListID'	=> $list_id,
		));

		// Print a proper message
		if(isset($response->Status) && $response->Status == 'OK')
		{
			$rsp = new xmlTag();
			$rsp->message(__('Thanks for subscribing'));
			return $rsp;
		} else if(isset($response->Status) && $response->Status == 'DUPLICATE') {
			throw new Exception( __('This email has been already subscribed'));
		} else if(isset($response->Status) && $response->Status == 'ERROR') {
			throw new Exception( __('Sorry, we could not subscribe you at this time'));
		}
	}
}
