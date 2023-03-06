<?php
/**
 ***********************************************************************************************
 * Setzen eines Bezahlt-Datums fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   payments.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html        - Standardmodus zun Anzeigen einer html-Liste aller Benutzer mit Beitraegen
 *                    assign_date - Setzen eines Bezahlt-Datums
 *                    delete_date - Löschen eines Bezahlt-Datums
 *                    prepare     - eine einzelne Checkbox oder "Alle" wurde angeklickt 
 * usr_id           : Id des Benutzers, fuer den das Bezahlt-Datum gesetzt wird
 * datum_neu        : das neue Bezahlt-Datum
 * mem_show_choice  : 0 - (Default) Alle Benutzer anzeigen
 *                    1 - Nur Benutzer anzeigen, bei denen ein Bezahlt-Datum vorhanden ist
 *                    2 - Nur Benutzer anzeigen, bei denen kein Bezahlt-Datum vorhanden ist
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
$userField = new TableUserField($gDb);

if(isset($_GET['mode']) && ($_GET['mode'] == 'assign_date' || $_GET['mode'] == 'delete_date' || $_GET['mode'] == 'prepare'))
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode        = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign_date', 'delete_date', 'prepare')));
$getUserId      = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0, 'directOutput' => true));
$getDatumNeu    = admFuncVariableIsValid($_GET, 'datum_neu', 'date');
$getChecked     = admFuncVariableIsValid($_GET, 'checked', 'string');
$getMembersShow = admFuncVariableIsValid($_GET, 'mem_show_choice', 'numeric', array('defaultValue' => 0));

// write role selection in session
if (strpos($gNavigation->getUrl(), 'membership_fee.php') !== false)
{
    $_SESSION['pMembershipFee']['checkedArray'] = array();
    $_SESSION['pMembershipFee']['selectAll'] = true;
    
	if (isset($_POST['payments_roleselection']) )
	{
		$_SESSION['pMembershipFee']['payments_rol_sel'] = $_POST['payments_roleselection'];
	}
	else
	{
		unset($_SESSION['pMembershipFee']['payments_rol_sel']);
	}
}

if ($getMode == 'assign_date')
{
    $ret_text = 'ERROR';
    
    if (count($_SESSION['pMembershipFee']['checkedArray']) === 0)
    {
        $ret_text = 'nothing_selected';
    }
    else
    {
        try
        {
            $workArray = $_SESSION['pMembershipFee']['checkedArray'];

            foreach ($workArray as $userId => $dummy)
            {
                $user->readDataById($userId);
            
                //zuerst mal sehen, ob bei diesem user bereits ein BEZAHLT-Datum vorhanden ist
                if (strlen($user->getValue('PAID'.$gCurrentOrgId)) === 0)
                {
                    //da kein BEZAHLT-Datum vorhanden ist, ein neues eintragen
                    $user->setValue('PAID'.$gCurrentOrgId, $getDatumNeu);
                
                    // wenn Lastschrifttyp noch nicht gesetzt ist: als Folgelastschrift kennzeichnen
                    // BEZAHLT bedeutet, es hat bereits eine Zahlung stattgefunden
                    // die naechste Zahlung kann nur eine Folgelastschrift sein
                    // Lastschrifttyp darf aber nur geaendert werden, wenn der Einzug per SEPA stattfand, also ein Faelligkeitsdatum vorhanden ist
                    if (strlen($user->getValue('SEQUENCETYPE'.$gCurrentOrgId)) === 0  && strlen($user->getValue('DUEDATE'.$gCurrentOrgId)) !== 0)
                    {
                        $user->setValue('SEQUENCETYPE'.$gCurrentOrgId, 'RCUR');
                    }
                
                    //falls Daten von einer Mandatsaenderung vorhanden sind, diese loeschen
                    if (strlen($user->getValue('ORIG_MANDATEID'.$gCurrentOrgId)) !== 0)
                    {
                        $user->setValue('ORIG_MANDATEID'.$gCurrentOrgId, '');
                    }
                    if (strlen($user->getValue('ORIG_IBAN')) !== 0)
                    {
                        $user->setValue('ORIG_IBAN', '');
                    }
                    if (strlen($user->getValue('ORIG_DEBTOR_AGENT')) !== 0)
                    {
                        $user->setValue('ORIG_DEBTOR_AGENT', '');
                    }
                
                    //das Faelligkeitsdatum loeschen (wird nicht mehr gebraucht, da ja bezahlt)
                    if (strlen($user->getValue('DUEDATE'.$gCurrentOrgId)) !== 0)
                    {
                        $user->setValue('DUEDATE'.$gCurrentOrgId, '');
                    }
                    $user->save();
                }
                unset($_SESSION['pMembershipFee']['checkedArray'][$userId]);
            }
            $_SESSION['pMembershipFee']['selectAll'] = true;
            $ret_text = 'success';
        }
        catch(AdmException $e)
        {
            $e->showText();
        }
    }
    echo $ret_text;
}
elseif ($getMode == 'delete_date')
{
    $ret_text = 'ERROR';
    
    if (count($_SESSION['pMembershipFee']['checkedArray']) === 0)
    {
        $ret_text = 'nothing_selected';
    }
    else
    {
        try
        {
            $workArray = $_SESSION['pMembershipFee']['checkedArray'];

            foreach ($workArray as $userId => $dummy)
            {
                $user->readDataById($userId);
            
                if (strlen($user->getValue('PAID'.$gCurrentOrgId)) > 0)
                {
                    //er hat bereits ein BEZAHLT-Datum, deshalb das vorhandene loeschen
                    $user->setValue('PAID'.$gCurrentOrgId, '');
                    $user->save();
                }
                unset($_SESSION['pMembershipFee']['checkedArray'][$userId]);
            }
            $_SESSION['pMembershipFee']['selectAll'] = true;
            $ret_text = 'success';
        }
        catch(AdmException $e)
        {
            $e->showText();
        }
    }
    echo $ret_text;
}
else
{
    $membersList = array();
  
    if (isset($_SESSION['pMembershipFee']['payments_rol_sel']) )
    {
    	// Rollenwahl ist vorhanden, deshalb Daten aufbereiten fuer list_members
    	$membersListRols = array();
    	$role = new TableRoles($gDb);
    	foreach ($_SESSION['pMembershipFee']['payments_rol_sel']as $rol_id)
    	{
    		$role->readDataById($rol_id);
    		$membersListRols[$role->getValue('rol_name')] = 0;
    	}
    }
    else
    {
    	$membersListRols = 0;
    }
    
    $membersListFields = array_filter($pPreferences->config['columnconfig']['payments_fields']);            //array_filter: löschen leerer Einträge, falls das Setup fehlgeschlagen ist 

    $membersListSqlCondition = 'AND mem_usr_id IN (SELECT DISTINCT usr_id
        FROM '. TBL_USERS. '
        LEFT JOIN '. TBL_USER_DATA. ' AS paid
          ON paid.usd_usr_id = usr_id
         AND paid.usd_usf_id = '. $gProfileFields->getProperty('PAID'.$gCurrentOrgId, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS fee
          ON fee.usd_usr_id = usr_id
         AND fee.usd_usf_id = '. $gProfileFields->getProperty('FEE'.$gCurrentOrgId, 'usf_id'). '
         		
        LEFT JOIN '. TBL_MEMBERS. ' AS mem
          ON mem.mem_begin  <= \''.DATE_NOW.'\'
         AND mem.mem_end     > \''.DATE_NOW.'\'
         AND mem.mem_usr_id  = usr_id
         		
       WHERE fee.usd_value IS NOT NULL ';
    
    if ($getMembersShow == 1)                   // Nur Benutzer anzeigen, bei denen ein Bezahlt-Datum vorhanden ist
    {
    	$membersListSqlCondition .= ' AND paid.usd_value IS NOT NULL ) ';
    }
    elseif ($getMembersShow == 2)				// Nur Benutzer anzeigen, bei denen kein Bezahlt-Datum vorhanden ist
    {
    	$membersListSqlCondition .= ' AND paid.usd_value IS NULL ) ';
    }
    else 										// Alle Benutzer anzeigen
    {
    	$membersListSqlCondition .= ' ) ';
    }
    
    $membersList = list_members($membersListFields, $membersListRols, $membersListSqlCondition);

    if ($getMode == 'prepare')
    {
        $ret_text = 'ERROR';
        if($getUserId != 0)           // ein einzelner User wurde selektiert
        {
            if ($getChecked == 'false')            // der Haken wurde geloescht
            {
                unset($_SESSION['pMembershipFee']['checkedArray'][$getUserId]);
            }
            elseif ($getChecked == 'true')        // der Haken wurde gesetzt
            {
                $_SESSION['pMembershipFee']['checkedArray'][$getUserId] = 'checked';
            }
            $ret_text = 'success';
        }
        else                        // "Alle" wurde angeklickt
        {
            foreach ($membersList as $member => $memberData)
   	        {
            
                if ($_SESSION['pMembershipFee']['selectAll'])
                {
                    $_SESSION['pMembershipFee']['checkedArray'][$member] = 'checked';
                }
                else
                {
                    unset($_SESSION['pMembershipFee']['checkedArray'][$member]);
                }
            }
            $_SESSION['pMembershipFee']['selectAll'] = !$_SESSION['pMembershipFee']['selectAll'];
            $ret_text = 'success';
        }
        echo $ret_text;
    }
    else
    {
        // set headline of the script
        $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS');

        $gNavigation->addUrl(CURRENT_URL, $headline);

        $page = new HtmlPage('plg-mitgliedsbeitrag-payments', $headline);

        $page->addJavascript('

        function assign_date() {
            var datum = $("#datum").val();
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php', array('mode' => 'assign_date')) .'&datum_neu=" + datum,
                function(data){
                    // check if error occurs
                    if(data == "nothing_selected") {
                        alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOTHING_SELECTED').'");
                        return false;
                    }
                    else if(data == "success") {
                        var mem_show = $("#mem_show").val();
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php').'?mem_show_choice=" + mem_show);
                    }
                    else {
                        alert(data);
                        return false;
                    }
                    return true;
                }
            );
        }

        function delete_date() {
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php', array('mode' => 'delete_date')) .'",
                function(data){
                    // check if error occurs
                    if(data == "nothing_selected") {
                        alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOTHING_SELECTED').'");
                        return false;
                    }
                    else if(data == "success") {
                        var mem_show = $("#mem_show").val();
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php').'?mem_show_choice=" + mem_show);
                    }
                    else {
                        alert(data);
                        return false;
                    }
                    return true;
                }
            );
        }
        
        function select_all() {
            var mem_show = $("#mem_show").val();
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php', array('mode' => 'prepare')) .'&mem_show_choice=" + mem_show,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php').'?mem_show_choice=" + mem_show);
                    }
                    else {
                        alert(data);
                        return false;
                    }
                    return true;
                }
            );
        }
        ');

        $javascriptCode = '
        // Anzeige abhaengig vom gewaehlten Filter
        $("#mem_show").change(function () {
            if($(this).val().length > 0) {
                window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php').'?mem_show_choice="+$(this).val());
            }
        });
                                  
        // if checkbox of user is clicked then change data
        $("input[type=checkbox].memlist_checkbox").click(function(e){
            e.stopPropagation();
            var checkbox = $(this);
            var row_id = $(this).parent().parent().attr("id");
            var pos = row_id.search("_");
            var userid = row_id.substring(pos+1);
            var member_checked = $("input[type=checkbox]#member_"+userid).prop("checked");
      
            // change data in database
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/payments.php', array('mode' => 'prepare')) .'&checked=" + member_checked + "&usr_id=" + userid,
                function(data){
                    // check if error occurs
                    if(data !== "success") {
                        alert(data);
                        return false;
                    }
                    return true;
                }
            );
        });
        ';
 
        $page->addJavascript($javascriptCode, true);
    
        if (isset($_SESSION['pMembershipFee']['payments_rol_sel']))
        {
            $page->addHtml('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLLQUERY_ACTIV').'</strong>');
        }

        $form = new HtmlForm('payments_filter_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
    
        $selectBoxEntries = array('0' => $gL10n->get('ORG_SHOW_ALL_USERS'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_PAID'), '2' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_PAID'));
        $form->addSelectBox('mem_show', $gL10n->get('PLG_MITGLIEDSBEITRAG_FILTER'), $selectBoxEntries, array('defaultValue' => $getMembersShow, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));
 
        $datumtemp = \DateTime::createFromFormat('Y-m-d', DATE_NOW);
        $datum = $datumtemp->format($gSettingsManager->getString('system_date'));
        $form->addInput('datum', $gL10n->get('PLG_MITGLIEDSBEITRAG_PAID_ON'), $datum, array('type' => 'date', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_PAID_DESC'));

        $form->addButton('btn_save_date', $gL10n->get('PLG_MITGLIEDSBEITRAG_SAVE_DATE'), array('icon' => 'fa-check', 'link' => 'javascript:assign_date()', 'class' => 'btn-primary'));
        $form->addDescription('&nbsp');
        $form->addButton('btn_delete_date', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_DATE'), array('icon' => 'fa-trash-alt', 'link' => 'javascript:delete_date()', 'class' => 'btn-primary'));
 
        $page->addHtml($form->show(false));

        // create table object
        $table = new HtmlTable('tbl_payments', $page, true, true, 'table table-condensed');
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

        $columnAlign  = array('center');
        
        $columnValues = array();
        $columnValues[] = '<a class="icon-text-link" href="javascript:select_all()">'.$gL10n->get('SYS_ALL').'</a>';

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
            }  
            break;							// Abbruch nach dem ersten Mitglied, da nur die usfIds eines Mitglieds benoetigt werden um die headlines zu erzeugen
        }
   
        $table->setColumnAlignByArray($columnAlign);
        $table->addRowHeadingByArray($columnValues);
        $table->disableDatatablesColumnsSort(array(1));
    
        //user data
        foreach ($membersList as $member => $memberData)
        {
            $columnValues = array();
    
            if (array_key_exists($member, $_SESSION['pMembershipFee']['checkedArray']))
            {
                $content= '<input type="checkbox" id="member_'.$member.'" name="member_'.$member.'" checked="checked" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$member.'"></b>';
            }
            else
            {
                $content= '<input type="checkbox" id="member_'.$member.'" name="member_'.$member.'" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$member.'"></b>';
            }
            $columnValues[] = $content;
        
            $user->readDataById($member);
    	
    	    foreach ($memberData as $usfId => $content)
    	    {
                if (!is_int($usfId))
                {
                    continue;
                }

                //*****************************************************************/
                // in some cases the content must have a special output format
                //*****************************************************************/
                if ($usfId === (int) $gProfileFields->getProperty('COUNTRY', 'usf_id'))
                {
                    $content = $gL10n->getCountryByCode($content);
                }
    	
                $userField->readDataById($usfId);

                $htmlValue = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $user->getValue('usr_uuid'));

                if (($usfId === (int) $gProfileFields->getProperty('LAST_NAME', 'usf_id') || $usfId === (int) $gProfileFields->getProperty('FIRST_NAME', 'usf_id')))
                {
                    $columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))).'">'.$htmlValue.'</a>';
                }
                elseif ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'EMAIL' || $usfId === (int) $gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_id'))
                {
                    $columnValues[] = getEmailLink($content, $user->getValue('usr_uuid'), $userField->getValue('usf_uuid'));      // hier $content, nicht $htmlValue
                }
                else
                {
                    $columnValues[] = $htmlValue;
                }
            }
            $table->addRowByArray($columnValues, 'userid_'.$member, array('nobr' => 'true'));
        }  // end foreach user

        $page->addHtml($table->show(false));
        $page->show();
    }
}
