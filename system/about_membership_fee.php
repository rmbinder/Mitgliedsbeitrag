<?php
/**
 ***********************************************************************************************
 * Erzeugt ein Modal-Fenster mit Plugininformationen
 *
 * @copyright The Admidio Team
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/**
 * ****************************************************************************
 * Parameters: none
 * ***************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorized()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_ABOUT') . ' ' . $gL10n->get('PLG_MEMBERSHIPFEE_NAME');

    $infoText = '
        <div class="row">
            <div ><strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE') . '</strong> (MembershipFee)</div>
        </div>
        <div class="row">
            <div >&nbsp;</div>
        </div>
        <div class="row">
            <div >' . $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE_DESC') . '</div>
        </div>
        <div class="row">
            <div >&nbsp;</div>
        </div>
        <div class="row">
            <div class="col-4"><strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_PLUGIN_VERSION') . ':</strong></div>
            <div class="col-8">' . $pPreferences->config['Plugininformationen']['version'] . ' (' . $pPreferences->config['Plugininformationen']['stand'] . ')</div>
        </div>
    ';

    $gMessage->showInModalWindow();
    $gMessage->show($infoText, $headline);
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

