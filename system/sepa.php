<?php
/**
 ***********************************************************************************************
 * Auswahl von Fälligkeitsdatum und Sequenztyp für den SEPA-Export für das Admidio-Plugin Mitgliedsbeitrag
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:  none
 *
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorized()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // CSRF protection: reject the request if the token is missing or invalid
    checkCsrfToken();

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $duedates = array();
    $directdebittype = false;

    // alle Mitglieder einlesen
    $members = list_members(array(
        'DUEDATE' . $gCurrentOrgId,
        'SEQUENCETYPE' . $gCurrentOrgId,
        'CONTRIBUTORY_TEXT' . $gCurrentOrgId,
        'PAID' . $gCurrentOrgId,
        'FEE' . $gCurrentOrgId,
        'MANDATEID' . $gCurrentOrgId,
        'MANDATEDATE' . $gCurrentOrgId,
        'IBAN',
        'BIC'
    ), 0);

    // jetzt wird gezaehlt
    foreach ($members as $member => $memberdata) {
        // alle Faelligkeitsdaten einlesen
        if (! empty($memberdata['DUEDATE' . $gCurrentOrgId]) && ! empty($memberdata['FEE' . $gCurrentOrgId]) && empty($memberdata['PAID' . $gCurrentOrgId]) && 
        // && !empty($memberdata['CONTRIBUTORY_TEXT'.$gCurrentOrgId])
        ! empty($memberdata['IBAN'])) {
            $directdebittype = true;

            if (! isset($duedates[$memberdata['DUEDATE' . $gCurrentOrgId]])) {
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]] = array();
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['FNAL'] = 0;
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['RCUR'] = 0;
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['OOFF'] = 0;
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['FRST'] = 0;
            }

            if ($memberdata['SEQUENCETYPE' . $gCurrentOrgId] == 'FNAL') {
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['FNAL'] ++;
            } elseif ($memberdata['SEQUENCETYPE' . $gCurrentOrgId] == 'RCUR') {
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['RCUR'] ++;
            } elseif ($memberdata['SEQUENCETYPE' . $gCurrentOrgId] == 'OOFF') {
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['OOFF'] ++;
            } else {
                $duedates[$memberdata['DUEDATE' . $gCurrentOrgId]]['FRST'] ++;
            }
        }
    }
    unset($members);

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_SEPA') . ' - ' . $gL10n->get('PLG_MEMBERSHIPFEE_EXPORT');
    $gNavigation->addUrl(CURRENT_URL, $headline);

    $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-sepa');
    $page->setHeadline($headline);

    $table = new DataTables($page, 'table_sepa');

    // data array
    $data = array(
        'headers' => array(),
        'rows' => array(),
        'column_align' => array(),
        'column_width' => array()
    );

    $data['column_align'] = array(
        'center',
        'center',
        'center',
        'center',
        'center'
    );

    $data['headers'] = array(
        $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE'),
        'FRST',
        'RCUR',
        'FNAL',
        'OOFF'
    );

    $data['column_width'] = array(
        '40%',
        '15%',
        '15%',
        '15%',
        '15%'
    );

    $listRowNumber = 1;
    foreach ($duedates as $duedate => $duedatedata) {
        $datumtemp = \DateTime::createFromFormat('Y-m-d', $duedate);
        $columnValues = array();

        $columnValues[] = $datumtemp->format($gSettingsManager->getString('system_date'));
        $columnValues[] = '<input type="checkbox" name="duedatesepatype[]" ' . (($duedatedata['FRST'] === 0) ? ' disabled="disabled" ' : '') . 'value="' . $duedate . 'FRST" /><small> (' . $duedatedata['FRST'] . ')</small>';
        $columnValues[] = '<input type="checkbox" name="duedatesepatype[]" ' . (($duedatedata['RCUR'] === 0) ? ' disabled="disabled" ' : '') . 'value="' . $duedate . 'RCUR" /><small> (' . $duedatedata['RCUR'] . ')</small>';
        $columnValues[] = '<input type="checkbox" name="duedatesepatype[]" ' . (($duedatedata['FNAL'] === 0) ? ' disabled="disabled" ' : '') . 'value="' . $duedate . 'FNAL" /><small> (' . $duedatedata['FNAL'] . ')</small>';
        $columnValues[] = '<input type="checkbox" name="duedatesepatype[]" ' . (($duedatedata['OOFF'] === 0) ? ' disabled="disabled" ' : '') . 'value="' . $duedate . 'OOFF" /><small> (' . $duedatedata['OOFF'] . ')</small>';

        $data['rows'][] = array(
            'id' => 'row-' . $listRowNumber,
            'data' => $columnValues
        );

        ++ $listRowNumber;
    }

    $exportActionFile = 'sepa_export.php';
    if ($pPreferences->config['SEPA']['direct_debit_format'] === 'v8') {
        $exportActionFile = 'sepa_export_pain.008.001.08.php';
    }
    $form = new FormPresenter('sepa_form', '../templates/sepa.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/' . $exportActionFile), $page);

    $form->addDescription('selection_desc', $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE_SELECTION_DESC'));

    $form->addRadioButton('export_file_mode', '', array(
        'xml_file' => $gL10n->get('PLG_MEMBERSHIPFEE_XML_FILE'),
        'ctl_file' => $gL10n->get('PLG_MEMBERSHIPFEE_CONTROL_FILE')
    ), array(
        'defaultValue' => 'xml_file'
    ));

    $html = '<div class="alert alert-warning alert-small" role="alert"><i class="bi bi-exclamation-triangle"></i>' . $gL10n->get('PLG_MEMBERSHIPFEE_SEPA_EXPORT_INFO') . '</div>';
    $form->addDescription('selection_alert', $html);

    $form->addSubmitButton('adm_button_save', $gL10n->get('PLG_MEMBERSHIPFEE_CREATE_EXPORT_FILE'), array(
        'icon' => 'bi-check-lg'
    ));

    $table->createJavascript(count($data['rows']), count($data['headers']));
    $table->setColumnAlignByArray($data['column_align']);

    $smarty = $page->createSmartyObject();
    $smarty->assign('l10n', $gL10n);
    $smarty->assign('classTable', 'table table-condensed table-hover');

    $smarty->assign('columnAlign', $data['column_align']);
    $smarty->assign('columnWidth', $data['column_width']);
    $smarty->assign('headers', $data['headers']);
    $smarty->assign('rows', $data['rows']);

    $form->addToSmarty($smarty);
    $gCurrentSession->addFormObject($form);

    $htmlTable = $smarty->fetch('../templates/sepa.plugin.membershipfee.tpl');
    $page->addHtml($htmlTable);

    $page->show();
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}
