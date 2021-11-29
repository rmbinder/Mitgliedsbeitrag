<?php
/**
 ***********************************************************************************************
 * Rechnungs-Export fuer das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright 2004-2021 The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Hinweis:   Grundgeruest erstellt von Günter Scheuermann
 *
 * Parameters:           keine
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

//alle Mitglieder einlesen
$members = list_members(array('FIRST_NAME', 'LAST_NAME', 'STREET', 'POSTCODE', 'CITY', 'EMAIL', 'FEE'.$gCurrentOrgId, 'CONTRIBUTORY_TEXT'.$gCurrentOrgId, 'PAID'.$gCurrentOrgId, 'IBAN', 'DEBTOR'), 0);

//$rechnungs_file[] = array();
$rechnungs_file = array();
$i = 0;

//alle Mitglieder durchlaufen und aufgrund von Rollenzugehoerigkeiten die Beitraege bestimmen
foreach ($members as $member => $memberdata){
    if (empty($memberdata['IBAN'])
            &&  empty($memberdata['PAID'.$gCurrentOrgId])
            && !empty($memberdata['FEE'.$gCurrentOrgId])
            && !empty($memberdata['CONTRIBUTORY_TEXT'.$gCurrentOrgId]))
    {
        if (empty($memberdata['DEBTOR']))
        {
            $members[$member]['DEBTOR'] = $memberdata['FIRST_NAME'].' '.$memberdata['LAST_NAME'];
        }
        $rechnungs_file[$i] = array(
                'name'           => $members[$member]['DEBTOR'],     // Name of account owner.
                'street'         => $members[$member]['STREET'],
                'postcode'       => $members[$member]['POSTCODE'],
                'city'           => $members[$member]['CITY'],
                'email'          => $members[$member]['EMAIL'],
                'beitrag'        => $members[$member]['FEE'.$gCurrentOrgId],
                'beitragstext'   => $members[$member]['CONTRIBUTORY_TEXT'.$gCurrentOrgId],
        );
        $i += 1;
    }
}

if (count($rechnungs_file) > 0)
{
    // Dateityp, der immer abgespeichert wird
    header('Content-Type: application/octet-stream');

    // noetig fuer IE, da ansonsten der Download mit SSL nicht funktioniert
    header('Cache-Control: private');

    // Im Grunde ueberfluessig, hat sich anscheinend bewaehrt
    header('Content-Transfer-Encoding: binary');

    // Zwischenspeichern auf Proxies verhindern
    header('Cache-Control: post-check=0, pre-check=0');
    header('Content-Disposition: attachment; filename="'.$pPreferences->config['Rechnungs-Export']['rechnung_dateiname'].'"');

    $nr = 1;
    $sum = 0;

    //echo("name;adress;plz;ort;email;beitrag;beitragstext;summe\n");
    echo $gL10n->get('PLG_MITGLIEDSBEITRAG_SERIAL_NUMBER').';'.$gL10n->get('SYS_NAME').';'.$gL10n->get('SYS_STREET').';'.$gL10n->get('SYS_POSTCODE').';'.$gL10n->get('SYS_LOCATION').';'.$gL10n->get('SYS_EMAIL').';'.$gL10n->get('PLG_MITGLIEDSBEITRAG_FEE').';'.$gL10n->get('PLG_MITGLIEDSBEITRAG_CONTRIBUTORY_TEXT').';'.$gL10n->get('PLG_MITGLIEDSBEITRAG_SUM')."\n";
    //print_r($rechnungs_file);

    for ($x = 0; $x < (count($rechnungs_file)); $x++)
    {
        $sum += $rechnungs_file[$x]['beitrag'];
        echo
            utf8_decode($nr).';'
            .utf8_decode($rechnungs_file[$x]['name']).';'
            .utf8_decode($rechnungs_file[$x]['street']).';'
            .utf8_decode($rechnungs_file[$x]['postcode']).';'
            .utf8_decode($rechnungs_file[$x]['city']).';'
            .utf8_decode($rechnungs_file[$x]['email']).';'
            .utf8_decode($rechnungs_file[$x]['beitrag']).';'
            .utf8_decode($rechnungs_file[$x]['beitragstext']).';'
            .utf8_decode($sum)
            ."\n";
        $nr += 1;
    }
}
else
{
    // set headline of the script
    $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_FILE');

    $message = '<strong>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_EXPORT_NO_DATA').'</strong>';
    $message .= '<br/><br/>'.$gL10n->get('PLG_MITGLIEDSBEITRAG_STATEMENT_EXPORT_NO_DATA2');

    // create html page object
    $page = new HtmlPage('plg-mitgliedsbeitrag-export-bill', $headline);

    $form = new HtmlForm('export_bill_form', null, $page);
    $form->addDescription($message);
    $form->addButton('next_page', $gL10n->get('SYS_NEXT'), array('icon' => 'fa-arrow-circle-right', 'link' => SecurityUtils::encodeUrl('mitgliedsbeitrag.php', array('show_option' => 'statementexport')), 'class' => 'btn-primary'));

    $page->addHtml($form->show(false));
    $page->show();
}
//########################################################
//exit;
