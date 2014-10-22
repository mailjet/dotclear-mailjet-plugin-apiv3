<?php
  
 /*
 * LICENSE BLOCK
 * 
 * This program is free software. It comes without any warranty, to the extent permitted by applicable law. You can redistribute it
 * and/or modify it under the terms of the Do What The Fuck You Want To Public License, Version 2, as published by Sam Hocevar. See
 * http://sam.zoy.org/wtfpl/COPYING for more details.
 * 
 */
 
 /**
 * This is Api Strategy Interface
 * @author		Pavel Tashev  
 * @author		Mailjet
 * @link		http://www.mailjet.com/
 */
 
 # ============================================== Interface ============================================== #
 interface Mailjet_Api_Interface 
 {
 	public function getContactLists($params);
 	public function addContact($params);
	public function removeContact($params);
	public function unsubContact($params);
	public function subContact($params);	
	public function getAuthToken($params);	
	public function validateEmail($email);
 }
 
 
 
 
 
 # ============================================== Strategy ============================================== #
 # Strategy ApiV1
 class Mailjet_Api_Strategy_V1 extends Mailjet_Api_V1 implements Mailjet_Api_Interface
 {
 	/**
	 * Get full list of contact lists
	 * 
	 * @param (array) $param = array('limit', ...) 
	 * @return (object)
	 */
 	public function getContactLists($params)
	{
		// Set input parameters
		$input = array();
		if(isset($params['limit'])) $input['limit'] = $params['limit'];
		
		// Get the list
		$response = $this->listsAll($input);

		// Check if the list exists
		if(isset($response->status) && $response->status == 'OK')
		{
			$lists = array();
			foreach ($response->lists as $list)
			{
				$lists[] = array(
					'value' 		=> $list->id,
					'label' 		=> $list->label,
					'subscribers'	=> $list->subscribers,
				);
			}
			return $lists;
		}		
		
		return (object) array('Status' => 'ERROR');
	}
	
	/**
	 * Add a contact to a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
 	public function addContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
		
		// Add the contact
		$response = $this->listsAddContact(array(
			'method'	=> 'POST',
			'contact'	=> $params['Email'],
			'id'		=> $params['ListID']
		));
				
		// Check if the contact is added 
		if($response)
			return (object) array('Status' => 'OK');
		
		return (object) array('Status' => 'ERROR');
	}
	
	/**
	 * Remove a contact from a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
	public function removeContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
		
		// Unsubscribe the contact
		$response = $this->listsRemoveContact(array(
			'method'	=> 'POST',
			'contact'	=> $params['Email'],
			'id'		=> $params['ListID']
		));
		
		// Check if the contact is added 
		if($response)
			return (object) array('Status' => 'OK');
		
		return (object) array('Status' => 'OK');
	}
	
	/**
	 * Unsubscribe a contact from a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	*/
	public function unsubContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
			
		// Unsubscribe the contact
		$response = $this->listsUnsubContact(array(
			'method'	=> 'POST',
			'contact'	=> $params['Email'],
			'id'		=> $params['ListID']
		));
		
		// Check if the contact is added 
		if($response)
			return (object) array('Status' => 'OK');
		
		return (object) array('Status' => 'OK');
	}
	
	/**
	 * Subscribe a contact to a contact list with ID = ListID
	 *
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
	public function subContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
		
		// Subscribe the user
		$response = $this->listsAddContact(array(
			'method'	=> 'POST',
			'id'		=> $params['ListID'],
			'contact'	=> $params['Email'],
			'force'		=> 1,
		));
		
		// Check if the contact is added 
		if($response)
			return (object) array('Status' => 'OK');
		
		return (object) array('Status' => 'OK');
	}
	
	/**
	 * Get the authentication token for the iframes
	 * 
	 * @param (array) $param = array('APIKey', 'SecretKey', ...) 
	 * @return (object)
	*/
	public function getAuthToken($params)
	{		
		// Check if the input data is OK
		if(strlen(trim($params['APIKey'])) == 0 || strlen(trim($params['SecretKey'])) == 0)
			return (object) array('Status' => 'ERROR');	
			
	 	if (isset($params['MailjetToken']))
		{
			$op = json_decode($params['MailjetToken']);
			if ($op->timestamp > time() - 3600)
				return $op->token;
		}

		// Get the culture
		if(isset($lang) && $lang != null)
		{
			$locale = substr($lang->getTag(), 0, 2);
			if (!in_array($locale, array('en', 'fr', 'es', 'de')))
				$locale = 'en';
		} else {
			$locale = 'en';
		}		
		
		// Define some required data
		$url = $this->apiUrl.'/apiKeyauthenticate?output=json';
		$data = array(
			'allowed_access[0]' => 'stats',
			'allowed_access[1]' => 'contacts',
			'allowed_access[2]' => 'campaigns',
			'lang' 				=> $locale,
			'default_page'		=> 'campaigns',
			'type' 				=> 'page',
			'apikey' 			=> $params['APIKey']
		);
		
		// Execute POST request
		$curl = curl_init();
		curl_setopt_array($curl, array(
		    CURLOPT_RETURNTRANSFER => 1,
		    CURLOPT_URL => $url,
		    CURLOPT_USERAGENT => 'Codular Sample cURL Request',
		    CURLOPT_POST => 1,
		    CURLOPT_POSTFIELDS => $data
		));
		curl_setopt($curl, CURLOPT_HTTPHEADER, array(
    		"Authorization: Basic ".base64_encode($params['APIKey'] . ':' . $params['SecretKey'])
    	));
		$result = curl_exec($curl);
		$resp = json_decode($result);
		
		if (is_object($resp))
			if ($resp->status == 'OK')
				return $resp->token;
		
		return (object) array('Status' => 'ERROR'); 
	}	

	/**
	 * Validate if $email is real email
	 * 
	 * @param (string) $email 
	 * @return (boolean) TRUE|FALSE 
	 */
	public function validateEmail($email) {
		return (preg_match("/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/", $email) || !preg_match("/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/", $email)) ? FALSE : TRUE;
	}
 }


 # Strategy ApiV3
 class Mailjet_Api_Strategy_V3 extends Mailjet_Api_V3 implements Mailjet_Api_Interface
 {
 	/**
	 * Get full list of contact lists
	 * 
	 * @param (array) $param = array('limit', ...) 
	 * @return (object)
	 */
 	public function getContactLists($params)
	{
		// Set input parameters
		$input = array();
		if(isset($this->_akid))
			$input['akid'] = $this->_akid; 
			
		if(isset($params['limit'])) $input['limit'] = $params['limit'];
		
		// Get the list
		$response = $this->liststatistics($input);

		// Check if the list exists
		if(isset($response->Data) && count($response->Data) > 0)
		{
			$lists = array();
			foreach ($response->Data as $list)
			{
				$lists[] = array(
					'value' 		=> $list->ID,
					'label' 		=> $list->Name,
					'subscribers'	=> $list->SubscriberCount,
				);
			}
			return $lists;
		}		
		
		return (object) array('Status' => 'ERROR');
	}
	
	/**
	 * Add a contact to a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
 	public function addContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
		
		// Add the contact
		$result = $this->manycontacts(array(
			'method'			=> 'POST',
			'Action'			=> 'Add',
			'Addresses'			=> array($params['Email']),
			'ListID'			=> $params['ListID'],
		));

		// Check if any error
		if(isset($result->Data['0']->Errors->Items)) {
			if( strpos($result->Data['0']->Errors->Items[0]->ErrorMessage, 'duplicate') !== FALSE )
				return (object) array('Status' => 'DUPLICATE');
			else
				return (object) array('Status' => 'ERROR');	
		}		
		
		$this->subContact($params);
		return (object) array('Status' => 'OK');
	}
	
	/**
	 * Remove a contact from a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
	public function removeContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
			
		// Get the contact	
		$result = $this->listrecipient(array(
			'akid'          => $this->_akid,
			'method'        => 'GET',
			'ListID'		=> $params['ListID'],
			'ContactEmail'  => $params['Email']
        ));
        if($result->Count > 0) 
        {
            foreach($result->Data as $contact) 
			{
				// Remove the contact
				$response = $this->listrecipient(array(
					'akid'				=> $this->_akid,
					'method'			=> 'delete',
					'ID'				=> $contact->ID
				));
            }
			
			// Check if the unsubscribe is done correctly
			if(isset($response->Data[0]->ID))
				return (object) array('Status' => 'OK');
        }

		return (object) array('Status' => 'ERROR');
	}
	 
	/**
	 * Unsubscribe a contact from a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	*/
	public function unsubContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
		
		// Get the contact	
		$result = $this->listrecipient(array(
			'akid'          => $this->_akid,
			'method'        => 'GET',
			'ListID'		=> $params['ListID'],
			'ContactEmail'  => $params['Email']
        ));
        if($result->Count > 0) 
        {
            foreach($result->Data as $contact) 
            {
                if($contact->IsUnsubscribed !== TRUE)
                {
                      $response = $this->listrecipient(array(
                            'akid'    			=> $this->_akid,
                            'method'   			=> 'PUT',
                            'ID'       			=> $contact->ID,
                            'IsUnsubscribed' 	=> 'true',
                            'UnsubscribedAt' 	=> date("Y-m-d\TH:i:s\Z", time()),
                      ));
                } 
            }
			
			// Check if the unsubscribe is done correctly
			if(isset($response->Data[0]->ID))
				return (object) array('Status' => 'OK');
        }
		
		return (object) array('Status' => 'ERROR');
	}
	
	/**
	 * Subscribe a contact to a contact list with ID = ListID
	 *
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
	public function subContact($params)
	{
		// Check if the input data is OK
		if(!is_numeric($params['ListID']) || !$this->validateEmail($params['Email']))
			return (object) array('Status' => 'ERROR');	
		
		// Get the contact	
		$result = $this->listrecipient(array(
			'akid'          => $this->_akid,
			'method'        => 'GET',
			'ListID'		=> $params['ListID'],
			'ContactEmail'  => $params['Email']
        ));		
		
        if($result->Count > 0) 
        {
            foreach($result->Data as $contact) 
            {
                if($contact->IsUnsubscribed === TRUE)
                {
	                  $response = $this->listrecipient(array(
	                        'akid'    			=> $this->_akid,
	                        'method'   			=> 'PUT',
	                        'ID'       			=> $contact->ID,
	                        'IsUnsubscribed' 	=> 'false',	                        
	                  ));
                } 
            }
			
			// Check if the subscribe is done correctly
			if(isset($response->Data[0]->ID))
				return (object) array('Status' => 'OK');
        }
		
		return (object) array('Status' => 'ERROR');
	}
	
	/**
	 * Get the authentication token for the iframes
	 * 
	 * @param (array) $param = array('APIKey', 'SecretKey', ...) 
	 * @return (object)
	*/
	public function getAuthToken($params)
	{
		// Check if the input data is OK
		if(strlen(trim($params['APIKey'])) == 0 || strlen(trim($params['SecretKey'])) == 0)
			return (object) array('Status' => 'ERROR');	

		// Get the ID of the Api Key
	 	$api_key_response = $this->apikey(array(
			'method' => 'GET',
			'APIKey' => $params['APIKey']
		));
		
		// Check if the response contains data
		if(!isset($api_key_response->Data[0]->ID))
			return (object) array('Status' => 'ERROR');

		// Get token
		$response = $this->apitoken(array(
			'AllowedAccess' =>  'campaigns,contacts,reports,stats,preferences,pricing,account',
			'method' 		=> 'POST',			
			'APIKeyID' 		=> $api_key_response->Data[0]->ID,
			'TokenType' 	=> 'iframe',			
			'CatchedIp'  	=> $_SERVER['REMOTE_ADDR'],
			'log_once' 		=> TRUE,
			'IsActive'		=> TRUE
		));	

	 	// Get and return the token
		if(isset($response->Data) && count($response->Data) > 0)
			return $response->Data[0]->Token;
		
		return (object) array('Status' => 'ERROR');
	}	
	
	/**
	 * Validate if $email is real email
	 * 
	 * @param (string) $email 
	 * @return (boolean) TRUE|FALSE 
	 */
	public function validateEmail($email) {
		return (preg_match("/(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)/", $email) || !preg_match("/^.+\@(\[?)[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/", $email)) ? FALSE : TRUE;
	}
 }
 
 
 
 
 
 # ============================================== Context ============================================== #
 class Mailjet_Api
 {
 	private $context;
	public $version; 
	public $mj_host; 
	public $mj_mailer;
	
	public function __construct($mailjet_username, $mailjet_password)
  	{  		
  		# Check the type of the user and set the corresponding Context/Strategy
  		// Set API V3 context and get the user and check if it's V3   		
		$this->setContext(new Mailjet_Api_Strategy_V3($mailjet_username, $mailjet_password));
		$response = $this->context->getContactLists(array('limit' => 1));
		if(isset($response->Status) && $response->Status == 'ERROR')
		{
			// Set API V1 context and get the contact lists of this user and check if it's V1
			$this->setContext(new Mailjet_Api_Strategy_V1($mailjet_username, $mailjet_password));	
			$response = $this->context->getContactLists(array('limit' => 1));
			if(isset($response->Status) && $response->Status == 'ERROR')
			{				
				$this->clearContext();			
			} 
			else {			
				// Get the version of the API
				$this->version = $this->context->version;
				
				// Some contacts
				$this->mj_host = 'in.mailjet.com';
				$this->mj_mailer = 'X-Mailer:WP-Mailjet/0.1';
			}
		} else {
			// Get the version of the API
			$this->version = $this->context->_version;
			
			// Some contacts
			$this->mj_host = 'in-v3.mailjet.com';
			$this->mj_mailer = 'X-Mailer:WP-Mailjet/0.1';
		}		
	}
	
	/**
	 * Set the context of the Api - V1 or V3 
	 *
     * @param Mailjet_Api_Interface $context
     * @return void
     */
	private function setContext(Mailjet_Api_Interface $context)
    {
        $this->context = $context;
    }
	
	/**
	 * Clear the context
	 *
     * @param void
     * @return void
     */
	private function clearContext()
    {
        $this->context = FALSE;
    }
	
	/**
	 * Get the context of the Api - V1 or V3 
	 *
     * @param void
     * @return void
     */
	public function getContext()
    {
        return $this->context;
    }
	
	
	/**
	 * Get full list of contact lists
	 * 
	 * @param (array) $param = array('limit', ...) 
	 * @return (object)
	 */
	public function getContactLists($params)
	{	
		// Check if we have context, if no, return error
        if($this->context === FALSE)
			return (object) array('Status' => 'ERROR');
			
		return $this->context->getContactLists($params);
	}
	
	/**
	 * Add a contact to a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
	 public function addContact($params)
	 {
	 	// Check if we have context, if no, return error
        if($this->context === FALSE)
			return (object) array('Status' => 'ERROR');
		
	 	return $this->context->addContact($params);
	 }
	 
	 /**
	 * Remove a contact from a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
	 public function removeContact($params)
	 {
	 	// Check if we have context, if no, return error
        if($this->context === FALSE)
			return (object) array('Status' => 'ERROR');
		
	 	return $this->context->removeContact($params);
	 }
	 
	 /**
	 * Unsubscribe a contact from a contact list with ID = ListID
	 * 
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	*/
	  public function unsubContact($params)
	  {
	  	// Check if we have context, if no, return error
        if($this->context === FALSE)
			return (object) array('Status' => 'ERROR');
		
	  	return $this->context->unsubContact($params);
	  }
	  
	 /**
	 * Subscribe a contact to a contact list with ID = ListID
	 *
	 * @param (array) $param = array('Email', 'ListID', ...) 
	 * @return (object)
	 */
	  public function subContact($params)
	  {
	  	// Check if we have context, if no, return error
        if($this->context === FALSE)
			return (object) array('Status' => 'ERROR');
		
	  	return $this->context->subContact($params);
	  }
	  
	  /**
		* Get the authentication token for the iframes
		* 
		* @param (array) $param = array('APIKey', 'SecretKey', ...) 
		* @return (object)
	  */
	  public function getAuthToken($params)
	  {
	  	// Check if we have context, if no, return error
        if($this->context === FALSE)
			return (object) array('Status' => 'ERROR');
		
	  	return $this->context->getAuthToken($params);
	  }	
	  
	  /**
	  * Validate if $email is real email
	  * 
	  * @param (string) $email 
	  * @return (boolean) TRUE|FALSE 
	  */
	  public function validateEmail($email) {
	  	// Check if we have context, if no, return error
        if($this->context === FALSE)
			return (object) array('Status' => 'ERROR');
		
		return $this->context->validateEmail($email);
	  }
 }
 