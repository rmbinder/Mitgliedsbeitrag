<?php
/**
 ***********************************************************************************************
 * Neuzuordnung von Mitgliedern
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

/******************************************************************************
 * Parameters:
 *
 * mode       : preview - preview of the new remapping
 *              write   - save the new remapping
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

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_AGE_STAGGERED_ROLES');

// create html page object
$page = new HtmlPage($headline);

if ($getMode == 'preview')     //Default
{
	//Vor der Neuzuordnung die altersgestaffelten Rollen auf Luecken oder Ueberlappungen pruefen
	$arr = check_rols();
	if (!in_array($gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_OK'), $arr))
	{
		$gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_ERROR2'));
	}
	unset($arr);
	
	$stack = array();
	$members = array();
	
	// alle Altersrollen einlesen
	$altersrollen = beitragsrollen_einlesen('alt', array('FIRST_NAME', 'LAST_NAME', 'BIRTHDAY'));
	
	// alle Altersrollen durchlaufen
	foreach ($altersrollen as $roleId => $roldata)
	{
		foreach($altersrollen[$roleId]['members'] as $member => $memberdata)
		{
			if(strlen($memberdata['BIRTHDAY']) === 0)
			{
				$gMessage->show('<strong>'.$gL10n->get('SYS_ERROR').':</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_MISSING_BIRTHDAY',
						'<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$member.'">'.$memberdata['FIRST_NAME'].'</a>',
						'<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$member.'">'.$memberdata['LAST_NAME'].'</a>' ));
			}
	
			$age = ageCalculator(strtotime($memberdata['BIRTHDAY']), strtotime($pPreferences->config['Altersrollen']['altersrollen_stichtag']));
	
			// ist das Alter des Mitglieds außerhalb des Altersschemas der Rolle
			if (($age < $roldata['von']) || ($age > $roldata['bis']))
			{
				// wenn ja, dann Mitglied auf den Stack legen 
				$stack[] = array('LAST_NAME' => $memberdata['LAST_NAME'],
						'FIRST_NAME' => $memberdata['FIRST_NAME'], 'user_id' => $member, 
						'alter' => $age, 'alterstyp' => $roldata['alterstyp']);
				
				$members[] = array('LAST_NAME' => $memberdata['LAST_NAME'],
								  'FIRST_NAME' => $memberdata['FIRST_NAME'], 
								     'user_id' => $member,
								     'role_id' => $roleId,
									    'role' => $roldata['rolle'], 
								         'age' => $age,
						                'toDo' => 'delete',
					     'icon_role_not_exist' => '&nbsp;',
				    	       'icon_role_new' => '&nbsp;',
					           'icon_role_old' => '<img src="'. THEME_URL . '/icons/delete.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE').'"  />' );
			}
		}
	}
	
	// wenn ein Mitglied Angehoeriger mehrerer Rollen war (duerfte eigentlich gar nicht vorkommen),
	// dann wurde er auch mehrfach in das Array $stack aufgenommen
	// --> doppelte Vorkommen loeschen
	$stack = array_map('unserialize', array_unique(array_map('serialize', $stack)));
	
	// den Stack abarbeiten
	foreach ($stack as $key => $stackdata)
	{
		// alle Altersrollen durchlaufen und pruefen, ob das Mitglied in das Altersschema der Rolle passt
		foreach ($altersrollen as $roleId => $roldata)
		{
			if (($stackdata['alter'] <= $roldata['bis'])
					&& ($stackdata['alter'] >= $roldata['von'])
					&& ($stackdata['alterstyp'] == $roldata['alterstyp'])
					&& !array_key_exists($stackdata['user_id'], $roldata['members']))
			{
				// das Mitglied passt in das Altersschema der Rolle und das Kennzeichen dieser Altersstaffelung passt auch
				$members[] = array('LAST_NAME' => $stackdata['LAST_NAME'],
						          'FIRST_NAME' => $stackdata['FIRST_NAME'],
						             'user_id' => $stackdata['user_id'],
						             'role_id' => $roleId,
						                'role' => $roldata['rolle'], 
						                 'age' => $stackdata['alter'],
						                'toDo' => 'set',
				         'icon_role_not_exist' => '&nbsp;',
						       'icon_role_new' => '<img src="'. THEME_URL . '/icons/add.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE').'"  />',
						       'icon_role_old' => '&nbsp;');
						
				unset($stack[$key]);
			}
		}
	}
	
	if (count($stack) > 0)
	{
		foreach ($stack as $stackdata)
		{
			$members[] = array('LAST_NAME' => $stackdata['LAST_NAME'],
					          'FIRST_NAME' => $stackdata['FIRST_NAME'],
					             'user_id' => $stackdata['user_id'],
					             'role_id' => '',
					                'role' => '',
					                 'age' => $stackdata['alter'],
					                'toDo' => '',
			         'icon_role_not_exist' => '<img src="'. THEME_URL .'/icons/warning.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING').'" />',
					       'icon_role_new' => '&nbsp;',
					       'icon_role_old' => '&nbsp;');
		}
	}
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/mitgliedsbeitrag.php?show_option=remapping', $gL10n->get('SYS_BACK'), 'back.png');
	
	$form = new HtmlForm('createmandateid_preview_form', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/remapping.php?mode=write', $page);
	
	if (sizeof($members) > 0)
	{
		// save members in session (for mode write and mode print)
		$_SESSION['pMembershipFee']['remapping_user'] = $members;
	
		$datatable = true;
		$hoverRows = true;
		$classTable  = 'table table-condensed';
		$table = new HtmlTable('table_new_remapping', $page, $hoverRows, $datatable, $classTable);
		$table->setColumnAlignByArray(array('left', 'left', 'center', 'center', 'center', 'center', 'left'));
		$columnValues = array($gL10n->get('SYS_LASTNAME'),
				$gL10n->get('SYS_FIRSTNAME'),
				'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/calendar.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_DESC').'" />',
				'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/delete.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE_DESC').'" />',
				'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/add.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_DESC').'" />',
				'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/warning.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING_DESC').'" />',
				$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'));
		$table->addRowHeadingByArray($columnValues);

		foreach ($members as $data)
		{
			$columnValues = array();
			$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$data['user_id'].'">'.$data['LAST_NAME'].'</a>';
			$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$data['user_id'].'">'.$data['FIRST_NAME'].'</a>';
			$columnValues[] = $data['age'];
			$columnValues[] = $data['icon_role_old'];
			$columnValues[] = $data['icon_role_new'];
			$columnValues[] = $data['icon_role_not_exist'];
			$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/roles/roles_new.php?rol_id='.$data['role_id'].'">'.$data['role'].'</a>';
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));

		$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => 'btn-primary'));
		$form->addDescription('<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_PREVIEW'));
	}
	else 
	{
		$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_NO_ASSIGN'));
		
		//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
		$form->addStaticControl('', '', '');
	}
	$page->addHtml($form->show(false));
}
elseif ($getMode == 'write')
{
	$tablemember = new TableMembers($gDb);
	$sql = '';
	
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'.ADMIDIO_URL. FOLDER_PLUGINS . $plugin_folder .'/remapping.php?mode=print", "_blank");
        });',
		true
	);
	
	$headerMenu = $page->getMenu();
	$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . $plugin_folder .'/mitgliedsbeitrag.php?show_option=remapping', $gL10n->get('SYS_BACK'), 'back.png');
	$headerMenu->addItem('menu_item_print_view', '#', $gL10n->get('LST_PRINT_PREVIEW'), 'print.png');
	
	$form = new HtmlForm('remapping_saved_form', null, $page);
	
	$datatable = true;
	$hoverRows = true;
	$classTable  = 'table table-condensed';
	$table = new HtmlTable('table_saved_remapping', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center', 'center', 'center', 'center', 'left'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'),
			$gL10n->get('SYS_FIRSTNAME'),
			'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/calendar.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_DESC').'" />',
			'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/delete.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE_DESC').'" />',
			'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/add.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_DESC').'" />',
			'<img class="admidio-icon-help" src="'. THEME_URL . '/icons/warning.png"
            			alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING_DESC').'" />',
			$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['remapping_user'] as $data)
	{
		$columnValues = array();
		$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$data['user_id'].'">'.$data['LAST_NAME'].'</a>';
		$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php?user_id='.$data['user_id'].'">'.$data['FIRST_NAME'].'</a>';
		$columnValues[] = $data['age'];
		$columnValues[] = $data['icon_role_old'];
		$columnValues[] = $data['icon_role_new'];
		$columnValues[] = $data['icon_role_not_exist'];
		$columnValues[] = '<a href="'.ADMIDIO_URL.FOLDER_MODULES.'/roles/roles_new.php?rol_id='.$data['role_id'].'">'.$data['role'].'</a>';
		$table->addRowByArray($columnValues);
		
		if ($data['toDo'] == 'delete')
		{
			$sql = 'UPDATE '.TBL_MEMBERS.'
			 		   SET mem_end = \''.date('Y-m-d', strtotime('-1 day')).'\'
			 	     WHERE mem_usr_id = '.$data['user_id'].'
				       AND mem_rol_id = '.$data['role_id'];
			$gDb->query($sql);
			
			// stopMembership() kann nicht verwendet werden, da es unter best. Umstaenden Mitgliedschaften nicht loescht
			// Beschreibung von stopMembership()
			//      only stop membership if there is an actual membership
			//      the actual date must be after the beginning
			//      and the actual date must be before the end date
			//$tablemember->stopMembership( $roleId, $member);
		}
		elseif ($data['toDo'] == 'set')
		{
			// das Mitglied passt in das Altersschema der Rolle und das Kennzeichen dieser Altersstaffelung passt auch
			$tablemember->startMembership($data['role_id'], $data['user_id']);
		}
	}
	
	$page->addHtml($table->show(false));
	$form->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_SAVED').'</strong>');
	
	//seltsamerweise wird in diesem Abschnitt nichts angezeigt wenn diese Anweisung fehlt
	$form->addStaticControl('', '', '');
	
	$page->addHtml($form->show(false));
}
elseif ($getMode == 'print')
{
	// date must be formated
	$dateUnformat = DateTime::createFromFormat('Y-m-d', DATE_NOW);
	$date = $dateUnformat->format($gPreferences['system_date']);
	
	// create html page object without the custom theme files
	$hoverRows = false;
	$datatable = false;
	$classTable  = 'table table-condensed table-striped';
	$page->hideThemeHtml();
	$page->hideMenu();
	$page->setPrintMode();
	$page->setHeadline($gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_SUMMARY', $date));
	$table = new HtmlTable('table_print_remapping', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center', 'center', 'center', 'center', 'left'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'),
			$gL10n->get('SYS_FIRSTNAME'),
			'<img src="'. THEME_URL . '/icons/calendar.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE').'"  />',
			'<img src="'. THEME_URL . '/icons/delete.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE').'" />',
			'<img src="'. THEME_URL . '/icons/add.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE').'" />',
			'<img src="'. THEME_URL . '/icons/warning.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING').'"  />',
			$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['remapping_user'] as $data)
	{
		$columnValues = array();
		$columnValues[] = $data['LAST_NAME'];
		$columnValues[] = $data['FIRST_NAME'];
		$columnValues[] = $data['age'];
		$columnValues[] = $data['icon_role_old'];
		$columnValues[] = $data['icon_role_new'];
		$columnValues[] = $data['icon_role_not_exist'];
		$columnValues[] = $data['role'];
		$table->addRowByArray($columnValues);
	}
	$page->addHtml($table->show(false));
}

$page->show();


