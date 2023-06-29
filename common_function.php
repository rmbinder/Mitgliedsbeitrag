<?php
/**
 ***********************************************************************************************
 * Gemeinsame Funktionen fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/../../adm_program/system/common.php');

if(!defined('PLUGIN_FOLDER'))
{
	define('PLUGIN_FOLDER', '/'.substr(__DIR__,strrpos(__DIR__,DIRECTORY_SEPARATOR)+1));
}

/**
 * Funktion um alle beitragsbezogenen Rollen einzulesen
 * @param string $rollenwahl [optional]     'alt' fuer alle altersgestaffelte Rollen,
 *                                          'fam' fuer alle Familienrollen,
 *                                          'fix' fuer alle Fixbeitragsrollen,
 *                                          leer fuer alle Beitragsrollen
 * @param   array   $with_members [optional]   Um die Rollen mit Mitgliedern einzulesen ist hier
 *                                            ein Array mit den einzulesenden usf_name_intern anzugeben,
 *                                            z.B. array('FIRST_NAME','LAST_NAME');
 *                                            ohne uebergebenen Parameter werden die Rollen ohne Mitglieder eingelesen
 * @return  array   $rollen         Array mit Rollennamen im Format:<br/>
 *                                  $rollen[rol_id]['rolle']                  =Rollenname ('rol_name')<br/>
 *                                  $rollen[rol_id]['rol_cost']               =Beitrag der Rollen ('rol_cost')<br/>
 *                                  $rollen[rol_id]['rol_cost_period']        =Beitragszeitraum ('rol_cost_period')<br/>
 *                                  $rollen[rol_id]['rol_timestamp_create']   =Erzeugungsdatum der Rolle ('rol_timestamp_create')<br/>
 *                                  $rollen[rol_id]['rol_description']        =Beschreibung ('rol_description')<br/>
 *                                  $rollen[rol_id]['von']                    =nur bei altersgestaffelten Rollen 'von'<br/>
 *                                  $rollen[rol_id]['bis']                    =nur bei altersgestaffelten Rollen 'bis'<br/>
 *                                  $rollen[rol_id]['alterstyp']              =nur bei altersgestaffelten Rollen 'Trennzeichen'<br/>
 *                                  $rollen[rol_id]['rollentyp']              =Rollentyp ('alt', 'fam' oder 'fix')
 */
function beitragsrollen_einlesen($rollenwahl = '', $with_members = array())
{
    global $pPreferences;
    $rollen = array();

    // alle Rollen einlesen
    $sql = 'SELECT rol_id, rol_name, rol_cost, rol_cost_period, rol_timestamp_create, rol_description
              FROM '.TBL_ROLES.', '. TBL_CATEGORIES. '
             WHERE rol_valid  = 1
               AND rol_cost IS NOT NULL
               AND rol_cost_period <> \'\'
               AND rol_cat_id = cat_id
               AND ( cat_org_id = ?
                OR cat_org_id IS NULL ) ';

    $statement = $GLOBALS['gDb']->queryPrepared($sql, array($GLOBALS['gCurrentOrgId']));

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
            if (substr_count($data['rolle'], $pPreferences->config['Altersrollen']['altersrollen_token'][$altkey]) === 4)
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
        if ($rollen[$key]['rollentyp'] == '')
        {
            $rollen[$key]['rollentyp'] = 'fix';
        }
    }

    // jetzt sind alle Familienrollen, Altersrollen und Fixrollen markiert
    // alle nicht benoetigten Rollen loeschen
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
            if (is_array($with_members) && count($with_members) > 0)
            {
                $rollen[$key]['members'] = list_members($with_members, array($data['rolle'] => 0));
            }
        }
    }
    return $rollen;
}

/**
 * Liest alle Mitglieder von Rollen einer oder mehrerer Kategorie(n) ein.
 * Die cat_ids der einzulesenden Kategorien werden direkt aus der $config_ini gelesen
 * @return  array $members   Array mit den user_ids der Mitglieder
 */
function bezugskategorie_einlesen()
{
    global $pPreferences;

    $members = array();

    // Hinweis: die Ueberpruefung, ob $config_ini['Rollenpruefung']['bezugskategorie']
    // befuellt und ein Array ist, ist in der aufrufenden Routine erfolgt

    $sql = 'SELECT DISTINCT mem_usr_id
            FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ' ';

    $firstpass = true;
    foreach ($pPreferences->config['Rollenpruefung']['bezugskategorie'] as $cat => $cat_id)
    {
        if($cat_id == ' ')
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

        $sql .= 'cat_id = '.$cat_id.' ';
        $sql .= ' AND mem_rol_id = rol_id
                  AND rol_valid  = 1

                  AND mem_begin <= \''.DATE_NOW.'\'
                  AND mem_end >= \''.DATE_NOW.'\'

                  AND rol_cat_id = cat_id ';
        $sql .= ' ) ';
        $firstpass = false;
    }

    $sql .= ' AND (  cat_org_id = ? -- $GLOBALS[\'gCurrentOrgId\']
              OR cat_org_id IS NULL )
              ORDER BY mem_usr_id ASC ';

    $statement = $GLOBALS['gDb']->queryPrepared($sql, array($GLOBALS['gCurrentOrgId']));
    while ($row = $statement->fetch())
    {
       $members[] = $row['mem_usr_id'];
    }

    return $members;
}


/**
 * Diese Funktion liefert als Rueckgabe die usr_ids von Rollenangehoerigen.<br/>
 * moegliche Aufrufe:<br/>
 *         list_members(array('usf_name_intern1','usf_name_intern2'), array('Rollenname1' => Schalter aktiv/ehem) )<br/>
 *   oder  list_members(array('usf_name_intern1','usf_name_intern2'), 'Rollenname' )<br/>
 *   oder  list_members(array('usf_name_intern1','usf_name_intern2'), Schalter aktiv/ehem )<br/>
 *   oder  list_members(array('p1','p2'), Schalter aktiv/ehem )<br/>
 *
 * Schalter aktiv/ehem: 0 = aktive Mitglieder, 1 = ehemalige Mitglieder, ungleich 1 oder 0: alle Mitglieder <br/>
 *
 * Aufruf: z.B. list_members(array('FIRST_NAME','LAST_NAME'), array('Mitglied' => 0,'Administrator' => 0));
 *
 * @param   array               $fields  		Array mit usf_name_intern oder p+usfID, z.B. array('FIRST_NAME','p2')
 * @param   array/string/bool   $rols    		Array mit Rollen, z.B. <br/>
 *                                            		array('Rollenname1' => Schalter aktiv/ehem) )<br/>
 *                                       			oder 'Rollenname' <br/>
 *                                       			oder Schalter aktiv/ehem  <br/>
 * @param   string               $conditions  	SQL-String als zusaetzlicher Filter von $members, z.B. 'AND usd_usf_id = 78'
 * @return  array   $members
 */
