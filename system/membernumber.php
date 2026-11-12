<?php
/**
 ***********************************************************************************************
 * Dieses Plugin generiert fuer aktive Mitglieder der aktuellen Organisation eine Mitgliedsnummer.
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
 * mode : preview - preview of the new member numbers
 * save - save the new member numbers
 * print - preview fpr printing
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
use Plugins\MembershipFee\classes\Service\Membernumbers;

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

    // beim ersten Aufruf des Scriptes, das Session-Array initialisieren/löschen
    if (strpos($gNavigation->getUrl(), 'membernumber.php') === false) {
        $_SESSION['pMembershipFee']['membernumber_user'] = array();
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

    $tableId = 'table_' . $getMode . '_membernumber';

    // wurde der OK-Button in der Filter-Bar gedrückt?
    if (isset($_GET['btn_producemembernumber'])) {
        $getFormat = admFuncVariableIsValid($_GET, 'producemembernumber_format', 'string');
        $getFillGaps = isset($_GET['producemembernumber_fill_gaps']) ? 1 : 0;
    } else {
        $getFormat = $pPreferences->config['membernumber']['format'] ?? '';
        $getFillGaps = $pPreferences->config['membernumber']['fill_gaps'] ?? 0;
    }

    $getRoleSelection = admFuncVariableIsValid($_GET, 'producemembernumber_roleselection', 'array');
    // wenn alle Einträge in einer Rollenauswahl gelöscht wurden, wird ein Array mit einem leeren Eintrag übergeben
    // dies führt im weiteren Verlauf des Scripts zu Fehlern
    if (is_array($getRoleSelection)) {
        // deshalb leere Einträge löschen
        $getRoleSelection = array_filter($getRoleSelection);
    }

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    // Default
    if ($getMode == 'preview') {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-membernumber-preview');

        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $form = new FormPresenter('membernumber_navbar_form', '../templates/common.navbar.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membernumber.php'), $page, array(
            'type' => 'navbar',
            'setFocus' => false
        ));

        $selectBoxEntriesAlleRollen = 'SELECT rol_id, rol_name, cat_name
          						 FROM ' . TBL_ROLES . '
    					   INNER JOIN ' . TBL_CATEGORIES . '
                                   ON cat_id = rol_cat_id
                                WHERE rol_valid   = true
                                  AND (  cat_org_id  = ' . $gCurrentOrgId . '
                                   OR cat_org_id IS NULL )
                             ORDER BY cat_sequence, rol_name';

        $form->addSelectBoxFromSql('producemembernumber_roleselection', '', $gDb, $selectBoxEntriesAlleRollen, array(
            'defaultValue' => $getRoleSelection,
            'showContextDependentFirstEntry' => false,
            'helpTextId' => '<b>' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_SELECTION') . '</b><br/><br/>' . $gL10n->get('PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER_DESC2'),
            'multiselect' => true
        ));
        $form->addInput('producemembernumber_format', $gL10n->get('PLG_MEMBERSHIPFEE_FORMAT'), $getFormat, array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_FORMAT_DESC'
        ));
        $form->addCheckbox('producemembernumber_fill_gaps', $gL10n->get('PLG_MEMBERSHIPFEE_FILL_GAPS'), $getFillGaps, array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_FILL_GAPS_DESC'
        ));

        $form->addSubmitButton('btn_producemembernumber', $gL10n->get('SYS_OK'), array(
            'icon' => 'bi-calculator'
        ));

        $form->addToHtmlPage();

        $membernumbers = new Membernumbers($gDb);

        if ($membernumbers->isDoubleNumber()) {
            $gMessage->show($gL10n->get('PLG_MEMBERSHIPFEE_MEMBERNUMBER_ERROR', array(
                $membernumbers->isDoubleNumber()
            )));
            // --> EXIT
        }

        $membernumbers->readUserWithoutMembernumber($getRoleSelection);
        $membernumbers->separateFormatSegment($getFormat);
        $membernumbers->getMembernumber($getFillGaps);

        $_SESSION['pMembershipFee']['membernumber_rol_sel'] = $getRoleSelection;
        $_SESSION['pMembershipFee']['membernumber_format'] = $getFormat;
        $_SESSION['pMembershipFee']['membernumber_fill_gaps'] = $getFillGaps;

        $table = new DataTables($page, $tableId);

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('PLG_MEMBERSHIPFEE_MEMBERNUMBER_NO_ASSIGN');

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
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERNUMBER_NEW')
        );

        $data['column_width'] = array(
            '25%',
            '25%',
            '50%'
        );

        if ($membernumbers->userWithoutMembernumberExist) {

            // save new membernumbers in session (for mode write and mode print)
            $_SESSION['pMembershipFee']['membernumber_user'] = $membernumbers->mUserWithoutMembernumber;

            $listRowNumber = 1;
            foreach ($membernumbers->mUserWithoutMembernumber as $memberdata) {
                $columnValues = array();

                $columnValues[] = $memberdata['last_name'];
                $columnValues[] = $memberdata['first_name'];
                $columnValues[] = $memberdata['membernumber'];

                $data['rows'][] = array(
                    'id' => 'row-' . $listRowNumber,
                    'data' => $columnValues
                );

                ++ $listRowNumber;
            }

            $form = new FormPresenter('membernumber_form', '../templates/membernumber.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membernumber.php', array(
                'mode' => 'save'
            )), $page);

            $form->addSubmitButton('btn_next_page', $gL10n->get('SYS_SAVE'), array(
                'icon' => 'bi-check-lg',
                'class' => 'btn-primary'
            ));
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
        $smarty->assign('mode', $getMode);
        $smarty->assign('tableId', $tableId);

        $form->addToSmarty($smarty);
        $gCurrentSession->addFormObject($form);

        $htmlTable = $smarty->fetch('../templates/membernumber.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'save') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-membernumber-save');

        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'bi-printer');

        $page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membernumber.php', array(
            'mode' => 'print'
        )) . '", "_blank");
        });', true);

        $table = new DataTables($page, $tableId);

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('PLG_MEMBERSHIPFEE_MEMBERNUMBER_NO_ASSIGN');

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
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERNUMBER_NEW')
        );

        $data['column_width'] = array(
            '25%',
            '25%',
            '50%'
        );

        $user = new User($gDb, $gProfileFields);

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['membernumber_user'] as $memberdata) {
            $columnValues = array();
            $columnValues[] = $memberdata['last_name'];
            $columnValues[] = $memberdata['first_name'];
            $columnValues[] = $memberdata['membernumber'];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;

            $user->readDataById($memberdata['usr_id']);
            $user->setValue('MEMBERNUMBER' . $gCurrentOrgId, $memberdata['membernumber']);
            $user->save();
        }

        // save the format string in database
        $pPreferences->config['membernumber']['format'] = $_SESSION['pMembershipFee']['membernumber_format'];
        $pPreferences->config['membernumber']['fill_gaps'] = $_SESSION['pMembershipFee']['membernumber_fill_gaps'];
        $pPreferences->save();

        $table->createJavascript(count($data['rows']), count($data['headers']));
        $table->setColumnAlignByArray($data['column_align']);

        $smarty = $page->createSmartyObject();
        $smarty->assign('l10n', $gL10n);
        $smarty->assign('classTable', 'table table-condensed table-hover');
        $smarty->assign('columnAlign', $data['column_align']);
        $smarty->assign('columnWidth', $data['column_width']);
        $smarty->assign('headers', $data['headers']);
        $smarty->assign('rows', $data['rows']);
        $smarty->assign('mode', $getMode);
        $smarty->assign('tableId', $tableId);

        $htmlTable = $smarty->fetch('../templates/membernumber.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'print') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-membernumber-print');

        $page->setHeadline($headline);
        $page->setPrintMode();

        $table = new DataTables($page, $tableId);
        // $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('PLG_MEMBERSHIPFEE_MEMBERNUMBER_NO_ASSIGN');

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
            'center'
        );

        $data['headers'] = array(
            $gL10n->get('SYS_LASTNAME'),
            $gL10n->get('SYS_FIRSTNAME'),
            $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERNUMBER_NEW')
        );

        $data['column_width'] = array(
            '25%',
            '25%',
            '50%'
        );

        $listRowNumber = 1;

        foreach ($_SESSION['pMembershipFee']['membernumber_user'] as $memberdata) {
            $columnValues = array();
            $columnValues[] = $memberdata['last_name'];
            $columnValues[] = $memberdata['first_name'];
            $columnValues[] = $memberdata['membernumber'];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;
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
        $smarty->assign('mode', $getMode);
        $smarty->assign('tableId', $tableId);

        $htmlTable = $smarty->fetch('../templates/membernumber.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

