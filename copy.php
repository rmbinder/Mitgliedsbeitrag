<?php
/**
 ***********************************************************************************************
 * Kopieren von Profildaten fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zun Anzeigen einer html-Liste
 *                    assign - Kopieren der Daten
 * full_screen    	: 0 	 - Normalbildschirm
 *           		  1 	 - Vollbildschirm
 * source_userid	: die UserID des Quelle-Mitglieds
 * target_userid	: die UserID des Ziel-Mitglieds
 * source_usfid		: die UsfID der Quelle
 * target_usfid		: die UsfID des Ziels
 ***********************************************************************************************
 */

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

$pPreferences = new ConfigTablePMB;
$pPreferences->read();

// only authorized user are allowed to start this module
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
	$gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

if(isset($_GET['mode']) && $_GET['mode'] == 'assign' )
{
    // ajax mode then only show text if error occurs
    $gMessage->showTextOnly(true);
}

// Initialize and check the parameters
$getMode           	= admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getFullScreen  	= admFuncVariableIsValid($_GET, 'full_screen', 'numeric');
$getSourceUserid 	= admFuncVariableIsValid($_GET, 'source_userid', 'numeric', array('defaultValue' => 0));
$getTargetUserid 	= admFuncVariableIsValid($_GET, 'target_userid', 'numeric', array('defaultValue' => 0));
$getSourceUsfid 	= admFuncVariableIsValid($_GET, 'source_usfid', 'numeric');
$getTargetUsfid 	= admFuncVariableIsValid($_GET, 'target_usfid', 'numeric');

$userSource = new User($gDb, $gProfileFields, $getSourceUserid);
$userTarget = new User($gDb, $gProfileFields, $getTargetUserid);

if($getMode == 'assign')
{   	
	$ret_text = 'ERROR';
	try
   	{
		if($gProfileFields->getPropertyById($getSourceUsfid, 'usf_type') <> $gProfileFields->getPropertyById($getTargetUsfid, 'usf_type'))
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
    // show html list
    
    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_COPY');

    // add current url to navigation stack if last url was not the same page
    if(strpos($gNavigation->getUrl(), 'copy.php') === false)
    {
        $gNavigation->addUrl(CURRENT_URL, $headline);
    }

    // create html page object
    $page = new HtmlPage($headline);
        
    if($getFullScreen == true)
    {
    	$page->hideThemeHtml();
    }

    $javascriptCode = '
    	// pulldown Quelle is clicked 
    	$("#quelle").change(function () {
        	if($(this).val().length > 0) {
                window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/copy.php?full_screen='.$getFullScreen.'&target_userid='.$getTargetUserid.'&source_userid="+$(this).val());
            }
        });

        // pulldown Ziel is clicked 
        $("#ziel").change(function () {
            if($(this).val().length > 0) {
                window.location.replace("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/copy.php?full_screen='.$getFullScreen.'&source_userid='.$getSourceUserid.'&target_userid="+$(this).val());
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
             
			if(sourcecheckbox.size() == 1) {
				var row_id = sourcecheckbox.attr("id");
       			var pos = row_id.search("_");
       			var source_usfid = row_id.substring(pos+1);
       			
             	$.post("'.$g_root_path. '/adm_plugins/'.$plugin_folder.'/copy.php?mode=assign&full_screen='.$getFullScreen.'&source_usfid="+source_usfid+"&target_usfid="+target_usfid+"&target_userid='.$getTargetUserid.'&source_userid='.$getSourceUserid.'",
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

    // get module menu
    $copyMenu = $page->getMenu();
    $copyMenu->addItem('menu_item_back', $g_root_path.'/adm_plugins/'.$plugin_folder.'/menue.php?show_option=copy', $gL10n->get('SYS_BACK'), 'back.png');

    if($getFullScreen == true)
    {
    	$copyMenu->addItem('menu_item_normal_picture', $g_root_path. '/adm_plugins/'.$plugin_folder.'/copy.php?source_userid='.$getSourceUserid.'&amp;target_userid='.$getTargetUserid.'&amp;full_screen=0',  
                $gL10n->get('SYS_NORMAL_PICTURE'), 'arrow_in.png');
    }
    else
    {
        $copyMenu->addItem('menu_item_full_screen', $g_root_path. '/adm_plugins/'.$plugin_folder.'/copy.php?source_userid='.$getSourceUserid.'&amp;target_userid='.$getTargetUserid.'&amp;full_screen=1',   
                $gL10n->get('SYS_FULL_SCREEN'), 'arrow_out.png');
    }   
    
    $membersSelectString='';
    $members = list_members(array('FIRST_NAME','LAST_NAME','BIRTHDAY'),0);
	foreach ($members as $member => $memberdata)
	{
        $datumtemp = new DateTimeExtended($memberdata['BIRTHDAY'], 'Y-m-d');
		$members[$member] = $memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME'].', '.$datumtemp->format($gPreferences['system_date']);
		$membersSelectString = $membersSelectString.'<option value='.$member.'>'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME'].', '.$datumtemp->format($gPreferences['system_date']).'</option>';		
	}
	asort($members);

    $navbarForm = new HtmlForm('navbar_copy_form', '', $page, array('type' => 'navbar', 'setFocus' => false));
	$navbarForm->addDescription($gL10n->get('PLG_MITGLIEDSBEITRAG_COPY_HEADERINFO'));
    $navbarForm->addSelectBox('quelle', $gL10n->get('PLG_MITGLIEDSBEITRAG_SOURCE'), $members, array('defaultValue' => $getSourceUserid,'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_SOURCE_DESC', 'showContextDependentFirstEntry' => true, 'property'=> FIELD_REQUIRED));
    $navbarForm->addSelectBox('ziel', $gL10n->get('PLG_MITGLIEDSBEITRAG_TARGET'), $members, array('defaultValue' => $getTargetUserid,'helpTextIdLabel' => 'PLG_MITGLIEDSBEITRAG_TARGET_DESC', 'showContextDependentFirstEntry' => true, 'property'=> FIELD_REQUIRED));   
    $copyMenu->addForm($navbarForm->show(false));

    // create table object
    $table = new HtmlTable('tbl_copy', $page, true, true, 'table table-condensed');
    $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES_FOUND');

    // create array with all column heading values
    $columnHeading = array(
        $gL10n->get('PLG_MITGLIEDSBEITRAG_SOURCE'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_CHOICE'),
       	$gL10n->get('MEM_PROFILE_FIELD'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_CHOICE'),
        $gL10n->get('PLG_MITGLIEDSBEITRAG_TARGET')
    );
    $table->setColumnAlignByArray(array('center', 'center', 'center','center','center'));
    $table->addRowHeadingByArray($columnHeading);
    if($getSourceUserid == 0)
    {
    	$table->setDatatablesColumnsHide(array(2));
    }	
	if($getTargetUserid ==0)
	{
		$table->setDatatablesColumnsHide(array(4));
	}

    // show rows 
   	foreach($gProfileFields->mProfileFields as $field)
    {
        $htmlSourceMarker 	= '&nbsp;';
        $htmlProfileField 	= '&nbsp;';
        $htmlTargetMarker 	= '&nbsp;';

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
    	$htmlProfileField 	= addslashes($field->getValue('usf_name'));
    	  
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
