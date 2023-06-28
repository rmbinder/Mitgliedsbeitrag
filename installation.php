<?php
/**
 ***********************************************************************************************
 * Installationsroutine fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode         : start     - Startbildschirm anzeigen
 *                anlegen   - Fehlende Kategorien und Profilfelder anlegen
 *                soll_ist  - Anzeige des Soll/Ist-Vergleiches
 ***********************************************************************************************
 */
use Ramsey\Uuid\Uuid;

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!$gCurrentUser->isAdministrator())
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$gNavigation->addStartUrl(CURRENT_URL);

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'start', 'validValues' => array('start', 'anlegen', 'soll_ist')));

if($getMode == 'anlegen')
{
    $arr = check_DB();

    // pruefen, ob es die Kategorie Mitgliedschaft gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_CATEGORIES']['Mitgliedschaft']['cat_id']))
    {
        $nextCatSequence = getNextCatSequence('USF');
        setCategory($arr['SOLL']['TBL_CATEGORIES']['Mitgliedschaft'], $nextCatSequence);    
    }

    $cat_id_mitgliedschaft = getCat_IDPMB('MEMBERSHIP'.$gCurrentOrgId);
    $nextFieldSequence = getNextFieldSequence($cat_id_mitgliedschaft);
    
    // pruefen, ob es das Profilfeld Mitgliedsnummer gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_USER_FIELDS']['Mitgliedsnummer']['usf_name']))
    {
        setUserField($cat_id_mitgliedschaft, $arr['SOLL']['TBL_USER_FIELDS']['Mitgliedsnummer'], $nextFieldSequence);    
        $nextFieldSequence++;
    }
    
    // pruefen, ob es das Profilfeld Beitritt gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_USER_FIELDS']['Beitritt']['usf_name']))
    {
        setUserField($cat_id_mitgliedschaft, $arr['SOLL']['TBL_USER_FIELDS']['Beitritt'], $nextFieldSequence);    
    }

    // pruefen, ob es die Kategorie Mitgliedsbeitrag gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_CATEGORIES']['Mitgliedsbeitrag']['cat_id']))
    {
        $nextCatSequence = getNextCatSequence('USF');
        setCategory($arr['SOLL']['TBL_CATEGORIES']['Mitgliedsbeitrag'], $nextCatSequence);
    }

    $cat_id_mitgliedsbeitrag = getCat_IDPMB('MEMBERSHIP_FEE'.$gCurrentOrgId);
    $nextFieldSequence = getNextFieldSequence($cat_id_mitgliedsbeitrag);

    // pruefen, ob es das Profilfeld Bezahlt gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_USER_FIELDS']['Bezahlt']['usf_name']))
    {
        setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Bezahlt'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Beitrag gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_USER_FIELDS']['Beitrag']['usf_name']))
    {
        setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Beitrag'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Beitragstext gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Beitragstext']['usf_name']))
    {
        setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Beitragstext'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Sequenztyp gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Sequenztyp']['usf_name']))
    {
        setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Sequenztyp'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Faelligkeitsdatum gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Faelligkeitsdatum']['usf_name']))
    {
        setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Faelligkeitsdatum'], $nextFieldSequence);
    }

    // pruefen, ob es die Kategorie Mandat gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_CATEGORIES']['Mandat']['cat_id']))
    {
        $nextCatSequence = getNextCatSequence('USF');
        setCategory($arr['SOLL']['TBL_CATEGORIES']['Mandat'], $nextCatSequence);
    }

    $cat_id_mandat = getCat_IDPMB('MANDATE'.$gCurrentOrgId);
    $nextFieldSequence = getNextFieldSequence($cat_id_mandat);

    // pruefen, ob es das Profilfeld Mandatsreferenz gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Mandatsreferenz']['usf_name']))
    {
        setUserField($cat_id_mandat, $arr['SOLL']['TBL_USER_FIELDS']['Mandatsreferenz'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Mandatsdatum gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Mandatsdatum']['usf_name']))
    {
        setUserField($cat_id_mandat, $arr['SOLL']['TBL_USER_FIELDS']['Mandatsdatum'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Orig_Mandatsreferenz gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Orig_Mandatsreferenz']['usf_name']))
    {
        setUserField($cat_id_mandat, $arr['SOLL']['TBL_USER_FIELDS']['Orig_Mandatsreferenz'], $nextFieldSequence);
    }

    // pruefen, ob es die Kategorie Kontodaten gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_CATEGORIES']['Kontodaten']['cat_id']))
    {
        $nextCatSequence = getNextCatSequence('USF');
        setCategory($arr['SOLL']['TBL_CATEGORIES']['Kontodaten'], $nextCatSequence);
    }

    $cat_id_kontodaten = getCat_IDPMB('ACCOUNT_DATA');
    $nextFieldSequence = getNextFieldSequence($cat_id_kontodaten);

   // pruefen, ob es das Profilfeld IBAN gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_USER_FIELDS']['IBAN']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['IBAN'], $nextFieldSequence);
        $nextFieldSequence++;
    }
    // pruefen, ob es das Profilfeld BIC gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_USER_FIELDS']['BIC']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['BIC'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Bankname gibt, wenn nicht: anlegen
    if (!isset($arr['IST']['TBL_USER_FIELDS']['Bankname']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Bankname'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Kontoinhaber gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Kontoinhaber']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Kontoinhaber'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld KontoinhaberStrasse gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberStrasse']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberStrasse'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld KontoinhaberPLZ gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberPLZ']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberPLZ'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld KontoinhaberOrt gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberOrt']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberOrt'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld KontoinhaberEMail gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberEMail']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberEMail'], $nextFieldSequence);
        $nextFieldSequence++;
    }

    // pruefen, ob es das Profilfeld Orig_Debtor_Agent gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Orig_Debtor_Agent']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Orig_Debtor_Agent'], $nextFieldSequence);
        $nextFieldSequence++;
    }
    // pruefen, ob es das Profilfeld Orig_IBAN gibt, wenn nicht: anlegen
    if(!isset($arr['IST']['TBL_USER_FIELDS']['Orig_IBAN']['usf_name']))
    {
        setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Orig_IBAN'], $nextFieldSequence);
    }
    
    // Update/Konvertierungsroutine 4.1.2/4.2.0 -> 4.2.4 NEU
    // im ersten Schritt pruefen, ob das Profilfeld 'MEMBERNUMBER' noch vorhanden ist
    if ($gProfileFields->getProperty('MEMBERNUMBER', 'usf_id') > 0)
    {
    	//wenn ja, das alte, org-uebergreifende Profilfeld umbenennen in "Mitgliedsnummer-alt"
    	$userField = new TableUserField($gDb, $gProfileFields->getProperty('MEMBERNUMBER', 'usf_id'));
    	$userField->setValue('usf_name', 'Mitgliedsnummer-alt');
    	$userField->setValue('usf_name_intern', 'MEMBERNUMBER_OLD');
    	$userField->save();
    }
        
    // $gProfileFields aktualisieren
    $gProfileFields->readProfileFields($gCurrentOrgId);

    // im zweiten Schritt pruefen, ob ueberhaupt Mitgliedsnummern existieren
    $sql = 'SELECT COUNT(*) AS count
              FROM '.TBL_USER_DATA.'
             WHERE usd_usf_id = '. $gProfileFields->getProperty('MEMBERNUMBER_OLD', 'usf_id').' ';
    
    $membNumOldStatement = $gDb->query($sql);
    if ($membNumOldStatement->fetchColumn() > 0)       // ja, es gibt alte Mitgliedsnummern
    {
    	// im dritten Schritt pruefen, ob Mitgliedsnummern bereits uebertragen wurden
    	$sql = 'SELECT COUNT(*) AS count
                  FROM '.TBL_USER_DATA.'
                 WHERE usd_usf_id = '. $gProfileFields->getProperty('MEMBERNUMBER'.$gCurrentOrgId, 'usf_id').' ';
                       
    	$membNumNewStatement = $gDb->query($sql);
        if ($membNumNewStatement->fetchColumn() == 0)       // nein, es gibt noch keine neuen Mitgliedsnummern
        {
        	$user = new User($gDb, $gProfileFields);
        	
        	$sql = 'SELECT usd_usr_id, usd_value
        		      FROM '. TBL_USER_DATA. '
        		     WHERE usd_usf_id = '. $gProfileFields->getProperty('MEMBERNUMBER_OLD', 'usf_id').' ';
        	
        	$statement = $gDb->query($sql);
        	while ($row = $statement->fetch())
        	{
        		$user->readDataById($row['usd_usr_id']);
        		$user->setValue('MEMBERNUMBER'.$gCurrentOrgId, $row['usd_value']);
        		$user->save();
        	}
        }
    }            
    //Ende Update/Konvertierungsroutine 4.1.2/4.2.0 -> 4.2.4 NEU
}

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_TITLE');