function list_members($fields, $rols = array(), $conditions = '')
{
    global $gProfileFields;
    
    $members = array();
    $rowArray = array();
    $selectString = '';
    $joinString = '';
    $nameRow = '';
    $nameIntern = '';
    $inString = '';
    $timeString = '';
    $startString = 'SELECT DISTINCT mem_usr_id ';
    $mainString = '';
    $addString = '';
    $sql = '';
    
    foreach ($fields as $field => $data)
    {
        $nameRow = $data;
        $nameIntern = $data;
        
        if (substr($data, 0 ,1) == 'p')
        {
            $usfID= substr($data, 1);
            $nameRow = $usfID;
            $nameIntern = $gProfileFields->getPropertyById($usfID, 'usf_name_intern');
            
            if ($nameIntern === '')         //prüfen, ob ein 'usf_name_intern' mit der angegebenen $usfID existiert; wenn nicht, zum nächsten Feld 
            {
                continue;
            }
        }
        
        $rowArray[] = $nameRow;
        $selectString .= ', '.$nameIntern.'.usd_value AS \''.$nameRow.'\'';
        
        $joinString .= 'LEFT JOIN '.TBL_USER_DATA.' AS '.$nameIntern.'
                               ON '.$nameIntern.'.usd_usr_id = mem_usr_id
                              AND '.$nameIntern.'.usd_usf_id = '. $gProfileFields->getProperty($nameIntern, 'usf_id'). '  ';
    }
    
    $mainString .= $selectString;
    $mainString .= ' FROM '. TBL_MEMBERS. ' ';
    $mainString .= $joinString;
    
    $inString .= 'WHERE mem_usr_id IN (SELECT DISTINCT mem_usr_id
                   FROM '. TBL_MEMBERS. ', '. TBL_ROLES. ', '. TBL_CATEGORIES. ' ';
    
    if (is_string($rols))
    {
        $inString .= ' WHERE mem_rol_id = '.getRoleId($rols).' AND ';
    }
    elseif (is_int($rols) && ($rols == 0))
    {
        // aktive Mitglieder
        $inString .= ' WHERE mem_begin <= \''.DATE_NOW.'\' ';
        $inString .= ' AND mem_end >= \''.DATE_NOW.'\' AND ';
        
    }
    elseif (is_int($rols) && ($rols == 1))
    {
        // nicht-aktive Mitglieder (ehemalige Mitglieder)
        $inString .= ' WHERE ( (mem_begin > \''.DATE_NOW.'\') OR (mem_end < \''.DATE_NOW.'\') ) AND ';
    }
    elseif (is_int($rols) && ($rols > 1 || $rols < 0))
    {
        // alle Mitglieder (aktiv und nicht-aktiv)
        $inString .= ' WHERE ';
    } 
    elseif (is_array($rols))
    {
        if (sizeof($rols) == 1)
        {
            $timeString = ', mem_begin, mem_end ';
            $rowArray[] = 'mem_begin';
            $rowArray[] = 'mem_end';
            
            reset($rols);                           // nur zur Sicherheit, falls eine Funktion vorher den Array-Zeiger verändert hat
            $roleKey = key($rols);
            $roleValue = current($rols);
       
            $addString .= ' ) AND mem_rol_id = '.getRoleId($roleKey).' ';
            if ($roleValue == 0)
            {
                // aktive Mitglieder
                $addString .= ' AND mem_begin <= \''.DATE_NOW.'\' ';
                $addString .= ' AND mem_end >= \''.DATE_NOW.'\'  ';
            }
            elseif ($roleValue == 1)
            {
                // nicht aktive Mitglieder (ehemalige Mitglieder)
                $addString .= ' AND ( (mem_begin > \''.DATE_NOW.'\') OR (mem_end < \''.DATE_NOW.'\') ) ';
            }
            $inString .= ' WHERE (';
        }
        else
        {
            $firstpass = true;
            foreach ($rols as $rol => $rol_switch)
            {
                if ($firstpass)
                {
                    $inString .= ' WHERE (( ';
                }
                else
                {
                    $inString .= ' OR ( ';
                }
                $inString .= 'mem_rol_id = '.getRoleId($rol).' ';
            
                if ($rol_switch == 0)
                {
                    // aktive Mitglieder
                    $inString .= ' AND mem_begin <= \''.DATE_NOW.'\' ';
                    $inString .= ' AND mem_end >= \''.DATE_NOW.'\' ';
                }
                elseif ($rol_switch == 1)
                {
                    // nicht aktive Mitglieder  (ehemalige Mitglieder)
                    $inString .= ' AND ( (mem_begin > \''.DATE_NOW.'\') OR (mem_end < \''.DATE_NOW.'\') )';
                }
                $inString .= ' ) ';
                $firstpass = false;
            }
            $inString .= ' ) AND ';
        }
    }
    
    $inString .= '  mem_rol_id = rol_id
                   AND rol_valid  = 1
                   AND rol_cat_id = cat_id
                   AND ( cat_org_id = '. $GLOBALS['gCurrentOrgId']. '
                    OR cat_org_id IS NULL ) ) ';
    
    $mainString .= $inString;
    
    $sql .= $startString.$timeString.$mainString.$addString.$conditions;
    $sql .= ' ORDER BY mem_usr_id ASC ';
    
    $statement = $GLOBALS['gDb']->queryPrepared($sql);
    while ($row = $statement->fetch())
    {
        $members[$row['mem_usr_id']] = array();
        foreach ($rowArray as $key)
        {
            $members[$row['mem_usr_id']][$key] = $row[$key];
        }
    }
    return $members;
}

/**
 * Callbackfunktion fuer array_filter ,
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
 * Funktion liest die Rollen-ID einer Rolle aus
 * @param   string  $role_name Name der zu pruefenden Rolle
 * @return  int     rol_id  Rol_id der Rolle, 0, wenn nicht gefunden
 */
