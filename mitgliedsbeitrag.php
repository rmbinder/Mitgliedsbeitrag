<?php
/**
 ***********************************************************************************************
 * Mitgliedsbeitrag
 *
 * Version 4.2.0
 *
 * Dieses Plugin berechnet Mitgliedsbeitraege anhand von Rollenzugehoerigkeiten.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 3.2
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

//Fehlermeldungen anzeigen
//error_reporting(E_ALL);

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');  	

$pPreferences = new ConfigTablePMB();

// eine Deinstallation hat stattgefunden, deshalb keine Installationsroutine durchlaufen und auch keinen Link anzeigen
if(!isset($_SESSION['pmbDeinst']))
{
	$checked = $pPreferences->checkforupdate();
	$startprog='menue.php';

	if ($checked==1 )   		//Update (Konfigurationdaten sind vorhanden, der Stand ist aber unterschiedlich zur Version.php)
	{
		$pPreferences->init();	
	}
	elseif ($checked==2)		//Installationsroutine durchlaufen
	{
		$startprog='installation.php';
		$pPreferences->init();
	}

	$pPreferences->read();			// (checked ==0) : nur Einlesen der Konfigurationsdaten
	
	// Zeige Link zum Plugin
	if(check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']) )
	{
		if (isset($pluginMenu))
		{
			// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, 
			// dann innerhalb dieses Menüs anzeigen
			$pluginMenu->addItem('membershipfee_show', '/adm_plugins/'.$plugin_folder.'/'.$startprog,
				$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
			$moduleMenu->addItem('membershipfee_show', '/adm_plugins/'.$plugin_folder.'/'.$startprog,
				$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'), '/icons/lists.png');
		}
	}
}	
