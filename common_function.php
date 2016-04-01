<?php
/******************************************************************************
 * 
 * common_function.php
 *   
 * Gemeinsame Funktionen fuer das Admidio-Plugin Mitgliedsbeitrag
 * 
 * Copyright    : (c) 2004 - 2014 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * 
 ****************************************************************************/

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);
 
require_once(substr(__FILE__, 0,strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');

// Funktion um alle beitragsbezogenen Rollen einzulesen
// übergebener 1. Parameter:    'alt'   für alle altersgestaffelte Rollen
//                              'fam'   für alle Familienrollen 
//                              'fix'   für alle restlichen Fixbeitragsrollen
//                              leer    für Gesamtliste aller beitragsbezogenen Rollen  (default))
// übergebener 2. Parameter:    leer    ohne Members (default)
//                              array   mit Members z.B. array('FIRST_NAME','LAST_NAME')   
function beitragsrollen_einlesen($rollenwahl = '',$with_members = array())
{
    global $gDb, $gCurrentOrganization, $pPreferences;
    $rollen = array();
    
    // alle Rollen einlesen
    $sql = 'SELECT rol_id, rol_name, rol_cost, rol_cost_period, rol_timestamp_create, rol_description
            FROM '.TBL_ROLES.', '. TBL_CATEGORIES. ' 
            WHERE rol_valid  = 1
            AND rol_cost >=0
            AND rol_cost_period <>\'\' 
            AND rol_cat_id = cat_id
            AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                OR cat_org_id IS NULL ) ';
                                                  
    $result = $gDb->query($sql);

    while ($row = $gDb->fetch_array($result))
    {
        $rollen[$row['rol_id']] = array('rolle' => $row['rol_name'],'rol_cost' => $row['rol_cost'],'rol_cost_period' => $row['rol_cost_period'],'rol_timestamp_create' => $row['rol_timestamp_create'],'rol_description' => $row['rol_description'],'von' => 0,'bis' => 0,'rollentyp' => '');
    }
    
    foreach ($rollen as $key => $data)
    {
        // alle Alterskonfigurationen durchlaufen
		foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $altkey => $altdata)
        {
        	// ist es eine altersgestaffelte Rolle?
            if (substr_count($data['rolle'], $pPreferences->config['Altersrollen']['altersrollen_token'][$altkey]) == 4) 
            {
                $v1 = strpos($data['rolle'], $pPreferences->config['Altersrollen']['altersrollen_token'][$altkey], 0);
                $v2 = strpos($data['rolle'], $pPreferences->config['Altersrollen']['altersrollen_token'][$altkey], $v1+1);
                $v3 = strpos($data['rolle'], $pPreferences->config['Altersrollen']['altersrollen_token'][$altkey], $v2+1);
                $v4 = strpos($data['rolle'], $pPreferences->config['Altersrollen']['altersrollen_token'][$altkey], $v3+1);
  
                $rollen[$key]['von'] = substr($data['rolle'], $v1+1, $v2-$v1-1);
                $rollen[$key]['bis'] = substr($data['rolle'], $v3+1, $v4-$v3-1);       
       
                $rollen[$key]['von'] = str_replace(' ', '', $rollen[$key]['von']);
                $rollen[$key]['bis'] = str_replace(' ', '', $rollen[$key]['bis']);
            
                if((is_numeric($rollen[$key]['von'] )) && (is_numeric($rollen[$key]['bis']) ))
                {          
                    if ($rollen[$key]['von'] > $rollen[$key]['bis']) 
                    {          
                        $dummy = $rollen[$key]['von']; 
                        $rollen[$key]['von'] = $rollen[$key]['bis'];
                        $rollen[$key]['bis'] = $dummy;
                    }
                    $rollen[$key]['rollentyp'] = 'alt';
                    $rollen[$key]['alterstyp'] = $pPreferences->config['Altersrollen']['altersrollen_token'][$altkey];
                }
            }   
        } 
         
        // alle Familienkonfigurationen durchlaufen
		foreach($pPreferences->config['Familienrollen']['familienrollen_prefix'] as $famkey => $famdata)
    	{
        	// ist es eine Familienrolle?
    		if (substr($data['rolle'],0, strlen($pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])) == $pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])   
        	{   
            	$rollen[$key]['rollentyp'] = 'fam';
            	$rollen[$key]['familientyp'] = $pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey];
        	}    	
    	}

        // wenn der Rollentyp jetzt immer noch leer ist, dann kann es nur eine Fixrolle sein                      
        if ($rollen[$key]['rollentyp']=='')   
        {          
            $rollen[$key]['rollentyp'] = 'fix';
        }
    }
    
    // jetzt sind alle Familienrollen, Altersrollen und Fixrollen markiert
    // alle nicht benötigten Rollen löschen
    foreach ($rollen as $key => $data)
    {
        if (($rollenwahl == 'fam') && ($rollen[$key]['rollentyp'] <> 'fam')) 
        {
            unset($rollen[$key]) ; 
        }
        elseif (($rollenwahl == 'alt') && ($rollen[$key]['rollentyp'] <> 'alt'))
        {
            unset($rollen[$key]) ; 
        } 
        elseif (($rollenwahl == 'fix') && ($rollen[$key]['rollentyp'] <> 'fix'))
        {
            unset($rollen[$key]) ; 
        }
        else
        {
            if (is_array($with_members) && sizeof($with_members)>0)
            {
                $rollen[$key]['members'] = list_members($with_members, array($data['rolle'] => 0))  ;                    
            }
        }
    }
    return $rollen;
}

