<?php
/**
 ***********************************************************************************************
 * Detailled installation routine for the Admidio plugin Mitgliedsbeitrag / Membership fee
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode         : start     - Startbildschirm anzeigen
 *                anlegen   - Fehlende Kategorien und Profilfelder anlegen
 *                soll_ist  - Anzeige des Soll/Ist-Vergleiches
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Entity\Entity;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\UI\Component\DataTables;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only administrators are allowed to start this module
    if (! $gCurrentUser->isAdministrator()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // CSRF protection: reject the request if the token is missing or invalid
    checkCsrfToken();

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'start',
        'validValues' => array(
            'start',
            'anlegen',
            'soll_ist'
        )
    ));

    $tableId = 'table_' . $getMode . '_install_db';

    if ($getMode == 'anlegen') {
        $arr = check_DB();

        // pruefen, ob es die Kategorie Mitgliedschaft gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_CATEGORIES']['Mitgliedschaft']['cat_id'])) {
            $nextCatSequence = getNextCatSequence('USF');
            setCategory($arr['SOLL']['TBL_CATEGORIES']['Mitgliedschaft'], $nextCatSequence);
        }

        $cat_id_mitgliedschaft = getCat_IDPMB('MEMBERSHIP' . $gCurrentOrgId);
        $nextFieldSequence = getNextFieldSequence($cat_id_mitgliedschaft);

        // pruefen, ob es das Profilfeld Mitgliedsnummer gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Mitgliedsnummer']['usf_name'])) {
            setUserField($cat_id_mitgliedschaft, $arr['SOLL']['TBL_USER_FIELDS']['Mitgliedsnummer'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Beitritt gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Beitritt']['usf_name'])) {
            setUserField($cat_id_mitgliedschaft, $arr['SOLL']['TBL_USER_FIELDS']['Beitritt'], $nextFieldSequence);
        }

        // pruefen, ob es die Kategorie Mitgliedsbeitrag gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_CATEGORIES']['Mitgliedsbeitrag']['cat_id'])) {
            $nextCatSequence = getNextCatSequence('USF');
            setCategory($arr['SOLL']['TBL_CATEGORIES']['Mitgliedsbeitrag'], $nextCatSequence);
        }

        $cat_id_mitgliedsbeitrag = getCat_IDPMB('MEMBERSHIP_FEE' . $gCurrentOrgId);
        $nextFieldSequence = getNextFieldSequence($cat_id_mitgliedsbeitrag);

        // pruefen, ob es das Profilfeld Bezahlt gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Bezahlt']['usf_name'])) {
            setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Bezahlt'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Beitrag gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Beitrag']['usf_name'])) {
            setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Beitrag'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Beitragstext gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Beitragstext']['usf_name'])) {
            setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Beitragstext'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Sequenztyp gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Sequenztyp']['usf_name'])) {
            setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Sequenztyp'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Faelligkeitsdatum gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Faelligkeitsdatum']['usf_name'])) {
            setUserField($cat_id_mitgliedsbeitrag, $arr['SOLL']['TBL_USER_FIELDS']['Faelligkeitsdatum'], $nextFieldSequence);
        }

        // pruefen, ob es die Kategorie Mandat gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_CATEGORIES']['Mandat']['cat_id'])) {
            $nextCatSequence = getNextCatSequence('USF');
            setCategory($arr['SOLL']['TBL_CATEGORIES']['Mandat'], $nextCatSequence);
        }

        $cat_id_mandat = getCat_IDPMB('MANDATE' . $gCurrentOrgId);
        $nextFieldSequence = getNextFieldSequence($cat_id_mandat);

        // pruefen, ob es das Profilfeld Mandatsreferenz gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Mandatsreferenz']['usf_name'])) {
            setUserField($cat_id_mandat, $arr['SOLL']['TBL_USER_FIELDS']['Mandatsreferenz'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Mandatsdatum gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Mandatsdatum']['usf_name'])) {
            setUserField($cat_id_mandat, $arr['SOLL']['TBL_USER_FIELDS']['Mandatsdatum'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Orig_Mandatsreferenz gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Orig_Mandatsreferenz']['usf_name'])) {
            setUserField($cat_id_mandat, $arr['SOLL']['TBL_USER_FIELDS']['Orig_Mandatsreferenz'], $nextFieldSequence);
        }

        // pruefen, ob es die Kategorie Kontodaten gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_CATEGORIES']['Kontodaten']['cat_id'])) {
            $nextCatSequence = getNextCatSequence('USF');
            setCategory($arr['SOLL']['TBL_CATEGORIES']['Kontodaten'], $nextCatSequence);
        }

        $cat_id_kontodaten = getCat_IDPMB('ACCOUNT_DATA');
        $nextFieldSequence = getNextFieldSequence($cat_id_kontodaten);

        // pruefen, ob es das Profilfeld IBAN gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['IBAN']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['IBAN'], $nextFieldSequence);
            $nextFieldSequence ++;
        }
        // pruefen, ob es das Profilfeld BIC gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['BIC']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['BIC'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Bankname gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Bankname']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Bankname'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Kontoinhaber gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Kontoinhaber']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Kontoinhaber'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld KontoinhaberStrasse gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberStrasse']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberStrasse'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld KontoinhaberPLZ gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberPLZ']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberPLZ'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld KontoinhaberOrt gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberOrt']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberOrt'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld KontoinhaberEMail gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberEMail']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['KontoinhaberEMail'], $nextFieldSequence);
            $nextFieldSequence ++;
        }

        // pruefen, ob es das Profilfeld Orig_Debtor_Agent gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Orig_Debtor_Agent']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Orig_Debtor_Agent'], $nextFieldSequence);
            $nextFieldSequence ++;
        }
        // pruefen, ob es das Profilfeld Orig_IBAN gibt, wenn nicht: anlegen
        if (! isset($arr['IST']['TBL_USER_FIELDS']['Orig_IBAN']['usf_name'])) {
            setUserField($cat_id_kontodaten, $arr['SOLL']['TBL_USER_FIELDS']['Orig_IBAN'], $nextFieldSequence);
        }

        if (str_contains($gNavigation->getUrl(), 'install.php')) {
            admRedirect(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php');
        }
    }

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_INSTALL_TITLE');

    if ($getMode === 'start' || $getMode === 'anlegen') // Default: start
    {
        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-installation');

        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $arr = check_DB();

        $table = new DataTables($page, $tableId);
        $table->setRowsPerPage($gSettingsManager->getInt('groups_roles_members_per_page'));

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
            $gL10n->get('SYS_CATEGORY'),
            $gL10n->get('PLG_MEMBERSHIPFEE_PROFILE_FIELD'),
            $gL10n->get('PLG_MEMBERSHIPFEE_STATUS')
        );

        $data['column_width'] = array(
            '25%',
            '25%',
            '50%'
        );

        $leer = '&nbsp;';
        $strich = '- ';

        $listRowNumber = 0;

        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP');
        $columnValues[] = $leer;
        $columnValues[] = ! (isset($arr['IST']['TBL_CATEGORIES']['Mitgliedschaft']['cat_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERNUMBER');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Mitgliedsnummer']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ACCESSION');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Beitritt']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE');
        $columnValues[] = $leer;
        $columnValues[] = ! (isset($arr['IST']['TBL_CATEGORIES']['Mitgliedsbeitrag']['cat_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('SYS_CONTRIBUTION');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Beitrag']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_PAID');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Bezahlt']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Beitragstext']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_SEQUENCETYPE');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Sequenztyp']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Faelligkeitsdatum']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );
        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE');
        $columnValues[] = $leer;
        $columnValues[] = ! (isset($arr['IST']['TBL_CATEGORIES']['Mandat']['cat_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATEID');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Mandatsreferenz']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATEDATE');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Mandatsdatum']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_MANDATEID');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Orig_Mandatsreferenz']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA');
        $columnValues[] = $leer;
        $columnValues[] = ! (isset($arr['IST']['TBL_CATEGORIES']['Kontodaten']['cat_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_IBAN');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['IBAN']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_BIC');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['BIC']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_BANK');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Bankname']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_HOLDER');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Kontoinhaber']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_STREET');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberStrasse']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_POSTCODE');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberPLZ']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_CITY');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberOrt']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_EMAIL');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberEMail']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_DEBTOR_AGENT');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Orig_Debtor_Agent']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_IBAN');
        $columnValues[] = ! (isset($arr['IST']['TBL_USER_FIELDS']['Orig_IBAN']['usf_name'])) ? '<strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_MISSING') . '</strong>' : $gL10n->get('PLG_MEMBERSHIPFEE_AVAILABLE');
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $form = new FormPresenter('installation_start_form', '../templates/install.db.plugin.membershipfee.tpl', '', $page);

        if ((! isset($arr['IST']['TBL_USER_FIELDS']['Beitritt']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Bezahlt']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Mitgliedsnummer']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Beitrag']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Beitragstext']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Faelligkeitsdatum']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['IBAN']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['BIC']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Bankname']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Kontoinhaber']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Sequenztyp']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Mandatsreferenz']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberStrasse']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberPLZ']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberOrt']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['KontoinhaberEMail']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Orig_Debtor_Agent']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Orig_IBAN']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Orig_Mandatsreferenz']['usf_name'])) || (! isset($arr['IST']['TBL_USER_FIELDS']['Mandatsdatum']['usf_name']))) {
            $form->addDescription('fields_should_be_created', $gL10n->get('PLG_MEMBERSHIPFEE_FIELDS_SHOULD_BE_CREATED'));

            $form->addButton('btnAnlegen', $gL10n->get('SYS_NEXT'), array(
                'icon' => 'bi-arrow-right-circle-fill',
                'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/install_db.php', array(
                    'mode' => 'anlegen'
                )),
                'class' => 'btn-primary'
            ));

            $form->addButton('btnAbbrechen', $gL10n->get('PLG_MEMBERSHIPFEE_ABORT'), array(
                'icon' => 'bi-door-open',
                'link' => $gHomepage,
                'class' => 'btn-primary'
            ));
        } else {
            $pPreferences = new ConfigTable();
            $pPreferences->init(); // Konfigurationstabelle anlegen

            $form->addButton('btnSollIst', $gL10n->get('SYS_NEXT'), array(
                'icon' => 'bi-arrow-right-circle-fill',
                'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/install_db.php', array(
                    'mode' => 'soll_ist'
                )),
                'class' => 'btn-primary'
            ));
        }

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

        $htmlTable = $smarty->fetch('../templates/install.db.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'soll_ist') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-installation_shall_is');

        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $arr = check_DB();

        $leer = '&nbsp;';
        $strich = '- ';

        $table = new DataTables($page, $tableId);

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
            'left',
            'left',
            'center ',
            'center',
            'center',
            'center',
            'center',
            'center',
            'center',
            'center'
        );

        $data['column_width'] = array(
            '10%',
            '10%',
            '10%',
            '10%',
            '10%',
            '10%',
            '5%',
            '5%',
            '5%',
            '5%',
            '5%',
            '5%'
        );

        $listRowNumber = 0;

        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP');
        $columnValues = array_merge($columnValues, SollIstKategorie($arr, 'Mitgliedschaft'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'subheader' => true,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERNUMBER');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Mitgliedsnummer'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ACCESSION');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Beitritt'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERSHIP_FEE');
        $columnValues = array_merge($columnValues, SollIstKategorie($arr, 'Mitgliedsbeitrag'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'subheader' => true,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('SYS_CONTRIBUTION');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Beitrag'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_PAID');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Bezahlt'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTORY_TEXT');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Beitragstext'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_SEQUENCETYPE');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Sequenztyp'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Faelligkeitsdatum'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE');
        $columnValues = array_merge($columnValues, SollIstKategorie($arr, 'Mandat'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'subheader' => true,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATEID');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Mandatsreferenz'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATEDATE');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Mandatsdatum'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_MANDATEID');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Orig_Mandatsreferenz'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA');
        $columnValues = array_merge($columnValues, SollIstKategorie($arr, 'Kontodaten'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'subheader' => true,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_IBAN');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'IBAN'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_BIC');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'BIC'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_BANK');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Bankname'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_HOLDER');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Kontoinhaber'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_STREET');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberStrasse'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_POSTCODE');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberPLZ'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_CITY');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberOrt'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_EMAIL');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'KontoinhaberEMail'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_DEBTOR_AGENT');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Orig_Debtor_Agent'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $columnValues = array();
        $columnValues[] = $leer;
        $columnValues[] = $strich . $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_IBAN');
        $columnValues = array_merge($columnValues, SollIstProfilfeld($arr, 'Orig_IBAN'));
        $data['rows'][] = array(
            'id' => 'row-' . ++ $listRowNumber,
            'data' => $columnValues
        );

        $form = new FormPresenter('installation_soll_ist_form', '../templates/install.db.plugin.membershipfee.tpl', '', $page);

        $form->addButton('btnNext', $gL10n->get('SYS_NEXT'), array(
            'icon' => 'bi-arrow-right-circle-fill',
            'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/index.php'),
            'class' => 'btn-primary'
        ));

        $smarty = $page->createSmartyObject();
        $smarty->assign('l10n', $gL10n);
        $smarty->assign('classTable', 'table table-condensed table-hover');
        $smarty->assign('columnAlign', $data['column_align']);
        $smarty->assign('columnWidth', $data['column_width']);
        $smarty->assign('headers', $data['headers']);
        $smarty->assign('rows', $data['rows']);
        $smarty->assign('mode', $getMode);
        $smarty->assign('tableId', $tableId);
        $smarty->assign('biEye', '<i class="bi bi-eye-fill" data-bs-toggle="tooltip" title="' . $gL10n->get('ORG_FIELD_NOT_HIDDEN') . '"></i>/<i class="bi bi-eye-fill admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $gL10n->get('ORG_FIELD_HIDDEN') . '"></i>');
        $smarty->assign('biKey', '<i class="bi bi-key-fill" data-bs-toggle="tooltip" data-bs-html="true" title="' . $gL10n->get('ORG_FIELD_DISABLED', array(
            $gL10n->get('SYS_RIGHT_EDIT_USER')
        )) . '"></i>/<i class="bi bi-key-fill admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $gL10n->get('ORG_FIELD_NOT_DISABLED') . '"></i>');
        $smarty->assign('biAsterisk', '<i class="bi bi-asterisk" data-bs-toggle="tooltip" title="' . $gL10n->get('SYS_REQUIRED_INPUT') . '"></i>/<i class="bi bi-asterisk admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $gL10n->get('SYS_REQUIRED_INPUT') . ': ' . $gL10n->get('SYS_NO') . '"></i>');

        $form->addToSmarty($smarty);
        $gCurrentSession->addFormObject($form);

        $htmlTable = $smarty->fetch('../templates/install.db.plugin.membershipfee.tpl');
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}

// Funktionen, die nur in diesem Script benoetigt werden

/**
 * Prueft die Datenbank auf fehlende Profilfelder und Kategorien
 *
 * @return array $DB_array
 */
