<?php
/**
 ***********************************************************************************************
 * Modul Vorabinformation fuer das Admidio-Plugin Mitgliedsbeitrag
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
 * duedate          : Das uebergebene Faelligkeitsdatum zur Filterung
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
$getDueDate = admFuncVariableIsValid($_GET, 'duedate', 'string', array('defaultValue' => 0));

// add current url to navigation stack if last url was not the same page
if(strpos($gNavigation->getUrl(), 'pre_notification.php') === false)
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
            AND usd_usf_id = '. $gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id'). '
            AND rol_valid  = 1
            AND rol_cat_id = cat_id
            AND (  cat_org_id = '. $gCurrentOrgId. '
                OR cat_org_id IS NULL ) ';

    if($getDueDate != 0)                  // nur Benutzer mit Faelligkeitsdatum anzeigen ("Mit Faelligkeitsdatum" wurde gewaehlt)
    {
        $memberCondition .= 'AND usd_value = \''.$getDueDate.'\'   )';
    }
    else
    {
        $memberCondition .= 'AND usd_value IS NOT NULL )';
    }

    $sql = 'SELECT DISTINCT usr_id, last_name.usd_value AS last_name, first_name.usd_value AS first_name, birthday.usd_value AS birthday,
               city.usd_value AS city, street.usd_value AS street, zip_code.usd_value AS zip_code, country.usd_value AS country,
               faelligkeitsdatum.usd_value AS faelligkeitsdatum, beitrag.usd_value AS beitrag, lastschrifttyp.usd_value AS lastschrifttyp,
               mandatsreferenz.usd_value AS mandatsreferenz, debtor.usd_value AS debtor, debtorstreet.usd_value AS debtorstreet,
               debtorpostcode.usd_value AS debtorpostcode, debtorcity.usd_value AS debtorcity, debtoremail.usd_value AS debtoremail,
               email.usd_value AS email
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
        LEFT JOIN '. TBL_USER_DATA. ' AS mandatsreferenz
          ON mandatsreferenz.usd_usr_id = usr_id
         AND mandatsreferenz.usd_usf_id = ? -- $gProfileFields->getProperty(\'MANDATEID\'.$gCurrentOrgId, \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS faelligkeitsdatum
          ON faelligkeitsdatum.usd_usr_id = usr_id
         AND faelligkeitsdatum.usd_usf_id = ? -- $gProfileFields->getProperty(\'DUEDATE\'.$gCurrentOrgId, \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS lastschrifttyp
          ON lastschrifttyp.usd_usr_id = usr_id
         AND lastschrifttyp.usd_usf_id = ? -- $gProfileFields->getProperty(\'SEQUENCETYPE\'.$gCurrentOrgId, \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS beitrag
          ON beitrag.usd_usr_id = usr_id
         AND beitrag.usd_usf_id = ? -- $gProfileFields->getProperty(\'FEE\'.$gCurrentOrgId, \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS zip_code
          ON zip_code.usd_usr_id = usr_id
         AND zip_code.usd_usf_id = ? -- $gProfileFields->getProperty(\'POSTCODE\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS debtor
          ON debtor.usd_usr_id = usr_id
         AND debtor.usd_usf_id = ? -- $gProfileFields->getProperty(\'DEBTOR\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS debtorstreet
          ON debtorstreet.usd_usr_id = usr_id
         AND debtorstreet.usd_usf_id = ? -- $gProfileFields->getProperty(\'DEBTOR_STREET\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS debtoremail
          ON debtoremail.usd_usr_id = usr_id
         AND debtoremail.usd_usf_id = ? -- $gProfileFields->getProperty(\'DEBTOR_EMAIL\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS email
          ON email.usd_usr_id = usr_id
         AND email.usd_usf_id = ? -- $gProfileFields->getProperty(\'EMAIL\', \'usf_id\')
         LEFT JOIN '. TBL_USER_DATA. ' AS debtorpostcode
          ON debtorpostcode.usd_usr_id = usr_id
         AND debtorpostcode.usd_usf_id = ? -- $gProfileFields->getProperty(\'DEBTOR_POSTCODE\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS debtorcity
          ON debtorcity.usd_usr_id = usr_id
         AND debtorcity.usd_usf_id = ? -- $gProfileFields->getProperty(\'DEBTOR_CITY\', \'usf_id\')
        LEFT JOIN '. TBL_USER_DATA. ' AS country
          ON country.usd_usr_id = usr_id
         AND country.usd_usf_id = ? -- $gProfileFields->getProperty(\'COUNTRY\', \'usf_id\')

        LEFT JOIN '. TBL_MEMBERS. ' mem
          ON  mem.mem_begin  <= ? -- DATE_NOW
         AND mem.mem_end     > ? -- DATE_NOW
         AND mem.mem_usr_id  = usr_id
       WHERE  '. $memberCondition. '
    ORDER BY last_name, first_name ';
            
    $queryParams = array(
        $gProfileFields->getProperty('LAST_NAME', 'usf_id'),
        $gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
        $gProfileFields->getProperty('BIRTHDAY', 'usf_id'),
        $gProfileFields->getProperty('CITY', 'usf_id'),
        $gProfileFields->getProperty('STREET', 'usf_id'),
        $gProfileFields->getProperty('MANDATEID'.$gCurrentOrgId, 'usf_id'),
        $gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id'),
        $gProfileFields->getProperty('SEQUENCETYPE'.$gCurrentOrgId, 'usf_id'),
        $gProfileFields->getProperty('FEE'.$gCurrentOrgId, 'usf_id'),
        $gProfileFields->getProperty('POSTCODE', 'usf_id'),
        $gProfileFields->getProperty('DEBTOR', 'usf_id'),
        $gProfileFields->getProperty('DEBTOR_STREET', 'usf_id'),
        $gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_id'),
        $gProfileFields->getProperty('EMAIL', 'usf_id'),
        $gProfileFields->getProperty('DEBTOR_POSTCODE', 'usf_id'),
        $gProfileFields->getProperty('DEBTOR_CITY', 'usf_id'),
        $gProfileFields->getProperty('COUNTRY', 'usf_id'),
        DATE_NOW,
        DATE_NOW
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
        $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION');

        $gNavigation->addUrl(CURRENT_URL, $headline);

        $page = new HtmlPage('plg-mitgliedsbeitrag-pre-notification', $headline);

        $page->addJavascript('
            function prenotexport(){ 
                //var duedate = $("#duedate").val(); 
                $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/pre_notification.php', array('mode' => 'export')) .'",
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
                            //alert("jetzt gehts zu export");
                            window.location.href = "'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/pre_notification_export.php') .'" ;
                        }
                        return true;
                    }
                );
            };
            
            function massmail(){ 
            //var duedate = $("#duedate").val(); 
                $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/pre_notification.php', array('mode' => 'mail')) .'",
                    function(data){
                        // check if error occurs
                        if(data == "marker_empty") {
                            alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_EMPTY').'");
                            return false;
                        }
                        else {
                            //alert("jetzt gehts zu mail");
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
            var duedate = $("#duedate").val(); 
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/pre_notification.php', array('mode' => 'prepare')) .'&duedate=" + duedate,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/pre_notification.php').'?duedate=" + duedate);
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
                window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/pre_notification.php').'?duedate=" + $(this).val());
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
            var duedate = $("#duedate").val();

            // change data in checkedArray
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/pre_notification.php', array('mode' => 'prepare')) .'&checked=" + member_checked + "&usr_id=" + userid + "&duedate=" + duedate,
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

        $form = new HtmlForm('pre_notification_filter_form', '', $page, array('type' => 'navbar', 'setFocus' => false));

        //alle Faelligkeitsdaten einlesen
        $sql = 'SELECT DISTINCT usd_value
                FROM '.TBL_USER_DATA.','. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE usd_usf_id =  ? -- $gProfileFields->getProperty(\'DUEDATE\'.$gCurrentOrgId, \'usf_id\')
                AND   mem_begin <= ? -- DATE_NOW
                AND   mem_end >= ? -- DATE_NOW
                AND   usd_usr_id = mem_usr_id
                AND   mem_rol_id = rol_id
                AND   rol_valid = 1
                AND   rol_cat_id = cat_id
                AND (  cat_org_id = ? -- $gCurrentOrgId
                 OR cat_org_id IS NULL ) ';
                 
        $queryParams = array(
            $gProfileFields->getProperty('DUEDATE'.$gCurrentOrgId, 'usf_id'),
            DATE_NOW,
            DATE_NOW,
            $gCurrentOrgId
        );

        $duedateStatement = $gDb->queryPrepared($sql, $queryParams);

        $selectBoxEntries = array('0' => '- '.$gL10n->get('PLG_MITGLIEDSBEITRAG_SHOW_ALL').' -');
        while ($row = $duedateStatement->fetch())
        {
            $DueDate = \DateTime::createFromFormat('Y-m-d', $row['usd_value']);
            $selectBoxEntries[$row['usd_value']] = $DueDate->format($gSettingsManager->getString('system_date'));
        }
        $form->addSelectBox('duedate', $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'), $selectBoxEntries, array('defaultValue' => $getDueDate, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));

        $form->addButton('btn_exportieren', $gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT'), array('icon' => 'fa-file-csv', 'link' => 'javascript:prenotexport()', 'class' => 'btn-primary'));
 	    $form->addDescription('&nbsp');
        $form->addButton('btn_mailen', $gL10n->get('SYS_EMAIL'), array('icon' => 'fa-envelope', 'link' => 'javascript:massmail()', 'class' => 'btn-primary'));
 
        $page->addHtml($form->show());
    
        // create table object
        $table = new HtmlTable('tbl_duedates', $page, true, true, 'table table-condensed');
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

        // create array with all column heading values
        $columnHeading = array(
            '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CHANGE_ALL').'"/>',
            $gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE'),
            '<i class="fas fa-comment admidio-info-icon" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE_DESC').'"></i>',
            $gL10n->get('PLG_MITGLIEDSBEITRAG_FEE'),
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            '<i class="fas fa-map-marked-alt admidio-info-icon" title="'.$gL10n->get('SYS_ADDRESS').'"></i>',
            $gL10n->get('SYS_STREET'),
            '<i class="fas fa-info-circle admidio-info-icon" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEBTOR').'"></i>',
            $gL10n->get('PLG_MITGLIEDSBEITRAG_DEBTOR'),
            '<i class="fas fa-envelope admidio-info-icon" title="'.$gL10n->get('SYS_EMAIL').'"></i>',
            $gL10n->get('SYS_EMAIL'),
            $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID')
        );

        $table->setColumnAlignByArray(array('left', 'left', 'center', 'right', 'left', 'left', 'center', 'left', 'center', 'left', 'center', 'left', 'left'));
        $table->setDatatablesOrderColumns(array(5, 6));
        $table->addRowHeadingByArray($columnHeading);
        $table->disableDatatablesColumnsSort(array(1));
        $table->setDatatablesAlternativeOrderColumns(7, 8);
        $table->setDatatablesAlternativeOrderColumns(9, 10);
        $table->setDatatablesAlternativeOrderColumns(11, 12);
        $table->setDatatablesColumnsHide(array(8, 10, 12));

        // show rows with all organization users
        while($usr = $statement->fetch())
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
           if (array_key_exists($usr['usr_id'], $_SESSION['pMembershipFee']['checkedArray']))
            {
                $htmlDueDateStatus = '<input type="checkbox" id="member_'.$usr['usr_id'].'" name="member_'.$usr['usr_id'].'" checked="checked" class="memlist_checkbox" /><b id="loadindicator_member_'.$usr['usr_id'].'"></b>';
            }
            else
            {
                $htmlDueDateStatus = '<input type="checkbox" id="member_'.$usr['usr_id'].'" name="member_'.$usr['usr_id'].'" class="memlist_checkbox" /><b id="loadindicator_member_'.$usr['usr_id'].'"></b>';
            }

            //2. Spalte ($htmlDueDate)
           if($usr['faelligkeitsdatum'] > 0)
            {
                $DueDate = \DateTime::createFromFormat('Y-m-d', $usr['faelligkeitsdatum']);
                $htmlDueDate = $DueDate->format($gSettingsManager->getString('system_date'));
            }

            //3. Spalte ($htmlLastschrifttyp)
            switch($usr['lastschrifttyp'])
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
            if($usr['beitrag'] > 0)
            {
                $htmlBeitrag = $usr['beitrag'].' '.$gSettingsManager->getString('system_currency');
            }

            //5. Spalte (Nachname)

            //6. Spalte (Vorname)

            //7. Spalte ($htmlAddress)
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

            //8. Spalte ($addressText)

            //10. Spalte ($htmlDebtorText)
            if(strlen($usr['debtor']) > 0)
            {
                $debtor_text = $usr['debtor'];
            }
            if(strlen($usr['debtorstreet']) > 0)
            {
                $debtor_text = $debtor_text. ' - '. $usr['debtorstreet'];
            }
            if(strlen($usr['debtorpostcode']) > 0 || strlen($usr['debtorcity']) > 0)
            {
                $debtor_text = $debtor_text. ' - '. $usr['debtorpostcode']. ' '. $usr['debtorcity'];
            }

            if(strlen($debtor_text) > 1)
            {
                $htmlDebtorText = '<i class="fas fa-info-circle admidio-info-icon" title="'.$debtor_text.'"></i>';
            }

            $user->readDataById($usr['usr_id']);
            
            //11. Spalte ($htmlMail)
            if(StringUtils::strValidCharacters($usr['debtoremail'], 'email'))
            {
                $email = $usr['debtoremail'];
                $usf_uuid = $gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_uuid');
            }
            elseif(strlen($usr['email']) > 0)
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

            //12. Spalte ($email)
            if(strlen($usr['mandatsreferenz']) > 0)
            {
                $htmlMandateID = $usr['mandatsreferenz'];
            }
            
            // create array with all column values
            $columnValues = array(
                $htmlDueDateStatus,
                $htmlDueDate,
                $htmlLastschrifttyp,
                $htmlBeitrag,
                '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))) .'">'.$usr['last_name'].'</a>',
                '<a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))) .'">'.$usr['first_name'].'</a>',
                $htmlAddress,
                $addressText,
                $htmlDebtorText,
                $debtor_text,
                $htmlMail,
                $email,
                $htmlMandateID
            );

            $table->addRowByArray($columnValues, 'userid_'.$usr['usr_id']);
        }//End While

        $page->addHtml($table->show(false));
        $page->show();
    }
}