// Funktion bezugskategorie_einlesen
// Liest alle Mitglieder von Rollen einer oder mehrerer Kategorie(en) ein
// übergebene Parameter:    keine, die cat_ids der einzulesenden Kategorien werden direkt aus der $config_ini gelesen
// Rückgabe:				Array mit den user_ids der Mitglieder
function bezugskategorie_einlesen()
{
    global $gDb, $gCurrentOrganization, $pPreferences;  
    
    $members = array(); 
    
    // Hinweis: die Überprüfung, ob $config_ini['Rollenpruefung']['bezugskategorie']
    // befüllt und ein Array ist, ist in der aufrufenden Routine erfolgt
    
    $sql = 'SELECT DISTINCT mem_usr_id
            FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ' ';
    
    $firstpass = true; 
    foreach ($pPreferences->config['Rollenpruefung']['bezugskategorie'] as $cat => $cat_id)
    {
    	if($cat_id==' ')
    	{
    		return $members;
    	}
        if ($firstpass)
        {
            $sql .= ' WHERE ( '; 
        }       
        else
        {
            $sql .= ' OR ( '; 
        }
        
        $sql .=  'cat_id = '.$cat_id.' '; 
		$sql .= ' AND mem_rol_id = rol_id
              	  AND rol_valid  = 1 
          
              	  AND mem_begin <= \''.DATE_NOW.'\' 
        		  AND mem_end >= \''.DATE_NOW.'\' 
        		  
                  AND rol_cat_id = cat_id ';            
        $sql .=  ' ) ';   
        $firstpass = false; 
    } 

    $sql .= ' AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
              OR cat_org_id IS NULL )
              ORDER BY mem_usr_id ASC ';

    $result = $gDb->query($sql);
    while($row = $gDb->fetch_array($result))
    {
       $members[] = $row['mem_usr_id']; 
    }    
    
    return $members;
}

//check_date wird verwendet um die bezahlt-Datumsangabe zu überprüfen
function check_date($date,$format,$sep)
{    
    $pos1    = strpos($format, 'd');
    $pos2    = strpos($format, 'm');
    $pos3    = strpos($format, 'Y'); 
    
    $check    = explode($sep,$date);
    
    return checkdate(intval($check[$pos2]),intval($check[$pos1]),intval($check[$pos3]));
}

// Funktion prueft, ob ein User die uebergebene Rolle besitzt
// $role_name - Name der zu pruefenden Rolle
// $user_id   - Id des Users, fuer den die Mitgliedschaft geprueft werden soll
function hasRole_IDPMB($role_id, $user_id = 0)
{
    global $gCurrentUser,$gCurrentOrganization, $gDb;

    if($user_id == 0)
    {
        $user_id = $gCurrentUser->getValue('usr_id');
    }
    elseif(is_numeric($user_id) == false)
    {
        return -1;
    }

    $sql    = 'SELECT mem_id
                FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                WHERE mem_usr_id = '.$user_id.'
                AND mem_begin <= \''.DATE_NOW.'\'
                AND mem_end    > \''.DATE_NOW.'\'
                AND mem_rol_id = rol_id
                AND rol_id   = \''.$role_id.'\'
                AND rol_valid  = 1 
                AND rol_cat_id = cat_id
                AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                OR cat_org_id IS NULL ) ';
                
    $result = $gDb->query($sql);

    $user_found = $gDb->num_rows($result);

    if($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

// Funktion list_members
// Diese Funktion liefert als Rückgabe ein assoziatives Array der usr_ids.
// mögliche Aufrufe:
//       list_members(array('usf_name_intern1','usf_name_intern2'),array('Rollenname1' => Schalter aktiv/ehem) )
// oder  list_members(array('usf_name_intern1','usf_name_intern2'), 'Rollenname' )
// oder  list_members(array('usf_name_intern1','usf_name_intern2'), Schalter aktiv/ehem )
//
// Schalter aktiv/ehem: 0 = aktive Mitglieder, 1 = ehemalige Mitglieder, ungleich 1 oder 0: alle Mitglieder
//
// Aufruf: z.B. list_members(array('FIRST_NAME','LAST_NAME'), array('Mitglied' => 0,'Webmaster' => 0));
function list_members( $fields, $rols = array() )
{
    global $gDb, $gCurrentOrganization, $gProfileFields;  
    
    $members = array(); 
    
    $sql = 'SELECT DISTINCT mem_usr_id, mem_begin, mem_end
            FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ' ';

    if  (is_string($rols))
    {
        $sql .= ' WHERE mem_rol_id = '.getRole_IDPMB($rols).' ';        
    } 
    elseif  (is_integer($rols) && ($rols == 0) )
    {
        // nur aktive Mitglieder
        $sql .= ' WHERE mem_begin <= \''.DATE_NOW.'\' '; 
        $sql .= ' AND mem_end >= \''.DATE_NOW.'\' '; 
        
    }       
    elseif  (is_integer($rols) && ($rols == 1) )   
    {
        // nicht-aktive Mitglieder    ALT:nur ehemalige Mitglieder
        $sql .= ' WHERE ( (mem_begin > \''.DATE_NOW.'\') OR (mem_end < \''.DATE_NOW.'\') )';    
    } 
    elseif (is_array($rols))
    {
        $firstpass = true; 
        foreach ($rols as $rol => $rol_switch)
        {
            if ($firstpass)
            {
                $sql .= ' WHERE ( '; 
            }       
            else
            {
                $sql .= ' OR ( '; 
            }
            $sql .=  'mem_rol_id = '.getRole_IDPMB($rol).' '; 
            
            if ($rol_switch == 0)  
            {
                // aktive Mitglieder
                $sql .= ' AND mem_begin <= \''.DATE_NOW.'\' '; 
        		$sql .= ' AND mem_end >= \''.DATE_NOW.'\' '; 
            }       
            elseif ($rol_switch == 1)
            {
                // nicht aktive Mitglieder  ALT: ehemalige Mitglieder
                $sql .= ' AND ( (mem_begin > \''.DATE_NOW.'\') OR (mem_end < \''.DATE_NOW.'\') )'; 
            }
            $sql .=  ' ) ';   
            $firstpass = false; 
        } 
    }
    
    $sql .= ' AND mem_rol_id = rol_id
              AND rol_valid  = 1   
              AND rol_cat_id = cat_id
              AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
              OR cat_org_id IS NULL )
              ORDER BY mem_usr_id ASC ';

    $result = $gDb->query($sql);
    while($row = $gDb->fetch_array($result))
    {
        $members[$row['mem_usr_id']] = '';
        
        // mem_begin und mem_end werden nur in der recalculation.php ausgewertet,
        // wird für anteilige Beitragsberechnung verwendet
        $members[$row['mem_usr_id']]['mem_begin']=$row['mem_begin'];
        $members[$row['mem_usr_id']]['mem_end']=$row['mem_end'];
    }    
    foreach ($members as $member => $key)
    { 
        foreach ($fields as $field => $data) 
        {
            $sql = 'SELECT usd_value
                    FROM '.TBL_USER_DATA.'
                    WHERE usd_usr_id = '.$member.'
                    AND usd_usf_id = '.$gProfileFields->getProperty($data, 'usf_id').' ';
		    $result = $gDb->query($sql);
		    $row = $gDb->fetch_array($result);
		    $members[$member][$data] = $row['usd_value'];
	    }
    } 
    return $members;
}

