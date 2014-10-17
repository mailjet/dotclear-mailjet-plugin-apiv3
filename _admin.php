<?php

/*
 * LICENSE BLOCK
 * 
 * This program is free software. It comes without any warranty, to the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 * 
 */

# add the plugin in the plugins list on the backend
# ajouter le plugin dans la liste des plugins du menu de l'administration
$_menu['Plugins']->addItem(
	# link's name
	# nom du lien (en anglais)
	__('Mailjet'),
	# base URL of the administration page
	# URL de base de la page d'administration
	'plugin.php?p=mailjet',
	# URL of the image used as icon
	# URL de l'image utilisée comme icône
	'index.php?pf=mailjet/assets/images/icon.png',
	# regular expression of the URL of the administration page
	# expression régulière de l'URL de la page d'administration
	preg_match('/plugin.php\?p=mailjet(&.*)?$/',
		$_SERVER['REQUEST_URI']),
	# required permissions to show the link
	# permissions nécessaires pour afficher le lien
	$core->auth->check('usage,contentadmin',$core->blog->id)
);

$core->addBehavior(
	'adminDashboardFavs',
	array(
		'adminExampleBehaviors',
		'adminDashboardFavs'
	)
);

$core->addBehavior(
	'adminDashboardFavsIcon',
	array(
		'adminExampleBehaviors',
		'adminDashboardFavsIcon'
	)
);

class adminExampleBehaviors
{
	public static function adminDashboardFavs($core,$favs)
	{
		$favs['mailjet'] = new ArrayObject(
			array(
				'mailjet',
				__('Mailjet'),
				'plugin.php?p=mailjet',
				'index.php?pf=mailjet/assets/images/icon.png',
				'index.php?pf=mailjet/assets/images/icon-big.png',
				'usage,contentadmin',
				null,
				null
			)
		);
	}

	public static function adminDashboardFavsIcon($core,$name,$icon)
	{
		if ($name == 'mailjet')
		{
			# this is an example to show how the icon, the URL and the URL
			#  can be changed with a test
			# ceci est un un exemple pour montrer comment l'icône, l'URL et
			#  l'image peuvent être changées avec un test
			if ($core->blog->settings->adminExample->adminexample_active)
			{
				$icon[0] = __('Mailjet: active');
				$icon[1] = 'plugin.php?p=mailjet&amp;tab=settings';
				$icon[2] = 'index.php?pf=mailjet/assets/images/icon-big.png';
			}
			else
			{
				$icon[0] = __('Mailjet: inactive');
				$icon[1] = 'plugin.php?p=mailjet';
				$icon[2] = 'index.php?pf=mailjet/assets/images/icon-block.png';
			}
		}
	}
}

require dirname(__FILE__).'/_widgets.php';