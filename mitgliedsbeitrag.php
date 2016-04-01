<?php
/******************************************************************************
 * Mitgliedsbeitrag
 *
 * Version 4.0.0
 *
 * Dieses Plugin berechnet Mitgliedsbeitraege anhand von Rollenzugehoerigkeiten.
 *
 * Copyright    : (c) 2004 - 2015 The Admidio Team
 * Homepage     : http://www.admidio.org
 * License      : GNU Public License 2 http://www.gnu.org/licenses/gpl-2.0.html
 * Autor		: rmb
 * 
 * Version	    : 4.0.0
 * Datum		: 09.11.2015
 * Änderung     : - Anpassung an Admidio 3.0
 *                - Deinstallationsroutine erstellt
 *                - Modul copy erstellt
 *                - Verfahren zum Einbinden des Plugins (include) geändert 
 *                - Menübezeichnungen angepasst (gleichlautend mit anderen Plugins)
 *                - Erweiterte Überprüfung auf unzulässige Zeichen in den SEPA-XML-Dateien   
 *                - Nur Intern: Verwaltung der Konfigurationsdaten geändert 
 * 
 * Version		: 3.3.7
 * Datum        : 08.09.2014
 * Änderung     : - wenn ein KtoInh angegeben war und bei diesem die E-Mail-Adresse leer war,
 * 					wurde eine E-Mail an den vorherigen User gesendet --> Fehler behoben
 * 				  - %creditor_id% wurde beim E-Mail-Versand nicht immer aufgelöst 
 * 				  - die Einschränkung auf die Rolle Mitglied beim Erzeugen von Mitgliedsnummern
 * 				    wurde entfernt
 * 
 * Version		: 3.3.6
 * Datum        : 25.07.2014
 * Änderung     : - IBAN only 
 * 				  - beim Setzen eines Bezahlt-Datums wird der Sequenztyp nach RCUR nur beim
 * 					 Vorliegen eines Fälligkeitsdatums geändert
 * 				  - im Modul "Fälligkeitsdatum bearbeiten" werden Mitglieder 
 * 					 1. nur noch bei vorhandenem Beitrag angezeigt,
 * 					 2. bei fehlender IBAN nicht mehr angezeigt,
 * 					 3. bei vorhandenem Bezahlt-Datum nicht mehr angezeigt
 * 				  - im Modul "SEPA Export" werden bei den Auswahlmöglichkeiten der Kombination Fälligkeitsdatum/Sequenztyp
 * 				     Mitglieder ohne vorhandenem Beitrag nicht mehr angezeigt
 * 				  - Eil-Lastschrift (COR1)
 * 				  - Menüpunkt DTA-Export gestrichen
 * 				  - Beitragszahlungen kann auf einzelne Beitragsrollen eingeschränkt werden
 *				  - IBAN-Prüfung
 *				  - E-Mail-Versand im Modul Vorabinformation an mehrere Mitglieder gleichzeitig möglich
 *				  - Prüfbedingung zum Übersetzen von Rollen und Kategorien durch Sprachdatei geändert
 *				  - Anzeigeposition des Kalenders in Einstellungen-Altersgestaffelte Rollen angepasst
 *				  - anteilige Beitragsberechnung bis zum Ende einer Rollenmitgliedschaft
 * 				  - unter dem Menüpunkt "Einstellungen-Reset" wurden diverse Löschmöglichkeiten entfernt,
 * 					 da diese seit der Version 3.3.5 unter "Allgemein-Beitragsberechnung" zu finden sind
 * 				  - interne Änderungen (ohne Funktionsänderungen):
 * 					 1. Module zahlungen_change und zahlungen_save zusammengefasst zu zahlungen_save
 * 					 2. Module faelligkeitsdatum_change und faelligkeitsdatum_save zusammengefasst zu faelligkeitsdatum_save
 * 					 3. Module mandatsdatumn_change und mandatsdatum_save zusammengefasst zu mandatsdatum_save
 * 					 4. Menüpunkt "Rollenprüfungen" wurde aufgrund erweiterter Funktionalität umbenannt in "Prüfungen"
 * 					 5. Menüpunkt "Einstellungen-Reset" wurde umbenannt in "Einstellungen-Löschen"
 * 				    
 * Version		: 3.3.5
 * Datum        : 12.02.2014
 *                (Dank an fiwad für die Unterstützung beim Testen dieser Version)
 * Änderung     : - Fehler ...indefined index:...language.php line 272... behoben
 *                - Fehler im Modul Beitragsanalyse behoben (fehlende IBAN-Verknüpfung)
 *                - Fehler im Modul Neuzuordnung behoben
 *                - Diakritische Zeichen werden in der SEPA-XML-Datei ersetzt
 *                - Errechnete Beiträge können aufsummiert werden
 *                - Beitragsneuberechnung kann auf einzelne Beitragsrollen eingeschränkt werden
 *                - Initialisierungsroutine überarbeitet
 *                - SOLL/IST-Anzeige der Setuproutine überarbeitet
 *                - Link auf Familien im Modul Rollenprüfung geändert
 *                - Beitragstext gestrafft (Leerzeichen und + entfernt)
 *                - Fälligkeitsdatum bearbeiten kann auf einzelne Beitragsrollen eingeschränkt werden
 *                - Rollenprüfung für Familienrollen umfassend neu gestaltet 
 *                - Menü Beitragsberechnung neu gestaltet
 * 
 * Version		: 3.3.4
 * Datum        : 30.10.2013
 * Änderung     : - Fehler (()) behoben
 * 				  - Erweiterung für "mehrere Familienrollen"
 * 				  - Erweiterung für "mehrere altersgestaffelte Rollen"
 * 				  - E-Mail-Versand in Modul Beitragszahlungen eingearbeitet
 * 				  - Ergebnismeldung in Modul Neuzuordnung eingearbeitet 
 * 
 * Version		: 3.3.3
 * Datum        : 13.09.2013
 * Änderung     : - E-Mail-Versand in Modul Vorabinformation eingearbeitet
 * 				  - Die Prüfung der Rollenmitgliedschaften kann auf Mitglieder
 * 					bestimmter Kategorien eingegrenzt werden (Wunsch von joesch)
 * 
 * Version		: 3.3.2
 * Datum        : 01.08.2013
 * Änderung     : - error_reporting überarbeitet
 *
 * Version		: 3.3.1
 * Datum        : 11.07.2013
 * Änderung     : - Fehler beim Anlegen der Kategorie Kontodaten behoben
 * 
 * Version		: 3.3.0
 * Datum        : 02.07.2013
 * Änderung     : - Plugin um SEPA erweitert
 *                - Einfache Mitgliedsnummern können erzeugt werden 
 *                - Fehler bei der Anzeige des Beitrag-Suffix bei anteiligen Beiträgen behoben
 * 
 * Version		: 3.2.0
 * Datum        : 26.03.2013
 * Änderung     : - Anpassung an Admidio 2.4
 * 				  - Konfigurationsdaten werden nicht mehr in einer config.ini gespeichert,
 * 				    sondern in der Admidio Datenbank abgelegt
 * 				  - Rechnungs-Export von guenter47 eingearbeitet
 * 				  - Menuestruktur überarbeitet
 * 				  - Fehler in der Rollenprüfung "Rollenmitgliedschaft (Ausschluss)" behoben 
 *   
 * Version		: 3.1.1
 * Datum        : 14.01.2013
 * Änderung     : - Im Modul Beitragszahlungen wird jetzt auch der Beitrag angezeigt
 * 				  - Ein Fehler beim Speichern des Bezahlt-Datums behoben (function date_format2mysql von eiseli) 
 *				  - Anführungszeichen fehlten in mitgliedsbeitrag_show Zeile 986 (eiseli)
 *					alt: (array(cat => $row['cat_name'],rol => $row['rol_name'])
 *					neu: (array('cat' => $row['cat_name'],'rol' => $row['rol_name']) 
 *				  - Undefinierte Variablen sind jetzt definiert
 *				  - Eine Sprachdatei deutsch (Sie)wurde erstellt
 *
 * Version		: 3.1.0
 * Datum        : 06.12.2012
 * Änderung     : - Ein Fehler beim Speichern der config.ini wurde behoben
 *                - Das Plugin wurde für "mehrere Organisationen" erweitert
 *                - Eine upgrade.php wurde erstellt (3.0.0 --> 3.1.x)                  
 *                - Die Anzeige der Mitgliedsnamen im Beitragstext ist jetzt möglich (Anregung durch hausi)
 *                		(eine Anzeige erfolgt jedoch nur, wenn: Kontoinhaber und Mitgliedsname unterschiedlich sind
 *                			oder Kontoinhaber leer ist)
 *                - In der Rollenprüfung werden die angezeigten Benutzernamen und Rollen 
 *                		jetzt mit einem Link hinterlegt (Anregung durch joesch)
 *                - Das Modul Zahlungen (jetzt Beitragszahlungen) wurde komplett überarbeitet (Anregung durch hausi und walegger)
 *                - Eine deutsche Sprachdatei wurde erstellt
 *
 * Version		: 3.0.0
 * Datum        : 25.06.2011
 * Änderung     :  Beiträge aus dem Forum eingearbeitet
 *                - Rundung der berechneten Mitgliedsbeiträge auf zwei Nachkommastellen
 *                - Profilfeld Beitritt ist kein Pflichtfeld mehr
 *                - zusätzlicher Schalter für die anteilige Beitragsberechnung  
 *                  (Berechnung anhand des Beitrittsdatums oder des Beginns einer Rollenzugehörigkeit) 
 *                - das Präfix für Familienrollen ist beim ersten Aufruf vordefiniert ('Familie')
 *                - bei Familienrollen kann ein Leiter definiert werden; dieser wird für die 
 *                  Beitragsberechnung herangezogen 
 *                - ein Fehler in der Beitragsberechnung wurde behoben (bestehende Alt-Beiträge
 *                  wurden nicht gelöscht )
 *                - ein Fehler bei der Berechnung des Alters eines Mitglieds,
 *                  bezogen auf den Stichtag, wurde behoben  
 *
 * Version		: 3.0.0 beta 1
 * Datum        : 30.05.2012
 * Änderung     : Das Plugin wurde komplett überarbeitet. Es ist jetzt als
 *                Accordion-Menü (ähnl. Administration-Organisationseinstellungen) in admidio
 *                integriert. Alle Einstellungen des Plugins sind über das Menü anwählbar. 
 *
 * Version		: 2.3.1
 * Datum        : 03.03.2012
 * Änderung     : - Ein Fehler bei der Berechnung von Cent-Beträgen wurde behoben  
 * 
 * Version		: 2.3.0 (mit Ergänzungen von hausi)
 * Datum        : 21.02.2012
 * Änderung     : - das Plugin ist jetzt Admidio 2.3 kompatibel
 *                - Über Rollenzugehörigkeiten können fixe Jahresbeiträge berechnet werden.
 *                  (Alle Mitglieder einer Kategorie zahlen einen festen Jahresbeitrag.
 *                  Die effektiven Beiträge werden von den jeweiligen Rollen der Kategorie
 *                  aus der DB ermittelt.) (hausi)
 *                - der Fehler bei einem Mitgliedsbeitrag von 0 wurde behoben 
 * 
 * Version		: 2.2.1
 * Datum        : 08.12.2011
 * Änderung     : - das Standard-Datenbankpräfix (adm_) ist nicht mehr fest kodiert
 * 
 * Version		: 2.2.0
 * Datum        : 21.11.2011
 * Änderung     : - Das externe Programm dtaus wird nicht mehr benötigt. Durch die
 *                  Integration der Klasse DTA ist es jetzt möglich, direkt die
 *                  dtaus-Datei und den dazugehörigen Begleitzettel zu erstellen. 
 *                - Die Exportdateien und die Bildschirmanzeige wurden 
 *                  in ihrer Struktur vereinheitlicht. Sie weisen jetzt alle
 *                  dieselben Spalten an derselben Position auf.  
 *                - bisher wurden bei einer Familie die Kontodaten eines zufällig
 *                  ausgewählten Mitglieds verwendet. Falls genau bei diesem Mitglied 
 *                  keine Kontodaten hinterlegt waren, wurde auf Rechnung umgestellt.
 *                  Dies wurde geändert. Es werden alle Mitglieder einer Familie
 *                  abgefragt. Nur wenn bei keinem Mitglied Kontodaten hinterlegt sind,
 *                  wird auf Rechnung umgestellt. 
 *                - Die Berechtigung das Plugin aufzurufen, wurde um 
 *                  Rollenmitgliedschaften erweitert.
 * 
 * Version		: 2.1.0
 * Datum        : 26.10.2011
 * Änderung     : - Dem Plugin wurde eine Weboberfläche verpasst.
 *                - Die erzeugte CSV-Datei wird nicht mehr auf dem Server 
 *                  zwischengespeichert, sie wird in der Listenansicht zum
 *                  Download angeboten. 
 *                - Das zusätzliche Plugin downloadfile.php wird nicht mehr benötigt.       
 * 
 * Version		: 2.0.0
 * Datum        : 12.07.2011    
 * Änderung     : - Neues Feld "Beitritt" für ein Mitglied
 *                - Berechnung eines Spartenbeitrages
 *                - Berechnung eines Schüler- und Studentenbeitrages
 *                - Beiträge können abgerundet werden
 *                - der Kategoriename für Familien ist frei wählbar    
 *  
 * Version		: 1.0.1
 * Autor		: Gerald Lutter
 * Datum        : 10.01.2011
 *                                                                                   
 *****************************************************************************/