$page = new HtmlPage('plg-mitgliedsbeitrag-installation', $headline);

$page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_DESCRIPTION'));
$page->addHtml('<br/><br/>');

if($getMode == 'start' || $getMode == 'anlegen')     //Default: start
{
    $arr = check_DB();

    $page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_FIRST_PASSAGE').':  ==> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_VERIFICATION_MISSING_FIELDS').'</strong>');
    $page->addHtml('<br/>');
    $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_SECOND_PASSAGE').': '.$gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_VERIFICATION_COMPARISON'));
    $page->addHtml('<br/><br/>');
    
    $datatable = false;
    $hoverRows = true;
    $classTable  = 'table table-condensed';
    $table = new HtmlTable('table_members_contribution', $page, $hoverRows, $datatable, $classTable);

    $leer = '&nbsp;';
    $strich = '- ';

    $columnValues = array();
    $columnValues[] = $gL10n->get('SYS_CATEGORY');
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_PROFILE_FIELD');
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_STATUS');
    $table->addRowHeadingByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP');
    $columnValues[] = $leer;
    $columnValues[] = !(isset($arr['IST']['TBL_CATEGORIES']['Mitgliedschaft']['cat_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);
    
    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Mitgliedsnummer']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESSION');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Beitritt']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE');
    $columnValues[] = $leer;
    $columnValues[] = !(isset($arr['IST']['TBL_CATEGORIES']['Mitgliedsbeitrag']['cat_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('SYS_CONTRIBUTION');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Beitrag']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_PAID');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Bezahlt']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Beitragstext']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Sequenztyp']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Faelligkeitsdatum']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE');
    $columnValues[] = $leer;
    $columnValues[] = !(isset($arr['IST']['TBL_CATEGORIES']['Mandat']['cat_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Mandatsreferenz']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Mandatsdatum']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Orig_Mandatsreferenz']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA');
    $columnValues[] = $leer;
    $columnValues[] = !(isset($arr['IST']['TBL_CATEGORIES']['Kontodaten']['cat_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['IBAN']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_BIC');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['BIC']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_BANK');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Bankname']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Kontoinhaber']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_STREET');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberStrasse']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_POSTCODE');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberPLZ']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_CITY');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberOrt']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberEMail']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Orig_Debtor_Agent']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN');
    $columnValues[] = !(isset($arr['IST']['TBL_USER_FIELDS']['Orig_IBAN']['usf_name'])) ? '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MISSING').'</strong>' : $gL10n->get('PLG_MITGLIEDSBEITRAG_AVAILABLE');
    $table->addRowByArray($columnValues);

    $page->addHtml($table->show(false));

    $form = new HtmlForm('installation_start_form', null, $page, array('setFocus' => false));
    
    
    if ((!isset($arr['IST']['TBL_USER_FIELDS']['Beitritt']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Bezahlt']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Mitgliedsnummer']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Beitrag']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Beitragstext']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Faelligkeitsdatum']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['IBAN']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['BIC']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Bankname']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Kontoinhaber']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Sequenztyp']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Mandatsreferenz']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberStrasse']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberPLZ']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberOrt']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberEMail']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Orig_Debtor_Agent']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Orig_IBAN']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Orig_Mandatsreferenz']['usf_name']))
        || (!isset($arr['IST']['TBL_USER_FIELDS']['Mandatsdatum']['usf_name'])))
    {
        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_FIELDS_SHOULD_BE_CREATED'));
        
        $form->openButtonGroup();
        $form->addButton('btnAnlegen', $gL10n->get('SYS_NEXT'), array('icon' => 'fa-arrow-circle-right', 'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/installation.php', array('mode' => 'anlegen')), 'class' => 'btn-primary'));
        $form->addDescription('&nbsp');
        $form->addButton('btnAbbrechen', $gL10n->get('SYS_ABORT'), array('icon' => 'fa-times', 'link' => $gHomepage, 'class' => 'btn-primary' ));
        $form->closeButtonGroup();
        
        $form->addDescription('<strong>'.$gL10n->get('SYS_NEXT').'</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MISSING_FIELDS'));
        $form->addDescription('<strong>'.$gL10n->get('SYS_ABORT').'</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_NO_CHANGES'));
    }
    else
    {
        $pPreferences = new ConfigTablePMB();
        $pPreferences->init();      //Konfigurationstabelle anlegen
        
        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ALL_FIELDS_ARE_AVAILABLE'));        
        $form->addButton('btnSollIst', $gL10n->get('SYS_NEXT'), array('icon' => 'fa-arrow-circle-right', 'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/installation.php', array('mode' => 'soll_ist')), 'class' => 'btn-primary'));
        $form->addDescription('<strong>'.$gL10n->get('SYS_NEXT').'</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CHANGE_NEXT_TEST'));
    }
    $page->addHtml($form->show(false));
}
elseif($getMode == 'soll_ist')
{
    $arr = check_DB();

    $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_FIRST_PASSAGE').': '.$gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_VERIFICATION_MISSING_FIELDS'));
    $page->addHtml('<br/>');
    $page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_SECOND_PASSAGE').':  ==> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_VERIFICATION_COMPARISON').'</strong>');
    $page->addHtml('<br/><br/>');
    
    $datatable = false;
    $hoverRows = true;
    $classTable  = 'table table-condensed';
    $table = new HtmlTable('table_members_contribution', $page, $hoverRows, $datatable, $classTable);

    $leer = '&nbsp;';
    $strich = '- ';

    $columnAttributes['style'] = 'text-align: center; vertical-align: middle';
    $columnAttributes['rowspan'] = 2;
    $table->addColumn($gL10n->get('SYS_CATEGORY'), $columnAttributes, 'th');
    $table->addColumn($gL10n->get('PLG_MITGLIEDSBEITRAG_PROFILE_FIELD'), $columnAttributes, 'th');

    $columnAttributes['rowspan'] = 1;
    $columnAttributes['colspan'] = 2;
    $table->addColumn($gL10n->get('SYS_INTERNAL_NAME'), $columnAttributes, 'th');
    $table->addColumn($gL10n->get('PLG_MITGLIEDSBEITRAG_DATA_TYPE'), $columnAttributes, 'th');
    $table->addColumn('<i class="fas fa-eye" data-toggle="tooltip" title="'.$gL10n->get('ORG_FIELD_NOT_HIDDEN').'"></i>/<i class="fas fa-eye admidio-opacity-reduced" data-toggle="tooltip" title="'.$gL10n->get('ORG_FIELD_HIDDEN').'"></i>', $columnAttributes, 'th');
    $table->addColumn('<i class="fas fa-key" data-toggle="tooltip" data-html="true" title="'.$gL10n->get('ORG_FIELD_DISABLED', array($gL10n->get('SYS_RIGHT_EDIT_USER'))).'"></i>/<i class="fas fa-key admidio-opacity-reduced" data-toggle="tooltip" title="'.$gL10n->get('ORG_FIELD_NOT_DISABLED').'"></i>', $columnAttributes, 'th');
    $table->addColumn('<i class="fas fa-asterisk" data-toggle="tooltip" title="'.$gL10n->get('SYS_REQUIRED_INPUT').'"></i>/<i class="fas fa-asterisk admidio-opacity-reduced" data-toggle="tooltip" title="'.$gL10n->get('SYS_REQUIRED_INPUT').': '.$gL10n->get('SYS_NO').'"></i>', $columnAttributes, 'th');

    $table->addRow('', null, 'th');
    $columnAttributes['colspan'] = 1;
    for ($i = 0; $i < 5; $i++)
    {
        $table->addColumn($gL10n->get('PLG_MITGLIEDSBEITRAG_SHALL'), $columnAttributes, 'th');
        $table->addColumn($gL10n->get('PLG_MITGLIEDSBEITRAG_IS'), $columnAttributes, 'th');
    }

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP');
    $columnValues = array_merge($columnValues, SollIstKategorie($arr, 'Mitgliedschaft'));
    $table->addRowByArray($columnValues, null, null, 8, 5);
    
    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Mitgliedsnummer'));
    $table->addRowByArray($columnValues);
 
    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESSION');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Beitritt'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE');
    $columnValues = array_merge($columnValues, SollIstKategorie($arr, 'Mitgliedsbeitrag'));
    $table->addRowByArray($columnValues, null, null, 8, 5);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('SYS_CONTRIBUTION');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Beitrag'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_PAID');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Bezahlt'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Beitragstext'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Sequenztyp'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Faelligkeitsdatum'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE');
    $columnValues = array_merge($columnValues, SollIstKategorie($arr, 'Mandat'));
    $table->addRowByArray($columnValues, null, null, 8, 5);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Mandatsreferenz'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Mandatsdatum'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Orig_Mandatsreferenz'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA');
    $columnValues  = array_merge($columnValues, SollIstKategorie($arr, 'Kontodaten'));
    $table->addRowByArray($columnValues, null, null, 8, 5);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'IBAN'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_BIC');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'BIC'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_BANK');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Bankname'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Kontoinhaber'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_STREET');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberStrasse'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_POSTCODE');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberPLZ'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_CITY');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberOrt'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberEMail'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Orig_Debtor_Agent'));
    $table->addRowByArray($columnValues);

    $columnValues   = array();
    $columnValues[] = $leer;
    $columnValues[] = $strich.$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN');
    $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Orig_IBAN'));
    $table->addRowByArray($columnValues);

    $page->addHtml($table->show(false));
    
    $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_SECOND_PASSAGE_INFO'));
    
    $form = new HtmlForm('installation_soll_ist_form', null, $page);
    $form->addButton('btnNext', $gL10n->get('SYS_NEXT'), array('icon' => 'fa-arrow-circle-right', 'link' => $gHomepage, 'class' => 'btn-primary'));
    $page->addHtml($form->show(false));
    
    $page->addHtml('<strong>'.$gL10n->get('SYS_NEXT').'</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_INSTALL_END'));
}
$page->show();

