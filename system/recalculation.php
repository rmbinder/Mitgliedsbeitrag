<?php
/**
 ***********************************************************************************************
 * Neuberechnung der Mitgliedsbeitraege
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
 * preview - preview of the new fees
 * save - save the new fees
 * print - preview for printing
 *
 * ***************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Roles\Entity\Role;
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
    $postRecalcRoleSelection = array();
    $postRecalcRoleSelection = admFuncVariableIsValid($_POST, 'recalculation_roleselection', 'array');
    $postRecalcNotPaid = admFuncVariableIsValid($_POST, 'recalculation_notpaid', 'bool', array(
        'defaultValue' => FALSE
    ));
    $postRecalcMode = admFuncVariableIsValid($_POST, 'recalculation_mode', 'string', array(
        'defaultValue' => 'standard',
        'validValues' => array(
            'standard',
            'overwrite',
            'summation'
        )
    ));

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $user = new User($gDb, $gProfileFields);

    $headline = $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION');

    $gNavigation->addUrl(CURRENT_URL, $headline);

    if ($getMode == 'preview') {

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-recalculation-preview');

        $page->setContentFullWidth();

        $form = new FormPresenter('recalculation_form', '../templates/recalculation.preview.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation.php'), $page);

        $rols = beitragsrollen_einlesen();
        $sortArray = array();
        $selectBoxEntriesBeitragsrollen = array();

        foreach ($rols as $key => $data) {
            $selectBoxEntriesBeitragsrollen[$key] = array(
                $key,
                $data['rolle'],
                expand_rollentyp($data['rollentyp'])
            );
            $sortArray[$key] = expand_rollentyp($data['rollentyp']);
        }
        array_multisort($sortArray, SORT_ASC, $selectBoxEntriesBeitragsrollen);
        unset($sortArray);

        $form->addSelectBox('recalculation_roleselection', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_SELECTION'), $selectBoxEntriesBeitragsrollen, array(
            'defaultValue' => $postRecalcRoleSelection,
            'showContextDependentFirstEntry' => false,
            'multiselect' => true
        ));

        $radioButtonEntries = array(
            FALSE => $gL10n->get('SYS_ALL'),
            TRUE => $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_NOT_PAID')
        );
        $form->addRadioButton('recalculation_notpaid', $gL10n->get('SYS_FILTER'), $radioButtonEntries, array(
            'defaultValue' => $postRecalcNotPaid
        ));

        $radioButtonEntries = array(
            'standard' => $gL10n->get('PLG_MEMBERSHIPFEE_DEFAULT'),
            'overwrite' => $gL10n->get('PLG_MEMBERSHIPFEE_OVERWRITE'),
            'summation' => $gL10n->get('PLG_MEMBERSHIPFEE_SUMMATION')
        );
        $form->addRadioButton('recalculation_mode', $gL10n->get('PLG_MEMBERSHIPFEE_MODE'), $radioButtonEntries, array(
            'defaultValue' => $postRecalcMode
        ));
        $form->addSubmitButton('btn_recalculation', $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION'), array(
            'icon' => 'bi-calculator'
        ));

        $members = array();
        $message = '';

        // anstelle eines Leerzeichens ist ein # in der $pPreferences->config gespeichert; # wird hier wieder ersetzt
        $text_token = ($pPreferences->config['Beitrag']['beitrag_text_token'] == '#') ? ' ' : $pPreferences->config['Beitrag']['beitrag_text_token'];

        $role_separator = ($pPreferences->config['Beitrag']['beitrag_role_separator'] != '') ? $pPreferences->config['Beitrag']['beitrag_role_separator'] : ' ';

        // alle Beitragsrollen einlesen
        $contributingRolls = beitragsrollen_einlesen('', array(
            'FIRST_NAME',
            'LAST_NAME',
            'IBAN',
            'DEBTOR'
        ));

        // pruefen, ob Eintraege in der Rollenauswahl bestehen
        if (is_array($postRecalcRoleSelection)) {
            $_SESSION['pMembershipFee']['recalculation_rol_sel'] = $postRecalcRoleSelection;

            // nicht gewaehlte Beitragsrollen im Array $contributingRolls loeschen
            foreach ($contributingRolls as $rol => $roldata) {
                if (! in_array($rol, $postRecalcRoleSelection)) {
                    unset($contributingRolls[$rol]);
                }
            }

            // Rollendaten aufbereiten fuer list_members()
            $selectionRolls = array();
            $role = new Role($gDb);
            foreach ($postRecalcRoleSelection as $rol) {
                $role->readDataById((int) $rol);
                $selectionRolls[$role->getValue('rol_name')] = 0;
            }
        } else {
            $selectionRolls = 0;
            unset($_SESSION['pMembershipFee']['recalculation_rol_sel']);
        }

        // eine Berechnung nur durchführen,
        // 1. beim ersten Aufruf des Scripts ($_SESSION['pMembershipFee']['recalculation_user'] ist noch nicht vorhanden)
        // 2. wenn der Button Neuberechnung gedrückt wurde
        // eine Neuberechnung darf nicht durchgeführt werden, wenn ein Beitrag oder ein Text editiert wurde (über recalculatiuon_edit.php)
        if (! isset($_SESSION['pMembershipFee']['recalculation_user']) || isset($_POST['btn_recalculation'])) {

            // diese Rollen durchlaufen und bei den Familienrollen eine Zahlungspflichtigen bestimmen
            foreach ($contributingRolls as $rol => $roldata) {
                // nur Familien
                if ($roldata['rollentyp'] == 'fam') {
                    // alle Mitglieder dieser Rolle durchlaufen und einen Zahlungspflichtigen bestimmen
                    // 1. Durchlauf: hierbei das erste Mitglied bei dem (Kontonummer UND BLZ) oder IBAN belegt sind bestimmen
                    foreach ($roldata['members'] as $key => $data) {
                        $contributingRolls[$rol]['has_to_pay'] = $key;

                        if (strlen($data['IBAN']) !== 0) {
                            $contributingRolls[$rol]['has_to_pay'] = $key;
                            break;
                        }
                    }
                    // alle Mitglieder dieser Rolle durchlaufen und einen Zahlungspflichtigen bestimmen
                    // 2. Durchlauf: gibt es einen Rollenleiter, dann den Zahlungspflichtigen ueberschreiben, da hoeherwertiger
                    foreach ($roldata['members'] as $key => $data) {
                        if (isGroupLeader($key, $rol)) {
                            $contributingRolls[$rol]['has_to_pay'] = $key;
                            break;
                        }
                    }
                }
            }

            // alle aktiven Mitglieder einlesen
            $members = list_members(array(
                'FIRST_NAME',
                'LAST_NAME',
                'FEE' . $gCurrentOrgId,
                'CONTRIBUTORY_TEXT' . $gCurrentOrgId,
                'PAID' . $gCurrentOrgId,
                'ACCESSION' . $gCurrentOrgId,
                'DEBTOR'
            ), $selectionRolls);

            // alle Mitglieder durchlaufen und aufgrund von Rollenzugehoerigkeiten die Beitraege bestimmen
            foreach ($members as $member => $memberdata) {
                $members[$member]['FEE_NEW'] = 0;
                $members[$member]['CONTRIBUTORY_TEXT_NEW'] = '';

                foreach ($contributingRolls as $rol => $roldata) {
                    $rolDescription = ' ';
                    if ($roldata['rol_description'] != '') {
                        $rolDescription = $role_separator . $roldata['rol_description'] . ' ';
                    }

                    // alle Rollen, außer Familienrollen
                    if (($roldata['rollentyp'] != 'fam') && (array_key_exists($member, $roldata['members']))) {
                        // anteilige Beitragsberechnung (Beginn)
                        if ($pPreferences->config['Beitrag']['beitrag_anteilig'] == true) // anhand des Beginns einer Rollenzugehörigkeit
                        {
                            $time_begin = strtotime((string) $roldata['members'][$member]['mem_begin']);
                        } else // anhand des Beitrittsdatums
                        {
                            $time_begin = strtotime($members[$member]['ACCESSION' . $gCurrentOrgId]);
                        }

                        // anteilige Beitragsberechnung anhand des Endes einer Rollenzugehörigkeit
                        // Info: da es das Feld "Austrittsdatum" nicht gibt, kann nur das Ende einer Rollenzugehörigkeit verarbeitet werden

                        // das Standarddatum '9999-12-31' kann auf einigen Systemen nicht verarbeitet werden
                        if ($roldata['members'][$member]['mem_end'] == '9999-12-31') {
                            $time_end = strtotime('2038-01-19');
                        } else {
                            $time_end = strtotime((string) $roldata['members'][$member]['mem_end']);
                        }

                        // Beitragsberechnung nur, wenn das Mitglied im aktuellen Jahr eingetreten ist. Geprüft wird aber im Prinzip nur das Beitrittsdatum
                        // Beginn einer Rollenzugehörigkeit wird nicht ausgewertet, da Mitglieder mit zukünftigen Rollenmitgliedschaften sowieso nicht eingelesen wurden
                        if ($time_begin < strtotime((date('Y') + 1) . '-01-01')) {

                            // anteiligen Beitrag berechnen, falls das Mitglied im aktuellen Jahr ein- oder ausgetreten ist
                            // && Beitragszeitraum (cost_period) darf nicht "Einmalig" (-1) sein
                            // && Beitragszeitraum (cost_period) darf nicht "Jaehrlich" (1) sein
                            if ((strtotime(date('Y') . '-01-01') < $time_begin || $time_end < strtotime(date('Y') . '-12-31')) && ($roldata['rol_cost_period'] != - 1) && ($roldata['rol_cost_period'] != 1)) {

                                if (strtotime(date('Y') . '-01-01') < $time_begin) {
                                    $month_begin = date('n', $time_begin);
                                } else {
                                    $month_begin = 1;
                                }
                                if (strtotime(date('Y') . '-12-31') > $time_end) {
                                    $month_end = date('n', $time_end);
                                } else {
                                    $month_end = 12;
                                }

                                $segment_begin = ceil($month_begin * $roldata['rol_cost_period'] / 12);
                                $segment_end = ceil($month_end * $roldata['rol_cost_period'] / 12);

                                $members[$member]['FEE_NEW'] += ($segment_end - $segment_begin + 1) * $roldata['rol_cost'] / $roldata['rol_cost_period'];
                                $members[$member]['CONTRIBUTORY_TEXT_NEW'] .= $rolDescription;

                                if ($pPreferences->config['Beitrag']['beitrag_suffix'] != '') {
                                    $members[$member]['CONTRIBUTORY_TEXT_NEW'] .= ' ' . $pPreferences->config['Beitrag']['beitrag_suffix'] . ' ';
                                }
                                // nur einmal soll beitrag_suffix angezeigt werden, wenn aber rol_description leer ist,
                                // wird es mehrfach hintereinander mit vielen Leerzeichen dazwischen angefuegt, deshalb ersetzen
                                // zuerst zwei aufeinanderfolgende Leerzeichen durch ein Leerzeichen ersetzen
                                // $members[$member]['CONTRIBUTORY_TEXT_NEW'] = str_replace(' ', ' ', $members[$member]['CONTRIBUTORY_TEXT_NEW']); //toDo testen
                                // jetzt mehrfache beitrag_suffix loeschen
                                $members[$member]['CONTRIBUTORY_TEXT_NEW'] = str_replace($pPreferences->config['Beitrag']['beitrag_suffix'] . ' ' . $pPreferences->config['Beitrag']['beitrag_suffix'], $pPreferences->config['Beitrag']['beitrag_suffix'], $members[$member]['CONTRIBUTORY_TEXT_NEW']);
                            } else // keine anteilige Berechnung
                            {
                                $members[$member]['FEE_NEW'] += $roldata['rol_cost'];
                                $members[$member]['CONTRIBUTORY_TEXT_NEW'] .= $rolDescription;
                            }
                        }
                    }
                }

                // wenn definiert: Beitragstext mit dem Namen des Benutzers
                if (($pPreferences->config['Beitrag']['beitrag_textmitnam'] == true) && ($members[$member]['FEE_NEW'] != 0) && ! (($members[$member]['LAST_NAME'] . ' ' . $members[$member]['FIRST_NAME'] == $members[$member]['DEBTOR']) || ($members[$member]['FIRST_NAME'] . ' ' . $members[$member]['LAST_NAME'] == $members[$member]['DEBTOR']) || (empty($members[$member]['DEBTOR'])))) {
                    $members[$member]['CONTRIBUTORY_TEXT_NEW'] .= $text_token . $members[$member]['LAST_NAME'] . ' ' . $members[$member]['FIRST_NAME'] . $text_token;
                }
            }

            // alle Rollen und deren Mitglieder durchlaufen und die Beitraege eines Mitglieds,
            // das zudem ein Familienmitglied ist, dem Zahlungspflichtigen der Familie zugeschlagen
            foreach ($contributingRolls as $rol => $roldata) {
                $rolDescription = ' ';
                if ($roldata['rol_description'] != '') {
                    $rolDescription = $role_separator . $roldata['rol_description'] . ' ';
                }

                // nur Rollen mit dem Praefix einer Familie && die Familienrolle muß Mitglieder aufweisen
                if (($roldata['rollentyp'] == 'fam') && (count($roldata['members']) > 0)) {
                    // wenn definiert: Beitragstext mit allen Familienmitgliedern
                    if ($pPreferences->config['Beitrag']['beitrag_textmitfam'] == true) {
                        $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] .= ' ';
                        foreach ($roldata['members'] as $member => $memberdata) {
                            $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] .= $text_token . $members[$member]['LAST_NAME'] . ' ' . $members[$member]['FIRST_NAME'];
                        }
                        $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] .= $text_token . ' ';
                    }

                    // alle Mitglieder dieser Rolle durchlaufen und die Beitraege der Mitglieder dem Zahlungspflichtigen zuordnen
                    foreach ($roldata['members'] as $member => $memberdata) {
                        // nicht beim Zahlungspflichtigen selber und auch nur, wenn ein Zusatzbeitrag beim Mitglied errechnet wurde
                        if (($roldata['has_to_pay'] != $member) && ($members[$member]['FEE_NEW'] > 0)) {
                            $members[$roldata['has_to_pay']]['FEE_NEW'] += $members[$member]['FEE_NEW'];
                            $members[$member]['FEE_NEW'] = 0;
                            $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] .= $members[$member]['CONTRIBUTORY_TEXT_NEW'] . ' ';

                            // wenn nicht definiert: Beitragstext mit allen Familienmitgliedern, trotzdem Name und Vorname anfuegen
                            if (! $pPreferences->config['Beitrag']['beitrag_textmitnam']) {
                                $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] .= $text_token . $memberdata['LAST_NAME'] . ' ' . $memberdata['FIRST_NAME'] . $text_token . ' ';
                            }
                            $members[$member]['CONTRIBUTORY_TEXT_NEW'] = '';
                        }
                    }

                    // ist diese Familienrolle als Multiplikatorrolle definiert?
                    if (in_array($rol, $pPreferences->config['multiplier']['roles'])) {
                        $members[$roldata['has_to_pay']]['FEE_NEW'] = $members[$roldata['has_to_pay']]['FEE_NEW'] * $roldata['rol_cost'] / 100;
                        $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] = $rolDescription . $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] . ' ';
                    } else {
                        if ($pPreferences->config['Beitrag']['beitrag_anteilig'] == true) // anteilige Beitragsberechnung anhand des Beginns einer Rollenzugehörigkeit
                        {
                            $time_begin = strtotime($roldata['members'][$roldata['has_to_pay']]['mem_begin']);
                        } else // anteilige Betragsberechnung anhand des Beitrittsdatums
                        {
                            $time_begin = strtotime($members[$roldata['has_to_pay']]['ACCESSION' . $gCurrentOrgId]);
                        }

                        // das Standarddatum '9999-12-31' kann auf einigen Systemen nicht verarbeitet werden
                        if ($roldata['members'][$member]['mem_end'] == '9999-12-31') {
                            $time_end = strtotime('2038-01-19');
                        } else {
                            $time_end = strtotime($roldata['members'][$member]['mem_end']);
                        }

                        // Beitragsberechnung nur, wenn das Mitglied im aktuellen Jahr eingetreten ist. Geprüft wird aber im Prinzip nur das Beitrittsdatum
                        // Beginn einer Rollenzugehörigkeit wird nicht ausgewertet, da Mitglieder mit zukünftigen Rollenmitgliedschaften sowieso nicht eingelesen wurden
                        if ($time_begin < strtotime((date('Y') + 1) . '-01-01')) {

                            // anteiligen Beitrag berechnen, falls das Mitglied (in diesem Fall der Zahlungspflichtige der Familienrolle) im aktuellen Jahr ein- oder ausgetreten ist
                            // && Beitragszeitraum (cost_period) darf nicht "Einmalig" (-1) sein
                            // && Beitragszeitraum (cost_period) darf nicht "Jaehrlich" (1) sein
                            if ((strtotime(date('Y') . '-01-01') < $time_begin || $time_end < strtotime(date('Y') . '-12-31')) && ($roldata['rol_cost_period'] != - 1) && ($roldata['rol_cost_period'] != 1)) {

                                if (strtotime(date('Y') . '-01-01') < $time_begin) {
                                    $month_begin = date('n', $time_begin);
                                } else {
                                    $month_begin = 1;
                                }
                                if (strtotime(date('Y') . '-12-31') > $time_end) {
                                    $month_end = date('n', $time_end);
                                } else {
                                    $month_end = 12;
                                }

                                $segment_begin = ceil($month_begin * $roldata['rol_cost_period'] / 12);
                                $segment_end = ceil($month_end * $roldata['rol_cost_period'] / 12);

                                $members[$roldata['has_to_pay']]['FEE_NEW'] += ($segment_end - $segment_begin + 1) * $roldata['rol_cost'] / $roldata['rol_cost_period'];
                                $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] = $rolDescription . $pPreferences->config['Beitrag']['beitrag_suffix'] . ' ' . $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] . ' ';
                            } else {
                                $members[$roldata['has_to_pay']]['FEE_NEW'] += $roldata['rol_cost'];
                                $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] = $rolDescription . $members[$roldata['has_to_pay']]['CONTRIBUTORY_TEXT_NEW'] . ' ';
                            }
                        }
                    }
                }
            }

            foreach ($members as $member => $memberdata) {
                // letzte Datenaufbereitung (aufsummieren, ueberschreiben, runden...)
                if ((empty($members[$member]['FEE' . $gCurrentOrgId]) || (! (empty($members[$member]['FEE' . $gCurrentOrgId])) && (($postRecalcMode == 'overwrite') || ($postRecalcMode == 'summation')))) && (! $postRecalcNotPaid || $postRecalcNotPaid && $members[$member]['PAID' . $gCurrentOrgId] == '') && ($members[$member]['FEE_NEW'] > $pPreferences->config['Beitrag']['beitrag_mindestbetrag'])) {

                    // wenn vorhanden, dann das erste $role_separator entfernen
                    // Bsp: SV Musterverein +Jahresbeitrag+Spartenbeitrag soll sein SV Musterverein Jahresbeitrag+Spartenbeitrag
                    if (substr($members[$member]['CONTRIBUTORY_TEXT_NEW'], 0, strlen($role_separator)) == $role_separator) {
                        $members[$member]['CONTRIBUTORY_TEXT_NEW'] = substr($members[$member]['CONTRIBUTORY_TEXT_NEW'], strlen($role_separator));
                    }

                    $members[$member]['CONTRIBUTORY_TEXT_NEW'] = $pPreferences->config['Beitrag']['beitrag_prefix'] . ' ' . $members[$member]['CONTRIBUTORY_TEXT_NEW'] . ' ';

                    // alle Beitraege auf 2 Nachkommastellen runden
                    $members[$member]['FEE_NEW'] = round($members[$member]['FEE_NEW'], 2);

                    // ggf. abrunden
                    if ($pPreferences->config['Beitrag']['beitrag_abrunden'] == true) {
                        $members[$member]['FEE_NEW'] = floor($members[$member]['FEE_NEW']);
                    }

                    // if (isset($_POST['recalculation_modus']) && $_POST['recalculation_modus'] == 'summation') {
                    if ($postRecalcMode == 'summation') {
                        $members[$member]['FEE_NEW'] += (float) $members[$member]['FEE' . $gCurrentOrgId];
                        $members[$member]['CONTRIBUTORY_TEXT_NEW'] .= ' ' . $members[$member]['CONTRIBUTORY_TEXT' . $gCurrentOrgId] . ' ';
                    }

                    // fuehrende und nachfolgene Leerstellen im Beitragstext loeschen
                    $members[$member]['CONTRIBUTORY_TEXT_NEW'] = trim($members[$member]['CONTRIBUTORY_TEXT_NEW']);
                    // zwei aufeinanderfolgende Leerzeichen durch ein Leerzeichen ersetzen
                    $members[$member]['CONTRIBUTORY_TEXT_NEW'] = str_replace('  ', ' ', $members[$member]['CONTRIBUTORY_TEXT_NEW']);
                } else {
                    unset($members[$member]); // wenn kein neuer Beitrag errechnet wurde, dann dieses Mitglied in der Liste loeschen
                }
            }
            // save members in session (for mode write and mode print)
            $_SESSION['pMembershipFee']['recalculation_user'] = $members;
        }

        $table = new DataTables($page, 'table_preview_recalculation');

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
            '15%',
            '25%',
            '15%',
            '25%'
        );

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['recalculation_user'] as $member => $memberdata) {
            $user->readDataById($member);

            $columnValues = array();
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['LAST_NAME'] . '</a>';
            $columnValues[] = '<a href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_MODULES . '/profile/profile.php', array(
                'user_uuid' => $user->getValue('usr_uuid')
            )) . '">' . $memberdata['FIRST_NAME'] . '</a>';
            $columnValues[] = '
                <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation_edit.php', array(
                'user_id' => $member
            )) . '">' . '
                    ' . $memberdata['FEE_NEW'] . '
                </a>';
            $columnValues[] = '
                <a class="admidio-icon-link openPopup" href="javascript:void(0);" data-href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation_edit.php', array(
                'user_id' => $member
            )) . '">' . '
                    ' . $memberdata['CONTRIBUTORY_TEXT_NEW'] . '
                </a>';
            $columnValues[] = $memberdata['FEE' . $gCurrentOrgId];
            $columnValues[] = $memberdata['CONTRIBUTORY_TEXT' . $gCurrentOrgId];

            $data['rows'][] = array(
                'id' => 'row-' . $listRowNumber,
                'data' => $columnValues
            );

            ++ $listRowNumber;
        }

        $form->addButton('btn_next_page', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation.php', array(
                'mode' => 'save'
            )),
            'class' => 'btn-primary'
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

        // Fetch the HTML table from our Smarty template
        $htmlTable = $smarty->fetch('../templates/recalculation.preview.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'save') {

        $headline = $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION');

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-recalculation-save');

        $page->setContentFullWidth();
        $page->setHeadline($headline);

        $page->addPageFunctionsMenuItem('menu_item_print_view', $gL10n->get('SYS_PRINT_PREVIEW'), 'javascript:void(0);', 'bi-printer');

        $page->addJavascript('
    	$("#menu_item_print_view").click(function() {
            window.open("' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/recalculation.php', array(
            'mode' => 'print'
        )) . '", "_blank");
        });', true);

        $table = new DataTables($page, 'table_save_recalculation');

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
            '20%',
            '20%',
            '15%',
            '45%'
        );

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['recalculation_user'] as $member => $memberdata) {
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

        $htmlTable = $smarty->fetch('../templates/recalculation.save.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    } elseif ($getMode == 'print') {

        $headline = $gL10n->get('PLG_MEMBERSHIPFEE_RECALCULATION_NEW');

        $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-recalculation-print');

        $page->setHeadline($headline);
        $page->setPrintMode();

        $table = new DataTables($page, 'table_print_recalculation');

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
            '20%',
            '20%',
            '15%',
            '45%'
        );

        $listRowNumber = 1;
        foreach ($_SESSION['pMembershipFee']['recalculation_user'] as $memberdata) {
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
        $htmlTable = $smarty->fetch('../templates/recalculation.print.plugin.membershipfee.tpl');
        // add table list to the page
        $page->addHtml($htmlTable);

        $page->show();
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}