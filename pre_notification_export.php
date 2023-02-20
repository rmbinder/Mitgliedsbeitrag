<?php
/**
 ***********************************************************************************************
 * Modul pre_notification_export fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:   none
 * 
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

$user = new User($gDb, $gProfileFields);

// initialize some special mode parameters
$separator    = '';
$valueQuotes  = '';
$charset      = '';
$csvStr       = '';
$header       = array();              //'xlsx'
$rows         = array();              //'xlsx'
$columnValues = array();
$filename     = $pPreferences->config['SEPA']['vorabinformation_dateiname'];
$exportMode   = $pPreferences->config['SEPA']['vorabinformation_dateityp'];

switch ($exportMode)
{
    case 'csv-ms':
        $separator   = ';';  // Microsoft Excel 2007 or new needs a semicolon
        $valueQuotes = '"';  // all values should be set with quotes
        $exportMode  = 'csv';
        $charset     = 'iso-8859-1';
        break;
    case 'csv-oo':
        $separator   = ',';   // a CSV file should have a comma
        $valueQuotes = '"';   // all values should be set with quotes
        $exportMode  = 'csv';
        $charset     = 'utf-8';
        break;
    case 'xlsx':
        include_once(__DIR__ . '/libs/PHP_XLSXWriter/xlsxwriter.class.php');
        $exportMode   = 'xlsx';
        break;
    default:
        break;
}

$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_SERIAL_NUMBER');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER');
$columnValues[] = $gL10n->get('SYS_FIRSTNAME');
$columnValues[] = $gL10n->get('SYS_LASTNAME');
$columnValues[] = $gL10n->get('SYS_STREET');
$columnValues[] = $gL10n->get('SYS_POSTCODE');
$columnValues[] = $gL10n->get('SYS_CITY');
$columnValues[] = $gL10n->get('SYS_EMAIL');
$columnValues[] = $gL10n->get('SYS_PHONE');
$columnValues[] = $gL10n->get('SYS_MOBILE');
$columnValues[] = $gL10n->get('SYS_BIRTHDAY');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESSION');                             
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER').'/'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEBTOR');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_STREET');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_POSTCODE');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_CITY');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL');                                                          
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT');
$rows[] = $columnValues;

if ($exportMode === 'csv')
{
   for ($i = 0; $i < (sizeof($columnValues)); $i++)
        {
            if ($i !== 0)
            {
                $csvStr .= $separator;
            }
            $csvStr .= $valueQuotes. $columnValues[$i]. $valueQuotes;
        }
        $csvStr .= "\n";
}

$nr = 1;

foreach ($_SESSION['pMembershipFee']['checkedArray'] as $UserId => $dummy)
{
    $user->readDataById($UserId);

    $columnValues = array();
    $columnValues[] = $nr;
    $columnValues[] = $user->getValue('MEMBERNUMBER'.$gCurrentOrgId);
    $columnValues[] = $user->getValue('FIRST_NAME');
    $columnValues[] = $user->getValue('LAST_NAME');
    $columnValues[] = $user->getValue('STREET');
    $columnValues[] = $user->getValue('POSTCODE');
    $columnValues[] = $user->getValue('CITY');
    $columnValues[] = $user->getValue('EMAIL');
    $columnValues[] = $user->getValue('PHONE');
    $columnValues[] = $user->getValue('MOBILE');
    $columnValues[] = $user->getValue('BIRTHDAY');
    $columnValues[] = $user->getValue('ACCESSION'.$gCurrentOrgId);
    
    if (strlen($user->getValue('DEBTOR')) !== 0)
    {
        $columnValues[] = $user->getValue('DEBTOR');
        $columnValues[] = $user->getValue('DEBTOR_STREET');
        $columnValues[] = $user->getValue('DEBTOR_POSTCODE');
        $columnValues[] = $user->getValue('DEBTOR_CITY');
        $columnValues[] = $user->getValue('DEBTOR_EMAIL');
    }
    else
    {
        $columnValues[] = $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME');
        $columnValues[] = $user->getValue('STREET');
        $columnValues[] = $user->getValue('POSTCODE');
        $columnValues[] = $user->getValue('CITY');
        $columnValues[] = $user->getValue('EMAIL');
    }
    
    $columnValues[] = $user->getValue('BANK');
    $columnValues[] = $user->getValue('BIC');
    $columnValues[] = $user->getValue('IBAN');
    $columnValues[] = $user->getValue('MANDATEDATE'.$gCurrentOrgId);
    $columnValues[] = $user->getValue('MANDATEID'.$gCurrentOrgId);
    $columnValues[] = $user->getValue('DUEDATE'.$gCurrentOrgId);
    $columnValues[] = $user->getValue('FEE'.$gCurrentOrgId);
    $columnValues[] = $user->getValue('CONTRIBUTORY_TEXT'.$gCurrentOrgId);
    
    if ($exportMode === 'csv')
    {
        for ($i = 0; $i < (sizeof($columnValues)); $i++)
        {
            if ($i !== 0)
            {
                $csvStr .= $separator;
            }
            $csvStr .= $valueQuotes. $columnValues[$i]. $valueQuotes;
        }
        $csvStr .= "\n";
    }
    elseif ($exportMode === 'xlsx')
    {
        $rows[] = $columnValues;
    }
    $nr += 1;
}
 
$filename = FileSystemUtils::getSanitizedPathEntry($filename) . '.' . $exportMode;

header('Content-Disposition: attachment; filename="'.$filename.'"');

// neccessary for IE6 to 8, because without it the download with SSL has problems
header('Cache-Control: private');
header('Pragma: public');

if ($exportMode === 'csv')
{
    // download CSV file
    header('Content-Type: text/comma-separated-values; charset='.$charset);
    
    if ($charset === 'iso-8859-1')
    {
        echo utf8_decode($csvStr);
    }
    else
    {
        echo $csvStr;
    }
}
elseif ($exportMode === 'xlsx')
{
    header('Content-disposition: attachment; filename="'.XLSXWriter::sanitize_filename($filename).'"');
    header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
    header('Content-Transfer-Encoding: binary');
    header('Cache-Control: must-revalidate');
    header('Pragma: public');
    
    $writer = new XLSXWriter();
    $writer->setAuthor($gCurrentUser->getValue('FIRST_NAME').' '.$gCurrentUser->getValue('LAST_NAME'));
    $writer->setTitle($filename);
    $writer->setSubject($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'));
    $writer->setCompany($gCurrentOrganization->getValue('org_longname'));
    $writer->setKeywords(array($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'), $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION'), $gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA')));
    $writer->setDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_CREATED_WITH'));
    $writer->writeSheet($rows,'', $header);
    $writer->writeToStdOut();
}