// loescht Zeilen in einem Array
// die Variable $delete_NULL_field muß in der uebergebenden Routine definiert sein
function delete_NULL ( $wert )
{
    global $delete_NULL_field;
    
    return ( $wert[$delete_NULL_field] != NULL );
}

// gibt zu einem Rollennamen die entsprechende Role_ID zurück
function getRole_IDPMB($role_name)
{
    global $gDb,$gCurrentOrganization;
	
    $sql    = 'SELECT rol_id
                 FROM '. TBL_ROLES. ', '. TBL_CATEGORIES. '
                 WHERE rol_name   = \''.$role_name.'\'
                 AND rol_valid  = 1 
                 AND rol_cat_id = cat_id
                 AND (  cat_org_id = '.$gCurrentOrganization->getValue('org_id').'
                 OR cat_org_id IS NULL ) ';
                      
    $result = $gDb->query($sql);
    $row = $gDb->fetch_object($result);
	if(isset($row->rol_id) AND strlen($row->rol_id) > 0)
	{
		return $row->rol_id ;
	}
	else 
	{
		return 0 ;
	}
}

function getCostPeriod($my_rol_cost_period)
{
    global $gL10n;
    
    if($my_rol_cost_period == -1)
    {
        return $gL10n->get('ROL_UNIQUELY');
    }
    elseif($my_rol_cost_period == 1)
    {
        return $gL10n->get('ROL_ANNUALLY');
    }
    elseif($my_rol_cost_period == 2)
    {
        return $gL10n->get('ROL_SEMIYEARLY');
    }
    elseif($my_rol_cost_period == 4)
    {
        return $gL10n->get('ROL_QUARTERLY');
    }
    elseif($my_rol_cost_period == 12)
    {
        return $gL10n->get('ROL_MONTHLY');
    }
    else
    {
        return '--';
    }
} 

function analyse_mem()                      
{
    global $gCurrentOrganization;
    
    $members = list_members(array('BEITRAG'.$gCurrentOrganization->getValue('org_id'),'BEITRAGSTEXT'.$gCurrentOrganization->getValue('org_id'),'BEZAHLT'.$gCurrentOrganization->getValue('org_id'),'KONTONUMMER','BANKLEITZAHL','IBAN','KONTOINHABER'), 0)  ; 
	$ret = array('data'=> $members,'BEITRAG_kto'=>0,'BEITRAG_kto_anzahl'=>0,'BEITRAG_rech'=>0,'BEITRAG_rech_anzahl'=>0,'BEZAHLT_kto'=>0,'BEZAHLT_kto_anzahl'=>0,'BEZAHLT_rech'=>0,'BEZAHLT_rech_anzahl'=>0);
    
	// alle Mitglieder durchlaufen und im ersten Schritt alle Mitglieder,  
	// bei denen kein Beitrag berechnet wurde,
	// und kein Beitragstext (=Verwendungszweck) existiert,  herausfiltern
    foreach ($members as $member => $memberdata)
    {
        if (empty($memberdata['BEITRAG'.$gCurrentOrganization->getValue('org_id')]) || empty($memberdata['BEITRAGSTEXT'.$gCurrentOrganization->getValue('org_id')]) )  
        {
            unset($members[$member]);
        }
    }

    //jetzt wird gezählt
    foreach($members as $member => $memberdata)
    {
        if ((!empty($memberdata['KONTONUMMER']) && !empty($memberdata['BANKLEITZAHL'])) || !empty($memberdata['IBAN'])) 	    
        {	
            $ret['BEITRAG_kto'] += $memberdata['BEITRAG'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEITRAG_kto_anzahl']+=1;
        }
        if ( ((!empty($memberdata['KONTONUMMER']) && !empty($memberdata['BANKLEITZAHL'])) || !empty($memberdata['IBAN']))
        	&& !empty($memberdata['BEZAHLT'.$gCurrentOrganization->getValue('org_id')]) )
        {
            $ret['BEZAHLT_kto'] += $memberdata['BEITRAG'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEZAHLT_kto_anzahl']+=1;
        }
        if (!((!empty($memberdata['KONTONUMMER']) && !empty($memberdata['BANKLEITZAHL'])) || !empty($memberdata['IBAN'])) )
        {
            $ret['BEITRAG_rech'] += $memberdata['BEITRAG'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEITRAG_rech_anzahl']+=1;
        }
        if ( !((!empty($memberdata['KONTONUMMER']) && !empty($memberdata['BANKLEITZAHL'])) || !empty($memberdata['IBAN'])) 
        	&& !empty($memberdata['BEZAHLT'.$gCurrentOrganization->getValue('org_id')]) )
        {
            $ret['BEZAHLT_rech'] += $memberdata['BEITRAG'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEZAHLT_rech_anzahl']+=1;
        }
    }
    return $ret;
}
    
function analyse_rol()                      
{
    global $pPreferences, $gCurrentOrganization, $gL10n;
  
    $ret = beitragsrollen_einlesen('alt');
    $ret = array_merge($ret,beitragsrollen_einlesen('fix'));
    foreach ($ret as $rol => $roldata)
    {
        $ret[$rol]['members'] = list_members(array('BEITRAG'.$gCurrentOrganization->getValue('org_id'),'BEZAHLT'.$gCurrentOrganization->getValue('org_id')), array($roldata['rolle'] => 0))  ; 
    }
    
    $fam = beitragsrollen_einlesen('fam');
    
	foreach($pPreferences->config['Familienrollen']['familienrollen_prefix'] as $famkey => $famdata)
    {
        //wieviele Familienrollen mit diesem Präfix gibt es denn?
        //in der aufrufenden Funktion wird mittels sizeof abgefragt, 
        //deshalb muss hier eine Array erzeugt werden
        $arr = array();
    	foreach($fam as $key => $data)
    	{
			if (substr($data['rolle'],0, strlen($pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])) == $pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])  
			{
				$arr[]=$key;
			} 
    	}	
		$ret[$pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey]] = array('rolle' => $gL10n->get('PMB_FAMILY_ROLE').' '.$pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey],'rol_cost' => $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$famkey],'rol_cost_period' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$famkey],'members' =>$arr,'rollentyp' => 'fam'); 
    		
    }
    return $ret;
}

