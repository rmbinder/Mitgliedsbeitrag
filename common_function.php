<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once(substr(__FILE__, 0, strpos(__FILE__, 'adm_plugins')-1).'/adm_program/system/common.php');

/**
 * Funktion um alle beitragsbezogenen Rollen einzulesen
 * @param string $rollenwahl [optional]     'alt' für alle altersgestaffelte Rollen,
 *                               			'fam' für alle Familienrollen,
 *                               			'fix' für alle Fixbeitragsrollen,
 *                               			leer für alle Beitragsrollen
 * @param   array   $with_members [optional]   Um die Rollen mit Mitgliedern einzulesen ist hier
 *                                            ein Array mit den einzulesenden usf_name_intern anzugeben,
 *                                            z.B. array('FIRST_NAME','LAST_NAME');
 *                                            ohne übergebenen Parameter werden die Rollen ohne Mitglieder eingelesen
 * @return  array   $rollen         Array mit Rollennamen im Format:<br>
 *                                  $rollen[rol_id]['rolle']                  =Rollenname ('rol_name')<br>
 *                                  $rollen[rol_id]['rol_cost']               =Beitrag der Rollen ('rol_cost')<br>
 *                                  $rollen[rol_id]['rol_cost_period']        =Beitragszeitraum ('rol_cost_period')<br>
 *                                  $rollen[rol_id]['rol_timestamp_create']   =Erzeugungsdatum der Rolle ('rol_timestamp_create')<br>
 *                                  $rollen[rol_id]['rol_description']        =Beschreibung ('rol_description')<br>
 *                                  $rollen[rol_id]['von']                    =nur bei altersgestaffelnten Rollen 'von'<br>
 *                                  $rollen[rol_id]['bis']                    =nur bei altersgestaffelnten Rollen 'bis'<br>
 *                                  $rollen[rol_id]['alterstyp']              =nur bei altersgestaffelnten Rollen 'Trennzeichen'<br>
 *                                  $rollen[rol_id]['rollentyp']              =Rollentyp ('alt', 'fam' oder 'fix')
 */
function beitragsrollen_einlesen($rollenwahl = '', $with_members = array())
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

    $statement = $gDb->query($sql);

	while ($row = $statement->fetch())
    {
        $rollen[$row['rol_id']] = array('rolle' => $row['rol_name'], 'rol_cost' => $row['rol_cost'], 'rol_cost_period' => $row['rol_cost_period'], 'rol_timestamp_create' => $row['rol_timestamp_create'], 'rol_description' => $row['rol_description'], 'von' => 0, 'bis' => 0, 'rollentyp' => '');
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

                if((is_numeric($rollen[$key]['von'])) && (is_numeric($rollen[$key]['bis'])))
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
    		if (substr($data['rolle'], 0, strlen($pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])) == $pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])
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
        if (($rollenwahl == 'fam') && ($rollen[$key]['rollentyp'] != 'fam'))
        {
            unset($rollen[$key]);
        }
        elseif (($rollenwahl == 'alt') && ($rollen[$key]['rollentyp'] != 'alt'))
        {
            unset($rollen[$key]);
        }
        elseif (($rollenwahl == 'fix') && ($rollen[$key]['rollentyp'] != 'fix'))
        {
            unset($rollen[$key]);
        }
        else
        {
            if (is_array($with_members) && sizeof($with_members)>0)
            {
                $rollen[$key]['members'] = list_members($with_members, array($data['rolle'] => 0));
            }
        }
    }
    return $rollen;
}

/**
 * Liest alle Mitglieder von Rollen einer oder mehrerer Kategorie(en) ein.
 * Die cat_ids der einzulesenden Kategorien werden direkt aus der $config_ini gelesen
 * @return  array $members   Array mit den user_ids der Mitglieder
 */
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

    $statement = $gDb->query($sql);
    while ($row = $statement->fetch())
    {
       $members[] = $row['mem_usr_id'];
    }

    return $members;
}

/**
 * Funktion prueft, ob ein User Angehöriger einer bestimmten Rolle ist
 *
 * @param   int  $role_id   ID der zu pruefenden Rolle
 * @param   int  $user_id [optional]  ID des Users, fuer den die Mitgliedschaft geprueft werden soll;
 * 										ohne Übergabe, wird für den aktuellen User geprüft
 * @return  bool
 */
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

    $statement = $gDb->query($sql);

    $user_found = $statement->rowCount();

    if($user_found == 1)
    {
        return 1;
    }
    else
    {
        return 0;
    }
}

