<?php
/**
 ***********************************************************************************************
 * SEPA-Export fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2018 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   Grundgeruest erstellt von Günter Scheuermann am 28.1.2013
 *
 * Parameters:
 *
 * duedatesepatype  :   Array mit Kombinationen von Faelligkeitsdatum und SepaTyp
 *                      - Zeichen 0 bis 9: Faelligkeitsdatum 
 *                      - ab Zeichen 10: Sepatyp
 *                      - Bsp.. 2017-12-12FRST oder 2017-12-30RCUR
 *
 ***********************************************************************************************
 */

require_once(__DIR__ . '/common_function.php');
require_once(__DIR__ . '/classes/configtable.php');

// only authorized user are allowed to start this module
if (!isUserAuthorized($_SESSION['pMembershipFee']['script_name']))
{
	$gMessage->show($gL10n->get('SYS_NO_RIGHTS'));
}

// Konfiguration einlesen
$pPreferences = new ConfigTablePMB();
$pPreferences->read();
$oneDueDateOnly = false;

if (!isset($_POST['duedatesepatype']))
{
	$gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA_EXPORT_NO_DATA'));
}

if (sizeof($_POST['duedatesepatype']) == 1)
{
	$oneDueDateOnly = true;				// es gibt nur ein Fälligkeitsdatum mit einem Sequenztyp: der PmtTpInf-Block wird im PmtInf-Block plaziert (damit KSK die XML-Datei einlesen kann)
}

$dueDateArr = array();
$zempf = array();
$zpflgt = array();
$nbOfTxs_Msg = 0; 																	// Anzahl der Transaktionen innerhalb der Message
$ctrlSum_Msg = 0;																	// Kontrollsumme der Beträge innerhalb der Message
$now = time();
$format1 = 'Y-m-d';
$format2 = 'H:i:s';
$filename_ext = '';

foreach ($_POST['duedatesepatype'] as $dummy => $data)
{
	$filename_ext .= '_'.substr($data, 0, 10).'-'.substr($data, 10);				// Erweiterung fuer den Dateinamen zusammensetzen
	$dueDateArr[substr($data, 0, 10)]['sequencetype'] = substr($data, 10);			// je DueDate ein PmtInf-Block
	$dueDateArr[substr($data, 0, 10)]['nbOfTxs_PmtInf'] = 0;						// Anzahl der Transaktionen innerhalb eines PmtInf-Blocks
	$dueDateArr[substr($data, 0, 10)]['ctrlSum_PmtInf'] = 0;						// Kontrollsumme der Beträge innerhalb eines PmtInf-Blocks
}
	
$members = list_members(array('FIRST_NAME', 'LAST_NAME', 'FEE'.ORG_ID, 'CONTRIBUTORY_TEXT'.ORG_ID, 'PAID'.ORG_ID, 'STREET', 'CITY', 'DEBTOR', 'DEBTOR_CITY', 'DEBTOR_STREET', 'IBAN', 'ORIG_IBAN', 'BIC', 'BANK', 'ORIG_DEBTOR_AGENT', 'MANDATEID'.ORG_ID, 'ORIG_MANDATEID'.ORG_ID, 'MANDATEDATE'.ORG_ID, 'DUEDATE'.ORG_ID, 'SEQUENCETYPE'.ORG_ID), 0);
		