function check_rollenmitgliedschaft_altersrolle()
{
	global $pPreferences, $gL10n, $g_root_path;
    $ret = array();
    $alt = beitragsrollen_einlesen('alt',array('FIRST_NAME','LAST_NAME'));

    $check = array();
    foreach ($alt as $altrol => $altdata)
    {
    	foreach($altdata['members'] as $member => $memberdata)
    	{
    		$check[$member]['alterstyp'][]= $altdata['alterstyp'];
    		$check[$member]['FIRST_NAME'] = $memberdata['FIRST_NAME'];
    		$check[$member]['LAST_NAME'] = $memberdata['LAST_NAME'];
    	}
    } 
    	
    // jetzt $check durchlaufen und alle Einträge löschen, bei denen die Größe des Arrays nur 1 beträgt
    foreach($check as $member => $memberdata)
    {
    	if(sizeof($memberdata['alterstyp'])>1)
    	{
    		$ret[] .= '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>'; 
    	}
    }

    if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PMB_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PMB_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

function check_rollenmitgliedschaft_pflicht()
{
    global $pPreferences, $gL10n, $g_root_path;
    $ret = array();
    
    // alle Beitragsrollen einlesen ('FIRST_NAME' wird zwar in der Funktion nicht benötigt, ist aber notwendig,
    // damit die Rollenmitglieder eingelesen werden)
    $beitragsrollen = beitragsrollen_einlesen('',array('FIRST_NAME'));
    $members = list_members(array('FIRST_NAME','LAST_NAME'), 0)  ;   
    
    // alle Beitragsrollen durchlaufen und diejenigen Rollen löschen, die nicht als Pflichtrolle definiert sind
    foreach ($beitragsrollen as $rol => $roldata)
    {
        //alle if und elseif könnte man in einer Zeile schreiben und mit || verknüpfen, aber so ist es übersichtlicher 
        if(($roldata['rollentyp'] == 'fam') && (!$pPreferences->config['Rollenpruefung']['familienrollenpflicht']) )
        {
            unset ($beitragsrollen[$rol]);                                                          
        }       
        elseif(($roldata['rollentyp'] == 'alt') && (!$pPreferences->config['Rollenpruefung']['altersrollenpflicht']) )
        {
            unset ($beitragsrollen[$rol]);                                                          
        } 
        elseif (($roldata['rollentyp'] == 'fix') && (!is_array($pPreferences->config['Rollenpruefung']['fixrollenpflicht'])))
        {
            unset ($beitragsrollen[$rol]);  
        }
        elseif (($roldata['rollentyp'] == 'fix') && (is_array($pPreferences->config['Rollenpruefung']['fixrollenpflicht'])) && !(in_array($rol,$pPreferences->config['Rollenpruefung']['fixrollenpflicht']))) 
        {
            unset ($beitragsrollen[$rol]);  
        } 
    }
    // in $beitragsrollen sind jetzt nur noch Pflicht-Beitragsrollen 

    // Feature-Wunsch von joesch
    $bezugskategorieMembers = array();
    if ($pPreferences->config['Rollenpruefung']['bezugskategorie'][0]<>' ')
    {
    	// zuerst alle Member der Bezugskategorien einlesen
		$bezugskategorieMembers = bezugskategorie_einlesen();

		foreach ($members as $member => $memberdata)
    	{
    		// alle usr_ids löschen, wenn sie nicht innerhalb der Bezugskategorie sind 
    		if (!in_array($member,$bezugskategorieMembers))
    		{
    			unset($members[$member]) ; 
    		}           	
    	}
    }
    
    // alle Mitglieder durchlaufen und prüfen, ob sie in mind. einer Pflicht-Beitragsrolle sind
    foreach ($members as $member => $memberdata)
    {
        $marker = false;
        foreach ($beitragsrollen as $rol => $roldata)
        {
            if(array_key_exists($member,$roldata['members']))
            {
                $marker = true;     
            } 
        }
        if (!$marker)
        { 
        	$ret[] .= '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>'; 
        }
    }

    if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PMB_ROLE_MEMBERSHIP_DUTY_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PMB_ROLE_MEMBERSHIP_DUTY_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

function check_rollenmitgliedschaft_ausschluss()
{
    global $pPreferences, $gL10n, $g_root_path;
    $ret = array();
    
    // alle Beitragsrollen einlesen ('FIRST_NAME' wird zwar in der Funktion nicht benötigt, ist aber notwendig,
    // damit die Rollenmitglieder eingelesen werden)
    $beitragsrollen = beitragsrollen_einlesen('',array('FIRST_NAME'));
    $members = list_members(array('FIRST_NAME','LAST_NAME'), 0)  ;   
    
    // alle Beitragsrollen durchlaufen und für jedes Mitglied seine Rollenzugehörigkeiten bestimmen
    foreach ($beitragsrollen as $rol => $roldata)
    {
        foreach ($roldata['members'] as $key => $dummy)
        {
        	if (!isset($members[$key]['rollen']))
        	{
            	$members[$key]['rollen'] = array();   
        	}
        
            if($roldata['rollentyp']== 'alt') 
            {
                $members[$key]['rollen'][]= 'alt';    
            }
            elseif($roldata['rollentyp']== 'fam') 
            {
                $members[$key]['rollen'][]= 'fam';    
            }
            else
            {
                $members[$key]['rollen'][]= $rol;    
            }
        }
    } 

    // Feature-Wunsch von joesch
    $bezugskategorieMembers = array();
    if ($pPreferences->config['Rollenpruefung']['bezugskategorie'][0]<> ' ')
    {
    	// zuerst alle Member der Bezugskategorien einlesen
		$bezugskategorieMembers = bezugskategorie_einlesen();

		foreach ($members as $member => $memberdata)
    	{
    		// alle usr_ids löschen, wenn sie nicht innerhalb der Bezugskategorie sind 
    		if (!in_array($member,$bezugskategorieMembers))
    		{
    			unset($members[$member]) ; 
    		}           	
    	}
    }        
    
    // alle Mitglieder durchlaufen und prüfen, ob sie in Ausschluss-Beitragsrollen sind
    foreach ($members as $member => $memberdata)
    {
        //falls das Mitglied kein Angehöriger einer Beitragsrolle ist: abbrechen und zum nächsten Datensatz gehen 
        if (!isset($memberdata['rollen']))
        {
            continue;     
        }
        
        $marker = false;
        if(($pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen']) && (in_array('fam',$memberdata['rollen'])) && (in_array('alt',$memberdata['rollen'])))
        {
            $marker = true;     
        } 
        if(is_array($pPreferences->config['Rollenpruefung']['familienrollenfix']))
        {
            foreach ($pPreferences->config['Rollenpruefung']['familienrollenfix'] as $rol => $roldata)
            {
                if((in_array($roldata,$memberdata['rollen'])) && (in_array('fam',$memberdata['rollen'])))
                {
                    $marker = true;     
                }   
            }     
        } 
        if(is_array($pPreferences->config['Rollenpruefung']['altersrollenfix']))
        {
            foreach ($pPreferences->config['Rollenpruefung']['altersrollenfix'] as $rol => $roldata)
            {
                if((in_array($roldata,$memberdata['rollen'])) && (in_array('alt',$memberdata['rollen'])))
                {
                    $marker = true;     
                }   
            }     
        } 
       
        if ($marker)
        {
        	$ret[] .= '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>'; 
        }
    } 

    if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PMB_ROLE_MEMBERSHIP_EXCLUSION_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PMB_ROLE_MEMBERSHIP_EXCLUSION_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

// Vergleichsfunktion; erforderlich für usort()
function vergleich($wert_a, $wert_b)
{
    //Sortierung nach dem zweiten Wert des Arrays (Index: 1)
    $a = $wert_a['year'];
    $b = $wert_b['year'];
   
    if ($a == $b) return 0;
    elseif ($a > $b) return 1;
    else return -1;
}   
 
function check_rols()
{
	global $pPreferences,$gL10n;
    $ret = array();
    $alt = beitragsrollen_einlesen('alt',array('LAST_NAME'));

    foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $tokenkey => $tokendata)
    {
    	$check = array();
    	foreach ($alt as $altrol => $altdata)
    	{
    		if ($altdata['alterstyp']==$tokendata)
    		{
	       		$check[]  = array('year' => $altdata['von'],'rol' => $altrol);
        		$check[]  = array('year' => $altdata['bis'],'rol' => $altrol);    		
    		}
    	} 
 
    	usort($check, 'vergleich');
     
    	for ($i = 0; $i < sizeof($check)-1; $i = $i+2) 
    	{
	   	if ($check[$i]['rol'] <> $check[$i+1]['rol']) 
        	{
            	$ret[$check[$i]['rol']] = '- '.$alt[$check[$i]['rol']]['rolle'];
            	$ret[$check[$i+1]['rol']] = '- '.$alt[$check[$i+1]['rol']]['rolle'];
			}
			if (($i < sizeof($check)-2) && ($check[$i+1]['year'] <> ($check[$i+2]['year'])-1)) 
        	{
            	$ret[$check[$i+1]['rol']] = '- '.$alt[$check[$i+1]['rol']]['rolle'];
            	$ret[$check[$i+2]['rol']] = '- '.$alt[$check[$i+2]['rol']]['rolle'];
			}
		}
    }
       
    if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PMB_AGE_STAGGERED_ROLES_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PMB_AGE_STAGGERED_ROLES_RESULT_ERROR').'</strong>';
    }
    return $ret;
} 

// Funktion für die Rollenprüfung von Familienrollen
function check_family_roles()
{
    global $pPreferences, $gL10n, $g_root_path;
    $ret = array();
    $ret_error = array();
    $temp_arr  = array();
    $temp_arr2 = array();    
    $ret_marker = false;
    $fam = beitragsrollen_einlesen('fam',array('LAST_NAME','FIRST_NAME','BIRTHDAY'));
    $check = $pPreferences->config['Familienrollen'];

    // alle Prüfbedingungen einlesen
	foreach($check['familienrollen_prefix'] as $key => $prefix)
    {
    	$temp_arr = explode(';',$pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key]);
		foreach ($temp_arr as $keybed => $bedingung)
		{
			// den Doppelpunkt in der Prüfbedingung ersetzen
			// eine Prüfbedingung könnte deshalb auch in folgender Syntax geschrieben werden: von*bis*Anzahl
			$bedingung = str_replace(':','*',$bedingung);
			
			$temp_arr2 = explode('*',$bedingung);
			
			// prüfen auf unsinnige Bedingungen
			if( isset($temp_arr2[0]) && isset($temp_arr2[1]) && isset($temp_arr2[2]) 
			 && is_numeric($temp_arr2[0]) && is_numeric($temp_arr2[1]) && is_numeric($temp_arr2[2]))
			{
				$check['pruefungsbedingungen'][$key][$keybed]['von'] = $temp_arr2[0];
				$check['pruefungsbedingungen'][$key][$keybed]['bis'] = $temp_arr2[1];
				$check['pruefungsbedingungen'][$key][$keybed]['anz'] = $temp_arr2[2];
			}
			else 
			{
				unset($check['familienrollen_prefix'][$key]);
    			unset($check['familienrollen_beitrag'][$key]);
    			unset($check['familienrollen_zeitraum'][$key]);
    			unset($check['familienrollen_beschreibung'][$key]);
    			unset($check['familienrollen_pruefung'][$key]);	
    			unset($check['pruefungsbedingungen'][$key]);
    			$ret_marker = true;
    			continue;
			}
		}

		// Meldung bei fehlerhaften Prüfbedingungen
		if($ret_marker && strlen($pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key])>0)
		{
			$ret_error[] = '<small>'.$gL10n->get('PMB_CONDITION').' '.$pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key].' ('.$prefix.') '.$gL10n->get('PMB_INVALID').'.</small>';
		}
		$ret_marker = false;        		
    }
    
    // Leerzeile einfügen
	if (sizeof($ret_error) <> 0)
    {
        $ret_error[] = '';
    }
    
    unset($bedingung);
    unset($keybed);
    unset($temp_arr);
    unset($temp_arr2);
    unset($ret_marker);
       
    // alle Prüfbedingungen durchlaufen
	foreach($check['familienrollen_prefix'] as $key => $prefix)
    {
     	// alle Familienrollen durchlaufen
		foreach($fam as $famkey => $famdata)
    	{
     		if ($famdata['familientyp'] == $prefix)
     		{
     			$ret_temp = array();
     			
    			// alle Prüfungsbedingungen durchlaufen
    			foreach ($check['pruefungsbedingungen'][$key] as $pruefkey => $pruefdata)
    			{	
    				$counter = 0;
    				// alle Mitglieder durchlaufen
					foreach($famdata['members'] as $memberID => $memberdata)
    				{
    					// das Alter des Mitglieds am Stichtag bestimmen
    					$age = date("Y", strtotime($pPreferences->config['Altersrollen']['altersrollen_stichtag'])) - date("Y", strtotime($memberdata['BIRTHDAY']));
    				
    					// passt das Alter zu einer der Prüfbedingungen?
    					if (   $age >= $pruefdata['von'] && $age <= $pruefdata['bis'] )
            			{ 
                			$counter++;
            			}
    				}
    				
    				if ($counter <> $pruefdata['anz'])         
                	{
                		$ret_temp[] = '&#160&#160&#160&#183<small>'.$gL10n->get('PMB_CONDITION').' '.$pruefdata['von'].'*'.$pruefdata['bis'].':'.$pruefdata['anz'].' '.$gL10n->get('PMB_NOT_SATISFIED').'.</small>';               		
                	}
    			}
    			if (sizeof($ret_temp) <> 0)
    			{   
	   				$ret[] = '- <a href="'.$g_root_path.'/adm_program/modules/roles/roles_new.php?rol_id='. $famkey. '">'.$famdata['rolle']. '</a>
	   					<a href="'.$g_root_path.'/adm_program/modules/lists/lists_show.php?mode=html&rol_id='. $famkey. '"><img src="'. THEME_PATH. '/icons/list.png"
	   					alt="'.$gL10n->get('ROL_SHOW_MEMBERS').'" title="'.$gL10n->get('ROL_SHOW_MEMBERS').'" /></a>'; 
	   				$ret = array_merge($ret,$ret_temp);
    			}
    		}     	
    	}    	
    }   

	if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PMB_FAMILY_ROLES_ROLE_TEST_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PMB_FAMILY_ROLES_ROLE_TEST_RESULT_ERROR').'</strong>';
    }
    
    // eine evtl. vorhandene Fehlermeldung davorsetzen 
	if (sizeof($ret_error) <> 0)
    {
    	$ret = array_merge($ret_error,$ret);
    }
    return $ret;
} 

