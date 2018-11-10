<?php

class PluginPhpsamlPhpsaml
{

    const SESSION_GLPI_NAME_ACCESSOR = 'glpiname';
    const SESSION_VALID_ID_ACCESSOR = 'valid_id';
	
	static private $init = false; 
	static private $docsPath = GLPI_PLUGIN_DOC_DIR.'/phpsaml/';
	static public $auth;
	static public $phpsamlsettings;
	static public $nameid;
	static public $userdata;
	static public $nameidformat;
	static public $sessionindex;
	static public $rightname = 'plugin_phpsaml_phpsaml';
	


	/**
     * Constructor
    **/
	function __construct() {
		
		require_once('libs.php');
	}
	
	public static function init() 
	{
		if (!self::$init) {
			require_once('libs.php');
			require_once(GLPI_ROOT .'/plugins/phpsaml/lib/php-saml/settings.php');
			//$samlSettings = new OneLogin\Saml2\Settings($phpsamlsettings);
			self::$phpsamlsettings = $phpsamlsettings;
			self::$auth = new OneLogin\Saml2\Auth(self::$phpsamlsettings);
			self::$init = true; 
		}
	}
	
    /**
     * @return bool
     */
    static public function isUserAuthenticated()
    {
        if (version_compare(GLPI_VERSION, '0.85', 'lt') && version_compare(GLPI_VERSION, '0.84', 'gt')) {
            return isset($_SESSION[self::SESSION_GLPI_NAME_ACCESSOR]);
        } else {
            return isset($_SESSION[self::SESSION_GLPI_NAME_ACCESSOR])
            && isset($_SESSION[self::SESSION_VALID_ID_ACCESSOR])
            && $_SESSION[self::SESSION_VALID_ID_ACCESSOR] === session_id();
        }
    }
	
	static public function glpiLogin($relayState = null)
    {
        $auth = new PluginPhpsamlAuth();
        if($auth->loadUserData(self::$nameid)->checkUserData()){
			Session::init($auth);
			self::redirectToMainPage($relayState);
		}
		throw new Exception('User not found.');
    }
	
	static public function glpiLogout()
	{
		Session::destroy();

		//Remove cookie to allow new login
		$cookie_name = session_name() . '_rememberme';
		$cookie_path = ini_get('session.cookie_path');

		if (isset($_COOKIE[$cookie_name])) {
		   setcookie($cookie_name, '', time() - 3600, $cookie_path);
		   unset($_COOKIE[$cookie_name]);
		}
	}
	
	static public function ssoRequest()
	{
		//$auth = new OneLogin\Saml2\Auth();
		try {
			self::$auth->login();
		} catch (Exception $e) {
			echo 'Caught Exception: ', $e->getMessage(), "\n";
		}
	}
	
	static public function sloRequest()
	{
		$returnTo 		= null;
		$parameters 	= array();
		$nameId 		= null;
		$sessionIndex 	= null;
		$nameIdFormat 	= null;
		
		if (isset(self::$nameid)) {
			$nameId = self::$nameid;
		}
		if (isset(self::$sessionindex)) {
			$sessionIndex = self::$sessionindex;
		}
		if (isset(self::$nameidformat)) {
			$nameIdFormat = self::$nameidformat;
		}

		self::glpiLogout();
		
		//$auth = new OneLogin\Saml2\Auth();
		try {
			self::$auth->logout($returnTo, $parameters, $nameId, $sessionIndex, false, $nameIdFormat);
		} catch (Exception $e) {
			echo 'Caught Exception: ', $e->getMessage(), "\n";
		}
	}
	
    static public function redirectToMainPage($relayState = null)
    {
        global $CFG_GLPI;
        $REDIRECT = "";
        $destinationUrl = $CFG_GLPI['url_base'];

        if ($relayState) {
            $REDIRECT = "?redirect=" . rawurlencode($relayState);
        }

        if (isset($_SESSION["glpiactiveprofile"])) {
            if ($_SESSION["glpiactiveprofile"]["interface"] == "helpdesk") {
                if ($_SESSION['glpiactiveprofile']['create_ticket_on_login']
                    && empty($REDIRECT)
                ) {
                    $destinationUrl .= $CFG_GLPI['root_doc'] . "/front/helpdesk.public.php?create_ticket=1";
                } else {
                    $destinationUrl .= $CFG_GLPI['root_doc'] . "/front/helpdesk.public.php$REDIRECT";
                }

            } else {
                if ($_SESSION['glpiactiveprofile']['create_ticket_on_login']
                    && empty($REDIRECT)
                ) {
                    $destinationUrl .= $CFG_GLPI['root_doc'] . "/front/ticket.form.php";
                } else {
                    $destinationUrl .= $CFG_GLPI['root_doc'] . "/front/central.php$REDIRECT";
                }
            }
        }

        header("Location: " . $destinationUrl);
    }
}