function check_DB()
{
    global $gProfileFields, $gL10n;

    // $DB_array['SOLL'] beinhaltet die erforderlichen Werte fuer die Kategorien und die User Fields
    $DB_array['SOLL']['TBL_CATEGORIES']['Kontodaten'] = array(
        'cat_org_id' => 'Null',
        'cat_name' => 'PMB_ACCOUNT_DATA',
        'cat_name_intern' => 'ACCOUNT_DATA',
        'cat_type' => 'USF',
        'cat_system' => 0
    );
    $DB_array['SOLL']['TBL_CATEGORIES']['Mitgliedsbeitrag'] = array(
        'cat_org_id' => $GLOBALS['gCurrentOrgId'],
        'cat_name' => 'PMB_MEMBERSHIP_FEE',
        'cat_name_intern' => 'MEMBERSHIP_FEE' . $GLOBALS['gCurrentOrgId'],
        'cat_type' => 'USF',
        'cat_system' => 0
    );
    $DB_array['SOLL']['TBL_CATEGORIES']['Mitgliedschaft'] = array(
        'cat_org_id' => $GLOBALS['gCurrentOrgId'],
        'cat_name' => 'PMB_MEMBERSHIP',
        'cat_name_intern' => 'MEMBERSHIP' . $GLOBALS['gCurrentOrgId'],
        'cat_type' => 'USF',
        'cat_system' => 0
    );
    $DB_array['SOLL']['TBL_CATEGORIES']['Mandat'] = array(
        'cat_org_id' => $GLOBALS['gCurrentOrgId'],
        'cat_name' => 'PMB_MANDATE',
        'cat_name_intern' => 'MANDATE' . $GLOBALS['gCurrentOrgId'],
        'cat_type' => 'USF',
        'cat_system' => 0
    );

    $DB_array['SOLL']['TBL_USER_FIELDS']['IBAN'] = array(
        'usf_name' => 'PMB_IBAN',
        'usf_name_intern' => 'IBAN',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['BIC'] = array(
        'usf_name' => 'PMB_BIC',
        'usf_name_intern' => 'BIC',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Bankname'] = array(
        'usf_name' => 'PMB_BANK',
        'usf_name_intern' => 'BANK',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_BANK')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Kontoinhaber'] = array(
        'usf_name' => 'PMB_DEBTOR',
        'usf_name_intern' => 'DEBTOR',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_DEBTOR')
    );

    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberStrasse'] = array(
        'usf_name' => 'PMB_DEBTOR_STREET',
        'usf_name_intern' => 'DEBTOR_STREET',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_DEBTOR_STREET')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberPLZ'] = array(
        'usf_name' => 'PMB_DEBTOR_POSTCODE',
        'usf_name_intern' => 'DEBTOR_POSTCODE',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_DEBTOR_POSTCODE')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberOrt'] = array(
        'usf_name' => 'PMB_DEBTOR_CITY',
        'usf_name_intern' => 'DEBTOR_CITY',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_DEBTOR_CITY')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['KontoinhaberEMail'] = array(
        'usf_name' => 'PMB_DEBTOR_EMAIL',
        'usf_name_intern' => 'DEBTOR_EMAIL',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 0,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );

    $DB_array['SOLL']['TBL_USER_FIELDS']['Mitgliedsnummer'] = array(
        'usf_name' => 'PMB_MEMBERNUMBER',
        'usf_name_intern' => 'MEMBERNUMBER' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Beitritt'] = array(
        'usf_name' => 'PMB_ACCESSION',
        'usf_name_intern' => 'ACCESSION' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'DATE',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_ACCESSION')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Bezahlt'] = array(
        'usf_name' => 'PMB_PAID',
        'usf_name_intern' => 'PAID' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'DATE',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_PAID')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Beitrag'] = array(
        'usf_name' => 'PMB_FEE',
        'usf_name_intern' => 'FEE' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'DECIMAL',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_FEE')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Beitragstext'] = array(
        'usf_name' => 'PMB_CONTRIBUTORY_TEXT',
        'usf_name_intern' => 'CONTRIBUTORY_TEXT' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_CONTRIBUTORY_TEXT')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Mandatsreferenz'] = array(
        'usf_name' => 'PMB_MANDATEID',
        'usf_name_intern' => 'MANDATEID' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Mandatsdatum'] = array(
        'usf_name' => 'PMB_MANDATEDATE',
        'usf_name_intern' => 'MANDATEDATE' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'DATE',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Faelligkeitsdatum'] = array(
        'usf_name' => 'PMB_DUEDATE',
        'usf_name_intern' => 'DUEDATE' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'DATE',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Sequenztyp'] = array(
        'usf_name' => 'PMB_SEQUENCETYPE',
        'usf_name_intern' => 'SEQUENCETYPE' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => ''
    );

    $DB_array['SOLL']['TBL_USER_FIELDS']['Orig_Debtor_Agent'] = array(
        'usf_name' => 'PMB_ORIG_DEBTOR_AGENT',
        'usf_name_intern' => 'ORIG_DEBTOR_AGENT',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_MANDATE_CHANGE')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Orig_IBAN'] = array(
        'usf_name' => 'PMB_ORIG_IBAN',
        'usf_name_intern' => 'ORIG_IBAN',
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_MANDATE_CHANGE')
    );
    $DB_array['SOLL']['TBL_USER_FIELDS']['Orig_Mandatsreferenz'] = array(
        'usf_name' => 'PMB_ORIG_MANDATEID',
        'usf_name_intern' => 'ORIG_MANDATEID' . $GLOBALS['gCurrentOrgId'],
        'usf_type' => 'TEXT',
        'usf_system' => 0,
        'usf_disabled' => 1,
        'usf_hidden' => 1,
        'usf_required_input' => 0,
        'usf_description' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT_FIELD_DESC_MANDATE_CHANGE')
    );

    $DB_array['IST'] = $DB_array['SOLL'];

    foreach ($DB_array['IST']['TBL_USER_FIELDS'] as $field => $fielddata) {
        $sql = ' SELECT usf_name, usf_name_intern, usf_type, usf_system, usf_disabled, usf_hidden, usf_required_input
            FROM ' . TBL_USER_FIELDS . ' , ' . TBL_CATEGORIES . '
            WHERE usf_name_intern = \'' . $fielddata['usf_name_intern'] . '\'
            AND usf_cat_id = cat_id
            AND (  cat_org_id = ' . $GLOBALS['gCurrentOrgId'] . '
            OR cat_org_id IS NULL ) ';

        $statement = $GLOBALS['gDb']->query($sql);
        $row = $statement->fetchObject();

        $DB_array['IST']['TBL_USER_FIELDS'][$field] = array(
            'usf_name_intern' => (isset($row->usf_name_intern) ? $row->usf_name_intern : ''),
            'usf_name' => (isset($row->usf_name) ? $row->usf_name : ''),
            'usf_type' => (isset($row->usf_type) ? $row->usf_type : ''),
            'usf_system' => (isset($row->usf_system) ? $row->usf_system : ''),
            'usf_disabled' => (isset($row->usf_disabled) ? $row->usf_disabled : ''),
            'usf_hidden' => (isset($row->usf_hidden) ? $row->usf_hidden : ''),
            'usf_required_input' => (isset($row->usf_required_input) ? $row->usf_required_input : '')
        );

        if ($DB_array['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern'] != $DB_array['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern']) {
            unset($DB_array['IST']['TBL_USER_FIELDS'][$field]);
        }
    }

    foreach ($DB_array['IST']['TBL_CATEGORIES'] as $cat => $catdata) {
        $sql = ' SELECT cat_id, cat_org_id, cat_name, cat_name_intern, cat_type, cat_system
            FROM ' . TBL_CATEGORIES . '
            WHERE cat_name_intern = \'' . $catdata['cat_name_intern'] . '\'
            AND cat_type = \'USF\'
            AND (  cat_org_id = ' . $GLOBALS['gCurrentOrgId'] . '
                OR cat_org_id IS NULL ) ';

        $statement = $GLOBALS['gDb']->query($sql);
        $row = $statement->fetchObject();

        $DB_array['IST']['TBL_CATEGORIES'][$cat] = array(
            'cat_name_intern' => (isset($row->cat_name_intern) ? $row->cat_name_intern : ''),
            'cat_name' => (isset($row->cat_name) ? $row->cat_name : ''),
            'cat_id' => (isset($row->cat_id) ? $row->cat_id : ''),
            'cat_org_id' => (isset($row->cat_org_id) ? $row->cat_org_id : ''),
            'cat_type' => (isset($row->cat_type) ? $row->cat_type : ''),
            'cat_system' => (isset($row->cat_system) ? $row->cat_system : '')
        );

        if ($DB_array['IST']['TBL_CATEGORIES'][$cat]['cat_name_intern'] != $DB_array['SOLL']['TBL_CATEGORIES'][$cat]['cat_name_intern']) {
            unset($DB_array['IST']['TBL_CATEGORIES'][$cat]);
        }
    }
    return $DB_array;
}

