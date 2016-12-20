<?php
/**
 ***********************************************************************************************
 * Class manages the configuration table
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 ***********************************************************************************************
 */

/******************************************************************************
 * Klasse verwaltet die Konfigurationstabelle "adm_plugin_preferences"
 *
 * Folgende Methoden stehen zur Verfügung:
 *
 * init()                       :   prüft, ob die Konfigurationstabelle existiert,
 *                                  legt sie ggf. an und befüllt sie mit Default-Werten
 * save()                       :   schreibt die Konfiguration in die Datenbank
 * read()                       :   liest die Konfigurationsdaten aus der Datenbank
 * checkforupdate()             :   vergleicht die Angaben in der Datei version.php
 *                                  mit den Daten in der DB
 * delete_config_data()         :   löscht Konfigurationsdaten in der Datenbank
 * delete_member_data           :   löscht Nutzerdaten in der Datenbank
 * delete_mail_data             :   löscht Mail-Texte  in der Datenbank
 *
 *****************************************************************************/

class ConfigTablePMB
{
    public $config = array();     ///< Array mit allen Konfigurationsdaten

    protected $table_name;
    protected static $shortcut =  'PMB';
    protected static $version;
    protected static $stand;
    protected static $dbtoken;

    public $config_default= array();

    /**
     * ConfigTablePMB constructor
     */
    public function __construct()
    {
        global  $gDb, $gCurrentOrganization, $g_tbl_praefix;

        require_once(__DIR__ . '/../version.php');
        include(__DIR__ . '/../configdata.php');

        $this->table_name = $g_tbl_praefix.'_plugin_preferences';

        if(isset($plugin_version))
        {
            self::$version = $plugin_version;
        }
        if(isset($plugin_stand))
        {
            self::$stand = $plugin_stand;
        }
        if(isset($dbtoken))
        {
            self::$dbtoken = $dbtoken;
        }
        $this->config_default = $config_default;
    }

    /**
     * Prüft, ob die Konfigurationstabelle existiert, legt sie ggf an und befüllt sie mit Standardwerten
     * @return void
     */
    public function init()
    {
        global $gL10n, $gDb, $gCurrentOrganization,$gProfileFields;

        $config_ist = array();

        // pruefen, ob es die Tabelle bereits gibt
        $sql = 'SHOW TABLES LIKE \''.$this->table_name.'\' ';
        $statement = $gDb->query($sql);

        // Tabelle anlegen, wenn es sie noch nicht gibt
        if (!$statement->rowCount())
        {
            // Tabelle ist nicht vorhanden --> anlegen
            $sql = 'CREATE TABLE '.$this->table_name.' (
                plp_id      integer     unsigned not null AUTO_INCREMENT,
                plp_org_id  integer     unsigned not null,
                plp_name    varchar(255) not null,
                plp_value   text,
                primary key (plp_id) )
                engine = InnoDB
                auto_increment = 1
                default character set = utf8
                collate = utf8_unicode_ci';
            $gDb->query($sql);
        }

        $this->read();

        // Update/Konvertierungsroutine 3.3.7 -> 4.0.0
        if (isset($this->config['Rollenpruefung']['bezugskategorie']) && $this->config['Rollenpruefung']['bezugskategorie'] == '')
        {
            $this->config['Rollenpruefung']['bezugskategorie'][0] = ' ';
        }
        //Update/Konvertierungsroutine 4.0.0 -> 4.1.0
        // seit 01.02.2016 gibt es keine Kontonummern mehr; sollen alle Kontonummern und Bankleitzahlen automatisch gelöscht werden,
        // so sind in den nächsten beiden Zeilen die führenden "//" zu entfernen
        //$this->delete_member_data(3,'KONTONUMMER');
        //$this->delete_member_data(3,'BANKLEITZAHL');

        // Hinweis: delete_member_data() wird auch im Modul Deinstallation verwendet
        // der zweite Parameter bestimmt das zu löschende Profilfeld
        // der erste Parameter definiert die Organistaion, in der gelöscht wird
        //  0 = Daten nur in aktueller Org löschen
        //  1 = Daten in allen Orgs löschen
        //  3 = Daten löschen, die in allen Orgs sichtbar sind (z.B. Stammdaten)

        //Update/Konvertierungsroutine 4.1.x -> 4.1.2
        if(isset($this->config['Rollenpruefung']['altersrollenfamilienrollen']) && !is_array($this->config['Rollenpruefung']['altersrollenfamilienrollen']))
        {
            unset($this->config['Rollenpruefung']['altersrollenfamilienrollen']);
        }
        if(isset($this->config['Rollenpruefung']['altersrollenpflicht']) && !is_array($this->config['Rollenpruefung']['altersrollenpflicht']))
        {
            unset($this->config['Rollenpruefung']['altersrollenpflicht']);
        }
        // Ende Update/Konvertierungsroutine

