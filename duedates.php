<?php
/**
 ***********************************************************************************************
 * Setzen eines Faelligkeitsdatums fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   duedates.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste aller Benutzer mit Beitraegen
 *                    assign - Setzen eines Faelligkeitsdatum
 * usr_id           : Id des Benutzers, fuer den das Faelligkeitsdatum gesetzt/geloescht wird
 * datum_neu        : das neue Faelligkeitsdatum
 * mem_show_choice  : 0 - (Default) Alle Benutzer anzeigen
 *                    1 - Nur Benutzer anzeigen, bei denen ein Faelligkeitsdatum vorhanden ist
 *                    2 - Nur Benutzer anzeigen, bei denen kein Faelligkeitsdatum vorhanden ist
 * sequencetype     : Sequenztyp, der gleichzeitig mit dem Faelligkeitsdatum gesetzt wird (FRST, RCUR, FNAL oder OOFF)
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

$user = new User($gDb, $gProfileFields);

if(isset($_GET['mode']) && $_GET['mode'] == 'assign')
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode         = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getUserId       = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0, 'directOutput' => true));
$getDatumNeu     = admFuncVariableIsValid($_GET, 'datum_neu', 'date');
$getMembersShow  = admFuncVariableIsValid($_GET, 'mem_show_choice', 'numeric', array('defaultValue' => 0));
$getSequenceType = admFuncVariableIsValid($_GET, 'sequencetype', 'string');

// write role selection in session
if (strpos($gNavigation->getUrl(), 'membership_fee.php') !== false)
{
	if (isset($_POST['duedates_roleselection']) )
	{
		$_SESSION['pMembershipFee']['duedates_rol_sel'] = $_POST['duedates_roleselection'];
	}
	else 
	{
		unset($_SESSION['pMembershipFee']['duedates_rol_sel']);
	}
}

if ($getMode == 'assign')
{
    $ret_text = 'ERROR';

    $userArray = array();
    if ($getUserId != 0)           // Faelligkeitsdatum nur fuer einen einzigen User aendern
    {
        $userArray[0] = $getUserId;
    }
    else                        // Alle aendern wurde gewaehlt
    {
        $userArray = $_SESSION['pMembershipFee']['duedates_user'];
    }

    try
    {
        foreach ($userArray as $dummy => $data)
        {   
            $user->readDataById($data);

            //zuerst mal sehen, ob bei diesem user bereits ein Faelligkeitsdatum vorhanden ist
            if (strlen($user->getValue('DUEDATE'.$gCurrentOrgId)) === 0)
            {
                //er hat noch kein Faelligkeitsdatum, deshalb ein neues eintragen
                $user->setValue('DUEDATE'.$gCurrentOrgId, $getDatumNeu);

                if ($getSequenceType == 'FRST')
                {
                    $user->setValue('SEQUENCETYPE'.$gCurrentOrgId, '');
                }
                elseif ($getSequenceType != '')
                {
                    $user->setValue('SEQUENCETYPE'.$gCurrentOrgId, $getSequenceType);
                }
            }
            else
            {
                //er hat bereits ein Faelligkeitsdatum, deshalb das vorhandene loeschen
                $user->setValue('DUEDATE'.$gCurrentOrgId, '');
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
    $membersList = array();
    
    if (isset($_SESSION['pMembershipFee']['duedates_rol_sel']) )
    {
    	// Rollenwahl ist vorhanden, deshalb Daten aufbereiten fuer list_members
    	$membersListRols = array();
    	$role = new TableRoles($gDb);
    	foreach ($_SESSION['pMembershipFee']['duedates_rol_sel'] as $rol_id)
    	{
    		$role->readDataById($rol_id);
    		$membersListRols[$role->getValue('rol_name')] = 0;
    	}
    }
    else
    {
    	$membersListRols = 0;
    }
    
	$membersListFields = array_filter($pPreferences->config['columnconfig']['duedates_fields']);           //array_filter: l�schen leerer Eintr�ge, falls das Setup fehlgeschlagen ist 
   
    $membersListSqlCondition = 'AND mem_usr_id IN (SELECT DISTINCT usr_id
        FROM '. TBL_USERS. '
        LEFT JOIN '. TBL_USER_DATA. ' AS mandateid
          ON mandateid.usd_usr_id = usr_id
         AND mandateid.usd_usf_id = '. $gProfileFields->getProperty('MANDATEID'.$gCurrentOrgId, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS mandatedate
          ON mandatedate.usd_usr_id = usr_id
         AND mandatedate.usd_usf_id = '. $gProfileFields->getProperty('MANDATEDATE'.$gCurrentOrgId, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS duedate
          ON duedate.usd_usr_id = usr_id
         AND duedate.usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS paid
          ON paid.usd_usr_id = usr_id
         AND paid.usd_usf_id = '. $gProfileFields->getProperty('PAID'.$gCurrentOrgId, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS fee
          ON fee.usd_usr_id = usr_id
         AND fee.usd_usf_id = '. $gProfileFields->getProperty('FEE'.$gCurrentOrgId, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS iban
          ON iban.usd_usr_id = usr_id
         AND iban.usd_usf_id = '. $gProfileFields->getProperty('IBAN', 'usf_id'). '
         		
        LEFT JOIN '. TBL_MEMBERS. ' AS mem
          ON mem.mem_begin  <= \''.DATE_NOW.'\'
         AND mem.mem_end     > \''.DATE_NOW.'\'
         AND mem.mem_usr_id  = usr_id
         		
       WHERE paid.usd_value IS NULL
         AND fee.usd_value IS NOT NULL
         AND iban.usd_value IS NOT NULL
		 AND mandatedate.usd_value IS NOT NULL
		 AND mandateid.usd_value IS NOT NULL ';
    
    if ($getMembersShow == 1)                   // Nur Benutzer anzeigen, bei denen ein Faelligkeitsdatumvorhanden ist
    {
    	$membersListSqlCondition .= ' AND duedate.usd_value IS NOT NULL ) ';
    }
    elseif ($getMembersShow == 2)				// Nur Benutzer anzeigen, bei denen kein Faelligkeitsdatum vorhanden ist
    {
    	$membersListSqlCondition .= ' AND duedate.usd_value IS NULL ) ';
    }
    else 										// Alle Benutzer anzeigen
    {
    	$membersListSqlCondition .= ' ) ';
    }
    
    $membersList = list_members($membersListFields, $membersListRols, $membersListSqlCondition);
    
    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    $page = new HtmlPage('plg-mitgliedsbeitrag-duedates', $headline);
   
    $javascriptCode = '
        // Anzeige abhaengig vom gewaehlten Filter
        $("#mem_show").change(function () {
            if($(this).val().length > 0) {
                window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/duedates.php').'?mem_show_choice=" + $(this).val());
            }
        });

        // if checkbox in header is clicked then change all data
        $("input[type=checkbox].change_checkbox").click(function(){
            var datum = $("#datum").val();
            var sequencetype = $("#lastschrifttyp").val(); 
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/duedates.php', array('mode' => 'assign')) .'&sequencetype=" + sequencetype + "&datum_neu=" + datum,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                        var mem_show = $("#mem_show").val();
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/duedates.php').'?mem_show_choice=" + mem_show);
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
        $("input[type=checkbox].memlist_checkbox").click(function(e){
            e.stopPropagation();
            var checkbox = $(this);
            var row_id = $(this).parent().parent().attr("id");
            var pos = row_id.search("_");
            var userid = row_id.substring(pos+1);
            var datum = $("#datum").val();
            var member_checked = $("input[type=checkbox]#member_"+userid).prop("checked");
            var sequencetype = $("#lastschrifttyp").val();

            // change data in database
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/duedates.php', array('mode' => 'assign')) .'&datum_neu=" + datum + "&sequencetype=" + sequencetype + "&usr_id=" + userid,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                        if(member_checked){
                            $("input[type=checkbox]#member_"+userid).prop("checked", true);
                            $("#duedate_"+userid).text(datum);

                            if(sequencetype == "FRST") {
                                $("#lastschrifttyp_"+userid).text("");
                            }
                            else if(sequencetype == "RCUR") {
                                $("#lastschrifttyp_"+userid).text("RCUR");
                            }
                            else if(sequencetype == "FNAL") {
                                $("#lastschrifttyp_"+userid).text("FNAL");
                            }
                            else if(sequencetype == "OOFF") {
                                $("#lastschrifttyp_"+userid).text("OOFF");
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

    if (isset($_SESSION['pMembershipFee']['duedates_rol_sel']))
    {
        $page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_ROLLQUERY_ACTIV').'</strong>');
    }

    $form = new HtmlForm('duedates_filter_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
    
    $datumtemp =  \DateTime::createFromFormat('Y-m-d', DATE_NOW);
    $datum = $datumtemp->format($gSettingsManager->getString('system_date'));
    $form->addInput('datum', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'), $datum, array('type' => 'date', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_DUEDATE_DESC'));

    $selectBoxEntries = array('RCUR' => $gL10n->get('PLG_MITGLIEDSBEITRAG_FOLLOW_DIRECT_DEBIT'), 'FNAL' => $gL10n->get('PLG_MITGLIEDSBEITRAG_FINAL_DIRECT_DEBIT'), 'OOFF' => $gL10n->get('PLG_MITGLIEDSBEITRAG_ONETIMES_DIRECT_DEBIT'), 'FRST' => $gL10n->get('PLG_MITGLIEDSBEITRAG_FIRST_DIRECT_DEBIT'));
    $form->addSelectBox('lastschrifttyp', $gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE'), $selectBoxEntries, array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_SEQUENCETYPE_SELECT_DESC', 'showContextDependentFirstEntry' => false, 'firstEntry' => $gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_CHANGE')));

    $selectBoxEntries = array('0' => $gL10n->get('ORG_SHOW_ALL_USERS'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_DUEDATE'), '2' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_DUEDATE'));
    $form->addSelectBox('mem_show', $gL10n->get('PLG_MITGLIEDSBEITRAG_FILTER'), $selectBoxEntries, array('defaultValue' => $getMembersShow, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));

    $page->addHtml($form->show(false));

    // create table object
    $table = new HtmlTable('tbl_duedates', $page, true, true, 'table table-condensed');
    $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

    $columnAlign  = array('center');
    $columnValues = array( '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE_CHANGE_ALL_DESC').'"/>');
    
    // headlines for columns
    foreach ($membersList as $member => $memberData)
    {
    	foreach ($memberData as $usfId => $dummy)
    	{
    		if (!is_int($usfId))
    		{
    			continue;
    		}
    		
    		// Find name of the field
    		$columnHeader = $gProfileFields->getPropertyById($usfId, 'usf_name');
    		
    		if ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'CHECKBOX'
    				||  $gProfileFields->getPropertyById($usfId, 'usf_name_intern') === 'GENDER')
    		{
    			$columnAlign[] = 'center';
    		}
    		elseif ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'NUMBER'
    				||   $gProfileFields->getPropertyById($usfId, 'usf_type') === 'DECIMAL')
    		{
    			$columnAlign[] = 'right';
    		}
    		else
    		{
    			$columnAlign[] = 'left';
    		}
    		$columnValues[] = $columnHeader;
    	}  // End-Foreach
    	break;							// Abbruch nach dem ersten Mitglied, da nur die usfIds eines Mitglieds benoetigt werden um die headlines zu erzeugen
    }
    
    $table->setColumnAlignByArray($columnAlign);
    $table->addRowHeadingByArray($columnValues);
    $table->disableDatatablesColumnsSort(array(1));
    
    //user data
    foreach ($membersList as $member => $memberData)
    {
    	if (isset($memberData[$gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id')]) && strlen($memberData[$gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id')]) > 0)
    	{
    		$content= '<input type="checkbox" id="member_'.$member.'" name="member_'.$member.'" checked="checked" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$member.'"></b>';
    	}
    	else
    	{
    		$content= '<input type="checkbox" id="member_'.$member.'" name="member_'.$member.'" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$member.'"></b>';
    	}
    	
    	$columnValues = array($content);
    	
    	foreach ($memberData as $usfId => $content)
    	{
    		if (!is_int($usfId))
    		{
    			continue;
    		}
    		
    		/*****************************************************************/
    		// in some cases the content must have a special output format
    		/*****************************************************************/
    		if ($usfId === (int) $gProfileFields->getProperty('COUNTRY', 'usf_id'))
    		{
    		    $content = $gL10n->getCountryName($content);
    		}

    		$htmlValue = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $user->getValue('usr_uuid'));
    		$user->readDataById($member);
    		
    		if (($usfId === (int) $gProfileFields->getProperty('LAST_NAME', 'usf_id') || $usfId === (int) $gProfileFields->getProperty('FIRST_NAME', 'usf_id')))
    		{
    			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$htmlValue.'</a>';
    		}
    		elseif ($usfId == $gProfileFields->getProperty('SEQUENCETYPE'.$gCurrentOrgId, 'usf_id'))
    		{
    		    $columnValues[] = '<div class="lastschrifttyp_'.$member.'" id="lastschrifttyp_'.$member.'">'.$htmlValue.'</div>';
    		}
    		elseif ($usfId == $gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id'))
    		{
    		    $columnValues[] = '<div class="duedate_'.$member.'" id="duedate_'.$member.'">'.$htmlValue.'</div>';
    		}
    		else
    		{
    		    $columnValues[] = $htmlValue;
    		}
    	}
    	
    	$table->addRowByArray($columnValues, 'userid_'.$member, array('nobr' => 'true'));
    	
    	$userArray[] = $member;
    	
    }  // End-foreach User
    
    $_SESSION['pMembershipFee']['duedates_user'] = $userArray;

    $page->addHtml($table->show(false));
    $page->addHtml('<p>'.$gL10n->get('SYS_CHECKBOX_AUTOSAVE').'</p>');

    $page->show();
}
