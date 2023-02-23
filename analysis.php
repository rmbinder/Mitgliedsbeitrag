<?php
/**
 ***********************************************************************************************
 * Beitragsanalyse fuer das Admidio-Plugin Mitgliedsbeitrag
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

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_ANALYSIS');

$page = new HtmlPage('plg-mitgliedsbeitrag-analysis', $headline);
  
$gNavigation->addUrl(CURRENT_URL, $headline);

$beitrag = analyse_mem();
$sum = 0;

$page->addHtml(openGroupBox('members_contribution', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERS_CONTRIBUTION')));

$datatable = false;
$hoverRows = true;
$classTable  = 'table table-condensed';

$table = new HtmlTable('table_members_contribution', $page, $hoverRows, $datatable, $classTable);

$columnAttributes['style'] = 'text-align: left';
$table->addColumn('', $columnAttributes, 'th');

$columnAttributes['colspan'] = 2;
$columnAttributes['style'] = 'text-align: right';
$table->addColumn($gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_ACCOUNT_DATA'), $columnAttributes, 'th');
$table->addColumn($gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_ACCOUNT_DATA'), $columnAttributes, 'th');
$table->addColumn($gL10n->get('PLG_MITGLIEDSBEITRAG_SUM'), $columnAttributes, 'th');

$columnAlign  = array('left', 'right', 'right', 'right', 'right', 'right', 'right');
$table->setColumnAlignByArray($columnAlign);

$columnValues = array();
$columnValues[] = '';
$columnValues[] = $gL10n->get('SYS_CONTRIBUTION');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_NUMBER');
$columnValues[] = $gL10n->get('SYS_CONTRIBUTION');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_NUMBER');
$columnValues[] = $gL10n->get('SYS_CONTRIBUTION');
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_NUMBER');
$table->addRowByArray($columnValues);

$columnValues = array();
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_DUES');
$columnValues[] = $beitrag['BEITRAG_kto'].' '.$gSettingsManager->getString('system_currency');
$columnValues[] = $beitrag['BEITRAG_kto_anzahl'];
$columnValues[] = $beitrag['BEITRAG_rech'].' '.$gSettingsManager->getString('system_currency');
$columnValues[] = $beitrag['BEITRAG_rech_anzahl'];
$columnValues[] = ($beitrag['BEITRAG_kto']+$beitrag['BEITRAG_rech']).' '.$gSettingsManager->getString('system_currency');
$columnValues[] = ($beitrag['BEITRAG_kto_anzahl']+$beitrag['BEITRAG_rech_anzahl']);
$table->addRowByArray($columnValues);

$columnValues = array();
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_ALREADY_PAID');
$columnValues[] = $beitrag['BEZAHLT_kto'].' '.$gSettingsManager->getString('system_currency');
$columnValues[] = $beitrag['BEZAHLT_kto_anzahl'];
$columnValues[] = $beitrag['BEZAHLT_rech'].' '.$gSettingsManager->getString('system_currency');
$columnValues[] = $beitrag['BEZAHLT_rech_anzahl'];
$columnValues[] = ($beitrag['BEZAHLT_kto']+$beitrag['BEZAHLT_rech']).' '.$gSettingsManager->getString('system_currency');
$columnValues[] = ($beitrag['BEZAHLT_kto_anzahl']+$beitrag['BEZAHLT_rech_anzahl']);
$table->addRowByArray($columnValues);

$columnValues = array();
$columnValues[] = $gL10n->get('PLG_MITGLIEDSBEITRAG_PENDING');
$columnValues[] = ($beitrag['BEITRAG_kto']-$beitrag['BEZAHLT_kto']).' '.$gSettingsManager->getString('system_currency');
$columnValues[] = ($beitrag['BEITRAG_kto_anzahl']-$beitrag['BEZAHLT_kto_anzahl']);
$columnValues[] = ($beitrag['BEITRAG_rech']-$beitrag['BEZAHLT_rech']).' '.$gSettingsManager->getString('system_currency');
$columnValues[] = ($beitrag['BEITRAG_rech_anzahl']-$beitrag['BEZAHLT_rech_anzahl']);
$columnValues[] = (($beitrag['BEITRAG_kto']+$beitrag['BEITRAG_rech'])-($beitrag['BEZAHLT_kto']+$beitrag['BEZAHLT_rech'])).' '.$gSettingsManager->getString('system_currency');
$columnValues[] = (($beitrag['BEITRAG_kto_anzahl']+$beitrag['BEITRAG_rech_anzahl'])-($beitrag['BEZAHLT_kto_anzahl']+$beitrag['BEZAHLT_rech_anzahl']));
$table->addRowByArray($columnValues);

$table->setDatatablesRowsPerPage(10);
$page->addHtml($table->show(false));
$page->addHtml('<strong>'.$gL10n->get('SYS_NOTE').':</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERS_CONTRIBUTION_DESC'));

$page->addHtml(closeGroupBox());

$page->addHtml(openGroupBox('roles_contribution', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLES_CONTRIBUTION')));

$datatable = true;
$hoverRows = true;
$classTable  = 'table table-condensed';
$table = new HtmlTable('table_roles_contribution', $page, $hoverRows, $datatable, $classTable);

$columnAlign  = array('left', 'right', 'right', 'right', 'right');
$table->setColumnAlignByArray($columnAlign);

$columnValues = array($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE'), 'dummy', $gL10n->get('SYS_CONTRIBUTION'), $gL10n->get('PLG_MITGLIEDSBEITRAG_NUMBER'), $gL10n->get('PLG_MITGLIEDSBEITRAG_SUM'));
$table->addRowHeadingByArray($columnValues);

$rollen = analyse_rol();
foreach ($rollen as $rol => $roldata)
{
    $columnValues = array();
    $columnValues[] = $roldata['rolle'];
    $columnValues[] = expand_rollentyp($roldata['rollentyp']);
    $columnValues[] = $roldata['rol_cost'].' '.$gSettingsManager->getString('system_currency');
    $columnValues[] = count($roldata['members']);
    $columnValues[] = ($roldata['rol_cost']*count($roldata['members'])).' '.$gSettingsManager->getString('system_currency');
    
    $sum += ($roldata['rol_cost']*count($roldata['members']));
    $table->addRowByArray($columnValues);
}

$columnValues = array($gL10n->get('PLG_MITGLIEDSBEITRAG_TOTAL'), '', '', '', $sum.' '.$gSettingsManager->getString('system_currency'));
$table->addRowByArray($columnValues);
$table->setDatatablesGroupColumn(2);
$table->setDatatablesRowsPerPage(10);

$page->addHtml($table->show(false));
$page->addHtml('<strong>'.$gL10n->get('SYS_NOTE').':</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLES_CONTRIBUTION_DESC'));

$page->addHtml(closeGroupBox());

$page->show();