/**
 * Diese Funktion liefert als Rückgabe die usr_ids von Rollenangehörigen.<br>
 * mögliche Aufrufe:<br>
 *         list_members(array('usf_name_intern1','usf_name_intern2'),array('Rollenname1' => Schalter aktiv/ehem) )<br>
 *   oder  list_members(array('usf_name_intern1','usf_name_intern2'), 'Rollenname' )<br>
 *   oder  list_members(array('usf_name_intern1','usf_name_intern2'), Schalter aktiv/ehem )<br>
 *
 * Schalter aktiv/ehem: 0 = aktive Mitglieder, 1 = ehemalige Mitglieder, ungleich 1 oder 0: alle Mitglieder <br>
 *
 * Aufruf: z.B. list_members(array('FIRST_NAME','LAST_NAME'), array('Mitglied' => 0,'Webmaster' => 0));
 *
 * @param   array               $fields  Array mit usf_name_intern, z.B. array('FIRST_NAME','LAST_NAME')
 * @param   array/string/bool   $rols    Array mit Rollen, z.B. <br>
 *                                            array('Rollenname1' => Schalter aktiv/ehem) )<br>
 *                                       oder 'Rollenname' <br>
 *                                       oder Schalter aktiv/ehem  <br>
 * @return  array   $members
 */
function list_members($fields, $rols = array())
{
    global $gDb, $gCurrentOrganization, $gProfileFields;

    $members = array();

    $sql = 'SELECT DISTINCT mem_usr_id, mem_begin, mem_end
            FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ' ';

    if  (is_string($rols))
    {
        $sql .= ' WHERE mem_rol_id = '.getRole_IDPMB($rols).' ';
    }
    elseif  (is_integer($rols) && ($rols == 0))
    {
        // nur aktive Mitglieder
        $sql .= ' WHERE mem_begin <= \''.DATE_NOW.'\' ';
        $sql .= ' AND mem_end >= \''.DATE_NOW.'\' ';

    }
    elseif  (is_integer($rols) && ($rols == 1))
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

    $statement = $gDb->query($sql);
    while ($row = $statement->fetch())
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
            $statement = $gDb->query($sql);
		    $row = $statement->fetch();
		    $members[$member][$data] = $row['usd_value'];
	    }
    }
    return $members;
}

/**
 * Callbackfunktion für array_filter ,
 * die globale Variable $delete_NULL_field muß in der uebergebenden Routine definiert sein
 * @param   string  $wert
 * @return  bool
 */
function delete_NULL ($wert)
{
    global $delete_NULL_field;

    return  $wert[$delete_NULL_field] != NULL;
}

/**
 * Funktion liest die Role-ID einer Rolle aus
 * @param   string  $role_name Name der zu pruefenden Rolle
 * @return  int     rol_id  Rol_id der Rolle, 0, wenn nicht gefunden
 */
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

    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
	if(isset($row->rol_id) && strlen($row->rol_id) > 0)
	{
		return $row->rol_id;
	}
	else
	{
		return 0;
	}
}

/**
 * Konvertiert einen numerischen Beitragszeitraum in einen String
 * @param   int     $my_rol_cost_period Beitragszeitraum als int
 * @return  string                      Beitragszeitraum als String
 */
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

/**
 * Erzeugt Array mit Daten für die Analyse
 * @return  array $ret   Array im Format:<br>
 *                       $ret['BEITRAG_kto']        =Gesamtsumme der Beiträge mit Kto-Verbindung<br>
 *                       $ret['BEITRAG_kto_anzahl'] =Anzahl mit Kto-Verbindung<br>
 *                       $ret['BEZAHLT_kto']        =Gesamtsumme mit Bezahlt und mit Kto-Verbindung<br>
 *                       $ret['BEZAHLT_kto_anzahl'] =Anzahl mit Bezahlt und mit Kto-Verbindung<br>
 *                       $ret['BEITRAG_rech']       =Gesamtsumme der Beiträge ohne Kto-Verbindung<br>
 *                       $ret['BEITRAG_rech_anzahl']=Anzahl ohne Kto-Verbindung<br>
 *                       $ret['BEZAHLT_rech']       =Gesamtsumme mit Bezahlt und ohne Kto-Verbindung<br>
 *                       $ret['BEZAHLT_rech_anzahl']=Anzahl mit Bezahlt und ohne Kto-Verbindung
 */
