<?php
/**
 ***********************************************************************************************
 * Modul Vorabinformation fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   pre_notification.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   	 - Standardmodus zun Anzeigen einer html-Liste
 *                    prepare 	 - user in einem CheckedArray setzen bzw löschen
 *                    csv_export - erzeugt eine csv-Datei
 *                    mail_export- nur zur Prüfung, ob user im CheckedArray markiert sind
 * usr_id           : <>0        - Id des Benutzers, für der im CheckedArray gesetzt/gelöscht wird
 *                    leer       - alle user im CheckedArray ändern von gesetzt->gelöscht bzw gelöscht->gesetzt
 * full_screen    	: 0 - Normalbildschirm
 *           		  1 - Vollbildschirm
 * checked		   : true  - Der Haken beim Benutzer wurde gesetzt
 * 				     false - Der Haken beim Benutzer wurde entfernt
 * duedate		   : Das übergebene Fälligkeitsdatum zur Filterung
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

if(isset($_GET['mode']) && ($_GET['mode'] == 'csv_export' || $_GET['mode'] == 'mail_export' || $_GET['mode'] == 'prepare') )
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode        = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'csv_export', 'mail_export', 'prepare')));
$getUserId      = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0, 'directOutput' => true));
$getFullScreen  = admFuncVariableIsValid($_GET, 'full_screen', 'numeric');
$getChecked		= admFuncVariableIsValid($_GET, 'checked', 'string');
$getDueDate     = admFuncVariableIsValid($_GET, 'duedate', 'string', array('defaultValue' => 0));

// Konfiguration einlesen
$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// add current url to navigation stack if last url was not the same page
if(strpos($gNavigation->getUrl(), 'pre_notification.php') === false)
{
	$_SESSION['checkedArray']= array();
}
    
if($getMode == 'csv_export')
{
	if (count($_SESSION['checkedArray'])<>0)
	{
		$export = '';
		$export = $gL10n->get('PLG_MITGLIEDSBEITRAG_SERIAL_NUMBER').";"
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER').";"		 	
			 	.$gL10n->get('SYS_FIRSTNAME').";"
			 	.$gL10n->get('SYS_LASTNAME').";"
			 	.$gL10n->get('SYS_ADDRESS').";"
			 	.$gL10n->get('SYS_POSTCODE').";"
			 	.$gL10n->get('SYS_CITY').";"	
			 	.$gL10n->get('SYS_EMAIL').";"	
			 	.$gL10n->get('SYS_PHONE').";"	
			 	.$gL10n->get('SYS_MOBILE').";"	
			 	.$gL10n->get('SYS_BIRTHDAY').";"				 
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESSION').";"
			 				 			 			 			 			 	 
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER')."/".$gL10n->get('PLG_MITGLIEDSBEITRAG_DEBTOR').";"
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADDRESS').";"		 
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_POSTCODE').";"
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_CITY').";"
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL').";"	

			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_BANK').";"
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_BIC').";"
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN').";"	
 	  		 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE').";"	
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID').";"
			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE').";"					 			 		 			 			 
 			 	.$gL10n->get('PLG_MITGLIEDSBEITRAG_FEE').";"
			 	."\n";
		 
		$nr = 1;

		foreach ($_SESSION['checkedArray'] as $UserId )
		{
			$user = new User($gDb, $gProfileFields, $UserId);	
	
			$export .= $nr.";";
			$export .= $user->getValue('MEMBERNUMBER').";";		
			$export .= $user->getValue('FIRST_NAME').";";
			$export .= $user->getValue('LAST_NAME').";";
			$export .= $user->getValue('ADDRESS').";";
			$export .= $user->getValue('POSTCODE').";";
			$export .= $user->getValue('CITY').";";
			$export .= $user->getValue('EMAIL').";";
			$export .= $user->getValue('PHONE').";";
			$export .= $user->getValue('MOBILE').";";
			$export .= $user->getValue('BIRTHDAY').";";				
			$export .= $user->getValue('BEITRITT'.$gCurrentOrganization->getValue('org_id')).";";		
		
			if (strlen($user->getValue('KONTOINHABER'))<>0)
			{
				$export .= $user->getValue('KONTOINHABER').";";
				$export .= $user->getValue('DEBTORADDRESS').";";
				$export .= $user->getValue('DEBTORPOSTCODE').";";
				$export .= $user->getValue('DEBTORCITY').";";
				$export .= $user->getValue('DEBTOREMAIL').";";							
			}
			else 
			{
				$export .= $user->getValue('FIRST_NAME')." ".$user->getValue('LAST_NAME').";";
				$export .= $user->getValue('ADDRESS').";";
				$export .= $user->getValue('POSTCODE').";";
				$export .= $user->getValue('CITY').";";	
				$export .= $user->getValue('EMAIL').";";					
			}

			$export .= $user->getValue('BANKNAME').";";	
			$export .= $user->getValue('BIC').";";	
			$export .= $user->getValue('IBAN').";";
			$export .= $user->getValue('MANDATEDATE'.$gCurrentOrganization->getValue('org_id')).";";
			$export .= $user->getValue('MANDATEID'.$gCurrentOrganization->getValue('org_id')).";";
			$export .= $user->getValue('DUEDATE'.$gCurrentOrganization->getValue('org_id')).";";		
			$export .= $user->getValue('BEITRAG'.$gCurrentOrganization->getValue('org_id')).";";
			$export .= "\n";
	
			$nr += 1;
		}
		echo $export;	
	}
	else 
	{
		echo 'marker_empty';
	}  
}
else if($getMode == 'mail_export')
{
	if (count($_SESSION['checkedArray'])==0)
	{
		echo 'marker_empty';
	} 
}
else
{
    // create sql for all relevant users
    $memberCondition = '';

	// Filter zusammensetzen
	$memberCondition = ' EXISTS 
		(SELECT 1
         FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ','. TBL_USER_DATA. '
         WHERE mem_usr_id = usr_id
            AND mem_rol_id = rol_id
            AND mem_begin <= \''.DATE_NOW.'\'
            AND mem_end    > \''.DATE_NOW.'\'
            AND usd_usr_id = usr_id
            AND usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '
         	
            AND rol_valid  = 1
            AND rol_cat_id = cat_id
            AND (  cat_org_id = '. $gCurrentOrganization->getValue('org_id'). '
                OR cat_org_id IS NULL ) ';	

	if($getDueDate <>0 )                  // nur Benutzer mit Fälligkeitsdatum anzeigen ("Mit Fälligkeitsdatum" wurde gewählt)
	{
		$memberCondition .= 'AND usd_value = \''.$getDueDate.'\'   )';
	}
	else                    
	{
		$memberCondition .= 'AND usd_value IS NOT NULL )';
	}

	$sql = 'SELECT DISTINCT usr_id, last_name.usd_value as last_name, first_name.usd_value as first_name, birthday.usd_value as birthday,
               city.usd_value as city, address.usd_value as address, zip_code.usd_value as zip_code, country.usd_value as country,
               faelligkeitsdatum.usd_value as faelligkeitsdatum,beitrag.usd_value as beitrag,lastschrifttyp.usd_value as lastschrifttyp,
               mandatsreferenz.usd_value as mandatsreferenz,debtor.usd_value as debtor, debtoraddress.usd_value as debtoraddress,
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
      	LEFT JOIN '. TBL_USER_DATA. ' as mandatsreferenz
          ON mandatsreferenz.usd_usr_id = usr_id
         AND mandatsreferenz.usd_usf_id = '. $gProfileFields->getProperty('MANDATEID'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). '     
       	LEFT JOIN '. TBL_USER_DATA. ' as faelligkeitsdatum
          ON faelligkeitsdatum.usd_usr_id = usr_id
         AND faelligkeitsdatum.usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
       	LEFT JOIN '. TBL_USER_DATA. ' as lastschrifttyp
          ON lastschrifttyp.usd_usr_id = usr_id
         AND lastschrifttyp.usd_usf_id = '. $gProfileFields->getProperty('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
        LEFT JOIN '. TBL_USER_DATA. ' as beitrag
          ON beitrag.usd_usr_id = usr_id
         AND beitrag.usd_usf_id = '. $gProfileFields->getProperty('BEITRAG'.$gCurrentOrganization->getValue('org_id'), 'usf_id'). ' 
        LEFT JOIN '. TBL_USER_DATA. ' as zip_code
          ON zip_code.usd_usr_id = usr_id
         AND zip_code.usd_usf_id = '. $gProfileFields->getProperty('POSTCODE', 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' as debtor
          ON debtor.usd_usr_id = usr_id
         AND debtor.usd_usf_id = '. $gProfileFields->getProperty('KONTOINHABER', 'usf_id'). '    
        LEFT JOIN '. TBL_USER_DATA. ' as debtoraddress
          ON debtoraddress.usd_usr_id = usr_id
         AND debtoraddress.usd_usf_id = '. $gProfileFields->getProperty('DEBTORADDRESS', 'usf_id'). '    
		LEFT JOIN '. TBL_USER_DATA. ' as debtoremail
          ON debtoremail.usd_usr_id = usr_id
         AND debtoremail.usd_usf_id = '. $gProfileFields->getProperty('DEBTOREMAIL', 'usf_id'). '    
 		LEFT JOIN '. TBL_USER_DATA. ' as email
          ON email.usd_usr_id = usr_id
         AND email.usd_usf_id = '. $gProfileFields->getProperty('EMAIL', 'usf_id'). '      
         LEFT JOIN '. TBL_USER_DATA. ' as debtorpostcode
          ON debtorpostcode.usd_usr_id = usr_id
         AND debtorpostcode.usd_usf_id = '. $gProfileFields->getProperty('DEBTORPOSTCODE', 'usf_id'). '    
        LEFT JOIN '. TBL_USER_DATA. ' as debtorcity
          ON debtorcity.usd_usr_id = usr_id
         AND debtorcity.usd_usf_id = '. $gProfileFields->getProperty('DEBTORCITY', 'usf_id'). '    
        LEFT JOIN '. TBL_USER_DATA. ' as country
          ON country.usd_usr_id = usr_id
         AND country.usd_usf_id = '. $gProfileFields->getProperty('COUNTRY', 'usf_id'). '
       
        LEFT JOIN '. TBL_MEMBERS. ' mem
          ON  mem.mem_begin  <= \''.DATE_NOW.'\'
         AND mem.mem_end     > \''.DATE_NOW.'\'
         AND mem.mem_usr_id  = usr_id   
         WHERE  '. $memberCondition. '
            ORDER BY last_name, first_name ';
    $statement = $gDb->query($sql);

    if($getMode == 'prepare')
	{
		$ret_text = 'ERROR';
		if($getUserId<>0)			// ein einzelner User wurde selektiert
		{
			if($getChecked=='false')			// der Haken wurde gelöscht
			{
		 		unset($_SESSION['checkedArray'][$getUserId]);
			 	$ret_text = 'success';
			}
			elseif ($getChecked=='true')		// der Haken wurde gesetzt
			{
				$_SESSION['checkedArray'][$getUserId]=$getUserId;
		 		$ret_text = 'success';
			 	
			}
		}
		else 						// Alle ändern wurde gewählt
		{
    		while($user = $statement->fetch())
    		{
    			if (in_array($user['usr_id'],$_SESSION['checkedArray']) ) 
    			{
    				unset($_SESSION['checkedArray'][$user['usr_id']]);
    			}
    			else 
    			{
    				$_SESSION['checkedArray'][$user['usr_id']]=$user['usr_id'];
    			}
 
    		}
    		$ret_text = 'success';
		}
		echo $ret_text;
    }
    else
	{
		// set headline of the script
    	$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION');

    	// add current url to navigation stack if last url was not the same page
    	if(strpos($gNavigation->getUrl(), 'pre_notification.php') === false)
    	{
        	$gNavigation->addUrl(CURRENT_URL, $headline);
    	}
    
    	// create html page object
    	$page = new HtmlPage($headline);
        
    	if($getFullScreen == true)
    	{
    		$page->hideThemeHtml();
    	}
    	
    	$page->addJavascript('
    		function prenotexport(){ 
				//var duedate = $("#duedate").val(); 
				$.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?mode=csv_export&full_screen='.$getFullScreen.'",
                	function(data){
                    	// check if error occurs
                   		if(data=="marker_empty") {
                   			alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT_EMPTY').'");
               				return false;
            			}
            			else {
 							//	var uriContent = "data:text/csv;charset=utf-8," + encodeURIComponent(data);
     						//	var myWindow = window.open(uriContent);
     						//	myWindow.focus(); 
     						var a = document.createElement("a");
     						a.href =  "data:text/csv;charset=utf-8," + encodeURIComponent(data);
     						a.target = "_blank";
     						a.download = "'.$pPreferences->config['SEPA']['vorabinformation_dateiname'].'.csv";
     						document.body.appendChild(a);
     						a.click();
            			}
                    	return true;
                	}
            	);
			};
			
			function massmail(){ 
			//var duedate = $("#duedate").val(); 
				$.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?mode=mail_export&full_screen='.$getFullScreen.'",
                	function(data){
                    	// check if error occurs
                   		if(data=="marker_empty") {
                   			alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_EMPTY').'");
               				return false;
            			}
            			else {
 							//alert("jetzt gehts zu mail");
 							window.location.href = "'.$g_root_path.'/adm_plugins/'.$plugin_folder.'/message_multiple_write.php" ; 
            			}
                    	return true;
                	}
            	);
			};	
		');            // !!!: ohne true

    	$javascriptCode = '    

    	// if checkbox in header is clicked then change all data
        $("input[type=checkbox].change_checkbox").click(function(){
            var duedate = $("#duedate").val(); 
            $.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?mode=prepare&full_screen='.$getFullScreen.'&duedate="+duedate,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    	window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?full_screen='.$getFullScreen.'&duedate="+duedate);  
					}
                    else {
                    	alert(data);
                        return false;
                    }
                    return true;
                }
            );
        }); 
    	
        $("#duedate").change(function () {
            if($(this).val().length > 0) {
                window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?full_screen='.$getFullScreen.'&duedate="+$(this).val());
            }
        });     

        // if checkbox of user is clicked then change data
        $("input[type=checkbox].memlist_checkbox").click(function(){
            var checkbox = $(this);
            var row_id = $(this).parent().parent().attr("id");
            var pos = row_id.search("_");
            var userid = row_id.substring(pos+1);
           	var member_checked = $("input[type=checkbox]#member_"+userid).prop("checked");
			var duedate = $("#duedate").val(); 

    		// change data in checkedArray
            $.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?mode=prepare&usr_id="+userid+"&full_screen='.$getFullScreen.'&checked="+member_checked+"&duedate="+duedate,
                function(data){
                    // check if error occurs
                   if(data != "success") {
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
        $preNotificationsMenu = $page->getMenu();
        $preNotificationsMenu->addItem('menu_item_back', $g_root_path.'/adm_plugins/'.$plugin_folder.'/menue.php?show_option=sepa', $gL10n->get('SYS_BACK'), 'back.png');

        if($getFullScreen == true)
        {
    	   $preNotificationsMenu->addItem('menu_item_normal_picture', $g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?full_screen=0',  
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
        }
        else
        {
            $preNotificationsMenu->addItem('menu_item_full_screen', $g_root_path. '/adm_plugins/'.$plugin_folder.'/pre_notification.php?full_screen=1',   
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
        }   
    
        $navbarForm = new HtmlForm('navbar_show_all_users_form', '', $page, array('type' => 'navbar', 'setFocus' => false));

        //alle Fälligkeitsdaten einlesen        
        $sql = 'SELECT DISTINCT usd_value
                FROM '.TBL_USER_DATA.','. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrganization->getValue('org_id'), 'usf_id').' 
                AND   mem_begin <= \''.DATE_NOW.'\'
                AND   mem_end >= \''.DATE_NOW.'\' 
                AND   usd_usr_id = mem_usr_id
                AND   mem_rol_id = rol_id
                AND   rol_valid = 1
                AND   rol_cat_id = cat_id
                AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                	OR cat_org_id IS NULL )  ';

        $duedateStatement = $gDb->query($sql);
        $selectBoxEntries = array('0'=> '- '.$gL10n->get('PLG_MITGLIEDSBEITRAG_SHOW_ALL').' -'); 
                                             
        while ($row = $duedateStatement->fetch())
        {
            $DueDate = new DateTimeExtended($row['usd_value'], 'Y-m-d');
            $selectBoxEntries[$row['usd_value']]=$DueDate->format($gPreferences['system_date']);
        }    
    
        $navbarForm->addSelectBox('duedate', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'), $selectBoxEntries, array('defaultValue' => $getDueDate,'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));
 	  	$navbarForm->addButton('btn_exportieren', $gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT'), array('icon' => THEME_PATH.'/icons/disk.png','link'=> 'javascript:prenotexport()', 'class' => 'btn-primary'));
 	  	$navbarForm->addButton('btn_mailen', $gL10n->get('SYS_EMAIL'), array('icon' => THEME_PATH.'/icons/email.png','link' => 'javascript:massmail()', 'class' => 'btn-primary'));
 	  	$preNotificationsMenu->addForm($navbarForm->show(false));

        // create table object
        $table = new HtmlTable('tbl_duedates', $page, true, true, 'table table-condensed');
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES_FOUND');

        // create array with all column heading values
        $columnHeading = array(
            '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CHANGE_ALL').'"/>',
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
            $gL10n->get('PLG_MITGLIEDSBEITRAG_DEBTOR'),
            '<img class="admidio-icon-help" src="'. THEME_PATH. '/icons/email.png"
                alt="'.$gL10n->get('SYS_EMAIL').'" title="'.$gL10n->get('SYS_EMAIL').'" />',
            $gL10n->get('SYS_EMAIL'),
            $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID')
        );
        
        $table->setColumnAlignByArray(array('left', 'left','center', 'right', 'left', 'left', 'center', 'left', 'center', 'left', 'center', 'left', 'left'));
   	    $table->setDatatablesOrderColumns(array(5, 6));
        $table->addRowHeadingByArray($columnHeading);
   	    $table->disableDatatablesColumnsSort(1);
        $table->setDatatablesAlternativOrderColumns(7, 8);
        $table->setDatatablesColumnsHide(8);
        $table->setDatatablesAlternativOrderColumns(9, 10);
        $table->setDatatablesColumnsHide(10);
        $table->setDatatablesAlternativOrderColumns(11, 12);
        $table->setDatatablesColumnsHide(12);

        // show rows with all organization users
        while($user = $statement->fetch())
        {
            $addressText  = ' ';
            $htmlAddress  = '&nbsp;';
            $htmlBirthday = '&nbsp;';
            $htmlBeitrag  = '&nbsp;';
            $htmlDueDate  = '&nbsp;';
            $email = '';
            $htmlMail = '&nbsp;';
            $debtor_text = ' ';
            $htmlDebtorText = '&nbsp;';
            $htmlMandateID = '&nbsp;';
            $htmlLastschrifttyp = '&nbsp;';
            $lastschrifttyp = '';

            //1. Spalte ($htmlDueDateStatus)
    	   if (in_array($user['usr_id'],$_SESSION['checkedArray']) ) 
            {
                $htmlDueDateStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" checked="checked" class="memlist_checkbox" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
            }
            else
            {
                $htmlDueDateStatus = '<input type="checkbox" id="member_'.$user['usr_id'].'" name="member_'.$user['usr_id'].'" class="memlist_checkbox" /><b id="loadindicator_member_'.$user['usr_id'].'"></b>';
            }
        
            //2. Spalte ($htmlDueDate)
    	   if($user['faelligkeitsdatum'] > 0)
            {
        	   $DueDate = new DateTimeExtended($user['faelligkeitsdatum'], 'Y-m-d');
                $htmlDueDate = $DueDate->format($gPreferences['system_date']);
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
        	   $htmlLastschrifttyp = $lastschrifttyp;
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
        
    	    if(strlen($user['mandatsreferenz']) > 0)
            {
                $htmlMandateID = $user['mandatsreferenz'];
            }
        
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
                $htmlDebtorText,
                $debtor_text,
                $htmlMail,
                $email,
                $htmlMandateID
            );
            
            $table->addRowByArray($columnValues, 'userid_'.$user['usr_id']);
        }//End While
    
        $page->addHtml($table->show(false));
        $page->show();
	}
}
