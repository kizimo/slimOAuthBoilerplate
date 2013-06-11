<?php
/**
 * Step 1: Require the Slim Framework
 *
 * If you are not using Composer, you need to require the
 * Slim Framework and register its PSR-0 autoloader.
 *
 * If you are using Composer, you can skip this step.
 */
require 'Slim/Slim.php';
require 'Slim/View.php';
require_once('library/DB/IgnitePDO.php');

/*
 * Always announce XRDS OAuth discovery
 */
//header('X-XRDS-Location: http://' . $_SERVER['SERVER_NAME'] . '/services.xrds');
//header('X-XRDS-Location: http://localhost:8888/Codebase/oauth-php/example/server/www/services.xrds');
header('X-XRDS-Location: http://'. $_SERVER['SERVER_NAME'].'/services.xrds.php');

/*
 * Initialize the database connection
 */


//1) get request token
//2) authorize
//3) get access token
//4) go nuts on api requests


require_once 'library/ISMOAuthServer.php';
require_once 'library/ISMOAuth2Server.php';
/*
 * Initialize OAuth store
 */
$oauthDB = new Mongo('mongodb://auth:auth@localhost/auth');
require_once 'library/oauth/OAuthStore.php';
OAuthStore::instance('Mongo', array('conn' => $oauthDB));


//$server = new ISMOAuthServer();
//require_once("library/oauth/OAuth.php");
//require_once("library/oauth/OAuth_TestServer.php");

/*
 * Config Section
 */
//$domain = $_SERVER['HTTP_HOST'];
//$base = "/oauth/example";
//$base_url = "http://$domain$base";

/**
 * Some default objects
 */

//$test_server = new TestOAuthServer(new MockOAuthDataStore());
//$hmac_method = new OAuthSignatureMethod_HMAC_SHA1();
//$plaintext_method = new OAuthSignatureMethod_PLAINTEXT();
//$rsa_method = new TestOAuthSignatureMethod_RSA_SHA1();

//$test_server->add_signature_method($hmac_method);
//$test_server->add_signature_method($plaintext_method);
//$test_server->add_signature_method($rsa_method);

//$sig_methods = $test_server->get_signature_methods();


\Slim\Slim::registerAutoloader();

/**
 * Step 2: Instantiate a Slim application
 *
 * This example instantiates a Slim application using
 * its default settings. However, you will usually configure
 * your Slim application now by passing an associative array
 * of setting names and values into the application constructor.
 */
$app = new \Slim\Slim();

/**
 * Step 3: Define the Slim application routes
 *
 * Here we define several Slim application routes that respond
 * to appropriate HTTP request methods. In this example, the second
 * argument for `Slim::get`, `Slim::post`, `Slim::put`, and `Slim::delete`
 * is an anonymous function.
 */

// GET route


$app->get('/', function () {
	echo "test";
});

$app->get('/entries/:limit', function ($limit) {
	
	/** oauth 1.0
	* $server = new ISMOAuthServer();
	* if($server->checkAuth()){
	*/

	// oauth 2
	$oauth = new ISMOAuth2Server();
	if ($oauth->verifyAccessToken()){
		$idb = new IgnitePDO($oauth->getDBConnect($oauth->getTokenFromHeader()));
		sendSlimJson($idb->getEntries($limit));
	} else {
		sendSlimJson(json_encode(array('error'=>'invalid request')));
	}
});

$app->get('/entry/:id', function ($id) {
	//$entry = $idb->executeSQL("SELECT * FROM entries WHERE ID=$id");
	// oauth 2
	$oauth = new ISMOAuth2Server();
	if ($oauth->verifyAccessToken()){
		$idb = new IgnitePDO($oauth->getDBConnect($oauth->getTokenFromHeader()));
		sendSlimJson($idb->getEntry($id));
	} else {
		sendSlimJson(json_encode(array('error'=>'invalid request')));
	}
});

/**
*
* OAuth 2.o
*
*/
$app->get('/2.0', function () {
	$oauth = new ISMOAuth2Server();
	$auth_params = $oauth->getAuthorizeParams();
	if ($auth_params){
		$accessArray = array_merge($_GET,$oauth->finishClientAuthorization2($_GET));
		$_POST = $accessArray;
		$_POST["grant_type"] = "authorization_code";
		sendSlimJson($oauth->grantAccessToken());	
	}
});

$app->get('/2.0/register', function () {
	\Slim\Slim::getInstance()->render('register2.php');
});

$app->post('/2.0/register', function () {
	$oauth = new ISMOAuth2Server();
	$oauth->addClient($_POST["client_id"], $_POST["client_secret"], $_POST["redirect_uri"]);
});

/**
*
* OAuth 1.o
*
*/
$app->get('/1.0/register', function() {
	//assert_logged_in();
	\Slim\Slim::getInstance()->render('register.php', array('id' => 't3st'));
});

$app->post('/1.0/register', function () {
	//assert_logged_in();
	var_dump($_POST);
	try {
		$store = OAuthStore::instance();
		$key   = $store->updateConsumer($_POST,1, true); //TODO::fix hardcode
		$c = $store->getConsumer($key,'1');
		echo 'Your consumer key is: <strong>' . $c['consumer_key'] . '</strong><br />';
		echo 'Your consumer secret is: <strong>' . $c['consumer_secret'] . '</strong><br />';
	} catch (OAuthException2 $e){
		echo '<strong>Error: ' . $e->getMessage() . '</strong><br />';
	}
});

$app->post('/1.0/request_token', function () {
	$server = new ISMOAuthServer();
	echo serialize($server->requestToken());
});

$app->get('/1.0/request_token', function () {
	$server = new ISMOAuthServer();
	echo serialize($server->requestToken());
});

$app->get('/1.0/authorize', function () {
	//assert_logged_in();
	$server = new ISMOAuthServer();
	try{
		$server->authorizeVerify();
		echo serialize($server->authorizeFinish(true, 1));
	}catch (OAuthException2 $e){
		echo "Failed OAuth Request: " . $e->getMessage();
	}
});


$app->post('/1.0/access_token', function () {
	$server = new ISMOAuthServer();
	echo serialize($server->accessToken());
});

$app->get('/1.0/access_token', function () {
	$server = new ISMOAuthServer();
	echo serialize($server->accessToken());
});

// POST route
//$app->post('/post', function () {
//    echo 'This is a POST route';
//});

// PUT route
//$app->put('/put', function () {
//    echo 'This is a PUT route';
//});

// DELETE route
//$app->delete('/delete', function () {
//    echo 'This is a DELETE route';
//});


function sendSlimJson($data) {
	\Slim\Slim::getInstance()->response()->header('Cache-Control', 'no-cache, must-revalidate');
	\Slim\Slim::getInstance()->response()->header('Content-Type', 'application/json');
	\Slim\Slim::getInstance()->response()->body($data);
}


/**
 * Step 4: Run the Slim application
 *
 * This method should be called last. This executes the Slim application
 * and returns the HTTP response to the HTTP client.
 */
$app->run();
