<?php
/**
 ***********************************************************************************************
 * Verarbeiten der Einstellungen des Admidio-Plugins Mitgliedsbeitrag
 *
 * @copyright 2004-2018 The Admidio Team
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

// Marker fuer Rueckgabecode
$echomarker = 0;

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

            $text = new TableText($gDb);
            $text->readDataByColumns(array('txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS', 'txt_org_id' => ORG_ID));
            $text->setValue('txt_text', $_POST['mail_text']);
            $text->save();
            break;

        case 'agestaggeredroles':
            unset($pPreferences->config['Altersrollen']);
            $altersrollen_anzahl = 0;
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
                $pPreferences->config['Familienrollen']['familienrollen_beitrag'][] = $_POST['familienrollen_beitrag'. $conf];
                $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][] = $_POST['familienrollen_zeitraum'. $conf];
                $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][] = $_POST['familienrollen_beschreibung'. $conf];
                $pPreferences->config['Familienrollen']['familienrollen_pruefung'][] = isset($familienrollen_pruefung[$conf]) ? $familienrollen_pruefung[$conf] : '';
            }
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
                $pPreferences->config['SEPA']['dateiname'],
                $pPreferences->config['SEPA']['kontroll_dateiname'],
                $pPreferences->config['SEPA']['vorabinformation_dateiname'],
                $pPreferences->config['Rechnungs-Export']
            );

            $pPreferences->config['SEPA']['dateiname'] = $_POST['dateiname'];
            $pPreferences->config['SEPA']['kontroll_dateiname'] = $_POST['kontroll_dateiname'];
            $pPreferences->config['SEPA']['vorabinformation_dateiname'] = $_POST['vorabinformation_dateiname'];
            $pPreferences->config['Rechnungs-Export']['rechnung_dateiname'] = $_POST['rechnung_dateiname'];

            $text = new TableText($gDb);
            $text->readDataByColumns(array('txt_name' => 'PMBMAIL_PRE_NOTIFICATION', 'txt_org_id' => ORG_ID));
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
            unset(
                $pPreferences->config['Familienrollen']['familienrollen_pruefung'],
                $pPreferences->config['Rollenpruefung']
            );

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

        default:
            $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
    }
}
catch(AdmException $e)
{
    $e->showText();
}

$pPreferences->save();

if ($echomarker == 0)
{
    echo 'success';
}
elseif($echomarker == 1)
{
    echo 'convert_error';
}
