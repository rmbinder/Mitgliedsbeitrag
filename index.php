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
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Exception;
use Plugins\MembershipFee\classes\Config\ConfigTable;

// Fehlermeldungen anzeigen
error_reporting(E_ALL);

try {
    require_once (__DIR__ . '/../../system/common.php');
    require_once (__DIR__ . '/../../system/login_valid.php');
    require_once (__DIR__ . '/system/common_function.php');

    // script_name ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/mitgliedsbeitrag...
    $_SESSION['pMembershipFee']['script_name'] = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

    // only authorized user are allowed to start this module
    if (! isUserAuthorized($_SESSION['pMembershipFee']['script_name'])) {
        $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
    }

    $gNavigation->addStartUrl(CURRENT_URL);

    $pPreferences = new ConfigTable();
    $checked = $pPreferences->checkforupdate();

    if ($checked === 1) {
        // Nur Update der Konfigurationstabelle (Konfigurationdaten sind vorhanden, der Stand ist aber unterschiedlich zur Version.php)
        $pPreferences->init();
    } elseif ($checked === 2) {
        // Detaillierte Installationsroutine durchlaufen (mind. ein Profilfeld fehlt)
        $urlInst = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/install_db.php';
        $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_DETAILED_DESC', array(
            '<a href="' . $urlInst . '">' . $urlInst . '</a>'
        )), $gL10n->get('PLG_MITGLIEDSBEITRAG_ATTENTION'));
    }

    $pPreferences->read();
    // prüfen, ob role_id und/ item_id gespeichert sind (Wichtig für eine Deinstallation; evtl. ist eine vorher durchgeführte Deinstallation fehlgeschlagen)
    if ($pPreferences->config['install']['access_role_id'] == 0 || $pPreferences->config['install']['menu_item_id'] == 0) {
        $urlInst = ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/install.php';
        $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_UPDATE_REQUIRED', array(
            '<a href="' . $urlInst . '">' . $urlInst . '</a>'
        )));
    }

    admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membership_fee.php');
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

