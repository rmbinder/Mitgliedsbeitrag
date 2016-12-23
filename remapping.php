<?php
/**
 ***********************************************************************************************
 * Neuzuordnung von Mitgliedern fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:       keine
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/classes/tablemembers.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
    $gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

//Vor der Neuzuordnung die altersgestaffelten Rollen auf Luecken oder Ueberlappungen pruefen
$arr = check_rols();
if (!in_array($gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_OK'), $arr))
{
    $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_ERROR2'));
}
unset($arr);

$stack = array();
$message = '';
$tablemember = new TableMembers($gDb);
$sql = '';

$message .= '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO3').'</strong><BR>';

// alle Altersrollen einlesen
$altersrollen = beitragsrollen_einlesen('alt', array('FIRST_NAME', 'LAST_NAME', 'BIRTHDAY'));

// alle Altersrollen durchlaufen
foreach ($altersrollen as $roleId => $roldata)
{
    foreach($altersrollen[$roleId]['members'] as $member => $memberdata)
    {
        if(strlen($memberdata['BIRTHDAY']) === 0)
        {
            $gMessage->show('<strong>'.$gL10n->get('SYS_ERROR').':</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO1').' '.$memberdata['FIRST_NAME'].' '.$memberdata['LAST_NAME'].' '.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO2'));
        }

        $age = ageCalculator(strtotime($memberdata['BIRTHDAY']), strtotime($pPreferences->config['Altersrollen']['altersrollen_stichtag']));

        // ist das Alter des Mitglieds außerhalb des Altersschemas der Rolle
        if (($age < $roldata['von']) || ($age > $roldata['bis']))
        {
            // wenn ja, dann Mitglied auf den Stack legen und Rollenmitgliedschaft loeschen
            $stack[] = array('last_name' => $memberdata['LAST_NAME'], 'first_name' => $memberdata['FIRST_NAME'], 'user_id'=> $member, 'alter' => $age, 'alterstyp' => $roldata['alterstyp']);

            $sql = 'UPDATE '.TBL_MEMBERS.'
                    SET mem_end = \''.date('Y-m-d', strtotime('-1 day')).'\'
                    WHERE mem_usr_id = '.$member.'
                    AND mem_rol_id = '.$roleId;
            $gDb->query($sql);

            // stopMembership() kann nicht verwendet werden, da es unter best. Umstaenden Mitgliedschaften nicht loescht
            // Beschreibung von stopMembership()
            //      only stop membership if there is an actual membership
            //      the actual date must be after the beginning
            //      and the actual date must be before the end date
            //$tablemember->stopMembership( $roleId, $member);

            $message .= '<BR>'.$memberdata['LAST_NAME'].' '.$memberdata['FIRST_NAME'].' '.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO4').' '.$roldata['rolle'];
        }
    }
}

if (count($stack) === 0)
{
    $message .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO5');
}

// wenn ein Mitglied Angehoeriger mehrerer Rollen war (duerfte eigentlich gar nicht vorkommen),
// dann wurde er auch mehrfach in das Array $stack aufgenommen
// --> doppelte Vorkommen loeschen
$stack = array_map('unserialize', array_unique(array_map('serialize', $stack)));

$message .= '<BR><BR><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO6').'</strong><BR>';

// den Stack abarbeiten
$marker = false;
foreach ($stack as $key => $stackdata)
{
    // alle Altersrollen durchlaufen und pruefen, ob das Mitglied in das Altersschema der Rolle passt
    foreach ($altersrollen as $roleId => $roldata)
    {
        if (($stackdata['alter'] <= $roldata['bis'])
        && ($stackdata['alter'] >= $roldata['von'])
        && ($stackdata['alterstyp']==$roldata['alterstyp'])
        && !array_key_exists($stackdata['user_id'], $roldata['members']))
        {
            // das Mitglied passt in das Altersschema der Rolle und das Kennzeichen dieser Altersstaffelung passt auch
            $tablemember->startMembership($roleId, $stackdata['user_id']);
            $message .= '<BR>'.$stackdata['last_name'].' '.$stackdata['first_name'].' '.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO4').' '.$roldata['rolle'];

            unset($stack[$key]);
            $marker = true;
        }
    }
}

if (!$marker)
{
    $message .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO7');
}

if (count($stack)>0)
{
    $message .= '<BR><BR><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO8').'</strong><BR><small>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO9').'</small><BR>';
    foreach ($stack as $stackdata)
    {
        $message .= '<BR>'.$stackdata['last_name'].' '.$stackdata['first_name'].' '.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_INFO10').' '.$gL10n->get('PLG_MITGLIEDSBEITRAG_STAGGERING').' '.$stackdata['alterstyp'];
    }
}

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_AGE_STAGGERED_ROLES');

// create html page object
$page = new HtmlPage($headline);

$form = new HtmlForm('remapping_form', null, $page);
$form->addDescription($message);
$form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => THEME_URL .'/icons/forward.png', 'link' => 'menue.php?show_option=remapping', 'class' => 'btn-primary'));

$page->addHtml($form->show(false));
$page->show();