function getRoleId($role_name)
{
    $sql = 'SELECT rol_id
              FROM '. TBL_ROLES. ', '. TBL_CATEGORIES. '
             WHERE rol_name  = ? -- $role_name
               AND rol_valid  = 1
               AND rol_cat_id = cat_id
               AND ( cat_org_id = ? -- $GLOBALS[\'gCurrentOrgId\']
                OR cat_org_id IS NULL ) ';

    $queryParams = array(
	   $role_name,
	   $GLOBALS['gCurrentOrgId']);
       
	$statement = $GLOBALS['gDb']->queryPrepared($sql, $queryParams);
                    
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
    if($my_rol_cost_period == -1)
    {
        return $GLOBALS['gL10n']->get('ROL_UNIQUELY');
    }
    elseif($my_rol_cost_period == 1)
    {
        return $GLOBALS['gL10n']->get('ROL_ANNUALLY');
    }
    elseif($my_rol_cost_period == 2)
    {
        return $GLOBALS['gL10n']->get('ROL_SEMIYEARLY');
    }
    elseif($my_rol_cost_period == 4)
    {
        return $GLOBALS['gL10n']->get('ROL_QUARTERLY');
    }
    elseif($my_rol_cost_period == 12)
    {
        return $GLOBALS['gL10n']->get('ROL_MONTHLY');
    }
    else
    {
        return '--';
    }
}

/**
 * Erzeugt Array mit Daten fuer die Analyse
 * @return  array $ret   Array im Format:<br/>
 *                       $ret['BEITRAG_kto']         = Gesamtsumme der Beitraege mit Kto-Verbindung<br/>
 *                       $ret['BEITRAG_kto_anzahl']  = Anzahl mit Kto-Verbindung<br/>
 *                       $ret['BEZAHLT_kto']         = Gesamtsumme mit Bezahlt und mit Kto-Verbindung<br/>
 *                       $ret['BEZAHLT_kto_anzahl']  = Anzahl mit Bezahlt und mit Kto-Verbindung<br/>
 *                       $ret['BEITRAG_rech']        = Gesamtsumme der Beitraege ohne Kto-Verbindung<br/>
 *                       $ret['BEITRAG_rech_anzahl'] = Anzahl ohne Kto-Verbindung<br/>
 *                       $ret['BEZAHLT_rech']        = Gesamtsumme mit Bezahlt und ohne Kto-Verbindung<br/>
 *                       $ret['BEZAHLT_rech_anzahl'] = Anzahl mit Bezahlt und ohne Kto-Verbindung
 */
function analyse_mem()
{
    $members = list_members(array('FEE'.$GLOBALS['gCurrentOrgId'], 'CONTRIBUTORY_TEXT'.$GLOBALS['gCurrentOrgId'], 'PAID'.$GLOBALS['gCurrentOrgId'], 'IBAN', 'DEBTOR'), 0);
    $ret = array('data' => $members, 'BEITRAG_kto' => 0, 'BEITRAG_kto_anzahl' => 0, 'BEITRAG_rech' => 0, 'BEITRAG_rech_anzahl' => 0, 'BEZAHLT_kto' => 0, 'BEZAHLT_kto_anzahl' => 0, 'BEZAHLT_rech' => 0, 'BEZAHLT_rech_anzahl' => 0);

    // alle Mitglieder durchlaufen und im ersten Schritt alle Mitglieder,
    // bei denen kein Beitrag berechnet wurde, herausfiltern
    foreach ($members as $member => $memberdata)
    {
        if (empty($memberdata['FEE'.$GLOBALS['gCurrentOrgId']]) )
        {
            unset($members[$member]);
        }
    }

    //jetzt wird gezaehlt
    foreach($members as $member => $memberdata)
    {
        if (!empty($memberdata['IBAN']))
        {
            $ret['BEITRAG_kto'] += $memberdata['FEE'.$GLOBALS['gCurrentOrgId']];
            $ret['BEITRAG_kto_anzahl'] += 1;
        }
        if ((!empty($memberdata['IBAN']))
            && !empty($memberdata['PAID'.$GLOBALS['gCurrentOrgId']]))
        {
            $ret['BEZAHLT_kto'] += $memberdata['FEE'.$GLOBALS['gCurrentOrgId']];
            $ret['BEZAHLT_kto_anzahl'] += 1;
        }
        if (empty($memberdata['IBAN']))
        {
            $ret['BEITRAG_rech'] += $memberdata['FEE'.$GLOBALS['gCurrentOrgId']];
            $ret['BEITRAG_rech_anzahl'] += 1;
        }
        if (empty($memberdata['IBAN'])
            && !empty($memberdata['PAID'.$GLOBALS['gCurrentOrgId']]))
        {
            $ret['BEZAHLT_rech'] += $memberdata['FEE'.$GLOBALS['gCurrentOrgId']];
            $ret['BEZAHLT_rech_anzahl'] += 1;
        }
    }
    return $ret;
}

/**
 * Erzeugt Array mit Daten fuer die Analyse
 * @return  array $ret
 */
function analyse_rol()
{
    global $pPreferences;

    $ret = beitragsrollen_einlesen('alt');
    $ret = array_merge($ret, beitragsrollen_einlesen('fix'));
    foreach ($ret as $rol => $roldata)
    {
        $ret[$rol]['members'] = list_members(array('FEE'.$GLOBALS['gCurrentOrgId'], 'PAID'.$GLOBALS['gCurrentOrgId']), array($roldata['rolle'] => 0));
    }

    $fam = beitragsrollen_einlesen('fam');

    foreach($pPreferences->config['Familienrollen']['familienrollen_prefix'] as $famkey => $famdata)
    {
        //wieviele Familienrollen mit diesem Praefix gibt es denn?
        //in der aufrufenden Funktion wird mittels sizeof abgefragt,
        //deshalb muss hier eine Array erzeugt werden
        $arr = array();
        foreach($fam as $key => $data)
        {
            if (substr($data['rolle'], 0, strlen($pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])) == $pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey])
            {
                $arr[] = $key;
            }
        }
        $ret[$pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey]] = array('rolle' => $GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLE').' '.$pPreferences->config['Familienrollen']['familienrollen_prefix'][$famkey], 'rol_cost' => $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$famkey], 'rol_cost_period' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$famkey], 'members' => $arr, 'rollentyp' => 'fam');

    }
    return $ret;
}

/**
 * Prueft die Rollenmitgliedschaften in altersgestaffelten Rollen
 * @return  array $ret
 */
