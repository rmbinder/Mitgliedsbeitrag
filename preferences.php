<?php
/**
 ***********************************************************************************************
 * Erzeugt das Einstellungen-Menue fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2022 The Admidio Team
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
elseif ($getChoice == 'individualcontributions')
{
    if ($getConf == -1)
    {
        $pPreferences->config['individual_contributions']['desc'][] = $pPreferences->config_default['individual_contributions']['desc'][0];
        $pPreferences->config['individual_contributions']['short_desc'][] = $pPreferences->config_default['individual_contributions']['short_desc'][0];
        $pPreferences->config['individual_contributions']['role'][] = $pPreferences->config_default['individual_contributions']['role'][0];
        $pPreferences->config['individual_contributions']['amount'][] = $pPreferences->config_default['individual_contributions']['amount'][0];
        $pPreferences->config['individual_contributions']['profilefield'][] = $pPreferences->config_default['individual_contributions']['profilefield'][0];
    }
    else
    {
        array_splice($pPreferences->config['individual_contributions']['desc'], $getConf, 1);
        array_splice($pPreferences->config['individual_contributions']['short_desc'], $getConf, 1);
        array_splice($pPreferences->config['individual_contributions']['role'], $getConf, 1);
        array_splice($pPreferences->config['individual_contributions']['amount'], $getConf, 1);
        array_splice($pPreferences->config['individual_contributions']['profilefield'], $getConf, 1);
    }
}

$num_agestaggeredroles = count($pPreferences->config['Altersrollen']['altersrollen_token']);
$num_familyroles = count($pPreferences->config['Familienrollen']['familienrollen_prefix']);
$num_individualcontributions = count($pPreferences->config['individual_contributions']['desc']);

$familienrollen = beitragsrollen_einlesen('fam');
$altersrollen = beitragsrollen_einlesen('alt');
$fixrollen = beitragsrollen_einlesen('fix');
$alt_fix_rollen = $altersrollen + $fixrollen;

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

$page = new HtmlPage('plg-mitgliedsbeitrag-preferences', $headline);

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
        	newCellCount.innerHTML = (fieldNumberShow) + ".&nbsp;'.$gL10n->get('SYS_COLUMN').'&nbsp;:";
        			
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
$formContributionSettings->addCheckbox('beitrag_anteilig', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PRORATA'), $pPreferences->config['Beitrag']['beitrag_anteilig'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PRORATA_DESC', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PRORATA_DESC2'));
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
$formContributionSettings->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'contributionsettings', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SETTINGS'), 'fas fa-edit', $formContributionSettings->show()));
                    
// PANEL: AGE_STAGGERED_ROLES                    
                    
$formAgeStaggeredRoles = new HtmlForm('agestaggeredroles_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'agestaggeredroles')), $page, array('class' => 'form-preferences'));
$formAgeStaggeredRoles->addInput('altersrollen_offset', $gL10n->get('PLG_MITGLIEDSBEITRAG_OFFSET'), $pPreferences->config['Altersrollen']['altersrollen_offset'], array('type' => 'number',  'step' => 1, 'minNumber' => -99, 'maxNumber' => 99, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_OFFSET_DESC', 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_OFFSET_INFO') );  
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
$htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SAVED_SETTINGS_LOST').'</div>';
$formAgeStaggeredRoles->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc));
$formAgeStaggeredRoles->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'agestaggeredroles', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES'), 'fas fa-user-clock', $formAgeStaggeredRoles->show()));
                    
// PANEL: FAMILY_ROLES                    

$formFamilyRoles = new HtmlForm('familyroles_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'familyroles')), $page, array('class' => 'form-preferences'));
$formFamilyRoles->addDescription('<div style="width:100%; height:'.($num_familyroles<2 ? 500 : 650).'px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_familyroles; $conf++)
{
    $formFamilyRoles->openGroupBox('familyroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE'));
    $formFamilyRoles->addInput('familienrollen_prefix'.$conf, $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX'), $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_PREFIX_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
    $formFamilyRoles->addInput('familienrollen_beitrag'.$conf, $gL10n->get('SYS_CONTRIBUTION').' '.$gSettingsManager->getString('system_currency'), $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_CONTRIBUTION_DESC', 'type' => 'number', 'minNumber' => -99999, 'maxNumber' => 99999, 'step' => 0.01));

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
$htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SAVED_SETTINGS_LOST').'</div>';
$formFamilyRoles->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc));
$formFamilyRoles->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'familyroles', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'), 'fas fa-user-friends', $formFamilyRoles->show()));

// PANEL: MULTIPLIER_ROLES

if (count($familienrollen) > 0)
{
    $formMultiplierRoles = new HtmlForm('multiplier_roles_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'multiplier_roles')), $page, array('class' => 'form-preferences'));
    
    $selectBoxEntries = array();
    foreach ($familienrollen as $key => $data)
    {
        $selectBoxEntries[$key] = $data['rolle'];
    }
    asort($selectBoxEntries);
    $formMultiplierRoles->addSelectBox('multiplier_roles', '', $selectBoxEntries, array('defaultValue' => $pPreferences->config['multiplier']['roles'], 'multiselect' => true, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_MULTIPLIER_ROLES_DESC'));
    
    $formMultiplierRoles->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));
    
    $page->addHtml(getMenuePanel('preferences', 'multiplier_roles', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_MULTIPLIER_ROLES'), 'fas fa-percent', $formMultiplierRoles->show()));
}

// PANEL: ADVANCED_ROLE_EDITING

$formAdvancedRoleEditing = new HtmlForm('advancedroleediting_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'advancedroleediting')), $page, array('class' => 'form-preferences'));
$formAdvancedRoleEditing->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ADVANCED_ROLE_EDITING_DESC'));

$formAdvancedRoleEditing->addDescription('<div style="width:100%; height:250px; overflow:auto; border:20px;">');

foreach($alt_fix_rollen as $key => $data)
{
    $formAdvancedRoleEditing->openGroupBox('advancedroleediting_group', $data['rolle']);
    
    $formAdvancedRoleEditing->addInput('rol_cost'.$key, $gL10n->get('SYS_CONTRIBUTION').' '.$gSettingsManager->getString('system_currency'), $data['rol_cost'], array('type' => 'number', 'minNumber' => -99999, 'maxNumber' => 99999, 'step' => 0.01));
    $formAdvancedRoleEditing->addSelectBox('rol_cost_period'.$key, $gL10n->get('SYS_CONTRIBUTION_PERIOD'), TableRoles::getCostPeriods(), array('defaultValue' => $data['rol_cost_period']));
    $formAdvancedRoleEditing->addInput('rol_description'.$key, $gL10n->get('SYS_DESCRIPTION'), $data['rol_description']);
    
    $formAdvancedRoleEditing->closeGroupBox();
}

$formAdvancedRoleEditing->addDescription('</div>');
$formAdvancedRoleEditing->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'advancedroleediting', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_ADVANCED_ROLE_EDITING'), 'fas fa-users-cog', $formAdvancedRoleEditing->show()));

// PANEL: INDIVIDUAL_CONTRIBUTIONS

$formIndividualContributionsSetup = new HtmlForm('individual_contributions_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'individualcontributions')), $page, array('class' => 'form-preferences'));

$selectBoxEntries = array(
    '0' => $gL10n->get('SYS_DISABLED'),
    '1' => $gL10n->get('SYS_ENABLED'));
$formIndividualContributionsSetup->addSelectBox(
    'enable_individual_contributions', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESS_TO_MODULE_INDIVIDUAL_CONTRIBUTIONS'), $selectBoxEntries,
    array('defaultValue' => $pPreferences->config['individual_contributions']['access_to_module'], 'showContextDependentFirstEntry' => false, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ACCESS_TO_MODULE_INDIVIDUAL_CONTRIBUTIONS_DESC'));

$formIndividualContributionsSetup->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS_DESC'));
$formIndividualContributionsSetup->addLine();

$formIndividualContributionsSetup->addDescription('<div style="width:100%; height:'.($num_individualcontributions<2 ? 500 : 650).'px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_individualcontributions; $conf++)
{
    $formIndividualContributionsSetup->openGroupBox('individualcontributions_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONFIGURATION'));
    $formIndividualContributionsSetup->addInput('individual_contributions_desc'.$conf, $gL10n->get('PLG_MITGLIEDSBEITRAG_DESCRIPTION'), $pPreferences->config['individual_contributions']['desc'][$conf], array( 'property' => HtmlForm::FIELD_REQUIRED, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_DESCRIPTION_DESC'));
    $formIndividualContributionsSetup->addInput('individual_contributions_short_desc'.$conf, $gL10n->get('PLG_MITGLIEDSBEITRAG_SHORT_DESCRIPTION'), $pPreferences->config['individual_contributions']['short_desc'][$conf], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_SHORT_DESCRIPTION_DESC'));
    
    $sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
              FROM '.TBL_CATEGORIES.' as cat, '.TBL_ROLES.' as rol
             WHERE cat.cat_id = rol.rol_cat_id
               AND ( cat.cat_org_id = '.$gCurrentOrgId.'
                OR cat.cat_org_id IS NULL )
          ORDER BY cat.cat_name DESC';
    $formIndividualContributionsSetup->addSelectBoxFromSql('individual_contributions_role'.$conf,  $gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE'), $gDb, $sql, array('defaultValue' => $pPreferences->config['individual_contributions']['role'][$conf],  'multiselect' => false, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_ROLE_DESC'));
    
    $formIndividualContributionsSetup->addInput('individual_contributions_amount'.$conf, $gL10n->get('PLG_MITGLIEDSBEITRAG_AMOUNT'), $pPreferences->config['individual_contributions']['amount'][$conf], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_AMOUNT_DESC'));
    
    $fieldSelectionList2 = array();
    
    foreach ($gProfileFields->getProfileFields() as $field)
    {
        if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->editUsers())
        {
            $fieldSelectionList2[] = array($field->getValue('usf_id'), addslashes($field->getValue('usf_name')), $field->getValue('cat_name') );
        }
    }
    
    $formIndividualContributionsSetup->addSelectBox('individual_contributions_profilefield'.$conf, $gL10n->get('SYS_PROFILE_FIELD'), $fieldSelectionList2, array('firstEntry' => '', 'defaultValue' => $pPreferences->config['individual_contributions']['profilefield'][$conf], 'showContextDependentFirstEntry' => true, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_PROFILE_FIELD_DESC'));
    
    if($num_individualcontributions != 1)
    {
        $html = '<a id="add_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'individualcontributions', 'conf' => $conf)).'">
        <i class="fas fa-trash-alt"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_CONFIG').'</a>';
        $formIndividualContributionsSetup->addCustomContent('', $html);
    }
    $formIndividualContributionsSetup->closeGroupBox();
}
$formIndividualContributionsSetup->addDescription('</div>');
$html = '<a id="add_config" class="icon-text-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', array('choice' => 'individualcontributions', 'conf' => -1)).'">
    <i class="fas fa-clone"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_CONFIG').'</a>';
$htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SAVED_SETTINGS_LOST').'</div>';
$formIndividualContributionsSetup->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc));
$formIndividualContributionsSetup->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'individualcontributions', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_INDIVIDUAL_CONTRIBUTIONS'), 'fas fa-coins', $formIndividualContributionsSetup->show()));

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
    $html = '<div class="alert alert-warning alert-small" role="alert"><i class="fas fa-exclamation-triangle"></i>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE_CDTR_INFO').'</div>';
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

$page->addHtml(getMenuePanel('preferences', 'accountdata', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA'), 'fas fa-money-check', $formAccountData->show()));
                                                                        
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

$page->addHtml(getMenuePanel('preferences', 'mandatemanagement', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT'), 'fas fa-puzzle-piece', $formMandateManagement->show()));
                                       
// PANEL: VIEW_DEFINITIONS

$formColumnSet = new HtmlForm('columnset_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'columnset')), $page, array('class' => 'form-preferences'));
$formColumnSet->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS_HEADER'));
$formColumnSet->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');

foreach ($pPreferences->config['columnconfig'] as $conf => $confFields)
{
	$groupHeader = '';
	switch($conf)
	{
		case 'payments_fields':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_PAYMENTS_FIELDS';
			break;
		case 'mandates_fields':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_MANDATES_FIELDS';
			break;
		case 'duedates_fields':
			$groupHeader= 'PLG_MITGLIEDSBEITRAG_DUEDATES_FIELDS';
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

$page->addHtml(getMenuePanel('preferences', 'columnset', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS'), 'fas fa-binoculars', $formColumnSet->show()));

// PANEL: EXPORT

$selectBoxEntries =  array('xlsx' => $gL10n->get('SYS_MICROSOFT_EXCEL').' (XLSX)', 'csv-ms' => $gL10n->get('SYS_MICROSOFT_EXCEL').' (CSV)', 'csv-oo' => $gL10n->get('SYS_CSV').' ('.$gL10n->get('SYS_UTF8').')' );

$formExport = new HtmlForm('configurations_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'export')), $page, array('class' => 'form-preferences'));

$formExport->openGroupBox('sepa', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA'));
$formExport->addInput('dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_XML_FILE_NAME'), $pPreferences->config['SEPA']['dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_XML_FILE_NAME_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
$formExport->addInput('kontroll_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE_NAME'), $pPreferences->config['SEPA']['kontroll_dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_CONTROL_FILE_NAME_DESC', 'property' => HtmlForm::FIELD_REQUIRED));
$formExport->addSelectBox('kontroll_dateityp', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE_TYPE'), $selectBoxEntries, array('defaultValue' => $pPreferences->config['SEPA']['kontroll_dateityp'], 'showContextDependentFirstEntry' => false));
$formExport->addInput('vorabinformation_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_FILE_NAME'), $pPreferences->config['SEPA']['vorabinformation_dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_NAME_WITHOUT_ENDING', 'property' => HtmlForm::FIELD_REQUIRED));
$formExport->addSelectBox('vorabinformation_dateityp', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_FILE_TYPE'), $selectBoxEntries, array('defaultValue' => $pPreferences->config['SEPA']['vorabinformation_dateityp'], 'showContextDependentFirstEntry' => false));
$formExport->closeGroupBox();

$formExport->openGroupBox('bill', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL'));
$formExport->addInput('rechnung_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL_FILE_NAME'), $pPreferences->config['Rechnungs-Export']['rechnung_dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_NAME_WITHOUT_ENDING', 'property' => HtmlForm::FIELD_REQUIRED));
$formExport->addSelectBox('rechnung_dateityp', $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL_FILE_TYPE'), $selectBoxEntries, array('defaultValue' => $pPreferences->config['Rechnungs-Export']['rechnung_dateityp'], 'showContextDependentFirstEntry' => false));
$formExport->closeGroupBox();

$formExport->addDescription('');
$formExport->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'export', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT'), 'fas fa-file-export', $formExport->show()));

//PANEL: EMAIL_NOTIFICATIONS

$formEmailNotifications = new HtmlForm('email_notifications_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'emailnotifications')), $page, array('class' => 'form-preferences'));

$formEmailNotifications->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_NOTIFICATIONS'),
    '<p>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_NOTIFICATIONS_DESC').':</p><p>
    <strong>#user_first_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FIRST_NAME').'<br />
    <strong>#user_last_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_LAST_NAME').'<br />
    <strong>#street#</strong> - '.$gL10n->get('SYS_STREET').'<br />
    <strong>#postcode#</strong> - '.$gL10n->get('SYS_POSTCODE').'<br />
    <strong>#city#</strong> - '.$gL10n->get('SYS_CITY').'<br />
    <strong>#email#</strong> - '.$gL10n->get('SYS_EMAIL').'<br />
    <strong>#phone#</strong> - '.$gL10n->get('SYS_PHONE').'<br />
    <strong>#mobile#</strong> - '.$gL10n->get('SYS_MOBILE').'<br />
    <strong>#birthday#</strong> - '.$gL10n->get('SYS_BIRTHDAY').'<br />
    <strong>#organization_long_name#</strong> - '.$gL10n->get('ORG_VARIABLE_NAME_ORGANIZATION').'<br />
    <strong>#fee#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FEE').'<br />
    <strong>#due_day#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_DUE_DAY').'<br />
    <strong>#mandate_id#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_MANDATE_ID').'<br />
    <strong>#mandate_date#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE').'<br />
    <strong>#creditor_id#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_CREDITOR_ID').'<br />
    <strong>#iban#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_IBAN').'<br />
	<strong>#iban_obfuscated#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN_OBFUSCATED').'<br />
    <strong>#bic#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_BIC').'<br />
    <strong>#bank#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_BANK').'<br />
    <strong>#debtor#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_DEBTOR').'<br />
    <strong>#membership_fee_text#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_MEMBERSHIP_FEE_TEXT').'</p>');

$text = new TableText($gDb);
$text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => $gCurrentOrgId));
if ($text->getValue('txt_text') == '')
{
    // convert <br /> to a normal line feed
    $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_CONTRIBUTION_PAYMENTS'));
    $text->setValue('txt_text', $value);
    $text->save();
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => $gCurrentOrgId));
}
$formEmailNotifications->addMultilineTextInput('mail_text', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS_MAIL_TEXT'), $text->getValue('txt_text'), 7);

$text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => $gCurrentOrgId));
if ($text->getValue('txt_text') == '')
{
    // convert <br /> to a normal line feed
    $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_PRE_NOTIFICATION'));
    $text->setValue('txt_text', $value);
    $text->save();
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => $gCurrentOrgId));
}
$formEmailNotifications->addMultilineTextInput('pre_notification_text', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_MAIL_TEXT'), $text->getValue('txt_text'), 7);

$text->readDataByColumns(array('txt_name' => 'PMBMAIL_BILL', 'txt_org_id' => $gCurrentOrgId));
if ($text->getValue('txt_text') == '')
{
    // convert <br /> to a normal line feed
    $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13).chr(10), $gL10n->get('PLG_MITGLIEDSBEITRAG_PMBMAIL_BILL'));
    $text->setValue('txt_text', $value);
    $text->save();
    $text->readDataByColumns(array('txt_name' => 'PMBMAIL_BILL', 'txt_org_id' => $gCurrentOrgId));
}
$formEmailNotifications->addMultilineTextInput('bill_text', $gL10n->get('PLG_MITGLIEDSBEITRAG_BILL_MAIL_TEXT'), $text->getValue('txt_text'), 7);

$formEmailNotifications->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'email_notifications', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_EMAIL_NOTIFICATIONS'), 'fas fa-envelope', $formEmailNotifications->show()));

// PANEL: TESTS

$formTestsSetup = new HtmlForm('testssetup_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'testssetup')), $page, array('class' => 'form-preferences'));

$formTestsSetup->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_TESTS_SETUP_INFO'));

$formTestsSetup->addCheckbox(
    'age_staggered_roles',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TEST').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['age_staggered_roles']
    );
$formTestsSetup->addLine();

$formTestsSetup->addCheckbox(
    'role_membership_age_staggered_roles',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TEST').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['role_membership_age_staggered_roles']
    );
$formTestsSetup->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES'), '', array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_DESC_LABEL', 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_DESC2'));
if (count($altersrollen) > 0 )
{
    if (count($altersrollen) > 0)
    {
        foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
        {
            $formTestsSetup->addCheckbox('age_staggered_roles_exclusion'.$token, $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$token .')', (in_array($token, $pPreferences->config['Rollenpruefung']['age_staggered_roles_exclusion']) ? 1 : 0));
        }
    }
}
else
{
    $formTestsSetup->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_NO_CONTRIBUTION_ROLES'));
}
$formTestsSetup->addLine();

$formTestsSetup->addCheckbox(
    'role_membership_duty_and_exclusion',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TESTS').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_AND').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['role_membership_duty_and_exclusion']
    );
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
           AND ( cat_org_id = '.$gCurrentOrgId.'
            OR cat_org_id IS NULL )';
$formTestsSetup->addSelectBoxFromSql('bezugskategorie', $gL10n->get('PLG_MITGLIEDSBEITRAG_CAT_SELECTION'), $gDb, $sql, array('defaultValue' => $pPreferences->config['Rollenpruefung']['bezugskategorie'], 'multiselect' => true, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CAT_SELECTION_DESC'));
$formTestsSetup->addLine();

$formTestsSetup->addCheckbox(
    'family_roles',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TEST').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['family_roles']
    );
$formTestsSetup->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'), '', array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_DESC2'));
$formTestsSetup->addDescription('<div style="width:100%; height:'.($num_familyroles<2 ? 140 : 300).'px; overflow:auto; border:20px;">');
for ($conf = 0; $conf < $num_familyroles; $conf++)
{
    $formTestsSetup->openGroupBox('familyroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE'));
    $formTestsSetup->addInput('familienrollen_pruefung'.$conf, $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$conf]);
    $formTestsSetup->closeGroupBox();
}
$formTestsSetup->addDescription('</div>');
$formTestsSetup->addLine();

$formTestsSetup->addCheckbox(
    'account_details',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TEST').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['account_details']
    );
$formTestsSetup->addLine();

$formTestsSetup->addCheckbox(
    'mandate_management',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TEST').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['mandate_management']
    );
$formTestsSetup->addLine();

$formTestsSetup->addCheckbox(
    'iban_check',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TEST').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBANCHECK').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['iban_check']
    );
$formTestsSetup->addLine();

$formTestsSetup->addCheckbox(
    'bic_check',
    $gL10n->get('PLG_MITGLIEDSBEITRAG_TEST').' "'.$gL10n->get('PLG_MITGLIEDSBEITRAG_BICCHECK').'" '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ENABLE'),
    (bool) $pPreferences->config['tests_enable']['bic_check']
    );
$formTestsSetup->addLine();

$formTestsSetup->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'testssetup', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_TESTS'), 'fas fa-check-double', $formTestsSetup->show()));

// PANEL: ACCESS_PREFERENCES

$formAccessPreferences = new HtmlForm('access_preferences_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'access_preferences')), $page, array('class' => 'form-preferences'));

$sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
          FROM '.TBL_CATEGORIES.' AS cat, '.TBL_ROLES.' AS rol
         WHERE cat.cat_id = rol.rol_cat_id
           AND ( cat.cat_org_id = '.$gCurrentOrgId.'
            OR cat.cat_org_id IS NULL )
      ORDER BY cat_sequence, rol.rol_name ASC';
$formAccessPreferences->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES_DESC', 'multiselect' => true));
$formAccessPreferences->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => 'fa-check', 'class' => ' offset-sm-3'));

$page->addHtml(getMenuePanel('preferences', 'access_preferences', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES'), 'fas fa-key', $formAccessPreferences->show()));

//PANEL: DEINSTALLATION

$formDeinstallation = new HtmlForm('deinstallation_form', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php', array('form' => 'deinstallation')), $page, array('class' => 'form-preferences'));
$formDeinstallation->addButton('btn_deinstallation', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION'), array('icon' => 'fa-trash-alt', 'link' => 'deinstallation.php', 'class' => 'btn-primary offset-sm-3'));
$formDeinstallation->addCustomContent('', '<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION_DESC'));

$page->addHtml(getMenuePanel('preferences', 'deinstallation', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION'), 'fas fa-trash-alt', $formDeinstallation->show()));

//PANEL: PLUGIN_INFORMATION                     

$formPluginInformations = new HtmlForm('plugin_informations_form', null, $page);
$formPluginInformations->addStaticControl('plg_name', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_NAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'));
$formPluginInformations->addStaticControl('plg_version', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
$formPluginInformations->addStaticControl('plg_date', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);

$html = '<a class="icon-text-link" href="https://www.admidio.org/dokuwiki/doku.php?id=de:plugins:mitgliedsbeitrag#mitgliedsbeitrag" target="_blank">
    <i class="fas fa-external-link-square-alt"></i> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_DOCUMENTATION_OPEN').'</a>';

$formPluginInformations->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_DOCUMENTATION'), $html, array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DOCUMENTATION_OPEN_DESC'));
  
$page->addHtml(getMenuePanel('preferences', 'plugin_informations', 'accordion_preferences', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_INFORMATION'), 'fas fa-info', $formPluginInformations->show()));
                        
$page->addHtml('
        </div>
    </div>
</div>');

$page->show();
