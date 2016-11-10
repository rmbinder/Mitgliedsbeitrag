<?php
/**
 ***********************************************************************************************
 * Setzen eines Fälligkeitsdatums fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   duedates.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste aller Benutzer mit Beiträgen
 *                    assign - Setzen eines Fälligkeitsdatum
 * usr_id           : Id des Benutzers, für den das Fälligkeitsdatum gesetzt/gelöscht wird
 * datum_neu		: das neue Fälligkeitsdatum
 * mem_show_choice	: 0 - (Default) Alle Benutzer anzeigen
 *                	  1 - Nur Benutzer anzeigen, bei denen ein Fälligkeitsdatum vorhanden ist
 *                	  2	- Nur Benutzer anzeigen, bei denen kein Fälligkeitsdatum vorhanden ist
 * full_screen    	: 0 - Normalbildschirm
 *           		  1 - Vollbildschirm
 * sequencetype    	: Sequenztyp, der gleichzeitig mit dem Fälligkeitsdatum gesetzt wird (FRST, RCUR, FNAL oder OOFF)
 ***********************************************************************************************
 */

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
	$gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

 //alle Beitragsrollen einlesen 
$rols = beitragsrollen_einlesen('',array('FIRST_NAME','LAST_NAME','IBAN','DEBTOR'));

//falls eine Rollenabfrage durchgeführt wurde, dann die Rollen, die nicht gewählt wurden, löschen
if ($pPreferences->config['SEPA']['duedate_rollenwahl'][0]<>' ')
{
	foreach ($rols as $rol => $roldata)
	{
		if (!in_array($rol,$pPreferences->config['SEPA']['duedate_rollenwahl']))
		{
			unset($rols[$rol]) ;	
		}
	}
}

//umwandeln von array nach string wg SQL-Statement
$rolesString = implode(',',array_keys($rols));   
    	
if(isset($_GET['mode']) && $_GET['mode'] == 'assign' )
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode        = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getUserId      = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0, 'directOutput' => true));
$getDatumNeu 	= admFuncVariableIsValid($_GET, 'datum_neu', 'date');
$getMembersShow = admFuncVariableIsValid($_GET, 'mem_show_choice', 'numeric', array('defaultValue' => 0));
$getFullScreen  = admFuncVariableIsValid($_GET, 'full_screen', 'numeric');
$getSequenceType= admFuncVariableIsValid($_GET, 'sequencetype', 'string');