function analyse_mem()
{
    global $gCurrentOrganization;

    $members = list_members(array('FEE'.$gCurrentOrganization->getValue('org_id'), 'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id'), 'PAID'.$gCurrentOrganization->getValue('org_id'), 'IBAN', 'DEBTOR'), 0);
	$ret = array('data'=> $members, 'BEITRAG_kto'=>0, 'BEITRAG_kto_anzahl'=>0, 'BEITRAG_rech'=>0, 'BEITRAG_rech_anzahl'=>0, 'BEZAHLT_kto'=>0, 'BEZAHLT_kto_anzahl'=>0, 'BEZAHLT_rech'=>0, 'BEZAHLT_rech_anzahl'=>0);

	// alle Mitglieder durchlaufen und im ersten Schritt alle Mitglieder,  
	// bei denen kein Beitrag berechnet wurde,
	// und kein Beitragstext (=Verwendungszweck) existiert,  herausfiltern
    foreach ($members as $member => $memberdata)
    {
        if (empty($memberdata['FEE'.$gCurrentOrganization->getValue('org_id')]) || empty($memberdata['CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id')]))
        {
            unset($members[$member]);
        }
    }

    //jetzt wird gezählt
    foreach($members as $member => $memberdata)
    {
        if (!empty($memberdata['IBAN']))
        {
            $ret['BEITRAG_kto'] += $memberdata['FEE'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEITRAG_kto_anzahl']+=1;
        }
        if ((!empty($memberdata['IBAN']))
        	&& !empty($memberdata['PAID'.$gCurrentOrganization->getValue('org_id')]))
        {
            $ret['BEZAHLT_kto'] += $memberdata['FEE'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEZAHLT_kto_anzahl']+=1;
        }
        if (empty($memberdata['IBAN']))
        {
            $ret['BEITRAG_rech'] += $memberdata['FEE'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEITRAG_rech_anzahl']+=1;
        }
        if (empty($memberdata['IBAN'])
        	&& !empty($memberdata['PAID'.$gCurrentOrganization->getValue('org_id')]))
        {
            $ret['BEZAHLT_rech'] += $memberdata['FEE'.$gCurrentOrganization->getValue('org_id')];
            $ret['BEZAHLT_rech_anzahl']+=1;
        }
    }
    return $ret;
}

/**
 * Erzeugt Array mit Daten für die Analyse
 * @return  array $ret
 */
function analyse_rol()
{
    global $pPreferences, $gCurrentOrganization, $gL10n;

    $ret = beitragsrollen_einlesen('alt');
    $ret = array_merge($ret, beitragsrollen_einlesen('fix'));
    foreach ($ret as $rol => $roldata)
    {
        $ret[$rol]['members'] = list_members(array('FEE'.$gCurrentOrganization->getValue('org_id'), 'PAID'.$gCurrentOrganization->getValue('org_id')), array($roldata['rolle'] => 0));
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
			if (substr($data['rolle'], 0, strlen($pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])) == $pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])
			{
				$arr[]=$key;
			}
    	}
		$ret[$pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey]] = array('rolle' => $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE').' '.$pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey], 'rol_cost' => $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$famkey], 'rol_cost_period' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$famkey], 'members' =>$arr, 'rollentyp' => 'fam');

    }
    return $ret;
}

/**
 * Prüft die Rollenmitgliedschaften in altersgestaffelten Rollen
 * @return  array $ret
 */
function check_rollenmitgliedschaft_altersrolle()
{
	global $pPreferences, $gL10n, $g_root_path;
    $ret = array();
    $alt = beitragsrollen_einlesen('alt', array('FIRST_NAME', 'LAST_NAME'));

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

    // jetzt $check durchlaufen und nur die Einträge bearbeiten, bei denen mehr als 1 Alterstyp vorhanden ist
    foreach($check as $member => $memberdata)
    {
    	if(sizeof($memberdata['alterstyp'])>1)
    	{
    		$alterstypen = '';
    		foreach($memberdata['alterstyp'] as $alterstyp)
    		{
    			$alterstypen .= ' ('.$alterstyp.')';
    		}
    		$ret[] .= '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME'].$alterstypen. '</a>';
    	}
    }

    if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prüft die Pflicht-Rollenmitgliedschaften
 * @return  array $ret
 */