        $this->config['Plugininformationen']['version'] = self::$version;
        $this->config['Plugininformationen']['stand'] = self::$stand;

        // die eingelesenen Konfigurationsdaten in ein Arbeitsarray kopieren
        $config_ist = $this->config;

        // die Default-config durchlaufen
        foreach($this->config_default as $section => $sectiondata)
        {
            foreach($sectiondata as $key => $value)
            {
                // gibt es diese Sektion bereits in der config?
                if (isset($config_ist[$section][$key]))
                {
                    // wenn ja, diese Sektion in der Ist-config löschen
                    unset($config_ist[$section][$key]);
                }
                else
                {
                    // wenn nicht, diese Sektion in der config anlegen und mit den Standardwerten aus der Soll-config befüllen
                    $this->config[$section][$key]=$value;
                }
            }
            // leere Abschnitte (=leere Arrays) löschen
            if ((isset($config_ist[$section]) && count($config_ist[$section])==0))
            {
                unset($config_ist[$section]);
            }
        }

        // die Ist-config durchlaufen
        // jetzt befinden sich hier nur noch die DB-Einträge, die nicht verwendet werden und deshalb:
        // 1. in der DB gelöscht werden können
        // 2. in der normalen config gelöscht werden können
        foreach($config_ist as $section => $sectiondata)
        {
            foreach($sectiondata as $key => $value)
            {
                $plp_name = self::$shortcut.'__'.$section.'__'.$key;
                $sql = 'DELETE FROM '.$this->table_name.'
                        WHERE plp_name = \''.$plp_name.'\'
                        AND plp_org_id = '.$gCurrentOrganization->getValue('org_id').' ';
                $gDb->query($sql);
                unset($this->config[$section][$key]);
            }
            // leere Abschnitte (=leere Arrays) löschen
            if (count($this->config[$section])==0)
            {
                unset($this->config[$section]);
            }
        }