if($getMode == 'assign')
{
	$ret_text = 'ERROR';
 
	$userArray = array();
	if($getUserId<>0)			// Fälligkeitsdatum nur für einen einzigen User ändern
	{
		$userArray[0] = $getUserId ;
	}
	else 						// Alle ändern wurde gewählt
	{
		$userArray = $_SESSION['userArray'] ;
	}

  	try
   	{
        foreach ($userArray as $dummy => $data )
		{
			$user = new User($gDb, $gProfileFields, $data);
			
			//zuerst mal sehen, ob bei diesem user bereits ein Fälligkeitsdatum vorhanden ist
			if ( strlen($user->getValue('DUEDATE'.$gCurrentOrganization->getValue('org_id'))) == 0  )
			{
				//er hat noch kein Fälligkeitsdatum, deshalb ein neues eintragen
				$user->setValue('DUEDATE'.$gCurrentOrganization->getValue('org_id'), $getDatumNeu);	

				if ($getSequenceType=='FRST')
				{
					$user->setValue('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'), '');	
				}
				elseif ($getSequenceType<>'')
				{
					$user->setValue('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'), $getSequenceType);	
				}
			}
			else 
			{
				//er hat bereits ein Fälligkeitsdatum, deshalb das vorhandene löschen
				$user->setValue('DUEDATE'.$gCurrentOrganization->getValue('org_id'), '');
			}
			
			$user->save();
			$ret_text = 'success';
		} 
   	}
    catch(AdmException $e)
    {
        $e->showText();
    }
    echo $ret_text;
}
else
{
	$userArray = array();
    
    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE');

    // add current url to navigation stack if last url was not the same page
    if(strpos($gNavigation->getUrl(), 'duedates.php') === false)
    {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }

    // create sql for all relevant users
    $memberCondition = '';

	// Filter zusammensetzen
	$memberCondition = ' EXISTS 
		(SELECT 1
		FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES.  ','. TBL_USER_DATA. '	
		WHERE mem_usr_id = usr_id
		AND mem_rol_id = rol_id
		AND mem_begin <= \''.DATE_NOW.'\'
		AND mem_end    > \''.DATE_NOW.'\'
		AND rol_valid  = 1
		AND rol_id IN ('.$rolesString.')
		AND rol_cat_id = cat_id
		AND (  cat_org_id = '. $gCurrentOrganization->getValue('org_id'). '
			OR cat_org_id IS NULL ) ';

	if($getMembersShow == 1)                  // nur Benutzer mit Fälligkeitsdatum anzeigen ("Mit Fälligkeitsdatum" wurde gewählt)
	{
		$memberCondition .= ' AND usd_usr_id = usr_id
			AND usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
    		AND usd_value IS NOT NULL )';
	}
	else 
	{
		$memberCondition .= ' AND usd_usr_id = usr_id
			AND usd_usf_id = '. $gProfileFields->getProperty('MANDATEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
			AND usd_value IS NOT NULL )';
	}

    $sql = 'SELECT DISTINCT usr_id, last_name.usd_value as last_name, first_name.usd_value as first_name, birthday.usd_value as birthday,
               city.usd_value as city, address.usd_value as address, zip_code.usd_value as zip_code, country.usd_value as country,
               iban.usd_value as iban,lastschrifttyp.usd_value as lastschrifttyp,
               mandatsdatum.usd_value as mandatsdatum, faelligkeitsdatum.usd_value as faelligkeitsdatum,beitrag.usd_value as beitrag
        FROM '. TBL_USERS. '
        LEFT JOIN '. TBL_USER_DATA. ' as last_name
          ON last_name.usd_usr_id = usr_id
         AND last_name.usd_usf_id = '. $gProfileFields->getProperty('LAST_NAME', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as first_name
          ON first_name.usd_usr_id = usr_id
         AND first_name.usd_usf_id = '. $gProfileFields->getProperty('FIRST_NAME', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as birthday
          ON birthday.usd_usr_id = usr_id
         AND birthday.usd_usf_id = '. $gProfileFields->getProperty('BIRTHDAY', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as city
          ON city.usd_usr_id = usr_id
         AND city.usd_usf_id = '. $gProfileFields->getProperty('CITY', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as address
          ON address.usd_usr_id = usr_id
         AND address.usd_usf_id = '. $gProfileFields->getProperty('ADDRESS', 'usf_id'). '
      	LEFT JOIN '. TBL_USER_DATA. ' as mandatsdatum
          ON mandatsdatum.usd_usr_id = usr_id
         AND mandatsdatum.usd_usf_id = '. $gProfileFields->getProperty('MANDATEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
       	LEFT JOIN '. TBL_USER_DATA. ' as faelligkeitsdatum
          ON faelligkeitsdatum.usd_usr_id = usr_id
         AND faelligkeitsdatum.usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
       	LEFT JOIN '. TBL_USER_DATA. ' as lastschrifttyp
          ON lastschrifttyp.usd_usr_id = usr_id
         AND lastschrifttyp.usd_usf_id = '. $gProfileFields->getProperty('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
         LEFT JOIN '. TBL_USER_DATA. ' as bezahlt
          ON bezahlt.usd_usr_id = usr_id
         AND bezahlt.usd_usf_id = '. $gProfileFields->getProperty('PAID'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
         LEFT JOIN '. TBL_USER_DATA. ' as beitrag
          ON beitrag.usd_usr_id = usr_id
         AND beitrag.usd_usf_id = '. $gProfileFields->getProperty('FEE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as zip_code
          ON zip_code.usd_usr_id = usr_id
         AND zip_code.usd_usf_id = '. $gProfileFields->getProperty('POSTCODE', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as iban
          ON iban.usd_usr_id = usr_id
         AND iban.usd_usf_id = '. $gProfileFields->getProperty('IBAN', 'usf_id'). '
         LEFT JOIN '. TBL_USER_DATA. ' as country
          ON country.usd_usr_id = usr_id
         AND country.usd_usf_id = '. $gProfileFields->getProperty('COUNTRY', 'usf_id'). '
       
        LEFT JOIN '. TBL_MEMBERS. ' mem
          ON  mem.mem_begin  <= \''.DATE_NOW.'\'
         AND mem.mem_end     > \''.DATE_NOW.'\'
         AND mem.mem_usr_id  = usr_id   
    
         WHERE  bezahlt.usd_value IS NULL 
         AND beitrag.usd_value IS NOT NULL 
         AND iban.usd_value IS NOT NULL
         AND '. $memberCondition. '
         ORDER BY last_name, first_name ';
    $statement = $gDb->query($sql);

    // create html page object
    $page = new HtmlPage($headline);
        
    if($getFullScreen == true)
    {
    	$page->hideThemeHtml();
    }

    $javascriptCode = ' 
        // Anzeige abhängig vom gewählten Filter
        $("#mem_show").change(function () {
        	if($(this).val().length > 0) {
            	window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/duedates.php?full_screen='.$getFullScreen.'&mem_show_choice="+$(this).val());
            }
        });    

        // if checkbox in header is clicked then change all data
        $("input[type=checkbox].change_checkbox").click(function(){
        	var datum = $("#datum").val();
        	var sequencetype = $("#lastschrifttyp").val(); 
            $.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/duedates.php?mode=assign&full_screen='.$getFullScreen.'&sequencetype="+sequencetype+"&datum_neu="+datum,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    var mem_show = $("#mem_show").val();
                    	window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/duedates.php?full_screen='.$getFullScreen.'&mem_show_choice="+mem_show);   
					}
                    else {
                    	alert(data);
                        return false;
                    }
                    return true;
                }
            );
        });                 

        // if checkbox of user is clicked then change data
        $("input[type=checkbox].memlist_checkbox").click(function(){
            var checkbox = $(this);
            var row_id = $(this).parent().parent().attr("id");
            var pos = row_id.search("_");
            var userid = row_id.substring(pos+1);
            var datum = $("#datum").val();  
           	var member_checked = $("input[type=checkbox]#member_"+userid).prop("checked");
			var sequencetype = $("#lastschrifttyp").val(); 
			
            // change data in database
            $.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/duedates.php?full_screen='.$getFullScreen.'&datum_neu="+datum+"&sequencetype="+sequencetype+"&mode=assign&usr_id="+userid,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    	if(member_checked){
                			$("input[type=checkbox]#member_"+userid).prop("checked", true);
               				$("#duedate_"+userid).text(datum);
        					
        					if(sequencetype=="FRST") {
        						$("#lastschrifttyp_"+userid).text("");
        	 				}
        	 				else if(sequencetype=="RCUR") {
        	 					$("#lastschrifttyp_"+userid).text("R");
        	 				}
        	   				else if(sequencetype=="FNAL") {
        	 					$("#lastschrifttyp_"+userid).text("F");
        	 				}
        	 				else if(sequencetype=="OOFF") {
        	 					$("#lastschrifttyp_"+userid).text("O");
        	 				}
            			}
            			else {
             				$("input[type=checkbox]#member_"+userid).prop("checked", false);
              				$("#duedate_"+userid).text("");
            			}
                    }
                    else {
                    	alert(data);
                        return false;
                    }
                    return true;
                }
            );
        });';

    $page->addJavascript($javascriptCode, true);

    // get module menu
    $duedatesMenu = $page->getMenu();
    $duedatesMenu->addItem('menu_item_back', $g_root_path.'/adm_plugins/'.$plugin_folder.'/menue.php?show_option=sepa', $gL10n->get('SYS_BACK'), 'back.png');

    if($getFullScreen == true)
    {
    	$duedatesMenu->addItem('menu_item_normal_picture', $g_root_path. '/adm_plugins/'.$plugin_folder.'/duedates.php?mem_show_choice='.$getMembersShow.'&amp;full_screen=0',  
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
    }
    else
    {
        $duedatesMenu->addItem('menu_item_full_screen', $g_root_path. '/adm_plugins/'.$plugin_folder.'/duedates.php?mem_show_choice='.$getMembersShow.'&amp;full_screen=1',   
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
    }   
    
    $navbarForm = new HtmlForm('navbar_show_all_users_form', '', $page, array('type' => 'navbar', 'setFocus' => false));

    $datumtemp = new DateTimeExtended(DATE_NOW, 'Y-m-d');
	$datum = $datumtemp->format($gPreferences['system_date']);
	
    $navbarForm->addInput('datum', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'),$datum ,array('type' => 'date','helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_DUEDATE_DESC'));
    $selectBoxEntries = array('RCUR' => $gL10n->get('PLG_MITGLIEDSBEITRAG_FOLLOW_DIRECT_DEBIT'),'FNAL' => $gL10n->get('PLG_MITGLIEDSBEITRAG_FINAL_DIRECT_DEBIT'),'OOFF' => $gL10n->get('PLG_MITGLIEDSBEITRAG_ONETIMES_DIRECT_DEBIT'),'FRST' => $gL10n->get('PLG_MITGLIEDSBEITRAG_FIRST_DIRECT_DEBIT') );
    $navbarForm->addSelectBox('lastschrifttyp', $gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE'), $selectBoxEntries, array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_SEQUENCETYPE_SELECT_DESC', 'showContextDependentFirstEntry' => false, 'firstEntry'=>$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_CHANGE')));
    $selectBoxEntries = array('0' => $gL10n->get('MEM_SHOW_ALL_USERS'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_DUEDATE'), '2' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_DUEDATE') );
    $navbarForm->addSelectBox('mem_show', $gL10n->get('PLG_MITGLIEDSBEITRAG_FILTER'), $selectBoxEntries, array('defaultValue' => $getMembersShow,'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));
    
    if ($pPreferences->config['SEPA']['duedate_rollenwahl'][0]<>' ')
	{
		$navbarForm->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_ROLLQUERY_ACTIV').'</strong>');
	}
    $duedatesMenu->addForm($navbarForm->show(false));

    // create table object
    $table = new HtmlTable('tbl_duedates', $page, true, true, 'table table-condensed');
    $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES_FOUND');

    // create array with all column heading values
    $columnHeading = array(
        '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_CHANGE_ALL_DESC').'"/>',
        $gL10n->get('PLG_MITGLIEDSBEITRAG_DUE_ON'),
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/comment.png"
            alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE_DESC').'" />',
        $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'),
        $gL10n->get('SYS_LASTNAME'),
        $gL10n->get('SYS_FIRSTNAME'),
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/map.png"
            alt="'.$gL10n->get('SYS_ADDRESS').'" title="'.$gL10n->get('SYS_ADDRESS').'" />',
        $gL10n->get('SYS_ADDRESS'),
        $gL10n->get('SYS_BIRTHDAY'),
        $gL10n->get('SYS_BIRTHDAY')
    );
        
    $table->setColumnAlignByArray(array('left', 'left','center', 'right', 'left', 'left', 'center', 'left', 'center', 'left'));
   	$table->setDatatablesOrderColumns(array(5, 6));
    $table->addRowHeadingByArray($columnHeading);
   	$table->disableDatatablesColumnsSort(array(1));
    $table->setDatatablesAlternativOrderColumns(7, 8);
    $table->setDatatablesAlternativOrderColumns(9, 10);
    $table->setDatatablesColumnsHide(array(8,10));

    // show rows with all organization users
    while($user = $statement->fetch())
    {
    	if(($getMembersShow == 2) && (strlen($user['faelligkeitsdatum'])>0) && (strlen($user['mandatsdatum'])>0) )
		{
			continue;
		}
		
        $addressText  = ' ';
        $htmlAddress  = '&nbsp;';
        $htmlBirthday = '&nbsp;';
        $htmlBeitrag  = '&nbsp;';
        $htmlDueDate  = '&nbsp;';
        $lastschrifttyp = '';
        
        //1. Spalte ($htmlDueDateStatus)+ 2. Spalte ($htmlDueDate)
    	if(strlen($user['faelligkeitsdatum']) > 0)
        {
            $htmlDueDateStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" checked="checked" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
            $DueDate = new DateTimeExtended($user['faelligkeitsdatum'], 'Y-m-d');
            $htmlDueDate = '<div class="duedate_'.$user['usr_id'].'" id="duedate_'.$user['usr_id'].'">'.$DueDate->format($gPreferences['system_date']).'</div>';
        }
        else
        {
            $htmlDueDateStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
 			$htmlDueDate = '<div class="duedate_'.$user['usr_id'].'" id="duedate_'.$user['usr_id'].'">&nbsp;</div>';
        }
        
    	//3. Spalte ($htmlLastschrifttyp)
    	switch($user['lastschrifttyp'])
        {
        	case 'RCUR':
        		$lastschrifttyp = 'R';
        		break;
        	case 'FNAL':
        		$lastschrifttyp = 'F';
        		break;
        	case 'OOFF':
        		$lastschrifttyp = 'O';
        		break;
        }

    	if(strlen($lastschrifttyp) > 0)
        {
            $htmlLastschrifttyp = '<div class="lastschrifttyp_'.$user['usr_id'].'" id="lastschrifttyp_'.$user['usr_id'].'">'.$lastschrifttyp.'</div>';
        }
        else
        {
 			$htmlLastschrifttyp = '<div class="lastschrifttyp_'.$user['usr_id'].'" id="lastschrifttyp_'.$user['usr_id'].'">&nbsp;</div>';
        }
        
        //4. Spalte ($htmlBeitrag)
    	if($user['beitrag'] > 0)
        {
            $htmlBeitrag = $user['beitrag'].' '.$gPreferences['system_currency'];
        }
        
        //5. Spalte (Nachname)
        
        //6. Spalte (Vorname)
        
        //7. Spalte ($htmlAddress)
        if(strlen($user['zip_code']) > 0 || strlen($user['city']) > 0)
        {
            $addressText .= $user['zip_code']. ' '. $user['city'];
        }
        if(strlen($user['address']) > 0)
        {
            $addressText .= ' - '. $user['address'];
        }
    	if(strlen($addressText) > 1)
        {
            $htmlAddress = '<img class="admidio-icon-info" src="'. THEME_PATH.'/icons/map.png" alt="'.$addressText.'" title="'.$addressText.'" />';
        }
        
        //8. Spalte ($addressText)
               
        //9. Spalte ($htmlBirthday)
        if(strlen($user['birthday']) > 0)
        {
            $birthdayDate = new DateTimeExtended($user['birthday'], 'Y-m-d');
            $htmlBirthday = $birthdayDate->format($gPreferences['system_date']);
            $birthdayDateSort=$birthdayDate->format("Ymd");
        }
        
        //10. Spalte ($birthdayDateSort)
        
        // create array with all column values
        $columnValues = array(
            $htmlDueDateStatus,
            $htmlDueDate,
            $htmlLastschrifttyp,
            $htmlBeitrag,
            '<a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='.$user['usr_id'].'">'.$user['last_name'].'</a>',
            '<a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='.$user['usr_id'].'">'.$user['first_name'].'</a>',
            $htmlAddress,
            $addressText,
            $htmlBirthday,
            $birthdayDateSort
        );
            
        $table->addRowByArray($columnValues, 'userid_'.$user['usr_id']);
        $userArray[] = $user['usr_id'];
  
    }//End While
    
	$_SESSION['userArray'] = $userArray;

    $page->addHtml($table->show(false));
    $page->addHtml('<p>'.$gL10n->get('SYS_CHECKBOX_AUTOSAVE').'</p>');

    $page->show();
}