// Funktionen, die nur in diesem Script benoetigt werden

/**
 * Prueft die Datenbank auf fehlende Profilfelder und Kategorien
 * @return array $DB_array
 */
function check_DB()
{
    global $gProfileFields;

    //Mit der Version 3.3.0 wurde die Installationsroutine umprogrammiert.
    //Frueher wurde auf usf_name geprueft, jetzt auf usf_name_intern.
    //Die Installationsscripte der Versionen 1.x und 2.x befuellten jedoch
    // von der Kategorie kontodaten usf_name_intern nicht mit dem Wert KONTODATEN.
    //Hier wird deshalb ueberprueft, ob es eine Kategorie kontodaten gibt.
    //Falls von dieser Kategorie der usf_name_intern leer ist, wird er mit KONTODATEN beschrieben.

    $sql = ' SELECT cat_name, cat_name_intern
            FROM '. TBL_CATEGORIES. '
            WHERE cat_name = \'Kontodaten\'
            AND (  cat_org_id = '. $GLOBALS['gCurrentOrgId']. '
            OR cat_org_id IS NULL ) ';

    $statement = $GLOBALS['gDb']->query($sql);
    $row = $statement->fetchObject();

    // Gibt es einen zutreffenden Datensatz?  Wenn ja: UPDATE
    if(isset($row->cat_name_intern) && isset($row->cat_name) && (($row->cat_name_intern) == '') && (($row->cat_name) == 'Kontodaten'))
    {
        $sql = 'UPDATE '.TBL_CATEGORIES.'
                SET cat_name_intern = \'KONTODATEN\'
                WHERE cat_name = \'Kontodaten\'
                AND (  cat_org_id = '. $GLOBALS['gCurrentOrgId']. '
                OR cat_org_id IS NULL ) ';
        $GLOBALS['gDb']->query($sql);
    }

    //Update/Konvertierungsroutine 4.1.2
    // mit Version 4.1.2 wird die Struktur der DB-Eintraege an Admidio angepasst
    // deutsche Bezeichnungen werden durch englische Bezeichnungen ersetzt
    $update_array = array();
    $update_array[] = array('alt_cat_name'        => 'Mitgliedschaft',
                            'alt_cat_name_intern' => 'MITGLIEDSCHAFT'.$GLOBALS['gCurrentOrgId'],
                            'neu_cat_name'        => 'PMB_MEMBERSHIP',
                            'neu_cat_name_intern' => 'MEMBERSHIP'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_cat_name'        => 'Mitgliedsbeitrag',
                            'alt_cat_name_intern' => 'MITGLIEDSBEITRAG'.$GLOBALS['gCurrentOrgId'],
                            'neu_cat_name'        => 'PMB_MEMBERSHIP_FEE',
                            'neu_cat_name_intern' => 'MEMBERSHIP_FEE'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_cat_name'        => 'Kontodaten',
                            'alt_cat_name_intern' => 'KONTODATEN',
                            'neu_cat_name'        => 'PMB_ACCOUNT_DATA',
                            'neu_cat_name_intern' => 'ACCOUNT_DATA');

    foreach($update_array as $data)
    {
        $sql = 'SELECT cat_id
                FROM '.TBL_CATEGORIES.'
                WHERE cat_name = \''.$data['alt_cat_name'].'\'
                AND cat_name_intern = \''.$data['alt_cat_name_intern'].'\'
                AND cat_type = \'USF\'
                 ';
                    //     AND (  cat_org_id = '.$gCurrentOrgId.'
                 // OR cat_org_id IS NULL )
        $statement = $GLOBALS['gDb']->query($sql);
        $row = $statement->fetchObject();
        // Gibt es einen Datensatz mit diesen (Alt-)Daten? Wenn ja: UPDATE auf die neue Version
        if(isset($row->cat_id) && strlen($row->cat_id) > 0)
        {
            $sql = 'UPDATE '.TBL_CATEGORIES.'
                    SET cat_name = \''.$data['neu_cat_name'].'\' ,
                           cat_name_intern = \''.$data['neu_cat_name_intern'].'\'
                    WHERE cat_id = '.$row->cat_id;
                $GLOBALS['gDb']->query($sql);
        }
    }

    $update_array = array();
    $update_array[] = array('alt_usf_name'        => 'PMB_BANK',
                            'alt_usf_name_intern' => 'BANKNAME',
                            'neu_usf_name'        => 'PMB_BANK',
                            'neu_usf_name_intern' => 'BANK');
     $update_array[] = array('alt_usf_name'       => 'Kontoinhaber',
                            'alt_usf_name_intern' => 'KONTOINHABER',
                            'neu_usf_name'        => 'PMB_DEBTOR',
                            'neu_usf_name_intern' => 'DEBTOR');
     $update_array[] = array('alt_usf_name'       => 'PMB_ADDRESS',
                            'alt_usf_name_intern' => 'DEBTORADDRESS',
                            'neu_usf_name'        => 'PMB_DEBTOR_ADDRESS',
                            'neu_usf_name_intern' => 'DEBTOR_ADDRESS');
    $update_array[] = array('alt_usf_name'        => 'PMB_POSTCODE',
                            'alt_usf_name_intern' => 'DEBTORPOSTCODE',
                            'neu_usf_name'        => 'PMB_DEBTOR_POSTCODE',
                            'neu_usf_name_intern' => 'DEBTOR_POSTCODE');
    $update_array[] = array('alt_usf_name'        => 'PMB_CITY',
                            'alt_usf_name_intern' => 'DEBTORCITY',
                            'neu_usf_name'        => 'PMB_DEBTOR_CITY',
                            'neu_usf_name_intern' => 'DEBTOR_CITY');
    $update_array[] = array('alt_usf_name'        => 'PMB_EMAIL',
                            'alt_usf_name_intern' => 'DEBTOREMAIL',
                            'neu_usf_name'        => 'PMB_DEBTOR_EMAIL',
                            'neu_usf_name_intern' => 'DEBTOR_EMAIL');
    $update_array[] = array('alt_usf_name'        => 'Beitritt',
                            'alt_usf_name_intern' => 'BEITRITT'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_ACCESSION',
                            'neu_usf_name_intern' => 'ACCESSION'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_usf_name'        => 'Bezahlt',
                            'alt_usf_name_intern' => 'BEZAHLT'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_PAID',
                            'neu_usf_name_intern' => 'PAID'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_usf_name'        => 'Beitrag',
                            'alt_usf_name_intern' => 'BEITRAG'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_FEE',
                            'neu_usf_name_intern' => 'FEE'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_usf_name'        => 'Beitragstext',
                            'alt_usf_name_intern' => 'BEITRAGSTEXT'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_CONTRIBUTORY_TEXT',
                            'neu_usf_name_intern' => 'CONTRIBUTORY_TEXT'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_usf_name'        => 'PMB_MANDATEDATE',
                            'alt_usf_name_intern' => 'MANDATEDATE'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_MANDATEDATE',
                            'neu_usf_name_intern' => 'MANDATEDATE'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_usf_name'        => 'PMB_DUEDATE',
                            'alt_usf_name_intern' => 'DUEDATE'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_DUEDATE',
                            'neu_usf_name_intern' => 'DUEDATE'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_usf_name'        => 'PMB_SEQUENCETYPE',
                            'alt_usf_name_intern' => 'SEQUENCETYPE'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_SEQUENCETYPE',
                            'neu_usf_name_intern' => 'SEQUENCETYPE'.$GLOBALS['gCurrentOrgId']);
    $update_array[] = array('alt_usf_name'        => 'PMB_ORIG_DEBTOR_AGENT',
                            'alt_usf_name_intern' => 'ORIGDEBTORAGENT',
                            'neu_usf_name'        => 'PMB_ORIG_DEBTOR_AGENT',
                            'neu_usf_name_intern' => 'ORIG_DEBTOR_AGENT');
    $update_array[] = array('alt_usf_name'        => 'PMB_ORIG_IBAN',
                            'alt_usf_name_intern' => 'ORIGIBAN',
                            'neu_usf_name'        => 'PMB_ORIG_IBAN',
                            'neu_usf_name_intern' => 'ORIG_IBAN');
    $update_array[] = array('alt_usf_name'        => 'PMB_ORIG_MANDATEID',
                            'alt_usf_name_intern' => 'ORIGMANDATEID'.$GLOBALS['gCurrentOrgId'],
                            'neu_usf_name'        => 'PMB_ORIG_MANDATEID',
                            'neu_usf_name_intern' => 'ORIG_MANDATEID'.$GLOBALS['gCurrentOrgId']);

    foreach($update_array as $data)
    {
        $sql = 'SELECT usf_id
                FROM '.TBL_USER_FIELDS.' , '. TBL_CATEGORIES.  '
                WHERE usf_name = \''.$data['alt_usf_name'].'\'
                AND usf_name_intern = \''.$data['alt_usf_name_intern'].'\'
                 ';
                    //     AND (  cat_org_id = '.$gCurrentOrgId.'
                //  OR cat_org_id IS NULL )
        $statement = $GLOBALS['gDb']->query($sql);
        $row = $statement->fetchObject();
        // Gibt es einen Datensatz mit diesen (Alt-)Daten? Wenn ja: UPDATE auf die neue Version
        if(isset($row->usf_id) && strlen($row->usf_id) > 0)
        {
            $sql = 'UPDATE '.TBL_USER_FIELDS.'
                    SET usf_name = \''.$data['neu_usf_name'].'\' ,
                        usf_name_intern = \''.$data['neu_usf_name_intern'].'\'
                    WHERE usf_id = '.$row->usf_id;
                $GLOBALS['gDb']->query($sql);
        }
    }
    
    //Update/Konvertierungsroutine 4.1.2/4.2.0 -> 4.3.0
    $sql = 'SELECT usf_id
        		FROM '.TBL_USER_FIELDS.'
        		WHERE usf_name =  \'PMB_DEBTOR_ADDRESS\' ';
    
    $statement = $GLOBALS['gDb']->query($sql);
    $row = $statement->fetchObject();
    // Gibt es einen Datensatz mit diesen Alt-Daten? Wenn ja: UPDATE auf die neue Version
    if (isset($row->usf_id) AND strlen($row->usf_id) > 0)
    {
    	$sql = 'UPDATE '.TBL_USER_FIELDS.'
            		SET usf_name        = \'PMB_DEBTOR_STREET\' ,
                        usf_name_intern = \'DEBTOR_STREET\'  ,
                        usf_description = \'<p>Straße des Kontoinhabers.</p><p>Eine Angabe ist zwingend erforderlich, wenn der Inhaber der Bankverbindung und das Mitglied nicht identisch sind.</p>\'
            		WHERE usf_id = '.$row->usf_id;
    	$GLOBALS['gDb']->query($sql);
    }
    
    $sql = 'SELECT usf_id
        		FROM '.TBL_USER_FIELDS.'
        		WHERE usf_name =  \'PMB_DEBTOR\' ';
    
    $statement = $GLOBALS['gDb']->query($sql);
    $row = $statement->fetchObject();
    // Gibt es einen Datensatz mit diesen Alt-Daten? Wenn ja: UPDATE auf die neue Version
    if (isset($row->usf_id) AND strlen($row->usf_id) > 0)
    {
    	$sql = 'UPDATE '.TBL_USER_FIELDS.'
            		SET usf_description = \'<p>Inhaber der angegebenen Bankverbindung.</p><p>Ein Eintrag ist nur erforderlich, wenn der Inhaber der Bankverbindung und das Mitglied nicht identisch sind. Wenn das Feld belegt ist, dann müssen KtoInh-Straße, KtoInh-PLZ und KtoInh-Ort ausgefüllt sein.</p>\'
            		WHERE usf_id = '.$row->usf_id;
    	$GLOBALS['gDb']->query($sql);
    }
    // Ende Update/Konvertierungsroutine

    // $DB_array['SOLL'] beinhaltet die erforderlichen Werte fuer die Kategorien und die User Fields
    $DB_array['SOLL']['TBL_CATEGORIES']['Kontodaten']       = array('cat_org_id' => 'Null',         'cat_name' => 'PMB_ACCOUNT_DATA',   'cat_name_intern' => 'ACCOUNT_DATA',                  'cat_type' => 'USF', 'cat_system' => 0);
    $DB_array['SOLL']['TBL_CATEGORIES']['Mitgliedsbeitrag'] = array('cat_org_id' => $GLOBALS['gCurrentOrgId'], 'cat_name' => 'PMB_MEMBERSHIP_FEE', 'cat_name_intern' => 'MEMBERSHIP_FEE'.$GLOBALS['gCurrentOrgId'], 'cat_type' => 'USF', 'cat_system' => 0);
    $DB_array['SOLL']['TBL_CATEGORIES']['Mitgliedschaft']   = array('cat_org_id' => $GLOBALS['gCurrentOrgId'], 'cat_name' => 'PMB_MEMBERSHIP',     'cat_name_intern' => 'MEMBERSHIP'.$GLOBALS['gCurrentOrgId'],     'cat_type' => 'USF', 'cat_system' => 0);
    $DB_array['SOLL']['TBL_CATEGORIES']['Mandat']           = array('cat_org_id' => $GLOBALS['gCurrentOrgId'], 'cat_name' => 'PMB_MANDATE',        'cat_name_intern' => 'MANDATE'.$GLOBALS['gCurrentOrgId'],        'cat_type' => 'USF', 'cat_system' => 0);

    $DB_array['SOLL']['TBL_USER_FIELDS']['IBAN']                 = array('usf_name' => 'PMB_IBAN',              'usf_name_intern' => 'IBAN',                             'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');
    $DB_array['SOLL']['TBL_USER_FIELDS']['BIC']                  = array('usf_name' => 'PMB_BIC',               'usf_name_intern' => 'BIC',                              'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Bankname']             = array('usf_name' => 'PMB_BANK',              'usf_name_intern' => 'BANK',                             'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Der Name der Bank für den Bankeinzug');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Kontoinhaber']         = array('usf_name' => 'PMB_DEBTOR',            'usf_name_intern' => 'DEBTOR',                           'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '<p>Inhaber der angegebenen Bankverbindung.</p><p>Ein Eintrag ist nur erforderlich, wenn der Inhaber der Bankverbindung und das Mitglied nicht identisch sind. Wenn das Feld belegt ist, dann müssen KtoInh-Strasse, KtoInh-PLZ und KtoInh-Ort ausgefüllt sein.</p>');

    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberStrasse']  = array('usf_name' => 'PMB_DEBTOR_STREET',     'usf_name_intern' => 'DEBTOR_STREET',                    'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '<p>Adresse des Kontoinhabers.</p><p>Eine Angabe ist zwingend erforderlich, wenn der Inhaber der Bankverbindung und das Mitglied nicht identisch sind.</p>');
    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberPLZ']      = array('usf_name' => 'PMB_DEBTOR_POSTCODE',   'usf_name_intern' => 'DEBTOR_POSTCODE',                  'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '<p>PLZ des Kontoinhabers.</p><p>Eine Angabe ist zwingend erforderlich, wenn der Inhaber der Bankverbindung und das Mitglied nicht identisch sind.</p>');
    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberOrt']      = array('usf_name' => 'PMB_DEBTOR_CITY',       'usf_name_intern' => 'DEBTOR_CITY',                      'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '<p>Wohnort des Kontoinhabers.</p><p>Eine Angabe ist zwingend erforderlich, wenn der Inhaber der Bankverbindung und das Mitglied nicht identisch sind.</p>');
    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberEMail']    = array('usf_name' => 'PMB_DEBTOR_EMAIL',      'usf_name_intern' => 'DEBTOR_EMAIL',                     'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 0, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');

    $DB_array['SOLL']['TBL_USER_FIELDS']['Mitgliedsnummer']      = array('usf_name' => 'PMB_MEMBERNUMBER',      'usf_name_intern' => 'MEMBERNUMBER'.$GLOBALS['gCurrentOrgId'],      'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Beitritt']             = array('usf_name' => 'PMB_ACCESSION',         'usf_name_intern' => 'ACCESSION'.$GLOBALS['gCurrentOrgId'],         'usf_type' => 'DATE',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Das Beitrittsdatum zum Verein');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Bezahlt']              = array('usf_name' => 'PMB_PAID',              'usf_name_intern' => 'PAID'.$GLOBALS['gCurrentOrgId'],              'usf_type' => 'DATE',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Datumsangabe, ob und wann der Beitrag bezahlt wurde');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Beitrag']              = array('usf_name' => 'PMB_FEE',               'usf_name_intern' => 'FEE'.$GLOBALS['gCurrentOrgId'],               'usf_type' => 'DECIMAL', 'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Der errechnete Mitgliedsbeitrag');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Beitragstext']         = array('usf_name' => 'PMB_CONTRIBUTORY_TEXT', 'usf_name_intern' => 'CONTRIBUTORY_TEXT'.$GLOBALS['gCurrentOrgId'], 'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Verwendungszweck');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Mandatsreferenz']      = array('usf_name' => 'PMB_MANDATEID',         'usf_name_intern' => 'MANDATEID'.$GLOBALS['gCurrentOrgId'],         'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Mandatsdatum']         = array('usf_name' => 'PMB_MANDATEDATE',       'usf_name_intern' => 'MANDATEDATE'.$GLOBALS['gCurrentOrgId'],       'usf_type' => 'DATE',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Faelligkeitsdatum']    = array('usf_name' => 'PMB_DUEDATE',           'usf_name_intern' => 'DUEDATE'.$GLOBALS['gCurrentOrgId'],           'usf_type' => 'DATE',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Sequenztyp']           = array('usf_name' => 'PMB_SEQUENCETYPE',      'usf_name_intern' => 'SEQUENCETYPE'.$GLOBALS['gCurrentOrgId'],      'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => '');

    $DB_array['SOLL']['TBL_USER_FIELDS']['Orig_Debtor_Agent']    = array('usf_name' => 'PMB_ORIG_DEBTOR_AGENT', 'usf_name_intern' => 'ORIG_DEBTOR_AGENT',                'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Wird durch das Modul Mandatsänderung automatisch befüllt.');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Orig_IBAN']            = array('usf_name' => 'PMB_ORIG_IBAN',         'usf_name_intern' => 'ORIG_IBAN',                        'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Wird durch das Modul Mandatsänderung automatisch befüllt.');
    $DB_array['SOLL']['TBL_USER_FIELDS']['Orig_Mandatsreferenz'] = array('usf_name' => 'PMB_ORIG_MANDATEID',    'usf_name_intern' => 'ORIG_MANDATEID'.$GLOBALS['gCurrentOrgId'],    'usf_type' => 'TEXT',    'usf_system' => 0, 'usf_disabled' => 1, 'usf_hidden' => 1, 'usf_required_input' => 0, 'usf_description' => 'Wird durch das Modul Mandatsänderung automatisch befüllt.');

     $DB_array['IST'] = $DB_array['SOLL'];

    foreach ($DB_array['IST']['TBL_USER_FIELDS'] as $field => $fielddata)
    {
        $sql = ' SELECT usf_name, usf_name_intern, usf_type, usf_system, usf_disabled, usf_hidden, usf_required_input
            FROM '.TBL_USER_FIELDS.' , '. TBL_CATEGORIES. '
            WHERE usf_name_intern = \''.$fielddata['usf_name_intern'].'\'
            AND usf_cat_id = cat_id
            AND (  cat_org_id = '.$GLOBALS['gCurrentOrgId'].'
            OR cat_org_id IS NULL ) ';

        $statement = $GLOBALS['gDb']->query($sql);
        $row = $statement->fetchObject();

        $DB_array['IST']['TBL_USER_FIELDS'][$field] = array(
            'usf_name_intern' => (isset($row->usf_name_intern) ? $row->usf_name_intern : ''),
            'usf_name'        => (isset($row->usf_name) ? $row->usf_name : ''),
            'usf_type'        => (isset($row->usf_type) ? $row->usf_type : ''),
            'usf_system'      => (isset($row->usf_system) ? $row->usf_system : ''),
            'usf_disabled'    => (isset($row->usf_disabled) ? $row->usf_disabled : ''),
            'usf_hidden'      => (isset($row->usf_hidden) ? $row->usf_hidden : ''),
            'usf_required_input'   => (isset($row->usf_required_input) ? $row->usf_required_input : '')
        );

        if ($DB_array['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern'] != $DB_array['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern'])
        {
            unset($DB_array['IST']['TBL_USER_FIELDS'][$field]);
        }
    }

    foreach ($DB_array['IST']['TBL_CATEGORIES'] as $cat => $catdata)
    {
        $sql = ' SELECT cat_id, cat_org_id, cat_name, cat_name_intern, cat_type, cat_system
            FROM '.TBL_CATEGORIES.'
            WHERE cat_name_intern = \''.$catdata['cat_name_intern'].'\'
            AND cat_type = \'USF\'
            AND (  cat_org_id = '.$GLOBALS['gCurrentOrgId'].'
                OR cat_org_id IS NULL ) ';

        $statement = $GLOBALS['gDb']->query($sql);
        $row = $statement->fetchObject();

        $DB_array['IST']['TBL_CATEGORIES'][$cat] = array(
            'cat_name_intern' => (isset($row->cat_name_intern) ? $row->cat_name_intern : ''),
            'cat_name'        => (isset($row->cat_name) ? $row->cat_name : ''),
            'cat_id'          => (isset($row->cat_id) ? $row->cat_id : ''),
            'cat_org_id'      => (isset($row->cat_org_id) ? $row->cat_org_id : ''),
            'cat_type'        => (isset($row->cat_type) ? $row->cat_type : ''),
            'cat_system'      => (isset($row->cat_system) ? $row->cat_system : '')
        );

        if ($DB_array['IST']['TBL_CATEGORIES'][$cat]['cat_name_intern'] != $DB_array['SOLL']['TBL_CATEGORIES'][$cat]['cat_name_intern'])
        {
            unset($DB_array['IST']['TBL_CATEGORIES'][$cat]);
        }
    }
    return $DB_array;
}

