<?php

require_once 'oauth/OAuthServer.php';

class ISMOAuthServer extends OAuthServer{
	
	public function requestToken () {
		OAuthRequestLogger::start($this);
		try
		{
			$this->verify(false);
			
			$options = array();
			$ttl     = $this->getParam('xoauth_token_ttl', false);
			if ($ttl)
			{
				$options['token_ttl'] = $ttl;
			}

 			// 1.0a Compatibility : associate callback url to the request token
 			$cbUrl   = $this->getParam('oauth_callback', true);
 			if ($cbUrl) {
 				$options['oauth_callback'] = $cbUrl;
 			}
			
			// Create a request token
			$store  = OAuthStore::instance();
			$token  = $store->addConsumerRequestToken($this->getParam('oauth_consumer_key', true), $options);
			$result = 'oauth_callback_confirmed=1&oauth_token='.$this->urlencode($token['token'])
					.'&oauth_token_secret='.$this->urlencode($token['token_secret']);

			if (!empty($token['token_ttl']))
			{
				$result .= '&xoauth_token_ttl='.$this->urlencode($token['token_ttl']);
			}

			$request_token = $token['token'];
			
			//header('HTTP/1.1 200 OK');
			//header('Content-Length: '.strlen($result));
			//header('Content-Type: application/x-www-form-urlencoded');

			return $result;
		}
		catch (OAuthException2 $e)
		{
			$request_token = false;
			return "OAuth Verification Failed: " . $e->getMessage();
		}

		OAuthRequestLogger::flush();
		return $request_token;
	}
	
	public function accessToken () {
		OAuthRequestLogger::start($this);

		try
		{
			$this->verify('request');

			$options = array();
			$ttl     = $this->getParam('xoauth_token_ttl', false);
			if ($ttl)
			{
				$options['token_ttl'] = $ttl;
			}

			$verifier = $this->getParam('oauth_verifier', false);
 			if ($verifier) {
 				$options['verifier'] = $verifier;
 			}
			
			$store  = OAuthStore::instance();
			$token  = $store->exchangeConsumerRequestForAccessToken($this->getParam('oauth_token', true), $options);
			$result = 'oauth_token='.$this->urlencode($token['token'])
					.'&oauth_token_secret='.$this->urlencode($token['token_secret']);
					
			if (!empty($token['token_ttl']))
			{
				$result .= '&xoauth_token_ttl='.$this->urlencode($token['token_ttl']);
			}
					
			return $result;
		} catch (OAuthException2 $e) {
			return "OAuth Verification Failed: " . $e->getMessage();
		}
		
		OAuthRequestLogger::flush();
		exit();
	}	

	public function checkAuth() {
		return ($this->verifyIfSigned())?true:false;
	}
}
?>