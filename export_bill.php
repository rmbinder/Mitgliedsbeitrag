<?php
/**
 ***********************************************************************************************
 * Rechnungs-Export fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2022 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   Grundgeruest erstellt von Günter Scheuermann
 *
 * Parameters:        
 * 
 * export_mode_bill   :  Output (csv-ms, csv-oo, xlsx)
 ***********************************************************************************************
 */

require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Konfiguration einlesen
$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// Initialize and check the parameters
$exportMode = admFuncVariableIsValid($_POST, 'export_mode_bill', 'string', array('defaultValue' => 'xlsx', 'validValues' => array('csv-ms', 'csv-oo', 'xlsx' )));

//alle Mitglieder einlesen
$members = list_members(array('FIRST_NAME', 'LAST_NAME', 'STREET', 'POSTCODE', 'CITY', 'EMAIL', 'FEE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'PAID'.$gCurrentOrgId, 'IBAN', 'DEBTOR'), 0);

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

//alle Mitglieder durchlaufen und aufgrund von Rollenzugehoerigkeiten die Beitraege bestimmen
foreach ($members as $member => $memberdata)
{
    if (empty($memberdata['IBAN'])
            &&  empty($memberdata['PAID'.$gCurrentOrgId])
            && !empty($memberdata['FEE'.$gCurrentOrgId])
            && !empty($memberdata['CONTRIBUTORY_TEXT'.$gCurrentOrgId]))
    {
        if (empty($memberdata['DEBTOR']))
        {
            $members[$member]['DEBTOR'] = $memberdata['FIRST_NAME'].' '.$memberdata['LAST_NAME'];
        }
        
        $columnValues = array();
        $columnValues[] = $nr;
        $columnValues[] = $members[$member]['DEBTOR'];
        $columnValues[] = $members[$member]['STREET'];
        $columnValues[] = $members[$member]['POSTCODE'];
        $columnValues[] = $members[$member]['CITY'];
        $columnValues[] = $members[$member]['EMAIL'];
        $columnValues[] = $members[$member]['FEE'.$gCurrentOrgId];
        $columnValues[] = $members[$member]['CONTRIBUTORY_TEXT'.$gCurrentOrgId];
        
        $sum += $members[$member]['FEE'.$gCurrentOrgId];
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
}

if (count($rows) > 1)
{
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
}
else
{
    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_FILE');

    $message = '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_EXPORT_NO_DATA').'</strong>';
    $message .= '<br/><br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_EXPORT_NO_DATA2');

    // create html page object
    $page = new HtmlPage('plg-mitgliedsbeitrag-export-bill', $headline);

    $form = new HtmlForm('export_bill_form', null, $page);
    $form->addDescription($message);
    $form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => 'fa-arrow-circle-right', 'link' => SecurityUtils::encodeUrl('mitgliedsbeitrag.php', array('show_option' => 'statementexport')), 'class' => 'btn-primary'));

    $page->addHtml($form->show(false));
    $page->show();
}

