<?php

/*
 * LICENSE BLOCK
 * 
 * This program is free software. It comes without any warranty, to the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 * 
 */

/* ====================================== START ================================= */
l10n::set(dirname (__FILE__) . '/locales/' . $_lang . '/admin');
$default_tab = 'settings';

if (isset($_REQUEST['tab']))
	$default_tab = $_REQUEST['tab'];

$page_title = __('Mailjet');

$core->blog->settings->addNameSpace('mailjet');

$settings = &$core->blog->settings->mailjet;

$fields = array('mj_enabled' => '',
				'mj_test' => '',
				'mj_test_address' => '',
				'mj_port' => '',
				'mj_ssl' => '',
				'mj_username' => '',
				'mj_password' => '');

$errors = array();


/* ====================================== DECLARE A FUNCTION ================================= */
function _get_auth_token($api, &$settings)
{
	// Get the
	$token = $api->getAuthToken(array(
		'APIKey'		=> $settings->mj_username, // Use any API Key from your Sub-accounts
		'SecretKey'		=> $settings->mj_password,
		'MailjetToken'	=> isset($settings->mj_token) ? $settings->mj_token : FALSE
	));

	// Return FALSE if there is token
	if(isset($token->Status) && $token->Status == 'ERROR')
		return FALSE;

	$settings->put('mj_token', $token, 'string', 'TOKEN');
	return $token;
}


/* ====================================== SAVE SETTINGS ================================= */
if (isset($_POST['saveconfig']))
{
	// Get the submited POST variables
	$fields['mj_enabled'] =			isset($_POST['mj_enabled']);
	$fields['mj_test'] =			isset($_POST['mj_test']);
	$fields['mj_test_address'] =	strip_tags($_POST['mj_test_address']);
	$fields['mj_sender_address'] =	strip_tags($_POST['mj_sender_address']);
	$fields['mj_username'] =		strip_tags($_POST['mj_username']);
	$fields['mj_password'] =		strip_tags($_POST['mj_password']);

	// Check for errors
	if ($fields['mj_test'] && empty ($fields['mj_test_address']))
		$errors[] = __('Enter a test address email');

	if (empty($fields['mj_username']))
		$errors[] = __('You must enter your Mailjet API Key');

	if (empty($fields['mj_sender_address']))
		$fields['mj_sender_address'] = DC_ADMIN_MAILFROM;

	if (empty($fields['mj_password']))
		$errors[] = __('You must enter your Mailjet Secret Key');

	if (count ($errors))
	{
		foreach ($errors as $value)
			$core->error->add(__($value));
	}
	else
	{
		// If there are no errors, continue - Save
		$was_enabled = $settings->mj_enabled;
		$settings->put('mj_enabled',		$fields['mj_enabled'], 'boolean', 'Enable Mailjet');
		$settings->put('mj_test',			$fields['mj_test'], 'boolean', 'Enable test mail');
		$settings->put('mj_test_address',	$fields['mj_test_address'], 'string', 'Test address');
		$settings->put('mj_sender_address',	$fields['mj_sender_address'], 'string', 'From address');
		$settings->put('mj_username',		$fields['mj_username'], 'string', 'API Key');
		$settings->put('mj_password',		$fields['mj_password'], 'string', 'Secret API');
	
		// Connect to the API
		$api = new Mailjet_Api($fields['mj_username'], $fields['mj_password']);
		
		// Check if there is connection with the API and if not, then display error message
		if(!is_object($api) || $api->getContext() === FALSE)
			$core->error->add(__('Wrong API/Secret Keys. Please try again!'));
		
		
		$configs = array(	array('ssl://', 465),
							array('tls://', 587),
							array('', 587),
							array('', 588),
							array('tls://', 25),
							array('', 25));		
		$host = $api->mj_host;
		$connected = false;

		for ($i = 0; $i < count($configs); ++$i)
		{
			$soc = @fsockopen($configs[$i][0] . $host, $configs[$i][1], $errno, $errstr, 5);		
			if ($soc)
			{
				fclose($soc);
				$connected = true;
				break;
			}
		}

		if ($connected)
		{
			if ('ssl://' == $configs[$i][0])
				$settings->put('mj_ssl', 'ssl', 'string', 'Secure connection');
			elseif ('tls://' == $configs[$i][0])
				$settings->put('mj_ssl', 'tls', 'string', 'Secure connection');
			else
				$settings->put('mj_ssl', '', 'string', 'Secure connection');

			$settings->put('mj_port', $configs[$i][1], 'integer', 'Port');

			if ($fields['mj_test'])
			{
				$subject = __('Your test mail from Mailjet');
				$message = __('Your Mailjet configuration is ok!');
				
				require_once (dirname(__FILE__)."/inc/class.dc.mailjet.php");					
				Mailjet::sendMail($fields['mj_test_address'], $subject, $message, NULL);
			}

			http::redirect ($p_url . '&saveconfig=1&wasEnabled=' . $was_enabled);
		}
		else
			$core->error->add(sprintf(__('Please contact Mailjet support to sort this out.<br /><br />%d - %s', $errno, $errstr)));
	}
}
else
{
	// There is no form submit, so we just extract the current settings
	$fields['mj_enabled'] =			$settings->mj_enabled;
	$fields['mj_test'] =			$settings->mj_test;
	$fields['mj_test_address'] =	$settings->mj_test_address;
	$fields['mj_sender_address'] =	$settings->mj_sender_address;
	$fields['mj_username'] =		$settings->mj_username;
	$fields['mj_password'] =		$settings->mj_password;
	
	// Connect to the API
	$api = new Mailjet_Api($settings->mj_username, $settings->mj_password);
}