function check_rollenmitgliedschaft_altersrolle()
{
    global $pPreferences, $gProfileFields;
    $ret = array();
    $alt = beitragsrollen_einlesen('alt', array('FIRST_NAME', 'LAST_NAME'));
    $user = new User($GLOBALS['gDb'], $gProfileFields);

    $check = array();
    foreach ($alt as $altrol => $altdata)
    {
        if (in_array($altdata['alterstyp'], $pPreferences->config['Rollenpruefung']['age_staggered_roles_exclusion']))
        {
            unset($alt[$altrol]);
            continue;
        }
        
        foreach($altdata['members'] as $member => $memberdata)
        {
            $check[$member]['alterstyp'][] = $altdata['alterstyp'];
            $check[$member]['FIRST_NAME'] = $memberdata['FIRST_NAME'];
            $check[$member]['LAST_NAME'] = $memberdata['LAST_NAME'];
        }
    }

    // jetzt $check durchlaufen und nur die Eintraege bearbeiten, bei denen mehr als ein Alterstyp vorhanden ist
    foreach($check as $member => $memberdata)
    {
        if(count($memberdata['alterstyp']) > 1)
        {
            $alterstypen = '';
            foreach($memberdata['alterstyp'] as $alterstyp)
            {
                $alterstypen .= ' ('.$alterstyp.')';
            }
            $user->readDataById($member);

            $ret[] .= '- <a href="'.SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME'].$alterstypen. '</a>';
        }
    }

    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prueft die Pflicht-Rollenmitgliedschaften
 * @return  array $ret
 */
function check_rollenmitgliedschaft_pflicht()
{
    global $pPreferences, $gProfileFields;
    $ret = array();
    $user = new User($GLOBALS['gDb'], $gProfileFields);


    // alle Beitragsrollen einlesen ('FIRST_NAME' wird zwar in der Funktion nicht benoetigt, ist aber notwendig,
    // damit die Rollenmitglieder eingelesen werden)
    $beitragsrollen = beitragsrollen_einlesen('', array('FIRST_NAME'));
    $members = list_members(array('FIRST_NAME', 'LAST_NAME'), 0);

    // alle Beitragsrollen durchlaufen und diejenigen Rollen loeschen, die nicht als Pflichtrolle definiert sind
    foreach ($beitragsrollen as $rol => $roldata)
    {
        //alle if und elseif koennte man in einer Zeile schreiben und mit || verknuepfen, aber so ist es uebersichtlicher
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
    if ($pPreferences->config['Rollenpruefung']['bezugskategorie'][0] != ' ')
    {
        // zuerst alle Member der Bezugskategorien einlesen
        $bezugskategorieMembers = bezugskategorie_einlesen();

        foreach ($members as $member => $memberdata)
        {
            // alle usr_ids loeschen, wenn sie nicht innerhalb der Bezugskategorie sind
            if (!in_array($member, $bezugskategorieMembers))
            {
                unset($members[$member]);
            }
        }
    }

    // alle Mitglieder durchlaufen und pruefen, ob sie in mind. einer Pflicht-Beitragsrolle sind
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
            $user->readDataById($member);
            $ret[] .= '- <a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
        }
    }

    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_DUTY_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prueft die Auschluss-Rollenmitgliedschaften
 * @return  array $ret
 */
function check_rollenmitgliedschaft_ausschluss()
{
    global $pPreferences, $gProfileFields;
    $ret = array();
    $user = new User($GLOBALS['gDb'], $gProfileFields);

    // alle Beitragsrollen einlesen ('FIRST_NAME' wird zwar in der Funktion nicht benoetigt, ist aber notwendig,
    // damit die Rollenmitglieder eingelesen werden)
    $beitragsrollen = beitragsrollen_einlesen('', array('FIRST_NAME'));
    $members = list_members(array('FIRST_NAME', 'LAST_NAME'), 0);

    // alle Beitragsrollen durchlaufen und fuer jedes Mitglied seine Rollenzugehoerigkeiten bestimmen
    foreach ($beitragsrollen as $rol => $roldata)
    {
        foreach ($roldata['members'] as $key => $dummy)
        {
            if (!isset($members[$key]['rollen']))
            {
                $members[$key]['rollen'] = array();
            }

            if($roldata['rollentyp'] == 'alt')
            {
                $members[$key]['rollen'][] = $roldata['alterstyp'].'alt';
            }
            elseif($roldata['rollentyp'] == 'fam')
            {
                $members[$key]['rollen'][] = 'fam';
            }
            else
            {
                $members[$key]['rollen'][] = $rol;
            }
        }
    }

    // Feature-Wunsch von joesch
    if ($pPreferences->config['Rollenpruefung']['bezugskategorie'][0] != ' ')
    {
        // zuerst alle Member der Bezugskategorien einlesen
        $bezugskategorieMembers = bezugskategorie_einlesen();

        foreach ($members as $member => $memberdata)
        {
            // alle usr_ids loeschen, wenn sie nicht innerhalb der Bezugskategorie sind
            if (!in_array($member, $bezugskategorieMembers))
            {
                unset($members[$member]);
            }
        }
    }

    // alle Mitglieder durchlaufen und pruefen, ob sie in Ausschluss-Beitragsrollen sind
    foreach ($members as $member => $memberdata)
    {
        //falls das Mitglied kein Angehoeriger einer Beitragsrolle ist: abbrechen und zum naechsten Datensatz gehen
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
                    if ((in_array(substr($roldata, strlen($token)), $memberdata['rollen'])) && (in_array(substr($roldata, 0,strlen($token)).'alt', $memberdata['rollen'])))
                    {
                        $marker = true;
                    }
                }
            }
        }
        if (is_array($pPreferences->config['Rollenpruefung']['fixrollenfixrollen']))
        {
        	foreach ($pPreferences->config['Rollenpruefung']['fixrollenfixrollen'] as $roldata)
        	{
        		$fixRols = explode('_', $roldata);
        		if ((in_array($fixRols[0], $memberdata['rollen'])) && (in_array($fixRols[1], $memberdata['rollen'])))
        		{
        			$marker = true;
        		}
        	}
        }

        if ($marker)
        {
            $user->readDataById($member);
            $ret[] .= '- <a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
        }
    }

    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ROLE_MEMBERSHIP_EXCLUSION_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Vergleichsfunktion erforderlich fuer usort()
 * @param  $wert_a
 * @param  $wert_b
 * @return int 0, 1 oder -1
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
 * Prueft die altersgestaffelten Rollen auf Luecken bzw Ueberschneidungen
 * @return  array $ret
 */
function check_rols()
{
    global $pPreferences;
    $ret = array();
    $alt = beitragsrollen_einlesen('alt', array('LAST_NAME'));

    foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $tokenkey => $tokendata)
    {
        $check = array();
        foreach ($alt as $altrol => $altdata)
        {
            if ($altdata['alterstyp'] == $tokendata)
            {
                $check[]  = array('year' => $altdata['von'], 'rol' => $altrol);
                $check[]  = array('year' => $altdata['bis'], 'rol' => $altrol);
            }
        }

        usort($check, 'vergleich');

        for ($i = 0; $i < count($check)-1; $i = $i+2)
        {
        if ($check[$i]['rol'] != $check[$i+1]['rol'])
            {
                $ret[$check[$i]['rol']] = '- '.$alt[$check[$i]['rol']]['rolle'];
                $ret[$check[$i+1]['rol']] = '- '.$alt[$check[$i+1]['rol']]['rolle'];
            }
            if (($i < count($check)-2) && ($check[$i+1]['year'] != ($check[$i+2]['year'])-1))
            {
                $ret[$check[$i+1]['rol']] = '- '.$alt[$check[$i+1]['rol']]['rolle'];
                $ret[$check[$i+2]['rol']] = '- '.$alt[$check[$i+2]['rol']]['rolle'];
            }
        }
    }

    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prueft, ob Angehoerige von Familienrollen die Pruefbedingungen erfuellen
 * @return  array $ret
 */