//alle Mitglieder durchlaufen und das Array $zpflgt befuellen
foreach ($members as $member => $memberdata)
{
	$dueDateMember = $memberdata['DUEDATE'.ORG_ID];
	$sequenceTypeMember = (empty($memberdata['SEQUENCETYPE'.ORG_ID])) ? 'FRST' : $memberdata['SEQUENCETYPE'.ORG_ID];
	
    if  (!empty($memberdata['FEE'.ORG_ID])
        && empty($memberdata['PAID'.ORG_ID])
        && !empty($memberdata['CONTRIBUTORY_TEXT'.ORG_ID])
        && !empty($memberdata['IBAN'])
        && in_array($dueDateMember.$sequenceTypeMember, $_POST['duedatesepatype']) )
    {
    	$zpflgt[$member]['duedate'] = $dueDateMember;
    	$zpflgt[$member]['sequencetype'] = $sequenceTypeMember;
    	
        if (empty($memberdata['DEBTOR']))
        {
            $members[$member]['DEBTOR'] = $memberdata['FIRST_NAME'].' '.$memberdata['LAST_NAME'];
            $members[$member]['DEBTOR_STREET'] = $memberdata['STREET'];
            $members[$member]['DEBTOR_CITY'] = $memberdata['CITY'];
        }

        $zpflgt[$member]['name'] = substr(replace_sepadaten($members[$member]['DEBTOR']), 0, 70);                                                     // Name of account owner.
        $zpflgt[$member]['alt_name'] = '';                                                                                                            // Zahlungspflichtiger abweichender Name
        $zpflgt[$member]['iban'] = strtoupper(str_replace(' ', '', $members[$member]['IBAN']));														  // IBAN
        
        if (isIbanNOT_EU_EWR($zpflgt[$member]['iban']))
        {
        	if (empty($members[$member]['BIC']))
        	{
        		$gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_BIC_MISSING', '<a href="'. ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php?user_id='. $member. '">'.$zpflgt[$member]['name']. '</a>'), $gL10n->get('SYS_ERROR'));
        	}
        	$zpflgt[$member]['land'] = substr($zpflgt[$member]['iban'], 0, 2);
        	$zpflgt[$member]['street'] = substr(replace_sepadaten($members[$member]['DEBTOR_STREET']), 0, 70);    
        	$zpflgt[$member]['ort'] = substr(replace_sepadaten($members[$member]['DEBTOR_CITY']), 0, 70);    
        }
                              
        $zpflgt[$member]['bic'] = strtoupper($members[$member]['BIC']);                                                                                           // BIC
        $zpflgt[$member]['mandat_id'] = $members[$member]['MANDATEID'.ORG_ID];                                     // Mandats-ID
        $zpflgt[$member]['mandat_datum'] = $members[$member]['MANDATEDATE'.ORG_ID];                                // Mandats-Datum

       	$fee = str_replace(',', '.', $members[$member]['FEE'.ORG_ID]);
       	if (strpos($fee, '.') !== false)
       	{
       		$fee = substr($fee, 0, strpos($fee, '.') +3);
       	}
       	
        $zpflgt[$member]['betrag'] = $fee;                                               															  // Amount of money
        $zpflgt[$member]['text'] = substr(replace_sepadaten($members[$member]['CONTRIBUTORY_TEXT'.ORG_ID]), 0, 140);   // Description of the transaction ("Verwendungszweck").
        $zpflgt[$member]['orig_mandat_id'] = $members[$member]['ORIG_MANDATEID'.ORG_ID];                           // urspruengliche Mandats-ID
        $zpflgt[$member]['orig_iban'] = strtoupper(str_replace(' ', '', $members[$member]['ORIG_IBAN']));                                             // urspruengliche IBAN
        $zpflgt[$member]['orig_dbtr_agent'] = $members[$member]['ORIG_DEBTOR_AGENT'];                                                                 // urspruengliches Kreditinstitut, nur "SMNDA" moeglich

        $dueDateArr[$dueDateMember]['nbOfTxs_PmtInf']++;
        $dueDateArr[$dueDateMember]['ctrlSum_PmtInf'] += $zpflgt[$member]['betrag'];
        $ctrlSum_Msg += $zpflgt[$member]['betrag'];

        $zpflgt[$member]['end2end_id'] = substr(replace_sepadaten($gCurrentOrganization->getValue('org_shortname')).'-'.$member.'-'.date($format1, $now), 0, 35);     //SEPA End2End-ID   (max. 35)
    }
}

$nbOfTxs_Msg = count($zpflgt);                                                                                        //SEPA Anzahl der Lastschriften

if ($nbOfTxs_Msg == 0)
{
    $gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_SEPA_EXPORT_NO_DATA'));
}

