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
 * To display an overview, the following lines must be inserted into "adm_themes/simple/templates/overview.tpl":
 * 
 * <div class="admidio-overview-plugin col-sm-6 col-lg-4 col-xl-3" id="admidio-plugin-membership_fee">
 *    <div class="card admidio-card">
 *        <div class="card-body">
 *            {load_admidio_plugin plugin="membership_fee" file="membership_fee_overview.php"}
 *        </div>
 *    </div>
 * </div>
 * 
 * Attention: If the plugin folder is not "membership_fee", the code plugin="..." must be modified accordingly.
 * 
 ***********************************************************************************************
 */
use Admidio\Users\Entity\User;

$rootPath = dirname(__DIR__, 2);
$pluginFolder = basename(__DIR__);

require_once($rootPath . '/adm_program/system/common.php');

echo '<div id="plugin_'. $pluginFolder. '" class="admidio-plugin-content">';

echo '<h3>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE').'</h3>';

if ($gValidLogin) 
{
    $user = new User($gDb, $gProfileFields, $gCurrentUserId);
    
    if (empty($user->getValue('FEE'.$gCurrentOrgId)))
    {
        echo $gL10n->get('PLG_MITGLIEDSBEITRAG_OVERVIEW_NO_DATA', array($user->getValue('FIRST_NAME'), $user->getValue('LAST_NAME')));
    }
    else 
    {
        // create a static form
        $form = new HtmlForm('plugin-membership_fee-static-form', '#', null, array('type' => 'vertical', 'setFocus' => false));
        
        $form->addStaticControl('plg_membership_fee_overview_fee', $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'), $user->getValue('FEE'.$gCurrentOrgId).' '.$gSettingsManager->getString('system_currency'));
        
        if (!empty($user->getValue('CONTRIBUTORY_TEXT'.$gCurrentOrgId)))
        {
            $form->addStaticControl('plg_membership_fee_overview_contributory_text', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT'), $user->getValue('CONTRIBUTORY_TEXT'.$gCurrentOrgId));
        }
        if (!empty($user->getValue('PAID'.$gCurrentOrgId)))
        {
            $form->addStaticControl('plg_membership_fee_overview_paid', $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID_ON'), $user->getValue('PAID'.$gCurrentOrgId));
        }
        if (!empty($user->getValue('DUEDATE'.$gCurrentOrgId)))
        {
            $form->addStaticControl('plg_membership_fee_overview_duedate', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'), $user->getValue('DUEDATE'.$gCurrentOrgId));
        }
        echo $form->show();
    }
}
else
{
    echo $gL10n->get('PLG_MITGLIEDSBEITRAG_OVERVIEW_NOVALIDLOGIN');
}

echo '</div>';
