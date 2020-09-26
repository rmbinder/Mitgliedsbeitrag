<?php
/**
 ***********************************************************************************************
 * Erzeugt das Einstellungen-Menue fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2020 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * choice     : agestaggeredroles   - Loeschen oder Hinzufuegen einer Konfiguration in den altersgestaffelten Rollen
 *              familyroles         - Loeschen oder Hinzufuegen einer Konfiguration in den Familienollen
 *              accountdata         - Mandatsaenderung im Abschnitt Kontodaten wurde gewaehlt
 * conf       : -1                  - Hinzufuegen einer Konfiguration
 *              Zahl >= 0           - Loeschen einer Konfiguration
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if (!isUserAuthorizedForPreferences())
{
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Initialize and check the parameters
$getChoice = admFuncVariableIsValid($_GET, 'choice', 'string', array('defaultValue' => ''));
$getConf   = admFuncVariableIsValid($_GET, 'conf', 'numeric');

$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE');

if ($getChoice == 'agestaggeredroles')
{
    if ($getConf == -1)
    {
        $pPreferences->config['Altersrollen']['altersrollen_token'][] = $pPreferences->config_default['Altersrollen']['altersrollen_token'][0];
    }
    else
    {
        array_splice($pPreferences->config['Altersrollen']['altersrollen_token'], $getConf, 1);
    }
}
elseif ($getChoice == 'familyroles')
{
    if ($getConf == -1)
    {
        $pPreferences->config['Familienrollen']['familienrollen_beitrag'][] = $pPreferences->config_default['Familienrollen']['familienrollen_beitrag'][0];
        $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][] = $pPreferences->config_default['Familienrollen']['familienrollen_zeitraum'][0];
        $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][] = $pPreferences->config_default['Familienrollen']['familienrollen_beschreibung'][0];
        $pPreferences->config['Familienrollen']['familienrollen_prefix'][] = $pPreferences->config_default['Familienrollen']['familienrollen_prefix'][0];
        $pPreferences->config['Familienrollen']['familienrollen_pruefung'][] = $pPreferences->config_default['Familienrollen']['familienrollen_pruefung'][0];
    }
    else
    {
        array_splice($pPreferences->config['Familienrollen']['familienrollen_beitrag'], $getConf, 1);
        array_splice($pPreferences->config['Familienrollen']['familienrollen_zeitraum'], $getConf, 1);
        array_splice($pPreferences->config['Familienrollen']['familienrollen_beschreibung'], $getConf, 1);
        array_splice($pPreferences->config['Familienrollen']['familienrollen_prefix'], $getConf, 1);
        array_splice($pPreferences->config['Familienrollen']['familienrollen_pruefung'], $getConf, 1);
    }
}
$num_agestaggeredroles = count($pPreferences->config['Altersrollen']['altersrollen_token']);
$num_familyroles = count($pPreferences->config['Familienrollen']['familienrollen_prefix']);

if ($getChoice == '')
{
    $gNavigation->addUrl(CURRENT_URL, $headline);
}

//das Array fuer die Auswahl der Profilfelder plus evtl. Zusatzfelder erzeugen
$fieldSelectionList = array();
$i = 1;
foreach ($gProfileFields->getProfileFields() as $field)
{
	if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
	{
		$fieldSelectionList[$i]['id']       = 'p'.$field->getValue('usf_id');
		$fieldSelectionList[$i]['cat_name'] = $field->getValue('cat_name');
		$fieldSelectionList[$i]['data']     = addslashes($field->getValue('usf_name'));
		$i++;
	}
}

// create html page object
$page = new HtmlPage('plg-mitgliedsbeitrag-preferences', $headline);
$page->setUrlPreviousPage($gNavigation->getPreviousUrl());

// open the modules tab if the options of a module should be shown
if ($getChoice != '')
{
    $page->addJavascript('
        $("#tabs_nav_preferences").attr("class", "nav-link active");
        $("#tabs-preferences").attr("class", "tab-pane fade show active");
        $("#collapse_'.$getChoice.'").attr("class", "collapse show");
        location.hash = "#" + "panel_'.$getChoice.'";',
        true
    );
}
else
{
    $page->addJavascript('
        $("#tabs_nav_preferences").attr("class", "nav-link active");
        $("#tabs-preferences").attr("class", "tab-pane active");', 
        true
    );
}

$page->addJavascript('
    function cischieben(){
        var ci = $("input[type=text]#ci").val();
        var origci = $("input[type=text]#origci").val(ci);
        $("input[type=text]#ci").val("");
    };
    function creditorschieben(){
        var creditor = $("input[type=text]#creditor").val();
        var origcreditor = $("input[type=text]#origcreditor").val(creditor);
        $("input[type=text]#creditor").val("");
    };'
);            // !!!: ohne true

$page->addJavascript('
     $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        var formAlert = $("#" + id + " .form-alert");
        formAlert.hide();
    
        // disable default form submit
        event.preventDefault();
    
        $.post({
    
            url: action,
            data: $(this).serialize(),
            success: function(data) {
                if (data === "success") {
                    formAlert.attr("class", "alert alert-success form-alert");
                    formAlert.html("<i class=\"fas fa-check\"></i><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 2500);
                    formAlert.fadeOut("slow");
                }
                else if(data === "convert_error") {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.html("<i class=\"fas fa-times\"></i><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NO_DATA_TO_CONVERT').'</strong>");
                    formAlert.fadeIn("slow");
                    formAlert.animate({opacity: 1.0}, 10000);
                    formAlert.fadeOut("slow");
                }
                else {
                    formAlert.attr("class", "alert alert-danger form-alert");
                    formAlert.fadeIn();
                    formAlert.html("<i class=\"fas fa-exclamation-circle\"></i>" + data);
                }
            }
        });
    });', 
    true
);


$javascriptCode = 'var arr_user_fields = createProfileFieldsArray(); ';

// create an array with the necessary data
foreach($pPreferences->config['columnconfig'] as $conf => $confFields)
{
	$javascriptCode .= '
        var arr_default_fields'.$conf.' = createColumnsArray'.$conf.'();
        var fieldNumberIntern'.$conf.'  = 0;
        		
    	// Funktion fuegt eine neue Zeile zum Zuordnen von Spalten fuer die Liste hinzu
    	function addColumn'.$conf.'()
    	{
        	var category = "";
        	var fieldNumberShow  = fieldNumberIntern'.$conf.' + 1;
        	var table = document.getElementById("mylist_fields_tbody'.$conf.'");
        	var newTableRow = table.insertRow(fieldNumberIntern'.$conf.');
        	newTableRow.setAttribute("id", "row" + (fieldNumberIntern'.$conf.' + 1))
        	//$(newTableRow).css("display", "none"); // ausgebaut wg. Kompatibilitaetsproblemen im IE8
        	var newCellCount = newTableRow.insertCell(-1);
        	newCellCount.innerHTML = (fieldNumberShow) + ".&nbsp;'.$gL10n->get('LST_COLUMN').'&nbsp;:";
        			
        	// neue Spalte zur Auswahl des Profilfeldes
        	var newCellField = newTableRow.insertCell(-1);
        	htmlCboFields = "<select class=\"form-control\"  size=\"1\" id=\"column" + fieldNumberShow + "\" class=\"ListProfileField\" name=\"column'.$conf.'_" + fieldNumberShow + "\">" +
                "<option value=\"\"></option>";
        	for(var counter = 1; counter < arr_user_fields.length; counter++)
        	{
            	if(category != arr_user_fields[counter]["cat_name"])
            	{
                	if(category.length > 0)
                	{
                    	htmlCboFields += "</optgroup>";
                	}
                	htmlCboFields += "<optgroup label=\"" + arr_user_fields[counter]["cat_name"] + "\">";
                	category = arr_user_fields[counter]["cat_name"];
            	}
        			
            	var selected = "";
        			
            	// bei gespeicherten Listen das entsprechende Profilfeld selektieren
            	// und den Feldnamen dem Listenarray hinzufuegen
            	if(arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.'])
            	{
                	if(arr_user_fields[counter]["id"] == arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["id"])
                	{
                    	selected = " selected=\"selected\" ";
                   	 	arr_default_fields'.$conf.'[fieldNumberIntern'.$conf.']["data"] = arr_user_fields[counter]["data"];
                	}
            	}
             	htmlCboFields += "<option value=\"" + arr_user_fields[counter]["id"] + "\" " + selected + ">" + arr_user_fields[counter]["data"] + "</option>";
        	}
        	htmlCboFields += "</select>";
        	newCellField.innerHTML = htmlCboFields;
                   	 		
        	$(newTableRow).fadeIn("slow");
        	fieldNumberIntern'.$conf.'++;
    	}
        			
    	function createColumnsArray'.$conf.'()
    	{
        	var default_fields = new Array(); ';
		
			for ($number = 0; $number < count($confFields); $number++)
			{
				foreach ($fieldSelectionList as $key => $data)
				{
					if ($confFields[$number] == $data['id'])
					{
						$javascriptCode .= '
                			default_fields['. $number. '] 		  = new Object();
                			default_fields['. $number. ']["id"]   = "'. $fieldSelectionList[$key]["id"]. '";
                			default_fields['. $number. ']["data"] = "'. $fieldSelectionList[$key]["data"]. '";
                		';
					}
				}
			}
			$javascriptCode .= '
        	return default_fields;
    	}
    ';
}

$javascriptCode .= '
    function createProfileFieldsArray()
    {
        var user_fields = new Array(); ';

		// create an array for all columns with the necessary data
		for ($i = 1; $i < count($fieldSelectionList)+1; $i++)
		{
			$javascriptCode .= '
                user_fields['. $i. '] 				= new Object();
                user_fields['. $i. ']["id"]   		= "'. $fieldSelectionList[$i]['id'] . '";
                user_fields['. $i. ']["cat_name"] 	= "'. $fieldSelectionList[$i]['cat_name']. '";
                user_fields['. $i. ']["data"]   	= "'. $fieldSelectionList[$i]['data'] . '";
            ';
		}

		$javascriptCode .= '
        return user_fields;
    }
';
$page->addJavascript($javascriptCode);

$javascriptCode = '$(document).ready(function() { ';
	foreach($pPreferences->config['columnconfig'] as $conf => $confFields)
	{
		$javascriptCode .= '
    		for(var counter = 0; counter < '. count($confFields). '; counter++) {
        		addColumn'. $conf. '();
    		}
    	';
	}
	$javascriptCode .= '}); 
';

$page->addJavascript($javascriptCode, true);  

$headerNavbar = new HtmlNavbar('navbar_menu_preferences');
$headerNavbar->addItem('menu_item_update', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', $gL10n->get('SYS_UPDATE'), 'fa-redo', 'right');
$page->addHtml($headerNavbar->show(false));
                    
$page->addHtml('
<ul id="preferences_tabs" class="nav nav-tabs" role="tablist">
    <li class="nav-item">
        <a id="tabs_nav_preferences" class="nav-link" href="#tabs-preferences" data-toggle="tab" role="tab">'.$gL10n->get('SYS_SETTINGS').'</a>
    </li>
</ul>
    
<div class="tab-content">
    <div class="tab-pane fade" id="tabs-preferences" role="tabpanel">
        <div class="accordion" id="accordion_preferences">');
                            
// PANEL: CONTRIBUTION_SETTINGS                   
                    
$formContributionSettings = new HtmlForm('contributionsettings_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'contributionsettings')), $page, array('class' => 'form-preferences'));
$formContributionSettings->addInput('beitrag_prefix', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX'), $pPreferences->config['Beitrag']['beitrag_prefix'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PREFIX_DESC'));
$formContributionSettings->addInput('beitrag_suffix', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SUFFIX'), $pPreferences->config['Beitrag']['beitrag_suffix'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SUFFIX_DESC'));
$formContributionSettings->addCheckbox('beitrag_anteilig', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PRORATA'), $pPreferences->config['Beitrag']['beitrag_anteilig'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PRORATA_DESC'));
$formContributionSettings->addCheckbox('beitrag_abrunden', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_ROUNDDOWN'), $pPreferences->config['Beitrag']['beitrag_abrunden'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_ROUNDDOWN_DESC'));
$formContributionSettings->addInput('beitrag_mindestbetrag', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_MINCALC').' '.$gSettingsManager->getString('system_currency'), $pPreferences->config['Beitrag']['beitrag_mindestbetrag'], array('type' => 'number', 'minNumber' => 0, 'maxNumber' => 999, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_MINCALC_DESC'));
$formContributionSettings->addCheckbox('beitrag_textmitnam', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_MEMNAMES'), $pPreferences->config['Beitrag']['beitrag_textmitnam'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_MEMNAMES_DESC'));
$formContributionSettings->addCheckbox('beitrag_textmitfam', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_FAMNAMES'), $pPreferences->config['Beitrag']['beitrag_textmitfam'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_FAMNAMES_DESC'));
$selectBoxEntries = array('#' => ' &nbsp '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_BLANK'),
                          '.' => '. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_DOT'),
                          ',' => ', '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_COMMA'),
                          '-' => '- '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HYPHEN'),
                          '/' => '/ '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SLASH'),
                          '+' => '+ '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PLUS'),
                          '*' => '* '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TIMES').'(*)',
                          '%' => '% '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PERCENT').'(*)');
$formContributionSettings->addSelectBox('beitrag_text_token', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_TOKEN'), $selectBoxEntries, array('defaultValue' => $pPreferences->config['Beitrag']['beitrag_text_token'], 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_TOKEN_DESC', 'showContextDependentFirstEntry' => false));

$formContributionSettings->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS_MAIL_TEXT'),
   '<p>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS_MAIL_TEXT_DESC').':</p><p>
   <strong>#user_first_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FIRST_NAME').'<br />
   <strong>#user_last_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_LAST_NAME').'<br />
   <strong>#organization_long_name#</strong> - '.$gL10n->get('ORG_VARIABLE_NAME_ORGANIZATION').'<br />
   <strong>#fee#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FEE').'<br />
   <strong>#due_day#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_DUE_DAY').'<br />
   <strong>#mandate_id#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_MANDATE_ID').'<br />
   <strong>#creditor_id#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_CREDITOR_ID').'<br />
   <strong>#iban#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_IBAN').'<br />
	<strong>#iban_obfuscated#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN_OBFUSCATED').'<br />
   <strong>#bic#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_BIC').'<br />
   <strong>#debtor#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_DEBTOR').'<br />
   <strong>#membership_fee_text#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_MEMBERSHIP_FEE_TEXT').'</p>');

$text = new TableText($gDb);
$text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => ORG_ID));
//wenn noch nichts drin steht, dann vorbelegen
if ($text->getValue('txt_text') == '')
{
    // convert <br /> to a normal line feed
    $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_CONTRIBUTION_PAYMENTS'));
    $text->setValue('txt_text', $value);
    $text->save();
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => ORG_ID));
}
$formContributionSettings->addMultilineTextInput('mail_text', '', $text->getValue('txt_text'), 7);
$formContributionSettings->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'contributionsettings', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SETTINGS'), 'fas fa-edit', $formContributionSettings->show()));
                    
// PANEL: AGE_STAGGERED_ROLES                    
                    
$formAgeStaggeredRoles = new HtmlForm('agestaggeredroles_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'agestaggeredroles')), $page, array('class' => 'form-preferences'));
$formAgeStaggeredRoles->addInput('altersrollen_stichtag', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEADLINE'), $pPreferences->config['Altersrollen']['altersrollen_stichtag'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DEADLINE_DESC', 'type' => 'date'));
$formAgeStaggeredRoles->addLine();
$formAgeStaggeredRoles->addStaticControl('descd', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER'), '', array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DELIMITER_DESC'));

$html = $gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER_INFO1').'<strong><br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER_INFO2').' </strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER_INFO3');
$formAgeStaggeredRoles->addCustomContent('', $html);

$formAgeStaggeredRoles->addDescription('<div style="width:100%; height:'.($num_agestaggeredroles<2 ? 170 : 210).'px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_agestaggeredroles; $conf++)
{
    $formAgeStaggeredRoles->openGroupBox('agestaggeredroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_STAGGERING'));
    $formAgeStaggeredRoles->addInput('altersrollen_token'.$conf, '', $pPreferences->config['Altersrollen']['altersrollen_token'][$conf], array('maxLength' => 1, 'property' => HtmlForm::FIELD_REQUIRED));
    if($num_agestaggeredroles != 1)
    {
        $html = '<a id="add_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'agestaggeredroles', 'conf' => $conf)).'">
            <i class="fas fa-trash-alt"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_CONFIG').'</a>';
        $formAgeStaggeredRoles->addCustomContent('', $html);
    }
    $formAgeStaggeredRoles->closeGroupBox();
}
$formAgeStaggeredRoles->addDescription('</div>');

$html = '<a id="add_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'agestaggeredroles', 'conf' => -1)).'">
    <i class="fas fa-clone"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_CONFIG').'</a>';
$htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SAVED_SETTINGS_LOST').'</div>';
$formAgeStaggeredRoles->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc));
$formAgeStaggeredRoles->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'agestaggeredroles', $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES'), 'fas fa-user-clock', $formAgeStaggeredRoles->show()));
                    
// PANEL: FAMILY_ROLES                    

$formFamilyRoles = new HtmlForm('familyroles_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'familyroles')), $page, array('class' => 'form-preferences'));
$formFamilyRoles->addDescription('<div style="width:100%; height:'.($num_familyroles<2 ? 500 : 650).'px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_familyroles; $conf++)
{
    $formFamilyRoles->openGroupBox('familyroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE'));
    $formFamilyRoles->addInput('familienrollen_prefix'.$conf, $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX'), $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_PREFIX_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
    $formFamilyRoles->addInput('familienrollen_beitrag'.$conf, $gL10n->get('SYS_CONTRIBUTION').' '.$gSettingsManager->getString('system_currency'), $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_CONTRIBUTION_DESC'));

    $selectBoxEntries = array('--', -1, 1, 2, 4, 12);
    $role = new TableRoles($gDb);
    $formFamilyRoles->addSelectBox('familienrollen_zeitraum'.$conf, $gL10n->get('SYS_CONTRIBUTION_PERIOD'), $role->getCostPeriods(), array('firstEntry' => '', 'defaultValue' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$conf], 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_CONTRIBUTION_PERIOD_DESC', 'showContextDependentFirstEntry' => false));
    $formFamilyRoles->addInput('familienrollen_beschreibung'.$conf, $gL10n->get('SYS_DESCRIPTION'), $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_DESCRIPTION_DESC'));
    if($num_familyroles != 1)
    {
        $html = '<a id="add_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'familyroles', 'conf' => $conf)).'">
            <i class="fas fa-trash-alt"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_CONFIG').'</a>';
        $formFamilyRoles->addCustomContent('', $html);
    }
    $formFamilyRoles->closeGroupBox();
}
$formFamilyRoles->addDescription('</div>');
$html = '<a id="add_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'familyroles', 'conf' => -1)).'">
    <i class="fas fa-clone"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_CONFIG').'</a>';
$htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SAVED_SETTINGS_LOST').'</div>';
$formFamilyRoles->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc));
$formFamilyRoles->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'familyroles', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'), 'fas fa-user-friends', $formFamilyRoles->show()));
              
// PANEL: ACCOUNT_DATA                    

$formAccountData = new HtmlForm('accountdata_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'accountdata')), $page, array('class' => 'form-preferences'));
$formAccountData->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA_INFO'));
$formAccountData->addInput('iban', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN'), $pPreferences->config['Kontodaten']['iban'], array('property' => HtmlForm::FIELD_REQUIRED));
$formAccountData->addInput('bic', $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC'), $pPreferences->config['Kontodaten']['bic']);
$formAccountData->addInput('bank', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK'), $pPreferences->config['Kontodaten']['bank'], array('property' => HtmlForm::FIELD_REQUIRED));

if($getChoice == 'accountdata')
{
    $formAccountData->addInput('creditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_CREDITOR'), $pPreferences->config['Kontodaten']['inhaber'], array('property' => HtmlForm::FIELD_REQUIRED));
    $html = '<a class="iconLink" id="creditorschieben" href="javascript:creditorschieben()">
        <i class="fas fa-arrow-down" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_CREDITOR').'"></i></a>';
   
    $formAccountData->addCustomContent('', $html);
    $formAccountData->addInput('origcreditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CREDITOR'), $pPreferences->config['Kontodaten']['origcreditor']);

    $formAccountData->addInput('ci', $gL10n->get('PLG_MITGLIEDSBEITRAG_CI'), $pPreferences->config['Kontodaten']['ci'], array('property' => HtmlForm::FIELD_REQUIRED));
    $html = '<a class="iconLink" id="cischieben" href="javascript:cischieben()">
       <i class="fas fa-arrow-down" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_CI').'"></i></a>';
    $formAccountData->addCustomContent('', $html);
    $formAccountData->addInput('origci', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CI'), $pPreferences->config['Kontodaten']['origci']);
    $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE_CDTR_INFO').'</div>';
    $formAccountData->addCustomContent('', $html);
}
else
{
    $formAccountData->addInput('creditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_CREDITOR'), $pPreferences->config['Kontodaten']['inhaber'], array('property' => HtmlForm::FIELD_REQUIRED));
    if(!empty($pPreferences->config['Kontodaten']['origcreditor']))
    {
        $formAccountData->addInput('origcreditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CREDITOR'), $pPreferences->config['Kontodaten']['origcreditor']);
    }
    $formAccountData->addInput('ci', $gL10n->get('PLG_MITGLIEDSBEITRAG_CI'), $pPreferences->config['Kontodaten']['ci'], array('property' => HtmlForm::FIELD_REQUIRED));
    if(!empty($pPreferences->config['Kontodaten']['origci']))
    {
        $formAccountData->addInput('origci', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CI'), $pPreferences->config['Kontodaten']['origci']);
    }

    $html = '<a class="icon-text-info" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'accountdata')).'">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'</a>';
    $formAccountData->addCustomContent('', $html, array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE_DESC'));
}
$formAccountData->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'accountdata', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA'), 'fas fa-money-check', $formAccountData->show()));
                                     
// PANEL: EXPORT                    

$formExport = new HtmlForm('configurations_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'export')), $page, array('class' => 'form-preferences'));
$formExport->openGroupBox('sepa', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA'));
$formExport->addInput('dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_XML_FILE_NAME'), $pPreferences->config['SEPA']['dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_XML_FILE_NAME_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
$formExport->addInput('kontroll_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE_NAME'), $pPreferences->config['SEPA']['kontroll_dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_CONTROL_FILE_NAME_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
$formExport->addInput('vorabinformation_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_FILE_NAME'), $pPreferences->config['SEPA']['vorabinformation_dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_FILE_NAME_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
$formExport->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_MAIL_TEXT'),
    '<p>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_MAIL_TEXT_DESC').':</p><p>
    <strong>#user_first_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FIRST_NAME').'<br />
    <strong>#user_last_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_LAST_NAME').'<br />
    <strong>#organization_long_name#</strong> - '.$gL10n->get('ORG_VARIABLE_NAME_ORGANIZATION').'<br />
    <strong>#fee#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FEE').'<br />
    <strong>#due_day#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_DUE_DAY').'<br />
    <strong>#mandate_id#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_MANDATE_ID').'<br />
    <strong>#creditor_id#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_CREDITOR_ID').'<br />
    <strong>#iban#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_IBAN').'<br />
	<strong>#iban_obfuscated#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN_OBFUSCATED').'<br />
    <strong>#debtor#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_DEBTOR').'<br />
    <strong>#membership_fee_text#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_MEMBERSHIP_FEE_TEXT').'</p>');

$text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => ORG_ID));
//wenn noch nichts drin steht, dann vorbelegen
if ($text->getValue('txt_text') == '')
{
    // convert <br /> to a normal line feed
    $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_PRE_NOTIFICATION'));
    $text->setValue('txt_text', $value);
    $text->save();
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => ORG_ID));
}
$formExport->addMultilineTextInput('pre_notification_text', '', $text->getValue('txt_text'), 7);
$formExport->closeGroupBox();
$formExport->openGroupBox('sepa', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_EXPORT'));
$formExport->addInput('rechnung_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_FILE_NAME'), $pPreferences->config['Rechnungs-Export']['rechnung_dateiname']);
$formExport->closeGroupBox();
$formExport->addDescription('');
$formExport->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'export', $gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT'), 'fas fa-file-export', $formExport->show()));
                                   
// PANEL: MANDATE_MANAGEMENT
                    
$formMandateManagement = new HtmlForm('mandatemanagement_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'mandatemanagement')), $page, array('class' => 'form-preferences'));
$formMandateManagement->addInput('prefix_fam', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX_FAM'), $pPreferences->config['Mandatsreferenz']['prefix_fam']);
$formMandateManagement->addInput('prefix_mem', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX_MEM'), $pPreferences->config['Mandatsreferenz']['prefix_mem']);
$formMandateManagement->addInput('prefix_pay', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX_PAY'), $pPreferences->config['Mandatsreferenz']['prefix_pay']);
$formMandateManagement->addInput('min_length', $gL10n->get('PLG_MITGLIEDSBEITRAG_MIN_LENGTH'), $pPreferences->config['Mandatsreferenz']['min_length'], array('type' => 'number', 'minNumber' => 5, 'maxNumber' => 35));

$configSelection = array();
$i  = 0;
foreach($gProfileFields->getProfileFields() as $field)
{
    $configSelection[$i][0]   = $field->getValue('usf_name_intern');
    $configSelection[$i][1]   = addslashes($field->getValue('usf_name'));
    $configSelection[$i][2]   = $field->getValue('cat_name');
    $i++;
}
$configSelection[$i][0]   = '-- User_ID --';
$configSelection[$i][1]   = '-- User_ID --';
$configSelection[$i][2]   = $gL10n->get('PLG_MITGLIEDSBEITRAG_ADDITIONAL_FIELDS');
$formMandateManagement->addSelectBox('data_field', $gL10n->get('PLG_MITGLIEDSBEITRAG_DATA_FIELD_SERIAL_NUMBER'), $configSelection, array('defaultValue' => $pPreferences->config['Mandatsreferenz']['data_field'], 'showContextDependentFirstEntry' => false));
$formMandateManagement->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT_DESC'));
$formMandateManagement->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'mandatemanagement', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT'), 'fas fa-puzzle-piece', $formMandateManagement->show()));
                                       
// PANEL: ROLE_TEST
                    
$formTestsSetup = new HtmlForm('testssetup_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'testssetup')), $page, array('class' => 'form-preferences'));
$formTestsSetup->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_TEST_SETUP_INFO'));
$formTestsSetup->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').'</strong>');
$formTestsSetup->addDescription('<div style="width:100%; height:'.($num_familyroles<2 ? 140 : 160).'px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_familyroles; $conf++)
{
    $formTestsSetup->openGroupBox('familyroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE'));
    $formTestsSetup->addInput('familienrollen_pruefung'.$conf, $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$conf]);
    $formTestsSetup->closeGroupBox();
}
$formTestsSetup->addDescription('</div>');
$formTestsSetup->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_DESC2'));
$formTestsSetup->addLine();
$familienrollen = beitragsrollen_einlesen('fam');
$altersrollen = beitragsrollen_einlesen('alt');
$fixrollen = beitragsrollen_einlesen('fix');
$formTestsSetup->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY'), '', array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY_DESC2'));
if ((count($altersrollen) > 0) || (count($familienrollen) > 0) || (count($fixrollen) > 0))
{
    $formTestsSetup->addDescription('<div style="width:100%; height:250px; overflow:auto; border:20px;">');
    if (count($altersrollen) > 0)
    {
        foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
        {
            $formTestsSetup->addCheckbox('altersrollenpflicht'.$token, $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$token .')', (in_array($token, $pPreferences->config['Rollenpruefung']['altersrollenpflicht']) ? 1 : 0));
        }
    }
    if (count($familienrollen) > 0)
    {
        $formTestsSetup->addCheckbox('familienrollenpflicht', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'), $pPreferences->config['Rollenpruefung']['familienrollenpflicht']);
    }
    if (count($fixrollen) > 0)
    {
        foreach($fixrollen as $key => $data)
        {
            $formTestsSetup->addCheckbox('fixrollenpflicht'.$key, $data['rolle'], (in_array($key, $pPreferences->config['Rollenpruefung']['fixrollenpflicht']) ? 1 : 0));
        }
    }
    $formTestsSetup->addDescription('</div>');
}
else
{
    $formTestsSetup->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_NO_CONTRIBUTION_ROLES'));
}
$formTestsSetup->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION'), '', array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION_DESC2'));
if (((count($altersrollen) > 0) && (count($familienrollen) > 0)) || count($fixrollen) > 0)
{
    $formTestsSetup->addDescription('<div style="width:100%; height:250px; overflow:auto; border:20px;">');
    if ((count($pPreferences->config['Altersrollen']['altersrollen_token'])>1))
    {
        for ($x = 0; $x < count($pPreferences->config['Altersrollen']['altersrollen_token'])-1; $x++)
        {
            for ($y = $x+1; $y < count($pPreferences->config['Altersrollen']['altersrollen_token']); $y++)
            {
                $formTestsSetup->addCheckbox('altersrollenaltersrollen'.$pPreferences->config['Altersrollen']['altersrollen_token'][$x].$pPreferences->config['Altersrollen']['altersrollen_token'][$y], $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$pPreferences->config['Altersrollen']['altersrollen_token'][$x].') ./. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$pPreferences->config['Altersrollen']['altersrollen_token'][$y].')', (in_array($pPreferences->config['Altersrollen']['altersrollen_token'][$x].','.$pPreferences->config['Altersrollen']['altersrollen_token'][$y], $pPreferences->config['Rollenpruefung']['altersrollenaltersrollen']) ? 1 : 0));
            }
        }
    }
    if ((count($altersrollen) > 0) && (count($familienrollen) > 0))
    {
        foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
        {
            $formTestsSetup->addCheckbox('altersrollenfamilienrollen'.$token, $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$token .') ./. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'), (in_array($token, $pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen']) ? 1 : 0));
        }
    }
    if ((count($altersrollen) > 0) && (count($fixrollen) > 0))
    {
        foreach($fixrollen as $key => $data)
        {
            foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
            {
                $formTestsSetup->addCheckbox('altersrollenfix'.$token.$key, $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$token .') ./. '.$data['rolle'], (in_array($token.$key, $pPreferences->config['Rollenpruefung']['altersrollenfix']) ? 1 : 0));
            }
        }
    }
    if ((count($familienrollen) > 0) && (count($fixrollen) > 0))
    {
        foreach($fixrollen as $key => $data)
        {
            $formTestsSetup->addCheckbox('familienrollenfix'.$key, $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').' ./. '.$data['rolle'], (in_array($key, $pPreferences->config['Rollenpruefung']['familienrollenfix']) ? 1 : 0));
        }
    }
    if (count($fixrollen) > 1)
    {
    	$fixrollenL = $fixrollen;
    	array_pop($fixrollenL);						// das letzte Element entfernen
    	$fixrollenR = $fixrollen;
    	
    	foreach ($fixrollenL as $keyL => $dataL)
    	{
    		unset($fixrollenR[$keyL]);				// dasselbe Element entfernen
    		foreach ($fixrollenR as $keyR=> $dataR)
    		{
    		    $formTestsSetup->addCheckbox('fixrollenfixrollen'.$keyL.'_'.$keyR, $dataL['rolle'].' ./. '.$dataR['rolle'], (in_array($keyL.'_'.$keyR, $pPreferences->config['Rollenpruefung']['fixrollenfixrollen']) ? 1 : 0));
    		}
    	}
    	unset($fixrollenL);
    	unset($fixrollenR);
    }
    $formTestsSetup->addDescription('</div>');
}
else
{
    $formTestsSetup->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_NO_COMBINATION_ROLES'));
}

$sql = 'SELECT cat_id, cat_name
          FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.'
         WHERE cat_id = rol_cat_id
           AND ( cat_org_id = '.ORG_ID.'
            OR cat_org_id IS NULL )';
$formTestsSetup->addSelectBoxFromSql('bezugskategorie', $gL10n->get('PLG_MITGLIEDSBEITRAG_CAT_SELECTION'), $gDb, $sql, array('defaultValue' => $pPreferences->config['Rollenpruefung']['bezugskategorie'], 'multiselect' => true, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CAT_SELECTION_DESC'));
$formTestsSetup->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'testssetup', $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_TEST'), 'fas fa-check-double', $formTestsSetup->show()));
                             
// PANEL: VIEW_DEFINITIONS

$formColumnSet = new HtmlForm('columnset_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'columnset')), $page, array('class' => 'form-preferences'));
$formColumnSet->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS_HEADER'));
$formColumnSet->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');

foreach ($pPreferences->config['columnconfig'] as $conf => $confFields)
{
	$groupHeader = '';
	switch($conf)
	{
		case 'payments_fields_normal_screen':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_PAYMENTS_FIELDS_NORMAL_SCREEN';
			break;
		case 'payments_fields_full_screen':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_PAYMENTS_FIELDS_FULL_SCREEN';
			break;
		case 'mandates_fields_normal_screen':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_MANDATES_FIELDS_NORMAL_SCREEN';
			break;
		case 'mandates_fields_full_screen':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_MANDATES_FIELDS_FULL_SCREEN';
			break;
		case 'duedates_fields_normal_screen':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_DUEDATES_FIELDS_NORMAL_SCREEN';
			break;
		case 'duedates_fields_full_screen':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_DUEDATES_FIELDS_FULL_SCREEN';
			break;
	}
	$formColumnSet->openGroupBox('configurations_group', $gL10n->get($groupHeader));
	
	$html = '
	<div class="table-responsive">
		<table class="table table-condensed" id="mylist_fields_table">
			<thead>
				<tr>
					<th style="width: 20%;">'.$gL10n->get('SYS_ABR_NO').'</th>
					<th style="width: 37%;">'.$gL10n->get('SYS_CONTENT').'</th>
				</tr>
			</thead>
			<tbody id="mylist_fields_tbody'.$conf.'">
				<tr id="table_row_button">
					<td colspan="2">
					
					    <a class="icon-text-link" href="javascript:addColumn'.$conf.'()"><i class="fas fa-plus-circle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_COLUMN').'</a>
                    </td>
				</tr>
			</tbody>
		</table>
	</div>';
	$formColumnSet->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_COLUMN_SELECTION'), $html);
	$formColumnSet->closeGroupBox();
}
$formColumnSet->addDescription('</div>');
$formColumnSet->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'columnset', $gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS'), 'fas fa-binoculars', $formColumnSet->show()));
                                
//PANEL: DEINSTALLATION
                    
$formDeinstallation = new HtmlForm('deinstallation_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'deinstallation')), $page, array('class' => 'form-preferences'));
$formDeinstallation->addButton('btn_deinstallation', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION'), array('icon' => 'fa-trash-alt', 'link' => 'deinstallation.php', 'class' => 'btn-primary offset-sm-3'));
$formDeinstallation->addCustomContent('', '<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION_DESC'));

$page->addHtml(getMenuePanel('preferences', 'deinstallation', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION'), 'fas fa-trash-alt', $formDeinstallation->show()));
   
// PANEL: ACCESS_PREFERENCES
                    
$formAccessPreferences = new HtmlForm('access_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'access_preferences')), $page, array('class' => 'form-preferences'));

$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' AS cat, '.TBL_ROLES.' AS rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND ( cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
            OR cat.cat_org_id IS NULL )';
$formAccessPreferences->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES_DESC', 'multiselect' => true, 'property' => HtmlForm::FIELD_REQUIRED));
$formAccessPreferences->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'access_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES'), 'fas fa-key', $formAccessPreferences->show()));

//PANEL: PLUGIN_INFORMATION                     

$formPluginInformations = new HtmlForm('plugin_informations_form', null, $page);
$formPluginInformations->addStaticControl('plg_name', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_NAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'));
$formPluginInformations->addStaticControl('plg_version', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
$formPluginInformations->addStaticControl('plg_date', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);

$html = '<a class="icon-text-link" href="https://www.admidio.org/dokuwiki/doku.php?id=de:plugins:mitgliedsbeitrag#mitgliedsbeitrag" target="_blank">
    <i class="fas fa-external-link-square-alt"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_DOCUMENTATION_OPEN').'</a>';

$formPluginInformations->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_DOCUMENTATION'), $html, array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DOCUMENTATION_OPEN_DESC'));
  
$page->addHtml(getMenuePanel('preferences', 'plugin_informations', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_INFORMATION'), 'fas fa-info', $formPluginInformations->show()));
                        

$page->addHtml('
        </div>
    </div>
</div>');

$page->show();
