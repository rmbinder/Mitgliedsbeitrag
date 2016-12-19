<?php
/**
 ***********************************************************************************************
 * Setzen eines Bezahlt-Datums fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   payments.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste aller Benutzer mit Beiträgen
 *                    assign - Setzen eines Bezahlt-Datums
 * usr_id           : Id des Benutzers, für den das Bezahlt-Datum gesetzt/gelöscht wird
 * datum_neu		: das neue Bezahlt-Datum
 * mem_show_choice	: 0 - (Default) Alle Benutzer anzeigen
 *                	  1 - Nur Benutzer anzeigen, bei denen ein Bezahlt-Datum vorhanden ist
 *                	  2	- Nur Benutzer anzeigen, bei denen kein Bezahlt-Datum vorhanden ist
 * full_screen    	: 0 - Normalbildschirm
 *           		  1 - Vollbildschirm
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
$rols = beitragsrollen_einlesen('', array('FIRST_NAME', 'LAST_NAME', 'IBAN', 'DEBTOR'));

//falls eine Rollenabfrage durchgeführt wurde, die Rollen, die nicht gewählt wurden, löschen
if ($pPreferences->config['Beitrag']['zahlungen_rollenwahl'][0]!=' ')
{
	foreach ($rols as $rol => $roldata)
	{
		if (!in_array($rol, $pPreferences->config['Beitrag']['zahlungen_rollenwahl']))
		{
			unset($rols[$rol]);
		}
	}
}

//umwandeln von array nach string wg SQL-Statement
$rolesString = implode(',', array_keys($rols));

if(isset($_GET['mode']) && $_GET['mode'] == 'assign')
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

if($getMode == 'assign')
{
	$ret_text = 'ERROR';

	$userArray = array();
	if($getUserId!=0)			// Bezahlt-Datum nur für einen einzigen User ändern
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

			//zuerst mal sehen, ob bei diesem user bereits ein BEZAHLT-Datum vorhanden ist
			if (strlen($user->getValue('PAID'.$gCurrentOrganization->getValue('org_id'))) == 0)
			{
				//er hat noch kein BEZAHLT-Datum, deshalb ein neues eintragen
				$user->setValue('PAID'.$gCurrentOrganization->getValue('org_id'), $getDatumNeu);

				// wenn Lastschrifttyp noch nicht gesetzt ist: als Folgelastschrift kennzeichnen
				// BEZAHLT bedeutet, es hat bereits eine Zahlung stattgefunden
				// die nächste Zahlung kann nur eine Folgelastschrift sein
				// Lastschrifttyp darf aber nur geändert werden, wenn der Einzug per SEPA stattfand, also ein Fälligkeitsdatum vorhanden ist
				if (strlen($user->getValue('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'))) == 0  && strlen($user->getValue('DUEDATE'.$gCurrentOrganization->getValue('org_id'))) != 0)
				{
					$user->setValue('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'), 'RCUR');
				}

				//falls Daten von einer Mandatsänderung vorhanden sind, diese löschen
				if (strlen($user->getValue('ORIG_MANDATEID'.$gCurrentOrganization->getValue('org_id'))) != 0)
				{
					$user->setValue('ORIG_MANDATEID'.$gCurrentOrganization->getValue('org_id'), '');
				}
				if (strlen($user->getValue('ORIG_IBAN')) != 0)
				{
					$user->setValue('ORIG_IBAN', '');
				}
				if (strlen($user->getValue('ORIG_DEBTOR_AGENT')) != 0)
				{
					$user->setValue('ORIG_DEBTOR_AGENT', '');
				}

				//das Fälligkeitsdatum löschen (wird nicht mehr gebraucht, da ja bezahlt)
				if (strlen($user->getValue('DUEDATE'.$gCurrentOrganization->getValue('org_id'))) != 0)
				{
					$user->setValue('DUEDATE'.$gCurrentOrganization->getValue('org_id'), '');
				}
			}
			else
			{
				//er hat bereits ein BEZAHLT-Datum, deshalb das vorhandene löschen
				$user->setValue('PAID'.$gCurrentOrganization->getValue('org_id'), '');
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
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS');

    // add current url to navigation stack if last url was not the same page
    if(strpos($gNavigation->getUrl(), 'payments.php') === false)
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

	if($getMembersShow == 1)                   // nur Benutzer mit Bezahlt-Datum anzeigen ("Mit Bezahlt-Datum" wurde gewählt)
	{
		$memberCondition .= ' AND usd_usr_id = usr_id
			AND usd_usf_id = '. $gProfileFields->getProperty('PAID'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
    		AND usd_value IS NOT NULL )';
	}
	else
	{
		$memberCondition .= ' AND usd_usr_id = usr_id
			AND usd_usf_id = '. $gProfileFields->getProperty('FEE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
			AND usd_value IS NOT NULL )';
	}

 	$sql = 'SELECT DISTINCT usr_id, last_name.usd_value as last_name, first_name.usd_value as first_name, birthday.usd_value as birthday,
               city.usd_value as city, address.usd_value as address, zip_code.usd_value as zip_code, country.usd_value as country,
               bezahlt.usd_value as bezahlt,beitrag.usd_value as beitrag,duedate.usd_value as duedate,lastschrifttyp.usd_value as lastschrifttyp,
               debtor.usd_value as debtor, debtoraddress.usd_value as debtoraddress,
               debtorpostcode.usd_value as debtorpostcode, debtorcity.usd_value as debtorcity, debtoremail.usd_value as debtoremail,
               email.usd_value as email
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
       	LEFT JOIN '. TBL_USER_DATA. ' as bezahlt
          ON bezahlt.usd_usr_id = usr_id
         AND bezahlt.usd_usf_id = '. $gProfileFields->getProperty('PAID'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as beitrag
          ON beitrag.usd_usr_id = usr_id
         AND beitrag.usd_usf_id = '. $gProfileFields->getProperty('FEE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as lastschrifttyp
          ON lastschrifttyp.usd_usr_id = usr_id
         AND lastschrifttyp.usd_usf_id = '. $gProfileFields->getProperty('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
		LEFT JOIN '. TBL_USER_DATA. ' as duedate
          ON duedate.usd_usr_id = usr_id
         AND duedate.usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
         LEFT JOIN '. TBL_USER_DATA. ' as zip_code
          ON zip_code.usd_usr_id = usr_id
         AND zip_code.usd_usf_id = '. $gProfileFields->getProperty('POSTCODE', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as debtor
          ON debtor.usd_usr_id = usr_id
         AND debtor.usd_usf_id = '. $gProfileFields->getProperty('DEBTOR', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as debtoraddress
          ON debtoraddress.usd_usr_id = usr_id
         AND debtoraddress.usd_usf_id = '. $gProfileFields->getProperty('DEBTOR_ADDRESS', 'usf_id'). '
		LEFT JOIN '. TBL_USER_DATA. ' as debtoremail
          ON debtoremail.usd_usr_id = usr_id
         AND debtoremail.usd_usf_id = '. $gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_id'). '
 		LEFT JOIN '. TBL_USER_DATA. ' as email
          ON email.usd_usr_id = usr_id
         AND email.usd_usf_id = '. $gProfileFields->getProperty('EMAIL', 'usf_id'). '      
         LEFT JOIN '. TBL_USER_DATA. ' as debtorpostcode
          ON debtorpostcode.usd_usr_id = usr_id
         AND debtorpostcode.usd_usf_id = '. $gProfileFields->getProperty('DEBTOR_POSTCODE', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as debtorcity
          ON debtorcity.usd_usr_id = usr_id
         AND debtorcity.usd_usf_id = '. $gProfileFields->getProperty('DEBTOR_CITY', 'usf_id'). '
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
                window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/payments.php?full_screen='.$getFullScreen.'&mem_show_choice="+$(this).val());
            }
        });  
        
    	// if checkbox in header is clicked then change all data
        $("input[type=checkbox].change_checkbox").click(function(){
        	var datum = $("#datum").val();
           	$.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/payments.php?mode=assign&full_screen='.$getFullScreen.'&datum_neu="+datum,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    var mem_show = $("#mem_show").val();
                    	window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/payments.php?full_screen='.$getFullScreen.'&mem_show_choice="+mem_show);  
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
 
            // change data in database
            $.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/payments.php?full_screen='.$getFullScreen.'&datum_neu="+datum+"&mode=assign&usr_id="+userid,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    	if(member_checked){
                			$("input[type=checkbox]#member_"+userid).prop("checked", true);
               				$("#bezahlt_"+userid).text(datum);
               				
               				var lastschrifttyp 	= $("#lastschrifttyp_"+userid).text();  
               				lastschrifttyp 		= lastschrifttyp.trim();
               				var duedate 		= $("#duedate_"+userid).text(); 
               				duedate 			= duedate.trim();
               				$("#duedate_"+userid).text(""); 
               				if(lastschrifttyp.length == 0 && duedate.length != 0){
               					$("#lastschrifttyp_"+userid).text("R");
               				}           				
            			}
            			else {
             				$("input[type=checkbox]#member_"+userid).prop("checked", false);
              				$("#bezahlt_"+userid).text("");
            			}
                    }
                    else {
                    	alert(data);
                        return false;
                    }
                    return true;
                }
            );
        });
    ';

    $page->addJavascript($javascriptCode, true);

    // get module menu
    $paymentsMenu = $page->getMenu();
    $paymentsMenu->addItem('menu_item_back', $g_root_path.'/adm_plugins/'.$plugin_folder.'/menue.php?show_option=payments', $gL10n->get('SYS_BACK'), 'back.png');

    if($getFullScreen == true)
    {
    	$paymentsMenu->addItem('menu_item_normal_picture', $g_root_path. '/adm_plugins/'.$plugin_folder.'/payments.php?mem_show_choice='.$getMembersShow.'&amp;full_screen=0',
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
    }
    else
    {
        $paymentsMenu->addItem('menu_item_full_screen', $g_root_path. '/adm_plugins/'.$plugin_folder.'/payments.php?mem_show_choice='.$getMembersShow.'&amp;full_screen=1',
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
    }

    $navbarForm = new HtmlForm('navbar_show_all_users_form', '', $page, array('type' => 'navbar', 'setFocus' => false));

    $datumtemp = new DateTimeExtended(DATE_NOW, 'Y-m-d');
	$datum = $datumtemp->format($gPreferences['system_date']);

    $navbarForm->addInput('datum', $gL10n->get('PLG_MITGLIEDSBEITRAG_DATE_PAID'), $datum, array('type' => 'date', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_DATE_PAID_DESC'));
    $selectBoxEntries = array('0' => $gL10n->get('MEM_SHOW_ALL_USERS'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_PAID'), '2' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_PAID'));
    $navbarForm->addSelectBox('mem_show', $gL10n->get('PLG_MITGLIEDSBEITRAG_FILTER'), $selectBoxEntries, array('defaultValue' => $getMembersShow, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));
  	if ($pPreferences->config['Beitrag']['zahlungen_rollenwahl'][0]!=' ')
	{
		$navbarForm->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLLQUERY_ACTIV').'</strong>');
	}
    $paymentsMenu->addForm($navbarForm->show(false));

    // create table object
    $table = new HtmlTable('tbl_assign_role_membership', $page, true, true, 'table table-condensed');
    $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES_FOUND');

    // create array with all column heading values
    $columnHeading = array(
        '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DATE_PAID_CHANGE_ALL_DESC').'"/>',
        $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID_ON'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'),
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/comment.png"
            alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE_DESC').'" />',
        $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'),
        $gL10n->get('SYS_LASTNAME'),
        $gL10n->get('SYS_FIRSTNAME'),
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/map.png"
            alt="'.$gL10n->get('SYS_ADDRESS').'" title="'.$gL10n->get('SYS_ADDRESS').'" />',
        $gL10n->get('SYS_ADDRESS'),
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/info.png"
            alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEBTOR').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEBTOR').'" />',
        '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/email.png"
            alt="'.$gL10n->get('SYS_EMAIL').'" title="'.$gL10n->get('SYS_EMAIL').'" />',
        $gL10n->get('SYS_EMAIL'),
        $gL10n->get('SYS_BIRTHDAY'),
        $gL10n->get('SYS_BIRTHDAY')
    );

    $table->setColumnAlignByArray(array('left', 'left', 'left', 'center', 'right', 'left', 'left', 'center', 'left', 'center', 'center', 'left', 'left', 'left'));
   	$table->setDatatablesOrderColumns(array(6, 7));
    $table->addRowHeadingByArray($columnHeading);
   	$table->disableDatatablesColumnsSort(array(1, 8, 9, 10));
    $table->setDatatablesAlternativOrderColumns(8, 9);
    $table->setDatatablesColumnsHide(9);
    $table->setDatatablesAlternativOrderColumns(11, 12);
    $table->setDatatablesColumnsHide(12);
    $table->setDatatablesAlternativOrderColumns(13, 14);
    $table->setDatatablesColumnsHide(14);

    // show rows with all organization users
    while($user = $statement->fetch())
    {
    	if(($getMembersShow == 2) && (strlen($user['beitrag'])>0) && (strlen($user['bezahlt'])>0))
		{
			continue;
		}

        $addressText  = ' ';
        $htmlAddress  = '&nbsp;';
        $htmlBirthday = '&nbsp;';
        $htmlBeitrag = '&nbsp;';
        $email = '';
        $htmlMail = '&nbsp;';
        $debtor_text = ' ';
        $htmlDebtorText = '&nbsp;';
        $htmlDueDate  = '&nbsp;';
    	$lastschrifttyp = '';

        //1. Spalte ($htmlBezahltStatus)+ 2. Spalte ($htmlBezahltDate)
    	if(strlen($user['bezahlt']) > 0)
        {
            $htmlBezahltStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" checked="checked" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
            $bezahltDate = new DateTimeExtended($user['bezahlt'], 'Y-m-d');
            $htmlBezahltDate = '<div class="bezahlt_'.$user['usr_id'].'" id="bezahlt_'.$user['usr_id'].'">'.$bezahltDate->format($gPreferences['system_date']).'</div>';
        }
        else
        {
            $htmlBezahltStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
 			$htmlBezahltDate = '<div class="bezahlt_'.$user['usr_id'].'" id="bezahlt_'.$user['usr_id'].'">&nbsp;</div>';
        }

     	//3. Spalte ($htmlDuedate)
    	if(strlen($user['duedate']) > 0)
        {
        	$duedateTemp = new DateTimeExtended($user['duedate'], 'Y-m-d');
            $htmlDuedate = '<div class="duedate_'.$user['usr_id'].'" id="duedate_'.$user['usr_id'].'">'.$duedateTemp->format($gPreferences['system_date']).'</div>';
        }
        else
        {
 			$htmlDuedate = '<div class="duedate_'.$user['usr_id'].'" id="duedate_'.$user['usr_id'].'">&nbsp;</div>';
        }

        //4. Spalte ($htmlLastschrifttyp)
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

        //5. Spalte ($htmlBeitrag)
    	if($user['beitrag'] > 0)
        {
            $htmlBeitrag = $user['beitrag'].' '.$gPreferences['system_currency'];
        }

        //6. Spalte (Nachname)

        //7. Spalte (Vorname)

        //8. Spalte ($htmlAddress)
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
        //9. Spalte ($addressText)

        //10. Spalte ($htmlDebtorText)
        if(strlen($user['debtor']) > 0)
        {
            $debtor_text = $user['debtor'];
        }
        if(strlen($user['debtoraddress']) > 0)
        {
            $debtor_text = $debtor_text. ' - '. $user['debtoraddress'];
        }
        if(strlen($user['debtorpostcode']) > 0 || strlen($user['debtorcity']) > 0)
        {
            $debtor_text = $debtor_text. ' - '. $user['debtorpostcode']. ' '. $user['debtorcity'];
        }
     	if(strlen($debtor_text) > 1)
        {
            $htmlDebtorText = '<img class="admidio-icon-info" src="'. THEME_PATH.'/icons/info.png" alt="'.$debtor_text.'" title="'.$debtor_text.'" />';
        }

        //11. Spalte ($htmlMail)
        if(strlen($user['debtor']) > 0)
        {
			if(strlen($user['debtoremail']) > 0)
			{
				$email = $user['debtoremail'];
			}
        }
        else
        {
			if(strlen($user['email']) > 0)
			{
				$email = $user['email'];
			}
        }
    	if(strlen($email) > 0)
		{
			if($gPreferences['enable_mail_module'] != 1)
			{
				$mail_link = 'mailto:'. $email;
			}
			else
			{
				$mail_link = $g_root_path.'/adm_plugins/'.$plugin_folder.'/message_write.php?usr_id='. $user['usr_id'];
			}
			$htmlMail='<a class="admidio-icon-info" href="'.$mail_link.'"><img src="'. THEME_PATH. '/icons/email.png"
					alt="'.$gL10n->get('SYS_SEND_EMAIL_TO', $email).'" title="'.$gL10n->get('SYS_SEND_EMAIL_TO', $email).'" /></a>';
		}

        //12. Spalte ($email)

        //13. Spalte ($htmlBirthday)
        if(strlen($user['birthday']) > 0)
        {
            $birthdayDate = new DateTimeExtended($user['birthday'], 'Y-m-d');
            $htmlBirthday = $birthdayDate->format($gPreferences['system_date']);
            $birthdayDateSort=$birthdayDate->format('Ymd');
        }

        //14. Spalte ($birthdayDateSort)

        // create array with all column values
        $columnValues = array(
            $htmlBezahltStatus,
            $htmlBezahltDate,
            $htmlDuedate,
            $htmlLastschrifttyp,
            $htmlBeitrag,
            '<a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='.$user['usr_id'].'">'.$user['last_name'].'</a>',
            '<a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='.$user['usr_id'].'">'.$user['first_name'].'</a>',
            $htmlAddress,
            $addressText,
            $htmlDebtorText,
            $htmlMail,
            $email,
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
