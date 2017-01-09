<?php
/**
 ***********************************************************************************************
 * Anzeigen einer Historie von Beitragszahlungen fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * history.php is a modified profile_field_history.php
 *
 * Parameters:
 *
 * filter_date_from : is set to actual date,
 *             if no date information is delivered
 * filter_date_to   : is set to 31.12.9999,
 *             if no date information is delivered
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// calculate default date from which the contribution history should be shown
$filterDateFrom = DateTime::createFromFormat('Y-m-d', DATE_NOW);
$filterDateFrom->modify('-'.$gPreferences['members_days_field_history'].' day');

// Initialize and check the parameters
$getDateFrom = admFuncVariableIsValid($_GET, 'filter_date_from', 'date', array('defaultValue' => $filterDateFrom->format($gPreferences['system_date'])));
$getDateTo   = admFuncVariableIsValid($_GET, 'filter_date_to',   'date', array('defaultValue' => DATE_NOW));

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HISTORY');

// Initialize local parameteres
$sqlConditions = '';

// add page to navigation history
$gNavigation->addUrl(CURRENT_URL, $headline);

// filter_date_from and filter_date_to can have different formats
// now we try to get a default format for intern use and html output
$objDateFrom = DateTime::createFromFormat('Y-m-d', $getDateFrom);
if($objDateFrom === false)
{
    // check if date has system format
    $objDateFrom = DateTime::createFromFormat($gPreferences['system_date'], $getDateFrom);
    if($objDateFrom === false)
    {
        $objDateFrom = DateTime::createFromFormat($gPreferences['system_date'], '1970-01-01');
    }
}

$objDateTo = DateTime::createFromFormat('Y-m-d', $getDateTo);
if($objDateTo === false)
{
    // check if date has system format
    $objDateTo = DateTime::createFromFormat($gPreferences['system_date'], $getDateTo);
    if($objDateTo === false)
    {
        $objDateTo = DateTime::createFromFormat($gPreferences['system_date'], '1970-01-01');
    }
}

// DateTo should be greater than DateFrom
if($objDateFrom > $objDateTo)
{
    $gMessage->show($gL10n->get('SYS_DATE_END_BEFORE_BEGIN'));
    // => EXIT
}

$dateFromIntern = $objDateFrom->format('Y-m-d');
$dateFromHtml   = $objDateFrom->format($gPreferences['system_date']);
$dateToIntern   = $objDateTo->format('Y-m-d');
$dateToHtml     = $objDateTo->format($gPreferences['system_date']);

// create select statement with all necessary data
$sql = 'SELECT usl_id, usl_usr_id, last_name.usd_value AS last_name, first_name.usd_value AS first_name, usl_usf_id, usl_value_new, usl_timestamp_create
          FROM '.TBL_USER_LOG.'
    INNER JOIN '.TBL_USER_DATA.' AS last_name
            ON last_name.usd_usr_id = usl_usr_id
           AND last_name.usd_usf_id = '. $gProfileFields->getProperty('LAST_NAME', 'usf_id').'
    INNER JOIN '.TBL_USER_DATA.' AS first_name
            ON first_name.usd_usr_id = usl_usr_id
           AND first_name.usd_usf_id = '. $gProfileFields->getProperty('FIRST_NAME', 'usf_id').'
    ORDER BY usl_id ASC';      

$fieldHistoryStatement = $gDb->query($sql);

if($fieldHistoryStatement->rowCount() === 0)
{
    // message is shown, so delete this page from navigation stack
    $gNavigation->deleteLastUrl();

    $gMessage->show($gL10n->get('MEM_NO_CHANGES'));
    // => EXIT
}

// create html page object
$page = new HtmlPage($headline);

// add back link to module menu
$profileFieldHistoryMenu = $page->getMenu();
$profileFieldHistoryMenu->addItem('menu_item_back', $gNavigation->getPreviousUrl(), $gL10n->get('SYS_BACK'), 'back.png');

// create filter menu with input elements for Startdate and Enddate
$FilterNavbar = new HtmlNavbar('menu_history_filter', null, null, 'filter');
$form = new HtmlForm('navbar_filter_form', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/history.php', $page, array('type' => 'navbar', 'setFocus' => false));
$form->addInput('filter_date_from', $gL10n->get('SYS_START'), $dateFromHtml, array('type' => 'date', 'maxLength' => 10));
$form->addInput('filter_date_to', $gL10n->get('SYS_END'), $dateToHtml, array('type' => 'date', 'maxLength' => 10));
$form->addSubmitButton('btn_send', $gL10n->get('SYS_OK'));
$FilterNavbar->addForm($form->show(false));
$page->addHtml($FilterNavbar->show());

$table = new HtmlTable('history_table', $page, true, true);

$columnHeading = array();

$table->setDatatablesOrderColumns(array(array(5, 'desc')));
$columnHeading[] = $gL10n->get('SYS_NAME');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID_ON');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE');
$columnHeading[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT');

$table->addRowHeadingByArray($columnHeading);

$columnValues    = array();

$presetColumnValues = array();
$presetColumnValues[$gProfileFields->getProperty('PAID'.$gCurrentOrganization->getValue('org_id'), 'usf_id')] =  '&nbsp;';
$presetColumnValues[$gProfileFields->getProperty('DUEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id')] =  '&nbsp;';
$presetColumnValues[$gProfileFields->getProperty('FEE'.$gCurrentOrganization->getValue('org_id'), 'usf_id')] =  '&nbsp;';
$presetColumnValues[$gProfileFields->getProperty('CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), 'usf_id')] =  '&nbsp;';

while($row = $fieldHistoryStatement->fetch())
{    
 	if(!array_key_exists( $row['usl_usf_id'],$presetColumnValues))
 	{
 		continue;
 	}
 	
 	if(!isset($columnValues[$row['usl_usr_id']]))
 	{
 		$columnValues[$row['usl_usr_id']] = $presetColumnValues;
 	}
   
   	$columnValues[$row['usl_usr_id']][$row['usl_usf_id']] = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById((int) $row['usl_usf_id'], 'usf_name_intern'), $row['usl_value_new']);    
   	
   	if( $row['usl_usf_id'] == $gProfileFields->getProperty('PAID'.$gCurrentOrganization->getValue('org_id'), 'usf_id') && $row['usl_value_new'] != '' && $row['usl_value_new'] >= $dateFromIntern && $row['usl_value_new'] <= $dateToIntern)
   	{
   		$columnValues[$row['usl_usr_id']] = array_merge(array('Name' => '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$row['usl_usr_id'].'">'.$row['last_name'].', '.$row['first_name'].'</a>'),$columnValues[$row['usl_usr_id']]);
   		$table->addRowByArray($columnValues[$row['usl_usr_id']]);
   		unset($columnValues[$row['usl_usr_id']]);
   	}
}

$page->addHtml($table->show());
$page->show();