/**
 * Erzeugt die Texte fuer den Soll-Ist-Vergleich der Profilfelder
 *
 * @param array $arr
 * @param string $field
 * @return array $columnValues
 */
function SollIstProfilfeld($arr, $field)
{
    $columnValues = array();

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern'] != $arr['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern']) {
        $columnValues[] = '<strong>' . $arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern'] . '</strong>';
        $columnValues[] = '<strong>' . $arr['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern'] . '</strong>';
    } else {
        $columnValues[] = $arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_name_intern'];
        $columnValues[] = $arr['IST']['TBL_USER_FIELDS'][$field]['usf_name_intern'];
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_type'] != $arr['IST']['TBL_USER_FIELDS'][$field]['usf_type']) {
        $columnValues[] = '<strong>' . $arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_type'] . '</strong>';
        $columnValues[] = '<strong>' . $arr['IST']['TBL_USER_FIELDS'][$field]['usf_type'] . '</strong>';
    } else {
        $columnValues[] = $arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_type'];
        $columnValues[] = $arr['IST']['TBL_USER_FIELDS'][$field]['usf_type'];
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_hidden'] == 1) {
        $columnValues[] = '<i class="bi bi-eye-fill admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_HIDDEN') . '"></i>';
    } else {
        $columnValues[] = '<i class="bi bi-eye-fill" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_NOT_HIDDEN') . '"></i>';
    }

    if ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_hidden'] == 1) {
        $columnValues[] = '<i class="bi bi-eye-fill admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_HIDDEN') . '"></i>';
    } else {
        $columnValues[] = '<i class="bi bi-eye-fill" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_NOT_HIDDEN') . '"></i>';
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_disabled'] == 1) {
        $columnValues[] = '<i class="bi bi-key-fill" data-bs-toggle="tooltip" data-bs-html="true" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_DISABLED', array(
            $GLOBALS['gL10n']->get('SYS_RIGHT_EDIT_USER')
        )) . '"></i>';
    } else {
        $columnValues[] = '<i class="bi bi-key-fill admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_NOT_DISABLED') . '"></i>';
    }

    if ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_disabled'] == 1) {
        $columnValues[] = '<i class="bi bi-key-fill" data-bs-toggle="tooltip" data-bs-html="true" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_DISABLED', array(
            $GLOBALS['gL10n']->get('SYS_RIGHT_EDIT_USER')
        )) . '"></i>';
    } else {
        $columnValues[] = '<i class="bi bi-key-fill admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('ORG_FIELD_NOT_DISABLED') . '"></i>';
    }

    if ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 1) {
        $columnValues[] = '<i class="bi bi-asterisk" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_YES') . '"></i>';
    } elseif ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 2) {
        $columnValues[] = '<i class="bi bi-asterisk" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_ONLY_AT_REGISTRATION_AND_OWN_PROFILE') . '"></i>';
    } elseif ($arr['SOLL']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 3) {
        $columnValues[] = '<i class="bi bi-asterisk" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_NOT_AT_REGISTRATION') . '"></i>';
    } else {
        $columnValues[] = '<i class="bi bi-asterisk admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_NO') . '"></i>';
    }

    if ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 1) {
        $columnValues[] = '<i class="bi bi-asterisk" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_YES') . '"></i>';
    } elseif ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 2) {
        $columnValues[] = '<i class="bi bi-asterisk" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_ONLY_AT_REGISTRATION_AND_OWN_PROFILE') . '"></i>';
    } elseif ($arr['IST']['TBL_USER_FIELDS'][$field]['usf_required_input'] == 3) {
        $columnValues[] = '<i class="bi bi-asterisk" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_NOT_AT_REGISTRATION') . '"></i>';
    } else {
        $columnValues[] = '<i class="bi bi-asterisk admidio-opacity-reduced" data-bs-toggle="tooltip" title="' . $GLOBALS['gL10n']->get('SYS_NO') . '"></i>';
    }

    return $columnValues;
}

