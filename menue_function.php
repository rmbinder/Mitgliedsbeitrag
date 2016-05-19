<?php
/**
 ***********************************************************************************************
 * Verarbeiten der Menueeinstellungen des Admidio-Plugins Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * form         : The name of the form preferences that were submitted.
 *
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

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// Initialize and check the parameters
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');

$gMessage->showHtmlTextOnly(true);

$ret_message = 'success';
	
try
{
	switch($getForm)
    {           	
       	case 'delete':
			$members = array();
			$members = list_members(array('PAID'.$gCurrentOrganization->getValue('org_id'),'FEE'.$gCurrentOrganization->getValue('org_id'),'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'),'DUEDATE'.$gCurrentOrganization->getValue('org_id')), 0)  ;

			foreach ($members as $key => $data)
			{
				$user = new User($gDb, $gProfileFields, $key);

    			if (!empty($data['DUEDATE'.$gCurrentOrganization->getValue('org_id')])
	   				&&  isset($_POST['duedate_only'])  )
    			{
  	     			$user->setValue('DUEDATE'.$gCurrentOrganization->getValue('org_id'), '');	
    			}

    			if (!empty($data['PAID'.$gCurrentOrganization->getValue('org_id')])
	   				&& (   isset($_POST['with_paid'])
						|| isset($_POST['paid_only'])
						|| isset($_POST['delete_all'])   ) )
    			{
   	    			$user->setValue('PAID'.$gCurrentOrganization->getValue('org_id'), '');
    			}
	
    			if (!empty($data['FEE'.$gCurrentOrganization->getValue('org_id')])
	   				&& (   (isset($_POST['with_paid']) && !empty($data['PAID'.$gCurrentOrganization->getValue('org_id')]))
		  				|| (isset($_POST['without_paid'])&& empty($data['PAID'.$gCurrentOrganization->getValue('org_id')]))
		  				|| isset($_POST['delete_all'])  ) )
    			{
  	     			$user->setValue('FEE'.$gCurrentOrganization->getValue('org_id'), '');
    			}
	
				if (!empty($data['CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id')])
					&& (   (isset($_POST['with_paid']) && !empty($data['PAID'.$gCurrentOrganization->getValue('org_id')]))
						|| (isset($_POST['without_paid'])&& empty($data['PAID'.$gCurrentOrganization->getValue('org_id')]))
						|| isset($_POST['delete_all'])    ) )
				{
   					$user->setValue('CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), '');
				}
  				$user->save();
			}        			
       		$ret_message= 'delete';
           	break;  

        case 'recalculation':
            $pPreferences->config['Beitrag']['beitrag_rollenwahl'] = isset($_POST['beitrag_rollenwahl']) ? $_POST['beitrag_rollenwahl'] : array(' ');
			$pPreferences->config['Beitrag']['beitrag_modus'] = $_POST['beitrag_modus'];
			$pPreferences->save();
            break; 
            	
        case 'payments':
            $pPreferences->config['Beitrag']['zahlungen_rollenwahl'] = isset($_POST['zahlungen_rollenwahl']) ? $_POST['zahlungen_rollenwahl'] : array(' ');
			$pPreferences->save();
            break; 	
            	
        case 'sepa':
            $pPreferences->config['SEPA']['duedate_rollenwahl'] = isset($_POST['duedate_rollenwahl']) ? $_POST['duedate_rollenwahl'] : array(' ');
			$pPreferences->save();
            break; 	
            	
       	case 'plugin_control':
            unset($pPreferences->config['Pluginfreigabe']);
    		$pPreferences->config['Pluginfreigabe']['freigabe'] = $_POST['freigabe'];
    		$pPreferences->config['Pluginfreigabe']['freigabe_config'] = $_POST['freigabe_config'];
            break;
            
        default:
           	$gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    }
}
catch(AdmException $e)
{
	$e->showText();
}    

echo $ret_message;