// If we save, then we define some message which will be displayed to the customer
if (isset($_GET['saveconfig']))
{
	$msg =		__('Configuration successfully updated.');
	$enabled =	__('To enable Mailjet, please copy this function _mail () in your dotclear/inc/config.php file :');
	$disabled = __('To disable Mailjet, please remove the function _mail () in your dotclear/inc/config.php file.');
}


/* ====================================== SET the STEP parameter ================================= */
$step = (!empty($_GET['add']) ? (integer) $_GET['add'] : 0);
if (($step > 2) || ($step < 0))
	$step = 0;


/* ====================================== HTML HEADER ================================= */
?><html>
<head>
	<title><?php echo __('Mailjet settings'); ?></title>
	<style>
		ul.nav{
		    margin: 0;
		    padding: 0;
		}
		ul.nav li {
		    list-style: none;
		    font-weight: bold;
		    font-size: 1.1em;
		    float: left;
		    margin: 0;
		}
		ul.nav li a{
		    display: block;
		    padding: 0.5em 1.5em 0.5em 0;
		    text-decoration: none;
		    border: none;
		
		}
		ul.nav li a.active{
		    color: #D30E60;
		}
	</style>
    <?php
    	echo dcPage::jsLoad('index.php?pf=mailjet/assets/js/mailjet.js');
    ?>
</head>
<body>
<?php
	/* Display the breadcrump, the sub menu */
?>
	<h2>
		<?=html::escapeHTML($core->blog->name)?> &rsaquo;
		<?php
			if (isset($_GET['lists']))
			{
				$active = 'lists';
				?>
					<a href="<?=$p_url?>"><?=$page_title?></a> &rsaquo; 
					<span class="page-title"><?=__('Contact lists')?></span>
				<?php
			}
			elseif (isset($_GET['campaigns']))
			{
			    $active = 'campaigns';
				?>
					<a href="<?=$p_url?>"><?=$page_title?></a> &rsaquo; 
					<span class="page-title"><?=__('Campaigns')?></span>
				<?php
			}
			elseif (isset($_GET['stats']))
			{
			    $active = 'stats';
				?>
					<a href="<?=$p_url?>"><?=$page_title?></a> &rsaquo; 
					<span class="page-title"><?=__('Stats')?></span>
				<?php
			}
			else
			{
			    $active = 'settings';
				?>
					<span class="page-title"><?=$page_title?></span>					
				<?php
			}
		?>
	</h2>
	
	<ul class="nav">
	    <li>
	    	<a href="<?=$p_url?>" <?=($active == 'settings')?'class="active"':''?>><?=__('Settings')?></a>
	    </li>
	    <?php if(is_object($api) && $api->getContext() !== FALSE): ?>
		    <li>
		    	<a href="<?=$p_url?>&lists=1" <?=($active == 'lists')?'class="active"':''?>><?=__('Contact lists')?></a>
		    </li>
		    <li>
		    	<a href="<?=$p_url?>&campaigns=1" <?=($active == 'campaigns')?'class="active"':''?>><?=__('Campaigns')?></a>
		    </li>
		    <li>
		    	<a href="<?=$p_url?>&stats=1" <?=($active == 'stats')?'class="active"':''?>><?=__('Stats')?></a>
		    </li>
	    <?php endif; ?>
    </ul>
    <hr style="visibility: hidden; clear:both;" />
    
    