/**
 * Erzeugt die Texte fuer den Soll-Ist-Vergleich der Kategorien
 *
 * @param array $arr
 * @param string $field
 * @return array $columnValues
 */
function SollIstKategorie($arr, $field)
{
    $columnValues = array();

    if ($arr['SOLL']['TBL_CATEGORIES'][$field]['cat_name_intern'] != $arr['IST']['TBL_CATEGORIES'][$field]['cat_name_intern']) {
        $columnValues[] = '&nbsp;';
        $columnValues[] = '<strong>' . $arr['SOLL']['TBL_CATEGORIES'][$field]['cat_name_intern'] . '</strong>';
        $columnValues[] = '<strong>' . $arr['IST']['TBL_CATEGORIES'][$field]['cat_name_intern'] . '</strong>';
        $columnValues[] = '&nbsp;';
    } else {
        $columnValues[] = '&nbsp;';
        $columnValues[] = $arr['SOLL']['TBL_CATEGORIES'][$field]['cat_name_intern'];
        $columnValues[] = $arr['IST']['TBL_CATEGORIES'][$field]['cat_name_intern'];
        $columnValues[] = '&nbsp;';
    }
    return $columnValues;
}

/**
 * Erzeugt den naechsten freien Wert fuer cat_sequence
 *
 * @param string $cat_type
 *            Kategorietyp
 * @return int Der naechste freie Wert fuer cat_sequence
 */
