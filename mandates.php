<?php
/**
 ***********************************************************************************************
 * Setzen eines Mandatsdatums fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   mandates.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste aller Benutzer mit Beiträgen
 *                    assign - Setzen eines Mandatsdatums
 * usr_id           : Id des Benutzers, für den das Mandatsdatum gesetzt/gelöscht wird
 * datum_neu		: Mandatsdatum
 * mem_show_choice	: 0 - (Default) Alle Benutzer anzeigen
 *                	  1 - Nur Benutzer anzeigen, bei denen ein Mandatsdatum vorhanden ist
 *                	  2	- Nur Benutzer anzeigen, bei denen kein Mandatsdatum vorhanden ist
 * full_screen    	: 0 - Normalbildschirm
 *           		  1 - Vollbildschirm
 * mandate_screen  	: 0 - zusätzliche Spalten mit Mandatsänderungen werden nicht angezeigt
 *           		  1 - zusätzliche Spalten mit Mandatsänderungen werden angezeigt
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

if(isset($_GET['mode']) && $_GET['mode'] == 'assign')
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode           	= admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getUserId         	= admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0, 'directOutput' => true));
$getDatumNeu 		= admFuncVariableIsValid($_GET, 'datum_neu', 'date');
$getMembersShow 	= admFuncVariableIsValid($_GET, 'mem_show_choice', 'numeric', array('defaultValue' => 0));
$getFullScreen  	= admFuncVariableIsValid($_GET, 'full_screen', 'numeric');
$getMandateScreen  	= admFuncVariableIsValid($_GET, 'mandate_screen', 'numeric');

if($getMode == 'assign')
{
	$ret_text = 'ERROR';

	$userArray = array();
	if($getUserId!=0)			// Mandatsdatum nur für einen einzigen User ändern
	{
		$userArray[0] = $getUserId;
	}
	else 						// Alle ändern wurde gewählt
	{
		$userArray = $_SESSION['userArray'];
	}

  try
   {
        foreach ($userArray as $dummy => $data)
		{
			$user = new User($gDb, $gProfileFields, $data);

			//zuerst mal sehen, ob bei diesem user bereits ein Mandatsdatum vorhanden ist
			if (strlen($user->getValue('MANDATEDATE'.$gCurrentOrganization->getValue('org_id'))) == 0)
			{
				//er hat noch kein Mandatsdatum, deshalb ein neues eintragen
				$user->setValue('MANDATEDATE'.$gCurrentOrganization->getValue('org_id'), $getDatumNeu);
			}
			else
			{
				//er hat bereits ein Mandatsdatum, deshalb das vorhandene löschen
				$user->setValue('MANDATEDATE'.$gCurrentOrganization->getValue('org_id'), '');
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

    // show html list

    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATES');

    // add current url to navigation stack if last url was not the same page
    if(strpos($gNavigation->getUrl(), 'mandates.php') === false)
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
	
		AND rol_cat_id = cat_id
		AND (  cat_org_id = '. $gCurrentOrganization->getValue('org_id'). '
			OR cat_org_id IS NULL ) ';

	if($getMembersShow == 1)                  // nur Benutzer anzeigen mit Bezahlt-Datum wurde gewählt
	{
		$memberCondition .= ' AND usd_usr_id = usr_id
			AND usd_usf_id = '. $gProfileFields->getProperty('MANDATEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
    		AND usd_value IS NOT NULL )';
	}
	else
	{
		$memberCondition .= ' AND usd_usr_id = usr_id
			AND usd_usf_id = '. $gProfileFields->getProperty('MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
			AND usd_value IS NOT NULL )';
	}

    $sql = 'SELECT DISTINCT usr_id, last_name.usd_value as last_name, first_name.usd_value as first_name, birthday.usd_value as birthday,
               city.usd_value as city, address.usd_value as address, zip_code.usd_value as zip_code, country.usd_value as country,
               mandatsdatum.usd_value as mandatsdatum,origmandatsreferenz.usd_value as origmandatsreferenz,
               origdebtoragent.usd_value as origdebtoragent,origiban.usd_value as origiban,mandatsreferenz.usd_value as mandatsreferenz
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
       	LEFT JOIN '. TBL_USER_DATA. ' as mandatsreferenz
          ON mandatsreferenz.usd_usr_id = usr_id
         AND mandatsreferenz.usd_usf_id = '. $gProfileFields->getProperty('MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
         LEFT JOIN '. TBL_USER_DATA. ' as origmandatsreferenz
          ON origmandatsreferenz.usd_usr_id = usr_id
         AND origmandatsreferenz.usd_usf_id = '. $gProfileFields->getProperty('ORIG_MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
 		LEFT JOIN '. TBL_USER_DATA. ' as origdebtoragent
          ON origdebtoragent.usd_usr_id = usr_id
         AND origdebtoragent.usd_usf_id = '. $gProfileFields->getProperty('ORIG_DEBTOR_AGENT', 'usf_id'). '
          LEFT JOIN '. TBL_USER_DATA. ' as origiban
          ON origiban.usd_usr_id = usr_id
         AND origiban.usd_usf_id = '. $gProfileFields->getProperty('ORIG_IBAN', 'usf_id'). '
         
         LEFT JOIN '. TBL_USER_DATA. ' as zip_code
          ON zip_code.usd_usr_id = usr_id
         AND zip_code.usd_usf_id = '. $gProfileFields->getProperty('POSTCODE', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as country
          ON country.usd_usr_id = usr_id
         AND country.usd_usf_id = '. $gProfileFields->getProperty('COUNTRY', 'usf_id'). '      
        LEFT JOIN '. TBL_MEMBERS. ' mem
          ON  mem.mem_begin  <= \''.DATE_NOW.'\'
         AND mem.mem_end     > \''.DATE_NOW.'\'
         AND mem.mem_usr_id  = usr_id
    
        WHERE '. $memberCondition. '
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
                	window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/mandates.php?full_screen='.$getFullScreen.'&mem_show_choice="+$(this).val());
                }
        });      
        
        // if checkbox in header is clicked then change all data
        $("input[type=checkbox].change_checkbox").click(function(){
            var datum = $("#datum").val();
           	$.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/mandates.php?mode=assign&full_screen='.$getFullScreen.'&datum_neu="+datum,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    var mem_show = $("#mem_show").val();
                    	window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/mandates.php?full_screen='.$getFullScreen.'&mem_show_choice="+mem_show);   
					}
                    else {
                    	alert(data);
                        return false;
                    }
                    return true;
                }
            );
        });             

        // checkbox "Mandatsänderungen anzeigen" wurde gewählt
        $("input[type=checkbox].mandatescreen_checkbox").click(function(){
        	if( $("input[type=checkbox]#mandate_screen").prop("checked")) {
            	var mandatescreen_checked=1;
            }
            else {
                var mandatescreen_checked=0;
            }   
         	window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/mandates.php?full_screen='.$getFullScreen.'&mandate_screen="+mandatescreen_checked);  
        });      
        

        // if checkbox of user is clicked then change data
        $("input[type=checkbox].memlist_checkbox").click(function(){
            var checkbox = $(this);
            var row_id = $(this).parent().parent().attr("id");
            var pos = row_id.search("_");
            var userid = row_id.substring(pos+1);
            var datum = $("#datum").val();  
           	var member_checked = $("input[type=checkbox]#member_"+userid).prop("checked");

            // change data in database
            $.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/mandates.php?full_screen='.$getFullScreen.'&datum_neu="+datum+"&mode=assign&usr_id="+userid,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    	if(member_checked){
                			$("input[type=checkbox]#member_"+userid).prop("checked", true);
               				$("#mandatedate_"+userid).text(datum);            				
            			}
            			else {
             				$("input[type=checkbox]#member_"+userid).prop("checked", false);
              				$("#mandatedate_"+userid).text("");
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
    $mandatesMenu = $page->getMenu();
    $mandatesMenu->addItem('menu_item_back', $g_root_path.'/adm_plugins/'.$plugin_folder.'/menue.php?show_option=mandates', $gL10n->get('SYS_BACK'), 'back.png');

    if($getFullScreen == true)
    {
    	$mandatesMenu->addItem('menu_item_normal_picture', $g_root_path. '/adm_plugins/'.$plugin_folder.'/mandates.php?mem_show_choice='.$getMembersShow.'&amp;full_screen=0',
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
    }
    else
    {
        $mandatesMenu->addItem('menu_item_full_screen', $g_root_path. '/adm_plugins/'.$plugin_folder.'/mandates.php?mem_show_choice='.$getMembersShow.'&amp;full_screen=1',
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
    }

    $navbarForm = new HtmlForm('navbar_show_all_users_form', '', $page, array('type' => 'navbar', 'setFocus' => false));

    $datumtemp = new DateTimeExtended(DATE_NOW, 'Y-m-d');
	$datum = $datumtemp->format($gPreferences['system_date']);

    $navbarForm->addInput('datum', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE'), $datum, array('type' => 'date', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_MANDATEDATE_DESC'));
	$selectBoxEntries = array('0' => $gL10n->get('MEM_SHOW_ALL_USERS'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_MANDATEDATE'), '2' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_MANDATEDATE'));
    $navbarForm->addSelectBox('mem_show', $gL10n->get('PLG_MITGLIEDSBEITRAG_FILTER'), $selectBoxEntries, array('defaultValue' => $getMembersShow, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));

    if($getFullScreen)
 	{
    	$navbarForm->addCheckbox('mandate_screen', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_SCREEN'), $getMandateScreen, array('class'=>'mandatescreen_checkbox', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_MANDATE_SCREEN_DESC'));
 	}
    $mandatesMenu->addForm($navbarForm->show(false));

    // create table object
    $table = new HtmlTable('tbl_assign_role_membership', $page, true, true, 'table table-condensed');
    $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES_FOUND');

    // create array with all column heading values
    $columnHeading = array(
        '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE_CHANGE_ALL_DESC').'"/>',
        $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID'),
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/edit.png"
            alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" />',
        $gL10n->get('SYS_LASTNAME'),
        $gL10n->get('SYS_FIRSTNAME'),
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/map.png"
            alt="'.$gL10n->get('SYS_ADDRESS').'" title="'.$gL10n->get('SYS_ADDRESS').'" />',
        $gL10n->get('SYS_ADDRESS'),
        $gL10n->get('SYS_BIRTHDAY'),
        $gL10n->get('SYS_BIRTHDAY'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT')
    );

    $table->setColumnAlignByArray(array('center', 'left', 'left', 'center', 'left', 'left', 'center', 'left', 'left', 'left', 'left', 'left', 'left'));
   	$table->setDatatablesOrderColumns(array(5, 6));
    $table->addRowHeadingByArray($columnHeading);
   	$table->disableDatatablesColumnsSort(array(1, 4));
    $table->setDatatablesAlternativOrderColumns(7, 8);
    $table->setDatatablesColumnsHide(8);
    $table->setDatatablesAlternativOrderColumns(9, 10);
    $table->setDatatablesColumnsHide(10);
    if($getFullScreen == false || ($getFullScreen && $getMandateScreen==false))
 	{
 		 $table->setDatatablesColumnsHide(array(11, 12, 13));
 	}
    // show rows with all organization users
    while($user = $statement->fetch())
    {
    	if(($getMembersShow == 2) && (strlen($user['mandatsreferenz'])>0) && (strlen($user['mandatsdatum'])>0))
		{
			continue;
		}

        $addressText  = ' ';
        $htmlAddress  = '&nbsp;';
        $htmlBirthday = '&nbsp;';
        $htmlMandateID = '&nbsp;';
        $htmlOrigMandateID = '&nbsp;';
    	$htmlOrigIBAN = '&nbsp;';
    	$htmlOrigDebtorAgent = '&nbsp;';

        //1. Spalte ($htmlMandatStatus)+ 2. Spalte ($htmlMandatDate)
    	if(strlen($user['mandatsdatum']) > 0)
        {
            $htmlMandatStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" checked="checked" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
            $mandatDate = new DateTimeExtended($user['mandatsdatum'], 'Y-m-d');
            $htmlMandatDate = '<div class="mandatedate_'.$user['usr_id'].'" id="mandatedate_'.$user['usr_id'].'">'.$mandatDate->format($gPreferences['system_date']).'</div>';
        }
        else
        {
            $htmlMandatStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
 			$htmlMandatDate = '<div class="mandatedate_'.$user['usr_id'].'" id="mandatedate_'.$user['usr_id'].'">&nbsp;</div>';
        }

        //3. Spalte ($htmlBeitrag)
  		if(strlen($user['mandatsreferenz']) > 0)
       	{
            $htmlMandateID = $user['mandatsreferenz'];
      	}

       	//4. Spalte (Mandatsänderung)

        //5. Spalte (Nachname)

        //6. Spalte (Vorname)

        //7. Spalte ($htmlAddress)
    	// create string with user address
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
            $birthdayDateSort=$birthdayDate->format('Ymd');
        }

        //10. Spalte ($birthdayDateSort)

        //11. Spalte und weiter: Anzeige von Mandatsänderungen
    	if(strlen($user['origmandatsreferenz']) > 0)
       	{
            $htmlOrigMandateID = $user['origmandatsreferenz'];
      	}
    	if(strlen($user['origiban']) > 0)
       	{
            $htmlOrigIBAN = $user['origiban'];
      	}
    	if(strlen($user['origdebtoragent']) > 0)
       	{
            $htmlOrigDebtorAgent = $user['origdebtoragent'];
      	}

        // create array with all column values
        $columnValues = array(
            $htmlMandatStatus,
            $htmlMandatDate,
            $htmlMandateID,
			'<a class="admidio-icon-info" href="'.$g_root_path.'/adm_plugins/'.$plugin_folder.'/mandate_change.php?user_id='. $user['usr_id']. '"><img src="'. THEME_PATH. '/icons/edit.png"
					alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" /></a>',
            '<a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='.$user['usr_id'].'">'.$user['last_name'].'</a>',
            '<a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='.$user['usr_id'].'">'.$user['first_name'].'</a>',
            $htmlAddress,
            $addressText,
            $htmlBirthday,
            $birthdayDateSort,
            $htmlOrigMandateID,
            $htmlOrigIBAN,
            $htmlOrigDebtorAgent
            );

        $table->addRowByArray($columnValues, 'userid_'.$user['usr_id']);
        $userArray[] = $user['usr_id'];

    }//End While

	$_SESSION['userArray'] = $userArray;

    $page->addHtml($table->show(false));
    $page->addHtml('<p>'.$gL10n->get('SYS_CHECKBOX_AUTOSAVE').'</p>');

    $page->show();
}