function check_family_roles()
{
    global $pPreferences;
    $ret = array();
    $ret_error = array();
    $temp_arr  = array();
    $temp_arr2 = array();
    $ret_marker = false;
    $fam = beitragsrollen_einlesen('fam', array('LAST_NAME', 'FIRST_NAME', 'BIRTHDAY'));
    $check = $pPreferences->config['Familienrollen'];
    $role = new TableRoles($GLOBALS['gDb']);


    // alle Pruefbedingungen einlesen
    foreach($check['familienrollen_prefix'] as $key => $prefix)
    {
        $temp_arr = explode(';', $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key]);
        foreach ($temp_arr as $keybed => $bedingung)
        {
            // den Doppelpunkt in der Pruefbedingung ersetzen
            // eine Pruefbedingung koennte deshalb auch in folgender Syntax geschrieben werden: von*bis*Anzahl
            $bedingung = str_replace(':', '*', $bedingung);

            $temp_arr2 = explode('*', $bedingung);

            // pruefen auf unsinnige Bedingungen
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

        // Meldung bei fehlerhaften Pruefbedingungen
        if($ret_marker && strlen($pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key]) > 0)
        {
            $ret_error[] = '<small>'.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_CONDITION').' '.$pPreferences->config['Familienrollen']['familienrollen_pruefung'][$key].' ('.$prefix.') '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_INVALID').'.</small>';
        }
        $ret_marker = false;
    }

    // Leerzeile einfuegen
    if (count($ret_error) !== 0)
    {
        $ret_error[] = '';
    }

    unset($bedingung, $keybed, $temp_arr, $temp_arr2, $ret_marker);

    // alle Pruefbedingungen durchlaufen
    foreach($check['familienrollen_prefix'] as $key => $prefix)
    {
        // alle Familienrollen durchlaufen
        foreach($fam as $famkey => $famdata)
        {
            if ($famdata['familientyp'] == $prefix)
            {
                $ret_temp = array();

                // alle Pruefungsbedingungen durchlaufen
                foreach ($check['pruefungsbedingungen'][$key] as $pruefkey => $pruefdata)
                {
                    $counter = 0;
                    // alle Mitglieder durchlaufen
                    foreach($famdata['members'] as $memberID => $memberdata)
                    {
                        // das Alter des Mitglieds am Stichtag bestimmen
                        $deadline = getDeadline($pPreferences->config['Altersrollen']['altersrollen_offset']);
                        $age = ageCalculator(strtotime($memberdata['BIRTHDAY']), strtotime($deadline));

                        // passt das Alter zu einer der Pruefbedingungen?
                        if ($age >= $pruefdata['von'] && $age <= $pruefdata['bis'])
                        {
                            $counter++;
                        }
                    }

                    if ($counter != $pruefdata['anz'])
                    {
                        $ret_temp[] = '&#160&#160&#160&#183<small>'.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_CONDITION').' '.$pruefdata['von'].'*'.$pruefdata['bis'].':'.$pruefdata['anz'].' '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_NOT_SATISFIED').'.</small>';
                    }
                }
                if (count($ret_temp) !== 0)
                {
                    $test = $role->readDataById($famkey);
                    $ret[] = '- <a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/groups_roles_new.php', array('role_uuid' => $role->getValue('rol_uuid'))). '">'.$famdata['rolle']. '</a>
                        <a class="admidio-icon-link" href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/groups-roles/lists_show.php', array('mode' => 'html', 'rol_ids' => $famkey)). '">
                            <i class="fas fa-users" alt="'.$GLOBALS['gL10n']->get('SYS_SHOW_MEMBER_LIST').'" title="'.$GLOBALS['gL10n']->get('SYS_SHOW_MEMBER_LIST').'"></i>
                        </a>';

                    $ret = array_merge($ret, $ret_temp);
                }
            }
        }
    }

    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES_ROLE_TEST_RESULT_ERROR').'</strong>';
    }

    // eine evtl. vorhandene Fehlermeldung davorsetzen
    if (count($ret_error) !== 0)
    {
        $ret = array_merge($ret_error, $ret);
    }
    return $ret;
}

/**
 * Prueft, ob Mandats-ID und Mandatsdatum vorhanden sind
 * (Voraussetzung: IBAN und Mitgliedsbeitrag dürfen nicht leer)
 * @return  array $ret
 */
