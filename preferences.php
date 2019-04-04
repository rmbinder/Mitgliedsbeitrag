<?php
/**
 ***********************************************************************************************
 * Erzeugt das Einstellungen-Menue fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2019 The Admidio Team
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
$page = new HtmlPage($headline);

// open the modules tab if the options of a module should be shown
if ($getChoice != '')
{
    $page->addJavascript('$("#tabs_nav_preferences").attr("class", "active");
        $("#tabs-preferences").attr("class", "tab-pane active");
        $("#collapse_'.$getChoice.'").attr("class", "panel-collapse collapse in");
        location.hash = "#" + "panel_'.$getChoice.'";', true);
}
else
{
    $page->addJavascript('$("#tabs_nav_preferences").attr("class", "active");
        $("#tabs-preferences").attr("class", "tab-pane active");
        ', true);
}

$page->addJavascript('function cischieben(){
        var ci = $("input[type=text]#ci").val();
        var origci = $("input[type=text]#origci").val(ci);
        $("input[type=text]#ci").val("");
    };
    function creditorschieben(){
        var creditor = $("input[type=text]#creditor").val();
        var origcreditor = $("input[type=text]#origcreditor").val(creditor);
        $("input[type=text]#creditor").val("");
    };
');            // !!!: ohne true

$page->addJavascript('
    $(".form-preferences").submit(function(event) {
        var id = $(this).attr("id");
        var action = $(this).attr("action");
        $("#"+id+" .form-alert").hide();

        // disable default form submit
        event.preventDefault();

        $.ajax({
            type:    "POST",
            url:     action,
            data:    $(this).serialize(),
            success: function(data) {
                if(data == "success") {
                    $("#"+id+" .form-alert").attr("class", "alert alert-success form-alert");
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-ok\"></span><strong>'.$gL10n->get('SYS_SAVE_DATA').'</strong>");
                    $("#"+id+" .form-alert").fadeIn("slow");
                    $("#"+id+" .form-alert").animate({opacity: 1.0}, 2500);
                    $("#"+id+" .form-alert").fadeOut("slow");
                }
                else if(data == "convert_error") {
                    $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NO_DATA_TO_CONVERT').'</strong>");
                    $("#"+id+" .form-alert").fadeIn("slow");
                    $("#"+id+" .form-alert").animate({opacity: 1.0}, 10000);
                    $("#"+id+" .form-alert").fadeOut("slow");
                }
                else {
                    $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                    $("#"+id+" .form-alert").fadeIn();
                    $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span>"+data);
                }
            }
        });
    });
', true);

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

// create module menu with back link
$headerMenu = new HtmlNavbar('menu_preferences', $headline, $page);
$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php', $gL10n->get('SYS_UPDATE'), 'update_link.png', 'right');
$headerMenu->addItem('menu_item_back', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/mitgliedsbeitrag.php', $gL10n->get('SYS_BACK'), 'back.png');

$page->addHtml($headerMenu->show(false));

$page->addHtml('
<ul class="nav nav-tabs" id="preferences_tabs">
    <li id="tabs_nav_preferences"><a href="#tabs-preferences" data-toggle="tab">'.$gL10n->get('SYS_SETTINGS').'</a></li>
</ul>

<div class="tab-content">
    <div class="tab-pane" id="tabs-preferences">
        <div class="panel-group" id="accordion_preferences">
            <div class="panel panel-default" id="panel_contributionsettings">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_contributionsettings">
                            <img src="'.THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SETTINGS').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SETTINGS').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SETTINGS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_contributionsettings" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=contributionsettings', $page, array('class' => 'form-preferences'));
                        $form->addInput('beitrag_prefix', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX'), $pPreferences->config['Beitrag']['beitrag_prefix'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PREFIX_DESC'));
                        $form->addInput('beitrag_suffix', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SUFFIX'), $pPreferences->config['Beitrag']['beitrag_suffix'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SUFFIX_DESC'));
                        $form->addCheckbox('beitrag_anteilig', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PRORATA'), $pPreferences->config['Beitrag']['beitrag_anteilig'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PRORATA_DESC'));
                        $form->addCheckbox('beitrag_abrunden', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_ROUNDDOWN'), $pPreferences->config['Beitrag']['beitrag_abrunden'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_ROUNDDOWN_DESC'));
                        $form->addInput('beitrag_mindestbetrag', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_MINCALC').' '.$gPreferences['system_currency'], $pPreferences->config['Beitrag']['beitrag_mindestbetrag'], array('type' => 'number', 'minNumber' => 0, 'maxNumber' => 999, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_MINCALC_DESC'));
                        $form->addCheckbox('beitrag_textmitnam', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_MEMNAMES'), $pPreferences->config['Beitrag']['beitrag_textmitnam'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_MEMNAMES_DESC'));
                        $form->addCheckbox('beitrag_textmitfam', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_FAMNAMES'), $pPreferences->config['Beitrag']['beitrag_textmitfam'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_FAMNAMES_DESC'));
                        $selectBoxEntries = array('#' => ' &nbsp '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_BLANK'),
                                                  '.' => '. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_DOT'),
                                                  ',' => ', '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_COMMA'),
                                                  '-' => '- '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_HYPHEN'),
                                                  '/' => '/ '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_SLASH'),
                                                  '+' => '+ '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PLUS'),
                                                  '*' => '* '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TIMES').'(*)',
                                                  '%' => '% '.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PERCENT').'(*)');
                        $form->addSelectBox('beitrag_text_token', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_TOKEN'), $selectBoxEntries, array('defaultValue' => $pPreferences->config['Beitrag']['beitrag_text_token'], 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CONTRIBUTION_TEXT_TOKEN_DESC', 'showContextDependentFirstEntry' => false));

                        $form->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS_MAIL_TEXT'),
                            '<p>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_PAYMENTS_MAIL_TEXT_DESC').':</p>
                                    <p><strong>#user_first_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FIRST_NAME').'<br />
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
                        $form->addMultilineTextInput('mail_text', '', $text->getValue('txt_text'), 7);
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

             <div class="panel panel-default" id="panel_agestaggeredroles">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_agestaggeredroles">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_agestaggeredroles" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=agestaggeredroles', $page, array('class' => 'form-preferences'));
                        $form->addInput('altersrollen_stichtag', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEADLINE'), $pPreferences->config['Altersrollen']['altersrollen_stichtag'], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DEADLINE_DESC', 'type' => 'date'));
                        $form->addLine();
                        $form->addStaticControl('descd', $gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER'), '', array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DELIMITER_DESC'));
                        $html = $gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER_INFO1').'<strong><br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER_INFO2').' </strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELIMITER_INFO3');
                        $form->addCustomContent('', $html);
                        $form->addDescription('<div style="width:100%; height:'.($num_agestaggeredroles<2 ? 170 : 210).'px; overflow:auto; border:20px;">');
                        for ($conf = 0; $conf < $num_agestaggeredroles; $conf++)
                        {
                            $form->openGroupBox('agestaggeredroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_STAGGERING'));
                            $form->addInput('altersrollen_token'.$conf, '', $pPreferences->config['Altersrollen']['altersrollen_token'][$conf], array('maxLength' => 1, 'property' => FIELD_REQUIRED));
                            if($num_agestaggeredroles != 1)
                            {
                                $html = '<a id="add_config" class="icon-text-link" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?choice=agestaggeredroles&conf='.$conf.'"><img
                                        src="'. THEME_URL . '/icons/delete.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_CONFIG').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_CONFIG').'</a>';
                                $form->addCustomContent('', $html);
                            }
                            $form->closeGroupBox();
                        }
                        $form->addDescription('</div>');
                        $html = '<a id="add_config" class="icon-text-link" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?choice=agestaggeredroles&conf=-1"><img
                                src="'. THEME_URL . '/icons/add.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_CONFIG').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_CONFIG').'</a>';
                        $htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SAVED_SETTINGS_LOST').'</div>';
                        $form->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc));
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

             <div class="panel panel-default" id="panel_familyroles">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_familyroles">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_familyroles" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=familyroles', $page, array('class' => 'form-preferences'));
                        $form->addDescription('<div style="width:100%; height:'.($num_familyroles<2 ? 500 : 650).'px; overflow:auto; border:20px;">');
                        for ($conf = 0; $conf < $num_familyroles; $conf++)
                        {
                            $form->openGroupBox('familyroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE'));
                            $form->addInput('familienrollen_prefix'.$conf, $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX'), $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_PREFIX_DESC', 'property' => FIELD_REQUIRED));
                            $form->addInput('familienrollen_beitrag'.$conf, $gL10n->get('SYS_CONTRIBUTION').' '.$gPreferences['system_currency'], $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_CONTRIBUTION_DESC'));

                            $selectBoxEntries = array('--', -1, 1, 2, 4, 12);
                            $role = new TableRoles($gDb);
                            $form->addSelectBox('familienrollen_zeitraum'.$conf, $gL10n->get('SYS_CONTRIBUTION_PERIOD'), $role->getCostPeriods(), array('firstEntry' => '', 'defaultValue' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$conf], 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_CONTRIBUTION_PERIOD_DESC', 'showContextDependentFirstEntry' => false));
                            $form->addInput('familienrollen_beschreibung'.$conf, $gL10n->get('SYS_DESCRIPTION'), $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$conf], array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_DESCRIPTION_DESC'));
                            if($num_familyroles != 1)
                            {
                                $html = '<a id="add_config" class="icon-text-link" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?choice=familyroles&conf='.$conf.'"><img
                                        src="'. THEME_URL . '/icons/delete.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_CONFIG').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_CONFIG').'</a>';
                                $form->addCustomContent('', $html);
                            }
                            $form->closeGroupBox();
                        }
                        $form->addDescription('</div>');
                        $html = '<a id="add_config" class="icon-text-link" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?choice=familyroles&conf=-1"><img
                                    src="'. THEME_URL . '/icons/add.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_CONFIG').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_CONFIG').'</a>';
                        $htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SAVED_SETTINGS_LOST').'</div>';
                        $form->addCustomContent('', $html, array('helpTextIdInline' => $htmlDesc));
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>
            <div class="panel panel-default" id="panel_accountdata">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_accountdata">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_accountdata" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=accountdata', $page, array('class' => 'form-preferences'));
                        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA_INFO'));
                        $form->addInput('iban', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN'), $pPreferences->config['Kontodaten']['iban'], array('property' => FIELD_REQUIRED));
                        $form->addInput('bic', $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC'), $pPreferences->config['Kontodaten']['bic']);
                        $form->addInput('bank', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK'), $pPreferences->config['Kontodaten']['bank'], array('property' => FIELD_REQUIRED));

                        if($getChoice == 'accountdata')
                        {
                            $form->addInput('creditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_CREDITOR'), $pPreferences->config['Kontodaten']['inhaber'], array('property' => FIELD_REQUIRED));
                            $html = '<a class="iconLink" id="creditorschieben" href="javascript:creditorschieben()"><img 
                                    src="'. THEME_URL . '/icons/arrow_down.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_CREDITOR').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_CREDITOR').'" /></a>';
                            $form->addCustomContent('', $html);
                            $form->addInput('origcreditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CREDITOR'), $pPreferences->config['Kontodaten']['origcreditor']);

                            $form->addInput('ci', $gL10n->get('PLG_MITGLIEDSBEITRAG_CI'), $pPreferences->config['Kontodaten']['ci'], array('property' => FIELD_REQUIRED));
                            $html = '<a class="iconLink" id="cischieben" href="javascript:cischieben()"><img 
                                    src="'. THEME_URL . '/icons/arrow_down.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_CI').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_CI').'" /></a>';
                            $form->addCustomContent('', $html);
                            $form->addInput('origci', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CI'), $pPreferences->config['Kontodaten']['origci']);
                            $html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE_CDTR_INFO').'</div>';
                            $form->addCustomContent('', $html);
                        }
                        else
                        {
                            $form->addInput('creditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_CREDITOR'), $pPreferences->config['Kontodaten']['inhaber'], array('property' => FIELD_REQUIRED));
                            if(!empty($pPreferences->config['Kontodaten']['origcreditor']))
                            {
                                $form->addInput('origcreditor', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CREDITOR'), $pPreferences->config['Kontodaten']['origcreditor']);
                            }
                            $form->addInput('ci', $gL10n->get('PLG_MITGLIEDSBEITRAG_CI'), $pPreferences->config['Kontodaten']['ci'], array('property' => FIELD_REQUIRED));
                            if(!empty($pPreferences->config['Kontodaten']['origci']))
                            {
                                $form->addInput('origci', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CI'), $pPreferences->config['Kontodaten']['origci']);
                            }

                            $html = '<a class="icon-text-info" href="'. ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences.php?choice=accountdata">'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').'</a>';
                            $form->addCustomContent('', $html, array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE_DESC'));
                        }
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_export">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_export">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_EXPORT').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_export" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=export', $page, array('class' => 'form-preferences'));
                        $form->openGroupBox('sepa', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA'));
                        $form->addInput('dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_XML_FILE_NAME'), $pPreferences->config['SEPA']['dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_XML_FILE_NAME_DESC', 'property' => FIELD_REQUIRED));
                        $form->addInput('kontroll_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE_NAME'), $pPreferences->config['SEPA']['kontroll_dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_CONTROL_FILE_NAME_DESC', 'property' => FIELD_REQUIRED));
                        $form->addInput('vorabinformation_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_FILE_NAME'), $pPreferences->config['SEPA']['vorabinformation_dateiname'], array('helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_FILE_NAME_DESC', 'property' => FIELD_REQUIRED));
                        $form->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_MAIL_TEXT'),
                            '<p>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_PRE_NOTIFICATION_MAIL_TEXT_DESC').':</p>
                            <p><strong>#user_first_name#</strong> - '.$gL10n->get('PLG_MITGLIEDSBEITRAG_VARIABLE_FIRST_NAME').'<br />
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
                        $form->addMultilineTextInput('pre_notification_text', '', $text->getValue('txt_text'), 7);
                        $form->closeGroupBox();
                        $form->openGroupBox('sepa', $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_EXPORT'));
                        $form->addInput('rechnung_dateiname', $gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_FILE_NAME'), $pPreferences->config['Rechnungs-Export']['rechnung_dateiname']);
                        $form->closeGroupBox();
                        $form->addDescription('');
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

             <div class="panel panel-default" id="panel_mandatemanagement">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_mandatemanagement">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_mandatemanagement" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=mandatemanagement', $page, array('class' => 'form-preferences'));
                        $form->addInput('prefix_fam', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX_FAM'), $pPreferences->config['Mandatsreferenz']['prefix_fam']);
                        $form->addInput('prefix_mem', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX_MEM'), $pPreferences->config['Mandatsreferenz']['prefix_mem']);
                        $form->addInput('prefix_pay', $gL10n->get('PLG_MITGLIEDSBEITRAG_PREFIX_PAY'), $pPreferences->config['Mandatsreferenz']['prefix_pay']);
                        $form->addInput('min_length', $gL10n->get('PLG_MITGLIEDSBEITRAG_MIN_LENGTH'), $pPreferences->config['Mandatsreferenz']['min_length'], array('type' => 'number', 'minNumber' => 5, 'maxNumber' => 35));

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
                        $form->addSelectBox('data_field', $gL10n->get('PLG_MITGLIEDSBEITRAG_DATA_FIELD_SERIAL_NUMBER'), $configSelection, array('defaultValue' => $pPreferences->config['Mandatsreferenz']['data_field'], 'showContextDependentFirstEntry' => false));
                        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT_DESC'));
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_testssetup">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_testssetup">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_TEST').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_TEST').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_TEST').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_testssetup" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=testssetup', $page, array('class' => 'form-preferences'));
                        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_TEST_SETUP_INFO'));
                        $form->addDescription('<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').'</strong>');
                        $form->addDescription('<div style="width:100%; height:'.($num_familyroles<2 ? 140 : 160).'px; overflow:auto; border:20px;">');
                        for ($conf = 0; $conf < $num_familyroles; $conf++)
                        {
                            $form->openGroupBox('familyroles_group', ($conf+1).'. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE'));
                            $form->addInput('familienrollen_pruefung'.$conf, $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$conf]);
                            $form->closeGroupBox();
                        }
                        $form->addDescription('</div>');
                        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_DESC2'));
                        $form->addLine();
                        $familienrollen = beitragsrollen_einlesen('fam');
                        $altersrollen = beitragsrollen_einlesen('alt');
                        $fixrollen = beitragsrollen_einlesen('fix');
                        $form->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY'), '', array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY_DESC2'));
                        if ((count($altersrollen) > 0) || (count($familienrollen) > 0) || (count($fixrollen) > 0))
                        {
                            $form->addDescription('<div style="width:100%; height:250px; overflow:auto; border:20px;">');
                            if (count($altersrollen) > 0)
                            {
                                foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
                                {
                                    $form->addCheckbox('altersrollenpflicht'.$token, $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$token .')', (in_array($token, $pPreferences->config['Rollenpruefung']['altersrollenpflicht']) ? 1 : 0));
                                }
                            }
                            if (count($familienrollen) > 0)
                            {
                                $form->addCheckbox('familienrollenpflicht', $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'), $pPreferences->config['Rollenpruefung']['familienrollenpflicht']);
                            }
                            if (count($fixrollen) > 0)
                            {
                                foreach($fixrollen as $key => $data)
                                {
                                    $form->addCheckbox('fixrollenpflicht'.$key, $data['rolle'], (in_array($key, $pPreferences->config['Rollenpruefung']['fixrollenpflicht']) ? 1 : 0));
                                }
                            }
                            $form->addDescription('</div>');
                        }
                        else
                        {
                            $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_NO_CONTRIBUTION_ROLES'));
                        }
                        $form->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION'), '', array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION_DESC2'));
                        if (((count($altersrollen) > 0) && (count($familienrollen) > 0)) || count($fixrollen) > 0)
                        {
                            $form->addDescription('<div style="width:100%; height:250px; overflow:auto; border:20px;">');
                            if ((count($pPreferences->config['Altersrollen']['altersrollen_token'])>1))
                            {
                                for ($x = 0; $x < count($pPreferences->config['Altersrollen']['altersrollen_token'])-1; $x++)
                                {
                                    for ($y = $x+1; $y < count($pPreferences->config['Altersrollen']['altersrollen_token']); $y++)
                                    {
                                        $form->addCheckbox('altersrollenaltersrollen'.$pPreferences->config['Altersrollen']['altersrollen_token'][$x].$pPreferences->config['Altersrollen']['altersrollen_token'][$y], $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$pPreferences->config['Altersrollen']['altersrollen_token'][$x].') ./. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$pPreferences->config['Altersrollen']['altersrollen_token'][$y].')', (in_array($pPreferences->config['Altersrollen']['altersrollen_token'][$x].','.$pPreferences->config['Altersrollen']['altersrollen_token'][$y], $pPreferences->config['Rollenpruefung']['altersrollenaltersrollen']) ? 1 : 0));
                                    }
                                }
                            }
                            if ((count($altersrollen) > 0) && (count($familienrollen) > 0))
                            {
                                foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
                                {
                                    $form->addCheckbox('altersrollenfamilienrollen'.$token, $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$token .') ./. '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES'), (in_array($token, $pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen']) ? 1 : 0));
                                }
                            }
                            if ((count($altersrollen) > 0) && (count($fixrollen) > 0))
                            {
                                foreach($fixrollen as $key => $data)
                                {
                                    foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
                                    {
                                        $form->addCheckbox('altersrollenfix'.$token.$key, $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES').' ('.$token .') ./. '.$data['rolle'], (in_array($token.$key, $pPreferences->config['Rollenpruefung']['altersrollenfix']) ? 1 : 0));
                                    }
                                }
                            }
                            if ((count($familienrollen) > 0) && (count($fixrollen) > 0))
                            {
                                foreach($fixrollen as $key => $data)
                                {
                                    $form->addCheckbox('familienrollenfix'.$key, $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES').' ./. '.$data['rolle'], (in_array($key, $pPreferences->config['Rollenpruefung']['familienrollenfix']) ? 1 : 0));
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
                            			$form->addCheckbox('fixrollenfixrollen'.$keyL.'_'.$keyR, $dataL['rolle'].' ./. '.$dataR['rolle'], (in_array($keyL.'_'.$keyR, $pPreferences->config['Rollenpruefung']['fixrollenfixrollen']) ? 1 : 0));
                            		}
                            	}
                            	unset($fixrollenL);
                            	unset($fixrollenR);
                            }
                            $form->addDescription('</div>');
                        }
                        else
                        {
                            $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_NO_COMBINATION_ROLES'));
                        }
                        $sql = 'SELECT cat_id, cat_name
                                    FROM '.TBL_CATEGORIES.' , '.TBL_ROLES.'
                                    WHERE cat_id = rol_cat_id
                                    AND (  cat_org_id = '.ORG_ID.'
                                    OR cat_org_id IS NULL )';
                        $form->addSelectBoxFromSql('bezugskategorie', $gL10n->get('PLG_MITGLIEDSBEITRAG_CAT_SELECTION'), $gDb, $sql, array('defaultValue' => $pPreferences->config['Rollenpruefung']['bezugskategorie'], 'multiselect' => true, 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_CAT_SELECTION_DESC'));
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

			<div class="panel panel-default" id="panel_columnset">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_columnset">
                            <img src="'. THEME_URL .'/icons/options.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_columnset" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('colset_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=columnset', $page, array('class' => 'form-preferences'));
                        $form->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_VIEW_DEFINITIONS_HEADER'));
                        $form->addDescription('<div style="width:100%; height:550px; overflow:auto; border:20px;">');

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
                        	$form->openGroupBox('configurations_group', $gL10n->get($groupHeader));
                        	
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
                    							<a class="icon-text-link" href="javascript:addColumn'.$conf.'()"><img src="'. THEME_URL . '/icons/add.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_COLUMN').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ADD_ANOTHER_COLUMN').'</a>
                							</td>
            							</tr>
        							</tbody>
    							</table>
    						</div>';
                        	$form->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_COLUMN_SELECTION'), $html);
                        	$form->closeGroupBox();
                        }
                        $form->addDescription('</div>');
                        $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_deinstallation">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_deinstallation">
                            <img src="'. THEME_URL .'/icons/delete.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_deinstallation" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                        $form = new HtmlForm('configurations_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=deinstallation', $page, array('class' => 'form-preferences'));
                        $form->addButton('btn_deinstallation', $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION'), array('icon' => THEME_URL .'/icons/delete.png', 'link' => 'deinstallation.php', 'class' => 'btn-primary col-sm-offset-3'));
                        $form->addCustomContent('', '<br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DEINSTALLATION_DESC'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_access_preferences">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_access_preferences">
                            <img src="'. THEME_URL .'/icons/lock.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_access_preferences" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // show form
                    $form = new HtmlForm('access_preferences_form', ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/preferences_function.php?form=access_preferences', $page, array('class' => 'form-preferences'));
                        $sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
                                FROM '.TBL_CATEGORIES.' AS cat, '.TBL_ROLES.' AS rol
                                WHERE cat.cat_id = rol.rol_cat_id
                                AND (  cat.cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                                OR cat.cat_org_id IS NULL )';
                        $form->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array('defaultValue' => $pPreferences->config['access']['preferences'], 'helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_ACCESS_PREFERENCES_DESC', 'multiselect' => true, 'property' => FIELD_REQUIRED));
                        $form->addSubmitButton('btn_save_access_preferences', $gL10n->get('SYS_SAVE'), array('icon' => THEME_URL .'/icons/disk.png', 'class' => ' col-sm-offset-3'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>

            <div class="panel panel-default" id="panel_plugin_informations">
                <div class="panel-heading">
                    <h4 class="panel-title">
                        <a class="icon-text-link" data-toggle="collapse" data-parent="#accordion_preferences" href="#collapse_plugin_informations">
                            <img src="'. THEME_URL .'/icons/info.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_INFORMATION').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_INFORMATION').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_INFORMATION').'
                        </a>
                    </h4>
                </div>
                <div id="collapse_plugin_informations" class="panel-collapse collapse">
                    <div class="panel-body">');
                        // create a static form
                        $form = new HtmlForm('plugin_informations_preferences_form', null, $page);
                        $form->addStaticControl('plg_name', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_NAME'), $gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERSHIP_FEE'));
                        $form->addStaticControl('plg_version', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_VERSION'), $pPreferences->config['Plugininformationen']['version']);
                        $form->addStaticControl('plg_date', $gL10n->get('PLG_MITGLIEDSBEITRAG_PLUGIN_DATE'), $pPreferences->config['Plugininformationen']['stand']);
                        $html = '<a class="btn" href="https://www.admidio.org/dokuwiki/doku.php?id=de:plugins:mitgliedsbeitrag#mitgliedsbeitrag" target="_blank"><img
                                    src="'. THEME_URL . '/icons/eye.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DOCUMENTATION_OPEN').'" />'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DOCUMENTATION_OPEN').'</a>';
                        $form->addCustomContent($gL10n->get('PLG_MITGLIEDSBEITRAG_DOCUMENTATION'), $html, array('helpTextIdInline' => 'PLG_MITGLIEDSBEITRAG_DOCUMENTATION_OPEN_DESC'));
                        $page->addHtml($form->show(false));
                    $page->addHtml('</div>
                </div>
            </div>
        </div>
    </div>
</div>
');

$page->show();
