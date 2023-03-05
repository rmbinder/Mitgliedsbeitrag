<?php
/**
 ***********************************************************************************************
 * Verarbeiten der Einstellungen des Admidio-Plugins Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * form         : The name of the form preferences that were submitted.
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');
require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// Initialize and check the parameters
$getForm = admFuncVariableIsValid($_GET, 'form', 'string');

// in ajax mode return simple text on error
$gMessage->showHtmlTextOnly(true);

// Rueckgabecode
$ret = 'success';

try
{
    switch($getForm)
    {
        case 'contributionsettings':
            unset($pPreferences->config['Beitrag']);

            $pPreferences->config['Beitrag']['beitrag_prefix'] = $_POST['beitrag_prefix'];
            $pPreferences->config['Beitrag']['beitrag_suffix'] = $_POST['beitrag_suffix'];
            $pPreferences->config['Beitrag']['beitrag_anteilig'] = isset($_POST['beitrag_anteilig']) ? 1 : 0;
            $pPreferences->config['Beitrag']['beitrag_abrunden'] = isset($_POST['beitrag_abrunden']) ? 1 : 0;
            $pPreferences->config['Beitrag']['beitrag_mindestbetrag'] = $_POST['beitrag_mindestbetrag'];
            $pPreferences->config['Beitrag']['beitrag_textmitnam'] = isset($_POST['beitrag_textmitnam']) ? 1 : 0;
            $pPreferences->config['Beitrag']['beitrag_textmitfam'] = isset($_POST['beitrag_textmitfam']) ? 1 : 0;
            $pPreferences->config['Beitrag']['beitrag_text_token'] = $_POST['beitrag_text_token'];
            break;

        case 'agestaggeredroles':
            unset($pPreferences->config['Altersrollen']);
            $altersrollen_anzahl = 0;
            $pPreferences->config['Altersrollen']['altersrollen_offset'] = $_POST['altersrollen_offset'];

            for($conf = 0; isset($_POST['altersrollen_token'. $conf]); $conf++)
            {
                if (empty($_POST['altersrollen_token'. $conf]))
                {
                    continue;
                }
                $pPreferences->config['Altersrollen']['altersrollen_token'][] = $_POST['altersrollen_token'. $conf];
                $altersrollen_anzahl++;
            }
            //diese Zeile ist nur zur Sicherheit, falls ein Nutzer einen Refresh (F5) des Browsers ausfuehrt
            //und dadurch nicht durch das Plugin kontrollierte Loeschungen oder Hinzufuegungen ausfuehrt
            if($altersrollen_anzahl == 0)
            {
                $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR_MIN_CONFIG'));
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
                $pPreferences->config['Familienrollen']['familienrollen_beitrag'][] = str_replace(',', '.', $_POST['familienrollen_beitrag'. $conf]);
                $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][] = $_POST['familienrollen_zeitraum'. $conf];
                $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][] = $_POST['familienrollen_beschreibung'. $conf];
                $pPreferences->config['Familienrollen']['familienrollen_pruefung'][] = isset($familienrollen_pruefung[$conf]) ? $familienrollen_pruefung[$conf] : '';
            }
            break;

        case 'advancedroleediting':
            $altersrollen = beitragsrollen_einlesen('alt');
            $fixrollen = beitragsrollen_einlesen('fix');
            $alt_fix_rollen = $altersrollen + $fixrollen;
            unset($altersrollen);
            unset($fixrollen);
            
            $role = new TableRoles($gDb);
            
            foreach($alt_fix_rollen as $rol_id => $dummy)
            {
                $role->readDataById((int) $rol_id);
                $role->setvalue('rol_cost', str_replace(',', '.', $_POST['rol_cost'. $rol_id]));
                $role->setvalue('rol_cost_period', $_POST['rol_cost_period'. $rol_id]);
                $role->setvalue('rol_description', $_POST['rol_description'. $rol_id]);
                $role->save();
            }
            $ret = 'success_replace';
            break;
            
        case 'events':
            if (isset($_POST['events']))
            {
                $role = new TableRoles($gDb);
                foreach(array_filter($_POST['events']) as $rol_id)
                {
                    $role->readDataById((int) $rol_id);
                    $role->setvalue('rol_cost', 0);
                    $role->setvalue('rol_cost_period', -1);
                    $role->save();
                }
            }
            $ret = 'success_replace';
            break;
        case 'accountdata':
            unset($pPreferences->config['Kontodaten']);

            $pPreferences->config['Kontodaten']['iban'] = $_POST['iban'];
            $pPreferences->config['Kontodaten']['bic'] = $_POST['bic'];
            $pPreferences->config['Kontodaten']['bank'] = $_POST['bank'];
            $pPreferences->config['Kontodaten']['inhaber'] = $_POST['creditor'];
            $pPreferences->config['Kontodaten']['origcreditor'] = isset($_POST['origcreditor']) ? $_POST['origcreditor'] : '';
            $pPreferences->config['Kontodaten']['ci'] = $_POST['ci'];
            $pPreferences->config['Kontodaten']['origci'] = isset($_POST['origci']) ? $_POST['origci'] : '';
            break;

        case 'export':
            unset(
                $pPreferences->config['SEPA'],
                $pPreferences->config['Rechnungs-Export']
            );

            $pPreferences->config['SEPA']['dateiname'] = $_POST['dateiname'];
            $pPreferences->config['SEPA']['kontroll_dateiname'] = $_POST['kontroll_dateiname'];
            $pPreferences->config['SEPA']['kontroll_dateityp'] = $_POST['kontroll_dateityp'];
            $pPreferences->config['SEPA']['vorabinformation_dateiname'] = $_POST['vorabinformation_dateiname'];
            $pPreferences->config['SEPA']['vorabinformation_dateityp'] = $_POST['vorabinformation_dateityp'];
            $pPreferences->config['Rechnungs-Export']['rechnung_dateiname'] = $_POST['rechnung_dateiname'];
            $pPreferences->config['Rechnungs-Export']['rechnung_dateityp'] = $_POST['rechnung_dateityp'];
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
            unset(
                $pPreferences->config['Familienrollen']['familienrollen_pruefung'],
                $pPreferences->config['Rollenpruefung'],
                $pPreferences->config['tests_enable']
            );
            $pPreferences->config['tests_enable']['age_staggered_roles'] = isset($_POST['age_staggered_roles']) ? 1 : 0;
            $pPreferences->config['tests_enable']['role_membership_age_staggered_roles'] = isset($_POST['role_membership_age_staggered_roles']) ? 1 : 0;
            $pPreferences->config['tests_enable']['role_membership_duty_and_exclusion'] = isset($_POST['role_membership_duty_and_exclusion']) ? 1 : 0;
            $pPreferences->config['tests_enable']['family_roles'] = isset($_POST['family_roles']) ? 1 : 0;
            $pPreferences->config['tests_enable']['account_details'] = isset($_POST['account_details']) ? 1 : 0;
            $pPreferences->config['tests_enable']['mandate_management'] = isset($_POST['mandate_management']) ? 1 : 0;
            $pPreferences->config['tests_enable']['iban_check'] = isset($_POST['iban_check']) ? 1 : 0;
            $pPreferences->config['tests_enable']['bic_check'] = isset($_POST['bic_check']) ? 1 : 0;
            
            for($conf = 0; isset($_POST['familienrollen_pruefung'. $conf]); $conf++)
            {
                $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$conf] = $_POST['familienrollen_pruefung'. $conf];
            }

            if (isset($_POST['familienrollenpflicht']))
            {
            	$pPreferences->config['Rollenpruefung']['familienrollenpflicht'] = $_POST['familienrollenpflicht'];
            }
            
            $fixrollen = beitragsrollen_einlesen('fix');
            foreach($fixrollen as $key => $data)
            {
                if(isset($_POST['fixrollenpflicht'. $key]))
                {
                    $pPreferences->config['Rollenpruefung']['fixrollenpflicht'][] = $key;
                }
                foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
                {
                    if(isset($_POST['altersrollenfix'. $token.$key]))
                    {
                        $pPreferences->config['Rollenpruefung']['altersrollenfix'][] = $token.$key;
                    }
                }

                if(isset($_POST['familienrollenfix'. $key]))
                {
                    $pPreferences->config['Rollenpruefung']['familienrollenfix'][] = $key;
                }
            }

            if ((count($pPreferences->config['Altersrollen']['altersrollen_token']) > 1))
            {
                for ($x = 0; $x < count($pPreferences->config['Altersrollen']['altersrollen_token'])-1; $x++)
                {
                    for ($y = $x+1; $y < count($pPreferences->config['Altersrollen']['altersrollen_token']); $y++)
                    {
                        if(isset($_POST['altersrollenaltersrollen'.$pPreferences->config['Altersrollen']['altersrollen_token'][$x].$pPreferences->config['Altersrollen']['altersrollen_token'][$y]]))
                        {
                            $pPreferences->config['Rollenpruefung']['altersrollenaltersrollen'][] = $pPreferences->config['Altersrollen']['altersrollen_token'][$x].','.$pPreferences->config['Altersrollen']['altersrollen_token'][$y];
                        }
                    }
                }
            }

            foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
            {
                if(isset($_POST['age_staggered_roles_exclusion'. $token]))
                {
                    $pPreferences->config['Rollenpruefung']['age_staggered_roles_exclusion'][] = $token;
                    
                }
                if(isset($_POST['altersrollenpflicht'. $token]))
                {
                    $pPreferences->config['Rollenpruefung']['altersrollenpflicht'][] = $token;

                }
                if(isset($_POST['altersrollenfamilienrollen'. $token]))
                {
                    $pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen'][] = $token;
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
            		foreach ($fixrollenR as $keyR => $dataR)
            		{
            			if (isset($_POST['fixrollenfixrollen'.$keyL.'_'.$keyR]))
            			{
            				$pPreferences->config['Rollenpruefung']['fixrollenfixrollen'][] = $keyL.'_'.$keyR;
            			}
            		}
            	}
            	unset($fixrollenL);
            	unset($fixrollenR);
            }
            
            if(isset($_POST['bezugskategorie']))
            {
            	$pPreferences->config['Rollenpruefung']['bezugskategorie'] = $_POST['bezugskategorie'];
            	
            }
            
            foreach ($pPreferences->config_default['Rollenpruefung'] as $roleTest => $dummy)
            {
            	if (!isset($pPreferences->config['Rollenpruefung'][$roleTest]))
            	{
            		$pPreferences->config['Rollenpruefung'][$roleTest] = $pPreferences->config_default['Rollenpruefung'][$roleTest];
            	}
            }
            break;

        case 'columnset':
        	foreach ($pPreferences->config['columnconfig'] as $conf => $confFields)
        	{ 
        		$pPreferences->config['columnconfig'][$conf] = array();

        		for ($number = 1; isset($_POST['column'.$conf.'_'.$number]); $number++)
        		{
        			if (strlen($_POST['column'.$conf.'_'.$number]) > 0)
        			{		
        				$pPreferences->config['columnconfig'][$conf][] = $_POST['column'.$conf.'_'.$number];
        			}
        		}
        	}
        	break; 
        	
        case 'individualcontributions':
            $pPreferences->config['individual_contributions']['access_to_module'] = $_POST['enable_individual_contributions'];
            
            unset($pPreferences->config['individual_contributions']['desc']);
            unset($pPreferences->config['individual_contributions']['short_desc']);
            unset($pPreferences->config['individual_contributions']['role']);
            unset($pPreferences->config['individual_contributions']['amount']);
            unset($pPreferences->config['individual_contributions']['profilefield']);
            
            for($conf = 0; isset($_POST['individual_contributions_desc'. $conf]); $conf++)
            {
                if (empty($_POST['individual_contributions_desc'. $conf]))
                {
                    continue;
                }
          
                $pPreferences->config['individual_contributions']['desc'][] = $_POST['individual_contributions_desc'. $conf];
                $pPreferences->config['individual_contributions']['short_desc'][] = $_POST['individual_contributions_short_desc'. $conf];
                $pPreferences->config['individual_contributions']['role'][] = $_POST['individual_contributions_role'. $conf];
                $pPreferences->config['individual_contributions']['amount'][] = $_POST['individual_contributions_amount'. $conf];
                $pPreferences->config['individual_contributions']['profilefield'][] = $_POST['individual_contributions_profilefield'. $conf];
            }
            break; 
        	
        case 'access_preferences':
            if (isset($_POST['access_preferences']))
            {
                $pPreferences->config['access']['preferences'] = array_filter($_POST['access_preferences']);
            }
            else 
            {
                $pPreferences->config['access']['preferences'] = array();
            }
            break;
            
        case 'multiplier_roles':
            if (isset($_POST['multiplier_roles']))
            {
                $pPreferences->config['multiplier']['roles'] = array_filter($_POST['multiplier_roles']);
            }
            else
            {
                $pPreferences->config['multiplier']['roles'] = array();
            }
            break;
            
        case 'emailnotifications':
            
            $text = new TableText($gDb);
            
            $text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => $gCurrentOrgId));
            $text->setValue('txt_text', $_POST['mail_text']);
            $text->save();
            
            $text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => $gCurrentOrgId));
            $text->setValue('txt_text', $_POST['pre_notification_text']);
            $text->save();
            
            $text->readDataByColumns(array('txt_name' => 'PMBMAIL_BILL', 'txt_org_id' => $gCurrentOrgId));
            $text->setValue('txt_text', $_POST['bill_text']);
            $text->save();
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

echo $ret;
