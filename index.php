<?php
/**
 ***********************************************************************************************
 * Mitgliedsbeitrag / Membership fee
 *
 * Version 5.3.1
 *
 * This plugin calculates membership fees based on role assignments.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 5
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

// Fehlermeldungen anzeigen
// error_reporting(E_ALL);
use Admidio\Infrastructure\Entity\Text;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Exception;

try {
    require_once (__DIR__ . '/../../system/common.php');
    require_once (__DIR__ . '/../../system/login_valid.php');
    require_once (__DIR__ . '/system/common_function.php');
    require_once (__DIR__ . '/classes/configtable.php');

    // script_name ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/mitgliedsbeitrag...
    $_SESSION['pMembershipFee']['script_name'] = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

    // only authorized user are allowed to start this module
    if (! isUserAuthorized($_SESSION['pMembershipFee']['script_name'])) {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }

    $pPreferences = new ConfigTablePMB();
    $checked = $pPreferences->checkforupdate();

    if ($checked == 1) // Update (Konfigurationdaten sind vorhanden, der Stand ist aber unterschiedlich zur Version.php)
    {
        $pPreferences->init();
    } elseif ($checked == 2) // Installationsroutine durchlaufen
    {
        admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/' . 'installation.php');
    }

    $gNavigation->addStartUrl(CURRENT_URL);
    admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membership_fee.php');
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

