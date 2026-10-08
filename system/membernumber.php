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
 * write - save the new member numbers
 * print - preview fpr printing
 *
 * ***************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Utils\StringUtils;
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

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // beim ersten Aufruf des Scriptes (wenn es von membership_fee aufgerufen wird), das Session-Array initialisieren/löschen
    if (StringUtils::strContains($gNavigation->getUrl(), 'membership_fee.php')) {
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

    // wurde der OK-Button in der Filter-Bar gedrückt?
    if (isset($_GET['btn_producemembernumber'])) {
        $getFormat = admFuncVariableIsValid($_GET, 'producemembernumber_format', 'string');
        $getFillGaps = isset($_GET['producemembernumber_fill_gaps']) ? 1 : 0;

        $getRoleselection = '';
        if (isset($_GET['producemembernumber_roleselection'])) {
            $tempArray = array_filter($_GET['producemembernumber_roleselection']);
            if (count($tempArray) > 0) {
                $getRoleselection = $tempArray;
            }
            unset($tempArray);
        }
    } else {
        $getFormat = isset($pPreferences->config['membernumber']['format']) ? $pPreferences->config['membernumber']['format'] : '';
        $getFillGaps = isset($pPreferences->config['membernumber']['fill_gaps']) ? $pPreferences->config['membernumber']['fill_gaps'] : 0;
        $getRoleselection = '';
    }

    // set headline of the script
    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    if ($getMode == 'preview') // Default
    {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-membernumber-preview');
        $page->setHeadline($headline);

        $form = new FormPresenter('membernumber_navbar', 'sys-template-parts/form.filter.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membernumber.php'), $page, array(
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
            'defaultValue' => $getRoleselection,
            'showContextDependentFirstEntry' => false,
            'helpTextId' => 'PLG_MEMBERSHIPFEE_PRODUCE_MEMBERNUMBER_DESC2',
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

        $membernumbers->readUserWithoutMembernumber($getRoleselection);
        $membernumbers->separateFormatSegment($getFormat);
        $membernumbers->getMembernumber($getFillGaps);

        $_SESSION['pMembershipFee']['membernumber_rol_sel'] = $getRoleselection;
        $_SESSION['pMembershipFee']['membernumber_format'] = $getFormat;
        $_SESSION['pMembershipFee']['membernumber_fill_gaps'] = $getFillGaps;

        $table = new DataTables($page, 'table_preview_membernumber');

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

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

            $form = new FormPresenter('membernumber_preview_form', '../templates/membernumber.preview.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membernumber.php', array(
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

        $errorMarker = false;
        $smarty->assign('errorMarker', $errorMarker);

        $form->addToSmarty($smarty);

        // Fetch the HTML table from our Smarty template
        $htmlTable = $smarty->fetch('../templates/membernumber.preview.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'save') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-membernumber-save');
        $page->setHeadline($headline);

        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'bi-printer');

        $page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/membernumber.php', array(
            'mode' => 'print'
        )) . '", "_blank");
        });', true);

        $table = new DataTables($page, 'table_save_membernumber');

        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

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

        $htmlTable = $smarty->fetch('../templates/membernumber.save.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'print') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-membernumber-print');

        $page->setHeadline($headline);
        $page->setPrintMode();

        $table = new DataTables($page, 'table_print_membernumer');
        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));
        $table->setMessageIfNoRowsFound('SYS_NO_ENTRIES');

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

        // Fetch the HTML table from our Smarty template
        $htmlTable = $smarty->fetch('../templates/membernumber.print.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

