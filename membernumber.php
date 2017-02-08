<?php
/**
 ***********************************************************************************************
 * Dieses Plugin generiert fuer jedes aktive und ehemalige Mitglied eine Mitgliedsnummer.
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   Die erzeugten Mitgliedsnummern sind numerisch.
 *            Begonnen wird bei der Zahl 1.
 *            Freie Nummern von geloeschten Mitgliedern werden wiederverwendet.
 *
 * Parameters:          keine
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
    $gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$members = array();
$message = '';

//pruefen, ob doppelte Mitgliedsnummern bestehen
create_membernumber();

// Name, Vorname von Mitgliedern einlesen, die 1. keine Mitgliedsnummer besitzen oder 2. eine Mitgliedsnummer < 1 besitzen
$sql = 'SELECT usr_id, last_name.usd_value AS last_name, first_name.usd_value AS first_name, membernumber.usd_value AS membernumber
          FROM '.TBL_USERS.'
     LEFT JOIN '.TBL_USER_DATA.' AS last_name
            ON last_name.usd_usr_id = usr_id
           AND last_name.usd_usf_id = '. $gProfileFields->getProperty('LAST_NAME', 'usf_id'). '
     LEFT JOIN '.TBL_USER_DATA.' AS first_name
            ON first_name.usd_usr_id = usr_id
           AND first_name.usd_usf_id = '. $gProfileFields->getProperty('FIRST_NAME', 'usf_id'). '
     LEFT JOIN '.TBL_USER_DATA.' AS membernumber
            ON membernumber.usd_usr_id = usr_id
           AND membernumber.usd_usf_id = '. $gProfileFields->getProperty('MEMBERNUMBER'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '         		
         WHERE usr_valid = 1 
           AND (membernumber.usd_value < 1
           	OR membernumber.usd_value IS NULL)
           AND EXISTS (SELECT 1
          FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES.  ','. TBL_USER_DATA. '
         WHERE mem_usr_id = usr_id
           AND mem_rol_id = rol_id
           AND mem_begin <= \''.DATE_NOW.'\'
           AND mem_end    > \''.DATE_NOW.'\'
           AND rol_valid  = 1
           AND rol_cat_id = cat_id
           AND cat_org_id = '. $gCurrentOrganization->getValue('org_id'). ') ';
           
$statement = $gDb->query($sql);
while ($row = $statement->fetch())
{
	$members[$row['usr_id']] = array('last_name'    => $row['last_name'], 
									 'first_name'   => $row['first_name'],
									 'membernumber' => $row['membernumber']);
}

$user = new User($gDb, $gProfileFields);
foreach ($members as $usrID => $data)
{
	$newMembernumber = create_membernumber();

    $user->readDataById($usrID);
    $user->setValue('MEMBERNUMBER'.$gCurrentOrganization->getValue('org_id'), $newMembernumber);
    $user->save();

    $message .= $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_RES1', $data['first_name'], $data['last_name'], $newMembernumber);
}

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_PRODUCE_MEMBERNUMBER');

// create html page object
$page = new HtmlPage($headline);

$form = new HtmlForm('membernumber_form', null, $page);

// Message ausgeben (wenn keinem Mitglied eine Mitgliedsnummer zugewiesen wurde, dann ist die Variable leer)
if ($message == '')
{
    $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_RES2'));
}
else
{
    $form->addDescription($message);
}

$form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => THEME_URL .'/icons/forward.png', 'link' => 'menue.php?show_option=producemembernumber', 'class' => 'btn-primary'));

$page->addHtml($form->show(false));
$page->show();

// Functions used only in this script
/**
 * Function creates a membernumber
 * @return  int     membernumer
 */
function create_membernumber()
{
	global $gDb, $gMessage, $gL10n, $gProfileFields, $gCurrentOrganization;

	$rueckgabe_mitgliedsnummer = 0;
	$mitgliedsnummern = array('0');
	 
	$sql = 'SELECT usd_value
              FROM '. TBL_USER_DATA .'
             WHERE usd_usf_id = \''.$gProfileFields->getProperty('MEMBERNUMBER'.$gCurrentOrganization->getValue('org_id'), 'usf_id').'\'
       		   AND EXISTS (SELECT 1
              FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES.  ','. TBL_USER_DATA. '
             WHERE mem_usr_id = usd_usr_id
               AND mem_rol_id = rol_id
               AND mem_begin <= \''.DATE_NOW.'\'
               AND mem_end    > \''.DATE_NOW.'\'
               AND rol_valid  = 1
               AND rol_cat_id = cat_id
               AND cat_org_id = '. $gCurrentOrganization->getValue('org_id'). ') ';
	
	$statement = $gDb->query($sql);
	while ($row = $statement->fetch())
	{
		$mitgliedsnummern[] = $row['usd_value'];
	}

	sort($mitgliedsnummern);

	//Ueberpruefung auf doppelte Mitgliedsnummern
	for ($i = 0; $i < count($mitgliedsnummern)-1; $i++)
	{
		if ($mitgliedsnummern[$i] === $mitgliedsnummern[$i+1])
		{
			$gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_ERROR', $mitgliedsnummern[$i]));
			// --> EXIT
		}
	}

	$hoechste_mitgliedsnummer = end($mitgliedsnummern);

	$i = 1;
	while ($i < $hoechste_mitgliedsnummer)
	{
		if (!in_array($i, $mitgliedsnummern))
		{
			$rueckgabe_mitgliedsnummer = $i;
			break;
		}
		$i++;
	}
	return ($rueckgabe_mitgliedsnummer == 0) ? $hoechste_mitgliedsnummer+1 : $rueckgabe_mitgliedsnummer;
}