/**
 * Erzeugt die Texte fuer den Soll-Ist-Vergleich der Profilfelder
 * @param  array    $arr
 * @param  string   $field
 * @return array   $columnValues
 */
function SollIstProfilfeld($arr, $field)
{
    $columnValues = array();

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern'] != $arr['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern'])
    {
        $columnValues[] = '<strong>'.$arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern'].'</strong>';
        $columnValues[] = '<strong>'.$arr['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern'].'</strong>';
    }
    else
    {
        $columnValues[] = $arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern'];
        $columnValues[] = $arr['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern'];
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_type'] != $arr['IST']['TBL_USER_FIELDS'][$field]['usf_type'])
    {
        $columnValues[] = '<strong>'.$arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_type'].'</strong>';
        $columnValues[] = '<strong>'.$arr['IST']['TBL_USER_FIELDS'][$field]['usf_type'].'</strong>';
    }
    else
    {
        $columnValues[] = $arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_type'];
        $columnValues[] = $arr['IST']['TBL_USER_FIELDS'][$field]['usf_type'];
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_hidden'] == 1)
    {
        $columnValues[] = '<i class="fas fa-eye admidio-opacity-reduced" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_HIDDEN').'"></i>';
    }                       
    else
    {
        $columnValues[] =  '<i class="fas fa-eye" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_NOT_HIDDEN').'"></i>';
    }

    if ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_hidden'] == 1)
    {
        $columnValues[] = '<i class="fas fa-eye admidio-opacity-reduced" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_HIDDEN').'"></i>';
    }
    else
    {
        $columnValues[] = '<i class="fas fa-eye" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_NOT_HIDDEN').'"></i>';
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_disabled'] == 1)
    {
        $columnValues[] = '<i class="fas fa-key" data-toggle="tooltip" data-html="true" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_DISABLED', array($GLOBALS['gL10n']->get('SYS_RIGHT_EDIT_USER'))).'"></i>';
    }
    else
    {
        $columnValues[] = '<i class="fas fa-key admidio-opacity-reduced" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_NOT_DISABLED').'"></i>';
    }

    if ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_disabled'] == 1)
    {
        $columnValues[] = '<i class="fas fa-key" data-toggle="tooltip" data-html="true" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_DISABLED', array($GLOBALS['gL10n']->get('SYS_RIGHT_EDIT_USER'))).'"></i>';
    }
    else
    {
        $columnValues[] = '<i class="fas fa-key admidio-opacity-reduced" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('ORG_FIELD_NOT_DISABLED').'"></i>';
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 1)
    {
        $columnValues[] = '<i class="fas fa-asterisk" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_YES').'"></i>';
    }
    elseif ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 2)
    {
        $columnValues[] = '<i class="fas fa-asterisk" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_ONLY_AT_REGISTRATION_AND_OWN_PROFILE').'"></i>';
    }
    elseif ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 3)
    {
        $columnValues[] = '<i class="fas fa-asterisk" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_NOT_AT_REGISTRATION').'"></i>';
    }
    else
    {
        $columnValues[] = '<i class="fas fa-asterisk admidio-opacity-reduced" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_NO').'"></i>';
    }

    if ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 1)
    { 
        $columnValues[] =  '<i class="fas fa-asterisk" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_YES').'"></i>' ;
    }
    elseif ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 2)
    {
        $columnValues[] =  '<i class="fas fa-asterisk" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_ONLY_AT_REGISTRATION_AND_OWN_PROFILE').'"></i>' ;
    }
    elseif ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 3)
    {
        $columnValues[] =  '<i class="fas fa-asterisk" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_NOT_AT_REGISTRATION').'"></i>' ;
    }
    else
    {
        $columnValues[] ='<i class="fas fa-asterisk admidio-opacity-reduced" data-toggle="tooltip" title="'.$GLOBALS['gL10n']->get('SYS_NO').'"></i>';
    }

    return $columnValues;
}