function check_mandate_management()
{
    global $gL10n, $g_root_path;
    $ret = array();
    
    $members = list_members(array('FIRST_NAME','LAST_NAME','KONTOINHABER','DEBTORPOSTCODE','DEBTORCITY','DEBTORADDRESS'), 0)  ;

	foreach ($members as $member => $memberdata)
	{
		if ((strlen($memberdata['KONTOINHABER'])<>0) && ( (strlen($memberdata['DEBTORPOSTCODE'])==0) || (strlen($memberdata['DEBTORCITY'])==0) || (strlen($memberdata['DEBTORADDRESS'])==0)) )
		{
			$ret[] = '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>'; 				
		}
	}
 
	if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PMB_MANDATE_MANAGEMENT_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PMB_MANDATE_MANAGEMENT_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

function check_iban()
{
    global $gL10n, $g_root_path;
    $ret = array();
    
    $members = list_members(array('FIRST_NAME','LAST_NAME','IBAN'), 0)  ;

	foreach ($members as $member => $memberdata)
	{
		if ((strlen($memberdata['IBAN'])<>0) && !test_iban($memberdata['IBAN']) )
		{
			$ret[] = '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>'; 				
		}
	}
 
	if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PMB_IBANCHECK_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PMB_IBANCHECK_RESULT_ERROR').'</strong>';
    }
    return $ret;
}
 
