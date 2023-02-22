<?php
/**
 ***********************************************************************************************
 * Kopieren von Profildaten fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste
 *                    assign - Kopieren der Daten
 * source_userid    : die UserID des Quelle-Mitglieds
 * target_userid    : die UserID des Ziel-Mitglieds
 * source_usfid     : die UsfID der Quelle
 * target_usfid     : die UsfID des Ziels
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

if(isset($_GET['mode']) && $_GET['mode'] == 'assign')
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode            = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getSourceUserid    = admFuncVariableIsValid($_GET, 'source_userid', 'numeric', array('defaultValue' => 0));
$getTargetUserid    = admFuncVariableIsValid($_GET, 'target_userid', 'numeric', array('defaultValue' => 0));
$getSourceUsfid     = admFuncVariableIsValid($_GET, 'source_usfid', 'numeric');
$getTargetUsfid     = admFuncVariableIsValid($_GET, 'target_usfid', 'numeric');

$userSource = new User($gDb, $gProfileFields, $getSourceUserid);
$userTarget = new User($gDb, $gProfileFields, $getTargetUserid);

if($getMode == 'assign')
{
    $ret_text = 'ERROR';
    try
    {
        if($gProfileFields->getPropertyById($getSourceUsfid, 'usf_type') != $gProfileFields->getPropertyById($getTargetUsfid, 'usf_type'))
        {
            $ret_text = 'unequal_datatype';
        }
        else
        {
            $userTarget->setValue($gProfileFields->getPropertyById($getTargetUsfid, 'usf_name_intern'), $userSource->getValue($gProfileFields->getPropertyById($getSourceUsfid, 'usf_name_intern')));
            $userTarget->save();
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
    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_COPY');

    $gNavigation->addUrl(CURRENT_URL, $headline);
    
    $page = new HtmlPage('plg-mitgliedsbeitrag-copy', $headline);

    $javascriptCode = '
        // pulldown Quelle is clicked 
        $("#quelle").change(function () {
            if($(this).val().length > 0) {
                window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/copy.php', array('target_userid' => $getTargetUserid)) . '&source_userid=" + $(this).val());
            }
        });

        // pulldown Ziel is clicked
        $("#ziel").change(function () {
            if($(this).val().length > 0) {
                window.location.replace("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/copy.php', array('source_userid' => $getSourceUserid)).' &target_userid=" + $(this).val());
            }
        });

        // source-checkbox of user is clicked
        $("input[type=checkbox].sourcelist_checkbox").click(function(){
            var checkbox = $(this);
            var row_id = $(this).attr("id");
            var pos = row_id.search("_");
            var source_usfid = row_id.substring(pos+1);

            $("input[type=checkbox].sourcelist_checkbox").prop("checked", false);
            $("input[type=checkbox]#sourcefield_"+source_usfid).prop("checked", true);
        });

        // target-checkbox of user is clicked --> change data
        $("input[type=checkbox].targetlist_checkbox").click(function(){
            var targetcheckbox = $(this);
            var row_id = targetcheckbox.attr("id");
            var pos = row_id.search("_");
            var target_usfid = row_id.substring(pos+1);
       
            var sourcecheckbox = $("input[type=checkbox].sourcelist_checkbox:checked");
            
            if(sourcecheckbox.length == 1) {
                var row_id = sourcecheckbox.attr("id");
                var pos = row_id.search("_");
                var source_usfid = row_id.substring(pos+1);
                               
                 $.post("'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/copy.php', array('mode' => 'assign', 'target_userid' => $getTargetUserid, 'source_userid' => $getSourceUserid)) .'&source_usfid=" + source_usfid + "&target_usfid=" + target_usfid,
                    function(data){
                        // check if error occurs
                        if(data == "success") {
                            //$("#targetval_"+target_usfid).fadeOut(3000);
                            $("#targetval_"+target_usfid).hide();
                            $("#targetval_"+target_usfid).text($("#sourceval_"+source_usfid).text()); 
                            $("#targetval_"+target_usfid).fadeIn(1200);
                        }
                        else if(data == "unequal_datatype"){
                            alert("'.$gL10n->get('PLG_MITGLIEDSBEITRAG_UNEQUAL_DATATYPE').'");
                            return false;
                        }
                        else {
                            alert(data);
                            return false;
                        }
                        return true;
                    }
                );
                $("input[type=checkbox].sourcelist_checkbox").prop("checked", false);
            }
            $("input[type=checkbox].targetlist_checkbox").prop("checked", false);
        });
    ';

    $page->addJavascript($javascriptCode, true);

    $membersSelectString = '';
    $members = list_members(array('FIRST_NAME', 'LAST_NAME', 'BIRTHDAY'), 0);
    foreach ($members as $member => $memberdata)
    {
        $birthday = '';
        $objBirthday = \DateTime::createFromFormat('Y-m-d', $memberdata['BIRTHDAY']);
        if ($objBirthday !== false)
        {
            $birthday = ', '.$objBirthday->format($gSettingsManager->getString('system_date'));
        }
        
        $members[$member] = $memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME'].$birthday;
        $membersSelectString = $membersSelectString.'<option value='.$member.'>'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME'].$birthday.'</option>';
    }
    asort($members);

    $page->addHtml($gL10n->get('PLG_MITGLIEDSBEITRAG_COPY_HEADERINFO'));
 
    $form = new HtmlForm('copy_selection_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
    $form->addSelectBox('quelle', $gL10n->get('PLG_MITGLIEDSBEITRAG_SOURCE'), $members, array('defaultValue' => $getSourceUserid, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_SOURCE_DESC', 'showContextDependentFirstEntry' => true, 'property' => HtmlForm::FIELD_REQUIRED));
    $form->addSelectBox('ziel',   $gL10n->get('PLG_MITGLIEDSBEITRAG_TARGET'), $members, array('defaultValue' => $getTargetUserid, 'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_TARGET_DESC', 'showContextDependentFirstEntry' => true, 'property' => HtmlForm::FIELD_REQUIRED));

    $page->addHtml($form->show(false));

    // create table object
    $table = new HtmlTable('tbl_copy', $page, true, true, 'table table-condensed');
    $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

    // create array with all column heading values
    $columnHeading = array(
        $gL10n->get('PLG_MITGLIEDSBEITRAG_SOURCE'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_CHOICE'),
        $gL10n->get('SYS_PROFILE_FIELD'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_CHOICE'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_TARGET')
    );
    $table->setColumnAlignByArray(array('center', 'center', 'center', 'center', 'center'));
    $table->addRowHeadingByArray($columnHeading);
    
    if($getSourceUserid == 0)
    {
        $table->setDatatablesColumnsHide(array(2));
    }
    if($getTargetUserid == 0)
    {
        $table->setDatatablesColumnsHide(array(4));
    }

    // show rows
    foreach($gProfileFields->getProfileFields() as $field)
    {
        $htmlSourceMarker   = '&nbsp;';
        $htmlProfileField   = '&nbsp;';
        $htmlTargetMarker   = '&nbsp;';

        //1. Spalte
        if(strlen($userSource->getValue($field->getValue('usf_name_intern'))) > 0)
        {
            $htmlSource = '<div class="sourceval_'.$field->getValue('usf_id').'" id="sourceval_'.$field->getValue('usf_id').'">'.$userSource->getValue($field->getValue('usf_name_intern')).'</div>';
        }
        else
        {
            $htmlSource = '<div class="sourceval_'.$field->getValue('usf_id').'" id="sourceval_'.$field->getValue('usf_id').'">'.'&nbsp;'.'</div>';
        }

        //2. Spalte
        $htmlSourceMarker = '<input type="checkbox" id="sourcefield_'.$field->getValue('usf_id').'" name="sourcefield_'.$field->getValue('usf_id').'" class="sourcelist_checkbox" /><b id="loadindicator_sourcefield_'.$field->getValue('usf_id').'"></b>';

        //3. Spalte
        $htmlProfileField   = addslashes($field->getValue('usf_name'));

        //4. Spalte
        $htmlTargetMarker = '<input type="checkbox" id="targetfield_'.$field->getValue('usf_id').'" name="targetfield_'.$field->getValue('usf_id').'" class="targetlist_checkbox" /><b id="loadindicator_targetfield_'.$field->getValue('usf_id').'"></b>';

        //5. Spalte
        if(strlen($userTarget->getValue($field->getValue('usf_name_intern'))) > 0)
        {
            $htmlTarget = '<div class="targetval_'.$field->getValue('usf_id').'" id="targetval_'.$field->getValue('usf_id').'">'.$userTarget->getValue($field->getValue('usf_name_intern')).'</div>';
        }
        else
        {
            $htmlTarget = '<div class="targetval_'.$field->getValue('usf_id').'" id="targetval_'.$field->getValue('usf_id').'">'.'&nbsp;'.'</div>';
        }

        // create array with all column values
        $columnValues = array(
            $htmlSource,
            $htmlSourceMarker,
            $htmlProfileField,
            $htmlTargetMarker,
            $htmlTarget
            );

        $table->addRowByArray($columnValues);
    }//End Foreach

    $page->addHtml($table->show(false));
    $page->show();
}