function check_mandate_management()
{
    global $gProfileFields;
    $ret = array();
    $user = new User($GLOBALS['gDb'], $gProfileFields);

    $members = list_members(array('FIRST_NAME', 'LAST_NAME', 'IBAN', 'FEE'.$GLOBALS['gCurrentOrgId'], 'MANDATEID'.$GLOBALS['gCurrentOrgId'], 'MANDATEDATE'.$GLOBALS['gCurrentOrgId']), 0);

    foreach ($members as $member => $memberdata)
    {
        if ((strlen($memberdata['IBAN']) !== 0) && (strlen($memberdata['FEE'.$GLOBALS['gCurrentOrgId']]) !== 0) && ((strlen($memberdata['MANDATEID'.$GLOBALS['gCurrentOrgId']]) === 0)  || (strlen($memberdata['MANDATEDATE'.$GLOBALS['gCurrentOrgId']]) === 0)))
        {
            $user->readDataById($member);
            $ret[] = '- <a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
        }
    }

    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_MANDATE_MANAGEMENT_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prueft, ob bei Angabe eines Kontoinhabers alle erforderlichen Daten (Strasse, Ort...) vorhanden sind
 * @return  array $ret
 */
function check_account_details()
{
    global $gProfileFields;
    $ret = array();
    $user = new User($GLOBALS['gDb'], $gProfileFields);
    
    $members = list_members(array('FIRST_NAME', 'LAST_NAME', 'DEBTOR', 'DEBTOR_POSTCODE', 'DEBTOR_CITY', 'DEBTOR_STREET'), 0);
    
    foreach ($members as $member => $memberdata)
    {
        if ((strlen($memberdata['DEBTOR']) !== 0) && ((strlen($memberdata['DEBTOR_POSTCODE']) === 0) || (strlen($memberdata['DEBTOR_CITY']) === 0) || (strlen($memberdata['DEBTOR_STREET']) === 0)))
        {
            $user->readDataById($member);
            $ret[] = '- <a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
        }
    }
    
    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA_TEST_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA_TEST_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Durchlaeuft alle Mitglieder und prueft deren IBAN
 * @return  array $ret
 */
function check_iban()
{
    global $gProfileFields;
    $ret = array();
    $user = new User($GLOBALS['gDb'], $gProfileFields);

    $members = list_members(array('FIRST_NAME', 'LAST_NAME', 'IBAN'), 0);

    foreach ($members as $member => $memberdata)
    {
        if ((strlen($memberdata['IBAN']) === 1) || ((strlen($memberdata['IBAN']) > 1) && !test_iban($memberdata['IBAN'])))
        {
            $user->readDataById($member);
            $ret[] = '- <a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
        }
    }

    if (count($ret) === 0)
    {
        $ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_IBANCHECK_RESULT_OK'));
    }
    else
    {
        $ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_IBANCHECK_RESULT_ERROR').'</strong>';
    }
    return $ret;
}

/**
 * Prueft die uebergebene IBAN
 * @param string $iban
 * @return  bool
 */
function test_iban($iban)
{    
    //von karelvh
    $iban = strtolower(str_replace(' ', '', $iban));
    $Countries = array('al'=>28,'ad'=>24,'at'=>20,'az'=>28,'bh'=>22,'be'=>16,'ba'=>20,'br'=>29,'bg'=>22,'cr'=>21,'hr'=>21,'cy'=>28,'cz'=>24,'dk'=>18,'do'=>28,'ee'=>20,'fo'=>18,'fi'=>18,'fr'=>27,'ge'=>22,'de'=>22,'gi'=>23,'gr'=>27,'gl'=>18,'gt'=>28,'hu'=>28,'is'=>26,'ie'=>22,'il'=>23,'it'=>27,'jo'=>30,'kz'=>20,'kw'=>30,'lv'=>21,'lb'=>28,'li'=>21,'lt'=>20,'lu'=>20,'mk'=>19,'mt'=>31,'mr'=>27,'mu'=>30,'mc'=>27,'md'=>24,'me'=>22,'nl'=>18,'no'=>15,'pk'=>24,'ps'=>29,'pl'=>28,'pt'=>25,'qa'=>29,'ro'=>24,'sm'=>27,'sa'=>24,'rs'=>22,'sk'=>24,'si'=>19,'es'=>24,'se'=>24,'ch'=>21,'tn'=>24,'tr'=>26,'ae'=>23,'gb'=>22,'vg'=>24);
    $Chars = array('a'=>10,'b'=>11,'c'=>12,'d'=>13,'e'=>14,'f'=>15,'g'=>16,'h'=>17,'i'=>18,'j'=>19,'k'=>20,'l'=>21,'m'=>22,'n'=>23,'o'=>24,'p'=>25,'q'=>26,'r'=>27,'s'=>28,'t'=>29,'u'=>30,'v'=>31,'w'=>32,'x'=>33,'y'=>34,'z'=>35);
    
    if (array_key_exists(substr($iban, 0, 2), $Countries) && strlen($iban) == $Countries[substr($iban, 0, 2)])
    {
    	$MovedChar = substr($iban, 4).substr($iban, 0, 4);
    	$MovedCharArray = str_split($MovedChar);
    	$NewString = "";
    
    	foreach ($MovedCharArray AS $key => $value)
    	{
    		if (!is_numeric($MovedCharArray[$key]))
    		{
    			$MovedCharArray[$key] = $Chars[$MovedCharArray[$key]];
    		}
    		$NewString .= $MovedCharArray[$key];
    	}
    
    	if (bcmod($NewString, '97') == 1)
    	{
    		return TRUE;
    	}
    	else
    	{
    		return FALSE;
    	}
    }
    else
    {
    	return FALSE;
    }
}

/**
 * Durchlaeuft alle Mitglieder und prueft ob ein BIC vorhanden ist, falls das Mitglied aus 
 * einem Land außerhalb EU/EWR stammt
 * Prueft die Kontodaten des Vereins ob ein BIC vorhanden ist, falls der Verein 
 * aus einem Land außerhalb EU/EWR stammt
 * @return  array $ret
 */
function check_bic()
{
	global $pPreferences, $gProfileFields;
	$ret = array();
    $user = new User($GLOBALS['gDb'], $gProfileFields);
	
	$members = list_members(array('FIRST_NAME', 'LAST_NAME', 'IBAN', 'BIC'), 0);
	
	foreach ($members as $member => $memberdata)
	{
		if (isIbanNOT_EU_EWR($memberdata['IBAN']) && empty($memberdata['BIC']))
		{
            $user->readDataById($member); 
			$ret[] = '- <a href="'. SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array('user_uuid' => $user->getValue('usr_uuid'))). '">'.$memberdata['LAST_NAME'].', '.$memberdata['FIRST_NAME']. '</a>';
		}
	}

	if (count($ret) === 0)
	{
		$ret = array($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_BICCHECK_RESULT_OK'));
	}
	else
	{
		if (isIbanNOT_EU_EWR($pPreferences->config['Kontodaten']['iban']) && empty($pPreferences->config['Kontodaten']['bic']))
		{
			$ret[] = '- '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_DATA').' '.$GLOBALS['gCurrentOrganization']->getValue('org_longname');
		}
		$ret[] = '<br/><strong>=> '.$GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_BICCHECK_RESULT_ERROR').'</strong>';
	}
	return $ret;
}

/**
 * Prueft, ob die uebergebene IBAN zu einem Land außerhalb EU/EWR gehoert
 * @param string $iban
 * @return  bool
 */
function isIbanNOT_EU_EWR($iban)
{
	$iban_land = strtoupper(substr(str_replace(' ', '', $iban), 0,2));                  
	
	$countries = array( 'CH',			//Schweiz
						'MC', 			//Monaco
						'SM', 			//San Marino
						'JE', 			//Jersey
						'GG', 			//Guernsey
						'IM',			//Isle of Man
//						'GB',			//Großbritannien (je nach Brexit-Vereinbarung)
						'PM' );			//St. Pierre und Miquelon
	
	if (in_array($iban_land, $countries))
	{
		return true;
	}
	else 
	{
		return false;
	}
}


/**
 * Funktion prueft, ob der Nutzer berechtigt ist das Plugin aufzurufen.
 * Zur Prüfung werden die Einstellungen von 'Modulrechte' und 'Sichtbar für'
 * verwendet, die im Modul Menü für dieses Plugin gesetzt wurden.
 * @param   string  $scriptName   Der Scriptname des Plugins
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorized($scriptName)
{
	global $gMessage;
	
	$userIsAuthorized = false;
	$menId = 0;
	
	$sql = 'SELECT men_id
              FROM '.TBL_MENU.'
             WHERE men_url = ? -- $scriptName ';
	
	$menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($scriptName));
	
	if ( $menuStatement->rowCount() === 0 || $menuStatement->rowCount() > 1)
	{
		$GLOBALS['gLogger']->notice('MembershipFee: Error with menu entry: Found rows: '. $menuStatement->rowCount() );
		$GLOBALS['gLogger']->notice('MembershipFee: Error with menu entry: ScriptName: '. $scriptName);
		$gMessage->show($GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_MENU_URL_ERROR', array($scriptName)), $GLOBALS['gL10n']->get('SYS_ERROR'));
	}
	else
	{
		while ($row = $menuStatement->fetch())
		{
			$menId = (int) $row['men_id'];
		}
	}
	
	$sql = 'SELECT men_id, men_com_id, com_name_intern
              FROM '.TBL_MENU.'
         LEFT JOIN '.TBL_COMPONENTS.'
                ON com_id = men_com_id
             WHERE men_id = ? -- $menId
          ORDER BY men_men_id_parent DESC, men_order';
	
	$menuStatement = $GLOBALS['gDb']->queryPrepared($sql, array($menId));
	while ($row = $menuStatement->fetch())
	{
		if ((int) $row['men_com_id'] === 0 || Component::isVisible($row['com_name_intern']))
		{
			// Read current roles rights of the menu
			$displayMenu = new RolesRights($GLOBALS['gDb'], 'menu_view', $row['men_id']);
			$rolesDisplayRight = $displayMenu->getRolesIds();
			
			// check for right to show the menu
			if (count($rolesDisplayRight) === 0 || $displayMenu->hasRight($GLOBALS['gCurrentUser']->getRoleMemberships()))
			{
				$userIsAuthorized = true;
			}
		}
	}
	return $userIsAuthorized;
}

/**
 * Funktion prueft, ob der Nutzer berechtigt ist, das Modul Preferences aufzurufen.
 * @param   none
 * @return  bool    true, wenn der User berechtigt ist
 */
function isUserAuthorizedForPreferences()
{
    global $pPreferences;
    
    $userIsAuthorized = false;
    
    if ($GLOBALS['gCurrentUser']->isAdministrator())                   // Mitglieder der Rolle Administrator dürfen "Preferences" immer aufrufen
    {
        $userIsAuthorized = true;
    }
    else
    {
        foreach ($pPreferences->config['access']['preferences'] as $roleId)
        {
            if ($GLOBALS['gCurrentUser']->isMemberOfRole((int) $roleId))
            {
                $userIsAuthorized = true;
                continue;
            }
        }
    }
    
    return $userIsAuthorized;
}

/**
 * Formatiert den uebergebenen Datumsstring fuer MySQL,
 * date_format2mysql ersetzt date_german2mysql (erstellt von eiseli)
 * @param   string  $date       Datumsstring
 * @return  date                Datum im Format Y-m-d
 */
function date_format2mysql($date)
{
    return date('Y-m-d', strtotime($date));
}

/**
 * Generates a new reference time for the division into age-based roles.
 * @param   int      $altersrollen_offset     The monthly offset for the reference time
 * @return  string   The new reference time (deadline) in Y-m-d format
 */
function getDeadline($altersrollen_offset)
{
    $dateTime = \DateTime::createFromFormat('Y-m-d', date('Y').'-1-1');
    
    $dayOffset = new \DateInterval('P1D');
    $monthOffset = new \DateInterval('P'. abs($altersrollen_offset).'M');
    
    if ($altersrollen_offset < 0)
    {
        $monthOffset->invert = 1;
    }
        
    $dateTime->add($monthOffset);
    $dateTime->sub($dayOffset);
    
    return $dateTime->format('Y-m-d');
}

/**
 * Berechnet das Alter an einem bestimmten Tag (Stichtag)
 * @param   date  $geburtstag     Datum des Geburtstages
 * @param   date  $stichtag       Datum des Stichtages
 * @return  int                   Das Alter in Jahren
 */
function ageCalculator($geburtstag, $stichtag)
{
    $day = date('d', $geburtstag);
    $month = date('m', $geburtstag);
    $year = date('Y', $geburtstag);

    $cur_day = date('d', $stichtag);
    $cur_month = date('m', $stichtag);
    $cur_year = date('Y', $stichtag);

    $calc_year = $cur_year - $year;

    if($month > $cur_month)
        return $calc_year - 1;
    elseif ($month == $cur_month && $day > $cur_day)
        return $calc_year - 1;
     else
        return $calc_year;
}


/**
 * Callbackfunktion fuer array_filter
 * @param   string  $wert
 * @return  bool    true, wenn Beitrag  != NULL ist
 */
function delete_without_BEITRAG ($wert)
{
    return  $wert['FEE'.$GLOBALS['gCurrentOrgId']] != NULL;
}

/**
 * Callbackfunktion fuer array_filter
 * @param   string  $wert
 * @return  bool    true, wenn IBAN  != NULL ist
 */
function delete_without_IBAN ($wert)
{
    return  $wert['IBAN'] != NULL;
}

/**
 * Callbackfunktion fuer array_filter
 * @param   string  $wert
 * @return  bool    true, wenn BIC  != NULL ist
 */
function delete_without_BIC ($wert)
{
    return  $wert['BIC'] != NULL;
}

/**
 * Callbackfunktion fuer array_filter
 * @param   string  $wert
 * @return  bool    true, wenn MandateID  == NULL ist
 */
function delete_with_MANDATEID ($wert)
{
    return !($wert['MANDATEID'.$GLOBALS['gCurrentOrgId']] != NULL);
}

/**
 * Callbackfunktion fuer array_filter
 * @param   string  $wert
 * @return  bool    true, wenn Bezahlt  == NULL ist
 */
function delete_with_BEZAHLT ($wert)
{
    return !($wert['PAID'.$GLOBALS['gCurrentOrgId']] != NULL);
}

/**
 * Callbackfunktion fuer array_filter
 * @param   string  $wert
 * @return  bool    true, wenn MandateID  != NULL ist
 */
function delete_without_MANDATEID ($wert)
{
    return  $wert['MANDATEID'.$GLOBALS['gCurrentOrgId']] != NULL;
}

/**
 * Callbackfunktion fuer array_filter
 * @param   string  $wert
 * @return  bool    true, wenn MandateID  != NULL ist
 */
function delete_without_MANDATEDATE ($wert)
{
    return  $wert['MANDATEDATE'.$GLOBALS['gCurrentOrgId']] != NULL;
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
    $tmptext = str_replace('&uuml;', 'ue', $tmptext);
    $tmptext = str_replace('&auml;', 'ae', $tmptext);
    $tmptext = str_replace('&ouml;', 'oe', $tmptext);
    $tmptext = str_replace('&szlig;', 'ss', $tmptext);
    $tmptext = str_replace('&Uuml;', 'Ue', $tmptext);
    $tmptext = str_replace('&Auml;', 'Ae', $tmptext);
    $tmptext = str_replace('&Ouml;', 'Oe', $tmptext);
    return $tmptext;
}

/**
 * Ersetzt und entfernt unzulaessige Zeichen in der SEPA-XML-Datei
 * @param   string  $tmptext
 * @return  string  $ret
 */
function replace_sepadaten($tmptext)
{
/*
Zulaessige Zeichen
Fuer die Erstellung von SEPA-Nachrichten sind die folgenden Zeichen in der
Kodierung gemaess UTF-8 bzw. ISO-885933 zugelassen.
---------------------------------------------------
Zugelassener Zeichencode| Zeichen   | Hexcode
Numerische Zeichen      | 0 bis 9   | X'30' bis X'39'
Großbuchstaben          | A bis Z   | X'41' bis X'5A'
Kleinbuchstaben         | a bis z   | X'61' bis 'X'7A'
Apostroph               |  '        | X'27
Doppelpunkt             |  :        | X'3A
Fragezeichen            |  ?        | X'3F
Komma                   |  ,        | X'2C
Minus                   |  -        | X'2D
Leerzeichen             |           | X'20
Linke Klammer           |  (        | X'28
Pluszeichen             |  +        | X'2B
Punkt                   |  .        | X'2E
Rechte Klammer          |  )        | X'29
Schraegstrich           |  /        | X'2F
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
    	'*' => '.',
    	'$' => '.',
    	'%' => '.',
        '&' => '+');

    $ret = str_replace(array_keys($charMap), array_values($charMap), $tmptext);

    for ($i = 0; $i < strlen($ret); $i++)
    {
        if (preg_match('/[^A-Za-z0-9\'\:\?\,\-\(\+\.\)\/]/', substr($ret, $i, 1)))
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
    global $pPreferences;

    // now replace all parameters in email text
    $text = preg_replace('/#user_first_name#/', $user->getValue('FIRST_NAME'),  $text);
    $text = preg_replace('/#user_last_name#/',  $user->getValue('LAST_NAME'), $text);
    $text = preg_replace('/#street#/',  $user->getValue('STREET'), $text);
    $text = preg_replace('/#postcode#/', $user->getValue('POSTCODE'), $text);
    $text = preg_replace('/#city#/', $user->getValue('CITY'), $text);
    $text = preg_replace('/#email#/', $user->getValue('EMAIL'), $text);
    $text = preg_replace('/#phone#/', $user->getValue('PHONE'), $text);
    $text = preg_replace('/#mobile#/', $user->getValue('MOBILE'), $text);
    $text = preg_replace('/#birthday#/', $user->getValue('BIRTHDAY'), $text);
    $text = preg_replace('/#organization_long_name#/', $GLOBALS['gCurrentOrganization']->getValue('org_longname'), $text);
    $text = preg_replace('/#fee#/', $user->getValue('FEE'.$GLOBALS['gCurrentOrgId']),   $text);
    $text = preg_replace('/#due_day#/', $user->getValue('DUEDATE'.$GLOBALS['gCurrentOrgId']),  $text);
    $text = preg_replace('/#mandate_id#/', $user->getValue('MANDATEID'.$GLOBALS['gCurrentOrgId']), $text);
    $text = preg_replace('/#mandate_date#/', $user->getValue('MANDATEDATE'.$GLOBALS['gCurrentOrgId']),   $text);
    $text = preg_replace('/#creditor_id#/', $pPreferences->config['Kontodaten']['ci'], $text);
    $text = preg_replace('/#iban#/', $user->getValue('IBAN'), $text);
    $text = preg_replace('/#bic#/', $user->getValue('BIC'), $text);
    $text = preg_replace('/#bank#/', $user->getValue('BANK'), $text);
    if ($user->getValue('DEBTOR') <> '')
    {
        $text = preg_replace('/#debtor#/', $user->getValue('DEBTOR'), $text);
    }
    else 
    {
        $text = preg_replace('/#debtor#/', $user->getValue('FIRST_NAME'). ' '. $user->getValue('LAST_NAME'), $text);
    }
    $text = preg_replace('/#membership_fee_text#/', $user->getValue('CONTRIBUTORY_TEXT'.$GLOBALS['gCurrentOrgId']),   $text);
    $text = preg_replace('/#iban_obfuscated#/', obfuscate_iban($user->getValue('IBAN')), $text);

    return $text;
}

/**
 * Wandelt Rollentyp von Kurzform in Langform um
 * @param   string  $rollentyp  Rollentyp in Kurzform ('fix' oder 'fam')
 * @return  string  $ret        Rollentyp in Langform (z.B. 'Familienrollen')
 */
function expand_rollentyp($rollentyp = '')
{
    if ($rollentyp == 'fix')
    {
        $ret = $GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_OTHER_CONTRIBUTION_ROLES');
    }
    elseif($rollentyp == 'fam')
    {
        $ret = $GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_FAMILY_ROLES');
    }
    else             //==alt
    {
        $ret = $GLOBALS['gL10n']->get('PLG_MITGLIEDSBEITRAG_AGE_STAGGERED_ROLES');
    }
    return $ret;
}

/**
 * Verschleiert die IBAN für den Versand durch Aus-X-en der 8. bis 17. Ziffer
 * @param   string  $iban  IBAN des Users
 * @return  string         verschleierte IBAN (z.B. 'DE1234567xxxxxxxxxx123', 'DE12 3456 7xxx xxxx xxx1 23', ...)
 */
function obfuscate_iban($iban) {
	$pos = 0;
	return preg_replace_callback('/\d/', function($matches) use (&$pos) {
		$pos++;
		if($pos > 7)
		{
			return 'x';
		}
		return $matches[0];
	}, $iban, 17);
}

/**
 * Returns the value with a html link to the mail module
 * @param string $value           The value that should be formated
 * @param string $user_uuid       The user uuid
 * @param string $usf_uuid        The usf uuid
 * @return string The formated string 
 */
function getEmailLink($value, $user_uuid, $usf_uuid)
{
	$htmlValue = '';
	
	if (StringUtils::strValidCharacters($value, 'email'))
	{
	    if (!$GLOBALS['gSettingsManager']->getBool('enable_mail_module'))
		{
			$emailLink = 'mailto:' . $value;
		}
		else
		{
		    $emailLink = SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER .'/message_write.php', array('user_uuid' => $user_uuid, 'usf_uuid' => $usf_uuid));
		}
		if (strlen($value) > 30)
		{
			$htmlValue = '<a href="' . $emailLink . '" title="' . $value . '">' . substr($value, 0, 30) . '...</a>';
		}
		else
		{
			$htmlValue = '<a href="' . $emailLink . '" title="' . $value . '" style="overflow: visible; display: inline;">' . $value . '</a>';
		}
	}
	return $htmlValue;
}

/**
 * @param string $group
 * @param string $id
 * @param string $title
 * @param string $icon
 * @param string $body
 * @return string
 */
function getMenuePanel($group, $id, $parentId, $title, $icon, $body)
{
    $html = '
        <div class="card" id="panel_' . $id . '">
            <div class="card-header">
                <a type="button" data-toggle="collapse" data-target="#collapse_' . $id . '">
                    <i class="' . $icon . ' fa-fw"></i>' . $title . '
                </a>
            </div>
            <div id="collapse_' . $id . '" class="collapse" aria-labelledby="headingOne" data-parent="#' . $parentId . '">
                <div class="card-body">
                    ' . $body . '
                </div>
            </div>
        </div>
    ';
    return $html;
}

/**
 * @param string $group
 * @param string $id
 * @param string $title
 * @param string $icon
 * @return string
 */
function getMenuePanelHeaderOnly($group, $id, $parentId, $title, $icon)
{
    $html = '
        <div class="card" id="panel_' . $id . '">
            <div class="card-header">
                <a type="button" data-toggle="collapse" data-target="#collapse_' . $id . '">
                    <i class="' . $icon . ' fa-fw"></i>' . $title . '
                </a>
            </div>
            <div id="collapse_' . $id . '" class="collapse" aria-labelledby="headingOne" data-parent="#' . $parentId . '">
                <div class="card-body">
    ';
    return $html;
}

/**
 * @param none
 * @return string
 */
function getMenuePanelFooterOnly()
{
    return '</div></div></div>';
}

/**
 * @param string $group
 * @return string
 */
function openMenueTab($group, $parentId)
{
    $html = '
        <div class="tab-pane fade" id="tabs-' . $group . '" role="tabpanel">
            <div class="accordion" id="' . $parentId . '">
    ';
    return $html;
}

/**
 * @param none
 * @return string
 */
function closeMenueTab()
{
    return '</div></div>';
}

/**
 * Add a new groupbox to the page. This could be used to group some elements
 * together. There is also the option to set a headline to this group box.
 * @param string $id       Id the the groupbox.
 * @param string $headline (optional) A headline that will be shown to the user.
 * @param string $class    (optional) An additional css classname for the row. The class **admFieldRow**
 *                         is set as default and need not set with this parameter.
 */
function openGroupBox($id, $headline = null, $class = '')
{
    $html = '<div id="' . $id . '" class="card admidio-field-group ' . $class . '">';
    // add headline to groupbox
    if ($headline !== null)
    {
        $html .= '<div class="card-header">' . $headline . '</div>';
    }
    $html .= '<div class="card-body">';
    return $html;
}

/**
 * Close the groupbox that was created before.
 */
function closeGroupBox()
{
    return '</div></div>';
}

/**
 * Shows a test result and, depending an the size, a scroll bar
 * @param array $testResult       array with test result
 * @return string
 */
function showTestResultWithScrollbar($testResult)
{
    $size = sizeof($testResult);
    $html = '';
    
    if ($size > 8)
    {
        $html .= '<div style="width:100%; height:200px; overflow:auto; border:20px;">';
    }
    foreach ($testResult as $data)
    {
        $html .= $data.'<br />';
    }
    if ($size > 8)
    {
        $html .= '</div>';
    }
    return $html;
}