function iban_berechnung_DE($blz,$kto)
{
	//berechnet die IBAN aus BLZ und Kontonummer
	//NUR FÜR DE-Banken!!!
	// Autor: guenter47
	$u_iban = "DE00";
	$bban=$blz.str_pad($kto,10,"0",STR_PAD_LEFT);
	$num_de="131400";   // (D = 13, E = 14, +00)
	$pruefsum=$bban.$num_de;
	$rest=0;
   	for($pos=0;$pos<strlen($pruefsum); $pos+=7 ) {
        $part = strval($rest) . substr($pruefsum,$pos,7);
        $rest = intval($part) % 97;
    }	
	$pz1=98-$rest;
	$pz2=str_pad($pz1,2,"0",STR_PAD_LEFT);
	$iban='DE'.$pz2.$bban;
	return $iban;
}

function test_iban( $iban ) 
{
	// aus dem Internet
    $iban = str_replace( ' ', '', $iban );
    $iban1 = substr( $iban,4 )
        . strval( ord( $iban{0} )-55 )
        . strval( ord( $iban{1} )-55 )
        . substr( $iban, 2, 2 );

    $rest=0;
    for ( $pos=0; $pos<strlen($iban1); $pos+=7 ) {
        $part = strval($rest) . substr($iban1,$pos,7);
        $rest = intval($part) % 97;
    }
    $pz = sprintf("%02d", 98-$rest);

    if ( substr($iban,2,2)=='00')
        return substr_replace( $iban, $pz, 2, 2 );
    else
        return ($rest==1) ? true : false;
}