/**
 * Erzeugt die Texte fuer den Soll-Ist-Vergleich der Kategorien
 * @param  array    $arr
 * @param  string   $field
 * @return array   $columnValues
 */
function SollIstKategorie($arr, $field)
{
    $columnValues = array();

    if ($arr['SOLL']['TBL_CATEGORIES'][$field]['cat_name_intern'] != $arr['IST']['TBL_CATEGORIES'][$field]['cat_name_intern'])
    {
        $columnValues[] = '&nbsp;';
        $columnValues[] = '<strong>'.$arr['SOLL']['TBL_CATEGORIES'][$field]['cat_name_intern'].'</strong>';
        $columnValues[] = '<strong>'.$arr['IST']['TBL_CATEGORIES'][$field]['cat_name_intern'].'</strong>';
        $columnValues[] = '&nbsp;';
     }
     else
     {
        $columnValues[] = '&nbsp;';
        $columnValues[] = $arr['SOLL']['TBL_CATEGORIES'][$field]['cat_name_intern'];
        $columnValues[] = $arr['IST']['TBL_CATEGORIES'][$field]['cat_name_intern'];
        $columnValues[] = '&nbsp;';
    }
    return $columnValues;
}

/**
 * Erzeugt den naechsten freien Wert fuer cat_sequence
 * @param  string $cat_type    Kategorietyp
 * @return int                 Der naechste freie Wert fuer cat_sequence
 */