function check_rollenmitgliedschaft_pflicht()
{
    global $pPreferences, $gL10n, $g_root_path;
    $ret = array();

    // alle Beitragsrollen einlesen ('FIRST_NAME' wird zwar in der Funktion nicht benötigt, ist aber notwendig,
    // damit die Rollenmitglieder eingelesen werden)
    $beitragsrollen = beitragsrollen_einlesen('', array('FIRST_NAME'));
    $members = list_members(array('FIRST_NAME', 'LAST_NAME'), 0);

    // alle Beitragsrollen durchlaufen und diejenigen Rollen löschen, die nicht als Pflichtrolle definiert sind
    foreach ($beitragsrollen as $rol => $roldata)
    {
        //alle if und elseif könnte man in einer Zeile schreiben und mit || verknüpfen, aber so ist es übersichtlicher 
        if(($roldata['rollentyp'] == 'fam') && (!$pPreferences->config['Rollenpruefung']['familienrollenpflicht']))
        {
            unset($beitragsrollen[$rol]);
        }
        elseif(($roldata['rollentyp'] == 'alt') && (is_array($pPreferences->config['Rollenpruefung']['altersrollenpflicht'])) && !(in_array($roldata['alterstyp'], $pPreferences->config['Rollenpruefung']['altersrollenpflicht'])))
        {
            unset($beitragsrollen[$rol]);
        }
        elseif (($roldata['rollentyp'] == 'fix') && (!is_array($pPreferences->config['Rollenpruefung']['fixrollenpflicht'])))
        {
            unset($beitragsrollen[$rol]);
        }
        elseif (($roldata['rollentyp'] == 'fix') && (is_array($pPreferences->config['Rollenpruefung']['fixrollenpflicht'])) && !(in_array($rol, $pPreferences->config['Rollenpruefung']['fixrollenpflicht'])))
        {
            unset($beitragsrollen[$rol]);
        }
    }
    // in $beitragsrollen sind jetzt nur noch Pflicht-Beitragsrollen 

    // Feature-Wunsch von joesch
    $bezugskategorieMembers = array();
    if ($pPreferences->config['Rollenpruefung']['bezugskategorie'][0]!=' ')
    {
    	// zuerst alle Member der Bezugskategorien einlesen
		$bezugskategorieMembers = bezugskategorie_einlesen();

		foreach ($members as $member => $memberdata)
    	{
    		// alle usr_ids löschen, wenn sie nicht innerhalb der Bezugskategorie sind 
    		if (!in_array($member, $bezugskategorieMembers))
    		{
    			unset($members[$member]);
    		}
    	}
    }

    // alle Mitglieder durchlaufen und prüfen, ob sie in mind. einer Pflicht-Beitragsrolle sind
    foreach ($members as $member => $memberdata)
    {
        $marker = false;
        foreach ($beitragsrollen as $rol => $roldata)
        {
            if(array_key_exists($member, $roldata['members']))
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
        $ret = array($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prüft die Auschluss-Rollenmitgliedschaften
 * @return  array $ret
 */
function check_rollenmitgliedschaft_ausschluss()
{
    global $pPreferences, $gL10n, $g_root_path;
    $ret = array();

    // alle Beitragsrollen einlesen ('FIRST_NAME' wird zwar in der Funktion nicht benötigt, ist aber notwendig,
    // damit die Rollenmitglieder eingelesen werden)
    $beitragsrollen = beitragsrollen_einlesen('', array('FIRST_NAME'));
    $members = list_members(array('FIRST_NAME', 'LAST_NAME'), 0);

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
                $members[$key]['rollen'][]= $roldata['alterstyp'].'alt';
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
    if ($pPreferences->config['Rollenpruefung']['bezugskategorie'][0]!= ' ')
    {
    	// zuerst alle Member der Bezugskategorien einlesen
		$bezugskategorieMembers = bezugskategorie_einlesen();

		foreach ($members as $member => $memberdata)
    	{
    		// alle usr_ids löschen, wenn sie nicht innerhalb der Bezugskategorie sind 
    		if (!in_array($member, $bezugskategorieMembers))
    		{
    			unset($members[$member]);
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
    	if(is_array($pPreferences->config['Rollenpruefung']['altersrollenaltersrollen']))
        {
        	foreach ($pPreferences->config['Rollenpruefung']['altersrollenaltersrollen'] as $data)
            {
            	$token = explode(',', $data);
            	if((in_array($token[0].'alt', $memberdata['rollen'])) && (in_array($token[1].'alt', $memberdata['rollen'])))
        		{
            		$marker = true;
        		}
            }
        }
        if(is_array($pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen']))
        {
        	foreach ($pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen'] as $token)
            {
            	if((in_array('fam', $memberdata['rollen'])) && (in_array($token.'alt', $memberdata['rollen'])))
        		{
            		$marker = true;
        		}
            }
        }
        if(is_array($pPreferences->config['Rollenpruefung']['familienrollenfix']))
        {
            foreach ($pPreferences->config['Rollenpruefung']['familienrollenfix'] as $rol => $roldata)
            {
                if((in_array($roldata, $memberdata['rollen'])) && (in_array('fam', $memberdata['rollen'])))
                {
                    $marker = true;
                }
            }
        }
        if(is_array($pPreferences->config['Rollenpruefung']['altersrollenfix']) &&  is_array($pPreferences->config['Altersrollen']['altersrollen_token']))
        {
        	foreach($pPreferences->config['Altersrollen']['altersrollen_token'] as $token)
            {
            	foreach ($pPreferences->config['Rollenpruefung']['altersrollenfix'] as $rol => $roldata)
            	{
                	if((in_array(substr($roldata, strlen($token)), $memberdata['rollen'])) && (in_array($token.'alt', $memberdata['rollen'])))
                	{
                    	$marker = true;
                	}
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
        $ret = array($gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Vergleichsfunktion erforderlich für usort()
 * @param  $wert_a
 * @param  $wert_b
 * @return int 0,1 oder -1
 */
function vergleich($wert_a, $wert_b)
{
    //Sortierung nach dem zweiten Wert des Arrays (Index: 1)
    $a = $wert_a['year'];
    $b = $wert_b['year'];

    if ($a == $b) return 0;
    elseif ($a > $b) return 1;
    else return -1;
}

/**
 * Prüft die altersgestaffelten Rollen auf Lücken bzw Überschneidungen
 * @return  array $ret
 */
function check_rols()
{
	global $pPreferences,$gL10n;
    $ret = array();
    $alt = beitragsrollen_einlesen('alt', array('LAST_NAME'));

    foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $tokenkey => $tokendata)
    {
    	$check = array();
    	foreach ($alt as $altrol => $altdata)
    	{
    		if ($altdata['alterstyp']==$tokendata)
    		{
	       		$check[]  = array('year' => $altdata['von'], 'rol' => $altrol);
        		$check[]  = array('year' => $altdata['bis'], 'rol' => $altrol);
    		}
    	}

    	usort($check, 'vergleich');

    	for ($i = 0; $i < sizeof($check)-1; $i = $i+2)
    	{
	   	if ($check[$i]['rol'] != $check[$i+1]['rol'])
        	{
            	$ret[$check[$i]['rol']] = '- '.$alt[$check[$i]['rol']]['rolle'];
            	$ret[$check[$i+1]['rol']] = '- '.$alt[$check[$i+1]['rol']]['rolle'];
			}
			if (($i < sizeof($check)-2) && ($check[$i+1]['year'] != ($check[$i+2]['year'])-1))
        	{
            	$ret[$check[$i+1]['rol']] = '- '.$alt[$check[$i+1]['rol']]['rolle'];
            	$ret[$check[$i+2]['rol']] = '- '.$alt[$check[$i+2]['rol']]['rolle'];
			}
		}
    }

    if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prüft, ob Angehörige von Familienrollen die Prüfbedingungen erfüllen
 * @return  array $ret
 */
function check_family_roles()
{
    global $pPreferences, $gL10n, $g_root_path;
    $ret = array();
    $ret_error = array();
    $temp_arr  = array();
    $temp_arr2 = array();
    $ret_marker = false;
    $fam = beitragsrollen_einlesen('fam', array('LAST_NAME', 'FIRST_NAME', 'BIRTHDAY'));
    $check = $pPreferences->config['Familienrollen'];

    // alle Prüfbedingungen einlesen
	foreach($check['familienrollen_prefix'] as $key => $prefix)
    {
    	$temp_arr = explode(';', $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key]);
		foreach ($temp_arr as $keybed => $bedingung)
		{
			// den Doppelpunkt in der Prüfbedingung ersetzen
			// eine Prüfbedingung könnte deshalb auch in folgender Syntax geschrieben werden: von*bis*Anzahl
			$bedingung = str_replace(':', '*', $bedingung);

			$temp_arr2 = explode('*', $bedingung);

			// prüfen auf unsinnige Bedingungen
			if(isset($temp_arr2[0]) && isset($temp_arr2[1]) && isset($temp_arr2[2])
			 && is_numeric($temp_arr2[0]) && is_numeric($temp_arr2[1]) && is_numeric($temp_arr2[2]))
			{
				$check['pruefungsbedingungen'][$key][$keybed]['von'] = $temp_arr2[0];
				$check['pruefungsbedingungen'][$key][$keybed]['bis'] = $temp_arr2[1];
				$check['pruefungsbedingungen'][$key][$keybed]['anz'] = $temp_arr2[2];
			}
			else
			{
				unset(
				    $check['familienrollen_prefix'][$key],
                    $check['familienrollen_beitrag'][$key],
                    $check['familienrollen_zeitraum'][$key],
                    $check['familienrollen_beschreibung'][$key],
                    $check['familienrollen_pruefung'][$key],
                    $check['pruefungsbedingungen'][$key]
                );

    			$ret_marker = true;
    			continue;
			}
		}

		// Meldung bei fehlerhaften Prüfbedingungen
		if($ret_marker && strlen($pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key])>0)
		{
			$ret_error[] = '<small>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONDITION').' '.$pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key].' ('.$prefix.') '.$gL10n->get('PLG_MITGLIEDSBEITRAG_INVALID').'.</small>';
		}
		$ret_marker = false;
    }

    // Leerzeile einfügen
	if (sizeof($ret_error) != 0)
    {
        $ret_error[] = '';
    }

    unset($bedingung, $keybed, $temp_arr, $temp_arr2, $ret_marker);

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
    					if ($age >= $pruefdata['von'] && $age <= $pruefdata['bis'])
            			{
                			$counter++;
            			}
    				}

    				if ($counter != $pruefdata['anz'])
                	{
                		$ret_temp[] = '&#160&#160&#160&#183<small>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONDITION').' '.$pruefdata['von'].'*'.$pruefdata['bis'].':'.$pruefdata['anz'].' '.$gL10n->get('PLG_MITGLIEDSBEITRAG_NOT_SATISFIED').'.</small>';
                	}
    			}
    			if (sizeof($ret_temp) != 0)
    			{
	   				$ret[] = '- <a href="'.$g_root_path.'/adm_program/modules/roles/roles_new.php?rol_id='. $famkey. '">'.$famdata['rolle']. '</a>
	   					<a href="'.$g_root_path.'/adm_program/modules/lists/lists_show.php?mode=html&rol_ids='. $famkey. '"><img src="'. THEME_PATH. '/icons/list.png"
	   					alt="'.$gL10n->get('ROL_SHOW_MEMBERS').'" title="'.$gL10n->get('ROL_SHOW_MEMBERS').'" /></a>';
	   				$ret = array_merge($ret, $ret_temp);
    			}
    		}
    	}
    }

	if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_RESULT_ERROR').'</strong>';
    }

    // eine evtl. vorhandene Fehlermeldung davorsetzen 
	if (sizeof($ret_error) != 0)
    {
    	$ret = array_merge($ret_error, $ret);
    }
    return $ret;
}

/**
 * Prüft, ob bei Angabe eines Kontoinhabers alle erforderlichen Daten (Adresse, Ort...) vorhanden sind
 * @return  array $ret
 */
function check_mandate_management()
{
    global $gL10n, $g_root_path;
    $ret = array();

    $members = list_members(array('FIRST_NAME', 'LAST_NAME', 'DEBTOR', 'DEBTOR_POSTCODE', 'DEBTOR_CITY', 'DEBTOR_ADDRESS'), 0);

	foreach ($members as $member => $memberdata)
	{
		if ((strlen($memberdata['DEBTOR'])!=0) && ((strlen($memberdata['DEBTOR_POSTCODE'])==0) || (strlen($memberdata['DEBTOR_CITY'])==0) || (strlen($memberdata['DEBTOR_ADDRESS'])==0)))
		{
			$ret[] = '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
		}
	}

	if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Durchläuft alle Mitglieder und prüft deren IBAN
 * @return  array $ret
 */
function check_iban()
{
    global $gL10n, $g_root_path;
    $ret = array();

    $members = list_members(array('FIRST_NAME', 'LAST_NAME', 'IBAN'), 0);

	foreach ($members as $member => $memberdata)
	{
		if ((strlen($memberdata['IBAN'])==1) || ((strlen($memberdata['IBAN'])>1) && !test_iban($memberdata['IBAN'])))
		{
			$ret[] = '- <a href="'.$g_root_path.'/adm_program/modules/profile/profile.php?user_id='. $member. '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
		}
	}

	if (sizeof($ret) == 0)
    {
        $ret = array($gL10n->get('PLG_MITGLIEDSBEITRAG_IBANCHECK_RESULT_OK'));
    }
    else
    {
        $ret[] = '<BR><strong>=> '.$gL10n->get('PLG_MITGLIEDSBEITRAG_IBANCHECK_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prüft die übergebene IBAN
 * @param string $iban
 * @return  bool
 */
function test_iban($iban)
{
	// aus dem Internet
    $iban = str_replace(' ', '', $iban);
    $iban1 = substr($iban, 4)
        . strval(ord($iban{0})-55)
        . strval(ord($iban{1})-55)
        . substr($iban, 2, 2);

    $rest=0;
    for ($pos=0; $pos<strlen($iban1); $pos+=7) {
        $part = strval($rest) . substr($iban1, $pos, 7);
        $rest = intval($part) % 97;
    }
    $pz = sprintf("%02d", 98-$rest);

    if (substr($iban, 2, 2)=='00')
        return substr_replace($iban, $pz, 2, 2);
    else
        return ($rest==1) ? true : false;
}

/**
 * Funktion prueft, ob der Nutzer, aufgrund seiner Rollenzugehörigkeit, berechtigt ist das Plugin aufzurufen
 * @param   array  $array   Array mit Rollen-IDs:   entweder $pPreferences->config['Pluginfreigabe']['freigabe']
 *                                                  oder $pPreferences->config['Pluginfreigabe']['freigabe_config']
 * @return  bool   $showPlugin
 */
function check_showpluginPMB($array)
{
    $showPlugin = false;

    foreach ($array as $i)
    {
        if(hasRole_IDPMB($i))
        {
            $showPlugin = true;
        }
    }
    return $showPlugin;
}

/**
 * Formatiert den übergebenen Datumsstring für MySQL,
 * date_format2mysql ersetzt date_german2mysql (erstellt von eiseli)
 * @param   string  $date       Datumsstring
 * @return  date                Datum im Format Y-m-d
 */
function date_format2mysql($date)
{
	return date('Y-m-d', strtotime($date));
}

/**
 * Berechnet das Alter an einem bestimmten Tag (Stichtag)
 * @param   date  $geburtstag     Datum des Geburtstages
 * @param   date  $stichtag       Datum des Stichtages
 * @return  int                   Das Alter in Jahren
 */
function ageCalculator($geburtstag, $stichtag)
{
    $day = date("d", $geburtstag);
    $month = date("m", $geburtstag);
    $year = date("Y", $geburtstag);

    $cur_day = date("d", $stichtag);
    $cur_month = date("m", $stichtag);
    $cur_year = date("Y", $stichtag);

    $calc_year = $cur_year - $year;

    if($month > $cur_month)
        return $calc_year - 1;
    elseif ($month == $cur_month && $day > $cur_day)
        return $calc_year - 1;
     else
        return $calc_year;
}

/**
 * Funktion erzeugt eine Mitgliedsnummer
 * @return  int     Mitgliedsnummeer
 */
function erzeuge_mitgliedsnummer()
{
	global $gDb,$gMessage,$gL10n;

    $rueckgabe_mitgliedsnummer = 0;
 	$mitgliedsnummern = array();
	$id_mitgliedsnummer = 0;

	$sql = ' SELECT usf_id
             FROM '.TBL_USER_FIELDS.'
             WHERE usf_name = \'PMB_MEMBERNUMBER\' ';
    $statement = $gDb->query($sql);
    $row = $statement->fetchObject();
    $id_mitgliedsnummer = $row->usf_id;

    $sql = 'SELECT usd_value
            FROM '.TBL_USER_DATA.'
            WHERE usd_usf_id = \''.$id_mitgliedsnummer.'\' ';
	$statement = $gDb->query($sql);

	while($row = $statement->fetch())
	{
		$mitgliedsnummern[] = $row['usd_value'];
	}

    sort($mitgliedsnummern);

    //überprüfung auf doppelte Mitgliedsnummern
    for ($i=0; $i < count($mitgliedsnummern)-1; $i++)
    {
        if ($mitgliedsnummern[$i] == $mitgliedsnummern[$i+1])
        {
            $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_MEMBERNUMBER_ERROR', $mitgliedsnummern[$i]));
            break;
     	}
    }

    $hoechste_mitgliedsnummer = end($mitgliedsnummern);

    $i = 1;
    while ($i < $hoechste_mitgliedsnummer)
    {
        if (!in_array($i, $mitgliedsnummern))
        {
            $rueckgabe_mitgliedsnummer = $i;
            break;
        }
        $i++;
    }
    return ($rueckgabe_mitgliedsnummer == 0) ? $hoechste_mitgliedsnummer+1 : $rueckgabe_mitgliedsnummer;
}

/**
 * Callbackfunktion für array_filter
 * @param   string  $wert
 * @return  bool    true, wenn Beitrag  != NULL ist
 */
function delete_without_BEITRAG ($wert)
{
    global $gCurrentOrganization;
    return  $wert['FEE'.$gCurrentOrganization->getValue('org_id')] != NULL;
}

/**
 * Callbackfunktion für array_filter
 * @param   string  $wert
 * @return  bool    true, wenn IBAN  != NULL ist
 */
function delete_without_IBAN ($wert)
{
    return  $wert['IBAN'] != NULL;
}

/**
 * Callbackfunktion für array_filter
 * @param   string  $wert
 * @return  bool    true, wenn BIC  != NULL ist
 */
function delete_without_BIC ($wert)
{
    return  $wert['BIC'] != NULL;
}

/**
 * Callbackfunktion für array_filter
 * @param   string  $wert
 * @return  bool    true, wenn MandateID  == NULL ist
 */
function delete_with_MANDATEID ($wert)
{
	global $gCurrentOrganization;
    return !($wert['MANDATEID'.$gCurrentOrganization->getValue('org_id')] != NULL);
}

/**
 * Callbackfunktion für array_filter
 * @param   string  $wert
 * @return  bool    true, wenn Bezahlt  == NULL ist
 */
function delete_with_BEZAHLT ($wert)
{
	global $gCurrentOrganization;
    return !($wert['PAID'.$gCurrentOrganization->getValue('org_id')] != NULL);
}

/**
 * Callbackfunktion für array_filter
 * @param   string  $wert
 * @return  bool    true, wenn MandateID  != NULL ist
 */
function delete_without_MANDATEID ($wert)
{
	global $gCurrentOrganization;
    return  $wert['MANDATEID'.$gCurrentOrganization->getValue('org_id')] != NULL;
}

/**
 * Callbackfunktion für array_filter
 * @param   string  $wert
 * @return  bool    true, wenn MandateID  != NULL ist
 */
function delete_without_MANDATEDATE ($wert)
{
	global $gCurrentOrganization;
    return  $wert['MANDATEDATE'.$gCurrentOrganization->getValue('org_id')] != NULL;
}

/**
 * Ersetzt Umlaute
 * @param   string  $tmptext
 * @return  string  $tmptext
 */
function umlaute($tmptext)
{
	// Autor: guenter47
	// angepasst wegen einem Fehler bei der Umsetzung von ß

	$tmptext = htmlentities($tmptext);
	$tmptext=str_replace('&uuml;', 'ue', $tmptext);
	$tmptext=str_replace('&auml;', 'ae', $tmptext);
	$tmptext=str_replace('&ouml;', 'oe', $tmptext);
	$tmptext=str_replace('&szlig;', 'ss', $tmptext);
	$tmptext=str_replace('&Uuml;', 'Ue', $tmptext);
	$tmptext=str_replace('&Auml;', 'Ae', $tmptext);
	$tmptext=str_replace('&Ouml;', 'Oe', $tmptext);
	return $tmptext;
}

/**
 * Ersetzt und entfernt unzulässige Zeichen in der SEPA-XML-Datei
 * @param   string  $tmptext
 * @return  string  $ret
 */
function replace_sepadaten($tmptext)
{
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

	for ($i=0; $i<strlen($ret); $i++)
	{
  		if (preg_match('/[^A-Za-z0-9\'\:\?\,\-\s\(\+\)\/]/', substr($ret, $i, 1)))
  		{
  			$ret = substr_replace($ret, ' ', $i, 1);
  		}
 	}
	return $ret;
}

/**
 * Ersetzt Parameter im E-Mail-Text
 * @param   string  $text       Die E-Mail-Nachricht mit Parametern
 * @param   objekt  $user       User-Objekt
 * @return  string  $text       Die E-Mail-Nachricht mit ersetzten Werten
 */
function replace_emailparameter($text, $user)
{
	global $gCurrentOrganization,$pPreferences;

	// now replace all parameters in email text
	$text = preg_replace('/#user_first_name#/', $user->getValue('FIRST_NAME'),  $text);
	$text = preg_replace('/#user_last_name#/',  $user->getValue('LAST_NAME'), $text);
	$text = preg_replace('/#organization_long_name#/', $gCurrentOrganization->getValue('org_longname'), $text);
	$text = preg_replace('/#fee#/', $user->getValue('FEE'.$gCurrentOrganization->getValue('org_id')),   $text);
	$text = preg_replace('/#due_day#/', $user->getValue('DUEDATE'.$gCurrentOrganization->getValue('org_id')),  $text);
	$text = preg_replace('/#mandate_id#/', $user->getValue('MANDATEID'.$gCurrentOrganization->getValue('org_id')), $text);
	$text = preg_replace('/#creditor_id#/',  $pPreferences->config['Kontodaten']['ci'], $text);
	$text = preg_replace('/#iban#/',   $user->getValue('IBAN'), $text);
	$text = preg_replace('/#bic#/',   $user->getValue('BIC'), $text);
	$text = preg_replace('/#debtor#/',   $user->getValue('DEBTOR'), $text);
	$text = preg_replace('/#membership_fee_text#/', $user->getValue('CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id')),   $text);

	return $text;
}

/**
 * Wandelt Rollentyp von Kurzform in Langform um
 * @param   string  $rollentyp  Rollentyp in Kurzform ('fix' oder 'fam')
 * @return  string  $ret        Rollentyp in Langform (z.B. 'Familienrollen')
 */
function expand_rollentyp($rollentyp='')
{
	global $gL10n;

	if ($rollentyp=='fix')
    {
    	$ret = $gL10n->get('PLG_MITGLIEDSBEITRAG_OTHER_CONTRIBUTION_ROLES');
    }
    elseif($rollentyp=='fam')
    {
    	$ret = $gL10n->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES');
    }
    else             //==alt
    {
    	$ret = $gL10n->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES');
    }
	return $ret;
}
