<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2016 The Admidio Team
 * @see http://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

global $gL10n, $gProfileFields;

//Standardwerte einer Neuinstallation
$config_default['Pluginfreigabe']['freigabe'] = array(	getRole_IDPMB($gL10n->get('SYS_WEBMASTER')),
													   getRole_IDPMB($gL10n->get('SYS_MEMBER')));    		
$config_default['Pluginfreigabe']['freigabe_config'] = array(	getRole_IDPMB($gL10n->get('SYS_WEBMASTER')),
															getRole_IDPMB($gL10n->get('SYS_MEMBER')));    		 		   		

// Altersrollen
$config_default['Altersrollen'] = array('altersrollen_token' 	=> array('*'),    	
   										'altersrollen_stichtag' => date('d.m.Y',strtotime((date('Y')-1).'-12-31')) );	
  
// Familienrollen
$config_default['Familienrollen'] = array(	'familienrollen_beitrag' => array(0),	
   											'familienrollen_zeitraum' => array(12),	
  											'familienrollen_beschreibung' => array('Familienbeitrag'),	
  											'familienrollen_prefix' => array('Familie'),	
   											'familienrollen_pruefung' => array('') );	

//Beitrag 
$config_default['Beitrag'] = array(	'beitrag_prefix' => 'Mitgliedsbeitrag 2016',
  									'beitrag_suffix' => '(ant.)',	
  									'beitrag_modus' => 'standard',		
  									'beitrag_rollenwahl' => array(' '),	
  									'zahlungen_rollenwahl'  => array(' '),	
   									'beitrag_textmitnam' => 1,	
   									'beitrag_textmitfam' => '',	
  									'beitrag_text_token' => '#',	
  									'beitrag_anteilig' => '',
  									'beitrag_abrunden' => 1,
  									'beitrag_mindestbetrag' => 0 );	

//Kontodaten
$config_default['Kontodaten'] = array(	'bank' => 'Sparkasse Musterstadt',	
  								  		'inhaber' => 'Musterverein e.V.',	
 								  		'iban' => 'DE123456789',	
 								 		'bic' => 'ABCDEFGH',	
 										'ci' => 'DE98ZZZ09999999999',	
 								  		'origcreditor' => '',	
 										'origci' => '');	
       
//Mandatsreferenz
$config_default['Mandatsreferenz'] = array(	'prefix_fam' => 'FAM',	
   											'prefix_mem' => 'MIT',	
   											'prefix_pay' => 'ZAL',	
  											'min_length' => 15,
   											'data_field' => '-- User_ID --');	
 
//Rollenpruefung
$config_default['Rollenpruefung'] = array(	'altersrollenfamilienrollen' => 1,	
  											'altersrollenpflicht' => '',	
  											'familienrollenpflicht' => '',	
   											'fixrollenpflicht' => array(' '),	
   											'bezugskategorie' => array(' '),	
  											'familienrollenfix' => array(' '),	
  											'altersrollenfix' => array(' ') );	
   
//Rechnungs-Export
$config_default['Rechnungs-Export'] = array('rechnung_dateiname' => 'rechnung.csv');	
  
//SEPA
$config_default['SEPA'] = array('dateiname' => 'sepa',	
 								'kontroll_dateiname' => 'sepa',	
								'vorabinformation_dateiname' => 'export',	
								'duedate_rollenwahl'  => array(' ') );	
	
// Plugininformationen													
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';

/*
 *  Mittels dieser Zeichenkombination werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert. 
 *  Müssen die vorgegebenen Zeichenkombinationen (#_#) jedoch ebenfalls, z.B. in der Beschreibung 
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten 
 *  nicht mehr richtig einlesen. In diesem Fall ist die vorgegebene Zeichenkombination abzuändern (z.B. in !-!)
 *  
 *  Achtung: Vor einer Änderung muss eine Deinstallation durchgeführt werden!
 *  Bereits gespeicherte Werte in der Datenbank können nach einer Änderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';  

