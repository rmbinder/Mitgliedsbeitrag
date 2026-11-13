<?php
/**
 ***********************************************************************************************
 * Membership fee overview
 *
 * Plugin shows an overview of membership fees
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 * Usage:
 * 
 * To display an overview, the following lines must be inserted into "themes/simple/templates/system/overview.tpl":
 * 
 * <div class="admidio-overview-plugin col-sm-6 col-lg-4 col-xl-3" id="admidio-plugin-membership_fee">
 *    <div class="card admidio-card">
 *        <div class="card-body">
 *            {load_admidio_plugin plugin="MembershipFee" file="system/membership_fee_overview.php"}
 *        </div>
 *    </div>
 * </div>
 * 
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Plugins\Overview;
use Admidio\Users\Entity\User;

try {
    $rootPath = dirname(__DIR__, 2);

    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    $membershipfeePlugin = new Overview(PLUGIN_FOLDER);

    if ($gValidLogin) {
        $membershipfeePlugin->assignTemplateVariable('valid_login', $gValidLogin);
        $user = new User($gDb, $gProfileFields, $gCurrentUserId);

        if (empty($user->getValue('FEE' . $gCurrentOrgId))) {
            $membershipfeePlugin->assignTemplateVariable('overview_no_data', $gL10n->get('PLG_MEMBERSHIPFEE_OVERVIEW_NO_DATA', array(
                $user->getValue('FIRST_NAME'),
                $user->getValue('LAST_NAME')
            )));
        } else {
            $membershipfeePlugin->assignTemplateVariable('overview_fee', $user->getValue('FEE' . $gCurrentOrgId) . ' ' . $gSettingsManager->getString('system_currency'));

            if (! empty($user->getValue('CONTRIBUTORY_TEXT' . $gCurrentOrgId))) {
                $membershipfeePlugin->assignTemplateVariable('overview_contributory_text', $user->getValue('CONTRIBUTORY_TEXT' . $gCurrentOrgId));
            }
            if (! empty($user->getValue('PAID' . $gCurrentOrgId))) {
                $membershipfeePlugin->assignTemplateVariable('overview_paid', $user->getValue('PAID' . $gCurrentOrgId));
            }
            if (! empty($user->getValue('DUEDATE' . $gCurrentOrgId))) {
                $membershipfeePlugin->assignTemplateVariable('overview_duedate', $user->getValue('DUEDATE' . $gCurrentOrgId));
            }
        }
    }
    if (isset($page)) {
        echo $membershipfeePlugin->html($rootPath . PLUGIN_FOLDER . '/templates/overview.plugin.membershipfee.tpl');
    } else {
        $membershipfeePlugin->showHtmlPage($rootPath . PLUGIN_FOLDER . '/templates/overview.plugin.membershipfee.tpl');
    }
} catch (Throwable $e) {
    echo $e->getMessage();
}