function getNextCatSequence($cat_type)
{
    $sql = 'SELECT cat_type, cat_sequence
                FROM ' . TBL_CATEGORIES . '
                WHERE cat_type = \'' . $cat_type . '\'
                AND (  cat_org_id  = ' . $GLOBALS['gCurrentOrgId'] . '
                    OR cat_org_id IS NULL )
                ORDER BY cat_sequence ASC';

    $statement = $GLOBALS['gDb']->query($sql);

    while ($row = $statement->fetch()) {
        $sequence = $row['cat_sequence'];
    }
    return $sequence + 1;
}

/**
 * Erzeugt den naechsten freien Wert fuer usf_sequence
 *
 * @param int $usf_cat_id
 *            Cat_Id
 * @return int Der naechste freie Wert fuer usf_sequence
 */
function getNextFieldSequence($usf_cat_id)
{
    $sequence = 0;

    $sql = 'SELECT usf_cat_id, usf_sequence
                FROM ' . TBL_USER_FIELDS . '
                WHERE usf_cat_id = \'' . $usf_cat_id . '\'
                ORDER BY usf_sequence ASC';

    $statement = $GLOBALS['gDb']->query($sql);

    while ($row = $statement->fetch()) {
        $sequence = $row['usf_sequence'];
    }
    return $sequence + 1;
}

