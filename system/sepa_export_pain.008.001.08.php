<?php
/**
 ***********************************************************************************************
 * SEPA-Export fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright The Admidio Team
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
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\FileSystemUtils;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Users\Entity\User;
use Plugins\MembershipFee\classes\Config\ConfigTable;

require_once (__DIR__ . '/common_function.php');

// only authorized user are allowed to start this module
if (! isUserAuthorized()) {
    throw new Exception('SYS_NO_RIGHTS');
}

// Konfiguration einlesen
$pPreferences = new ConfigTable();
$pPreferences->read();

$oneDueDateOnly = false;
$user = new User($gDb, $gProfileFields);

if (! isset($_POST['duedatesepatype'])) {
    $gMessage->show($gL10n->get('PLG_MEMBERSHIPFEE_SEPA_EXPORT_NO_DATA'));
}

if (sizeof($_POST['duedatesepatype']) == 1) {
    $oneDueDateOnly = true; // es gibt nur ein Fälligkeitsdatum mit einem Sequenztyp: der PmtTpInf-Block wird im PmtInf-Block plaziert (damit KSK die XML-Datei einlesen kann)
}

// Initialize and check the parameters
$postExportFileMode = admFuncVariableIsValid($_POST, 'export_file_mode', 'string', array(
    'defaultValue' => 'xml_file',
    'validValues' => array(
        'xml_file',
        'ctl_file'
    )
));

$dueDateArr = array();
$zempf = array();
$zpflgt = array();
$nbOfTxs_Msg = 0; // Anzahl der Transaktionen innerhalb der Message
$ctrlSum_Msg = 0; // Kontrollsumme der Beträge innerhalb der Message
$now = time();
$format1 = 'Y-m-d';
$format2 = 'H:i:s';
$filename_ext = '';

foreach ($_POST['duedatesepatype'] as $dummy => $data) {
    $filename_ext .= '_' . substr($data, 0, 10) . '-' . substr($data, 10); // Erweiterung fuer den Dateinamen zusammensetzen
    $dueDateArr[substr($data, 0, 10)]['sequencetype'] = substr($data, 10); // je DueDate ein PmtInf-Block
    $dueDateArr[substr($data, 0, 10)]['nbOfTxs_PmtInf'] = 0; // Anzahl der Transaktionen innerhalb eines PmtInf-Blocks
    $dueDateArr[substr($data, 0, 10)]['ctrlSum_PmtInf'] = 0; // Kontrollsumme der Beträge innerhalb eines PmtInf-Blocks
}

$members = list_members(array(
    'FIRST_NAME',
    'LAST_NAME',
    'FEE' . $gCurrentOrgId,
    'CONTRIBUTORY_TEXT' . $gCurrentOrgId,
    'PAID' . $gCurrentOrgId,
    'STREET',
    'CITY',
    'POSTCODE',
    'DEBTOR',
    'DEBTOR_CITY',
    'DEBTOR_STREET',
    'DEBTOR_POSTCODE',
    'IBAN',
    'ORIG_IBAN',
    'BIC',
    'BANK',
    'ORIG_DEBTOR_AGENT',
    'MANDATEID' . $gCurrentOrgId,
    'ORIG_MANDATEID' . $gCurrentOrgId,
    'MANDATEDATE' . $gCurrentOrgId,
    'DUEDATE' . $gCurrentOrgId,
    'SEQUENCETYPE' . $gCurrentOrgId
), 0);

// alle Mitglieder durchlaufen und das Array $zpflgt befuellen
foreach ($members as $member => $memberdata) {
    $dueDateMember = $memberdata['DUEDATE' . $gCurrentOrgId];
    $sequenceTypeMember = (empty($memberdata['SEQUENCETYPE' . $gCurrentOrgId])) ? 'FRST' : $memberdata['SEQUENCETYPE' . $gCurrentOrgId];

    if (! empty($memberdata['FEE' . $gCurrentOrgId]) && empty($memberdata['PAID' . $gCurrentOrgId]) && ! empty($memberdata['IBAN']) && in_array($dueDateMember . $sequenceTypeMember, $_POST['duedatesepatype'])) {
        $zpflgt[$member]['duedate'] = '';
        $zpflgt[$member]['sequencetype'] = '';
        $zpflgt[$member]['name'] = '';
        $zpflgt[$member]['alt_name'] = '';
        $zpflgt[$member]['iban'] = '';
        $zpflgt[$member]['land'] = '';
        $zpflgt[$member]['street'] = '';
        $zpflgt[$member]['ort'] = '';
        $zpflgt[$member]['postcode'] = '';
        $zpflgt[$member]['bic'] = '';
        $zpflgt[$member]['mandat_id'] = '';
        $zpflgt[$member]['mandat_datum'] = '';
        $zpflgt[$member]['betrag'] = '';
        $zpflgt[$member]['text'] = '';
        $zpflgt[$member]['orig_mandat_id'] = '';
        $zpflgt[$member]['orig_iban'] = '';
        $zpflgt[$member]['orig_dbtr_agent'] = '';
        $zpflgt[$member]['end2end_id'] = '';
        $zpflgt[$member]['betrag'] = '';

        $zpflgt[$member]['duedate'] = $dueDateMember;
        $zpflgt[$member]['sequencetype'] = $sequenceTypeMember;

        if (empty($memberdata['DEBTOR'])) {
            $members[$member]['DEBTOR'] = $memberdata['FIRST_NAME'] . ' ' . $memberdata['LAST_NAME'];
            $members[$member]['DEBTOR_STREET'] = $memberdata['STREET'];
            $members[$member]['DEBTOR_CITY'] = $memberdata['CITY'];
            $members[$member]['DEBTOR_POSTCODE'] = $memberdata['POSTCODE'];
        }

        $zpflgt[$member]['name'] = substr(replace_sepadaten($members[$member]['DEBTOR']), 0, 70); // Name of account owner.
                                                                                                  // $zpflgt[$member]['alt_name'] = ''; // Zahlungspflichtiger abweichender Name
        $zpflgt[$member]['iban'] = strtoupper(str_replace(' ', '', $members[$member]['IBAN'])); // IBAN

        if (isIbanNOT_EU_EWR($zpflgt[$member]['iban'])) {
            if (empty($members[$member]['BIC'])) {
                $user->readDataById($member);
                $gMessage->show($gL10n->get('PLG_MEMBERSHIPFEE_BIC_MISSING', array(
                    '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                        'user_uuid' => $user->getValue('usr_uuid')
                    )) . '">' . $zpflgt[$member]['name'] . '</a>'
                )), $gL10n->get('SYS_ERROR'));
            }
            $zpflgt[$member]['land'] = substr($zpflgt[$member]['iban'], 0, 2);
            $zpflgt[$member]['street'] = substr(replace_sepadaten($members[$member]['DEBTOR_STREET']), 0, 70);
            $zpflgt[$member]['ort'] = substr(replace_sepadaten($members[$member]['DEBTOR_CITY']), 0, 70);
            $zpflgt[$member]['postcode'] = substr(replace_sepadaten($members[$member]['DEBTOR_POSTCODE']), 0, 16);
        }

        $zpflgt[$member]['bic'] = strtoupper($members[$member]['BIC']); // BIC
        $zpflgt[$member]['mandat_id'] = $members[$member]['MANDATEID' . $gCurrentOrgId]; // Mandats-ID
        $zpflgt[$member]['mandat_datum'] = $members[$member]['MANDATEDATE' . $gCurrentOrgId]; // Mandats-Datum

        $fee = str_replace(',', '.', $members[$member]['FEE' . $gCurrentOrgId]);
        if (strpos($fee, '.') !== false) {
            $fee = substr($fee, 0, strpos($fee, '.') + 3);
        }

        $zpflgt[$member]['betrag'] = $fee; // Amount of money
        $zpflgt[$member]['text'] = substr(replace_sepadaten($members[$member]['CONTRIBUTORY_TEXT' . $gCurrentOrgId]), 0, 140); // Description of the transaction ("Verwendungszweck").
        $zpflgt[$member]['orig_mandat_id'] = $members[$member]['ORIG_MANDATEID' . $gCurrentOrgId]; // urspruengliche Mandats-ID
        $zpflgt[$member]['orig_iban'] = strtoupper(str_replace(' ', '', $members[$member]['ORIG_IBAN'])); // urspruengliche IBAN
        $zpflgt[$member]['orig_dbtr_agent'] = $members[$member]['ORIG_DEBTOR_AGENT']; // urspruengliches Kreditinstitut, nur "SMNDA" moeglich

        $dueDateArr[$dueDateMember]['nbOfTxs_PmtInf'] ++;
        $dueDateArr[$dueDateMember]['ctrlSum_PmtInf'] += $zpflgt[$member]['betrag'];
        $ctrlSum_Msg += $zpflgt[$member]['betrag'];

        $zpflgt[$member]['end2end_id'] = substr(replace_sepadaten($gCurrentOrganization->getValue('org_shortname')) . '-' . $member . '-' . date($format1, $now), 0, 35); // SEPA End2End-ID (max. 35)
    }
}

$nbOfTxs_Msg = count($zpflgt); // SEPA Anzahl der Lastschriften

if ($nbOfTxs_Msg == 0) {
    $gMessage->show($gL10n->get('PLG_MEMBERSHIPFEE_SEPA_EXPORT_NO_DATA'));
}

$message_id = substr('Message-ID-' . replace_sepadaten($gCurrentOrganization->getValue('org_shortname')), 0, 35); // SEPA Message-ID (max. 35)
$message_datum = date($format1, $now) . 'T' . date($format2, $now) . '.000Z'; // SEPA Message-Datum z.B.: 2010-11-21T09:30:47.000Z
$message_initiator_name = substr(replace_sepadaten($pPreferences->config['Kontodaten']['inhaber']), 0, 70); // SEPA Message Initiator Name

$payment_id = 'Beitragszahlungen'; // SEPA Payment_ID (max. 35)
$payment_end2end_id = 'NOTPROVIDED'; // SEPA Payment_EndToEndIdentification

$zempf['name'] = substr(replace_sepadaten($pPreferences->config['Kontodaten']['inhaber']), 0, 70); // SEPA Zahlungsempfaenger Kontoinhaber
$zempf['ci'] = $pPreferences->config['Kontodaten']['ci']; // Organisation SEPA_ID (Glaeubiger-ID Bundesdbank)

if (isIbanNOT_EU_EWR($pPreferences->config['Kontodaten']['iban']) && empty($pPreferences->config['Kontodaten']['bic'])) {
    $gMessage->show($gL10n->get('PLG_MEMBERSHIPFEE_BIC_MISSING', array(
        $zempf['name']
    )), $gL10n->get('SYS_ERROR'));
}

$zempf['iban'] = strtoupper(str_replace(' ', '', $pPreferences->config['Kontodaten']['iban'])); // SEPA Zahlungsempfaenger IBAN
$zempf['bic'] = strtoupper($pPreferences->config['Kontodaten']['bic']); // SEPA Zahlungsempfaenger BIC
$zempf['orig_cdtr_name'] = $pPreferences->config['Kontodaten']['origcreditor']; // urspruenglicher Creditor
$zempf['orig_cdtr_id'] = $pPreferences->config['Kontodaten']['origci']; // urspruengliche Mandats-ID

if ($postExportFileMode === 'xml_file') {

    /**
     * ****************************************************************************
     * Schreibt Lastschriften in einen XML-String
     * ***************************************************************************
     */

    // DFÜ-Abkommen Version 26.11
    // Pain 008.001.008
    // ########## Document ###########
    $xml = new SimpleXMLElement("<?xml version='1.0' encoding='utf-8'?>
        <Document xmlns='urn:iso:std:iso:20022:tech:xsd:pain.008.001.08' 
        xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance' 
        xsi:schemaLocation='urn:iso:std:iso:20022:tech:xsd:pain.008.001.08 pain.008.001.08.xsd'/>");

    // ########## Customer Direct Debit Initiation ###########
    $cstmrDrctDbtInitn = $xml->addChild('CstmrDrctDbtInitn');

    // ########## Group-Header ###########
    $grpHdr = $cstmrDrctDbtInitn->addChild('GrpHdr');
    $grpHdr->addChild('MsgId', $message_id);
    $grpHdr->addChild('CreDtTm', $message_datum);
    $grpHdr->addChild('NbOfTxs', $nbOfTxs_Msg);
    $grpHdr->addChild('CtrlSum', $ctrlSum_Msg);
    $grpHdr->addChild('InitgPty');
    $grpHdr->InitgPty->addChild('Nm', $message_initiator_name);

    foreach ($dueDateArr as $dueDate => $data) // je DueDate ein PmtInf-Block
    {
        // ########## Payment Information ###########
        $pmtInf = $cstmrDrctDbtInitn->addChild('PmtInf');
        $pmtInf->addChild('PmtInfId', $payment_id); // Payment-ID
        $pmtInf->addChild('PmtMtd', 'DD'); // Payment-Methode, Lastschrift: DD
        $pmtInf->addChild('BtchBookg', 'true'); // BatchBooking, Sammelbuchung (true) oder eine Einzelbuchung handelt (false)
        $pmtInf->addChild('NbOfTxs', $data['nbOfTxs_PmtInf']); // Number of Transactions
        $pmtInf->addChild('CtrlSum', $data['ctrlSum_PmtInf']); // Control Sum

        if ($oneDueDateOnly) // es gibt nur ein Fälligkeitsdatum mit einem Sequenztyp: der PmtTpInf-Block wird im PmtInf-Block plaziert
        {
            $pmtInf->addChild('PmtTpInf'); // PaymentTypeInformation
            $pmtInf->PmtTpInf->addChild('SvcLvl'); // ServiceLevel
            $pmtInf->PmtTpInf->SvcLvl->addChild('Cd', 'SEPA'); // Code, immer SEPA
            $pmtInf->PmtTpInf->addChild('LclInstrm'); // LocalInstrument, Lastschriftart
            $pmtInf->PmtTpInf->LclInstrm->addChild('Cd', 'CORE'); // CORE (Basislastschrift oder B2B (Firmenlastschrift)
            $pmtInf->PmtTpInf->addChild('SeqTp', $data['sequencetype']); // SequenceType
                                                                         // Der SequenceType gibt an, ob es sich um eine Erst-, Folge-,
                                                                         // Einmal- oder letztmalige Lastschrift handelt.
                                                                         // Zulaessige Werte: FRST, RCUR, OOFF, FNAL
                                                                         // Wenn <OrgnlDbtrAcct> = SMNDA und <Amdmnt-Ind> = true
                                                                         // dann muss dieses Feld mit FRST belegt sein.
        }

        $pmtInf->addChild('ReqdColltnDt', $dueDate); // RequestedCollectionDate, Faelligkeitsdatum der Lastschrift
        $pmtInf->addChild('Cdtr'); // Creditor
        $pmtInf->Cdtr->addChild('Nm', $zempf['name']); // Name, max. 70 Zeichen
        $pmtInf->addChild('CdtrAcct'); // CreditorAccount, Creditor-Konto
        $pmtInf->CdtrAcct->addChild('Id');
        $pmtInf->CdtrAcct->Id->addChild('IBAN', $zempf['iban']);
        $pmtInf->addChild('CdtrAgt'); // CreditorAgent, Creditor-Bank
        $pmtInf->CdtrAgt->addChild('FinInstnId'); // FinancialInstitutionIdentification

        if (strlen($zempf['bic']) !== 0) // ist ein BIC vorhanden?
        {
            $pmtInf->CdtrAgt->FinInstnId->addChild('BICFI', $zempf['bic']);
        } else {

            $pmtInf->CdtrAgt->FinInstnId->addChild('Othr');
            $pmtInf->CdtrAgt->FinInstnId->Othr->addChild('Id', 'NOTPROVIDED');
        }

        $pmtInf->addChild('ChrgBr', 'SLEV'); // ChargeBearer, Entgeltverrechnungsart, immer SLEV

        // ########## CREDITOR, Zahlungsempfaenger ##############
        $pmtInf->addChild('CdtrSchmeId'); // CreditorSchemeIdentification, Identifikation des Zahlungsempfaengers
        $pmtInf->CdtrSchmeId->addChild('Id'); // Eindeutiges Identifizierungmerkmal einer Organisation oder Person
        $pmtInf->CdtrSchmeId->Id->addChild('PrvtId'); // PrivateIdentification, Personenidentifikation
        $pmtInf->CdtrSchmeId->Id->PrvtId->addChild('Othr'); // OtherIdentification
        $pmtInf->CdtrSchmeId->Id->PrvtId->Othr->addChild('Id', $zempf['ci']); // Eindeutiges Identifizierungsmerkmal des Glaeubigers
        $pmtInf->CdtrSchmeId->Id->PrvtId->Othr->addChild('SchmeNm'); // SchemeName, Name des Identifikationsschemas
        $pmtInf->CdtrSchmeId->Id->PrvtId->Othr->SchmeNm->addChild('Prtry', 'SEPA'); // Proprietary, immer SEPA

        // ######### Direct Debit Transaction Information, Lastschriften ##############
        foreach ($zpflgt as $dummy => $zpflgtdata) // je Zahlungspflichtiger ein DrctDbtTxInf-Block
        {
            if ($dueDate == $zpflgtdata['duedate']) {

                $drctDbtTxInf = $pmtInf->addChild('DrctDbtTxInf'); // DirectDebitTransactionInformation

                $drctDbtTxInf->addChild('PmtId'); // PaymentIdentification, Referenzierung einer einzelnen Transaktion
                $drctDbtTxInf->PmtId->addChild('EndToEndId', $zpflgtdata['end2end_id']); // EndToEndIdentification
                                                                                         // eindeutige Referenz des Zahlers (Auftraggebers). Diese Referenz
                                                                                         // wird unveraendert durch die gesamte Kette bis zum Zahlungsempfaenger
                                                                                         // geleitet (Ende-zu-Ende-Referenz). Ist keine Referenz vorhanden
                                                                                         // muss die Konstante NOTPROVIDED benutzt werden.

                if (! $oneDueDateOnly) // PmtTpInf-Block entweder hier unter DrctDbtTxInf oder unter PmtInf
                {
                    $drctDbtTxInf->addChild('PmtTpInf'); // PaymentTypeInformation
                    $drctDbtTxInf->PmtTpInf->addChild('SvcLvl'); // ServiceLevel
                    $drctDbtTxInf->PmtTpInf->SvcLvl->addChild('Cd', 'SEPA'); // Code, immer SEPA
                    $drctDbtTxInf->PmtTpInf->addChild('LclInstrm'); // LocalInstrument, Lastschriftart
                    $drctDbtTxInf->PmtTpInf->LclInstrm->addChild('Cd', 'CORE'); // CORE (Basislastschrift oder B2B (Firmenlastschrift)
                    $drctDbtTxInf->PmtTpInf->addChild('SeqTp', $zpflgtdata['sequencetype']); // SequenceType
                                                                                             // Der SequenceType gibt an, ob es sich um eine Erst-, Folge-,
                                                                                             // Einmal- oder letztmalige Lastschrift handelt.
                                                                                             // Zulaessige Werte: FRST, RCUR, OOFF, FNAL
                                                                                             // Wenn <OrgnlDbtrAcct> = SMNDA und <Amdmnt-Ind> = true
                                                                                             // dann muss dieses Feld mit FRST belegt sein.
                }

                $drctDbtTxInf->addChild('InstdAmt', $zpflgtdata['betrag']); // InstructedAmount (Dezimalpunkt)
                $drctDbtTxInf->InstdAmt->addAttribute('Ccy', 'EUR');
                $drctDbtTxInf->addChild('DrctDbtTx'); // DirectDebitTransaction, Angaben zum Lastschriftmandat
                $drctDbtTxInf->DrctDbtTx->addChild('MndtRltdInf'); // MandateRelated-Information, mandatsbezogene Informationen
                $drctDbtTxInf->DrctDbtTx->MndtRltdInf->addChild('MndtId', $zpflgtdata['mandat_id']); // eindeutige Mandatsreferenz
                $drctDbtTxInf->DrctDbtTx->MndtRltdInf->addChild('DtOfSgntr', $zpflgtdata['mandat_datum']); // Datum, zu dem das Mandat unterschrieben wurde

                if ((strlen($zempf['orig_cdtr_name']) !== 0) || (strlen($zempf['orig_cdtr_id']) !== 0) || (strlen($zpflgtdata['orig_mandat_id']) !== 0) || (strlen($zpflgtdata['orig_iban']) !== 0) || (strlen($zpflgtdata['orig_dbtr_agent']) !== 0)) // Kennzeichnet, ob das Mandat veraendert wurde,
                {
                    $drctDbtTxInf->DrctDbtTx->MndtRltdInf->addChild('AmdmntInd', 'true'); // AmendmentIndicator "true"
                    $drctDbtTxInf->DrctDbtTx->MndtRltdInf->addChild('AmdmntInfDtls'); // AmendmentInformationDetails, Pflichtfeld, falls <AmdmntInd>=true

                    if (strlen($zpflgtdata['orig_mandat_id']) !== 0) // Kennzeichnet, ob das Mandat veraendert wurde,
                    {
                        $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->addChild('OrgnlMndtId', $zpflgtdata['orig_mandat_id']);
                    }

                    if ((strlen($zempf['orig_cdtr_name']) !== 0) || (strlen($zempf['orig_cdtr_id']) !== 0)) // Kennzeichnet, ob das Mandat veraendert wurde,
                    {
                        $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->addChild('OrgnlCdtrSchmeId'); // Identifikation des Zahlungsempfaengers

                        if (strlen($zempf['orig_cdtr_name']) !== 0) // Kennzeichnet, ob das Mandat veraendert wurde,
                        {
                            $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlCdtrSchmeId->addChild('Nm', $zempf['orig_cdtr_name']);
                        }
                        if (strlen($zempf['orig_cdtr_id']) !== 0) {

                            $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlCdtrSchmeId->addChild('Id');
                            $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlCdtrSchmeId->Id->addChild('PrvtId');
                            $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlCdtrSchmeId->Id->PrvtId->addChild('Othr');
                            $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlCdtrSchmeId->Id->PrvtId->Othr->addChild('Id', $zempf['orig_cdtr_id']);
                            $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlCdtrSchmeId->Id->PrvtId->Othr->addChild('SchmeNm');
                            $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlCdtrSchmeId->Id->PrvtId->Othr->SchmeNm->addChild('Prtry', 'SEPA');
                        }
                    }

                    if (strlen($zpflgtdata['orig_iban']) !== 0 || strlen($zpflgtdata['orig_dbtr_agent']) !== 0) // Kennzeichnet, ob das Mandat veraendert wurde,
                    {

                        $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->addChild('OrgnlDbtrAcct');
                        $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlDbtrAcct->addChild('Id');
                        $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlDbtrAcct->Id->addChild('Othr');
                        $drctDbtTxInf->DrctDbtTx->MndtRltdInf->AmdmntInfDtls->OrgnlDbtrAcct->Id->Othr->addChild('Id', 'SMNDA');
                    }

                    /*
                     * if(strlen($zpflgtdata['orig_dbtr_agent']) !== 0) //Kennzeichnet, ob das Mandat veraendert wurde,
                     * {
                     * $xmlfile .= "<OrgnlDbtrAgt>\n";
                     * $xmlfile .= "<FinInstnId>\n";
                     * $xmlfile .= "<Othr>\n";
                     * $xmlfile .= '<Id>'.$zpflgtdata['orig_dbtr_agent']."</Id>\n";
                     * $xmlfile .= "</Othr>\n";
                     * $xmlfile .= "</FinInstnId>\n";
                     * $xmlfile .= "</OrgnlDbtrAgt>\n";
                     * }
                     */
                } else {

                    $drctDbtTxInf->DrctDbtTx->MndtRltdInf->addChild('AmdmntInd', 'false'); // AmendmentIndicator "false"
                }
                ;

                // ## Kreditinstitut des Zahlers (Zahlungspflichtigen)
                $drctDbtTxInf->addChild('DbtrAgt'); // DebtorAgent, Kreditinstitut des Zahlers (Zahlungspflichtigen)
                $drctDbtTxInf->DbtrAgt->addChild('FinInstnId'); // FinancialInstitutionIdentification

                if (strlen($zpflgtdata['bic']) !== 0) // ist ein BIC vorhanden?
                {
                    $drctDbtTxInf->DbtrAgt->FinInstnId->addChild('BICFI', $zpflgtdata['bic']);
                } else {

                    $drctDbtTxInf->DbtrAgt->FinInstnId->addChild('Othr');
                    $drctDbtTxInf->DbtrAgt->FinInstnId->Othr->addChild('Id', 'NOTPROVIDED');
                }

                $drctDbtTxInf->addChild('Dbtr'); // Zahlungspflichtiger
                $drctDbtTxInf->Dbtr->addChild('Nm', $zpflgtdata['name']); // Name (70)

                if (! empty($zpflgtdata['land'])) {
                    // Zahlungspflichtigen-Adresse ist Pflicht bei Lastschriften ausserhalb EU/EWR

                    $drctDbtTxInf->Dbtr->addChild('PstlAdr');
                    $drctDbtTxInf->Dbtr->PstlAdr->addChild('PstCd', $zpflgtdata['postcode']);
                    $drctDbtTxInf->Dbtr->PstlAdr->addChild('TwnNm', $zpflgtdata['ort']);
                    $drctDbtTxInf->Dbtr->PstlAdr->addChild('Ctry', $zpflgtdata['land']);
                    $drctDbtTxInf->Dbtr->PstlAdr->addChild('AdrLine', $zpflgtdata['street']);
                }

                $drctDbtTxInf->addChild('DbtrAcct');
                $drctDbtTxInf->DbtrAcct->addChild('Id');
                $drctDbtTxInf->DbtrAcct->Id->addChild('IBAN', $zpflgtdata['iban']);

                if (strlen($zpflgtdata['alt_name']) > 0) {
                    $drctDbtTxInf->addChild('UltmtDbtr'); // UltimateDebtor
                    $drctDbtTxInf->UltmtDbtr->addChild('Nm', $zpflgtdata['alt_name']);
                }

                if (strlen($zpflgtdata['text']) > 0) {

                    $drctDbtTxInf->addChild('RmtInf'); // Remittance Information, Verwendungszweck
                    $drctDbtTxInf->RmtInf->addChild('Ustrd', $zpflgtdata['text']); // Unstructured, unstrukturierter Verwendungszweck(max. 140 Zeichen))
                }
            } // Ende if $dueDate == $zpflgtdata['duedate']
        } // Ende foreach ($zpflgt as $dummy => $zpflgtdata)
    } // Ende foreach ($dueDateArr as $dueDate => $dummy), Payment Information Block

    /**
     * ****************************************************************************
     * Schreibt XML-Datei
     * ***************************************************************************
     */

    header('content-type: text/xml');
    header('Cache-Control: private'); // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
    header('Content-Transfer-Encoding: binary'); // Im Grunde ueberfluessig, hat sich anscheinend bewaehrt
    header('Cache-Control: post-check=0, pre-check=0'); // Zwischenspeichern auf Proxies verhindern
    header('Content-Disposition: attachment; filename="' . $pPreferences->config['SEPA']['dateiname'] . $filename_ext . '.xml"');

    // diese Anweisung erzeugt zwar einen wohlgeformten XML-String, er ist aber schlecht lesbar, da er in einer einzigen Zeile geschrieben ist
    // echo $xml->asXML();

    // formatierten XML-String erzeugen
    $dom = new DOMDocument('1.0');
    $dom->preserveWhiteSpace = false;
    $dom->formatOutput = true;
    $dom->loadXML($xml->asXML());
    echo $dom->saveXML();

    die();
} elseif ($postExportFileMode === 'ctl_file') {
    // initialize some special mode parameters
    $separator = '';
    $valueQuotes = '';
    $charset = '';
    $csvStr = '';
    $header = array(); // 'xlsx'
    $rows = array(); // 'xlsx'
    $filename = $pPreferences->config['SEPA']['kontroll_dateiname'] . $filename_ext;
    $exportMode = $pPreferences->config['SEPA']['kontroll_dateityp'];

    switch ($exportMode) {
        case 'csv-ms':
            $separator = ';'; // Microsoft Excel 2007 or new needs a semicolon
            $valueQuotes = '"'; // all values should be set with quotes
            $exportMode = 'csv';
            $charset = 'iso-8859-1';
            break;
        case 'csv-oo':
            $separator = ','; // a CSV file should have a comma
            $valueQuotes = '"'; // all values should be set with quotes
            $exportMode = 'csv';
            $charset = 'utf-8';
            break;
        case 'xlsx':
            include_once (__DIR__ . '/../libs/PHP_XLSXWriter/xlsxwriter.class.php');
            $exportMode = 'xlsx';
            break;
        default:
            break;
    }

    $filename = FileSystemUtils::getSanitizedPathEntry($filename) . '.' . $exportMode;

    $rows[] = array(
        'SEPA-' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTROL_FILE')
    );
    $rows[] = array(
        ''
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_CONTROL_FILE_NAME'),
        $filename
    );
    $rows[] = array(
        ''
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_MESSAGE_ID'),
        $message_id
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_MESSAGE_DATE'),
        $message_datum
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_MESSAGE_INITIATOR_NAME'),
        $message_initiator_name
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_NUMBER_TRANSACTIONS'),
        $nbOfTxs_Msg
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_CONTROL_SUM'),
        $ctrlSum_Msg
    );
    $rows[] = array(
        ''
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_PAYMENT_ID'),
        $payment_id
    );
    $rows[] = array(
        ''
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_CREDITOR'),
        $zempf['name']
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_CI'),
        $zempf['ci']
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_IBAN'),
        $zempf['iban']
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_BIC'),
        $zempf['bic']
    );
    $rows[] = array(
        ''
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_CI'),
        $zempf['orig_cdtr_id']
    );
    $rows[] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_CREDITOR'),
        $zempf['orig_cdtr_name']
    );
    $rows[] = array(
        ''
    );

    $columnValues = array();
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_SERIAL_NUMBER');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_HOLDER');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_IBAN');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_BIC');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_SEQUENCETYPE');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_FEE');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MANDATEID');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MANDATEDATE');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ULTIMATE_DEBTOR');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_MANDATEID');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_IBAN');
    $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_DEBTOR_AGENT');
    $columnValues[] = $gL10n->get('SYS_COUNTRY');
    $columnValues[] = $gL10n->get('SYS_STREET');
    $columnValues[] = $gL10n->get('SYS_POSTCODE');
    $columnValues[] = $gL10n->get('SYS_CITY');
    $rows[] = $columnValues;

    if ($exportMode === 'csv') {
        foreach ($rows as $row => $cols) {
            for ($i = 0; $i < (sizeof($cols)); $i ++) {
                if ($i !== 0) {
                    $csvStr .= $separator;
                }
                $csvStr .= $valueQuotes . $cols[$i] . $valueQuotes;
            }
            $csvStr .= "\n";
        }
        $csvStr .= "\n";
    }

    $nr = 1;
    foreach ($zpflgt as $dummy => $zpflgtdata) {
        $datumDueDate = \DateTime::createFromFormat('Y-m-d', $zpflgtdata['duedate']);
        $datumMandate = \DateTime::createFromFormat('Y-m-d', $zpflgtdata['mandat_datum']);

        $columnValues = array();
        $columnValues[] = $nr;
        $columnValues[] = $zpflgtdata['name'];
        $columnValues[] = $zpflgtdata['iban'];
        $columnValues[] = $zpflgtdata['bic'];
        $columnValues[] = $datumDueDate->format($gSettingsManager->getString('system_date'));
        $columnValues[] = $zpflgtdata['sequencetype'];
        $columnValues[] = $zpflgtdata['betrag'];
        $columnValues[] = $zpflgtdata['text'];
        $columnValues[] = $zpflgtdata['mandat_id'];
        $columnValues[] = $datumMandate->format($gSettingsManager->getString('system_date'));
        $columnValues[] = $zpflgtdata['alt_name'];
        $columnValues[] = $zpflgtdata['orig_mandat_id'];
        $columnValues[] = $zpflgtdata['orig_iban'];
        $columnValues[] = $zpflgtdata['orig_dbtr_agent'];
        $columnValues[] = $zpflgtdata['land'];
        $columnValues[] = $zpflgtdata['street'];
        $columnValues[] = $zpflgtdata['postcode'];
        $columnValues[] = $zpflgtdata['ort'];

        if ($exportMode === 'csv') {
            for ($i = 0; $i < (sizeof($columnValues)); $i ++) {
                if ($i !== 0) {
                    $csvStr .= $separator;
                }
                $csvStr .= $valueQuotes . $columnValues[$i] . $valueQuotes;
            }
            $csvStr .= "\n";
        } elseif ($exportMode === 'xlsx') {
            $rows[] = $columnValues;
        }
        $nr += 1;
    }

    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // neccessary for IE6 to 8, because without it the download with SSL has problems
    header('Cache-Control: private');
    header('Pragma: public');

    if ($exportMode === 'csv') {
        // download CSV file
        header('Content-Type: text/comma-separated-values; charset=' . $charset);

        if ($charset === 'iso-8859-1') {
            echo utf8_decode($csvStr);
        } else {
            echo $csvStr;
        }
    } elseif ($exportMode === 'xlsx') {
        header('Content-disposition: attachment; filename="' . XLSXWriter::sanitize_filename($filename) . '"');
        header("Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
        header('Content-Transfer-Encoding: binary');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $writer = new XLSXWriter();
        $writer->setAuthor($gCurrentUser->getValue('FIRST_NAME') . ' ' . $gCurrentUser->getValue('LAST_NAME'));
        $writer->setTitle($filename);
        $writer->setSubject($gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE'));
        $writer->setCompany($gCurrentOrganization->getValue('org_longname'));
        $writer->setKeywords(array(
            $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PAYMENTS'),
            $gL10n->get('PLG_MEMBERSHIPFEE_SEPA')
        ));
        $writer->setDescription($gL10n->get('PLG_MEMBERSHIPFEE_CREATED_WITH'));
        $writer->writeSheet($rows, '', $header);
        $writer->writeToStdOut();
    }

    exit();
} else {
    $gMessage->show($gL10n->get('SYS_INVALID_PAGE_VIEW'));
}
