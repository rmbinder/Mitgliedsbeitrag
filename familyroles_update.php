<?php
/**
 ***********************************************************************************************
 * Dieses Plugin führt einen Abgleich durch zwischen den Einträgen von Beitrag, Beitragszeitraum
 * und Beschreibung von Familienrollen mit den Angaben in Einstellungen-Familienrollen.
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * mode       : preview - preview of the familiy roles update
 *              write   - save the new values for cost, cost period and description
 *              print   - preview for printing  
 *
 *****************************************************************************/

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'preview', 'validValues' => array('preview', 'write', 'print')));

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

$role = new TableRoles($gDb);

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE');

$gNavigation->addUrl(CURRENT_URL, $headline);

if ($getMode == 'preview')     //Default
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-familyrolesupdate-preview', $headline);
    
	$familyRolesToUpdate = array();
	
	// alle Familienkonfigurationen durchlaufen
	foreach($pPreferences->config['Familienrollen']['familienrollen_prefix'] as $key => $data)
	{
		// Familienrollen anhand des Präfix bestimmen
		$sql = 'SELECT rol_id, rol_name, rol_cost, rol_cost_period, rol_description
                FROM '.TBL_ROLES.', '. TBL_CATEGORIES. '
				WHERE rol_valid  = 1
                AND rol_name LIKE ?
				AND rol_cat_id = cat_id
            	AND ( cat_org_id = ?
                OR cat_org_id IS NULL ) '; 		
		
	   $statement = $gDb->queryPrepared($sql, array($data.'%', $gCurrentOrgId));
		
		// die Einträge von Beitrag, Beitragszeitraum und Beschreibung auslesen und mit den Einträgen im Setup vergleichen
		while ($row = $statement->fetch())
		{
			if (	($row['rol_cost']        != $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$key]) 
				|| 	($row['rol_cost_period'] != $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$key])
				|| 	($row['rol_description'] != $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$key]) )
			{
				$familyRolesToUpdate[$row['rol_id']] = array(
					'rol_name' => $row['rol_name'],
						
					'rol_cost_is' => $row['rol_cost'],
					'rol_cost_shall' => $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$key],
					'rol_cost_update' => ($row['rol_cost'] != $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$key] ? true : false),
						
					'rol_cost_period_is' => $row['rol_cost_period'],
					'rol_cost_period_shall' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$key], 
					'rol_cost_period_update' => ($row['rol_cost_period'] != $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$key] ? true : false),
						
					'rol_description_is' =>  $row['rol_description'],
					'rol_description_shall' => $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$key],
					'rol_description_update' => ($row['rol_description'] != $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$key] ? true : false)
				);
			}
		}
	}
	
	if (sizeof($familyRolesToUpdate) > 0)
	{
    	$form = new HtmlForm('familyrolesupdate_preview_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/familyroles_update.php', array('mode' => 'write')), $page);

		// save new values in session (for mode write and mode print)
		$_SESSION['pMembershipFee']['familyroles_update'] = $familyRolesToUpdate;
	
		$datatable = true;
		$hoverRows = true;
		$classTable  = 'table table-condensed';
        
		$table = new HtmlTable('table_new_familyrolesupdate', $page, $hoverRows, $datatable, $classTable);
		$table->setColumnAlignByArray(array('left', 'center', 'center', 'center','center', 'center', 'center'));
		$columnValues = array(
			$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'),
			$gL10n->get('PLG_MITGLIEDSBEITRAG_IS').' '.$gL10n->get('SYS_CONTRIBUTION'),
			$gL10n->get('PLG_MITGLIEDSBEITRAG_SHALL').' '.$gL10n->get('SYS_CONTRIBUTION'),
			$gL10n->get('PLG_MITGLIEDSBEITRAG_IS').' '.$gL10n->get('SYS_CONTRIBUTION_PERIOD'),
			$gL10n->get('PLG_MITGLIEDSBEITRAG_SHALL').' '.$gL10n->get('SYS_CONTRIBUTION_PERIOD'),
			$gL10n->get('PLG_MITGLIEDSBEITRAG_IS').' '.$gL10n->get('SYS_DESCRIPTION'),
			$gL10n->get('PLG_MITGLIEDSBEITRAG_SHALL').' '.$gL10n->get('SYS_DESCRIPTION') );
		$table->addRowHeadingByArray($columnValues);

		foreach ($familyRolesToUpdate as $rol_id => $data)
		{
            $role->readDataById( $rol_id);
        
			//Sonderfall absichern, wenn rol_cost_period_is oder rol_cost_period_shall nicht gesetzt, also null ist
			$rol_cost_period_is = $data['rol_cost_period_is'] !== null ? TableRoles::getCostPeriods($data['rol_cost_period_is']) : '';
			$rol_cost_period_shall = $data['rol_cost_period_shall'] !== null ? TableRoles::getCostPeriods($data['rol_cost_period_shall']) : '';
			
			$columnValues = array();
			$columnValues[] = '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles_new.php', array('role_uuid' => $role->getValue('rol_uuid'))). '">'.$data['rol_name']. '</a>';
			$columnValues[] = ($data['rol_cost_update'] ? '<strong>'.$data['rol_cost_is'].'</strong>': $data['rol_cost_is']);
			$columnValues[] = ($data['rol_cost_update'] ? '<strong>'.$data['rol_cost_shall'].'</strong>': $data['rol_cost_shall']);
			$columnValues[] = ($data['rol_cost_period_update'] ? '<strong>'.$rol_cost_period_is.'</strong>': $rol_cost_period_is);
			$columnValues[] = ($data['rol_cost_period_update'] ? '<strong>'.$rol_cost_period_shall.'</strong>' : $rol_cost_period_shall);
			$columnValues[] = ($data['rol_description_update'] ? '<strong>'.$data['rol_description_is'].'</strong>' : $data['rol_description_is']);
			$columnValues[] = ($data['rol_description_update'] ? '<strong>'.$data['rol_description_shall'].'</strong>': $data['rol_description_shall']);
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));
        
		$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check'));
		$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE_PREVIEW'));
        
        $page->addHtml($form->show(false));
	}
	else 
	{
        $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE_NO_ASSIGN').'<br/><br/>');
	}
}
elseif ($getMode == 'write')
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-familyrolesupdate-write', $headline);
    
 	$page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'fa-print');
    
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'. SecurityUtils::encodeUrl(ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/familyroles_update.php', array('mode' => 'print')). '", "_blank");
        });',
		true
	);
	
	$datatable = false;
	$hoverRows = true;
	$classTable  = 'table table-condensed';
    
	$table = new HtmlTable('table_saved_familyrolesupdate', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'center', 'center', 'center'));
	$columnValues = array(
		$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'),
		$gL10n->get('SYS_CONTRIBUTION'),
		$gL10n->get('SYS_CONTRIBUTION_PERIOD'),
		$gL10n->get('SYS_DESCRIPTION') );
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['familyroles_update'] as $rol_id => $data)
	{
		$role->readDataById( $rol_id);
		
		if ($data['rol_cost_update'])
		{
			$role->setvalue('rol_cost', $data['rol_cost_shall']);
		}
		if ($data['rol_cost_period_update'])
		{
			$role->setvalue('rol_cost_period', $data['rol_cost_period_shall']);
		}
		if ($data['rol_description_update'])
		{
			$role->setvalue('rol_description', $data['rol_description_shall']);
		}
		$role->save();

		$columnValues = array(
			'<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles_new.php', array('role_uuid' => $role->getValue('rol_uuid'))). '">'.$data['rol_name']. '</a>',
			$role->getValue('rol_cost'),
			($role->getValue('rol_cost_period') !== null ? TableRoles::getCostPeriods($role->getValue('rol_cost_period')) : ''),
			$role->getValue('rol_description') );
		$table->addRowByArray($columnValues);
	}
	
	$page->addHtml('<div style="width:100%; height: 500px; overflow:auto; border:20px;">');
	$page->addHtml($table->show(false));
	$page->addHtml('</div><br/>');
    $page->addHtml('<strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong><br/><br/>');
}
elseif ($getMode == 'print')
{
	// create html page object without the custom theme files
	$hoverRows = false;
	$datatable = false;
	$classTable = 'table table-condensed table-striped';
 
 	$page = new HtmlPage('plg-mitgliedsbeitrag-familyrolesupdate-print', $headline);
	$page->setPrintMode();

	$table = new HtmlTable('table_print_familyrolesupdate', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'center', 'center', 'center'));
	$columnValues = array(
		$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'),
		$gL10n->get('SYS_CONTRIBUTION'),
		$gL10n->get('SYS_CONTRIBUTION_PERIOD'),
		$gL10n->get('SYS_DESCRIPTION') );
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['familyroles_update'] as $rol_id => $data)
	{
		$role->readDataById( $rol_id);
		
		$columnValues = array(
			$data['rol_name'],
			$role->getValue('rol_cost'),
			($role->getValue('rol_cost_period') !== null ? TableRoles::getCostPeriods($role->getValue('rol_cost_period')) : ''),
			$role->getValue('rol_description') );
		$table->addRowByArray($columnValues);
	}
	$page->addHtml($table->show(false));
}

$page->show();


