<?php
/**
 ***********************************************************************************************
 * Berechnung von Individualbeiträgen
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 * 
 ***********************************************************************************************
 */

/**
 * ****************************************************************************
 * Parameters:
 *
 * mode :
 * preview - preview of the new individual contributions
 * save - save the new individual contributions
 * print - preview for printing
 *
 * ***************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Admidio\Users\Entity\User;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorized()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'preview',
        'validValues' => array(
            'preview',
            'save',
            'print'
        )
    ));

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $user = new User($gDb, $gProfileFields);

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    for ($i = 0; $i < count($pPreferences->config['individual_contributions']['desc']); $i ++) {
        if (($pPreferences->config['individual_contributions']['role'][$i] == 0) || ($pPreferences->config['individual_contributions']['amount'][$i] == '')) {
            // $getMode = 'error';

            throw new Exception('<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_WRONG_INDIVIDUAL_CONTRIBUTION') . '</strong>');
        }
    }

    if ($getMode == 'preview') // Default
    {
        // $page = new HtmlPage('plg-mitgliedsbeitrag-individualcontributions-preview', $headline);
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-individualcontributions-preview');
        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $members = array();

        // alle aktiven Mitglieder einlesen
        $members = list_members(array(
            'FIRST_NAME',
            'LAST_NAME',
            'FEE' . $gCurrentOrgId,
            'CONTRIBUTORY_TEXT' . $gCurrentOrgId
        ), 0);

        foreach ($members as $member => $memberdata) {
            $members[$member]['FEE_NEW'] = 0;
            $members[$member]['CONTRIBUTORY_TEXT_NEW'] = '';

            $user->readDataById($member);
            $user->getRoleMemberships();

            for ($i = 0; $i < count($pPreferences->config['individual_contributions']['desc']); $i ++) {

                if (! $user->isMemberOfRole((int) $pPreferences->config['individual_contributions']['role'][$i])) {
                    continue;
                }

                $multiplikator = 1;
                if ($pPreferences->config['individual_contributions']['profilefield'][$i] != '') {
                    $usfid = $pPreferences->config['individual_contributions']['profilefield'][$i];
                    $multiplikator = $user->getValue($gProfileFields->getPropertyById((int) $usfid, 'usf_name_intern'));

                    // wenn das Profilfeld leer ist, dann wäre $multiplikator = ""; mit "" kann aber nicht multipliziert werden
                    $multiplikator = ($multiplikator === "") ? 0 : $multiplikator;
                }

                $amount = $pPreferences->config['individual_contributions']['amount'][$i] * $multiplikator;

                // Einzelbeträge auf 2 Nachkommastellen runden
                $amount = round($amount, 2);

                $members[$member]['FEE_NEW'] += $amount;
                if ($pPreferences->config['individual_contributions']['short_desc'][$i] != '') {
                    $members[$member]['CONTRIBUTORY_TEXT_NEW'] .= ' ' . $pPreferences->config['individual_contributions']['short_desc'][$i] . ' ' . $amount . ' ';
                }
            }

            // Gesamtbetrag auf 2 Nachkommastellen runden
            $members[$member]['FEE_NEW'] = round($members[$member]['FEE_NEW'], 2);

            // ggf. abrunden
            if ($pPreferences->config['Beitrag']['beitrag_abrunden'] == true) {
                $members[$member]['FEE_NEW'] = floor($members[$member]['FEE_NEW']);
            }

            // letzte Datenaufbereitung
            if ($members[$member]['FEE_NEW'] > $pPreferences->config['Beitrag']['beitrag_mindestbetrag']) {
                $members[$member]['FEE_NEW'] += (float) $members[$member]['FEE' . $gCurrentOrgId];
                $members[$member]['CONTRIBUTORY_TEXT_NEW'] = $members[$member]['CONTRIBUTORY_TEXT' . $gCurrentOrgId] . ' ' . $members[$member]['CONTRIBUTORY_TEXT_NEW'];

                // fuehrende und nachfolgene Leerstellen im Beitragstext loeschen
                $members[$member]['CONTRIBUTORY_TEXT_NEW'] = trim($members[$member]['CONTRIBUTORY_TEXT_NEW']);
                // zwei aufeinanderfolgende Leerzeichen durch ein Leerzeichen ersetzen
                $members[$member]['CONTRIBUTORY_TEXT_NEW'] = str_replace('  ', ' ', $members[$member]['CONTRIBUTORY_TEXT_NEW']);
            } else {
                unset($members[$member]); // wenn kein neuer Beitrag errechnet wurde, dann dieses Mitglied in der Liste loeschen
            }
        }

        // save members in session (for mode write and mode print)
        $_SESSION['pMembershipFee']['individualcontributions_user'] = $members;

        $table = new DataTables($page, 'table_preview_individualcontributions');
        $table->setRowsPerPage(10);

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'left',
            'center',
            'center',
            'center',
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_FEE_NEW'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT_NEW'),
            $gL10n->get('PLG_MEMBERSHIPFEE_FEE_PREVIOUS'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT_PREVIOUS')
        );

        $data['column_width'] = array(
            '10%',
            '10%',
            '5%',
            '35%',
            '5%',
            '35%'
        );

        $listRowNumber = 1;

        foreach ($members as $member => $memberdata) {
            $user->readDataById($member);

            $columnValues = array();
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['LAST_NAME'] . '</a>';
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['FIRST_NAME'] . '</a>';
            $columnValues[] = $memberdata['FEE_NEW'];
            $columnValues[] = $memberdata['CONTRIBUTORY_TEXT_NEW'];
            $columnValues[] = $memberdata['FEE' . $gCurrentOrgId];
            $columnValues[] = $memberdata['CONTRIBUTORY_TEXT' . $gCurrentOrgId];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;
        }

        $form = new FormPresenter('individualcontributions_form', '../templates/individualcontributions.preview.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/individualcontributions.php', array(
            'mode' => 'save'
        )), $page);

        $form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array(
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

        $htmlTable = $smarty->fetch('../templates/individualcontributions.preview.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'save') {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-individualcontributions-save');
        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'bi-printer');

        $page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/individualcontributions.php', array(
            'mode' => 'print'
        )) . '", "_blank");
        });', true);

        $table = new DataTables($page, 'table_save_individualcontributions');
        $table->setRowsPerPage(10);

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'left',

            'center',
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_FEE_NEW'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT_NEW')
        );

        $data['column_width'] = array(
            '10%',
            '10%',
            '5%',
            '75%'
        );

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['individualcontributions_user'] as $member => $memberdata) {
            $user->readDataById($member);

            $columnValues = array();
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['LAST_NAME'] . '</a>';
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['FIRST_NAME'] . '</a>';
            $columnValues[] = $memberdata['FEE_NEW'];
            $columnValues[] = $memberdata['CONTRIBUTORY_TEXT_NEW'];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;

            $user->setValue('FEE' . $gCurrentOrgId, $memberdata['FEE_NEW']);
            $user->setValue('CONTRIBUTORY_TEXT' . $gCurrentOrgId, $memberdata['CONTRIBUTORY_TEXT_NEW']);
            $user->save();
        }

        $table->createJavascript(count($data['rows']), count($data['headers']));
        $table->setColumnAlignByArray($data['column_align']);

        $smarty = $page->createSmartyObject();
        $smarty->assign('l10n', $gL10n);
        $smarty->assign('classTable', 'table table-condensed table-hover');
        $smarty->assign('columnAlign', $data['column_align']);
        $smarty->assign('columnWidth', $data['column_width']);
        $smarty->assign('headers', $data['headers']);
        $smarty->assign('rows', $data['rows']);

        $htmlTable = $smarty->fetch('../templates/individualcontributions.save.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'print') {
        $headline = $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS_NEW');

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-individualcontributions-print');
        $page->setHeadline($headline);
        $page->setPrintMode();

        $page = new HtmlPage('plg-membershipfee-individualcontributions-print', $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS_NEW'));
        $page->setPrintMode();

        // data array
        $data = array(
            'headers' => array(),
            'rows' => array(),
            'column_align' => array(),
            'column_width' => array()
        );

        $data['column_align'] = array(
            'left',
            'left',
            'center',
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_FEE_NEW'),
            $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT_NEW')
        );

        $data['column_width'] = array(
            '15%',
            '15%',
            '10%',
            '60%'
        );

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['individualcontributions_user'] as $memberdata) {
            $columnValues = array();
            $columnValues[] = $memberdata['LAST_NAME'];
            $columnValues[] = $memberdata['FIRST_NAME'];
            $columnValues[] = $memberdata['FEE_NEW'];
            $columnValues[] = $memberdata['CONTRIBUTORY_TEXT_NEW'];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;
        }
        $smarty = $page->createSmartyObject();
        $smarty->assign('l10n', $gL10n);
        $smarty->assign('classTable', 'table table-condensed table-hover');
        $smarty->assign('columnAlign', $data['column_align']);
        $smarty->assign('columnWidth', $data['column_width']);
        $smarty->assign('headers', $data['headers']);
        $smarty->assign('rows', $data['rows']);

        $htmlTable = $smarty->fetch('../templates/individualcontributions.print.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

