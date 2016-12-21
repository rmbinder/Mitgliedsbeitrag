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
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
    $gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$members = array();
$message = '';

//pruefen, ob doppelte Mitgliedsnummern bestehen
$nummer = erzeuge_mitgliedsnummer();

// alle Mitglieder abfragen
$sql = ' SELECT mem_usr_id
         FROM '.TBL_MEMBERS.' ';
$statement = $gDb->query($sql);

while($row = $statement->fetch())
{
    $members[$row['mem_usr_id']] = array();
}

// die IDs der Attribute aus der Datenbank herausssuchen
$attributes = array('SYS_LASTNAME' => 0, 'SYS_FIRSTNAME' => 0, 'PMB_MEMBERNUMBER' => 0);
foreach($attributes as $attribute => $dummy)
{
    $sql = ' SELECT usf_id
             FROM '.TBL_USER_FIELDS.'
             WHERE usf_name = \''.$attribute.'\' ';
    $statement = $gDb->query($sql);
    $row = $statement->fetch();
    $attributes[$attribute] = $row['usf_id'];
}

// Die Daten jedes Mitglieds abfragen und in das Array schreiben
foreach ($members as $member => $key)
{
    foreach ($attributes as $attribute => $usf_id)
    {
        $sql = 'SELECT usd_value
                FROM '.TBL_USER_DATA.'
                WHERE usd_usr_id = \''.$member.'\'
                AND usd_usf_id = \''.$usf_id.'\' ';
        $statement = $gDb->query($sql);
        $row = $statement->fetch();
        $members[$member][$attribute] = $row['usd_value'];
    }
}

//alle Mitglieder durchlaufen und pruefen, ob eine Mitgliedsnummer existiert
 foreach ($members as $member => $key)
{
    if (($members[$member]['PMB_MEMBERNUMBER'] == '') || ($members[$member]['PMB_MEMBERNUMBER'] < 1))
    {
        $nummer = erzeuge_mitgliedsnummer();

        $user = new User($gDb, $gProfileFields, $member);
        $user->setValue('MEMBERNUMBER', $nummer);
        $user->save();

        $message .= $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_RES1', $members[$member]['SYS_FIRSTNAME'], $members[$member]['SYS_LASTNAME'], $nummer);
    }
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