<?php 
	/* =========================== PRINT ENABLE MESSAGE ============================= */
	if (isset($_GET['saveconfig'])): ?>
		<p class="message"><?=$msg?></p>	    
<?php endif; ?>


<?php
	/* =========================== CONTENT OF THE PAGES ==============================*/
	if (!$step)
	{	
		if (isset($_GET['lists']))
		{
			if(is_object($api) && $api->getContext() !== FALSE)
			{
				?>
					<div class="multi-part" id="settings" title="<?php echo __('Contacts'); ?>">
				<?php
					echo '<iframe width="980px" height="1200" src="https://'.(($api->version == '0.1')?'www':(($api->version == 'REST')?'app':'www')).'.mailjet.com/contacts/lists?t='._get_auth_token($api, $settings).'&show_menu=none&u=dotclear-3.0&f=amc"></iframe>';
				?>
					</div>
				<?php
			}
			else 
				$core->error->add(sprintf(__('Please set API and Secret Kets first!')));
		}
		elseif(isset($_GET['campaigns']))
		{
			if(is_object($api) && $api->getContext() !== FALSE)
			{
				?>
					<div class="multi-part" id="settings" title="<?php echo __('Campaigns'); ?>">
				<?php
					echo '<iframe width="980px" height="1200" src="https://'.(($api->version == '0.1')?'www':(($api->version == 'REST')?'app':'www')).'.mailjet.com/campaigns?t='._get_auth_token($api, $settings).'&show_menu=none&u=dotclear-3.0&f=amc"></iframe>';
				?>
					</div>
				<?php
				}
			else 
				$core->error->add(sprintf(__('Please set API and Secret Kets first!')));
		} 
		elseif(isset($_GET['stats']))
		{
			if(is_object($api) && $api->getContext() !== FALSE)
			{
				?>
					<div class="multi-part" id="settings" title="<?php echo __('Contacts'); ?>">
				<?php
					echo '<iframe width="980px" height="1200" src="https://'.(($api->version == '0.1')?'www':(($api->version == 'REST')?'app':'www')).'.mailjet.com/stats?t='._get_auth_token($api, $settings).'&show_menu=none&u=dotclear-3.0&f=amc"></iframe>';
				?>
					</div>
				<?php
			}
			else 
				$core->error->add(sprintf(__('Please set API and Secret Kets first!')));
		}
		else 
		{
			?>
				<div class="multi-part" id="settings" title="<?php echo __('Settings'); ?>">
				    <form method="post" action="<?php echo $p_url; ?>">
				        <fieldset>
							<p>
				        		<?php 
				        			echo __('<a target="_blank" href="https://www.mailjet.com/signup?p=dotclear-3.0">Create your Mailjet account</a> if you don\'t have any.');
								?>
							</p>
				            <legend><?php echo __('General settings'); ?></legend>
				            <p>
				                <label class="classic"><?php echo __('Enabled :') . ' ' . form::checkbox ('mj_enabled', '1', $fields['mj_enabled']); ?></label>
				            </p>
				            <p>
				                <label class="classic"><?php echo __('Send test mail now :') . ' ' . form::checkbox ('mj_test', '1', $fields['mj_test']); ?></label>
				            </p>
				            <p>
				                <label class="classic"><?php echo __('Recipient of test mail :') . ' ' . form::field ('mj_test_address', 50, 50, $fields['mj_test_address']); ?></label>
				            </p>
				            <p>
				                <label class="classic"><?php echo __('Sender email address :') . ' ' . form::field ('mj_sender_address', 50, 50, ($fields['mj_sender_address'] ? $fields['mj_sender_address'] : DC_ADMIN_MAILFROM)); ?></label>
				            </p>
				        </fieldset>
				        <fieldset>
				            <legend><?php echo __('Mailjet settings'); ?></legend>
				            <p>
				                <label class="classic"><?php echo __('API Key :') . ' ' . form::field ('mj_username', 32, 32, $fields['mj_username']); ?></label>
				            </p>
				            <p>
				                <label class="classic"><?php echo __('Secret Key :') . ' ' . form::field ('mj_password', 32, 32, $fields['mj_password']); ?></label>
				            </p>
				        </fieldset>
				        <p>
				            <?php echo $core->formNonce(); ?>
				        </p>
				        <p>
				            <input type="submit" name="saveconfig" value="<?php echo __('Save configuration'); ?>" />
				        </p>
				    </form>
				</div>
			<?php 
		}
	}
?>
</body>
</html>