$message_id = substr('Message-ID-'.replace_sepadaten($gCurrentOrganization->getValue('org_shortname')), 0, 35);   //SEPA Message-ID    (max. 35)
$message_datum = date($format1, $now).'T'.date($format2, $now).'.000Z';                                           //SEPA Message-Datum z.B.: 2010-11-21T09:30:47.000Z
$message_initiator_name = substr(replace_sepadaten($pPreferences->config['Kontodaten']['inhaber']), 0, 70);       //SEPA Message Initiator Name

$payment_id = 'Beitragszahlungen';                                                                                //SEPA Payment_ID (max. 35)
$payment_end2end_id = 'NOTPROVIDED';                                                                              //SEPA Payment_EndToEndIdentification

$zempf['name'] = substr(replace_sepadaten($pPreferences->config['Kontodaten']['inhaber']), 0, 70);                //SEPA  Zahlungsempfaenger Kontoinhaber
$zempf['ci'] = $pPreferences->config['Kontodaten']['ci'];                                                         //Organisation SEPA_ID (Glaeubiger-ID Bundesdbank)

if (isIbanNOT_EU_EWR($pPreferences->config['Kontodaten']['iban']) && empty($pPreferences->config['Kontodaten']['bic']))
{
	$gMessage->show($gL10n->get('PLG_MITGLIEDSBEITRAG_BIC_MISSING', $zempf['name']), $gL10n->get('SYS_ERROR'));
}

$zempf['iban'] = strtoupper(str_replace(' ', '', $pPreferences->config['Kontodaten']['iban']));                   //SEPA  Zahlungsempfaenger IBAN
$zempf['bic'] = strtoupper($pPreferences->config['Kontodaten']['bic']);                                           //SEPA  Zahlungsempfaenger BIC
$zempf['orig_cdtr_name'] = $pPreferences->config['Kontodaten']['origcreditor'];                                   //urspruenglicher Creditor
$zempf['orig_cdtr_id'] = $pPreferences->config['Kontodaten']['origci'];                                           //urspruengliche Mandats-ID

