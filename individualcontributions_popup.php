<?php
/**
 * Zeigt im Menue Einstellungen ein Popup-Fenster mit Hinweisen an
 *
 * @copyright 2004-2021 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:	keine
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

// only authorized user are allowed to start this module
if (!$gCurrentUser->isAdministrator())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS');

header('Content-type: text/html; charset=utf-8');

echo '
<div class="modal-header">
    <h4 class="modal-title">'.$headline.'</h4>
</div>
<div class="modal-body">
	<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DESCRIPTION').'</strong><br/>
    '.$gL10n->get('PLG_MITGLIEDSBEITRAG_DESCRIPTION_DESC').'<br/><br/>
    <strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SHORT_DESCRIPTION').'</strong><br/>
	'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SHORT_DESCRIPTION_DESC').'<br/><br/>		
    <strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE').'</strong><br/>
	'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_DESC').'<br/><br/>
    <strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AMOUNT').'</strong><br/>
	'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AMOUNT_DESC').'<br/><br/>	
    <strong>'.$gL10n->get('MEM_PROFILE_FIELD').'</strong><br/>
	'.$gL10n->get('PLG_MITGLIEDSBEITRAG_PROFILE_FIELD_DESC').'
</div>';
