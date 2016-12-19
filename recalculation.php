<?php
/**
 ***********************************************************************************************
 * Neuberechnung der Mitgliedsbeitraege fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:       keine
 *
 ***********************************************************************************************
 */

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php');

// Konfiguration einlesen
$pPreferences = new ConfigTablePMB();
$pPreferences->read();

// only authorized user are allowed to start this module
if(!check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']))
{
	$gMessage->setForwardUrl($gHomepage, 3000);
    $gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// anstelle eines Leerzeichens ist ein # in der $pPreferences->config gespeichert; # wird hier wieder ersetzt
$text_token = ($pPreferences->config['Beitrag']['beitrag_text_token']=='#') ? ' ' : $pPreferences->config['Beitrag']['beitrag_text_token'];
$message = '';

//alle Beitragsrollen einlesen
$rols = beitragsrollen_einlesen('', array('FIRST_NAME', 'LAST_NAME', 'IBAN', 'DEBTOR'));

//falls eine Rollenabfrage durchgeführt wurde, die Rollen, die nicht gewählt wurden, löschen
if ($pPreferences->config['Beitrag']['beitrag_rollenwahl'][0]!=' ')
{
	$message .= '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTION_ROLLQUERY_INFO').'</strong><BR><BR>';
	foreach ($rols as $rol => $roldata)
	{
		if (!in_array($rol, $pPreferences->config['Beitrag']['beitrag_rollenwahl']))
		{
			unset($rols[$rol]);
		}
		else
		{
			$message .= $roldata['rolle'].'<BR>';
		}
	}
	$message .= '<BR><BR>';
}

// diese Rollen durchlaufen und bei den Familienrollen eine Zahlungspflichtigen bestimmen
foreach ($rols as $rol => $roldata)
{
   	// nur Familien
	if ($roldata['rollentyp']== 'fam')
    {
        // alle Mitglieder dieser Rolle durchlaufen und einen Zahlungspflichtigen bestimmen
        // 1. Durchlauf: hierbei das erste Mitglied bei dem (Kontonummer UND BLZ) oder IBAN belegt sind bestimmen
        foreach ($roldata['members'] as $key => $data)
        {
            $rols[$rol]['has_to_pay'] = $key;

            if(strlen($data['IBAN'])!=0)
            {
                $rols[$rol]['has_to_pay'] = $key;
                break;
            }
        }
        // alle Mitglieder dieser Rolle durchlaufen und einen Zahlungspflichtigen bestimmen
        // 2. Durchlauf: gibt es einen Rollenleiter, dann den Zahlungspflichtigen überschreiben, da höherwertiger
        foreach ($roldata['members'] as $key => $data)
        {
            if (isGroupLeader($key, $rol))
            {
                $rols[$rol]['has_to_pay'] = $key;
                break;
            }
        }
    }
}

// alle aktiven Mitglieder einlesen
$members = list_members(array('FIRST_NAME', 'LAST_NAME', 'FEE'.$gCurrentOrganization->getValue('org_id'), 'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), 'PAID'.$gCurrentOrganization->getValue('org_id'), 'ACCESSION'.$gCurrentOrganization->getValue('org_id'), 'DEBTOR'), 0);

//alle Mitglieder durchlaufen und aufgrund von Rollenzugehörigkeiten die Beiträge bestimmen
foreach ($members as $member => $memberdata)
{
	$members[$member]['BEITRAG-NEU']='';
	$members[$member]['BEITRAGSTEXT-NEU']='';

    foreach ($rols as $rol => $roldata)
    {
    	// alle Rollen, außer Familienrollen
    	if (($roldata['rollentyp']!= 'fam')	&& (array_key_exists($member, $roldata['members'])))
		{
            if($pPreferences->config['Beitrag']['beitrag_anteilig'] == true)
            {
                $members[$member]['ACCESSION'.$gCurrentOrganization->getValue('org_id')] = $roldata['members'][$member]['mem_begin'];
            }

			if($pPreferences->config['Beitrag']['beitrag_anteilig'] == true)
            {
                $time_begin = strtotime($roldata['members'][$member]['mem_begin']);
            }
            else
            {
            	$time_begin = strtotime($members[$member]['ACCESSION'.$gCurrentOrganization->getValue('org_id')]);
            }

            // das Standarddatum '9999-12-31' kann auf best. Systemen nicht verarbeitet werden
			if($roldata['members'][$member]['mem_end'] == '9999-12-31')
            {
                $time_end = strtotime('2038-01-19');
            }
            else
            {
            	$time_end = strtotime($roldata['members'][$member]['mem_end']);
            }

            // anteiligen Beitrag berechnen, falls das Mitglied im aktuellen Jahr ein- oder ausgetreten ist
            // && Beitragszeitraum (cost_period) darf nicht "Einmalig" (-1) sein
            // && Beitragszeitraum (cost_period) darf nicht "Jährlich" (1) sein
            if ((strtotime(date('Y').'-01-01') < $time_begin || $time_end < strtotime(date('Y').'-12-31'))
            	&& ($roldata['rol_cost_period']!=-1)
            	&& ($roldata['rol_cost_period']!=1))
            {

            	if (strtotime(date('Y').'-01-01') <  $time_begin)
            	{
            		$month_begin = date('n', $time_begin);
            	}
            	else
            	{
            		$month_begin = 1;
            	}
            	if (strtotime(date('Y').'-12-31') >  $time_end)
            	{
            		$month_end   = date('n', $time_end);
            	}
            	else
            	{
            		$month_end = 12;
            	}

                $segment_begin = ceil($month_begin * $roldata['rol_cost_period']/12);
                $segment_end = ceil($month_end * $roldata['rol_cost_period']/12);

                $members[$member]['BEITRAG-NEU'] +=  ($segment_end - $segment_begin +1) * $roldata['rol_cost'] / $roldata['rol_cost_period'];
                if ($roldata['rol_description']!='')
                {
                    $members[$member]['BEITRAGSTEXT-NEU'] .= ' '.$roldata['rol_description'].' ';
                }
                if ($pPreferences->config['Beitrag']['beitrag_suffix']!='')
                {
                	$members[$member]['BEITRAGSTEXT-NEU'] .= ' '.$pPreferences->config['Beitrag']['beitrag_suffix'].' ';
                }
                // nur einmal soll beitrag_suffix angezeigt werden, wenn aber rol_description leer ist,
                // wird es mehrfach hintereinander mit vielen Leerzeichen dazwischen angefügt, deshalb ersetzen
                //zuerst zwei aufeinanderfolgende Leerzeichen durch ein Leerzeichen ersetzen
        		$members[$member]['BEITRAGSTEXT-NEU'] = str_replace('  ', ' ', $members[$member]['BEITRAGSTEXT-NEU']);
        		//jetzt mehrfache beitrag_suffix löschen
                $members[$member]['BEITRAGSTEXT-NEU'] = str_replace($pPreferences->config['Beitrag']['beitrag_suffix'].' '.$pPreferences->config['Beitrag']['beitrag_suffix'], $pPreferences->config['Beitrag']['beitrag_suffix'], $members[$member]['BEITRAGSTEXT-NEU']);
            }
            else                             //keine anteilige Berechnung
            {
                $members[$member]['BEITRAG-NEU'] += $roldata['rol_cost'];
                if ($roldata['rol_description']!='')
                {
                    $members[$member]['BEITRAGSTEXT-NEU'] .= ' '.$roldata['rol_description'].' ';
                }
            }
        }
    }

    // wenn definiert: Beitragstext mit dem Namen des Benutzers
    if(($pPreferences->config['Beitrag']['beitrag_textmitnam'] == true)
    	&&  ($members[$member]['BEITRAG-NEU']!='')
        &&  !(($members[$member]['LAST_NAME'].' '.$members[$member]['FIRST_NAME']==$members[$member]['DEBTOR'])
           || ($members[$member]['FIRST_NAME'].' '.$members[$member]['LAST_NAME']==$members[$member]['DEBTOR'])
           || (empty($members[$member]['DEBTOR']))))
    {
        $members[$member]['BEITRAGSTEXT-NEU'] .= $text_token.$members[$member]['LAST_NAME'].' '.$members[$member]['FIRST_NAME'].$text_token;
    }
}

// alle Rollen und deren Mitglieder durchlaufen  und die Beiträge eines Mitglieds,
// das zudem ein Familienmitglied ist, dem Zahlungspflichtigen der Familie zugeschlagen
foreach ($rols as $rol => $roldata)
{
    // nur Rollen mit dem Präfix einer Familie && die Familienrolle muß Mitglieder aufweisen
    if (($roldata['rollentyp']== 'fam')	&& (sizeof($roldata['members'])>0))
    {
        // wenn definiert: Beitragstext mit allen Familienmitgliedern
        if($pPreferences->config['Beitrag']['beitrag_textmitfam'] == true)
        {
            $members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'] .= ' ';
            foreach ($roldata['members'] as $member => $memberdata)
            {
                $members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'] .= $text_token.$members[$member]['LAST_NAME'].' '.$members[$member]['FIRST_NAME'];
            }
            $members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'] .= $text_token.' ';
        }

        //alle Mitglieder dieser Rolle durchlaufen und die Beiträge der Mitglieder dem Zahlungspflichtigen zuordnen
        foreach ($roldata['members'] as $member => $memberdata)
        {
            // nicht beim Zahlungspflichtigen selber und auch nur, wenn ein Zusatzbeitrag beim Mitglied errechnet wurde
            if  (($roldata['has_to_pay'] != $member) && ($members[$member]['BEITRAG-NEU'] > 0))
            {
                $members[$roldata['has_to_pay']]['BEITRAG-NEU'] += $members[$member]['BEITRAG-NEU'];
                $members[$member]['BEITRAG-NEU'] = '';
                $members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'] .= $members[$member]['BEITRAGSTEXT-NEU'].' ';

                // wenn nicht definiert: Beitragstext mit allen Familienmitgliedern, trotzdem Name und Vorname anfügen
                if(!$pPreferences->config['Beitrag']['beitrag_textmitnam'])
                {
                    $members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'] .= $text_token.$memberdata['LAST_NAME'].' '.$memberdata['FIRST_NAME'].$text_token.' ';
                }
                $members[$member]['BEITRAGSTEXT-NEU'] = '';
            }
        }

        // anteiligen Beitrag berechnen, falls die Familie erst im aktuellen Jahr angelegt wurde
        // && Beitragszeitraum (cost_period) darf nicht "Einmalig" (-1) sein
        // && Beitragszeitraum (cost_period) darf nicht "Jährlich" (1) sein
	    if ((date('Y') == date('Y', strtotime($roldata['rol_timestamp_create']))) && ($roldata['rol_cost_period']!=-1) && ($roldata['rol_cost_period']!=1))
        {
            $beitrittsmonat = date('n', strtotime($roldata['rol_timestamp_create']));
            $members[$roldata['has_to_pay']]['BEITRAG-NEU'] +=  (($roldata['rol_cost_period']+1)-ceil($beitrittsmonat/(12/$roldata['rol_cost_period'])))*($roldata['rol_cost']/$roldata['rol_cost_period']);
            $members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'] = ' '.$roldata['rol_description'].' '.$pPreferences->config['Beitrag']['beitrag_suffix'].' '.$members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'].' ';
        }
        else
        {
            $members[$roldata['has_to_pay']]['BEITRAG-NEU'] += $roldata['rol_cost'];
            $members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'] = ' '.$roldata['rol_description'].$members[$roldata['has_to_pay']]['BEITRAGSTEXT-NEU'].' ';
        }
    }
}

foreach ($members as $member => $memberdata)
{
    // den errechneten Beitrag nur in die DB schreiben wenn mehrere Kriterien erfüllt sind
    if ((is_null($members[$member]['FEE'.$gCurrentOrganization->getValue('org_id')])
    		||  (!(is_null($members[$member]['FEE'.$gCurrentOrganization->getValue('org_id')]))
    			&& (($pPreferences->config['Beitrag']['beitrag_modus'] == 'overwrite')
    				||($pPreferences->config['Beitrag']['beitrag_modus'] == 'summation'))))
    	&& ($members[$member]['BEITRAG-NEU']>$pPreferences->config['Beitrag']['beitrag_mindestbetrag']))
    {
        $members[$member]['BEITRAGSTEXT-NEU'] =  $pPreferences->config['Beitrag']['beitrag_prefix'].' '.$members[$member]['BEITRAGSTEXT-NEU'].' ';

        // alle Beiträge auf 2 Nachkommastellen runden
        $members[$member]['BEITRAG-NEU'] = round($members[$member]['BEITRAG-NEU'], 2);

        //ggf. abrunden
        if ($pPreferences->config['Beitrag']['beitrag_abrunden'] == true)
        {
            $members[$member]['BEITRAG-NEU'] = floor($members[$member]['BEITRAG-NEU']);
        }

        if($pPreferences->config['Beitrag']['beitrag_modus'] == 'summation')
        {
         	$members[$member]['BEITRAG-NEU'] += $members[$member]['FEE'.$gCurrentOrganization->getValue('org_id')];
        	$members[$member]['BEITRAGSTEXT-NEU'] .= ' '.$members[$member]['CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id')].' ';
        }

        //führende und nachfolgene Leerstellen im Beitragstext löschen
        $members[$member]['BEITRAGSTEXT-NEU'] = trim($members[$member]['BEITRAGSTEXT-NEU']);
        //zwei aufeinanderfolgende Leerzeichen durch ein Leerzeichen ersetzen
        $members[$member]['BEITRAGSTEXT-NEU'] = str_replace('  ', ' ', $members[$member]['BEITRAGSTEXT-NEU']);

        //neuen Beitrag schreiben
        $user = new User($gDb, $gProfileFields, $member);
    	$user->setValue('FEE'.$gCurrentOrganization->getValue('org_id'), $members[$member]['BEITRAG-NEU']);
    	$user->setValue('CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), $members[$member]['BEITRAGSTEXT-NEU']);
    	$user->save();
    }
}
$message .= $gL10n->get('SYS_SAVE_DATA');

// set headline of the script
$headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_RECALCULATION');

// create html page object
$page = new HtmlPage($headline);

$form = new HtmlForm('recalculation_form', null, $page);
$form->addDescription($message);
$form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => THEME_PATH.'/icons/forward.png', 'link' => 'menue.php?show_option=recalculation', 'class' => 'btn-primary'));

$page->addHtml($form->show(false));
$page->show();