if (isset($_POST['btn_xml_file']))
{

    /******************************************************************************
    * Schreibt Lastschriften in einen XML-String
    *****************************************************************************/
    $xmlfile = '';
    $xmlfile .= "<?xml version='1.0' encoding='UTF-8'?>\n";

    // DFÜ-Abkommen Version 3.1
    // Pain 008.001.002
    // ########## Document ###########
    $xmlfile .=  "<Document xmlns='urn:iso:std:iso:20022:tech:xsd:pain.008.001.02' 
    		xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
    		xsi:schemaLocation='urn:iso:std:iso:20022:tech:xsd:pain.008.001.02 pain.008.001.02.xsd'>\n";
    
    // ########## Customer Direct Debit Initiation ###########
    $xmlfile .= "<CstmrDrctDbtInitn>\n";

        // ########## Group-Header ###########
        $xmlfile .= "<GrpHdr>\n";
            $xmlfile .= "<MsgId>$message_id</MsgId>\n";                       		//MessageIdentification
            $xmlfile .= "<CreDtTm>$message_datum</CreDtTm>\n";                		//Datum & Zeit
            $xmlfile .= "<NbOfTxs>$nbOfTxs_Msg</NbOfTxs>\n";                      	//NumberOfTransactions
            $xmlfile .= "<CtrlSum>$ctrlSum_Msg</CtrlSum>\n";                 		//ControlSum
            $xmlfile .= "<InitgPty>\n";
                $xmlfile .= "<Nm>$message_initiator_name</Nm>\n";
            $xmlfile .= "</InitgPty>\n";
        $xmlfile .= "</GrpHdr>\n";

        foreach ($dueDateArr as $dueDate => $data)      							// je DueDate ein PmtInf-Block
        {
        	// ########## Payment Information ###########
        	$xmlfile .= "<PmtInf>\n";
            	$xmlfile .= "<PmtInfId>$payment_id</PmtInfId>\n";                 	//Payment-ID
            	$xmlfile .= "<PmtMtd>DD</PmtMtd>\n";                              	//Payment-Methode, Lastschrift: DD
            	$xmlfile .= "<BtchBookg>true</BtchBookg>\n";                      	//BatchBooking, Sammelbuchung (true) oder eine Einzelbuchung handelt (false)
            	$xmlfile .= '<NbOfTxs>'.$data['nbOfTxs_PmtInf']."</NbOfTxs>\n";     //Number of Transactions
            	$xmlfile .= '<CtrlSum>'.$data['ctrlSum_PmtInf']."</CtrlSum>\n";     //Control Sum
     
            	if ($oneDueDateOnly)												//es gibt nur ein Fälligkeitsdatum mit einem Sequenztyp: der PmtTpInf-Block wird im PmtInf-Block plaziert
            	{
            		$xmlfile .= "<PmtTpInf>\n";                                     //PaymentTypeInformation
            			$xmlfile .= "<SvcLvl>\n";                                   //ServiceLevel
            				$xmlfile .= "<Cd>SEPA</Cd>\n";                          //Code, immer SEPA
            			$xmlfile .= "</SvcLvl>\n";
            			$xmlfile .= "<LclInstrm>\n";                                //LocalInstrument, Lastschriftart
            				$xmlfile .= "<Cd>CORE</Cd>\n";                          //CORE (Basislastschrift oder B2B (Firmenlastschrift)
            			$xmlfile .= "</LclInstrm>\n";
            			$xmlfile .= '<SeqTp>'.$data['sequencetype']."</SeqTp>\n";           //SequenceType
            			//Der SequenceType gibt an, ob es sich um eine Erst-, Folge-,
            			//Einmal- oder letztmalige Lastschrift handelt.
            			//Zulaessige Werte: FRST, RCUR, OOFF, FNAL
            			//Wenn <OrgnlDbtrAcct> = SMNDA und <Amdmnt-Ind> = true
            			//dann muss dieses Feld mit FRST belegt sein.
            		$xmlfile .= "</PmtTpInf>\n";
            	}
            	
            	$xmlfile .= "<ReqdColltnDt>$dueDate</ReqdColltnDt>\n";      		//RequestedCollectionDate, Faelligkeitsdatum der Lastschrift
            	$xmlfile .= "<Cdtr>\n";                                           	//Creditor
                	$xmlfile .= '<Nm>'.$zempf['name']."</Nm>\n";                  	//Name, max. 70 Zeichen
            	$xmlfile .= "</Cdtr>\n";
            	$xmlfile .= "<CdtrAcct>\n";                                       	//CreditorAccount, Creditor-Konto
                	$xmlfile .= "<Id>\n";
                    	$xmlfile .= '<IBAN>'.$zempf['iban']."</IBAN>\n";
                	$xmlfile .= "</Id>\n";
            	$xmlfile .= "</CdtrAcct>\n";
            	$xmlfile .= "<CdtrAgt>\n";                                        	//CreditorAgent, Creditor-Bank
                	$xmlfile .= "<FinInstnId>\n";                                 	//FinancialInstitutionIdentification
                	if(strlen($zempf['bic']) !== 0)       						  	//ist ein BIC vorhanden?
                	{
                		$xmlfile .= '<BIC>'.$zempf['bic']."</BIC>\n";
                	}
                	else
                	{
                		$xmlfile .= "<Othr>\n";
                			$xmlfile .= "<Id>NOTPROVIDED</Id>\n";
                		$xmlfile .= "</Othr>\n";
                	}
                	$xmlfile .= "</FinInstnId>\n";
            	$xmlfile .= "</CdtrAgt>\n";
            	$xmlfile .= "<ChrgBr>SLEV</ChrgBr>\n";                            //ChargeBearer, Entgeltverrechnungsart, immer SLEV

            	// ########## CREDITOR, Zahlungsempfaenger ##############
            	$xmlfile .= "<CdtrSchmeId>\n";                                    //CreditorSchemeIdentification, Identifikation des Zahlungsempfaengers
                	$xmlfile .= "<Id>\n";                                         //Eindeutiges Identifizierungmerkmal einer Organisation oder Person
                    	$xmlfile .= "<PrvtId>\n";                                 //PrivateIdentification, Personenidentifikation
                        	$xmlfile .= "<Othr>\n";                               //OtherIdentification
                            	$xmlfile .= '<Id>'.$zempf['ci']."</Id>\n";        //Eindeutiges Identifizierungsmerkmal des Glaeubigers
                            	$xmlfile .= "<SchmeNm>\n";                        //SchemeName, Name des Identifikationsschemas
                                	$xmlfile .= "<Prtry>SEPA</Prtry>\n";          //Proprietary, immer SEPA
                            	$xmlfile .= "</SchmeNm>\n";
                        	$xmlfile .= "</Othr>\n";
                    	$xmlfile .= "</PrvtId>\n";
                	$xmlfile .= "</Id>\n";
            	$xmlfile .= "</CdtrSchmeId>\n";

            	// ######### Direct Debit Transaction Information, Lastschriften ##############
            	foreach ($zpflgt as $dummy => $zpflgtdata)                        // je Zahlungspflichtiger ein DrctDbtTxInf-Block
            	{
            		if ($dueDate == $zpflgtdata['duedate'])
            		{
                		$xmlfile .= "<DrctDbtTxInf>\n";                               //DirectDebitTransactionInformation
                    		$xmlfile .= "<PmtId>\n";                                  //PaymentIdentification, Referenzierung einer einzelnen Transaktion
                        		$xmlfile .= '<EndToEndId>'.$zpflgtdata['end2end_id']."</EndToEndId>\n";   //EndToEndIdentification
                                        //eindeutige Referenz des Zahlers (Auftraggebers). Diese Referenz
                                        //wird unveraendert durch die gesamte Kette bis zum Zahlungsempfaenger
                                        //geleitet (Ende-zu-Ende-Referenz). Ist keine Referenz vorhanden
                                        //muss die Konstante NOTPROVIDED benutzt werden.
                    		$xmlfile .= "</PmtId>\n";
                    		
                    		if (!$oneDueDateOnly)												//PmtTpInf-Block entweder hier unter DrctDbtTxInf oder unter PmtInf
                    		{
                     			$xmlfile .= "<PmtTpInf>\n";                                     //PaymentTypeInformation
                     				$xmlfile .= "<SvcLvl>\n";                                   //ServiceLevel
                     					$xmlfile .= "<Cd>SEPA</Cd>\n";                          //Code, immer SEPA
                     				$xmlfile .= "</SvcLvl>\n";
                     				$xmlfile .= "<LclInstrm>\n";                                //LocalInstrument, Lastschriftart
                     					$xmlfile .= "<Cd>CORE</Cd>\n";                          //CORE (Basislastschrift oder B2B (Firmenlastschrift)
                     				$xmlfile .= "</LclInstrm>\n";
                     				$xmlfile .= '<SeqTp>'.$zpflgtdata['sequencetype']."</SeqTp>\n";                //SequenceType
                     																//Der SequenceType gibt an, ob es sich um eine Erst-, Folge-,
                     																//Einmal- oder letztmalige Lastschrift handelt.
                     																//Zulaessige Werte: FRST, RCUR, OOFF, FNAL
                     																//Wenn <OrgnlDbtrAcct> = SMNDA und <Amdmnt-Ind> = true
                     																//dann muss dieses Feld mit FRST belegt sein.
                     			$xmlfile .= "</PmtTpInf>\n";
                    		}
                    		
                    		$xmlfile .= '<InstdAmt Ccy="EUR">'.$zpflgtdata['betrag']."</InstdAmt>\n";   //InstructedAmount (Dezimalpunkt)
                    		$xmlfile .= "<DrctDbtTx>\n";                              //DirectDebitTransaction, Angaben zum Lastschriftmandat
                        		$xmlfile .= "<MndtRltdInf>\n";                        //MandateRelated-Information, mandatsbezogene Informationen
                            		$xmlfile .= '<MndtId>'.$zpflgtdata['mandat_id']."</MndtId>\n";            //eindeutige Mandatsreferenz
                            		$xmlfile .= '<DtOfSgntr>'.$zpflgtdata['mandat_datum']."</DtOfSgntr>\n";   //Datum, zu dem das Mandat unterschrieben wurde

                            		if((strlen($zempf['orig_cdtr_name']) !== 0)
                                		|| (strlen($zempf['orig_cdtr_id']) !== 0)
                                		|| (strlen($zpflgtdata['orig_mandat_id']) !== 0)
                                		|| (strlen($zpflgtdata['orig_iban']) !== 0)
                                		|| (strlen($zpflgtdata['orig_dbtr_agent']) !== 0)) //Kennzeichnet, ob das Mandat veraendert wurde,
                            		{
                                		$xmlfile .= "<AmdmntInd>true</AmdmntInd>\n";  //AmendmentIndicator "true"
                                		$xmlfile .= "<AmdmntInfDtls>\n";              //AmendmentInformationDetails, Pflichtfeld, falls <AmdmntInd>=true

                                		if(strlen($zpflgtdata['orig_mandat_id']) !== 0)        //Kennzeichnet, ob das Mandat veraendert wurde,
                                		{
                                    		$xmlfile .= '<OrgnlMndtId>'.$zpflgtdata['orig_mandat_id']."</OrgnlMndtId>\n";
                                		}

                                		if((strlen($zempf['orig_cdtr_name']) !== 0) || (strlen($zempf['orig_cdtr_id']) !== 0))                //Kennzeichnet, ob das Mandat veraendert wurde,
                                		{
                                    		$xmlfile .= "<OrgnlCdtrSchmeId>\n";       //Identifikation des Zahlungsempfaengers
                                    		if(strlen($zempf['orig_cdtr_name']) !== 0) //Kennzeichnet, ob das Mandat veraendert wurde,
                                    		{
                                        		$xmlfile .= '<Nm>'.$zempf['orig_cdtr_name']."</Nm>\n";
                                    		}
                                    		if(strlen($zempf['orig_cdtr_id']) !== 0)
                                    		{
                                        		$xmlfile .= "<Id>\n";
                                            		$xmlfile .= "<PrvtId>\n";
                                                		$xmlfile .= "<Othr>\n";
                                                    		$xmlfile .= '<Id>'.$zempf['orig_cdtr_id']."</Id>\n";
                                                    		$xmlfile .= "<SchmeNm>\n";
                                                        		$xmlfile .= "<Prtry>SEPA</Prtry>\n";
                                                    		$xmlfile .= "</SchmeNm>\n";
                                                		$xmlfile .= "</Othr>\n";
                                            		$xmlfile .= "</PrvtId>\n";
                                        		$xmlfile .= "</Id>\n";
                                    		}
                                    		$xmlfile .= "</OrgnlCdtrSchmeId>\n";
                                		}

                                		if(strlen($zpflgtdata['orig_iban']) !== 0 || strlen($zpflgtdata['orig_dbtr_agent']) !== 0)             //Kennzeichnet, ob das Mandat veraendert wurde,
                                		{
                                    		$xmlfile .= "<OrgnlDbtrAcct>\n";
                                        		$xmlfile .= "<Id>\n";
                                        			$xmlfile .= "<Othr>\n";
                                        				$xmlfile .= "<Id>SMNDA</Id>\n";
                                        			$xmlfile .= "</Othr>\n";
                                        		$xmlfile .= "</Id>\n";
                                    		$xmlfile .= "</OrgnlDbtrAcct>\n";
                                		}

                                	/*	if(strlen($zpflgtdata['orig_dbtr_agent']) !== 0)       //Kennzeichnet, ob das Mandat veraendert wurde,
                                		{
                                    		$xmlfile .= "<OrgnlDbtrAgt>\n";
                                        		$xmlfile .= "<FinInstnId>\n";
                                            		$xmlfile .= "<Othr>\n";
                                                		$xmlfile .= '<Id>'.$zpflgtdata['orig_dbtr_agent']."</Id>\n";
                                            		$xmlfile .= "</Othr>\n";
                                       	 		$xmlfile .= "</FinInstnId>\n";
                                    		$xmlfile .= "</OrgnlDbtrAgt>\n";
                                		}*/

                                		$xmlfile .= "</AmdmntInfDtls>\n";
                            		}
                            		else
                            		{
                                		$xmlfile .= "<AmdmntInd>false</AmdmntInd>\n";     //AmendmentIndicator "false"
                            		}
                        		$xmlfile .= "</MndtRltdInf>\n";
                    		$xmlfile .= "</DrctDbtTx>\n";

                    		//## Kreditinstitut des Zahlers (Zahlungspflichtigen)
                        	$xmlfile .= "<DbtrAgt>\n";                                //DebtorAgent, Kreditinstitut des Zahlers (Zahlungspflichtigen)
                            	$xmlfile .= "<FinInstnId>\n";                         //FinancialInstitutionIdentification
                            	if(strlen($zpflgtdata['bic']) !== 0)       			  //ist ein BIC vorhanden?
                            	{
                                	$xmlfile .= '<BIC>'.$zpflgtdata['bic']."</BIC>\n";
                            	}
                            	else
                            	{
                                	$xmlfile .= "<Othr>\n";
                                    	$xmlfile .= "<Id>NOTPROVIDED</Id>\n";
                                	$xmlfile .= "</Othr>\n";
                            	}
                            	$xmlfile .= "</FinInstnId>\n";
                        	$xmlfile .= "</DbtrAgt>\n";

                    		$xmlfile .= "<Dbtr>\n";                                       //Zahlungspflichtiger
                        		$xmlfile .= '<Nm>'.$zpflgtdata['name']."</Nm>\n";         //Name (70)
                        		if (!empty($zpflgtdata['land']))
                        		{
                        			$xmlfile .= "<PstlAdr>\n";
                        				$xmlfile .= '<Ctry>'.$zpflgtdata['land']."</Ctry>\n";              //Zahlungspflichtigen-Adresse ist Pflicht
                        				$xmlfile .= '<AdrLine>'.$zpflgtdata['street']."</AdrLine>\n";     // bei Lastschriften ausserhalb EU/EWR
                        				$xmlfile .= '<AdrLine>'.$zpflgtdata['ort']."</AdrLine>\n";          
                        			$xmlfile .= "</PstlAdr>\n";
                        		}
                    		$xmlfile .= "</Dbtr>\n";
                    		
                    		$xmlfile .= "<DbtrAcct>\n";
                        		$xmlfile .= "<Id>\n";
                            		$xmlfile .= '<IBAN>'.$zpflgtdata['iban']."</IBAN>\n";
                        		$xmlfile .= "</Id>\n";
                    		$xmlfile .= "</DbtrAcct>\n";
                    		
                    		if(strlen($zpflgtdata['alt_name']) > 0)
                    		{
                        		$xmlfile .= "<UltmtDbtr>\n";                              //UltimateDebtor
                            		$xmlfile .= '<Nm>'.$zpflgtdata['alt_name']."</Nm>\n";
                        		$xmlfile .= "</UltmtDbtr>\n";
                    		}
                    		
                    		$xmlfile .= "<RmtInf>\n";                                     // Remittance Information, Verwendungszweck
                        		$xmlfile .= '<Ustrd>'.$zpflgtdata['text']."</Ustrd>\n";   //Unstructured, unstrukturierter Verwendungszweck(max. 140 Zeichen))
                    		$xmlfile .= "</RmtInf>\n";
                		$xmlfile .= "</DrctDbtTxInf>\n";
            		}                 							// Ende if $dueDate == $zpflgtdata['duedate']
            	}												// Ende foreach ($zpflgt as $dummy => $zpflgtdata)
        	$xmlfile .= "</PmtInf>\n";
    	}														// Ende foreach ($dueDateArr as $dueDate => $dummy), Payment Information Block
    $xmlfile .= "</CstmrDrctDbtInitn>\n";						// Ende Customer Direct Debit Transfer Initiation

    $xmlfile .= "</Document>\n";								//Ende Document

    /******************************************************************************
    * Schreibt XML-Datei
    *****************************************************************************/

    header('content-type: text/xml');
    header('Cache-Control: private'); // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
    header('Content-Transfer-Encoding: binary'); // Im Grunde ueberfluessig, hat sich anscheinend bewaehrt
    header('Cache-Control: post-check=0, pre-check=0'); // Zwischenspeichern auf Proxies verhindern
    header('Content-Disposition: attachment; filename="'.$pPreferences->config['SEPA']['dateiname'].$filename_ext.'.xml"');

    echo $xmlfile;

    die();
}
elseif (isset($_POST['btn_xml_kontroll_datei']))
{
    // Dateityp, der immer abgespeichert wird
    header('Content-Type: application/octet-stream');

    // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
    header('Cache-Control: private');

    // Im Grunde ueberfluessig, hat sich anscheinend bewaehrt
    header('Content-Transfer-Encoding: binary');

    // Zwischenspeichern auf Proxies verhindern
    header('Cache-Control: post-check=0, pre-check=0');
    header('Content-Disposition: attachment; filename="'.$pPreferences->config['SEPA']['kontroll_dateiname'].$filename_ext.'.csv"');

    $datumtemp = \DateTime::createFromFormat('Y-m-d', $payment_datum);

    echo 'SEPA-'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE')."\n\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_FILE_NAME').';'.$pPreferences->config['SEPA']['kontroll_dateiname'].$filename_ext.'.csv'."\n"
        ."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_MESSAGE_ID').';'.utf8_decode($message_id)."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_MESSAGE_DATE').';'.utf8_decode($message_datum)."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_MESSAGE_INITIATOR_NAME').';'.utf8_decode($message_initiator_name)."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_NUMBER_TRANSACTIONS').';'.utf8_decode($nbOfTxs_Msg)."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTROL_SUM').';'.utf8_decode($ctrlSum_Msg)."\n"
        ."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_PAYMENT_ID').';'.utf8_decode($payment_id)."\n"
        ."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_CREDITOR').';'.utf8_decode($zempf['name'])."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_CI').';'.utf8_decode($zempf['ci'])."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN').';'.utf8_decode($zempf['iban'])."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_BIC').';'.utf8_decode($zempf['bic'])."\n"
        ."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CI').';'.utf8_decode($zempf['orig_cdtr_id'])."\n"
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_CREDITOR').';'.utf8_decode($zempf['orig_cdtr_name'])."\n\n";

    echo $gL10n->get('PLG_MITGLIEDSBEITRAG_SERIAL_NUMBER').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_ACCOUNT_HOLDER').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_IBAN').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_BIC').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_DUEDATE').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_SEQUENCETYPE').';'        		
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_FEE').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEID').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_MANDATEDATE').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_ULTIMATE_DEBTOR').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_MANDATEID').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_IBAN').';'
        .$gL10n->get('PLG_MITGLIEDSBEITRAG_ORIG_DEBTOR_AGENT').';'
        .$gL10n->get('SYS_COUNTRY').';'
        .$gL10n->get('SYS_STREET').';'
        .$gL10n->get('SYS_CITY')."\n";

    $nr = 1;
    foreach ($zpflgt as $dummy => $zpflgtdata)
    {
        $datumDueDate = \DateTime::createFromFormat('Y-m-d', $zpflgtdata['duedate']);
        $datumMandate = \DateTime::createFromFormat('Y-m-d', $zpflgtdata['mandat_datum']);

        echo
            utf8_decode($nr).';'
            .utf8_decode($zpflgtdata['name']).';'
            .utf8_decode($zpflgtdata['iban']).';'
            .utf8_decode($zpflgtdata['bic']).';'
            .$datumDueDate->format($gPreferences['system_date']).';'
            .utf8_decode($zpflgtdata['sequencetype']).';'            		
            .utf8_decode($zpflgtdata['betrag']).';'
            .utf8_decode($zpflgtdata['text']).';'
            .utf8_decode($zpflgtdata['mandat_id']).';'
            .$datumMandate->format($gPreferences['system_date']).';'
            .utf8_decode($zpflgtdata['alt_name']).';'
            .utf8_decode($zpflgtdata['orig_mandat_id']).';'
            .utf8_decode($zpflgtdata['orig_iban']).';'
            .utf8_decode($zpflgtdata['orig_dbtr_agent']).';'
            .utf8_decode($zpflgtdata['land']).';'            		
            .utf8_decode($zpflgtdata['street']).';'            		
            .utf8_decode($zpflgtdata['ort'])
            ."\n";
        $nr += 1;
    }
    exit;
}
else
{
    exit;
}
