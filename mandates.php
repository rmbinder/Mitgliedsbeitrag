<?php
/**
 ***********************************************************************************************
 * Setzen eines Mandatsdatums fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   mandates.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste aller Benutzer mit Beitraegen
 *                    assign - Setzen eines Mandatsdatums
 * usr_id           : Id des Benutzers, fuer den das Mandatsdatum gesetzt/geloescht wird
 * datum_neu        : Mandatsdatum
 * mem_show_choice  : 0 - (Default) Alle Benutzer anzeigen
 *                    1 - Nur Benutzer anzeigen, bei denen ein Mandatsdatum vorhanden ist
 *                    2 - Nur Benutzer anzeigen, bei denen kein Mandatsdatum vorhanden ist
 * full_screen      : 0 - Normalbildschirm
 *                    1 - Vollbildschirm
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

if (isset($_GET['mode']) && $_GET['mode'] == 'assign')
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode        = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getUserId      = admFuncVariableIsValid($_GET, 'usr_id', 'numeric', array('defaultValue' => 0, 'directOutput' => true));
$getDatumNeu    = admFuncVariableIsValid($_GET, 'datum_neu', 'date');
$getMembersShow = admFuncVariableIsValid($_GET, 'mem_show_choice', 'numeric', array('defaultValue' => 0));
$getFullScreen  = admFuncVariableIsValid($_GET, 'full_screen', 'numeric');

if ($getMode == 'assign')
{
    $ret_text = 'ERROR';

    $userArray = array();
    if ($getUserId != 0)           // Mandatsdatum nur fuer einen einzigen User aendern
    {
        $userArray[0] = $getUserId;
    }
    else                          // Alle aendern wurde gewaehlt
    {
        $userArray = $_SESSION['pMembershipFee']['mandates_user'];
    }

  try
   {
        foreach ($userArray as $dummy => $data)
        {
            $user = new User($gDb, $gProfileFields, $data);

            //zuerst mal sehen, ob bei diesem user bereits ein Mandatsdatum vorhanden ist
            if (strlen($user->getValue('MANDATEDATE'.ORG_ID)) === 0)
            {
                //er hat noch kein Mandatsdatum, deshalb ein neues eintragen
                $user->setValue('MANDATEDATE'.ORG_ID, $getDatumNeu);
            }
            else
            {
                //er hat bereits ein Mandatsdatum, deshalb das vorhandene loeschen
                $user->setValue('MANDATEDATE'.ORG_ID, '');
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
   
    if ($getFullScreen == true)
    {
    	$membersListFields = $pPreferences->config['columnconfig']['mandates_fields_full_screen'];
    }
    else
    {
    	$membersListFields = $pPreferences->config['columnconfig']['mandates_fields_normal_screen'];
    }
    
    $membersListSqlCondition = 'AND mem_usr_id IN (SELECT DISTINCT usr_id
        FROM '. TBL_USERS. '
        LEFT JOIN '. TBL_USER_DATA. ' AS mandateid
          ON mandateid.usd_usr_id = usr_id
         AND mandateid.usd_usf_id = '. $gProfileFields->getProperty('MANDATEID'.ORG_ID, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS mandatedate
          ON mandatedate.usd_usr_id = usr_id
         AND mandatedate.usd_usf_id = '. $gProfileFields->getProperty('MANDATEDATE'.ORG_ID, 'usf_id'). '
        LEFT JOIN '. TBL_USER_DATA. ' AS iban
          ON iban.usd_usr_id = usr_id
         AND iban.usd_usf_id = '. $gProfileFields->getProperty('IBAN', 'usf_id'). '
         		
        LEFT JOIN '. TBL_MEMBERS. ' AS mem
          ON mem.mem_begin  <= \''.DATE_NOW.'\'
         AND mem.mem_end     > \''.DATE_NOW.'\'
         AND mem.mem_usr_id  = usr_id
         		
       WHERE iban.usd_value IS NOT NULL
         AND mandateid.usd_value IS NOT NULL ';
    
    if ($getMembersShow == 1)                   // Nur Benutzer anzeigen, bei denen ein Mandatsdatum vorhanden ist
    {
    	$membersListSqlCondition .= ' AND mandatedate.usd_value IS NOT NULL ) ';
    }
    elseif ($getMembersShow == 2)				// Nur Benutzer anzeigen, bei denen kein Mandatsdatum vorhanden ist
    {
    	$membersListSqlCondition .= ' AND mandatedate.usd_value IS NULL ) ';
    }
    else 										// Alle Benutzer anzeigen
    {
    	$membersListSqlCondition .= ' ) ';
    }
    
    $membersList = list_members($membersListFields, 0, $membersListSqlCondition);

    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATES');

    // add current url to navigation stack if last url was not the same page
    if (strpos($gNavigation->getUrl(), 'mandates.php') === false)
    {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }

    // create html page object
    $page = new HtmlPage($headline);

    if ($getFullScreen == true)
    {
        $page->hideThemeHtml();
    }

    $javascriptCode = '
        // Anzeige abhaengig vom gewaehlten Filter
        $("#mem_show").change(function () {
                if($(this).val().length > 0) {
                    window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php', array('full_screen' => $getFullScreen)). '&mem_show_choice=" + $(this).val());
                }
        });

        // if checkbox in header is clicked then change all data
        $("input[type=checkbox].change_checkbox").click(function(){
            var datum = $("#datum").val();
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php', array('mode' => 'assign', 'full_screen' => $getFullScreen)) .'&datum_neu=" + datum,
                function(data){
                    // check if error occurs
                    if(data == "success") {
                    var mem_show = $("#mem_show").val();
                        window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php', array('full_screen' => $getFullScreen)).' &mem_show_choice="  + mem_show);
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

            // change data in database
            $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php', array('full_screen' => $getFullScreen, 'mode' => 'assign')) .'&datum_neu=" + datum + "&usr_id=" + userid,
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

    $mandatesMenu = $page->getMenu();
    $mandatesMenu->addItem('menu_item_back', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php', arraqy('show_option' => 'mandates')), $gL10n->get('SYS_BACK'), 'back.png');

    if ($getFullScreen == true)
    {
        $mandatesMenu->addItem('menu_item_normal_picture', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php', array('mem_show_choice' => $getMembersShow. 'full_screen' => 0)),
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
    }
    else
    {
        $mandatesMenu->addItem('menu_item_full_screen', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandates.php', array('mem_show_choice' => $getMembersShow. 'full_screen' => 1)),
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
    }

    $navbarForm = new HtmlForm('navbar_show_all_users_form', '', $page, array('type' => 'navbar', 'setFocus' => false));

    $datumtemp = \DateTime::createFromFormat('Y-m-d', DATE_NOW);
    $datum = $datumtemp->format($gSettingsManager->getString('system_date'));

    $navbarForm->addInput('datum', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE'), $datum, array('type' => 'date', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_MANDATEDATE_DESC'));
    $selectBoxEntries = array('0' => $gL10n->get('MEM_SHOW_ALL_USERS'), '1' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITH_MANDATEDATE'), '2' => $gL10n->get('PLG_MITGLIEDSBEITRAG_WITHOUT_MANDATEDATE'));
    $navbarForm->addSelectBox('mem_show', $gL10n->get('PLG_MITGLIEDSBEITRAG_FILTER'), $selectBoxEntries, array('defaultValue' => $getMembersShow, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_FILTER_DESC', 'showContextDependentFirstEntry' => false));

    $mandatesMenu->addForm($navbarForm->show(false));

    // create table object
    $table = new HtmlTable('tbl_assign_role_membership', $page, true, true, 'table table-condensed');
    $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES_FOUND');
    
    $columnAlign  = array('center');
    $columnValues = array( '<input type="checkbox" id="change" name="change" class="change_checkbox admidio-icon-help" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE_CHANGE_ALL_DESC').'"/>');
    
    //column mandate change
    $columnAlign[]  = 'center';
    $columnValues[] = '<img class="admidio-icon-help" src="'. THEME_URL . '/icons/edit.png"
            alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" />';
    
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
    $table->disableDatatablesColumnsSort(array(1,2));
    
    //user data
    foreach ($membersList as $member => $memberData)
    {
    	if (strlen($memberData[$gProfileFields->getProperty('MANDATEDATE'.ORG_ID, 'usf_id')]) > 0)
    	{
    		$content= '<input type="checkbox" id="member_'.$member.'" name="member_'.$member.'" checked="checked" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$member.'"></b>';
    	}
    	else
    	{
    		$content= '<input type="checkbox" id="member_'.$member.'" name="member_'.$member.'" class="memlist_checkbox memlist_member" /><b id="loadindicator_member_'.$member.'"></b>';
    	}
    	
    	$columnValues = array($content);
    	
    	$columnValues[] = '<a class="admidio-icon-info" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mandate_change.php', array('user_id' => $member)). '"><img src="'. THEME_URL . '/icons/edit.png"
                    alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'" /></a>';
                    		
    	foreach ($memberData as $usfId => $data)
    	{
    		if (!is_int($usfId))
    		{
    			continue;
    		}
    		
    		// fill content with data of database
    		$content = $data;
    		
    		/*****************************************************************/
    		// in some cases the content must have a special output format
    		/*****************************************************************/
    		if ($usfId === (int) $gProfileFields->getProperty('COUNTRY', 'usf_id'))
    		{
    			$content = $gL10n->getCountryByCode($data);
    		}
    		elseif ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'CHECKBOX')
    		{
    			if ($content != 1)
    			{
    				$content = 0;
    			}
    		}
    		elseif ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'DATE')
    		{
    			if (strlen($data) > 0)
    			{
    				// date must be formated
    				$date = DateTime::createFromFormat('Y-m-d', $data);
    				$content = $date->format($gSettingsManager->getString('system_date'));
    			}
    		}
    		
    		if ($usfId == $gProfileFields->getProperty('MANDATEDATE'.ORG_ID, 'usf_id'))
    		{
    			$content = '<div class="mandatedate_'.$member.'" id="mandatedate_'.$member.'">'.$content.'</div>';
    		}
    		
    		// firstname and lastname get a link to the profile
    		if (($usfId === (int) $gProfileFields->getProperty('LAST_NAME', 'usf_id')
    				|| $usfId === (int) $gProfileFields->getProperty('FIRST_NAME', 'usf_id')))
    		{
    			$htmlValue = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $member);
    			$columnValues[] = '<a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL.FOLDER_MODULES.'/profile/profile.php', array('user_id' => $member)).'">'.$htmlValue.'</a>';
    		}
    		else
    		{
    			// checkbox must set a sorting value
    			if ($gProfileFields->getPropertyById($usfId, 'usf_type') === 'CHECKBOX')
    			{
    				$columnValues[] = array('value' => $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $member), 'order' => $content);
    			}
    			else
    			{
    				$columnValues[] = $gProfileFields->getHtmlValue($gProfileFields->getPropertyById($usfId, 'usf_name_intern'), $content, $member);
    			}
    		}
    	}
    	
    	$table->addRowByArray($columnValues, 'userid_'.$member, array('nobr' => 'true'));
    	
    	$userArray[] = $member;
    	
    }  // End-foreach User
    
    $_SESSION['pMembershipFee']['mandates_user'] = $userArray;

    $page->addHtml($table->show(false));
    $page->addHtml('<p>'.$gL10n->get('SYS_CHECKBOX_AUTOSAVE').'</p>');

    $page->show();
}
