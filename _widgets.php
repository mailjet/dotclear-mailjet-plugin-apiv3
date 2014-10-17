<?php

/*
 * LICENSE BLOCK
 * 
 * This program is free software. It comes without any warranty, to the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 * 
 */
 
if (!defined('DC_RC_PATH')) { return; }

$core->addBehavior('initWidgets', array('mailjetSubscribeWidgetBehaviors', 'initWidgets'));

class mailjetSubscribeWidgetBehaviors
{
	public static function initWidgets($w)
	{
		global $core;

		$blog =		$core->blog;
		$settings =	$blog->settings->mailjet;
		
		// Connect to the API
		$api = new Mailjet_Api($settings->mj_username, $settings->mj_password);
		
		// Get all contact lists
		$response = $api->getContactLists(array());
		
		// Extract the required information
		if(!isset($response->Status) || (isset($response->Status) && $response->Status != 'ERROR'))
		{
			foreach ($response as $list)
				$options[$list['label']] = $list['value'];
		}

		$w->create('mailjetSubscribeWidget', __('Mailjet subscription'), array('publicMailjetSubscribeWidget','mailjetSubscribeWidget'));

		$w->mailjetSubscribeWidget->setting('title', __('Title:'), 'Subscribe to our newsletter','text');
		$w->mailjetSubscribeWidget->setting('button_text', __('Button text:'), 'Subscribe','text');
		$w->mailjetSubscribeWidget->setting('list', __('List:'), null, 'combo', $options);
	}
}
