<?php
/**
 ***********************************************************************************************
 * Modul Rechnung für das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode             : html       - Standardmodus zun Anzeigen einer html-Liste
 *                    prepare    - user in einem CheckedArray setzen bzw loeschen
 *                    export     - erzeugt eine Exportdatei
 *                    mail       - nur zur Pruefung, ob user im CheckedArray markiert sind
 * usr_id           : <>0        - Id des Benutzers, fuer der im CheckedArray gesetzt/geloescht wird
 *                    leer       - alle user im CheckedArray aendern von gesetzt->geloescht bzw geloescht->gesetzt
 * checked          : true  - Der Haken beim Benutzer wurde gesetzt
 *                    false - Der Haken beim Benutzer wurde entfernt
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

if(isset($_GET['mode']) && ($_GET['mode'] == 'export' || $_GET['mode'] == 'mail' || $_GET['mode'] == 'prepare'))
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode    = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'export', 'mail', 'prepare')));
$getUserId  = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0, 'directOutput' => true));
$getChecked = admFuncVariableIsValid($_GET, 'checked', 'string');

// add current url to navigation stack if last url was not the same page
if(strpos($gNavigation->getUrl(), 'bill.php') === false)
{
    $_SESSION['pMembershipFee']['checkedArray'] = array();
    $_SESSION['pMembershipFee']['mailArray'] = array();
}

if($getMode == 'mail' || $getMode == 'export')
{
    if (count($_SESSION['pMembershipFee']['checkedArray']) === 0)
    {
        echo 'marker_empty';
    }
}
else
{
    $membersListFields = array_filter($pPreferences->config['columnconfig']['bill_fields']);           //array_filter: löschen leerer Einträge, falls das Setup fehlgeschlagen ist

    $membersListSqlCondition = 'AND mem_usr_id IN (SELECT DISTINCT usr_id
        FROM '. TBL_USERS. '

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
             
       WHERE iban.usd_value IS NULL
         AND paid.usd_value IS NULL
         AND fee.usd_value IS NOT NULL
                    ';
    $membersListSqlCondition .= ' ) ';

    $membersList = list_members($membersListFields, 0, $membersListSqlCondition);
    
    if($getMode == 'prepare')
    {
        $ret_text = 'ERROR';
        if($getUserId != 0)           // ein einzelner User wurde selektiert
        {
            if($getChecked == 'false')            // der Haken wurde geloescht
            {
                unset($_SESSION['pMembershipFee']['checkedArray'][$getUserId]);
                $ret_text = 'success';
            }
            elseif ($getChecked == 'true')        // der Haken wurde gesetzt
            {
                $_SESSION['pMembershipFee']['checkedArray'][$getUserId] = isset($_SESSION['pMembershipFee']['mailArray'][$getUserId]) ? $_SESSION['pMembershipFee']['mailArray'][$getUserId] : '';
                $ret_text = 'success';
            }
        }
        else                        // Alle aendern wurde gewaehlt
        {
            foreach ($membersList as $member => $memberData)
            {
                if (array_key_exists($member, $_SESSION['pMembershipFee']['checkedArray']))
                {
                    unset($_SESSION['pMembershipFee']['checkedArray'][$member]);
                }
                else
                {
                    $_SESSION['pMembershipFee']['checkedArray'][$member] =  isset($_SESSION['pMembershipFee']['mailArray'][$member]) ? $_SESSION['pMembershipFee']['mailArray'][$member] : '';
                }
            }
            $ret_text = 'success';
        }
        echo $ret_text;
    }
    else
    {
        // set headline of the script
        $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL_EDIT');

        $gNavigation->addUrl(CURRENT_URL, $headline);
    
        $page = new HtmlPage('plg-mitgliedsbeitrag-bill', $headline);

        $page->addJavascript('
            function billexport(){ 
                $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/bill.php', array('mode' => 'export')) .'",
                    function(data){
                        // check if error occurs
                        if(data == "marker_empty") {
                            alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT_EMPTY').'");
                            return false;
                        }
                        else if(data == "success") {
                            alert("file OK");
                        }
                        else {
                            window.location.href = "'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/bill_export.php') .'" ;
                        }
                        return true;
                    }
                );
            };
            
            function massmail(){ 
                $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/bill.php', array('mode' => 'mail')) .'",
                    function(data){
                        // check if error occurs
                        if(data == "marker_empty") {
                            alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_EMPTY').'");
                            return false;
                        }
                        else {
                            window.location.href = "'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_write.php" ;
                        }
                        return true;
                    }
                );
            }; 
            
            function select_all() {
            var mem_show = $("#mem_show").val();
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/bill.php', array('mode' => 'prepare')) .'" ,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/bill.php').'");
                    }
                    else {
                        alert(data);
                        return false;
                    }
                    return true;
                }
            );
        }   
        ');            // !!!: ohne true

        $javascriptCode = '    

        // if checkbox of user is clicked then change data
        $("input[type=checkbox].memlist_checkbox").click(function(e){
            e.stopPropagation();
            var checkbox = $(this);
            var row_id = $(this).parent().parent().attr("id");
            var pos = row_id.search("_");
            var userid = row_id.substring(pos+1);
            var member_checked = $("input[type=checkbox]#member_"+userid).prop("checked");

            // change data in checkedArray
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/bill.php', array('mode' => 'prepare')) .'&checked=" + member_checked + "&usr_id=" + userid ,
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

        $form = new HtmlForm('bill_filter_form', '', $page, array('type' => 'navbar', 'setFocus' => false));

        $form->addButton('btn_exportieren', $gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT'), array('icon' => 'fa-file-csv', 'link' => 'javascript:billexport()', 'class' => 'btn-primary'));
 	    $form->addDescription('&nbsp');
        $form->addButton('btn_mailen', $gL10n->get('SYS_EMAIL'), array('icon' => 'fa-envelope', 'link' => 'javascript:massmail()', 'class' => 'btn-primary'));
 
        $page->addHtml($form->show());
    
        // create table object
        $table = new HtmlTable('tbl_bill', $page, true, true, 'table table-condensed');
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
                    ||  $gProfileFields->getPropertyById($usfId, 'usf_name_intern') === 'GENDER'
                    ||  $gProfileFields->getPropertyById($usfId, 'usf_type') === 'EMAIL' 
                    ||  $usfId === (int) $gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_id'))
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
    	
            $usf_uuid = '';         
            if(strlen($user->getValue('DEBTOR_EMAIL')) > 0)
            {
                $usf_uuid = $gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_uuid');
            }
            if(strlen($user->getValue('EMAIL')) > 0)
            {
                $usf_uuid = $gProfileFields->getProperty('EMAIL', 'usf_uuid');
            }
            $_SESSION['pMembershipFee']['mailArray'][$member] = $usf_uuid;
            
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
                    if (strlen($content)>0)
                    {
                    if($gSettingsManager->getString('enable_mail_module') != 1)
                    {
                        $mail_link = 'mailto:'. $content;
                    }
                    else
                    {
                        $mail_link = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_write.php', array('user_uuid' => $user->getValue('usr_uuid'), 'usf_uuid' => $userField->getValue('usf_uuid')));
                    }
                    $columnValues[] = '<a class="admidio-icon-link" href="'.$mail_link.'"><i class="fas fa-envelope" title="'.$gL10n->get('SYS_SEND_EMAIL_TO', array($content)).'"></i>';
                    }
                    else 
                    {
                        $columnValues[] = '';
                    }
                    
                }
                else
                {
                    $columnValues[] = $htmlValue;
                }
            }
            $table->addRowByArray($columnValues, 'userid_'.$member, array('nobr' => 'true'));      
        }//End Foreach

        $page->addHtml($table->show(false));
        $page->show();
    }
}
