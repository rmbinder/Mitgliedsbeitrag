<?php
/**
 ***********************************************************************************************
 * Konfigurationsdaten fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2023 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 ***********************************************************************************************
 */

global $gProfileFields;

$config_default = array();

//Standardwerte einer Neuinstallation
                                                                                     
// Altersrollen
$config_default['Altersrollen'] = array('altersrollen_token'    => array('*'),
                                        'altersrollen_offset'   => 0);

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
										    'fixrollenfixrollen'       => array(' '),
                                            'age_staggered_roles_exclusion'=> array(' '));

$config_default['tests_enable'] = array('age_staggered_roles'                   => 1,
                                        'role_membership_age_staggered_roles'   => 1,
                                        'role_membership_duty_and_exclusion'    => 1,
                                        'family_roles'                          => 1,
                                        'account_details'                       => 1,
                                        'mandate_management'                    => 1,
                                        'iban_check'                            => 1,
                                        'bic_check'                             => 1);

//Rechnungs-Export
$config_default['Rechnungs-Export'] = array('rechnung_dateiname' => 'rechnung',
                                            'rechnung_dateityp'  => 'xlsx');

//SEPA
$config_default['SEPA'] = array('dateiname'                  => 'sepa',
                                'kontroll_dateiname'         => 'sepa',
                                'kontroll_dateityp'          => 'xlsx',
                                'vorabinformation_dateiname' => 'export',
                                'vorabinformation_dateityp'  => 'xlsx');

// Plugininformationen
$config_default['Plugininformationen']['version'] = '';
$config_default['Plugininformationen']['stand'] = '';

//Spalten fuer die Ansichtsdefinitionen
$config_default['columnconfig'] = array('payments_fields' => array( 'p'.$gProfileFields->getProperty('PAID'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('DUEDATE'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('SEQUENCETYPE'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('FEE'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')),
										'mandates_fields' => array( 'p'.$gProfileFields->getProperty('MANDATEDATE'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('MANDATEID'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')),
                                            'bill_fields' => array( 'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
                                                                    'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
                                                                    'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')),
										'duedates_fields' => array( 'p'.$gProfileFields->getProperty('DUEDATE'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('SEQUENCETYPE'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('FEE'.$GLOBALS['gCurrentOrgId'], 'usf_id'),
																	'p'.$gProfileFields->getProperty('LAST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('FIRST_NAME', 'usf_id'),
																	'p'.$gProfileFields->getProperty('BIRTHDAY', 'usf_id')));
	
//Mitgliedsnummer
$config_default['membernumber'] = array('format'    => '',
                                        'fill_gaps' => 1);

//Zugriffsberechtigung für das Modul preferences
$config_default['access']['preferences'] = array();

//Individualbeiträge
$config_default['individual_contributions'] = array('access_to_module'  => '0',
                                                    'desc'              => array(' '),
                                                    'short_desc'        => array(' '),
                                                    'role'              => array(' '),
                                                    'amount'            => array(' '),
                                                    'profilefield'      => array(' ')
                                                );

//Multiplikatorrollen (nur Familienrollen)
$config_default['multiplier']['roles'] = array();

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