// Funktion prueft, ob der Nutzer, aufgrund seiner Rollenzugehoerigkeiten berechtigt ist, das Plugin aufzurufen
// Parameter: Array mit Rollen-IDs  ( => $pPreferences->config['Pluginfreigabe']['freigabe'] )
function check_showpluginPMB($array)
{
    $showPlugin = false;

    foreach ($array AS $i)
    {
        if(hasRole_IDPMB($i))
        {
            $showPlugin = true;
        } 
    } 
    return $showPlugin;
}

// Funktion date_format2mysql ersetzt date_german2mysql (erstellt von eiseli)
function date_format2mysql($date)
{
	return date('Y-m-d',strtotime($date));
}

function ageCalculator( $geburtstag, $stichtag )
{ 
    $day = date("d", $geburtstag); 
    $month = date("m", $geburtstag); 
    $year = date("Y", $geburtstag); 
       
    $cur_day = date("d", $stichtag); 
    $cur_month = date("m", $stichtag); 
    $cur_year = date("Y", $stichtag); 

    $calc_year = $cur_year - $year; 
     
    if( $month > $cur_month ) 
        return $calc_year - 1; 
    elseif ( $month == $cur_month && $day > $cur_day ) 
        return $calc_year - 1; 
     else 
        return $calc_year; 
}   

// Funktion überprüft den übergebenen Namen, ob er gemaess den Namenskonventionen für
// Profilfelder und Kategorien zum Uebersetzen durch eine Sprachdatei geeignet ist
// Bsp: SYS_COMMON --> Rueckgabe true
// Bsp: Mitgliedsbeitrag --> Rueckgabe false
function check_languagePMB($field_name)
{
    $ret = false;
 
    //prüfen, ob die ersten 3 Zeichen von $field_name Grußbuchstaben sind
    //prüfen, ob das vierte Zeichen von $field_name ein _ ist
    
    //Prüfung entfällt: prüfen, ob die restlichen Zeichen von $field_name Grußbuchstaben sind
    //if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')  && (ctype_upper(substr($field_name,4)))   )

    if ((ctype_upper(substr($field_name,0,3))) && ((substr($field_name,3,1))=='_')   )
    {
    	$ret = true;
    }
    return $ret;
} 

// diese Funktion erzeugt eine Mitgliedsnummer
function erzeuge_mitgliedsnummer()
{    
	global $gDb,$gMessage,$gL10n;
	
    $rueckgabe_mitgliedsnummer = 0;
 	$mitgliedsnummern = array();
	$id_mitgliedsnummer = 0;
	
	$sql = ' SELECT usf_id
             FROM '.TBL_USER_FIELDS.'
             WHERE usf_name = \'PMB_MEMBERNUMBER\' ';
    $result = $gDb->query($sql);
   	$row = $gDb->fetch_object($result);
    $id_mitgliedsnummer = $row->usf_id;
    
    $sql = 'SELECT usd_value
            FROM '.TBL_USER_DATA.'
            WHERE usd_usf_id = \''.$id_mitgliedsnummer.'\' ';
    $result = $gDb->query($sql);
    while( $row = $gDb->fetch_array($result))
	{
		$mitgliedsnummern[] = $row['usd_value'];
	}	
      
    sort($mitgliedsnummern);
   
    //überprüfung auf doppelte Mitgliedsnummern
    for ($i=0; $i < count($mitgliedsnummern)-1;$i++)
    {
        if ($mitgliedsnummern[$i] == $mitgliedsnummern[$i+1])
        {
            $gMessage->show($gL10n->get('PMB_MEMBERNUMBER_ERROR',$mitgliedsnummern[$i]));
            break;
     	}
    }     
        
    $hoechste_mitgliedsnummer = end($mitgliedsnummern);

    $i = 1;
    while ($i < $hoechste_mitgliedsnummer)
    {
        if (!in_array($i,$mitgliedsnummern)) 
        {
            $rueckgabe_mitgliedsnummer = $i;
            break;
        }
        $i++;
    }  
    return ($rueckgabe_mitgliedsnummer == 0) ? $hoechste_mitgliedsnummer+1 : $rueckgabe_mitgliedsnummer;
}

// loescht Zeilen in einem Array
function delete_without_BEITRAG ( $wert )
{
    global $gCurrentOrganization;
    return ( $wert['BEITRAG'.$gCurrentOrganization->getValue('org_id')] != NULL );
}
// loescht Zeilen in einem Array
function delete_without_IBAN ( $wert )
{    
    return ( $wert['IBAN'] != NULL );
}
// loescht Zeilen in einem Array
function delete_without_BIC ( $wert )
{    
    return ( $wert['BIC'] != NULL );
}
// loescht Zeilen in einem Array
function delete_with_MANDATEID ( $wert )
{       
	global $gCurrentOrganization;
    return !( $wert['MANDATEID'.$gCurrentOrganization->getValue('org_id')] != NULL );
}
// loescht Zeilen in einem Array
function delete_with_BEZAHLT ( $wert )
{       
	global $gCurrentOrganization;
    return !( $wert['BEZAHLT'.$gCurrentOrganization->getValue('org_id')] != NULL );
}
// loescht Zeilen in einem Array
function delete_without_MANDATEID ( $wert )
{       
	global $gCurrentOrganization;
    return ( $wert['MANDATEID'.$gCurrentOrganization->getValue('org_id')] != NULL );
}
// loescht Zeilen in einem Array
function delete_without_MANDATEDATE ( $wert )
{       
	global $gCurrentOrganization;
    return ( $wert['MANDATEDATE'.$gCurrentOrganization->getValue('org_id')] != NULL );
}

