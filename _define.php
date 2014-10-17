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

$this->registerModule(
	/* Name */				'mailjet',
	/* Description */		'Mailjet puts your e-mail delivery and traceability at ease. Simply send, we take care of the rest.',
	/* Author */			'Mailjet SAS',
	/* Version */			'2.0.0',
	/* Permissions */		'usage,contentadmin',
	array(
		'type'		=>		'plugin'
	)
);
