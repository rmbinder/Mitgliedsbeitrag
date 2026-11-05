<?php
/**
 ***********************************************************************************************
 * Module viewdefinitions of the Admidio plugin MembershipFee
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameter: 
 * 
 * view_definition     : Parameter indicating which view is being edited
 *                      - payments_fields
 *                      - mandates_fields
 *                      - duedates_fields
 *                      - bill_fields
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');
    require (__DIR__ . '/../../../system/login_valid.php');

    $getViewDefinition = admFuncVariableIsValid($_GET, 'view_definition', 'string', array(
        'defaultValue' => 'payments_fields',
        'validValues' => array(
            'payments_fields',
            'mandates_fields',
            'duedates_fields',
            'bill_fields'
        )
    ));

    switch ($getViewDefinition) {
        case 'payments_fields':
            $groupHeader = $gL10n->get('PLG_MEMBERSHIPFEE_PAYMENTS_FIELDS');
            break;
        case 'mandates_fields':
            $groupHeader = $gL10n->get('PLG_MEMBERSHIPFEE_MANDATES_FIELDS');
            break;
        case 'duedates_fields':
            $groupHeader = $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATES_FIELDS');
            break;
        case 'bill_fields':
            $groupHeader = $gL10n->get('PLG_MEMBERSHIPFEE_BILL');
            break;
    }

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_VIEW_DEFINITIONS');

    // add current url to navigation stack if last url was not the same page
    if (! str_contains($gNavigation->getUrl(), 'viewdefinitions.php')) {
        $gNavigation->addUrl(SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'panel' => 'view_definitions'
        )), $gL10n->get('SYS_SETTINGS'));
        $gNavigation->addUrl(CURRENT_URL, $groupHeader);
    }

    $i = 0;
    foreach ($pPreferences->config['columnconfig'][$getViewDefinition] as $confFields) {
        foreach ($gProfileFields->getProfileFields() as $field) {
            if ('p' . $field->getValue('usf_id') === $confFields) {
                $formValues['column'][++ $i] = $field->getValue('usf_name_intern');
            }
        }
    }
    $defaultColumnRows = count($pPreferences->config['columnconfig'][$getViewDefinition]);

    // create HTML page object
    $page = PagePresenter::withHtmlIDAndHeadline('membershipfee-viewdefinitions', $headline);

    $page->assignSmartyVariable('groupHeader', $groupHeader);

    $javascriptCode = '
    var fieldNumberIntern = 0;
    var arrUserFields     = createProfileFieldsArray();
    var arrDefaultFields  = createColumnsArray();

    /**
     * Function adds a new line to assign columns for the list
     */
    function addColumn() {
        var category = "";
        var fieldNumberShow = fieldNumberIntern + 1;
        var table = document.getElementById("viewdefinitions_fields_tbody");
        var newTableRow = table.insertRow(fieldNumberIntern);
        newTableRow.setAttribute("id", "row" + fieldNumberShow)
        $(newTableRow).css("display", "none");
        var newCellCount = newTableRow.insertCell(-1);
        newCellCount.textContent = (fieldNumberShow) + ". ' . $gL10n->get('SYS_COLUMN') . ' :";

        // neue Spalte zur Auswahl des Profilfeldes
        var newCellField = newTableRow.insertCell(-1);
        htmlCboFields = "<select class=\"form-control\" size=\"1\" id=\"column" + fieldNumberShow + "\" class=\"ListProfileField\" name=\"column[]\">" +
                "<option value=\"\"></option>";
        for (var counter = 1; counter < arrUserFields.length; counter++) {
            if (category !== arrUserFields[counter]["cat_name"]) {
                if (category.length > 0) {
                    htmlCboFields += "</optgroup>";
                }
                htmlCboFields += "<optgroup label=\"" + arrUserFields[counter]["cat_name"] + "\">";
                category = arrUserFields[counter]["cat_name"];
            }

            var selected = "";
           
            // bei gespeicherten Listen das entsprechende Profilfeld selektieren
            // und den Feldnamen dem Listenarray hinzufügen
            if (arrDefaultFields[fieldNumberShow]) {
                if (arrUserFields[counter]["usf_name_intern"] === arrDefaultFields[fieldNumberShow]["usf_name_intern"]) {
                    selected = " selected=\"selected\" ";
                    arrDefaultFields[fieldNumberShow]["usf_name"] = arrUserFields[counter]["usf_name"];
                }
            }
            htmlCboFields += "<option value=\"" + arrUserFields[counter]["usf_name_intern"] + "\" " + selected + ">" + arrUserFields[counter]["usf_name"] + "</option>";
        }
        htmlCboFields += "</select>";
        newCellField.innerHTML = htmlCboFields;

        var newCellHandle = newTableRow.insertCell(-1);
        newCellHandle.innerHTML = "    <a class=\"admidio-icon-link admidio-move-row\" style=\"padding-left: 0pt; padding-right: 0pt;\">" + 
            "        <i class=\"bi bi-arrows-move handle\" data-bs-toggle=\"tooltip\" title=\"' . $gL10n->get('SYS_MOVE_VAR') . '\"></i></a>" +
            "    <a class=\"admidio-icon-link admidio-delete\" style=\"padding-left: 0pt; padding-right: 0pt;\">" + 
            "        <i class=\"bi bi-trash\" data-bs-toggle=\"tooltip\" title=\"' . $gL10n->get('SYS_DELETE') . '\"></i></a>";

        $(newTableRow).fadeIn("slow");
        fieldNumberIntern++;
    }

    function createProfileFieldsArray() {
        var userFields = [];';

    // create a multidimensional array for all columns with the necessary data
    $i = 0;
    foreach ($gProfileFields->getProfileFields() as $field) {
        // add profile field to user field array
        if ($gProfileFields->isVisible($field->getValue('usf_name_intern'), $gCurrentUser->isAdministratorUsers())) {
            $javascriptCode .= '
            userFields[' . ++ $i . '] = {
                "cat_name": "' . str_replace('"', '\'', $field->getValue('cat_name')) . '",
                "usf_name": "' . addslashes($field->getValue('usf_name')) . '",
                "usf_name_intern": "' . addslashes($field->getValue('usf_name_intern')) . '",
                "usf_type": "' . $field->getValue('usf_type') . '"
            };';
        }
    }

    $javascriptCode .= '
        return userFields;
    }

    function createColumnsArray()
    {
        var defaultFields = [];';

    // now add all columns to the javascript row objects
    $actualColumnNumber = 1;
    while (isset($formValues['column'][$actualColumnNumber])) {

        $javascriptCode .= '
        defaultFields[' . $actualColumnNumber . '] = {
            "usf_name_intern": "' . $formValues['column'][$actualColumnNumber] . '"
        };';

        ++ $actualColumnNumber;
    }

    $javascriptCode .= '
        return defaultFields;
    }

    function send() {
     
        $("#adm_viewdefinitions_configuration_form").attr("action", "' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/viewdefinitions_function.php', array(
        'view_definition' => $getViewDefinition
    )) . '");
         $("#adm_viewdefinitions_configuration_form").submit();
               
    }';
    $page->addJavascript($javascriptCode);
    $page->addJavascript('$(function() {
        $("#btn_add_column").click(function() { addColumn(); });
        $("#adm_button_save").click(function() { send(); });

        $("#viewdefinitions_fields_tbody").sortable({
            handle: ".admidio-move-row", 
            items: "tr",
            update: updateNumbering
        });
        function updateNumbering() {
            $("tbody#viewdefinitions_fields_tbody").find("tr").each(function(index) {
                $(this).find("td:first").text((index + 1) + ". ' . $gL10n->get('SYS_COLUMN') . ':");
            });
        }
        $(document).on("click", ".admidio-delete", function(){
            let row = $(this).closest("tr").fadeOut(300, function() {
                $(this).remove();
                updateNumbering();
            });
        });

        for (var counter = 0; counter < ' . $defaultColumnRows . '; counter++) {
            addColumn();
        }
        updateNumbering();
    });', true);

    // show form
    $form = new FormPresenter('adm_viewdefinitions_configuration_form', '../templates/viewdefinitions.plugin.membershipfee.tpl', '#', $page);

    $form->addButton('btn_add_column', $gL10n->get('SYS_ADD_COLUMN'), array(
        'icon' => 'bi-plus-circle-fill',
        'class' => 'btn-primary'
    ));
    $form->addButton('adm_button_save', $gL10n->get('SYS_SAVE_CONFIGURATION'), array(
        'icon' => 'bi-check-lg',
        'class' => 'btn-primary'
    ));

    $form->addToHtmlPage();
    $gCurrentSession->addFormObject($form);

    $page->show();
} catch (Throwable $e) {
    handleException($e);
}
