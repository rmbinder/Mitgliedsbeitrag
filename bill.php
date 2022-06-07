<?php
/**
 ***********************************************************************************************
 * Modul Rechnung für das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2022 The Admidio Team
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
    $sql = 'SELECT DISTINCT usr_id, last_name.usd_value AS last_name, first_name.usd_value AS first_name, birthday.usd_value AS birthday,
               city.usd_value AS city, street.usd_value AS street, zip_code.usd_value AS zip_code, beitrag.usd_value AS beitrag,
               iban.usd_value AS iban, paid.usd_value AS paid, contributory_text.usd_value AS contributory_text, email.usd_value AS email
        FROM '. TBL_USERS. '
        LEFT JOIN '. TBL_USER_DATA. ' AS last_name
          ON last_name.usd_usr_id = usr_id
         AND last_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'LAST_NAME\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS first_name
          ON first_name.usd_usr_id = usr_id
         AND first_name.usd_usf_id = ? -- $gProfileFields->getProperty(\'FIRST_NAME\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS birthday
          ON birthday.usd_usr_id = usr_id
         AND birthday.usd_usf_id = ? -- $gProfileFields->getProperty(\'BIRTHDAY\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS city
          ON city.usd_usr_id = usr_id
         AND city.usd_usf_id = ? -- $gProfileFields->getProperty(\'CITY\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS street
          ON street.usd_usr_id = usr_id
         AND street.usd_usf_id = ? -- $gProfileFields->getProperty(\'STREET\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS beitrag
          ON beitrag.usd_usr_id = usr_id
         AND beitrag.usd_usf_id = ? -- $gProfileFields->getProperty(\'FEE\'.$gCurrentOrgId, \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS iban
          ON iban.usd_usr_id = usr_id
         AND iban.usd_usf_id = ? -- $gProfileFields->getProperty(\'IBAN\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS paid
          ON paid.usd_usr_id = usr_id
         AND paid.usd_usf_id = ? -- $gProfileFields->getProperty(\'PAID\'.$gCurrentOrgId, \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS contributory_text
          ON contributory_text.usd_usr_id = usr_id
         AND contributory_text.usd_usf_id = ? -- $gProfileFields->getProperty(\'CONTRIBUTORY_TEXT\'.$gCurrentOrgId, \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS zip_code
          ON zip_code.usd_usr_id = usr_id
         AND zip_code.usd_usf_id = ? -- $gProfileFields->getProperty(\'POSTCODE\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS email
          ON email.usd_usr_id = usr_id
         AND email.usd_usf_id = ? -- $gProfileFields->getProperty(\'EMAIL\', \'usf_id\')

        LEFT JOIN '. TBL_MEMBERS. ' AS mem
          ON  mem.mem_begin  <= ? -- DATE_NOW
         AND mem.mem_end     > ? -- DATE_NOW
         AND mem.mem_usr_id  = usr_id

       WHERE iban.usd_value IS NULL
         AND paid.usd_value IS NULL
         AND beitrag.usd_value IS NOT NULL
    
         AND EXISTS
        (SELECT 1
           FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ','. TBL_USER_DATA. '
          WHERE mem_usr_id = usr_id
            AND mem_rol_id = rol_id
            AND mem_begin <= ? -- DATE_NOW
            AND mem_end    > ? -- DATE_NOW
            AND usd_usr_id = usr_id
            AND rol_valid  = 1
            AND rol_cat_id = cat_id
            AND ( cat_org_id = ? -- $gCurrentOrgId
                OR cat_org_id IS NULL ) 
            AND usd_value IS NOT NULL )                                    

    ORDER BY last_name, first_name ';
    
    $queryParams = array(
        $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
        $gProfileFields->getProperty('BIRTHDAY', 'usf_id'),
        $gProfileFields->getProperty('CITY', 'usf_id'),
        $gProfileFields->getProperty('STREET', 'usf_id'),
        $gProfileFields->getProperty('FEE'.$gCurrentOrgId, 'usf_id'),
        $gProfileFields->getProperty('IBAN', 'usf_id'),
        $gProfileFields->getProperty('PAID'.$gCurrentOrgId, 'usf_id'),
        $gProfileFields->getProperty('CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'usf_id'),
        $gProfileFields->getProperty('POSTCODE', 'usf_id'),
        $gProfileFields->getProperty('EMAIL', 'usf_id'),
        DATE_NOW,
        DATE_NOW,
        DATE_NOW,
        DATE_NOW,
        $gCurrentOrgId
    );
       
    $statement = $gDb->queryPrepared($sql, $queryParams);

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
            while($usr = $statement->fetch())
            {
                if (array_key_exists($usr['usr_id'], $_SESSION['pMembershipFee']['checkedArray']))
                {
                    unset($_SESSION['pMembershipFee']['checkedArray'][$usr['usr_id']]);
                }
                else
                {
                    $_SESSION['pMembershipFee']['checkedArray'][$usr['usr_id']] =  isset($_SESSION['pMembershipFee']['mailArray'][$usr['usr_id']]) ? $_SESSION['pMembershipFee']['mailArray'][$usr['usr_id']] : '';
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

        // add current url to navigation stack if last url was not the same page
        if(strpos($gNavigation->getUrl(), 'bill.php') === false)
        {
            $gNavigation->addUrl(SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php', array('show_option' => 'sepa')));
            $gNavigation->addUrl(CURRENT_URL);
        }

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
        ');            // !!!: ohne true

        $javascriptCode = '    

        // if checkbox in header is clicked then change all data
        $("input[type=checkbox].change_checkbox").click(function(){
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
        });

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

        // create array with all column heading values
        $columnHeading = array(
            '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CHANGE_ALL').'"/>',
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'),
            $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT'),
            '<i class="fas fa-map-marked-alt admidio-info-icon" title="'.$gL10n->get('SYS_ADDRESS').'"></i>',
            $gL10n->get('SYS_STREET'),
            '<i class="fas fa-envelope admidio-info-icon" title="'.$gL10n->get('SYS_EMAIL').'"></i>',
            $gL10n->get('SYS_EMAIL')
        );
        
        $table->setColumnAlignByArray(array('center', 'left', 'left',  'right', 'left', 'left',  'left', 'center', 'center'));
        $table->setDatatablesOrderColumns(array(2, 3));
        $table->addRowHeadingByArray($columnHeading);
        $table->disableDatatablesColumnsSort(array(1));
        $table->setDatatablesAlternativeOrderColumns(6, 7);
        $table->setDatatablesAlternativeOrderColumns(8, 9);
        $table->setDatatablesColumnsHide(array(7, 9));

        // show rows with users
        while($usr = $statement->fetch())
        {
            $addressText  = ' ';
            $htmlAddress  = '&nbsp;';
            $htmlBirthday = '&nbsp;';
            $htmlBeitrag  = '&nbsp;';
            $htmlBeitragstext = '&nbsp;';
            $email = '';
            $htmlMail = '&nbsp;';
            
            $user->readDataById($usr['usr_id']);
            
            //1. Spalte ($htmlCheckedStatus)
           if (array_key_exists($usr['usr_id'], $_SESSION['pMembershipFee']['checkedArray']))
            {
                $htmlCheckedStatus = '<input type="checkbox" id="member_'.$usr['usr_id'].'" name="member_'.$usr['usr_id'].'" checked="checked" class="memlist_checkbox" /><b id="loadindicator_member_'.$usr['usr_id'].'"></b>';
            }
            else
            {
                $htmlCheckedStatus = '<input type="checkbox" id="member_'.$usr['usr_id'].'" name="member_'.$usr['usr_id'].'" class="memlist_checkbox" /><b id="loadindicator_member_'.$usr['usr_id'].'"></b>';
            }

            //2. Spalte (Nachname)
            
            //3. Spalte (Vorname)
            
            //4. Spalte ($htmlBeitrag)
            if($usr['beitrag'] > 0)
            {
                $htmlBeitrag = $usr['beitrag'].' '.$gSettingsManager->getString('system_currency');
            }

            //5. Spalte ($htmlBeitragstext)
            if(strlen($usr['contributory_text']) > 0)
            {
                $htmlBeitragstext = $usr['contributory_text'];
            }
            
            //6. Spalte ($htmlAddress)
            if(strlen($usr['zip_code']) > 0 || strlen($usr['city']) > 0)
            {
                $addressText .= $usr['zip_code']. ' '. $usr['city'];
            }
            if(strlen($usr['street']) > 0)
            {
                $addressText .= ' - '. $usr['street'];
            }
            if(strlen($addressText) > 1)
            {
                $htmlAddress = '<i class="fas fa-map-marked-alt admidio-info-icon" title="'.$addressText.'"></i>';
            }

            //7. Spalte ($addressText)

            //10. Spalte ($htmlDebtorText)
            
            //8. Spalte ($htmlMail)
            //9. Spalte ($email)
                
            if(strlen($usr['email']) > 0)
            {
                $email = $usr['email'];
                $usf_uuid = $gProfileFields->getProperty('EMAIL', 'usf_uuid');
            }
            if(strlen($email) > 0)
            {
                $_SESSION['pMembershipFee']['mailArray'][$usr['usr_id']] = $usf_uuid;
                if($gSettingsManager->getString('enable_mail_module') != 1)
                {
                   $mail_link = 'mailto:'. $email;
                }
                else
                {
                    $mail_link = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_write.php', array('user_uuid' => $user->getValue('usr_uuid'), 'usf_uuid' => $usf_uuid));
                }
                $htmlMail = '<a class="admidio-icon-link" href="'.$mail_link.'"><i class="fas fa-envelope" title="'.$gL10n->get('SYS_SEND_EMAIL_TO', array($email)).'"></i>';
            }

            // create array with all column values
            $columnValues = array(
                $htmlCheckedStatus,     
                '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))) .'">'.$usr['last_name'].'</a>',
                '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))) .'">'.$usr['first_name'].'</a>',
                $htmlBeitrag,
                $htmlBeitragstext,
                $htmlAddress,
                $addressText,
                $htmlMail,
                $email
            );

            $table->addRowByArray($columnValues, 'userid_'.$usr['usr_id']);
        }//End While

        $page->addHtml($table->show(false));
        $page->show();
    }
}
