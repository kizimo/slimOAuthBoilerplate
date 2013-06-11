<?php

class IgniteOauth
{

	private $id;
	private $key;
	private $secret;
	private $token;
	
	public function _construct(){}
	
	public function authClient($k,$s){
		//$this->checkCredentials($k,$s);
		//try{
			//$oauth = new OAuth(OAUTH_CONSUMER_KEY,OAUTH_CONSUMER_SECRET);
			//$oauth->setToken("token","token-secret");
		try {
			$oauth = new OAuth($k,$cons_secret,OAUTH_SIG_METHOD_HMACSHA1,OAUTH_AUTH_TYPE_URI);
			$oauth->enableDebug();  
		} catch(OAuthException $e) {
		  print_r($e);
		}
	}

	private function checkCredentials($k,$s){
		$this->key = $k;
		$this->secret = $s;
		$idb = new IgnitePDO();
		$idb->setDB('127.0.0.1','auth','8889','root','root');
		$this->id = $idb->executeSQL("SELECT id FROM customers WHERE key=$this->key and secret=$this->secret");
		//$sql="select consumer_secret,uid from `w_user` where consumer_key='" . $dbmain->real_escape_string($_REQUEST['oauth_consumer_key']) ."'";
		if ($this->id){
			$this->token = $idb->executeSQL("SELECT token FROM tokens WHERE customer_id=$this->id");
			if ($this->token){
				
				
			} else {
				$this->token = generateToken()
			}
		}
		
	}

	private function generateToken(){
		$consumer = new OAuthConsumer($this->key, $this->secret);
		$sig_method = new OAuthSignatureMethod_HMAC_SHA1;
 
    	$sig = $_REQUEST['oauth_signature'];    
    	$req = new OAuthRequest($method, $uri);
    	$valid = $sig_method->check_signature( $req, $consumer, null, $sig );
    }

 
    private function returnAuth($valid){
    	if(!$valid){
        	header('HTTP/1.1 401 Unauthorized', true, 401);
        	die('HTTP/1.1 401 Unauthorized');
        } else {
	       	return $this->data->uid;
	    }
	}
 
}