<?php
namespace Plugins\MembershipFee\classes\Service;

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Entity\Text;
use Admidio\Roles\Entity\Role;
use Admidio\Users\Entity\User;
use Plugins\MembershipFee\classes\Config\ConfigTable;

/**
 *
 * @brief Class with methods to display the module pages.
 *
 * This class adds some functions that are used in the preferences module to keep the
 * code easy to read and short
 *
 * MembershipfeePreferencesService is a modified (Admidio)PreferencesService
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class MembershipfeePreferencesService
{

    /**
     * Save all form data of the panel to the database.
     *
     * @param string $panel
     *            Name of the panel for which the data should be saved.
     * @param array $formData
     *            All form data of the panel.
     * @return void
     * @throws Exception
     */
    public function save(string $panel, array $formData)
    {
        global $gL10n, $gSettingsManager, $gCurrentSession, $gDb, $gCurrentOrgId, $gProfileFields, $gLogger;

        require_once (__DIR__ . '/../../system/common_function.php');
        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $result = $gL10n->get('SYS_SAVE_DATA');

        // first check the fields of the submitted form
        switch ($panel) {
            case 'contribution_settings':
                unset($pPreferences->config['Beitrag']);

                $pPreferences->config['Beitrag']['beitrag_prefix'] = $formData['beitrag_prefix'];
                $pPreferences->config['Beitrag']['beitrag_suffix'] = $formData['beitrag_suffix'];
                $pPreferences->config['Beitrag']['beitrag_anteilig'] = isset($formData['beitrag_anteilig']) ? 1 : 0;
                $pPreferences->config['Beitrag']['beitrag_abrunden'] = isset($formData['beitrag_abrunden']) ? 1 : 0;
                $pPreferences->config['Beitrag']['beitrag_mindestbetrag'] = $formData['beitrag_mindestbetrag'];
                $pPreferences->config['Beitrag']['beitrag_textmitnam'] = isset($formData['beitrag_textmitnam']) ? 1 : 0;
                $pPreferences->config['Beitrag']['beitrag_textmitfam'] = isset($formData['beitrag_textmitfam']) ? 1 : 0;
                $pPreferences->config['Beitrag']['beitrag_text_token'] = $formData['beitrag_text_token'];
                $pPreferences->config['Beitrag']['beitrag_role_separator'] = replace_sepadaten($formData['beitrag_role_separator']);
                // $_POST übergibt keine führenden und nachfolgenden Leerzeichen, $_REQUEST schon
                break;

            case 'tests_setup':
                unset($pPreferences->config['Familienrollen']['familienrollen_pruefung'], $pPreferences->config['Rollenpruefung'], $pPreferences->config['tests_enable']);
                $pPreferences->config['tests_enable']['age_staggered_roles'] = isset($formData['age_staggered_roles']) ? 1 : 0;
                $pPreferences->config['tests_enable']['role_membership_age_staggered_roles'] = isset($formData['role_membership_age_staggered_roles']) ? 1 : 0;
                $pPreferences->config['tests_enable']['role_membership_duty_and_exclusion'] = isset($formData['role_membership_duty_and_exclusion']) ? 1 : 0;
                $pPreferences->config['tests_enable']['family_roles'] = isset($formData['family_roles']) ? 1 : 0;
                $pPreferences->config['tests_enable']['account_details'] = isset($formData['account_details']) ? 1 : 0;
                $pPreferences->config['tests_enable']['mandate_management'] = isset($formData['mandate_management']) ? 1 : 0;
                $pPreferences->config['tests_enable']['iban_check'] = isset($formData['iban_check']) ? 1 : 0;
                $pPreferences->config['tests_enable']['bic_check'] = isset($formData['bic_check']) ? 1 : 0;

                for ($conf = 0; isset($formData['familienrollen_pruefung' . $conf]); $conf ++) {
                    $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$conf] = $formData['familienrollen_pruefung' . $conf];
                }

                if (isset($formData['familienrollenpflicht'])) {
                    $pPreferences->config['Rollenpruefung']['familienrollenpflicht'] = $formData['familienrollenpflicht'];
                }

                $fixrollen = beitragsrollen_einlesen('fix');
                foreach ($fixrollen as $key => $data) {
                    if (isset($formData['fixrollenpflicht' . $key])) {
                        $pPreferences->config['Rollenpruefung']['fixrollenpflicht'][] = $key;
                    }
                    foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $token) {
                        if (isset($formData['altersrollenfix' . $token . $key])) {
                            $pPreferences->config['Rollenpruefung']['altersrollenfix'][] = $token . $key;
                        }
                    }

                    if (isset($formData['familienrollenfix' . $key])) {
                        $pPreferences->config['Rollenpruefung']['familienrollenfix'][] = $key;
                    }
                }

                if ((count($pPreferences->config['Altersrollen']['altersrollen_token']) > 1)) {
                    for ($x = 0; $x < count($pPreferences->config['Altersrollen']['altersrollen_token']) - 1; $x ++) {
                        for ($y = $x + 1; $y < count($pPreferences->config['Altersrollen']['altersrollen_token']); $y ++) {
                            if (isset($formData['altersrollenaltersrollen' . $pPreferences->config['Altersrollen']['altersrollen_token'][$x] . $pPreferences->config['Altersrollen']['altersrollen_token'][$y]])) {
                                $pPreferences->config['Rollenpruefung']['altersrollenaltersrollen'][] = $pPreferences->config['Altersrollen']['altersrollen_token'][$x] . ',' . $pPreferences->config['Altersrollen']['altersrollen_token'][$y];
                            }
                        }
                    }
                }

                foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $token) {
                    if (isset($formData['age_staggered_roles_exclusion' . $token])) {
                        $pPreferences->config['Rollenpruefung']['age_staggered_roles_exclusion'][] = $token;
                    }
                    if (isset($formData['altersrollenpflicht' . $token])) {
                        $pPreferences->config['Rollenpruefung']['altersrollenpflicht'][] = $token;
                    }
                    if (isset($formData['altersrollenfamilienrollen' . $token])) {
                        $pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen'][] = $token;
                    }
                }

                if (count($fixrollen) > 1) {
                    $fixrollenL = $fixrollen;
                    array_pop($fixrollenL); // das letzte Element entfernen
                    $fixrollenR = $fixrollen;

                    foreach ($fixrollenL as $keyL => $dataL) {
                        unset($fixrollenR[$keyL]); // dasselbe Element entfernen
                        foreach ($fixrollenR as $keyR => $dataR) {
                            if (isset($formData['fixrollenfixrollen' . $keyL . '_' . $keyR])) {
                                $pPreferences->config['Rollenpruefung']['fixrollenfixrollen'][] = $keyL . '_' . $keyR;
                            }
                        }
                    }
                    unset($fixrollenL);
                    unset($fixrollenR);
                }

                if (isset($formData['bezugskategorie'])) {
                    $pPreferences->config['Rollenpruefung']['bezugskategorie'] = $formData['bezugskategorie'];
                }

                foreach ($pPreferences->config_default['Rollenpruefung'] as $roleTest => $dummy) {
                    if (! isset($pPreferences->config['Rollenpruefung'][$roleTest])) {
                        $pPreferences->config['Rollenpruefung'][$roleTest] = $pPreferences->config_default['Rollenpruefung'][$roleTest];
                    }
                }

                break;

            case 'delete':
                $members = array();
                $members = list_members(array(
                    'PAID' . $gCurrentOrgId,
                    'FEE' . $gCurrentOrgId,
                    'CONTRIBUTORY_TEXT' . $gCurrentOrgId,
                    'DUEDATE' . $gCurrentOrgId
                ), 0);

                foreach ($members as $key => $data) {
                    $user = new User($gDb, $gProfileFields, $key);

                    // isset($formData['delete']['duedate_only']
                    if (! empty($data['DUEDATE' . $gCurrentOrgId]) && $formData['delete'] === 'duedate_only') {
                        $user->setValue('DUEDATE' . $gCurrentOrgId, '');
                    }

                    if (! empty($data['PAID' . $gCurrentOrgId]) && $formData['delete'] === 'with_paid' || $formData['delete'] === 'paid_only' || $formData['delete'] === 'delete_all') {
                        $user->setValue('PAID' . $gCurrentOrgId, '');
                    }

                    if (! empty($data['FEE' . $gCurrentOrgId]) && (($formData['delete'] === 'with_paid' && ! empty($data['PAID' . $gCurrentOrgId])) || ($formData['delete'] === 'without_paid' && empty($data['PAID' . $gCurrentOrgId])) || $formData['delete'] === 'delete_all')) {
                        $user->setValue('FEE' . $gCurrentOrgId, '');
                    }

                    if (! empty($data['CONTRIBUTORY_TEXT' . $gCurrentOrgId]) && (($formData['delete'] === 'with_paid' && ! empty($data['PAID' . $gCurrentOrgId])) || ($formData['delete'] === 'without_paid' && empty($data['PAID' . $gCurrentOrgId])) || $formData['delete'] === 'delete_all')) {
                        $user->setValue('CONTRIBUTORY_TEXT' . $gCurrentOrgId, '');
                    }
                    $user->save();
                }
                break;

            case 'agestaggered_roles':
                unset($pPreferences->config['Altersrollen']);

                $altersrollen_anzahl = 0;
                $pPreferences->config['Altersrollen']['altersrollen_offset'] = $formData['altersrollen_offset'];

                for ($conf = 0; isset($formData['altersrollen_token' . $conf]); $conf ++) {
                    if (empty($formData['altersrollen_token' . $conf])) {
                        continue;
                    }
                    $pPreferences->config['Altersrollen']['altersrollen_token'][] = $formData['altersrollen_token' . $conf];
                    $altersrollen_anzahl ++;
                }
                // diese Zeile ist nur zur Sicherheit, falls ein Nutzer einen Refresh (F5) des Browsers ausfuehrt
                // und dadurch nicht durch das Plugin kontrollierte Loeschungen oder Hinzufuegungen ausfuehrt
                if ($altersrollen_anzahl == 0) {
                    throw new Exception('PLG_MEMBERSHIPFEE_ERROR_MIN_CONFIG');
                }
                break;

            case 'family_roles':
                // familienrollen_pruefung zwischenspeichern
                $familienrollen_pruefung = $pPreferences->config['Familienrollen']['familienrollen_pruefung'];

                unset($pPreferences->config['Familienrollen']);

                for ($conf = 0; isset($formData['familienrollen_prefix' . $conf]); $conf ++) {
                    if (empty($formData['familienrollen_prefix' . $conf])) {
                        continue;
                    }

                    $pPreferences->config['Familienrollen']['familienrollen_prefix'][] = $formData['familienrollen_prefix' . $conf];
                    $pPreferences->config['Familienrollen']['familienrollen_beitrag'][] = str_replace(',', '.', $formData['familienrollen_beitrag' . $conf]);
                    $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][] = $formData['familienrollen_zeitraum' . $conf];
                    $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][] = $formData['familienrollen_beschreibung' . $conf];
                    $pPreferences->config['Familienrollen']['familienrollen_pruefung'][] = isset($familienrollen_pruefung[$conf]) ? $familienrollen_pruefung[$conf] : '';
                }
                break;

            case 'multiplier_roles':
                if (isset($formData['multiplier_roles'])) {
                    $pPreferences->config['multiplier']['roles'] = array_filter($formData['multiplier_roles']);
                } else {
                    $pPreferences->config['multiplier']['roles'] = array();
                }
                break;

            case 'advanced_role_editing':
                $altersrollen = beitragsrollen_einlesen('alt');
                $fixrollen = beitragsrollen_einlesen('fix');
                $alt_fix_rollen = $altersrollen + $fixrollen;
                unset($altersrollen);
                unset($fixrollen);

                $role = new Role($gDb);

                foreach ($alt_fix_rollen as $rol_id => $dummy) {
                    $role->readDataById((int) $rol_id);
                    $role->setvalue('rol_cost', str_replace(',', '.', $formData['rol_cost' . $rol_id]));
                    $role->setvalue('rol_cost_period', $formData['rol_cost_period' . $rol_id]);
                    $role->setvalue('rol_description', $formData['rol_description' . $rol_id]);
                    $role->save();
                }
                break;

            case 'events_selection':
                if (isset($formData['events_selection'])) {
                    $role = new Role($gDb);
                    foreach (array_filter($formData['events_selection']) as $rol_id) {
                        $role->readDataById((int) $rol_id);
                        $role->setvalue('rol_cost', 0);
                        $role->setvalue('rol_cost_period', - 1);
                        $role->save();
                    }
                }
                break;

            case 'individual_contributions':
                $pPreferences->config['individual_contributions']['access_to_module'] = isset($formData['individual_contributions_module_enabled']) ? 1 : 0;

                unset($pPreferences->config['individual_contributions']['desc']);
                unset($pPreferences->config['individual_contributions']['short_desc']);
                unset($pPreferences->config['individual_contributions']['role']);
                unset($pPreferences->config['individual_contributions']['amount']);
                unset($pPreferences->config['individual_contributions']['profilefield']);

                for ($conf = 0; isset($formData['individual_contributions_desc' . $conf]); $conf ++) {
                    if (empty($formData['individual_contributions_desc' . $conf])) {
                        continue;
                    }

                    $pPreferences->config['individual_contributions']['desc'][] = $formData['individual_contributions_desc' . $conf];
                    $pPreferences->config['individual_contributions']['short_desc'][] = $formData['individual_contributions_short_desc' . $conf];
                    $pPreferences->config['individual_contributions']['role'][] = $formData['individual_contributions_role' . $conf];
                    $pPreferences->config['individual_contributions']['amount'][] = $formData['individual_contributions_amount' . $conf];
                    $pPreferences->config['individual_contributions']['profilefield'][] = $formData['individual_contributions_profilefield' . $conf];
                }
                break;

            case 'sepa':
                unset($pPreferences->config['SEPA']);

                $pPreferences->config['SEPA']['dateiname'] = $formData['dateiname'];
                $pPreferences->config['SEPA']['direct_debit_format'] = $formData['direct_debit_format'];
                $pPreferences->config['SEPA']['kontroll_dateiname'] = $formData['kontroll_dateiname'];
                $pPreferences->config['SEPA']['kontroll_dateityp'] = $formData['kontroll_dateityp'];
                $pPreferences->config['SEPA']['vorabinformation_dateiname'] = $formData['vorabinformation_dateiname'];
                $pPreferences->config['SEPA']['vorabinformation_dateityp'] = $formData['vorabinformation_dateityp'];
                $pPreferences->config['SEPA']['duedate_for_all'] = isset($formData['duedate_for_all']) ? 1 : 0;
                break;

            case 'bill':
                unset($pPreferences->config['Rechnungs-Export']);

                $pPreferences->config['Rechnungs-Export']['rechnung_dateiname'] = $formData['rechnung_dateiname'];
                $pPreferences->config['Rechnungs-Export']['rechnung_dateityp'] = $formData['rechnung_dateityp'];
                break;

            case 'email_notifications':
                $text = new Text($gDb);

                $text->readDataByColumns(array(
                    'txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS',
                    'txt_org_id' => $gCurrentOrgId
                ));
                // $text->setValue('txt_text', $_POST['mail_text']);
                $text->setValue('txt_text', $formData['mail_text']);
                $text->save();

                $text->readDataByColumns(array(
                    'txt_name' => 'PMBMAIL_PRE_NOTIFICATION',
                    'txt_org_id' => $gCurrentOrgId
                ));
                // $text->setValue('txt_text', $_POST['pre_notification_text']);
                $text->setValue('txt_text', $formData['pre_notification_text']);
                $text->save();

                $text->readDataByColumns(array(
                    'txt_name' => 'PMBMAIL_BILL',
                    'txt_org_id' => $gCurrentOrgId
                ));
                // $text->setValue('txt_text', $_POST['bill_text']);
                $text->setValue('txt_text', $formData['bill_text']);
                $text->save();
                break;

            case 'account_data':
                unset($pPreferences->config['Kontodaten']);

                $pPreferences->config['Kontodaten']['iban'] = $formData['iban'];
                $pPreferences->config['Kontodaten']['bic'] = $formData['bic'];
                $pPreferences->config['Kontodaten']['bank'] = $formData['bank'];
                $pPreferences->config['Kontodaten']['inhaber'] = $formData['creditor'];
                $pPreferences->config['Kontodaten']['origcreditor'] = isset($formData['origcreditor']) ? $formData['origcreditor'] : '';
                $pPreferences->config['Kontodaten']['ci'] = $formData['ci'];
                $pPreferences->config['Kontodaten']['origci'] = isset($formData['origci']) ? $formData['origci'] : '';
                unset($_SESSION['pMembershipFee']['account_data_mandate_change']);
                break;

            case 'mandate_management':
                unset($pPreferences->config['Mandatsreferenz']);

                $pPreferences->config['Mandatsreferenz']['prefix_fam'] = $formData['prefix_fam'];
                $pPreferences->config['Mandatsreferenz']['prefix_mem'] = $formData['prefix_mem'];
                $pPreferences->config['Mandatsreferenz']['prefix_pay'] = $formData['prefix_pay'];
                $pPreferences->config['Mandatsreferenz']['min_length'] = $formData['min_length'];
                $pPreferences->config['Mandatsreferenz']['data_field'] = $formData['data_field'];
                break;

            case 'access':
                if (isset($formData['access_preferences'])) {
                    $pPreferences->config['access']['preferences'] = array_values(array_filter($formData['access_preferences']));
                } else {
                    $pPreferences->config['access']['preferences'] = array();
                }
                break;
        }
        $pPreferences->save();
        return $result;

        // clean up
        $gCurrentSession->reloadAllSessions();
    }
}
