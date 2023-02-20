<?php
/**
 ***********************************************************************************************
 * Modul bill_export fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:  none
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
$sum          = 0;
$nr           = 1;
$header       = array();              //'xlsx'
$rows         = array();              //'xlsx'
$columnValues = array();
$filename     = $pPreferences->config['Rechnungs-Export']['rechnung_dateiname'];
$exportMode   = $pPreferences->config['Rechnungs-Export']['rechnung_dateityp'];


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
$columnValues[] = $gL10n->get('SYS_NAME');
$columnValues[] = $gL10n->get('SYS_STREET');
$columnValues[] = $gL10n->get('SYS_POSTCODE');
$columnValues[] = $gL10n->get('SYS_LOCATION');
$columnValues[] = $gL10n->get('SYS_EMAIL');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_SUM');
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
    
    if (strlen($user->getValue('DEBTOR')) !== 0)
    {
        $columnValues[] = $user->getValue('DEBTOR');
    }
    else
    {
        $columnValues[] = $user->getValue('FIRST_NAME').' '.$user->getValue('LAST_NAME');
    }
    $columnValues[] = $user->getValue('STREET');
    $columnValues[] = $user->getValue('POSTCODE');
    $columnValues[] = $user->getValue('CITY');
    $columnValues[] = $user->getValue('EMAIL');
    $columnValues[] = $user->getValue('FEE'.$gCurrentOrgId);
    $columnValues[] = $user->getValue('CONTRIBUTORY_TEXT'.$gCurrentOrgId);
        
    $sum += $user->getValue('FEE'.$gCurrentOrgId);
    $columnValues[] = $sum;       
        
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

    $rows[] = $columnValues;
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
    $writer->setKeywords(array($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'), $gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_FILE'), $gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA')));
    $writer->setDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_CREATED_WITH'));
    $writer->writeSheet($rows,'', $header);
    $writer->writeToStdOut();
}