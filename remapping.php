<?php
/**
 ***********************************************************************************************
 * Neuzuordnung von Mitgliedern
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

$user = new User($gDb, $gProfileFields);
$role = new TableRoles($gDb);

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_AGE_STAGGERED_ROLES');

$gNavigation->addUrl(CURRENT_URL, $headline);

if ($getMode == 'preview')     //Default
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-remapping-preview', $headline);
    
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
                $user->readDataById($member);

				$gMessage->show('<strong>'.$gL10n->get('SYS_ERROR').':</strong> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_MISSING_BIRTHDAY',array(
						'<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$memberdata['FIRST_NAME'].'</a>',
						'<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$memberdata['LAST_NAME'].'</a>' )));
			}
	
			// das Alter des Mitglieds am Stichtag bestimmen
            $deadline = getDeadline($pPreferences->config['Altersrollen']['altersrollen_offset']);
            $age = ageCalculator(strtotime($memberdata['BIRTHDAY']), strtotime($deadline));
			
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
		                       'icon_role_old' => '<i class="fas fa-minus" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE').'"></i>'  );
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
					           'icon_role_new' => '<i class="fas fa-plus" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE').'"></i>',	     
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
        			 'icon_role_not_exist' => '<i class="fas fa-exclamation" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING').'"></i>',		    
                           'icon_role_new' => '&nbsp;',
					       'icon_role_old' => '&nbsp;');
		}
	}
			
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
            '<i class="fas fa-birthday-cake" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_DESC').'"></i>',
		    '<i class="fas fa-minus" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE_DESC').'"></i>',
		    '<i class="fas fa-plus" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_DESC').'"></i>',
		    '<i class="fas fa-exclamation" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING_DESC').'"></i>',
            $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'));
		$table->addRowHeadingByArray($columnValues);

		foreach ($members as $data)
		{
            $user->readDataById($data['user_id']);
            $role->readDataById($data['role_id']);

			$columnValues = array();
			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['LAST_NAME'].'</a>';
			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['FIRST_NAME'].'</a>';
			$columnValues[] = $data['age'];
			$columnValues[] = $data['icon_role_old'];
			$columnValues[] = $data['icon_role_new'];
			$columnValues[] = $data['icon_role_not_exist'];
			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/groups-roles/groups_roles_new.php', array('role_uuid' => $role->getValue('rol_uuid'))).'">'.$data['role'].'</a>';
			$table->addRowByArray($columnValues);
		}

		$page->addHtml($table->show(false));

        $form = new HtmlForm('createmandateid_preview_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/remapping.php', array('mode' => 'write')), $page);
		$form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'),  array('icon' => 'fa-check'));
		$form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_PREVIEW'));
		
		$page->addHtml($form->show(false));
	}
	else 
	{
		$page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_NO_ASSIGN').'<br/><br/>');
	}
}
elseif ($getMode == 'write')
{
    $page = new HtmlPage('plg-mitgliedsbeitrag-remapping-write', $headline);

 	$page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'fa-print');
        
	$tablemember = new TableMembers($gDb);
	$sql = '';
	
	$page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("'. SecurityUtils::encodeUrl(ADMIDIO_URL. FOLDER_PLUGINS . PLUGIN_FOLDER .'/remapping.php', array('mode' => 'print')). '", "_blank");
        });',
		true
	);

	$datatable = false;
	$hoverRows = true;
	$classTable  = 'table table-condensed';
    
	$table = new HtmlTable('table_saved_remapping', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center', 'center', 'center', 'center', 'left'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'),
		$gL10n->get('SYS_FIRSTNAME'),
	    '<i class="fas fa-birthday-cake" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_DESC').'"></i>',
	    '<i class="fas fa-minus" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE_DESC').'"></i>',
	    '<i class="fas fa-plus" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_DESC').'"></i>',
	    '<i class="fas fa-exclamation" data-toggle="tooltip" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING_DESC').'"></i>',
		$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_NAME'));
	$table->addRowHeadingByArray($columnValues);
	
	foreach ($_SESSION['pMembershipFee']['remapping_user'] as $data)
	{
        $user->readDataById($data['user_id']);
        $role->readDataById($data['role_id']);
		
        $columnValues = array();
		$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['LAST_NAME'].'</a>';
		$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$data['FIRST_NAME'].'</a>';
		$columnValues[] = $data['age'];
		$columnValues[] = $data['icon_role_old'];
		$columnValues[] = $data['icon_role_new'];
		$columnValues[] = $data['icon_role_not_exist'];
		$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/groups-roles/groups_roles_new.php', array('role_uuid' => $role->getValue('rol_uuid'))).'">'.$data['role'].'</a>';
		$table->addRowByArray($columnValues);
		
		if ($data['toDo'] == 'delete')
		{
            $value = date('Y-m-d', strtotime('-1 day')) ;
			$sql = 'UPDATE '.TBL_MEMBERS.'
			 		   SET mem_end = ? -- $value
			 	     WHERE mem_usr_id = ? -- $data[\'user_id\']
				       AND mem_rol_id = ? -- $data[\'role_id\'] ';
                       
            $queryParams = array(
                $value,
                $data['user_id'],
                $data['role_id']
            );
                      
			$gDb->queryPrepared($sql, $queryParams);
            
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

	$page->addHtml('<div style="width:100%; height: 500px; overflow:auto; border:20px;">');
	$page->addHtml($table->show(false));
	$page->addHtml('</div><br/>');
	$page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_SAVED').'</strong><br/><br/>');
}
elseif ($getMode == 'print')
{
	// date must be formated
	$dateUnformat = DateTime::createFromFormat('Y-m-d', DATE_NOW);
	$date = $dateUnformat->format($gSettingsManager->getString('system_date'));
	
	$hoverRows = false;
	$datatable = false;
	$classTable  = 'table table-condensed table-striped';
	
	$page = new HtmlPage('plg-mitgliedsbeitrag-remapping-print', $gL10n->get('PLG_MITGLIEDSBEITRAG_REMAPPING_SUMMARY', array($date)));
	$page->setPrintMode();
	
	$table = new HtmlTable('table_print_remapping', $page, $hoverRows, $datatable, $classTable);
	$table->setColumnAlignByArray(array('left', 'left', 'center', 'center', 'center', 'center', 'left'));
	$columnValues = array($gL10n->get('SYS_LASTNAME'),
		$gL10n->get('SYS_FIRSTNAME'),
	    '<i class="fas fa-birthday-cake" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE').'"></i>',
	    '<i class="fas fa-minus" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_OLD_ROLE').'"></i>',
	    '<i class="fas fa-plus" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE').'"></i>',
	    '<i class="fas fa-exclamation" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NEW_ROLE_MISSING').'"></i>',
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