        // die aktualisierten und bereinigten Konfigurationsdaten in die DB schreiben
        $this->save();
    }

    /**
     * Schreibt die Konfigurationsdaten in die Datenbank
     * @return void
     */
    public function save()
    {
        global $gDb, $gCurrentOrganization;

        foreach($this->config as $section => $sectiondata)
        {
            foreach($sectiondata as $key => $value)
            {
                if (is_array($value))
                {
                    // um diesen Datensatz in der Datenbank als Array zu kennzeichnen, wird er von Doppelklammern eingeschlossen
                    $value = '(('.implode(self::$dbtoken, $value).'))';
                }

                $plp_name = self::$shortcut.'__'.$section.'__'.$key;

                $sql = ' SELECT plp_id
                        FROM '.$this->table_name.'
                        WHERE plp_name = \''.$plp_name.'\'
                        AND (  plp_org_id = '.$gCurrentOrganization->getValue('org_id').'
                        OR plp_org_id IS NULL ) ';
                $statement = $gDb->query($sql);
                $row = $statement->fetchObject();

                // Gibt es den Datensatz bereits?
                // wenn ja: UPDATE des bestehende Datensatzes
                if(isset($row->plp_id) && strlen($row->plp_id) > 0)
                {
                    $sql = 'UPDATE '.$this->table_name.'
                            SET plp_value = \''.$value.'\'
                            WHERE plp_id = '.$row->plp_id;

                    $gDb->query($sql);
                }
                // wenn nicht: INSERT eines neuen Datensatzes
                else
                {
                    $sql = 'INSERT INTO '.$this->table_name.' (plp_org_id, plp_name, plp_value)
                            VALUES (\''.$gCurrentOrganization->getValue('org_id').'\' ,\''.self::$shortcut.'__'.$section.'__'.$key.'\' ,\''.$value.'\')';
                    $gDb->query($sql);
                }
            }
        }
    }

    /**
     * Liest die Konfigurationsdaten aus der Datenbank
     * @return void
     */
    public function read()
    {
        global $gDb, $gCurrentOrganization;

        $sql = ' SELECT plp_id, plp_name, plp_value
                FROM '.$this->table_name.'
                WHERE plp_name LIKE \''.self::$shortcut.'__%\'
                AND (  plp_org_id = '.$gCurrentOrganization->getValue('org_id').'
                    OR plp_org_id IS NULL ) ';
        $statement = $gDb->query($sql);

        while($row = $statement->fetch())
        {
            $array = explode('__', $row['plp_name']);

            // wenn plp_value von ((  )) eingeschlossen ist, dann ist es als Array einzulesen
            if ((substr($row['plp_value'], 0, 2)=='((') && (substr($row['plp_value'], -2)=='))'))
            {
                $row['plp_value'] = substr($row['plp_value'], 2, -2);
                $this->config[$array[1]] [$array[2]] = explode(self::$dbtoken, $row['plp_value']);
            }
            else
            {
                $this->config[$array[1]] [$array[2]] = $row['plp_value'];
            }
        }
    }

    /**
     * Vergleicht die Daten in der version.php mit den Daten in der DB
     * @return int  $ret<br>
     *              0 = kein Update erforderlich<br>
     *              1 = Versionen von Stand und Datum sind unterschiedlich: Init-Routine durchlaufen<br>
     *              2 = Struktur der DB unterschiedlich: Install-Routine durchlaufen
     */
    public function checkforupdate()
    {
        global $gL10n, $gDb, $gCurrentOrganization,$gProfileFields;
        $ret = 0;

        // pruefen, ob es die Konfigurationstabelle gibt
        $sql = 'SHOW TABLES LIKE \''.$this->table_name.'\' ';
        $tableExistStatement = $gDb->query($sql);

        if ($tableExistStatement->rowCount())
        {
            $plp_name = self::$shortcut.'__Plugininformationen__version';

            $sql = 'SELECT plp_value
                    FROM '.$this->table_name.'
                    WHERE plp_name = \''.$plp_name.'\'
                    AND (  plp_org_id = '.$gCurrentOrganization->getValue('org_id').'
                        OR plp_org_id IS NULL ) ';
            $statement = $gDb->query($sql);
            $row = $statement->fetchObject();

            // Vergleich Version.php  ./. DB (hier: version)
            if(!isset($row->plp_value) || strlen($row->plp_value) == 0 || $row->plp_value!=self::$version)
            {
                $ret = 1;
            }

            $plp_name = self::$shortcut.'__Plugininformationen__stand';

            $sql = 'SELECT plp_value
                    FROM '.$this->table_name.'
                    WHERE plp_name = \''.$plp_name.'\'
                    AND (  plp_org_id = '.$gCurrentOrganization->getValue('org_id').'
                        OR plp_org_id IS NULL ) ';
            $statement = $gDb->query($sql);
            $row = $statement->fetchObject();

            // Vergleich Version.php  ./. DB (hier: stand)
            if(!isset($row->plp_value) || strlen($row->plp_value) == 0 || $row->plp_value!=self::$stand)
            {
                $ret = 1;
            }
        }
        else        // nein, Konfigurationstabelle fehlt komplett, deshalb Neuinstallation
        {
            $ret = 2;
        }

        // einen Suchstring für die SQL-Abfrage aufbereiten
        $fieldsarray = array();
        $fieldsarray[] = 'MEMBERNUMBER';
        $fieldsarray[] = 'ACCESSION'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'FEE'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'PAID'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'CONTRIBUTORY_TEXT'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'SEQUENCETYPE'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'DUEDATE'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'MANDATEID'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'MANDATEDATE'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'ORIG_MANDATEID'.$gCurrentOrganization->getValue('org_id');
        $fieldsarray[] = 'IBAN';
        $fieldsarray[] = 'BIC';
        $fieldsarray[] = 'BANK';
        $fieldsarray[] = 'DEBTOR';
        $fieldsarray[] = 'DEBTOR_ADDRESS';
        $fieldsarray[] = 'DEBTOR_POSTCODE';
        $fieldsarray[] = 'DEBTOR_CITY';
        $fieldsarray[] = 'DEBTOR_EMAIL';
        $fieldsarray[] = 'ORIG_DEBTOR_AGENT';
        $fieldsarray[] = 'ORIG_IBAN';

        $fieldsString ='';
        foreach ($fieldsarray as $string)
        {
            $fieldsString .="'".$string."',";
        }
        $fieldsString = substr($fieldsString, 0, -1);

        // pruefen, ob alle erforderlichen Profilfelder des Plugins vorhanden sind
        $sql = 'SELECT DISTINCT usf_id
                    FROM '.TBL_USER_FIELDS.' , '. TBL_CATEGORIES.  '
                    WHERE usf_name_intern IN ('.$fieldsString.')
                    AND (  cat_org_id = '. $gCurrentOrganization->getValue('org_id'). '
            OR cat_org_id IS NULL ) ';
        $statement = $gDb->query($sql);

        if($statement->rowCount() != count($fieldsarray))
        {
            $ret = 2;
        }

        return $ret;
    }

    /**
     * Löscht die Konfigurationsdaten in der Datenbank
     * @param   int     $deinst_org_select  0 = Daten nur in aktueller Org löschen, 1 = Daten in allen Org löschen
     * @return  string  $result             Meldung
     */
    public function delete_config_data($deinst_org_select)
    {
        global $gDb, $gCurrentOrganization,$gL10n;

        $result = '';
        $result_data=false;
        $result_db = false;

        if($deinst_org_select==0)                    //0 = Daten nur in aktueller Org löschen
        {
            $sql = 'DELETE FROM '.$this->table_name.'
                    WHERE plp_name LIKE \''.self::$shortcut.'__%\'
                    AND plp_org_id = '.$gCurrentOrganization->getValue('org_id').' ';
            $result_data = $gDb->query($sql);
        }
        elseif ($deinst_org_select==1)              //1 = Daten in allen Org löschen
        {
            $sql = 'DELETE FROM '.$this->table_name.'
                    WHERE plp_name LIKE \''.self::$shortcut.'__%\' ';
            $result_data = $gDb->query($sql);
        }

        // wenn die Tabelle nur Einträge dieses Plugins hatte, sollte sie jetzt leer sein und kann gelöscht werden
        $sql = 'SELECT * FROM '.$this->table_name.' ';
        $statement = $gDb->query($sql);

        if($statement->rowCount() ==0)
        {
            $sql = 'DROP TABLE '.$this->table_name.' ';
            $result_db = $gDb->query($sql);
        }

        $result  = ($result_data ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_DATA_DELETE_SUCCESS') : $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_DATA_DELETE_ERROR'));
        $result .= ($result_db ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_TABLE_DELETE_SUCCESS') : $gL10n->get('PLG_MITGLIEDSBEITRAG_DEINST_TABLE_DELETE_ERROR'));

        return $result;
    }

    /**
     * Löscht die Nutzerdaten in der Datenbank
     * @param   int     $deinst_org_select  0 = Daten nur in aktueller Org löschen, 1 = Daten in allen Orgs löschen, !=0 oder !=1) = Daten löschen, die in allen Orgs sichtbar sind
     * @param   string  $dataField          usf_name_intern des zu löschenden Datenfeldes
     * @param   string  $dataDesc           Überschrift eines Blocks der Meldung
     * @return  string  $result             Meldung
     */
    public function delete_member_data($deinst_org_select, $dataField, $dataDesc='')
    {
        global $gDb, $gCurrentOrganization,$gL10n,$gProfileFields;

        $result = '';
        $result_data=false;
        $result_logdata=false;
        $result_listdata=false;
        $result_profilefield=false;
        $result_category=false;
        $usfIDs=array();

        if($deinst_org_select==0)                   //0 = Daten nur in aktueller Org löschen
        {
            $orgSelector = $gCurrentOrganization->getValue('org_id');
        }
        elseif ($deinst_org_select==1)              //1 = Daten in allen Org löschen
        {
            $orgSelector = '%';
            //$orgSelector = '_';
        }
        else                                         // else: übergebenes Datenfeld ist nicht Org-gebunden (ohne Org-ID, NULL)
        {
            $orgSelector = '';
        }

        // alle usf_id´s des übergebenen $dataField einlesen
        $sql = 'SELECT usf_id, usf_name, usf_name_intern,usf_cat_id, cat_name,cat_name_intern FROM '.TBL_USER_FIELDS.', '.TBL_CATEGORIES.'
                WHERE usf_name_intern LIKE  \''.$dataField.$orgSelector.'\'
                AND  usf_cat_id=cat_id  ';
        $statement = $gDb->query($sql);

        while($row = $statement->fetch())
        {
            $usfIDs[$row['usf_id']]['usf_id'] = $row['usf_id'];
            $usfIDs[$row['usf_id']]['usf_name'] = $row['usf_name'];
            $usfIDs[$row['usf_id']]['usf_name_intern'] = $row['usf_name_intern'];
            $usfIDs[$row['usf_id']]['usf_cat_id'] = $row['usf_cat_id'];
            $usfIDs[$row['usf_id']]['cat_name'] = $row['cat_name'];
            $usfIDs[$row['usf_id']]['cat_name_intern'] = $row['cat_name_intern'];
        }

        $result .= '<BR><EM>'.$dataDesc.'</EM>';

        // das Array durchlaufen und DELETE ausführen
        foreach ($usfIDs as $dummy => $data)
        {
            $sql = 'SELECT * FROM '.TBL_USER_DATA.'
            WHERE usd_usf_id = '.$data['usf_id'];
            $statement = $gDb->query($sql);

            if($statement->rowCount() !=0)
            {
                $sql = 'DELETE FROM '.TBL_USER_DATA.'
                        WHERE usd_usf_id = '.$data['usf_id'];
                $result_data = $gDb->query($sql);
                $result .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_DATA_FROM').' '.$data['usf_name_intern'].' in '.TBL_USER_DATA.' - Status: '.($result_data ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETED') : $gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR'));
                //$result_sum .='<BR>';
            }

            $sql = 'SELECT * FROM '.TBL_USER_LOG.'
            WHERE usl_usf_id = '.$data['usf_id'];
            $statement = $gDb->query($sql);

            if($statement->rowCount() !=0)
            {
                $sql = 'DELETE FROM '.TBL_USER_LOG.'
                    WHERE usl_usf_id = '.$data['usf_id'];
                $result_logdata = $gDb->query($sql);
                $result .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_DATA_FROM').' '.$data['usf_name_intern'].' in '.TBL_USER_LOG.' - Status: '.($result_logdata ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETED') : $gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR'));
                //$result_sum .='<BR>';
            }

            $sql = 'SELECT * FROM '.TBL_LIST_COLUMNS.'
                WHERE lsc_usf_id = '.$data['usf_id'];
            $statement = $gDb->query($sql);

            if($statement->rowCount() !=0)
            {
                $sql = 'DELETE FROM '.TBL_LIST_COLUMNS.'
                    WHERE lsc_usf_id = '.$data['usf_id'];
                $result_listdata = $gDb->query($sql);
                $result .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_DATA_FROM').' '.$data['usf_name_intern'].' in '.TBL_LIST_COLUMNS.' - Status: '.($result_listdata ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETED') : $gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR'));
                //$result_sum .='<BR>';
            }

            $sql = 'DELETE FROM '.TBL_USER_FIELDS.'
                    WHERE usf_id = '.$data['usf_id'];
            $result_profilefield = $gDb->query($sql);

            $result .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMOVE_PROFILEFIELD').' '.$data['usf_name_intern'].' in '.TBL_USER_FIELDS.' - Status: '.($result_profilefield ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETED') : $gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR'));

            $sql = 'SELECT * FROM '.TBL_USER_FIELDS.'
                    WHERE usf_cat_id = '.$data['usf_cat_id'];
            $statement = $gDb->query($sql);
            if($statement->rowCount() ==0)
            {
                    $sql = 'DELETE FROM '.TBL_CATEGORIES.'
                        WHERE cat_id = '.$data['usf_cat_id'];
                    $result_category = $gDb->query($sql);

                    $result .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_REMOVE_CATEGORY').' '.$data['cat_name_intern'].' in '.TBL_CATEGORIES.' - Status: '.($result_category ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETED') : $gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR'));
            }
        }
        $result  .= '<BR>';

        return $result;
    }

    /**
     * Löscht die über das Plugin erstellten Mailtexte in der Datenbank
     * @param   int     $deinst_org_select  0 = Daten nur in aktueller Org löschen, 1 = Daten in allen Org löschen
     * @return  string  $result             Meldung
     */
    public function delete_mail_data($deinst_org_select)
    {
        global $gDb, $gCurrentOrganization,$gL10n;

        $result = '';
        $result_data=false;

        if($deinst_org_select==0)                    //0 = Daten nur in aktueller Org löschen
        {
            $sql = 'DELETE FROM '.TBL_TEXTS.'
                    WHERE txt_name LIKE \'PMBMAIL_%\'
                    AND txt_org_id = '.$gCurrentOrganization->getValue('org_id').' ';
            $result_data = $gDb->query($sql);
        }
        elseif ($deinst_org_select==1)              //1 = Daten in allen Org löschen
        {
            $sql = 'DELETE FROM '.TBL_TEXTS.'
                    WHERE txt_name LIKE \'PMBMAIL_%\' ';
            $result_data = $gDb->query($sql);
        }

        $result .= '<BR><EM>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_MAIL_TEXTS').'</EM>';

        $result .= '<BR>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_DELETE_MAIL_TEXTS').TBL_LIST_COLUMNS.' - Status: '.($result_data ? $gL10n->get('PLG_MITGLIEDSBEITRAG_DELETED') : $gL10n->get('PLG_MITGLIEDSBEITRAG_ERROR'));

        return $result;
    }
}
