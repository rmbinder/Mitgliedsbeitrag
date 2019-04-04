<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2019 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

global $gL10n, $gProfileFields;

//Standardwerte einer Neuinstallation

// Altersrollen
$config_default['Altersrollen'] = array('altersrollen_token'    => array('*'),
                                        'altersrollen_stichtag' => date('d.m.Y', strtotime((date('Y')-1).'-12-31')));

// Familienrollen
$config_default['Familienrollen'] = array('familienrollen_beitrag'        => array(0),
                                            'familienrollen_zeitraum'     => array(12),
                                            'familienrollen_beschreibung' => array('Familienbeitrag'),
                                            'familienrollen_prefix'       => array('Familie'),
                                            'familienrollen_pruefung'     => array(''));

//Beitrag
$config_default['Beitrag'] = array('beitrag_prefix'         => 'Mitgliedsbeitrag 2019',
                                    'beitrag_suffix'        => '(ant.)',
                                    'beitrag_textmitnam'    => 1,
                                    'beitrag_textmitfam'    => '',
                                    'beitrag_text_token'    => '#',
                                    'beitrag_anteilig'      => '',
                                    'beitrag_abrunden'      => 1,
                                    'beitrag_mindestbetrag' => 0);

//Kontodaten
$config_default['Kontodaten'] = array('bank'           => 'Sparkasse Musterstadt',
                                        'inhaber'      => 'Musterverein e.V.',
                                        'iban'         => 'DE123456789',
                                        'bic'          => 'ABCDEFGH',
                                        'ci'           => 'DE98ZZZ09999999999',
                                        'origcreditor' => '',
                                        'origci'       => '');

//Mandatsreferenz
$config_default['Mandatsreferenz'] = array('prefix_fam'  => 'FAM',
                                            'prefix_mem' => 'MIT',
                                            'prefix_pay' => 'ZAL',
                                            'min_length' => 15,
                                            'data_field' => '-- User_ID --');

//Rollenpruefung
$config_default['Rollenpruefung'] = array('altersrollenfamilienrollen' => array(' '),
                                            'altersrollenpflicht'      => array(' '),
                                            'familienrollenpflicht'    => '',
                                            'fixrollenpflicht'         => array(' '),
                                            'bezugskategorie'          => array(' '),
                                            'altersrollenaltersrollen' => array(' '),
                                            'familienrollenfix'        => array(' '),
                                            'altersrollenfix'          => array(' '),
										    'fixrollenfixrollen'       => array(' '));

//Rechnungs-Export
$config_default['Rechnungs-Export'] = array('rechnung_dateiname' => 'rechnung.csv');

//SEPA
$config_default['SEPA'] = array('dateiname'                  => 'sepa',
                                'kontroll_dateiname'         => 'sepa',
                                'vorabinformation_dateiname' => 'export');

// Plugininformationen
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';

//Spalten fuer die Ansichtsdefinitionen
$config_default['columnconfig'] = array('payments_fields_normal_screen' => array('p'.$gProfileFields->getProperty('PAID'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('DUEDATE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('SEQUENCETYPE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FEE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')),
										'payments_fields_full_screen' 	=> array('p'.$gProfileFields->getProperty('PAID'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('DUEDATE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('SEQUENCETYPE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FEE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
												                                 'p'.$gProfileFields->getProperty('ORIG_MANDATEID'.ORG_ID, 'usf_id'),
												                                 'p'.$gProfileFields->getProperty('ORIG_DEBTOR_AGENT', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('DEBTOR', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('DEBTOR_EMAIL', 'usf_id')),
										'mandates_fields_normal_screen' => array('p'.$gProfileFields->getProperty('MANDATEDATE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('MANDATEID'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')),
										'mandates_fields_full_screen' 	=> array('p'.$gProfileFields->getProperty('MANDATEDATE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('MANDATEID'.ORG_ID, 'usf_id'),
												                                 'p'.$gProfileFields->getProperty('SEQUENCETYPE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('IBAN', 'usf_id')),
										'duedates_fields_normal_screen' => array('p'.$gProfileFields->getProperty('DUEDATE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('SEQUENCETYPE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FEE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')),
										'duedates_fields_full_screen' 	=> array('p'.$gProfileFields->getProperty('DUEDATE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('SEQUENCETYPE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FEE'.ORG_ID, 'usf_id'),
																				 'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																				 'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')));
	
//Formatierung der Mitgliedsnummer
$config_default['membernumber'] = array('format' => '');

//Zugriffsberechtigung für das Modul preferences
$config_default['access']['preferences'] = array(getRole_IDPMB($gL10n->get('SYS_ADMINISTRATOR')));

/*
 *  Mittels dieser Zeichenkombination werden Konfigurationsdaten, die zur Laufzeit als Array verwaltet werden,
 *  zu einem String zusammengefasst und in der Admidiodatenbank gespeichert.
 *  Muessen die vorgegebenen Zeichenkombinationen (#_#) jedoch ebenfalls, z.B. in der Beschreibung
 *  einer Konfiguration, verwendet werden, so kann das Plugin gespeicherte Konfigurationsdaten
 *  nicht mehr richtig einlesen. In diesem Fall ist die vorgegebene Zeichenkombination abzuaendern (z.B. in !-!)
 *
 *  Achtung: Vor einer Aenderung muss eine Deinstallation durchgefuehrt werden!
 *  Bereits gespeicherte Werte in der Datenbank koennen nach einer Aenderung nicht mehr eingelesen werden!
 */
$dbtoken  = '#_#';
