<?php
/**
 ***********************************************************************************************
 * Mitgliedsbeitrag
 *
 * Version 5.1.5
 *
 * This plugin calculates membership fees based on role assignments.
 *
 * Author: rmb
 *
 * Compatible with Admidio version 4.1.1
 *
 * @copyright 2004-2022 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

//Fehlermeldungen anzeigen
//error_reporting(E_ALL);

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/../../adm_program/system/login_valid.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

//script_name ist der Name wie er im Menue eingetragen werden muss, also ohne evtl. vorgelagerte Ordner wie z.B. /playground/adm_plugins/mitgliedsbeitrag...
$_SESSION['pMembershipFee']['script_name'] = substr($_SERVER['SCRIPT_NAME'], strpos($_SERVER['SCRIPT_NAME'], FOLDER_PLUGINS));

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$showOption = admFuncVariableIsValid($_GET, 'show_option', 'string');

$pPreferences = new ConfigTablePMB();
$checked = $pPreferences->checkforupdate();

$role = new TableRoles($gDb);

if ($checked == 1)        //Update (Konfigurationdaten sind vorhanden, der Stand ist aber unterschiedlich zur Version.php)
{
	$pPreferences->init();
}
elseif ($checked == 2)        //Installationsroutine durchlaufen
{
	admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/'.'installation.php');
}

$pPreferences->read();            // (checked == 0) : nur Einlesen der Konfigurationsdaten

$duedates = array();
$directdebittype = false;
$duedatecount = 0;
$paidcount = 0;

//alle Mitglieder einlesen
$members = list_members(array('DUEDATE'.$gCurrentOrgId, 'SEQUENCETYPE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'PAID'.$gCurrentOrgId, 'FEE'.$gCurrentOrgId, 'MANDATEID'.$gCurrentOrgId, 'MANDATEDATE'.$gCurrentOrgId, 'IBAN', 'BIC'), 0);

//jetzt wird gezaehlt
foreach ($members as $member => $memberdata)
{
    //alle Faelligkeitsdaten einlesen
    if (!empty($memberdata['DUEDATE'.$gCurrentOrgId])
    	&& !empty($memberdata['FEE'.$gCurrentOrgId])
        && empty($memberdata['PAID'.$gCurrentOrgId])
     //   && !empty($memberdata['CONTRIBUTORY_TEXT'.$gCurrentOrgId])
        && !empty($memberdata['IBAN']))
    {
        $directdebittype = true;

        if(!isset($duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]))
        {
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]] = array();
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['FNAL'] = 0;
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['RCUR'] = 0;
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['OOFF'] = 0;
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['FRST'] = 0;
        }

        if($memberdata['SEQUENCETYPE'.$gCurrentOrgId] == 'FNAL')
        {
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['FNAL']++;
        }
        elseif($memberdata['SEQUENCETYPE'.$gCurrentOrgId] == 'RCUR')
        {
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['RCUR']++;
        }
        elseif($memberdata['SEQUENCETYPE'.$gCurrentOrgId] == 'OOFF')
        {
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['OOFF']++;
        }
        else
        {
            $duedates[$memberdata['DUEDATE'.$gCurrentOrgId]]['FRST']++;
        }
    }
    if (!empty($memberdata['DUEDATE'.$gCurrentOrgId]))
    {
    	$duedatecount++;
    }
    if (!empty($memberdata['PAID'.$gCurrentOrgId]))
    {
        $paidcount++;
    }
}
unset($members);

$beitrag = analyse_mem();
$sum = 0;

$rols = beitragsrollen_einlesen();
$sortArray = array();
$selectBoxEntriesBeitragsrollen = array();

foreach ($rols as $key => $data)
{
    $selectBoxEntriesBeitragsrollen[$key] = array($key, $data['rolle'], expand_rollentyp($data['rollentyp']));
    $sortArray[$key] = expand_rollentyp($data['rollentyp']);
}

array_multisort($sortArray, SORT_ASC, $selectBoxEntriesBeitragsrollen);
$selectBoxEntriesAlleRollen = 'SELECT rol_id, rol_name, cat_name
          						 FROM '.TBL_ROLES.'
    					   INNER JOIN '.TBL_CATEGORIES.'
                                   ON cat_id = rol_cat_id
                                WHERE rol_valid   = 1
                                  AND (  cat_org_id  = '. $gCurrentOrgId. '
                                   OR cat_org_id IS NULL )
                             ORDER BY cat_sequence, rol_name';

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE');

$gNavigation->addStartUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php', $headline);

// create html page object
$page = new HtmlPage('plg-mitgliedsbeitrag-main', $headline);

if($showOption != '')
{
    if(in_array($showOption, array('mandategenerate', 'mandates', 'createmandateid')) == true)
    {
        $navOption = 'mandatemanagement';
    }
    elseif(in_array($showOption, array('sepa', 'bill')) == true)
    {
        $navOption = 'export';
    }
    elseif(in_array($showOption, array('producemembernumber', 'copy', 'familyrolesupdate')) == true)
    {
        $navOption = 'options';
    }
    else
    {
        $navOption = 'fees';
    }

    $page->addJavascript('
        $("#tabs_nav_'.$navOption.'").attr("class", "nav-link active");
        $("#tabs-'.$navOption.'").attr("class", "tab-pane fade show active");
        $("#collapse_'.$showOption.'").attr("class", "collapse show");
        location.hash = "#" + "panel_'.$showOption.'";',
        true
    );       
}
else
{
    $page->addJavascript('
        $("#tabs_nav_fees").attr("class", "nav-link active");
        $("#tabs-fees").attr("class", "tab-pane fade show active");',
        true
    );
}

if (isUserAuthorizedForPreferences())
{  
	// show link to pluginpreferences
	$page->addPageFunctionsMenuItem('admMenuItemPreferencesLists', $gL10n->get('SYS_SETTINGS'),
	    ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php',  'fa-cog');
}

//show Static Display in Header
$formStaticDisplay = new HtmlForm('navbar_static_display', '', $page, array('type' => 'navbar', 'setFocus' => false));

$formStaticDisplay->addCustomContent('', '<table class="table table-condensed">
    <tr>
        <td style="text-align: right;">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_TOTAL').':</td>
        <td style="text-align: right;">'.($beitrag['BEITRAG_kto']+$beitrag['BEITRAG_rech']).' '.$gSettingsManager->getString('system_currency').'</td>
        <td>&#160;&#160;&#160;&#160;</td>
        <td align = "right">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ALREADY_PAID').':</td>
        <td style="text-align: right;">'.($beitrag['BEZAHLT_kto']+$beitrag['BEZAHLT_rech']).' '.$gSettingsManager->getString('system_currency').'</td>
        <td>&#160;&#160;&#160;&#160;</td>
        <td style="text-align: right;">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_PENDING').':</td>
        <td style="text-align: right;">'.(($beitrag['BEITRAG_kto']+$beitrag['BEITRAG_rech'])-($beitrag['BEZAHLT_kto']+$beitrag['BEZAHLT_rech'])).' '.$gSettingsManager->getString('system_currency').'</td>
    </tr>
    <tr>
        <td style="text-align: right;">#</td>
        <td style="text-align: right;">'.($beitrag['BEITRAG_kto_anzahl']+$beitrag['BEITRAG_rech_anzahl']).'</td>
        <td>&#160;&#160;&#160;&#160;</td>
        <td style="text-align: right;">#</td>
        <td style="text-align: right;">'.($beitrag['BEZAHLT_kto_anzahl']+$beitrag['BEZAHLT_rech_anzahl']).'</td>
        <td>&#160;&#160;&#160;&#160;</td>
        <td style="text-align: right;">#</td>
        <td style="text-align: right;">'.(($beitrag['BEITRAG_kto_anzahl']+$beitrag['BEITRAG_rech_anzahl'])-($beitrag['BEZAHLT_kto_anzahl']+$beitrag['BEZAHLT_rech_anzahl'])).'</td>
    </tr>
</table>');

$page->addHtml($formStaticDisplay->show(false));

if(count($rols) > 0)
{
    $page->addHtml('
    <ul id="preferences_tabs" class="nav nav-tabs" role="tablist">
        <li class="nav-item">
            <a id="tabs_nav_fees" class="nav-link" href="#tabs-fees" data-toggle="tab" role="tab">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FEES').'</a>
        </li>
        <li class="nav-item">
            <a id="tabs_nav_mandatemanagement" class="nav-link" href="#tabs-mandatemanagement" data-toggle="tab" role="tab">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT').'</a>
        </li>
        <li class="nav-item">
            <a id="tabs_nav_export" class="nav-link" href="#tabs-export" data-toggle="tab" role="tab">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT').'</a>
        </li>
        <li class="nav-item">
            <a id="tabs_nav_options" class="nav-link" href="#tabs-options" data-toggle="tab" role="tab">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OPTIONS').'</a>
        </li>
    </ul>
    
    <div class="tab-content">
    ');
    
    // TAB: FEES
    $page->addHtml(openMenueTab('fees', 'accordion_fees'));
        
    // PANEL: REMAPPING
                
    if (count(beitragsrollen_einlesen('alt')) > 0)
    {
        $formRemapping = new HtmlForm('remapping_form', null, $page, array('class' => 'form-preferences'));
    
        $formRemapping->addButton('btn_remapping_age_staggered_roles', $gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING'), array('icon' => 'fa-random', 'link' => 'remapping.php', 'class' => 'btn-primary offset-sm-3'));
        $formRemapping->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_AGE_STAGGERED_ROLES_DESC'));
    
        $page->addHtml(getMenuePanel('fees', 'remapping', 'accordion_fees', $gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_AGE_STAGGERED_ROLES'), 'fas fa-random', $formRemapping->show()));
    }
    
    // PANEL: DELETE

    $formDelete = new HtmlForm('delete_form', null, $page, array('class' => 'form-preferences'));
    
    $formDelete->addButton('btn_delete', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE'), array('icon' => 'fa-trash-alt', 'link' => 'delete.php', 'class' => 'btn-primary offset-sm-3'));
    $formDelete->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_DELETE_DESC'));
        
    $page->addHtml(getMenuePanel('fees', 'delete', 'accordion_fees', $gL10n->get('PLG_MITGLIEDSBEITRAG_RESET'), 'fas fa-trash-alt', $formDelete->show()));
                                                
    // PANEL: RECALCULATION

    unset($_SESSION['pMembershipFee']['recalculation_user']);
    
    $formRecalculation = new HtmlForm('recalculation_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/recalculation.php', $page, array('class' => 'form-preferences'));
    
    $formRecalculation->addSelectBox('recalculation_roleselection', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_SELECTION'), $selectBoxEntriesBeitragsrollen, array('defaultValue' => (isset($_SESSION['pMembershipFee']['recalculation_rol_sel']) ? $_SESSION['pMembershipFee']['recalculation_rol_sel'] : ''), 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_RECALCULATION_ROLLQUERY_DESC', 'multiselect' => true));
    $formRecalculation->addCheckbox('recalculation_notpaid', $gL10n->get('PLG_MITGLIEDSBEITRAG_RECALCULATION_NOT_PAID'), false, array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_RECALCULATION_NOT_PAID_DESC'));
    $radioButtonEntries = array('standard'  => $gL10n->get('PLG_MITGLIEDSBEITRAG_DEFAULT'),
                                'overwrite' => $gL10n->get('PLG_MITGLIEDSBEITRAG_OVERWRITE'),
                                'summation' => $gL10n->get('PLG_MITGLIEDSBEITRAG_SUMMATION'));
    $formRecalculation->addRadioButton('recalculation_modus', '', $radioButtonEntries, array('defaultValue' => 'standard', 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_RECALCULATION_MODUS_DESC'));
    $formRecalculation->addSubmitButton('btn_recalculation', $gL10n->get('PLG_MITGLIEDSBEITRAG_RECALCULATION'), array('icon' => 'fa-calculator', 'class' => 'offset-sm-3'));
    $formRecalculation->addCustomContent('', '<strong>'.$gL10n->get('SYS_NOTE').':</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_RECALCULATION_MODUS_NOTE'));
 
    $page->addHtml(getMenuePanel('fees', 'recalculation', 'accordion_fees', $gL10n->get('PLG_MITGLIEDSBEITRAG_RECALCULATION'), 'fas fa-calculator', $formRecalculation->show()));
 
    // PANEL: INDIVIDUAL_CONTRIBUTIONS
    
    if ( $pPreferences->config['individual_contributions']['access_to_module'] )
    {   
        $formIndividualContributions = new HtmlForm('individual_contributions_form', null, $page, array('class' => 'form-preferences'));
            
        $formIndividualContributions->addButton('btn_individualcontributions', $gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS'), array('icon' => 'fa-calculator', 'link' => 'individualcontributions.php', 'class' => 'btn-primary offset-sm-3'));
        $formIndividualContributions->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS_DESC'));
            
        $page->addHtml(getMenuePanel('fees', 'individualcontributions', 'accordion_fees', $gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS'), 'fas fa-calculator', $formIndividualContributions->show()));
    }
    
    // PANEL: PAYMENTS
    
    $formPayments = new HtmlForm('payments_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php', $page, array('class' => 'form-preferences'));
                                
    $formPayments->addSelectBox('payments_roleselection', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_SELECTION'), $selectBoxEntriesBeitragsrollen, array('defaultValue' => (isset($_SESSION['pMembershipFee']['payments_rol_sel']) ? $_SESSION['pMembershipFee']['payments_rol_sel'] : ''), 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_PAYMENTS_ROLLQUERY_DESC', 'multiselect' => true));              
    $formPayments->addSubmitButton('btn_payments', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS_EDIT'), array('icon' => 'fa-coins', 'class' => 'offset-sm-3'));   
    $formPayments->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS_DESC'));

    $page->addHtml(getMenuePanel('fees', 'payments', 'accordion_fees', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS'), 'fas fa-coins', $formPayments->show()));                            
                            
    // PANEL: ANALYSIS 
    
    $page->addHtml(getMenuePanelHeaderOnly('fees', 'analysis', 'accordion_fees', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_ANALYSIS'), 'fas fa-stream'));
            
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
    
    $page->addHtml(getMenuePanelFooterOnly());
                        
    // PANEL: HISTORY
    
    $formHistory = new HtmlForm('history_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/history.php', $page, array('class' => 'form-preferences'));
    
    $formHistory->addSubmitButton('btn_history', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HISTORY_SHOW'), array('icon' => 'fa-history',  'class' => 'offset-sm-3'));
    $formHistory->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HISTORY_DESC'));
                                
    $page->addHtml(getMenuePanel('fees', 'history', 'accordion_fees', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HISTORY'), 'fas fa-history', $formHistory->show()));                               
                                
    $page->addHtml(closeMenueTab());
    
    // TAB: MANDATEMANAGEMENT
    $page->addHtml(openMenueTab('mandatemanagement', 'accordion_mandatemanagement'));
    
    // PANEL: CREATEMANDATEID

    unset($_SESSION['pMembershipFee']['createmandateid_user']);
     
    $formCreateMandateID = new HtmlForm('createmandateid_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/create_mandate_id.php', $page, array('class' => 'form-preferences'));
    
    $formCreateMandateID->addSelectBoxFromSql('createmandateid_roleselection', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_SELECTION'), $gDb, $selectBoxEntriesAlleRollen, array('defaultValue' => (isset($_SESSION['pMembershipFee']['createmandateid_rol_sel']) ? $_SESSION['pMembershipFee']['createmandateid_rol_sel'] : ''), 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID_DESC', 'multiselect' => true));
    $formCreateMandateID->addSubmitButton('btn_createmandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID'), array('icon' => 'fa-plus-circle',  'class' => 'offset-sm-3'));

    $page->addHtml(getMenuePanel('mandatemanagement', 'createmandateid', 'accordion_mandatemanagement', $gL10n->get('PLG_MITGLIEDSBEITRAG_CREATE_MANDATE_ID'), 'fas fa-plus-circle', $formCreateMandateID->show())); 
                            
    // PANEL: MANDATES
    
    $formMandates = new HtmlForm('mandates_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php', $page, array('class' => 'form-preferences'));
    
    $formMandates->addSubmitButton('btn_mandates', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_EDIT'), array('icon' => 'fa-edit', 'class' => 'offset-sm-3'));
    $formMandates->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_EDIT_DESC'));
                                
    $page->addHtml(getMenuePanel('mandatemanagement', 'mandates', 'accordion_mandatemanagement', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_EDIT'), 'fas fa-edit', $formMandates->show()));  
    
    $page->addHtml(closeMenueTab());
    
    // TAB: EXPORT
    $page->addHtml(openMenueTab('export', 'accordion_export'));
                            
    // PANEL: SEPA
    
    $page->addHtml(getMenuePanelHeaderOnly('export', 'sepa', 'accordion_export', $gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA'), 'fas fa-file-invoice-dollar'));
    
    $formDuedates = new HtmlForm('duedates_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/duedates.php', $page, array('class' => 'form-preferences'));
    
    $formDuedates->addSelectBox('duedates_roleselection', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_SELECTION'), $selectBoxEntriesBeitragsrollen, array('defaultValue' => (isset($_SESSION['pMembershipFee']['duedates_rol_sel']) ? $_SESSION['pMembershipFee']['duedates_rol_sel'] : ''), 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DUEDATE_ROLLQUERY_DESC', 'multiselect' => true));
    $formDuedates->addSubmitButton('btn_duedates', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'), array('icon' => 'fa-edit', 'class' => 'offset-sm-3'));
    $formDuedates->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_EDIT_DESC'));
    $formDuedates->addLine();
    $page->addHtml($formDuedates->show(false));
    
    $formSepa = new HtmlForm('sepa_export_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/sepa_export.php', $page);
    if (!$directdebittype)
    {
        $html = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NO_DUEDATES_EXIST').'</div>';
        $formSepa->addCustomContent('', $html);
    }
    else
    {
        $htmlTable = '
        <table class="table table-condensed">
            <thead>
                <tr>
                    <th style="text-align: center;font-weight:bold;">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE').'</th>
                    <th style="text-align: center;font-weight:bold;">FRST</th>
                    <th style="text-align: center;font-weight:bold;">RCUR</th>
                    <th style="text-align: center;font-weight:bold;">FNAL</th>
                    <th style="text-align: center;font-weight:bold;">OOFF</th>
                </tr>
            </thead>';
    
            $htmlTable .= '
            <tbody id="test">';
    
                foreach($duedates as $duedate => $duedatedata)
                {
                	$datumtemp = \DateTime::createFromFormat('Y-m-d', $duedate);
    
                    $htmlTable .= '
                    <tr>
                        <td style="text-align: center;">'.$datumtemp->format($gSettingsManager->getString('system_date')).'</td>
                        <td style="text-align: center;"><input type="checkbox" name="duedatesepatype[]" ';
                            if ($duedatedata['FRST'] == 0)
                            {
                                $htmlTable .= ' disabled="disabled" ';
                            }
                            $htmlTable .= 'value="'.$duedate.'FRST" /><small> ('.$duedatedata['FRST'].')</small>
                        </td>
                        <td style="text-align: center;"><input type="checkbox" name="duedatesepatype[]" ';
                            if ($duedatedata['RCUR'] == 0)
                            {
                                $htmlTable .= ' disabled="disabled" ';
                            }
                            $htmlTable .= 'value="'.$duedate.'RCUR" /><small> ('.$duedatedata['RCUR'].')</small>
                        </td>
                        <td style="text-align: center;"><input type="checkbox" name="duedatesepatype[]"  ';
                            if ($duedatedata['FNAL'] == 0)
                            {
                                $htmlTable .= ' disabled="disabled" ';
                            }
                            $htmlTable .= 'value="'.$duedate.'FNAL" /><small> ('.$duedatedata['FNAL'].')</small>
                        </td>
                        <td style="text-align: center;"><input type="checkbox" name="duedatesepatype[]"  ';
                            if ($duedatedata['OOFF'] == 0)
                            {
                                $htmlTable .= ' disabled="disabled" ';
                            }
                            $htmlTable .= 'value="'.$duedate.'OOFF" /><small> ('.$duedatedata['OOFF'].')</small>
                        </td>
                    </tr>';
                }
                $htmlTable .= '
                </tbody>
        </table>';
    
        $formSepa->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_SELECTION'), $htmlTable);
        $formSepa->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_SELECTION_DESC'));
        $formSepa->addLine();
        
        $formSepa->addSubmitButton('btn_xml_file', $gL10n->get('PLG_MITGLIEDSBEITRAG_XML_FILE'), array('icon' => 'fa-file-alt', 'class' => 'btn-primary offset-sm-3'));
        $formSepa->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_XML_FILE_DESC'));
        $html = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA_EXPORT_INFO').'</div>';
        $formSepa->addStaticControl('', '', $html);
        $formSepa->addLine();   
        $formSepa->addSubmitButton('btn_xml_kontroll_datei', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE'), array('icon' => 'fa-file', 'class' => 'btn-primary offset-sm-3'));
        $formSepa->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE_DESC'));
        $formSepa->addLine();
        $formSepa->addSubmitButton('btn_pre_notification', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION'), array('icon' => 'fa-file', 'class' => 'btn-primary offset-sm-3'));
        $formSepa->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_DESC'));
    }
    
    $page->addHtml($formSepa->show(false));
                            
    $page->addHtml(getMenuePanelFooterOnly());
                            
    // PANEL: BILL
     
    $formBillExport = new HtmlForm('billexport_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/bill.php', $page, array('class' => 'form-preferences'));
    $formBillExport->addSubmitButton('btn_bill', $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL_EDIT'), array('icon' => 'fa-file',  'class' => 'offset-sm-3'));
    $formBillExport->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL_EDIT_DESC'));
    
    $page->addHtml(getMenuePanel('export', 'bill', 'accordion_export', $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL'), 'fas fa-file-invoice', $formBillExport->show()));  
    
    $page->addHtml(closeMenueTab());
    
    // TAB: OPTIONS
    $page->addHtml(openMenueTab('options', 'accordion_options'));
    
    // PANEL: PRODUCEMEMBERNUMBER

    unset($_SESSION['pMembershipFee']['membernumber_user']);
    
    $formProduceMembernumber = new HtmlForm('producemembernumber_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/membernumber.php', $page, array('class' => 'form-preferences'));
    
    $formProduceMembernumber->addSelectBoxFromSql('producemembernumber_roleselection', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_SELECTION'), $gDb, $selectBoxEntriesAlleRollen, array('defaultValue' => (isset($_SESSION['pMembershipFee']['membernumber_rol_sel']) ? $_SESSION['pMembershipFee']['membernumber_rol_sel'] : ''), 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_PRODUCE_MEMBERNUMBER_DESC2', 'multiselect' => true));
    $formProduceMembernumber->addInput('producemembernumber_format', $gL10n->get('PLG_MITGLIEDSBEITRAG_FORMAT'), (isset($_SESSION['pMembershipFee']['membernumber_format']) ? $_SESSION['pMembershipFee']['membernumber_format'] : (isset($pPreferences->config['membernumber']['format']) ? $pPreferences->config['membernumber']['format'] : '')), array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FORMAT_DESC'));
    $formProduceMembernumber->addCheckbox('producemembernumber_fill_gaps', $gL10n->get('PLG_MITGLIEDSBEITRAG_FILL_GAPS'),  (isset($_SESSION['pMembershipFee']['membernumber_fill_gaps']) ? $_SESSION['pMembershipFee']['membernumber_fill_gaps'] : (isset($pPreferences->config['membernumber']['fill_gaps']) ? $pPreferences->config['membernumber']['fill_gaps'] : '')), array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FILL_GAPS_DESC'));         
    $formProduceMembernumber->addSubmitButton('btn_producemembernumber', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRODUCE_MEMBERNUMBER'), array('icon' => 'fa-plus-circle',  'class' => 'offset-sm-3'));
    $formProduceMembernumber->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRODUCE_MEMBERNUMBER_DESC'));
    
    $page->addHtml(getMenuePanel('options', 'producemembernumber', 'accordion_options', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRODUCE_MEMBERNUMBER'), 'fas fa-plus-circle', $formProduceMembernumber->show()));  
           
    // PANEL: FAMILYROLESUPDATE
    
    unset($_SESSION['pMembershipFee']['familyroles_update']);
    
    $formFamilyrolesUpdate = new HtmlForm('familyrolesupdate_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/familyroles_update.php', $page, array('class' => 'form-preferences'));
    
    $formFamilyrolesUpdate->addSubmitButton('btn_familyrolesupdate', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE'), array('icon' => 'fa-sync',  'class' => 'offset-sm-3'));
    $formFamilyrolesUpdate->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE_DESC'));
    
    $page->addHtml(getMenuePanel('options', 'familyrolesupdate', 'accordion_options', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE'), 'fas fa-sync', $formFamilyrolesUpdate->show()));  
                            
    // PANEL: COPY

    $formCopy = new HtmlForm('copy_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/copy.php', $page, array('class' => 'form-preferences'));
    
    $formCopy->addSubmitButton('btn_copy', $gL10n->get('PLG_MITGLIEDSBEITRAG_COPY'), array('icon' => 'fa-clone',  'class' => 'offset-sm-3'));
    $formCopy->addCustomContent('', $gL10n->get('PLG_MITGLIEDSBEITRAG_COPY_DESC'));
    
    $page->addHtml(getMenuePanel('options', 'copy', 'accordion_options', $gL10n->get('PLG_MITGLIEDSBEITRAG_COPY'), 'fas fa-clone', $formCopy->show()));  
                            
    // PANEL: TESTS

    //Panel Tests nur anzeigen, wenn mindesteste ein Einzeltest aktiviert ist
    if (in_array(1, $pPreferences->config['tests_enable']))
    {
        $formTests = new HtmlForm('tests_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag_function.php', array('form' => 'tests')), $page, array('class' => 'form-preferences'));
        
        if ($pPreferences->config['tests_enable']['age_staggered_roles'])
        {
            $formTests->openGroupBox('AGE_STAGGERed_roles', $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_rols()));
            $formTests->closeGroupBox();
        }
        
        // Pruefung der Rollenmitgliedschaften in den altersgestaffelten Rollen nur, wenn es mehrere Staffelungen gibt
        if ($pPreferences->config['tests_enable']['role_membership_age_staggered_roles'] && count($pPreferences->config['Altersrollen']['altersrollen_token']) > 1)
        {
            $formTests->openGroupBox('role_membership_AGE_STAGGERed_roles', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_rollenmitgliedschaft_altersrolle()));
            $formTests->closeGroupBox();
        }
        
        if ($pPreferences->config['tests_enable']['role_membership_duty_and_exclusion'])
        {
            $formTests->openGroupBox('role_membership_duty', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_rollenmitgliedschaft_pflicht()));
            $formTests->closeGroupBox();
            
            $formTests->openGroupBox('role_membership_exclusion', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_rollenmitgliedschaft_ausschluss()));
            $formTests->closeGroupBox();
        }
        
        if ($pPreferences->config['tests_enable']['family_roles'])
        {
            $formTests->openGroupBox('family_roles', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_family_roles()));
            $formTests->closeGroupBox();
        }
        
        if ($pPreferences->config['tests_enable']['account_details'])
        {
            $formTests->openGroupBox('account_details', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA_TEST_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_account_details()));
            $formTests->closeGroupBox();
        }
        
        if ($pPreferences->config['tests_enable']['mandate_management'])
        {
            $formTests->openGroupBox('mandate_management', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT_DESC2').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_mandate_management()));
            $formTests->closeGroupBox();
        }
        
        if ($pPreferences->config['tests_enable']['iban_check'])
        {
            $formTests->openGroupBox('iban_check', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBANCHECK'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBANCHECK_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_iban()));
            $formTests->closeGroupBox();
        }
        
        if ($pPreferences->config['tests_enable']['bic_check'])
        {
            $formTests->openGroupBox('bic_check', $gL10n->get('PLG_MITGLIEDSBEITRAG_BICCHECK'));
            $formTests->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_BICCHECK_DESC').'</strong>');
            $formTests->addDescription(showTestResultWithScrollbar(check_bic()));
            $formTests->closeGroupBox();
        }
        
        //seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
        $formTests->addStaticControl('', '', '');
        
        $page->addHtml(getMenuePanel('options', 'tests', 'accordion_options', $gL10n->get('PLG_MITGLIEDSBEITRAG_TESTS'), 'fas fa-user-md', $formTests->show()));  
    }
           
    // PANEL: ROLEOVERVIEW

    $page->addHtml(getMenuePanelHeaderOnly('options', 'roleoverview', 'accordion_options', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_OVERVIEW'), 'fas fa-info'));
     
    $datatable = true;
    $hoverRows = true;
    $classTable  = 'table table-condensed';
    $table = new HtmlTable('table_role_overview', $page, $hoverRows, $datatable, $classTable);
    
    $columnAlign  = array('left', 'right', 'right');
    $table->setColumnAlignByArray($columnAlign);
    
    $columnValues = array($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'), 'dummy', $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBER_ACCOUNT'));
    $table->addRowHeadingByArray($columnValues);
    
    $rollen = beitragsrollen_einlesen('', array('LAST_NAME'));
    foreach ($rollen as $rol_id => $data)
    {
        $role->readDataById($rol_id);
    
        $columnValues = array();
        $columnValues[] = '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles_new.php', array('role_uuid' => $role->getValue('rol_uuid'))). '">'.$data['rolle']. '</a>';
        $columnValues[] = expand_rollentyp($data['rollentyp']);
        $columnValues[] = count($data['members']);
        $table->addRowByArray($columnValues);
    }
    $table->setDatatablesGroupColumn(2);
    $table->setDatatablesRowsPerPage(10);
    
    $page->addHtml($table->show(false));
    $page->addHtml(getMenuePanelFooterOnly());
                                
    $page->addHtml(closeMenueTab());

    $page->addHtml('</div>');               //end div class="tab-content"
}
else
{   
    $page->addHtml('<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NO_CONTRIBUTION_ROLES_DEFINED').'</div>');
}

$page->show();