function getNextCatSequence($cat_type)
{
    $sql    = 'SELECT cat_type, cat_sequence
                FROM '. TBL_CATEGORIES. '
                WHERE cat_type = \''.$cat_type.'\'
                AND (  cat_org_id  = '. $GLOBALS['gCurrentOrgId']. '
                    OR cat_org_id IS NULL )
                ORDER BY cat_sequence ASC';

    $statement = $GLOBALS['gDb']->query($sql);

    while($row = $statement->fetch())
    {
        $sequence = $row['cat_sequence'];
    }
    return $sequence+1;
}

/**
 * Erzeugt den naechsten freien Wert fuer usf_sequence
 * @param  int $usf_cat_id   Cat_Id
 * @return int               Der naechste freie Wert fuer usf_sequence
 */
function getNextFieldSequence($usf_cat_id)
{
    $sequence = 0;

    $sql    = 'SELECT usf_cat_id, usf_sequence
                FROM '. TBL_USER_FIELDS. '
                WHERE usf_cat_id = \''.$usf_cat_id.'\'
                ORDER BY usf_sequence ASC';

    $statement = $GLOBALS['gDb']->query($sql);

    while($row = $statement->fetch())
    {
        $sequence = $row['usf_sequence'];
    }
    return $sequence+1;
}

