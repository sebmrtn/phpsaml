<?php

/*
   ------------------------------------------------------------------------
   Derrick Smith - PHP SAML Plugin
   Copyright (C) 2014 by Derrick Smith
   ------------------------------------------------------------------------

   LICENSE

   This file is part of phpsaml project.

   PHP SAML Plugin is free software: you can redistribute it and/or modify
   it under the terms of the GNU Affero General Public License as published by
   the Free Software Foundation, either version 3 of the License, or
   (at your option) any later version.

   phpsaml is distributed in the hope that it will be useful,
   but WITHOUT ANY WARRANTY; without even the implied warranty of
   MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
   GNU Affero General Public License for more details.

   You should have received a copy of the GNU Affero General Public License
   along with phpsaml. If not, see <http://www.gnu.org/licenses/>.

   ------------------------------------------------------------------------

   @package   phpsaml
   @author    Derrick Smith
   @co-author
   @copyright Copyright (c) 2018 by Derrick Smith
   @license   AGPL License 3.0 or (at your option) any later version
              http://www.gnu.org/licenses/agpl-3.0-standalone.html
   @since     2018

   ------------------------------------------------------------------------
 */
 
define ("PLUGIN_PHPSAML_VERSION", "1.0.0");

/**
 * Definition of the plugin version and its compatibility with the version of core
 *
 * @return array
 */
function plugin_version_phpsaml()
{

    return array('name' => "PHP SAML",
        'version' => PLUGIN_PHPSAML_VERSION,
        'author' => 'Derrick Smith',
        'license' => 'GPLv2+',
        'homepage' => 'http://derrick-smith.com',
        'minGlpiVersion' => '0.84'); // For compatibility / no install in version < 0.80
}

/**
 * Blocking a specific version of GLPI.
 * GLPI constantly evolving in terms of functions of the heart, it is advisable
 * to create a plugin blocking the current version, quite to modify the function
 * to a later version of GLPI. In this example, the plugin will be operational
 * with the 0.84 and 0.85 versions of GLPI.
 *
 * @return boolean
 */
function plugin_phpsaml_check_prerequisites()
{

    if (version_compare(GLPI_VERSION, '0.84', 'lt') || version_compare(GLPI_VERSION, '9.4', 'gt')) {
        echo "This plugin requires GLPI >= 0.84 and GLPI <= 9.4";
        return false;
    }

    return true;
}

/**
 * Control of the configuration
 *
 * @param type $verbose
 * @return boolean
 */
function plugin_phpsaml_check_config($verbose = false)
{
    if (true) { // Your configuration check
       return true;
    }

    if ($verbose) {
        echo 'Installed / not configured';
    }

    return false;
}

/**
 * Initialization of the plugin
 *
 * @global array $PLUGIN_HOOKS
 */
function plugin_init_phpsaml()
{
    global $PLUGIN_HOOKS;
    $PLUGIN_HOOKS['post_init']['phpsaml'] = 'plugin_post_init_phpsaml';
    $PLUGIN_HOOKS['csrf_compliant']['phpsaml'] = true;
	
	Plugin::registerClass('PluginPhpsaml');
	
	// Config page
   if (Session::haveRight('config', UPDATE)) {
      $PLUGIN_HOOKS['config_page']['phpsaml'] = 'front/config.php';
   }

   //Redirect code
   $PLUGIN_HOOKS['redirect_page']['phpsaml'] = 'phpsaml.form.php';

}

function plugin_post_init_phpsaml()
{
	PluginPhpsamlPhpsaml::init();
	if (strpos($_SERVER['REQUEST_URI'], 'front/cron.php')){
		return;
	}
	
	if (strpos($_SERVER['REQUEST_URI'], 'apirest.php')){
		return;
	}
	
	if (strpos($_SERVER['REQUEST_URI'], 'front/acs.php')){
		return;
	}

	if (class_exists('PluginFusioninventoryCommunication') && strpos($_SERVER['REQUEST_URI'], '/plugins/fusioninventory/') !== false && strpos($_SERVER['HTTP_USER_AGENT'], 'FusionInventory-Agent_') !== false){
		$access = date("Y/m/d H:i:s");
		syslog(LOG_WARNING, "Fusion Inventory: $access {$_SERVER['REMOTE_ADDR']} ({$_SERVER['HTTP_USER_AGENT']})");
		return;
	}
	
	$phpsamlConfig = new PluginPhpsamlConfig();
	$config = $phpsamlConfig->getConfig();

	if (!empty($config['saml_sp_certificate']) && !empty($config['saml_sp_certificate_key']) && !empty($config['saml_idp_entity_id']) && !empty($config['saml_idp_single_sign_on_service']) && !empty($config['saml_idp_single_logout_service']) && !empty($config['saml_idp_certificate'])){
		
		
		
		if (!PluginPhpsamlPhpsaml::isUserAuthenticated()) {
			PluginPhpsamlPhpsaml::ssoRequest();
		} else {
			if (strpos($_SERVER['REQUEST_URI'], 'logout.php')){
				PluginPhpsamlPhpsaml::sloRequest();
			}		
		}
	}
}
