<?php
/******************************************************************************
 * 
 * preferences_function.php
 * 
 * Verarbeiten der Einstellungen des Admidio-Plugins Mitgliedsbeitrag
 * 
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 * Parameters:
 *
 * form         - The name of the form preferences that were submitted.
 * 
 ****************************************************************************/

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

// in ajax mode return simple text on error
$gMessage->showHtmlTextOnly(true);

// Marker für Rückgabecode
$echomarker = 0;

try
{
	switch($getForm)
    {
    	case 'contributionsettings':
			unset($pPreferences->config['Beitrag']);
				
    		$pPreferences->config['Beitrag']['beitrag_prefix'] = $_POST['beitrag_prefix'];
    		$pPreferences->config['Beitrag']['beitrag_suffix'] = $_POST['beitrag_suffix'];
    		$pPreferences->config['Beitrag']['beitrag_anteilig'] = isset($_POST['beitrag_anteilig']) ? 1 : 0 ;
    		$pPreferences->config['Beitrag']['beitrag_abrunden'] = isset($_POST['beitrag_abrunden']) ? 1 : 0 ;
    		$pPreferences->config['Beitrag']['beitrag_mindestbetrag'] = $_POST['beitrag_mindestbetrag'];
    		$pPreferences->config['Beitrag']['beitrag_textmitnam'] = isset($_POST['beitrag_textmitnam']) ? 1 : 0 ;
    		$pPreferences->config['Beitrag']['beitrag_textmitfam'] = isset($_POST['beitrag_textmitfam']) ? 1 : 0 ;
    		$pPreferences->config['Beitrag']['beitrag_text_token'] = $_POST['beitrag_text_token'];
    			
    		$text = new TableText($gDb);
    		$text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => $gCurrentOrganization->getValue('org_id')));
			$text->setValue('txt_text', $_POST['mail_text']);
            $text->save();
            break; 
            	
        case 'agestaggeredroles':
			unset($pPreferences->config['Altersrollen']);
			$altersrollen_anzahl=0;
			$pPreferences->config['Altersrollen']['altersrollen_stichtag'] = $_POST['altersrollen_stichtag'];
				
    		for($conf = 0; isset($_POST['altersrollen_token'. $conf]); $conf++)
    		{
    			if (empty($_POST['altersrollen_token'. $conf]))	
    			{
    				continue;
    			}
        		$pPreferences->config['Altersrollen']['altersrollen_token'][] = $_POST['altersrollen_token'. $conf];
        		$altersrollen_anzahl++;
    		}
    		//diese Zeile ist nur zur Sicherheit, falls ein Nutzer einen Refresh (F5) des Browsers ausführt
    		// und dadurch nicht durch das Plugin kontrollierte Löschungen oder Hinzufügungen ausführt
    		if($altersrollen_anzahl==0)
    		{
    			$gMessage->show($gL10n->get('PMB_ERROR_MIN_CONFIG'));
    		}
            break; 
            	
		case 'familyroles':
            //familienrollen_pruefung zwischenspeichern
            $familienrollen_pruefung = $pPreferences->config['Familienrollen']['familienrollen_pruefung'];
            	
			unset($pPreferences->config['Familienrollen']);
				
    		for($conf = 0; isset($_POST['familienrollen_prefix'. $conf]); $conf++)
    		{
    			if (empty($_POST['familienrollen_prefix'. $conf]))	
    			{
    				continue;
    			}
    				
        		$pPreferences->config['Familienrollen']['familienrollen_prefix'][] = $_POST['familienrollen_prefix'. $conf];
        		$pPreferences->config['Familienrollen']['familienrollen_beitrag'][] = $_POST['familienrollen_beitrag'. $conf];
        		$pPreferences->config['Familienrollen']['familienrollen_zeitraum'][] = $_POST['familienrollen_zeitraum'. $conf];
        		$pPreferences->config['Familienrollen']['familienrollen_beschreibung'][] = $_POST['familienrollen_beschreibung'. $conf];
        		$pPreferences->config['Familienrollen']['familienrollen_pruefung'][] = isset($familienrollen_pruefung[$conf]) ? $familienrollen_pruefung[$conf] : '' ;
    		}

    		// alle Familienkonfigurationen durchlaufen
			foreach($pPreferences->config['Familienrollen']['familienrollen_prefix'] as $key => $data)
    		{ 
    			// zuerst alle Familienrollen herausfinden 
    			$sql = 'SELECT rol_name, rol_id, rol_cost, rol_cost_period
            			FROM '.TBL_ROLES.' 
            			WHERE rol_name LIKE \''. $data.'%'. '\' ';
                                
    			$result = $gDb->query($sql); 
    	   	
        		// jetzt den neuen Betrag, den Beitragszeitraum und die Beschreibung in die DB schreiben
    			while ($row = $gDb->fetch_array($result))
    			{
        			$sql = 'UPDATE '.TBL_ROLES.'
                			SET rol_cost = \''.$pPreferences->config['Familienrollen']['familienrollen_beitrag'][$key].'\'
                			WHERE rol_id = \''.$row['rol_id'].'\' '; 
           
        			$gDb->query($sql);
 
        			$sql = 'UPDATE '.TBL_ROLES.'
            				SET rol_cost_period = \''.$pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$key].'\'
            				WHERE rol_id = \''.$row['rol_id'].'\' '; 
           
        			$gDb->query($sql); 
     
        			$sql = 'UPDATE '.TBL_ROLES.'
                			SET rol_description = \''.$pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$key].'\'
                			WHERE rol_id = \''.$row['rol_id'].'\' '; 
           
        			$gDb->query($sql); 
    			}
    		}    	
    		break; 
            	
    	case 'accountdata':
			unset($pPreferences->config['Kontodaten']);
				
    		$pPreferences->config['Kontodaten']['ktonr'] = $_POST['ktonr'];
    		$pPreferences->config['Kontodaten']['blz'] = $_POST['blz'];
    		$pPreferences->config['Kontodaten']['iban'] = $_POST['iban'];
    		$pPreferences->config['Kontodaten']['bic'] = $_POST['bic'];
    		$pPreferences->config['Kontodaten']['bank'] = $_POST['bank'];
    		$pPreferences->config['Kontodaten']['inhaber'] = $_POST['creditor'];
    		$pPreferences->config['Kontodaten']['origcreditor'] = isset($_POST['origcreditor']) ? $_POST['origcreditor'] : '' ;
    		$pPreferences->config['Kontodaten']['ci'] = $_POST['ci'];
    		$pPreferences->config['Kontodaten']['origci'] = isset($_POST['origci']) ? $_POST['origci'] : '' ;
            break; 
            	
		case 'convert':
            	
            // wenn BIC oder Bankname gewählt wurde, dann muss auch eine Bankleitzahlendatei ausgewählt worden sein
			// --> überprüfen
			if (isset($_POST['generateBIC']) || isset($_POST['generateBank']) )
			{
    			//Dateigroesse ueberpruefen Servereinstellungen
    			if ($_FILES['userfile']['error']==1) 
    			{
      				$gMessage->show($gL10n->get('SYS_FILE_TO_LARGE_SERVER', $gPreferences['max_file_upload_size']));
    			}
    
    			//Dateigroesse ueberpruefen Administratoreinstellungen
    			if (($_FILES['userfile']['size']>($gPreferences['max_file_upload_size'])*1024) || ($_FILES['userfile']['error']==2) )
    			{
       				$gMessage->show($gL10n->get('DOW_FILE_TO_LARGE', $gPreferences['max_file_upload_size']));
    			}
    
    			// Dateinamen ermitteln
    			$file_name = $_FILES['userfile']['name'];

    			// pruefen, ob der Dateiname gueltig ist
    			$ret_code = isValidFileName($file_name, true);

    			if($ret_code < 0)
    			{
        			if($ret_code == -1)
        			{
            			$gMessage->show($gL10n->get('SYS_FIELD_EMPTY', $gL10n->get('DOW_CHOOSE_FILE')));
        			}
        			elseif($ret_code == -2)
        			{
            			$gMessage->show($gL10n->get('DOW_FILE_NAME_INVALID'));
        			}
        			elseif($ret_code == -3)
        			{
            			$gMessage->show($gL10n->get('DOW_FILE_EXTENSION_INVALID'));
        			}
    			}
    
				if ($_FILES['userfile']['error']==0)
				{
					// bei einem Upload von lokal auf Server ist $_FILES['userfile']['tmp_name'] befüllt
					// dann diesen temporären Dateinamen verwenden
					// wenn sich die blz-Datei bereits auf dem Server befindet (Testumgebung),
					//      dann ist $_FILES['userfile']['tmp_name'] leer
					// dann den originalen Dateinamen verwenden		 
					if (strlen($_FILES['userfile']['tmp_name'])<>0)
					{
						$blz_array = file ($_FILES['userfile']['tmp_name']);			
					}
					else 
					{
						$blz_array = file ($_FILES['userfile']['name']);			
					}
				}
				else 
				{
					$gMessage->show($gL10n->get('DOW_FILE_UPLOAD_ERROR',$file_name));	
				}    
			}

			$echomarker = 1;

			$members = list_members(array('KONTONUMMER','BANKLEITZAHL','IBAN','BIC','BANKNAME'), 0)  ;

			//alle Mitglieder durchlaufen
			foreach ($members as $member => $memberdata)
			{
				$user = new User($gDb, $gProfileFields, $member);
	
				// IBAN erzeugen
    			if (empty($memberdata['IBAN']) && !empty($memberdata['KONTONUMMER']) && !empty($memberdata['BANKLEITZAHL']) )
    			{
					$user->setValue('IBAN', iban_berechnung_DE($memberdata['BANKLEITZAHL'],$memberdata['KONTONUMMER']));
					$echomarker = 0;
    			}
    
    			// BIC erzeugen
				if ( empty($memberdata['BIC']) && !empty($memberdata['BANKLEITZAHL']) &&  isset($_POST['generateBIC']) )
    			{
    				foreach ($blz_array as $data)
					{
						if ( (substr($data,0,8) == $memberdata['BANKLEITZAHL']) && (substr($data,8,1) =='1') )
    					{
    						$user->setValue('BIC', substr($data,139,11));
    							$echomarker = 0;
    					}
					}
    			}

    			// Bankname erzeugen
				if ( empty($memberdata['BANKNAME']) && !empty($memberdata['BANKLEITZAHL']) &&  isset($_POST['generateBank']) )
    			{
    				foreach ($blz_array as $data)
					{
						if ( (substr($data,0,8) == $memberdata['BANKLEITZAHL']) && (substr($data,8,1) =='1') )
    					{
    						$user->setValue('BANKNAME', umlaute(substr($data,9,58)));
    						$echomarker = 0;
    					}
					}	
    			}
    			$user->save();
			}
            break;  
            	
       	case 'export':
       		unset($pPreferences->config['SEPA']['dateiname']);
       		unset($pPreferences->config['SEPA']['kontroll_dateiname']);
       		unset($pPreferences->config['SEPA']['vorabinformation_dateiname']);
       		unset($pPreferences->config['Rechnungs-Export']);
       			
       		$pPreferences->config['SEPA']['dateiname'] = $_POST['dateiname'];
    		$pPreferences->config['SEPA']['kontroll_dateiname'] = $_POST['kontroll_dateiname'];
    		$pPreferences->config['SEPA']['vorabinformation_dateiname'] = $_POST['vorabinformation_dateiname'];
    		$pPreferences->config['Rechnungs-Export']['rechnung_dateiname'] = $_POST['rechnung_dateiname'];
       				
 	        $text = new TableText($gDb);
    		$text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => $gCurrentOrganization->getValue('org_id')));
			$text->setValue('txt_text', $_POST['pre_notification_text']);
            $text->save();
            break;  
            	
		case 'mandatemanagement':
       		unset($pPreferences->config['Mandatsreferenz']);
       			
       		$pPreferences->config['Mandatsreferenz']['prefix_fam'] = $_POST['prefix_fam'];
       		$pPreferences->config['Mandatsreferenz']['prefix_mem'] = $_POST['prefix_mem'];
       		$pPreferences->config['Mandatsreferenz']['prefix_pay'] = $_POST['prefix_pay'];
       		$pPreferences->config['Mandatsreferenz']['min_length'] = $_POST['min_length'];
       		$pPreferences->config['Mandatsreferenz']['data_field'] = $_POST['data_field'];
            break;  
            		
		case 'testssetup':
    		unset($pPreferences->config['Familienrollen']['familienrollen_pruefung']);
    		unset($pPreferences->config['Rollenpruefung']);
			
    		for($conf = 0; isset($_POST['familienrollen_pruefung'. $conf]); $conf++)
    		{
        		$pPreferences->config['Familienrollen']['familienrollen_pruefung'][$conf] = $_POST['familienrollen_pruefung'. $conf];
    		}
    			
    		$pPreferences->config['Rollenpruefung']['altersrollenpflicht'] = isset($_POST['altersrollenpflicht']) ? $_POST['altersrollenpflicht'] : '' ;
       		$pPreferences->config['Rollenpruefung']['familienrollenpflicht'] = isset($_POST['familienrollenpflicht']) ? $_POST['familienrollenpflicht'] : '';
    			
       		$fixrollen = beitragsrollen_einlesen('fix');
       		foreach($fixrollen as $key => $data)
            {
                if(isset($_POST['fixrollenpflicht'. $key]))
                {
                	$pPreferences->config['Rollenpruefung']['fixrollenpflicht'][] = $key;
                }
                if(isset($_POST['altersrollenfix'. $key]))
                {
                	$pPreferences->config['Rollenpruefung']['altersrollenfix'][] = $key;
                }
                if(isset($_POST['familienrollenfix'. $key]))
                {
                	$pPreferences->config['Rollenpruefung']['familienrollenfix'][] = $key;
                }
			} 

            $pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen'] = isset($_POST['altersrollenfamilienrollen']) ? $_POST['altersrollenfamilienrollen'] : '' ;
            $pPreferences->config['Rollenpruefung']['bezugskategorie'] = isset($_POST['bezugskategorie']) ? $_POST['bezugskategorie'] : array(' ');
			break;  	  
            	
        case 'plugin_control':
            unset($pPreferences->config['Pluginfreigabe']);
    		$pPreferences->config['Pluginfreigabe']['freigabe'] = isset($_POST['freigabe']) ? $_POST['freigabe'] : $pPreferences->config_default['Pluginfreigabe']['freigabe'];
    		$pPreferences->config['Pluginfreigabe']['freigabe_config'] = isset($_POST['freigabe_config']) ? $_POST['freigabe_config'] : $pPreferences->config_default['Pluginfreigabe']['freigabe_config'];
            break;
            
        default:
           	$gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    }
}
catch(AdmException $e)
{
	$e->showText();
}    
    
$pPreferences->save();

if ($echomarker==0)
{
	echo 'success';         
}
elseif($echomarker==1)
{
	echo 'convert_error'; 
}
	
?>