function umlaute($tmptext)
{
	// Autor: guenter47
	// angepasst wegen einem Fehler bei der Umsetzung von ß
	
	$tmptext = htmlentities($tmptext);
	$tmptext=str_replace('&uuml;','ue', $tmptext);
	$tmptext=str_replace('&auml;','ae', $tmptext);
	$tmptext=str_replace('&ouml;','oe', $tmptext);
	$tmptext=str_replace('&szlig;','ss',$tmptext);
	$tmptext=str_replace('&Uuml;','Ue', $tmptext);
	$tmptext=str_replace('&Auml;','Ae', $tmptext);
	$tmptext=str_replace('&Ouml;','Oe', $tmptext);
	return $tmptext;
}

//Funktion replace_sepadaten
// ersetzt und entfernt unzulässige Zeichen in der SEPA-XML-Datei
/*
Zulässige Zeichen
Für die Erstellung von SEPA-Nachrichten sind die folgenden Zeichen in der 
Kodierung gemäß UTF-8 bzw. ISO-885933 zugelassen.
---------------------------------------------------
Zugelassener Zeichencode| Zeichen 	| Hexcode
Numerische Zeichen 		| 0 bis 9	| X'30' bis X'39'
Großbuchstaben 			| A bis Z 	| X'41' bis X'5A'
Kleinbuchstaben 		| a bis z 	| X'61' bis 'X'7A'
Apostroph 				|  '  		| X'27
Doppelpunkt 			|  :  		| X'3A
Fragezeichen 			|  ?  		| X'3F
Komma 					|  ,  		| X'2C
Minus 					|  -  		| X'2D
Leerzeichen 			|     		| X'20
Linke Klammer 			|  (  		| X'28
Pluszeichen 			|  +  		| X'2B
Rechte Klammer 			|  )  		| X'29
Schrägstrich 			|  /  		| X'2F
*/
function replace_sepadaten($tmptext)
{
	$charMap = array(
        'Ä' => 'Ae',
        'ä' => 'ae',
        'À' => 'A',
        'à' => 'a',
        'Á' => 'A',
        'á' => 'a',
        'Â' => 'A',
        'â' => 'a',
        'Æ' => 'AE',
        'æ' => 'ae',
        'Ã' => 'A',
        'ã' => 'a',
        'Å' => 'A',
        'å' => 'a',
        'Ç' => 'C',
        'ç' => 'c',
        'Ë' => 'E',
        'ë' => 'e',
        'È' => 'E',
        'è' => 'e',
        'É' => 'E',
        'é' => 'e',
        'Ê' => 'E',
        'ê' => 'e',
        'Ï' => 'I',
        'ï' => 'i',
        'Ì' => 'I',
        'ì' => 'i',
        'Í' => 'I',
        'í' => 'i',
        'Î' => 'I',
        'î' => 'i',
        'ß' => 'ss',
        'Ñ' => 'N',
        'ñ' => 'n',
        'Œ' => 'OE',
        'œ' => 'oe',
        'Ö' => 'Oe',
        'ö' => 'oe',
        'Ò' => 'O',
        'ò' => 'o',
        'Ó' => 'O',
        'ó' => 'o',
        'Ô' => 'O',
        'ô' => 'o',
        'Õ' => 'O',
        'õ' => 'o',
        'Ø' => 'O',
        'ø' => 'o',
        'ß' => 'ss',
        'Ü' => 'Ue',
        'ü' => 'ue',
        'Ù' => 'U',
        'ù' => 'u',
        'Ú' => 'U',
        'ú' => 'u',
        'Û' => 'U',
        'û' => 'u',
        'ÿ' => 'y',
        'Ý' => 'Y',
        'ý' => 'y',
		'€' => 'EUR',
		'&' => 'und');

	$ret=str_replace(array_keys($charMap), array_values($charMap), $tmptext);
	
	for ($i=0;$i<strlen($ret);$i++)
	{
  		if (preg_match('/[^A-Za-z0-9\'\:\?\,\-\s\(\+\)\/]/',substr($ret,$i,1))) 
  		{
  			$ret = substr_replace($ret,' ',$i,1);
  		}
 	}
	return $ret;
}

function replace_emailparameter($text,$user)
{       
	global $gCurrentOrganization,$pPreferences;

	// now replace all parameters in email text
	$text = preg_replace ('/%user_first_name%/', $user->getValue('FIRST_NAME'),  $text);
	$text = preg_replace ('/%user_last_name%/',  $user->getValue('LAST_NAME'), $text);
	$text = preg_replace ('/%organization_long_name%/', $gCurrentOrganization->getValue('org_longname'), $text);
	$text = preg_replace ('/%fee%/', $user->getValue('BEITRAG'.$gCurrentOrganization->getValue('org_id')),   $text);
	$text = preg_replace ('/%due_day%/', $user->getValue('DUEDATE'.$gCurrentOrganization->getValue('org_id')),  $text);
	$text = preg_replace ('/%mandate_id%/', $user->getValue('MANDATEID'.$gCurrentOrganization->getValue('org_id')), $text);
	$text = preg_replace ('/%creditor_id%/',  $pPreferences->config['Kontodaten']['ci'], $text);
	$text = preg_replace ('/%iban%/',   $user->getValue('IBAN'), $text);
	$text = preg_replace ('/%bic%/',   $user->getValue('BIC'), $text);
	$text = preg_replace ('/%debtor%/',   $user->getValue('KONTOINHABER'), $text);
	$text = preg_replace ('/%membership_fee_text%/', $user->getValue('BEITRAGSTEXT'.$gCurrentOrganization->getValue('org_id')),   $text);
 
	return $text; 
}

function expand_rollentyp($rollentyp='')
{ 
	global $gL10n;
	
	if ($rollentyp=='fix')
    {
    	$ret = $gL10n->get('PMB_OTHER_CONTRIBUTION_ROLES');
    }
    elseif($rollentyp=='fam')
    {
    	$ret = $gL10n->get('PMB_FAMILY_ROLES');
    }
    else             //==alt
    {
    	$ret = $gL10n->get('PMB_AGE_STAGGERED_ROLES');
    }
	return $ret;				
}
	
?>