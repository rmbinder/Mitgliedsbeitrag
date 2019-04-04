<?php
/**
 ***********************************************************************************************
 * Dieses Plugin führt einen Abgleich durch zwischen den Einträgen von Beitrag, Beitragszeitraum
 * und Beschreibung von Familienrollen mit den Angaben in Einstellungen-Familienrollen.
 *
 * @copyright 2004-2019 The Admidio Team
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
$getMode    = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'preview', 'validValues' => array('preview', 'write', 'print')));

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

$role = new TableRoles($gDb);

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE');

// create html page object
$page = new HtmlPage($headline);

if ($getMode == 'preview')     //Default
{
	$familyRolesToUpdate = array();
	
	// alle Familienkonfigurationen durchlaufen
	foreach($pPreferences->config['Familienrollen']['familienrollen_prefix'] as $key => $data)
	{
		// Familienrollen anhand des Präfix bestimmen
		$sql = 'SELECT rol_id, rol_name, rol_cost, rol_cost_period, rol_description
                FROM '.TBL_ROLES.', '. TBL_CATEGORIES. '
				WHERE rol_valid  = 1
                AND rol_name LIKE \''. $data.'%'. '\'
				AND rol_cat_id = cat_id
            	AND ( cat_org_id = '.ORG_ID.'
                OR cat_org_id IS NULL ) '; 		
		
		$statement = $gDb->query($sql);
		
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
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php?show_option=familyrolesupdate', $gL10n->get('SYS_BACK'), 'back.png');
	
	$form = new HtmlForm('familyrolesupdate_preview_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/familyroles_update.php?mode=write', $page);
	
	if (sizeof($familyRolesToUpdate) > 0)
	{
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
			//Sonderfall absichern, wenn rol_cost_period_is oder rol_cost_period_shall nicht gesetzt, also null ist
			$rol_cost_period_is = $data['rol_cost_period_is'] !== null ? TableRoles::getCostPeriods($data['rol_cost_period_is']) : '';
			$rol_cost_period_shall = $data['rol_cost_period_shall'] !== null ? TableRoles::getCostPeriods($data['rol_cost_period_shall']) : '';
			
			$columnValues = array();
			$columnValues[] = '<a href="'. ADMIDIO_URL . FOLDER_MODULES . '/roles/roles_new.php?rol_id='. $rol_id. '">'.$data['rol_name']. '</a>';
			$columnValues[] = ($data['rol_cost_update'] ? '<strong>'.$data['rol_cost_is'].'</strong>': $data['rol_cost_is']);
			$columnValues[] = ($data['rol_cost_update'] ? '<strong>'.$data['rol_cost_shall'].'</strong>': $data['rol_cost_shall']);
			$columnValues[] = ($data['rol_cost_period_update'] ? '<strong>'.$rol_cost_period_is.'</strong>': $rol_cost_period_is);
			$columnValues[] = ($data['rol_cost_period_update'] ? '<strong>'.$rol_cost_period_shall.'</strong>' : $rol_cost_period_shall);
			$columnValues[] = ($data['rol_description_update'] ? '<strong>'.$data['rol_description_is'].'</strong>' : $data['rol_description_is']);
			$columnValues[] = ($data['rol_description_update'] ? '<strong>'.$data['rol_description_shall'].'</strong>': $data['rol_description_shall']);
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));
		$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => 'btn-primary'));
		$form->addDescription('<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE_PREVIEW'));
	}
	else 
	{
		$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE_NO_ASSIGN'));
		
		//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
		$form->addStaticControl('', '', '');
	}
	$page->addHtml($form->show(false));
}
elseif ($getMode == 'write')
{
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'.ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/familyroles_update.php?mode=print", "_blank");
        });',
		true
	);
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php?show_option=familyrolesupdate', $gL10n->get('SYS_BACK'), 'back.png');
	$headerMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');
	
	$form = new HtmlForm('familyrolesupdate_saved_form', null, $page);
	
	$datatable = true;
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
			'<a href="'. ADMIDIO_URL . FOLDER_MODULES . '/roles/roles_new.php?rol_id='. $rol_id. '">'.$data['rol_name']. '</a>',
			$role->getValue('rol_cost'),
			($role->getValue('rol_cost_period') !== null ? TableRoles::getCostPeriods($role->getValue('rol_cost_period')) : ''),
			$role->getValue('rol_description') );
		$table->addRowByArray($columnValues);
	}
	
	$page->addHtml($table->show(false));
	$form->addDescription('<strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>');
	
	//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
	$form->addStaticControl('', '', '');
	
	$page->addHtml($form->show(false));
}
elseif ($getMode == 'print')
{
	// create html page object without the custom theme files
	$hoverRows = false;
	$datatable = false;
	$classTable  = 'table table-condensed table-striped';
	$page->hideThemeHtml();
	$page->hideMenu();
	$page->setPrintMode();
	$page->setHeadline($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_UPDATE'));
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