//Fehlermeldungen anzeigen
//error_reporting(E_ALL);

// Pfad des Plugins ermitteln
$plugin_folder_pos = strpos(__FILE__, 'adm_plugins') + 11;
$plugin_file_pos   = strpos(__FILE__, basename(__FILE__));
$plugin_path       = substr(__FILE__, 0, $plugin_folder_pos);
$plugin_folder     = substr(__FILE__, $plugin_folder_pos+1, $plugin_file_pos-$plugin_folder_pos-2);

require_once($plugin_path. '/../adm_program/system/common.php');
require_once($plugin_path. '/'.$plugin_folder.'/common_function.php');
require_once($plugin_path. '/'.$plugin_folder.'/classes/configtable.php'); 

$pPreferences = new ConfigTablePMB();

// DB auf Admidio setzen, da evtl. noch andere DBs beim User laufen
$gDb->setCurrentDB();

// Einbinden der Sprachdatei
$gL10n->addLanguagePath($plugin_path.'/'.$plugin_folder.'/languages');  	

// eine Deinstallation hat stattgefunden, deshalb keine Installationsroutine durchlaufen und auch keinen Link anzeigen
if(!isset($_SESSION['pmbDeinst']))
{
	$checked = $pPreferences->checkforupdate();
	$startprog='menue.php';

	if ($checked==1 )   		//Update (Konfigurationdaten sind vorhanden, der Stand ist aber unterschiedlich zur Version.php)
	{
		$pPreferences->init();	
	}
	elseif ($checked==2)		//Installationsroutine durchlaufen
	{
		$startprog='installation.php';
		$pPreferences->init();
	}

	$pPreferences->read();			// (checked ==0) : nur Einlesen der Konfigurationsdaten
	
	// Zeige Link zum Plugin
	if(check_showpluginPMB($pPreferences->config['Pluginfreigabe']['freigabe']) )
	{
		if (isset($pluginMenu))
		{
			// wenn in der my_body_bottom.php ein $pluginMenu definiert wurde, 
			// dann innerhalb dieses Menüs anzeigen
			$pluginMenu->addItem('membershipfee_show', '/adm_plugins/'.$plugin_folder.'/'.$startprog,
				$gL10n->get('PMB_MEMBERSHIP_FEE'), '/icons/lists.png'); 
		}
		else 
		{
			// wenn nicht, dann innerhalb des (immer vorhandenen) Module-Menus anzeigen
			$moduleMenu->addItem('membershipfee_show', '/adm_plugins/'.$plugin_folder.'/'.$startprog,
				$gL10n->get('PMB_MEMBERSHIP_FEE'), '/icons/lists.png'); 
		}
	}
}	

?>