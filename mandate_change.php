<?php
/**
 ***********************************************************************************************
 * Routine um eine Mandatsaenderung (Zahlungspflichtiger) zu bearbeiten
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   mandate_change.php ist eine modifizierte members_assignment.php
 *
 * Parameters:
 *
 * mode             : html   - Standardmodus zum Anzeigen einer html-Liste
 *                    assign - Schreiben der Änderungen in die Datenbank
 * usr_id           : Id des Benutzers, für den die Mandatsänderungen durchgeführt werden
 * iban             : die neue IBAN des Zahlungspflichtigen
 * origiban		    : die urspruengliche IBAN des Zahlungspflichtigen
 * mandateid	    : die neue Mandatsreferenz des Zahlungspflichtigen
 * origmandateid    : die urspruengliche Mandatsreferenz des Zahlungspflichtigen
 * bankchanged	    : die Bankverbindung wurde geaendert
 * bank             : die neue Bank des Zahlungspflichtigen
 * bic              : der neue BIC des Zahlungspflichtigen
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
$getMode            = admFuncVariableIsValid($_GET, 'mode', 'string', array('defaultValue' => 'html', 'validValues' => array('html', 'assign')));
$getUserId 			= admFuncVariableIsValid($_GET, 'user_id', 'numeric');
$getIBAN            = admFuncVariableIsValid($_GET, 'iban', 'string');
$getOrigIBAN        = admFuncVariableIsValid($_GET, 'origiban', 'string');
$getMandateID       = admFuncVariableIsValid($_GET, 'mandateid', 'string');
$getOrigMandateID   = admFuncVariableIsValid($_GET, 'origmandateid', 'string');
$getBankChanged     = admFuncVariableIsValid($_GET, 'bankchanged', 'string');
$getBank   			= admFuncVariableIsValid($_GET, 'bank', 'string', array('defaultValue' => ''));
$getBIC     		= admFuncVariableIsValid($_GET, 'bic', 'string', array('defaultValue' => ''));

$user = new User($gDb, $gProfileFields, $getUserId);

if($getMode == 'assign')   
{
	$ret_txt='error_nothing_changed';
	$iban_change='false';
	$bank_change='false';
	$mandateid_change='false';
	 
    $gMessage->showTextOnly(true);
	
	// wurde die Bank geändert?
	if (  $getBankChanged == 'false' )             //nein, dieselbe Bank
	{
		//hat eine Änderung der IBAN stattgefunden?
		if ( $getIBAN <> $user->getValue('IBAN')  )
		{
			//ja, dann muss origIBAN befüllt sein
			if (strlen($getOrigIBAN) <> 0 )
			{
				$iban_change='true';
				$ret_txt='success';
			}
			else 
			{
				$ret_txt="error_origiban_missing";
			}
		}
	}
	else               //die Bank wurde geändert
	{
		//bei einer Änderung der Bank muss es eine andere IBAN geben
		if ( $getIBAN <> $user->getValue('IBAN'))
		{
			$bank_change='true';
			$ret_txt='success';
		}
		else 
		{
			$ret_txt="error_bank_changed";
		}
	}
	 
	// wurde die Mandatsreferenz geändert?
	if($getMandateID <> $user->getValue('MANDATEID'.$gCurrentOrganization->getValue('org_id')))
	{
		//bei einer Änderung muss origMandateID befüllt sein
		if (strlen($getOrigMandateID) <> 0 )
		{
			$mandateid_change='true';
			$ret_txt='success';
		}
		else 
		{
			$ret_txt="error_origmandateid_missing";
		}
	}
	
	if($ret_txt=='success')
	{
		if($iban_change=='true')
		{
			$user->setValue('IBAN', $getIBAN);	
			$user->setValue('ORIG_IBAN', $getOrigIBAN);
		}
		if($bank_change=='true')
		{
			$user->setValue('IBAN', $getIBAN);	
			$user->setValue('BIC', $getBIC);	
			$user->setValue('BANK', $getBank);
			$user->setValue('SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id'), '');
			$user->setValue('ORIG_DEBTOR_AGENT', 'SMNDA');
	
			// wenn die Bank gewechselt wurde, braucht die neue Bank die ursprüngliche IBAN nicht zu kennen
			$user->setValue('ORIG_IBAN', '');
		}
		if($mandateid_change=='true')
		{
			$user->setValue('MANDATEID'.$gCurrentOrganization->getValue('org_id'), $getMandateID);
			$user->setValue('ORIG_MANDATEID'.$gCurrentOrganization->getValue('org_id'), $getOrigMandateID);
		}
		$user->save();
	}
    echo $ret_txt;
}
else 
{
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE').' ('. $user->getValue('LAST_NAME').' '.$user->getValue('FIRST_NAME').')';

    // create html page object
    $page = new HtmlPage($headline);

    $page->addJavascript('
        function ibanschieben(){ 
		  var iban = $("input[type=text]#iban").val(); 
		  var origiban = $("input[type=text]#origiban").val(iban);
		  $("input[type=text]#iban").val("");
	   };
	   function mandatschieben(){ 
		  var mandateid = $("input[type=text]#mandateid").val(); 
		  var origmandateid = $("input[type=text]#origmandateid").val(mandateid);
		  $("input[type=text]#mandateid").val("");
	   };	
    ');            // !!!: ohne true


    $page->addJavascript('

        // checkbox "Kontoverbindung bei anderer Bank" wurde gewählt
        $("input[type=checkbox].bank_changed_checkbox").click(function(){
        	var bankchanged = $("input[type=checkbox]#bankchanged").prop("checked");
        	if(bankchanged) {
         		$("input[type=text]#bic").val("");
         		$("input[type=text]#bic").prop("disabled", false);
          		$("input[type=text]#bank").val("");
          		$("input[type=text]#bank").prop("disabled", false);
          		$("input[type=text]#origiban").val("");
          		$("input[type=text]#origdebtoragent").val("SMNDA");
        	}
        	else {
         		window.location.replace("'. ADMIDIO_URL . '/adm_plugins/'.$plugin_folder.'/mandate_change.php?user_id='.$getUserId.'");
        	}
        }); 
   
        $(".form-mandate_change").submit(function(event) {
            var id = $(this).attr("id");
            var iban = $("input[type=text]#iban").val(); 
		    var origiban = $("input[type=text]#origiban").val();
		    var mandateid = $("input[type=text]#mandateid").val(); 
		    var origmandateid = $("input[type=text]#origmandateid").val();
		    var bank = $("input[type=text]#bank").val();
		    var bic = $("input[type=text]#bic").val();
		    var bankchanged = $("input[type=checkbox]#bankchanged").prop("checked");
		
            var action ="'. ADMIDIO_URL . '/adm_plugins/'.$plugin_folder.'/mandate_change.php?user_id='.$getUserId.'&mode=assign&iban="+iban+"&origiban="+origiban+"&mandateid="+mandateid+"&origmandateid="+origmandateid+"&bank="+bank+"&bic="+bic+"&bankchanged="+bankchanged;
        
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
                    else if(data == "error_nothing_changed") {
                        $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                        $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_NOTHING_CHANGED').'</strong>");
                        $("#"+id+" .form-alert").fadeIn("slow");
                        $("#"+id+" .form-alert").animate({opacity: 1.0}, 5000);
                        $("#"+id+" .form-alert").fadeOut("slow");
                    }
                    else if(data == "error_origmandateid_missing") {
                        $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                        $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_ORIGMANDATEID_MISSING').'</strong>");
                        $("#"+id+" .form-alert").fadeIn("slow");
                        $("#"+id+" .form-alert").animate({opacity: 1.0}, 5000);
                        $("#"+id+" .form-alert").fadeOut("slow");
                    }
                    else if(data == "error_origiban_missing") {
                        $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                        $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_ORIGIBAN_MISSING').'</strong>");
                        $("#"+id+" .form-alert").fadeIn("slow");
                        $("#"+id+" .form-alert").animate({opacity: 1.0}, 5000);
                        $("#"+id+" .form-alert").fadeOut("slow");
                    }
                    else if(data == "error_bank_changed") {
                        $("#"+id+" .form-alert").attr("class", "alert alert-danger form-alert");
                        $("#"+id+" .form-alert").html("<span class=\"glyphicon glyphicon-remove\"></span><strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_BANK_CHANGED').'</strong>");
                        $("#"+id+" .form-alert").fadeIn("slow");
                        $("#"+id+" .form-alert").animate({opacity: 1.0}, 5000);
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

    $mandateChangeMenu = $page->getMenu();
    $mandateChangeMenu->addItem('menu_item_back', $gNavigation->getUrl(), $gL10n->get('SYS_BACK'), 'back.png');

    $form = new HtmlForm('mandate_change_form', null, $page, array('class' => 'form-mandate_change')); 
    $form->addInput('mandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID'), $user->getValue('MANDATEID'.$gCurrentOrganization->getValue('org_id')),array('property' => FIELD_REQUIRED));
	$html = '<a class="iconLink" id="mandatschieben" href="javascript:mandatschieben()"><img 
			src="'. THEME_PATH. '/icons/arrow_down.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_MANDATEID').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_MANDATEID').'" /></a>';
    $form->addCustomContent('', $html);	
	$form->addInput('origmandateid', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID'), $user->getValue('ORIG_MANDATEID'.$gCurrentOrganization->getValue('org_id')),array('property' => FIELD_DISABLED));
	$form->addInput('iban', $gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN'), $user->getValue('IBAN'),array('property' => FIELD_REQUIRED));
    $html = '<a class="iconLink" id="ibanschieben" href="javascript:ibanschieben()"><img 
			src="'. THEME_PATH. '/icons/arrow_down.png" alt="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_IBAN').'" title="'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MOVE_IBAN').'" /></a>';
    $form->addCustomContent('', $html);	
    $form->addInput('origiban', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN'), $user->getValue('ORIG_IBAN'),array('property' => FIELD_DISABLED));
	$form->addCheckbox('bankchanged', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK_CHANGED'), 0, array('class'=>'bank_changed_checkbox'));  
	$form->addInput('bic', $gL10n->get('PLG_MITGLIEDSBEITRAG_BIC'), $user->getValue('BIC'),array('property' => FIELD_DISABLED));        
	$form->addInput('bank', $gL10n->get('PLG_MITGLIEDSBEITRAG_BANK'), $user->getValue('BANK'),array('property' => FIELD_DISABLED));
	$form->addInput('origdebtoragent', $gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT'), $user->getValue('ORIG_DEBTOR_AGENT'),array('property' => FIELD_DISABLED));
	$html = '<div class="alert alert-warning alert-small" role="alert"><span class="glyphicon glyphicon-warning-sign"></span>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_CHANGE_DBTR_INFO').'</div>';
    $form->addCustomContent('', $html);	
    
    $form->addSubmitButton('btn_save_configurations', $gL10n->get('SYS_SAVE'), array('icon' => THEME_PATH.'/icons/disk.png', 'class' => ' col-sm-offset-3'));
    
    $page->addHtml($form->show(false));
                       
    $page->show();  
}