/**
 * Gibt zu einem Kategorienamen die entsprechende Cat_ID zurueck
 * @param   string  $cat_name_intern       Name der zu pruefenden Kategorie
 * @return  int     cat_id          Cat_id der Kategorie
 */
function getCat_IDPMB($cat_name_intern)
{
    $sql = ' SELECT cat_id
            FROM '.TBL_CATEGORIES.'
            WHERE cat_name_intern = \''.$cat_name_intern.'\'
            AND cat_type = \'USF\'
            AND (  cat_org_id = '. $GLOBALS['gCurrentOrgId']. '
                OR cat_org_id IS NULL ) ';

    $statement = $GLOBALS['gDb']->query($sql);
    $row = $statement->fetchObject();

    return $row->cat_id;
}

/**
 * Erzeugt eine Kategorie in der Tabelle TBL_CATEGORIES
 * @param   array   $arr          Array mit Werten für die Spalten
 * @param   int     $sequence     Neue Sequence der anzulegenden Kategorie
 * @return  void
 */
function setCategory($arr, $sequence)
{
    $newCategory = new TableAccess($GLOBALS['gDb'], TBL_CATEGORIES, 'cat');
    $newCategory->setValue('cat_sequence', $sequence);
    
    foreach ($arr as $key => $value)
    {
        $newCategory->setValue($key, $value);
    }
    
    $newCategory->save();
} 

/**
 * Erzeugt ein Profilfeld in der Tabelle TBL_USER_FIELDS
 * @param   int     $cat_id       Cat_Id der Kategorie, in der das Profilfeld angelegt wird
 * @param   array   $arr          Array mit Werten für die Spalten
 * @param   int     $sequence     Neue Sequence des anzulegenden Profilfeldes
 * @return  void
 */
function setUserField($cat_id, $arr, $sequence)
{
    $newUserField = new TableAccess($GLOBALS['gDb'], TBL_USER_FIELDS, 'usf');
    $newUserField->setValue('usf_cat_id', $cat_id);
    $newUserField->setValue('usf_sequence', $sequence);
    
    foreach ($arr as $key => $value)
    {
        $newUserField->setValue($key, $value);
    }
    
    $newUserField->save();
}  
