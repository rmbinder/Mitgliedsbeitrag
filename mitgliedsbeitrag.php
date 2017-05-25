<?php
/**
 ***********************************************************************************************
 * Mitgliedsbeitrag
 *
 * Version 4.2.1
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

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$plugin_folder = '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1);

// Einbinden der Sprachdatei
$gL10n->addLanguagePath(ADMIDIO_PATH . FOLDER_PLUGINS . $plugin_folder . '/languages');

$pPreferences = new ConfigTablePMB();

// eine Deinstallation hat stattgefunden, deshalb keine Installationsroutine durchlaufen und auch keinen Link anzeigen
// Zweite Voraussetzung: Ein User muss erfolgreich eingeloggt sein
if(!isset($_SESSION['pmbDeinst']) && $gValidLogin)
{
    $checked = $pPreferences->checkforupdate();
    $startprog = 'menue.php';

    if ($checked == 1)        //Update (Konfigurationdaten sind vorhanden, der Stand ist aber unterschiedlich zur Version.php)
    {
        $pPreferences->init();
    }
    elseif ($checked == 2)        //Installationsroutine durchlaufen
    {
        $startprog = 'installation.php';
        $pPreferences->init();
    }

    $pPreferences->read();            // (checked == 0) : nur Einlesen der Konfigurationsdaten

    // Zeige Link zum Plugin
    if(check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
    {
        if (isset($pluginMenu))
        {
            // wenn in der my_body_bottom.php ein $pluginMenu definiert wurde,
            // dann innerhalb dieses Menues anzeigen
            $pluginMenu->addItem('membershipfee_show', FOLDER_PLUGINS . $plugin_folder .'/'.$startprog,
                $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'), '/icons/lists.png');
        }
        else
        {
            // wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
            $moduleMenu->addItem('membershipfee_show', FOLDER_PLUGINS . $plugin_folder .'/'.$startprog,
                $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'), '/icons/lists.png');
        }
    }
}