/**
 * Gibt zu einem Kategorienamen die entsprechende Cat_ID zurueck
 *
 * @param string $cat_name_intern
 *            Name der zu pruefenden Kategorie
 * @return int cat_id Cat_id der Kategorie
 */
function getCat_IDPMB($cat_name_intern)
{
    $sql = ' SELECT cat_id
            FROM ' . TBL_CATEGORIES . '
            WHERE cat_name_intern = \'' . $cat_name_intern . '\'
            AND cat_type = \'USF\'
            AND (  cat_org_id = ' . $GLOBALS['gCurrentOrgId'] . '
                OR cat_org_id IS NULL ) ';

    $statement = $GLOBALS['gDb']->query($sql);
    $row = $statement->fetchObject();

    return $row->cat_id;
}

/**
 * Erzeugt eine Kategorie in der Tabelle TBL_CATEGORIES
 *
 * @param array $arr
 *            Array mit Werten für die Spalten
 * @param int $sequence
 *            Neue Sequence der anzulegenden Kategorie
 * @return void
 */
function setCategory($arr, $sequence)
{
    // $newCategory = new TableAccess($GLOBALS['gDb'], TBL_CATEGORIES, 'cat');
    $newCategory = new Entity($GLOBALS['gDb'], TBL_CATEGORIES, 'cat');
    $newCategory->setValue('cat_sequence', $sequence);

    foreach ($arr as $key => $value) {
        $newCategory->setValue($key, $value);
    }

    $newCategory->save();
}

/**
 * Erzeugt ein Profilfeld in der Tabelle TBL_USER_FIELDS
 *
 * @param int $cat_id
 *            Cat_Id der Kategorie, in der das Profilfeld angelegt wird
 * @param array $arr
 *            Array mit Werten für die Spalten
 * @param int $sequence
 *            Neue Sequence des anzulegenden Profilfeldes
 * @return void
 */
function setUserField($cat_id, $arr, $sequence)
{
    // $newUserField = new TableAccess($GLOBALS['gDb'], TBL_USER_FIELDS, 'usf');
    $newUserField = new Entity($GLOBALS['gDb'], TBL_USER_FIELDS, 'usf');
    $newUserField->setValue('usf_cat_id', $cat_id);
    $newUserField->setValue('usf_sequence', $sequence);

    foreach ($arr as $key => $value) {
        $newUserField->setValue($key, $value);
    }

    $newUserField->save();
}  