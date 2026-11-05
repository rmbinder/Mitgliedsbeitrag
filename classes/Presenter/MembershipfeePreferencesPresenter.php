<?php
namespace Plugins\MembershipFee\classes\Presenter;

use Admidio\Infrastructure\Exception;
use Admidio\Infrastructure\Entity\Text;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Roles\Entity\Role;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\MembershipFee\classes\Config\ConfigTable;

/**
 *
 * @brief Class with methods to display the preference page and helpful functions.
 *
 * This class adds some functions that are used in the formfiller-preferences module to keep the
 * code easy to read and short
 *
 * MembershipfeePreferencesPresenter is a modified (Admidio)PreferencesPresenter
 *
 * **Code example**
 * ```
 * // generate html output
 * $page = new MembershipfeePreferencesPresenter('Options', $headline);
 * $page->createOptionsForm();
 * $page->show();
 * ```
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 */
class MembershipfeePreferencesPresenter extends PagePresenter
{

    /**
     *
     * @var array Array with all possible entries for the preferences.
     *      Each entry consists of an array that has the following structure:
     *      array ('key' => 'xzy', 'label' => 'xyz', 'panels' => array('id' => 'xyz', 'title' => 'xyz', 'icon' => 'xyz'))
     *     
     *      There are thwo different visualizations of the preferences:
     *      1) a nested tab structure (main tabs created by 'key' and 'label' and sub tabs created by 'panels')
     *      2) a accordion structure when the @media query (max-width: 768px) is active ('key' and 'label' are used for card header
     *      and 'panels' for accordions inside the card)
     */
    protected array $preferenceTabs = array();

    /**
     *
     * @var string Name of the preference panel that should be shown after page loading.
     *      If this parameter is empty, then show the common preferences.
     */
    protected string $preferencesPanelToShow = '';

    /**
     * Constructor that initializes the class member parameters
     *
     * @throws Exception
     */
    public function __construct(string $panel = '')
    {
        global $gL10n;

        $this->initialize();
        $this->setPanelToShow($panel);

        $this->setHtmlID('adm_preferences');
        $this->setHeadline($gL10n->get('SYS_SETTINGS'));

        parent::__construct();
    }

    /**
     *
     * @throws Exception
     */
    private function initialize(): void
    {
        global $gL10n;
        $this->preferenceTabs = array(

            // === 1) Fees ===
            array(
                'key' => 'fees',
                'label' => $gL10n->get('PLG_MEMBERSHIPFEE_FEES'),
                'panels' => array(
                    array(
                        'id' => 'contribution_settings',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_SETTINGS'),
                        'icon' => 'bi-database-gear',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'tests_setup',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_TESTS'),
                        'icon' => 'bi-heart-pulse',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'delete',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_RESET'),
                        'icon' => 'bi-trash',
                        'subcards' => false
                    )
                )
            ),

            // === 2) Roles ===
            array(
                'key' => 'roles',
                'label' => $gL10n->get('SYS_ROLES'),
                'panels' => array(
                    array(
                        'id' => 'agestaggered_roles',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES'),
                        'icon' => 'bi-hourglass-split',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'family_roles',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES'),
                        'icon' => 'bi-people',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'multiplier_roles',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_MULTIPLIER_ROLES'),
                        'icon' => 'bi-magic',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'advanced_role_editing',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_ADVANCED_ROLE_EDITING'),
                        'icon' => 'bi-person-gear',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'events_selection',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_EVENTS_SELECTION'),
                        'icon' => 'bi-calendar-range',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'individual_contributions',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_INDIVIDUAL_CONTRIBUTIONS'),
                        'icon' => 'bi-house-gear',
                        'subcards' => false
                    )
                )
            ),

            // === 3) Export & Communication===
            array(
                'key' => 'export',
                'label' => $gL10n->get('PLG_MEMBERSHIPFEE_EXPORT') . ' & ' . $gL10n->get('SYS_COMMUNICATION'),
                'panels' => array(
                    array(
                        'id' => 'sepa',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_SEPA'),
                        'icon' => 'bi-download',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'bill',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_BILL'),
                        'icon' => 'bi-download',
                        'subcards' => false
                    ),

                    array(
                        'id' => 'email_notifications',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_EMAIL_NOTIFICATIONS'),
                        'icon' => 'bi-envelope',
                        'subcards' => false
                    )
                )
            ),

            // === 4) System ===
            array(
                'key' => 'system',
                'label' => $gL10n->get('SYS_SYSTEM'),
                'panels' => array(

                    array(
                        'id' => 'account_data',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA'),
                        'icon' => 'bi-bank',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'mandate_management',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT'),
                        'icon' => 'bi-sliders2',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'view_definitions',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_VIEW_DEFINITIONS'),
                        'icon' => 'bi-eyeglasses',
                        'subcards' => false
                    ),
                    array(
                        'id' => 'uninstallation',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_UNINSTALLATION'),
                        'icon' => 'bi-trash',
                        'subcards' => false
                    )
                )
            ),

            // === 5) Security ===
            array(
                'key' => 'security',
                'label' => $gL10n->get('SYS_SECURITY'),
                'panels' => array(
                    array(
                        'id' => 'access',
                        'title' => $gL10n->get('PLG_MEMBERSHIPFEE_ACCESS_PREFERENCES'),
                        'icon' => 'bi-key',
                        'subcards' => false
                    )
                )
            )
        );
    }

    /**
     * Generates the html of the form from the contribution settings preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the contribution settings preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createContributionSettingsForm(): string
    {
        global $gL10n, $gSettingsManager, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formContributionSettings = new FormPresenter('adm_preferences_form_contributionsettings', '../templates/preferences.contributionsettings.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'contribution_settings'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formContributionSettings->addInput('beitrag_prefix', $gL10n->get('PLG_MEMBERSHIPFEE_PREFIX'), $pPreferences->config['Beitrag']['beitrag_prefix'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_PREFIX_DESC'
        ));
        $formContributionSettings->addInput('beitrag_suffix', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_SUFFIX'), $pPreferences->config['Beitrag']['beitrag_suffix'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_SUFFIX_DESC'
        ));

        $formContributionSettings->addCheckbox('beitrag_anteilig', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PRORATA'), $pPreferences->config['Beitrag']['beitrag_anteilig'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_PRORATA_DESC',
            'helpTextIdLabel' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_PRORATA_DESC2'
        ));
        $formContributionSettings->addCustomContent('beitrag_anteilig_desc2', '', '<small>' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PRORATA_DESC2') . '</small>');

        $formContributionSettings->addCheckbox('beitrag_abrunden', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_ROUNDDOWN'), $pPreferences->config['Beitrag']['beitrag_abrunden'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_ROUNDDOWN_DESC'
        ));
        $formContributionSettings->addInput('beitrag_mindestbetrag', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_MINCALC') . ' ' . $gSettingsManager->getString('system_currency'), $pPreferences->config['Beitrag']['beitrag_mindestbetrag'], array(
            'type' => 'number',
            'minNumber' => 0,
            'maxNumber' => 999,
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_MINCALC_DESC'
        ));
        $formContributionSettings->addCheckbox('beitrag_textmitnam', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_TEXT_MEMNAMES'), $pPreferences->config['Beitrag']['beitrag_textmitnam'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_TEXT_MEMNAMES_DESC'
        ));
        $formContributionSettings->addCheckbox('beitrag_textmitfam', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_TEXT_FAMNAMES'), $pPreferences->config['Beitrag']['beitrag_textmitfam'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_TEXT_FAMNAMES_DESC'
        ));
        $selectBoxEntries = array(
            '#' => ' &nbsp ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_BLANK'),
            '.' => '. ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_DOT'),
            ',' => ', ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_COMMA'),
            '-' => '- ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_HYPHEN'),
            '/' => '/ ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_SLASH'),
            '+' => '+ ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PLUS'),
            '*' => '* ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_TIMES') . '(*)',
            '%' => '% ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PERCENT') . '(*)'
        );
        $formContributionSettings->addSelectBox('beitrag_text_token', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_TEXT_TOKEN'), $selectBoxEntries, array(
            'defaultValue' => $pPreferences->config['Beitrag']['beitrag_text_token'],
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTRIBUTION_TEXT_TOKEN_DESC',
            'showContextDependentFirstEntry' => false
        ));
        $formContributionSettings->addInput('beitrag_role_separator', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_SEPARATOR'), $pPreferences->config['Beitrag']['beitrag_role_separator'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_ROLE_SEPARATOR_DESC',
            'helpTextIdLabel' => 'PLG_MEMBERSHIPFEE_ROLE_SEPARATOR_DESC2'
        ));
        $formContributionSettings->addCustomContent('role_seperator_desc2', '', '<small>' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_SEPARATOR_DESC2') . '</small>');

        $formContributionSettings->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formContributionSettings->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formContributionSettings);
        return $smarty->fetch('../templates/preferences.contributionsettings.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the tests setup preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the tests setup preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createTestsSetupForm(): string
    {
        global $gDb, $gL10n, $gCurrentOrgId, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $familienrollen = beitragsrollen_einlesen('fam');
        $altersrollen = beitragsrollen_einlesen('alt');
        $fixrollen = beitragsrollen_einlesen('fix');

        $conf_array = array();

        $formTestsSetup = new FormPresenter('adm_preferences_form_testssetup', '../templates/preferences.testssetup.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'tests_setup'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formTestsSetup->addCheckbox('age_staggered_roles', $gL10n->get('PLG_MEMBERSHIPFEE_TEST') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['age_staggered_roles']);

        $formTestsSetup->addCheckbox('role_membership_age_staggered_roles', $gL10n->get('PLG_MEMBERSHIPFEE_TEST') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['role_membership_age_staggered_roles']);
        $formTestsSetup->addCustomContent('age_staggered_roles_cust_cont', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES'), '', array(
            'helpTextId' => $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_DESC_LABEL') . '<br><br>' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_AGE_STAGGERED_ROLES_DESC2')
        ));
        if (count($altersrollen) > 0) {
            if (count($altersrollen) > 0) {
                foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $token) {
                    $formTestsSetup->addCheckbox('age_staggered_roles_exclusion' . $token, $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES') . ' (' . $token . ')', (in_array($token, $pPreferences->config['Rollenpruefung']['age_staggered_roles_exclusion']) ? 1 : 0));
                }
            }
        }

        $formTestsSetup->addCheckbox('role_membership_duty_and_exclusion', $gL10n->get('PLG_MEMBERSHIPFEE_TESTS') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_DUTY') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_AND') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_EXCLUSION') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['role_membership_duty_and_exclusion']);
        $formTestsSetup->addCustomContent('role_membership_duty_cust_cont', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_DUTY'), '', array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_DUTY_DESC2'
        ));
        if ((count($altersrollen) > 0) || (count($familienrollen) > 0) || (count($fixrollen) > 0)) {
            if (count($altersrollen) > 0) {
                foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $token) {
                    $formTestsSetup->addCheckbox('altersrollenpflicht' . $token, $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES') . ' (' . $token . ')', (in_array($token, $pPreferences->config['Rollenpruefung']['altersrollenpflicht']) ? 1 : 0));
                }
            }
            if (count($familienrollen) > 0) {
                $formTestsSetup->addCheckbox('familienrollenpflicht', $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES'), $pPreferences->config['Rollenpruefung']['familienrollenpflicht']);
            }
            if (count($fixrollen) > 0) {
                foreach ($fixrollen as $key => $data) {
                    $formTestsSetup->addCheckbox('fixrollenpflicht' . $key, $data['rolle'], (in_array($key, $pPreferences->config['Rollenpruefung']['fixrollenpflicht']) ? 1 : 0));
                }
            }
        }

        $formTestsSetup->addCustomContent('role_membertship_exclusion_cust_cont', $gL10n->get('PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_EXCLUSION'), '', array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_ROLE_MEMBERSHIP_EXCLUSION_DESC2'
        ));
        if (((count($altersrollen) > 0) && (count($familienrollen) > 0)) || count($fixrollen) > 0) {
            if ((count($pPreferences->config['Altersrollen']['altersrollen_token']) > 1)) {
                for ($x = 0; $x < count($pPreferences->config['Altersrollen']['altersrollen_token']) - 1; $x ++) {
                    for ($y = $x + 1; $y < count($pPreferences->config['Altersrollen']['altersrollen_token']); $y ++) {
                        $formTestsSetup->addCheckbox('altersrollenaltersrollen' . $pPreferences->config['Altersrollen']['altersrollen_token'][$x] . $pPreferences->config['Altersrollen']['altersrollen_token'][$y], $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES') . ' (' . $pPreferences->config['Altersrollen']['altersrollen_token'][$x] . ') ./. ' . $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES') . ' (' . $pPreferences->config['Altersrollen']['altersrollen_token'][$y] . ')', (in_array($pPreferences->config['Altersrollen']['altersrollen_token'][$x] . ',' . $pPreferences->config['Altersrollen']['altersrollen_token'][$y], $pPreferences->config['Rollenpruefung']['altersrollenaltersrollen']) ? 1 : 0));
                        $conf_array['altersrollenaltersrollen'][] = 'altersrollenaltersrollen' . $pPreferences->config['Altersrollen']['altersrollen_token'][$x] . $pPreferences->config['Altersrollen']['altersrollen_token'][$y];
                    }
                }
            }
            if ((count($altersrollen) > 0) && (count($familienrollen) > 0)) {
                foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $token) {
                    $formTestsSetup->addCheckbox('altersrollenfamilienrollen' . $token, $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES') . ' (' . $token . ') ./. ' . $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES'), (in_array($token, $pPreferences->config['Rollenpruefung']['altersrollenfamilienrollen']) ? 1 : 0));
                }
            }
            if ((count($altersrollen) > 0) && (count($fixrollen) > 0)) {
                foreach ($fixrollen as $key => $data) {
                    foreach ($pPreferences->config['Altersrollen']['altersrollen_token'] as $token) {
                        $formTestsSetup->addCheckbox('altersrollenfix' . $token . $key, $gL10n->get('PLG_MEMBERSHIPFEE_AGE_STAGGERED_ROLES') . ' (' . $token . ') ./. ' . $data['rolle'], (in_array($token . $key, $pPreferences->config['Rollenpruefung']['altersrollenfix']) ? 1 : 0));
                    }
                }
            }
            if ((count($familienrollen) > 0) && (count($fixrollen) > 0)) {
                foreach ($fixrollen as $key => $data) {
                    $formTestsSetup->addCheckbox('familienrollenfix' . $key, $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES') . ' ./. ' . $data['rolle'], (in_array($key, $pPreferences->config['Rollenpruefung']['familienrollenfix']) ? 1 : 0));
                }
            }

            if (count($fixrollen) > 1) {
                $fixrollenL = $fixrollen;
                array_pop($fixrollenL); // das letzte Element entfernen
                $fixrollenR = $fixrollen;

                foreach ($fixrollenL as $keyL => $dataL) {
                    unset($fixrollenR[$keyL]); // dasselbe Element entfernen
                    foreach ($fixrollenR as $keyR => $dataR) {
                        $formTestsSetup->addCheckbox('fixrollenfixrollen' . $keyL . '_' . $keyR, $dataL['rolle'] . ' ./. ' . $dataR['rolle'], (in_array($keyL . '_' . $keyR, $pPreferences->config['Rollenpruefung']['fixrollenfixrollen']) ? 1 : 0));
                        $conf_array['fixrollenfixrollen'][] = 'fixrollenfixrollen' . $keyL . '_' . $keyR;
                    }
                }
                unset($fixrollenL);
                unset($fixrollenR);
            }
        }

        $sql = 'SELECT cat_id, cat_name
          FROM ' . TBL_CATEGORIES . ' , ' . TBL_ROLES . '
         WHERE cat_id = rol_cat_id
           AND ( cat_org_id = ' . $gCurrentOrgId . '
            OR cat_org_id IS NULL )';
        $formTestsSetup->addSelectBoxFromSql('bezugskategorie', $gL10n->get('PLG_MEMBERSHIPFEE_CAT_SELECTION'), $gDb, $sql, array(
            'defaultValue' => $pPreferences->config['Rollenpruefung']['bezugskategorie'],
            'multiselect' => true,
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CAT_SELECTION_DESC'
        ));

        $num_familyroles = count($pPreferences->config['Familienrollen']['familienrollen_prefix']);
        if ($num_familyroles > 0) {
            $formTestsSetup->addCheckbox('family_roles', $gL10n->get('PLG_MEMBERSHIPFEE_TEST') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['family_roles']);
            $formTestsSetup->addCustomContent('family_roles_cust_cont', $gL10n->get('PLG_MEMBERSHIPFEE_FAMILY_ROLES'), '', array(
                'helpTextId' => 'PLG_MEMBERSHIPFEE_FAMILY_ROLES_ROLE_TEST_DESC2'
            ));

            for ($conf = 0; $conf < $num_familyroles; $conf ++) {
                $formTestsSetup->addInput('familienrollen_pruefung' . $conf, $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], $pPreferences->config['Familienrollen']['familienrollen_pruefung'][$conf]);
            }
        }

        $formTestsSetup->addCheckbox('account_details', $gL10n->get('PLG_MEMBERSHIPFEE_TEST') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_ACCOUNT_DATA') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['account_details']);
        $formTestsSetup->addCheckbox('mandate_management', $gL10n->get('PLG_MEMBERSHIPFEE_TEST') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['mandate_management']);
        $formTestsSetup->addCheckbox('iban_check', $gL10n->get('PLG_MEMBERSHIPFEE_TEST') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_IBANCHECK') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['iban_check']);
        $formTestsSetup->addCheckbox('bic_check', $gL10n->get('PLG_MEMBERSHIPFEE_TEST') . ' "' . $gL10n->get('PLG_MEMBERSHIPFEE_BICCHECK') . '" ' . $gL10n->get('PLG_MEMBERSHIPFEE_ENABLE'), (bool) $pPreferences->config['tests_enable']['bic_check']);

        $formTestsSetup->addSubmitButton('adm_button_save_testssetup', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $smarty->assign('familienrollen', $familienrollen);
        $smarty->assign('altersrollen', $altersrollen);
        $smarty->assign('fixrollen', $fixrollen);
        $smarty->assign('pPreferences', $pPreferences);
        $smarty->assign('conf_array', $conf_array);
        $smarty->assign('num_familyroles', $num_familyroles);

        $formTestsSetup->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formTestsSetup);
        return $smarty->fetch('../templates/preferences.testssetup.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the delete preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the delete preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createDeleteForm(): string
    {
        global $gL10n, $gCurrentOrgId, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $duedatecount = 0;
        $paidcount = 0;

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
            if (! empty($memberdata['DUEDATE' . $gCurrentOrgId])) {
                $duedatecount ++;
            }
            if (! empty($memberdata['PAID' . $gCurrentOrgId])) {
                $paidcount ++;
            }
        }
        unset($members);
        $beitrag = analyse_mem();

        $formDelete = new FormPresenter('adm_preferences_form_delete', '../templates/preferences.delete.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'delete'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $radioButtonEntries = array(
            'delete_all' => $gL10n->get('PLG_MEMBERSHIPFEE_DELETE_ALL') . ' (' . ($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']) . ')<br><small>' . $gL10n->get('PLG_MEMBERSHIPFEE_DELETE_ALL_DESC') . '</small><br><br>',
            'with_paid' => $gL10n->get('PLG_MEMBERSHIPFEE_WITH_PAID') . ' (' . ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl']) . ')<br><small>' . $gL10n->get('PLG_MEMBERSHIPFEE_WITH_PAID_DESC') . '</small><br><br>',
            'without_paid' => $gL10n->get('PLG_MEMBERSHIPFEE_WITHOUT_PAID') . ' (' . (($beitrag['BEITRAG_kto_anzahl'] + $beitrag['BEITRAG_rech_anzahl']) - ($beitrag['BEZAHLT_kto_anzahl'] + $beitrag['BEZAHLT_rech_anzahl'])) . ')<br><small>' . $gL10n->get('PLG_MEMBERSHIPFEE_WITHOUT_PAID_DESC') . '</small><br><br>',
            'paid_only' => $gL10n->get('PLG_MEMBERSHIPFEE_PAID_ONLY') . ' (' . $paidcount . ')<br><small>' . $gL10n->get('PLG_MEMBERSHIPFEE_PAID_ONLY_DESC') . '</small><br><br>',
            'duedate_only' => $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE_ONLY') . ' (' . $duedatecount . ')<br><small>' . $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE_ONLY_DESC') . '</small>'
        );
        $formDelete->addRadioButton('delete', '', $radioButtonEntries, array(
            'defaultValue' => 'delete_all'
        ));

        $formDelete->addSubmitButton('adm_button_delete', $gL10n->get('PLG_MEMBERSHIPFEE_DELETE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formDelete->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formDelete);
        return $smarty->fetch('../templates/preferences.delete.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the age staggered roles preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the age staggered roles preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAgeStaggeredRolesForm(): string
    {
        global $gL10n, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $num_agestaggeredroles = count($pPreferences->config['Altersrollen']['altersrollen_token']);

        $formAgeStaggeredRoles = new FormPresenter('adm_preferences_form_agestaggeredroles', '../templates/preferences.agestaggeredroles.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'agestaggered_roles'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formAgeStaggeredRoles->addInput('altersrollen_offset', $gL10n->get('PLG_MEMBERSHIPFEE_OFFSET'), $pPreferences->config['Altersrollen']['altersrollen_offset'], array(
            'type' => 'number',
            'step' => 1,
            'minNumber' => - 99,
            'maxNumber' => 99,
            'helpTextId' => 'PLG_MEMBERSHIPFEE_OFFSET_DESC',
            'helpTextIdLabel' => 'PLG_MEMBERSHIPFEE_OFFSET_INFO'
        ));
        $formAgeStaggeredRoles->addCustomContent('altersrollen_offset_info', '', '<small>' . $gL10n->get('PLG_MEMBERSHIPFEE_OFFSET_INFO') . '</small>');
        $formAgeStaggeredRoles->addCustomContent('delimiter', $gL10n->get('PLG_MEMBERSHIPFEE_DELIMITER'), '', array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_DELIMITER_DESC'
        ));

        $html = $gL10n->get('PLG_MEMBERSHIPFEE_DELIMITER_INFO1') . '<strong><br/>' . $gL10n->get('PLG_MEMBERSHIPFEE_DELIMITER_INFO2') . ' </strong>' . $gL10n->get('PLG_MEMBERSHIPFEE_DELIMITER_INFO3');
        $formAgeStaggeredRoles->addCustomContent('delimiter_info', '', $html);

        for ($conf = 0; $conf < $num_agestaggeredroles; $conf ++) {
            $formAgeStaggeredRoles->addInput('altersrollen_token' . $conf, ($conf + 1) . '. ' . $gL10n->get('PLG_MEMBERSHIPFEE_STAGGERING'), $pPreferences->config['Altersrollen']['altersrollen_token'][$conf], array(
                'maxLength' => 1,
                'property' => FormPresenter::FIELD_REQUIRED
            ));

            if ($num_agestaggeredroles != 1) {
                $html = '<a id="add_config" class="icon-text-link" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
                    'panel' => 'agestaggered_roles',
                    'mode' => 'set',
                    'conf' => $conf
                )) . '">
                            <i class="bi bi-trash"></i> ' . $gL10n->get('PLG_MEMBERSHIPFEE_DELETE_CONFIG') . '
                        </a>';
                $formAgeStaggeredRoles->addCustomContent('add_trash' . $conf, '', $html);
            }
        }

        $html = '<a id="add_config" class="icon-text-link" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'panel' => 'agestaggered_roles',
            'mode' => 'set',
            'conf' => - 1
        )) . '">
                    <i class="bi bi-copy"></i> ' . $gL10n->get('PLG_MEMBERSHIPFEE_ADD_ANOTHER_CONFIG') . '
                </a>';
        $htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><i class="bi bi-exclamation-triangle"></i>' . $gL10n->get('PLG_MEMBERSHIPFEE_NOT_SAVED_SETTINGS_LOST') . '</div>';
        $formAgeStaggeredRoles->addCustomContent('add_config', '', $html);

        $formAgeStaggeredRoles->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $smarty->assign('num_agestaggeredroles', $num_agestaggeredroles);
        $formAgeStaggeredRoles->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formAgeStaggeredRoles);
        return $smarty->fetch('../templates/preferences.agestaggeredroles.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the family roles preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the family roles preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createFamilyRolesForm(): string
    {
        global $gDb, $gL10n, $gSettingsManager, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $num_familyroles = count($pPreferences->config['Familienrollen']['familienrollen_prefix']);

        $formFamilyRoles = new FormPresenter('adm_preferences_form_familyroles', '../templates/preferences.familyroles.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'family_roles'
        )), null, array(
            'class' => 'form-preferences'
        ));

        for ($conf = 0; $conf < $num_familyroles; $conf ++) {

            $formFamilyRoles->addInput('familienrollen_prefix' . $conf, $gL10n->get('PLG_MEMBERSHIPFEE_PREFIX'), $pPreferences->config['Familienrollen']['familienrollen_prefix'][$conf], array(
                'helpTextId' => 'PLG_MEMBERSHIPFEE_FAMILY_ROLES_PREFIX_DESC',
                'property' => FormPresenter::FIELD_REQUIRED
            ));

            $formFamilyRoles->addInput('familienrollen_beitrag' . $conf, $gL10n->get('SYS_CONTRIBUTION') . ' ' . $gSettingsManager->getString('system_currency'), $pPreferences->config['Familienrollen']['familienrollen_beitrag'][$conf], array(
                'helpTextId' => 'PLG_MEMBERSHIPFEE_FAMILY_ROLES_CONTRIBUTION_DESC',
                'type' => 'number',
                'minNumber' => - 99999,
                'maxNumber' => 99999,
                'step' => 0.01
            ));

            $selectBoxEntries = array(
                '--',
                - 1,
                1,
                2,
                4,
                12
            );
            $role = new Role($gDb);
            $formFamilyRoles->addSelectBox('familienrollen_zeitraum' . $conf, $gL10n->get('SYS_CONTRIBUTION_PERIOD'), $role->getCostPeriods(), array(
                'firstEntry' => '',
                'defaultValue' => $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][$conf],
                'helpTextId' => 'PLG_MEMBERSHIPFEE_FAMILY_ROLES_CONTRIBUTION_PERIOD_DESC',
                'showContextDependentFirstEntry' => false
            ));

            $formFamilyRoles->addInput('familienrollen_beschreibung' . $conf, $gL10n->get('SYS_DESCRIPTION'), $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][$conf], array(
                'helpTextId' => 'PLG_MEMBERSHIPFEE_FAMILY_ROLES_DESCRIPTION_DESC'
            ));

            if ($num_familyroles != 1) {
                $html = '<a id="add_trash" class="icon-text-link" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
                    'panel' => 'family_roles',
                    'mode' => 'set',
                    'conf' => $conf
                )) . '">
                            <i class="bi bi-trash"></i> ' . $gL10n->get('PLG_MEMBERSHIPFEE_DELETE_CONFIG') . '
                        </a>';
                $formFamilyRoles->addCustomContent('add_trash' . $conf, '', $html);
            }
        }

        $html = '<a id="add_config" class="icon-text-link" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'panel' => 'family_roles',
            'mode' => 'set',
            'conf' => - 1
        )) . '">
                    <i class="bi bi-copy"></i> ' . $gL10n->get('PLG_MEMBERSHIPFEE_ADD_ANOTHER_CONFIG') . '
                </a>';
        $htmlDesc = '<div class="alert alert-warning alert-small" role="alert"><i class="bi bi-exclamation-triangle"></i>' . $gL10n->get('PLG_MEMBERSHIPFEE_NOT_SAVED_SETTINGS_LOST') . '</div>';
        $formFamilyRoles->addCustomContent('add_config', '', $html);

        $formFamilyRoles->addSubmitButton('adm_button_save_familyroles', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $smarty->assign('num_familyroles', $num_familyroles);
        $formFamilyRoles->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formFamilyRoles);
        return $smarty->fetch('../templates/preferences.familyroles.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the multiplier roles preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the multiplier roles preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createMultiplierRolesForm(): string
    {
        global $gL10n, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $familienrollen = beitragsrollen_einlesen('fam');

        $formMultiplierRoles = new FormPresenter('adm_preferences_form_multiplierroles', '../templates/preferences.multiplierroles.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'multiplier_roles'
        )), null, array(
            'class' => 'form-preferences'
        ));

        if (count($familienrollen) > 0) {
            $selectBoxEntries = array();
            foreach ($familienrollen as $key => $data) {
                $selectBoxEntries[$key] = $data['rolle'];
            }
            asort($selectBoxEntries);
            $formMultiplierRoles->addSelectBox('multiplier_roles', '', $selectBoxEntries, array(
                'defaultValue' => $pPreferences->config['multiplier']['roles'],
                'multiselect' => true
            ));
        }

        $html = '<div class="alert alert-warning alert-small" role="alert"><i class="bi bi-exclamation-triangle"></i>' . $gL10n->get('PLG_MEMBERSHIPFEE_NO_ROLES') . '</div>';
        $formMultiplierRoles->addCustomContent('alert_no_roles', '', $html);

        $formMultiplierRoles->addCustomContent('desc', '', '<small>' . $gL10n->get('PLG_MEMBERSHIPFEE_MULTIPLIER_ROLES_DESC') . '</small>');

        $formMultiplierRoles->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formMultiplierRoles->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formMultiplierRoles);

        return $smarty->fetch('../templates/preferences.multiplierroles.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the advanced role editing preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the advanced role editing preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAdvancedRoleEditingForm(): string
    {
        global $gDb, $gL10n, $gSettingsManager, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $altersrollen = beitragsrollen_einlesen('alt');
        $fixrollen = beitragsrollen_einlesen('fix');
        $alt_fix_rollen = $altersrollen + $fixrollen;

        $formAdvancedRoleEditing = new FormPresenter('adm_preferences_form_advancedroleediting', '../templates/preferences.advancedroleediting.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'advanced_role_editing'
        )), null, array(
            'class' => 'form-preferences'
        ));

        foreach ($alt_fix_rollen as $key => $data) {

            $formAdvancedRoleEditing->addInput('rol_cost' . $key, $gL10n->get('SYS_CONTRIBUTION') . ' ' . $gSettingsManager->getString('system_currency'), $data['rol_cost'], array(
                'type' => 'number',
                'minNumber' => - 99999,
                'maxNumber' => 99999,
                'step' => 0.01
            ));

            $formAdvancedRoleEditing->addSelectBox('rol_cost_period' . $key, $gL10n->get('SYS_CONTRIBUTION_PERIOD'), Role::getCostPeriods(), array(
                'defaultValue' => $data['rol_cost_period']
            ));

            $formAdvancedRoleEditing->addInput('rol_description' . $key, $gL10n->get('SYS_DESCRIPTION'), $data['rol_description']);
        }

        $formAdvancedRoleEditing->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $smarty->assign('alt_fix_rollen', $alt_fix_rollen);
        $formAdvancedRoleEditing->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formAdvancedRoleEditing);
        return $smarty->fetch('../templates/preferences.advancedroleediting.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the events selection preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the events selection preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createEventsSelectionForm(): string
    {
        global $gL10n, $gDb, $gCurrentSession, $gCurrentOrgId;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $familienrollen = beitragsrollen_einlesen('fam');

        $formEventsSelection = new FormPresenter('adm_preferences_form_eventsselection', '../templates/preferences.eventsselection.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'events_selection'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $sqlData['query'] = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
                               FROM ' . TBL_CATEGORIES . ' as cat, ' . TBL_ROLES . ' as rol
                              WHERE cat.cat_id = rol.rol_cat_id
                                AND rol.rol_cost_period IS NULL
                                AND ( cat.cat_org_id = ?
                                 OR cat.cat_org_id IS NULL )
                                AND cat.cat_name_intern = ? ';

        $sqlData['params'] = array(
            $gCurrentOrgId,
            'EVENTS'
        );

        $formEventsSelection->addSelectBoxFromSql('events_selection', '', $gDb, $sqlData, array(
            'multiselect' => true,
            'helpTextId' => 'PLG_MEMBERSHIPFEE_EVENTS_SELECTION_DESC'
        ));

        $formEventsSelection->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formEventsSelection->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formEventsSelection);
        return $smarty->fetch('../templates/preferences.eventsselection.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the individual contributions preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the individual contributions preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createIndividualContributionsForm(): string
    {
        global $gL10n, $gDb, $gCurrentOrgId, $gCurrentUser, $gProfileFields, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $num_individualcontributions = count($pPreferences->config['individual_contributions']['desc']);

        $formIndividualContributions = new FormPresenter('adm_preferences_form_individualcontributions', '../templates/preferences.individualcontributions.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'individual_contributions'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formIndividualContributions->addCheckbox('individual_contributions_module_enabled', $gL10n->get('PLG_MEMBERSHIPFEE_ACCESS_TO_MODULE_INDIVIDUAL_CONTRIBUTIONS'), $pPreferences->config['individual_contributions']['access_to_module'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_ACCESS_TO_MODULE_INDIVIDUAL_CONTRIBUTIONS_DESC'
        ));

        for ($conf = 0; $conf < $num_individualcontributions; $conf ++) {

            $formIndividualContributions->addDescription('header' . $conf, ($conf + 1) . '. ' . $gL10n->get('PLG_MEMBERSHIPFEE_CONFIGURATION'));

            $formIndividualContributions->addInput('individual_contributions_desc' . $conf, $gL10n->get('PLG_MEMBERSHIPFEE_DESCRIPTION'), $pPreferences->config['individual_contributions']['desc'][$conf], array(
                'property' => FormPresenter::FIELD_REQUIRED,
                'helpTextId' => 'PLG_MEMBERSHIPFEE_DESCRIPTION_DESC'
            ));

            $formIndividualContributions->addInput('individual_contributions_short_desc' . $conf, $gL10n->get('PLG_MEMBERSHIPFEE_SHORT_DESCRIPTION'), $pPreferences->config['individual_contributions']['short_desc'][$conf], array(
                'helpTextId' => 'PLG_MEMBERSHIPFEE_SHORT_DESCRIPTION_DESC'
            ));

            $sql = 'SELECT rol.rol_id, rol.rol_name, cat.cat_name
                      FROM ' . TBL_CATEGORIES . ' as cat, ' . TBL_ROLES . ' as rol
                     WHERE cat.cat_id = rol.rol_cat_id
                       AND ( cat.cat_org_id = ' . $gCurrentOrgId . '
                        OR cat.cat_org_id IS NULL )
                  ORDER BY cat.cat_name DESC';
            $formIndividualContributions->addSelectBoxFromSql('individual_contributions_role' . $conf, $gL10n->get('PLG_MEMBERSHIPFEE_ROLE'), $gDb, $sql, array(
                'defaultValue' => $pPreferences->config['individual_contributions']['role'][$conf],
                'multiselect' => false,
                'helpTextId' => 'PLG_MEMBERSHIPFEE_ROLE_DESC'
            ));

            $formIndividualContributions->addInput('individual_contributions_amount' . $conf, $gL10n->get('PLG_MEMBERSHIPFEE_AMOUNT'), $pPreferences->config['individual_contributions']['amount'][$conf], array(
                'helpTextId' => 'PLG_MEMBERSHIPFEE_AMOUNT_DESC'
            ));

            $fieldSelectionList2 = array();

            foreach ($gProfileFields->getProfileFields() as $field) {
                if ($field->getValue('usf_hidden') == 0 || $gCurrentUser->isAdministratorUsers()) {
                    $fieldSelectionList2[] = array(
                        $field->getValue('usf_id'),
                        addslashes($field->getValue('usf_name')),
                        $field->getValue('cat_name')
                    );
                }
            }

            $formIndividualContributions->addSelectBox('individual_contributions_profilefield' . $conf, $gL10n->get('SYS_PROFILE_FIELD'), $fieldSelectionList2, array(
                'firstEntry' => '',
                'defaultValue' => $pPreferences->config['individual_contributions']['profilefield'][$conf],
                'showContextDependentFirstEntry' => true,
                'helpTextId' => 'PLG_MEMBERSHIPFEE_PROFILE_FIELD_DESC'
            ));

            if ($num_individualcontributions != 1) {
                $html = '<a id="add_trash" class="icon-text-link" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
                    'panel' => 'individual_contributions',
                    'mode' => 'set',

                    'conf' => $conf
                )) . '">
                            <i class="bi bi-trash"></i> ' . $gL10n->get('PLG_MEMBERSHIPFEE_DELETE_CONFIG') . '
                        </a>';
                $formIndividualContributions->addCustomContent('add_trash' . $conf, '', $html);
            }
        }

        $html = '<a id="add_config" class="icon-text-link" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'panel' => 'individual_contributions',
            'mode' => 'set',
            'conf' => - 1
        )) . '">
                    <i class="bi bi-copy"></i> ' . $gL10n->get('PLG_MEMBERSHIPFEE_ADD_ANOTHER_CONFIG') . '
                </a>';

        $formIndividualContributions->addCustomContent('add_config', '', $html);

        $formIndividualContributions->addSubmitButton('adm_button_save_individualcontributions', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $formIndividualContributions->addDescription('line', '<hr>');

        $smarty = $this->getSmartyTemplate();
        $smarty->assign('num_individualcontributions', $num_individualcontributions);
        $formIndividualContributions->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formIndividualContributions);
        return $smarty->fetch('../templates/preferences.individualcontributions.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the sepa preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the sepa preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createSepaForm(): string
    {
        global $gL10n, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $selectBoxEntries = array(
            'xlsx' => $gL10n->get('SYS_MICROSOFT_EXCEL') . ' (XLSX)',
            'csv-ms' => $gL10n->get('SYS_MICROSOFT_EXCEL') . ' (CSV)',
            'csv-oo' => $gL10n->get('SYS_CSV') . ' (' . $gL10n->get('SYS_UTF8') . ')'
        );

        $formSepa = new FormPresenter('adm_preferences_form_sepa', '../templates/preferences.sepa.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'sepa'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formSepa->addInput('dateiname', $gL10n->get('PLG_MEMBERSHIPFEE_XML_FILE_NAME'), $pPreferences->config['SEPA']['dateiname'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_XML_FILE_NAME_DESC',
            'property' => FormPresenter::FIELD_REQUIRED
        ));
        $selectBoxEntriesDirectDebit = array(
            'v2' => 'pain.008.001.02',
            'v8' => 'pain.008.001.08'
        );
        $formSepa->addSelectBox('direct_debit_format', $gL10n->get('PLG_MEMBERSHIPFEE_XML_FILE') . ' ' . $gL10n->get('SYS_FORMAT'), $selectBoxEntriesDirectDebit, array(
            'defaultValue' => $pPreferences->config['SEPA']['direct_debit_format'],
            'showContextDependentFirstEntry' => false
        ));
        $formSepa->addInput('kontroll_dateiname', $gL10n->get('PLG_MEMBERSHIPFEE_CONTROL_FILE_NAME'), $pPreferences->config['SEPA']['kontroll_dateiname'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_CONTROL_FILE_NAME_DESC',
            'property' => FormPresenter::FIELD_REQUIRED
        ));
        $formSepa->addSelectBox('kontroll_dateityp', $gL10n->get('PLG_MEMBERSHIPFEE_CONTROL_FILE_TYPE'), $selectBoxEntries, array(
            'defaultValue' => $pPreferences->config['SEPA']['kontroll_dateityp'],
            'showContextDependentFirstEntry' => false
        ));
        $formSepa->addInput('vorabinformation_dateiname', $gL10n->get('PLG_MEMBERSHIPFEE_PRE_NOTIFICATION_FILE_NAME'), $pPreferences->config['SEPA']['vorabinformation_dateiname'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_NAME_WITHOUT_ENDING',
            'property' => FormPresenter::FIELD_REQUIRED
        ));
        $formSepa->addSelectBox('vorabinformation_dateityp', $gL10n->get('PLG_MEMBERSHIPFEE_PRE_NOTIFICATION_FILE_TYPE'), $selectBoxEntries, array(
            'defaultValue' => $pPreferences->config['SEPA']['vorabinformation_dateityp'],
            'showContextDependentFirstEntry' => false
        ));
        $formSepa->addCheckbox('duedate_for_all', $gL10n->get('PLG_MEMBERSHIPFEE_DUEDATE_FOR_ALL'), $pPreferences->config['SEPA']['duedate_for_all'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_DUEDATE_FOR_ALL_DESC'
        ));

        $formSepa->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formSepa->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formSepa);
        return $smarty->fetch('../templates/preferences.sepa.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the bill preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the bill preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createBillForm(): string
    {
        global $gL10n, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $selectBoxEntries = array(
            'xlsx' => $gL10n->get('SYS_MICROSOFT_EXCEL') . ' (XLSX)',
            'csv-ms' => $gL10n->get('SYS_MICROSOFT_EXCEL') . ' (CSV)',
            'csv-oo' => $gL10n->get('SYS_CSV') . ' (' . $gL10n->get('SYS_UTF8') . ')'
        );

        $formBill = new FormPresenter('adm_preferences_form_bill', '../templates/preferences.bill.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'bill'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formBill->addInput('rechnung_dateiname', $gL10n->get('PLG_MEMBERSHIPFEE_BILL_FILE_NAME'), $pPreferences->config['Rechnungs-Export']['rechnung_dateiname'], array(
            'helpTextId' => 'PLG_MEMBERSHIPFEE_NAME_WITHOUT_ENDING',
            'property' => FormPresenter::FIELD_REQUIRED
        ));

        $formBill->addSelectBox('rechnung_dateityp', $gL10n->get('PLG_MEMBERSHIPFEE_BILL_FILE_TYPE'), $selectBoxEntries, array(
            'defaultValue' => $pPreferences->config['Rechnungs-Export']['rechnung_dateityp'],
            'showContextDependentFirstEntry' => false
        ));

        $formBill->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formBill->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formBill);
        return $smarty->fetch('../templates/preferences.bill.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the email notifications preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the email notifications preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createEmailNotificationsForm(): string
    {
        global $gDb, $gL10n, $gCurrentSession, $gCurrentOrgId;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formEmailNotifications = new FormPresenter('adm_preferences_form_emailnotifications', '../templates/preferences.emailnotifications.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'email_notifications'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formEmailNotifications->addCustomContent('emailnotifications', $gL10n->get('PLG_MEMBERSHIPFEE_EMAIL_NOTIFICATIONS'), '<p>' . $gL10n->get('PLG_MEMBERSHIPFEE_EMAIL_NOTIFICATIONS_DESC') . ':</p><p>
            <strong>#user_first_name#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_FIRST_NAME') . '<br />
            <strong>#user_last_name#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_LAST_NAME') . '<br />
            <strong>#street#</strong> - ' . $gL10n->get('SYS_STREET') . '<br />
            <strong>#postcode#</strong> - ' . $gL10n->get('SYS_POSTCODE') . '<br />
            <strong>#city#</strong> - ' . $gL10n->get('SYS_CITY') . '<br />
            <strong>#email#</strong> - ' . $gL10n->get('SYS_EMAIL') . '<br />
            <strong>#phone#</strong> - ' . $gL10n->get('SYS_PHONE') . '<br />
            <strong>#mobile#</strong> - ' . $gL10n->get('SYS_MOBILE') . '<br />
            <strong>#birthday#</strong> - ' . $gL10n->get('SYS_BIRTHDAY') . '<br />
            <strong>#organization_long_name#</strong> - ' . $gL10n->get('ORG_VARIABLE_NAME_ORGANIZATION') . '<br />
            <strong>#fee#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_FEE') . '<br />
            <strong>#due_day#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_DUE_DAY') . '<br />
            <strong>#mandate_id#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_MANDATE_ID') . '<br />
            <strong>#mandate_date#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATEDATE') . '<br />
            <strong>#creditor_id#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_CREDITOR_ID') . '<br />
            <strong>#iban#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_IBAN') . '<br />
	        <strong>#iban_obfuscated#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_IBAN_OBFUSCATED') . '<br />
            <strong>#bic#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_BIC') . '<br />
            <strong>#bank#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_BANK') . '<br />
            <strong>#debtor#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_DEBTOR') . '<br />
            <strong>#membership_fee_text#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_VARIABLE_MEMBERSHIP_FEE_TEXT') . '<br />
            <strong >#membernumber#</strong> - ' . $gL10n->get('PLG_MEMBERSHIPFEE_MEMBERNUMBER') . '</p>');

        $text = new Text($gDb);
        $text->readDataByColumns(array(
            'txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS',
            'txt_org_id' => $gCurrentOrgId
        ));
        if ($text->getValue('txt_text') == '') {
            // convert <br /> to a normal line feed
            $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13) . chr(10), $gL10n->get('PLG_MEMBERSHIPFEE_PMBMAIL_CONTRIBUTION_PAYMENTS'));
            $text->setValue('txt_text', $value);
            $text->save();
            $text->readDataByColumns(array(
                'txt_name' => 'PMBMAIL_CONTRIBUTION_PAYMENTS',
                'txt_org_id' => $gCurrentOrgId
            ));
        }
        $formEmailNotifications->addMultilineTextInput('mail_text', $gL10n->get('PLG_MEMBERSHIPFEE_CONTRIBUTION_PAYMENTS_MAIL_TEXT'), $text->getValue('txt_text'), 15);

        $text->readDataByColumns(array(
            'txt_name' => 'PMBMAIL_PRE_NOTIFICATION',
            'txt_org_id' => $gCurrentOrgId
        ));
        if ($text->getValue('txt_text') == '') {
            // convert <br /> to a normal line feed
            $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13) . chr(10), $gL10n->get('PLG_MEMBERSHIPFEE_PMBMAIL_PRE_NOTIFICATION'));
            $text->setValue('txt_text', $value);
            $text->save();
            $text->readDataByColumns(array(
                'txt_name' => 'PMBMAIL_PRE_NOTIFICATION',
                'txt_org_id' => $gCurrentOrgId
            ));
        }
        $formEmailNotifications->addMultilineTextInput('pre_notification_text', $gL10n->get('PLG_MEMBERSHIPFEE_PRE_NOTIFICATION_MAIL_TEXT'), $text->getValue('txt_text'), 15);

        $text->readDataByColumns(array(
            'txt_name' => 'PMBMAIL_BILL',
            'txt_org_id' => $gCurrentOrgId
        ));
        if ($text->getValue('txt_text') == '') {
            // convert <br /> to a normal line feed
            $value = preg_replace('/<br[[:space:]]*\/?[[:space:]]*>/', chr(13) . chr(10), $gL10n->get('PLG_MEMBERSHIPFEE_PMBMAIL_BILL'));
            $text->setValue('txt_text', $value);
            $text->save();
            $text->readDataByColumns(array(
                'txt_name' => 'PMBMAIL_BILL',
                'txt_org_id' => $gCurrentOrgId
            ));
        }
        $formEmailNotifications->addMultilineTextInput('bill_text', $gL10n->get('PLG_MEMBERSHIPFEE_BILL_MAIL_TEXT'), $text->getValue('txt_text'), 15);

        $formEmailNotifications->addSubmitButton('adm_button_save_options', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formEmailNotifications->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formEmailNotifications);
        return $smarty->fetch('../templates/preferences.emailnotifications.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the account data preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the account data preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAccountDataForm(): string
    {
        global $gL10n, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $mandate_change = FALSE;
        if (isset($_SESSION['pMembershipFee']['account_data_mandate_change']) && $_SESSION['pMembershipFee']['account_data_mandate_change']) {
            $mandate_change = TRUE;
        }

        $formAccountData = new FormPresenter('adm_preferences_form_accountdata', '../templates/preferences.accountdata.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'account_data'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formAccountData->addInput('iban', $gL10n->get('PLG_MEMBERSHIPFEE_IBAN'), $pPreferences->config['Kontodaten']['iban'], array(
            'property' => FormPresenter::FIELD_REQUIRED
        ));
        $formAccountData->addInput('bic', $gL10n->get('PLG_MEMBERSHIPFEE_BIC'), $pPreferences->config['Kontodaten']['bic']);
        $formAccountData->addInput('bank', $gL10n->get('PLG_MEMBERSHIPFEE_BANK'), $pPreferences->config['Kontodaten']['bank'], array(
            'property' => FormPresenter::FIELD_REQUIRED
        ));

        $formAccountData->addInput('creditor', $gL10n->get('PLG_MEMBERSHIPFEE_CREDITOR'), $pPreferences->config['Kontodaten']['inhaber'], array(
            'property' => FormPresenter::FIELD_REQUIRED
        ));

        if ($mandate_change) {
            $html = '<a class="iconLink" id="creditorschieben" href="javascript:creditorschieben()">
                        <i class="bi bi-arrow-down" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_MOVE_CREDITOR') . '"></i>
                    </a>';
            $formAccountData->addCustomContent('iconlink_creditorschieben', '', $html);
        }
        if (! empty($pPreferences->config['Kontodaten']['origcreditor']) || $mandate_change) {
            $formAccountData->addInput('origcreditor', $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_CREDITOR'), $pPreferences->config['Kontodaten']['origcreditor']);
        }

        $formAccountData->addInput('ci', $gL10n->get('PLG_MEMBERSHIPFEE_CI'), $pPreferences->config['Kontodaten']['ci'], array(
            'property' => FormPresenter::FIELD_REQUIRED
        ));
        if ($mandate_change) {
            $html = '<a class="iconLink" id="cischieben" href="javascript:cischieben()">
                        <i class="bi bi-arrow-down" title="' . $gL10n->get('PLG_MEMBERSHIPFEE_MOVE_CI') . '"></i>
                    </a>';
            $formAccountData->addCustomContent('iconlink_cischieben', '', $html);
        }
        if (! empty($pPreferences->config['Kontodaten']['origci']) || $mandate_change) {
            $formAccountData->addInput('origci', $gL10n->get('PLG_MEMBERSHIPFEE_ORIG_CI'), $pPreferences->config['Kontodaten']['origci']);
        }
        if ($mandate_change) {
            $html = '<div class="alert alert-warning alert-small" role="alert"><i class="bi bi-exclamation-triangle"></i>' . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_CHANGE_CDTR_INFO') . '</div>';
            $formAccountData->addCustomContent('alert_mandate_change_info', '', $html);
        } else {
            $html = '<a class="icon-text-info" href="' . SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
                'panel' => 'account_data',
                'mode' => 'set'
            )) . '">' . $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_CHANGE') . '</a>';
            $formAccountData->addCustomContent('mandate_change_desc_cust_cont', '', $html, array(
                'helpTextId' => 'PLG_MEMBERSHIPFEE_MANDATE_CHANGE_DESC'
            ));
        }
        $formAccountData->addSubmitButton('adm_button_save_accountdata', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formAccountData->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formAccountData);
        return $smarty->fetch('../templates/preferences.accountdata.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the mandate management preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the mandat _management preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createMandateManagementForm(): string
    {
        global $gDb, $gL10n, $gCurrentOrgId, $gProfileFields, $gCurrentSession;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formMandateManagement = new FormPresenter('adm_preferences_form_mandatemanagement', '../templates/preferences.mandatemanagement.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'mandate_management'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $formMandateManagement->addInput('prefix_fam', $gL10n->get('PLG_MEMBERSHIPFEE_PREFIX_FAM'), $pPreferences->config['Mandatsreferenz']['prefix_fam']);
        $formMandateManagement->addInput('prefix_mem', $gL10n->get('PLG_MEMBERSHIPFEE_PREFIX_MEM'), $pPreferences->config['Mandatsreferenz']['prefix_mem']);
        $formMandateManagement->addInput('prefix_pay', $gL10n->get('PLG_MEMBERSHIPFEE_PREFIX_PAY'), $pPreferences->config['Mandatsreferenz']['prefix_pay']);
        $formMandateManagement->addInput('min_length', $gL10n->get('PLG_MEMBERSHIPFEE_MIN_LENGTH'), $pPreferences->config['Mandatsreferenz']['min_length'], array(
            'type' => 'number',
            'minNumber' => 5,
            'maxNumber' => 35
        ));

        $configSelection = array();
        $i = 0;
        foreach ($gProfileFields->getProfileFields() as $field) {
            // normalerweise sollte für das "Datenfeld für die laufende Nummer" ein Profilfeld mit einer laufenden Nummer (Mitgliedsnummer oder UserID) gewählt werden
            // LAST_NAME oder DEBTOR z.B. darf nicht gewählt werden, da dies zu einem SQL-FEHLER in der Funktion list_members führt
            if (in_array($field->getValue('usf_name_intern'), array(
                'LAST_NAME',
                'FIRST_NAME',
                'DEBTOR',
                'MANDATEID' . $gCurrentOrgId,
                'FEE' . $gCurrentOrgId,
                'CONTRIBUTORY_TEXT' . $gCurrentOrgId,
                'IBAN'
            ))) {
                continue;
            }

            $configSelection[$i][0] = $field->getValue('usf_name_intern');
            $configSelection[$i][1] = addslashes($field->getValue('usf_name'));
            $configSelection[$i][2] = $field->getValue('cat_name');
            $i ++;
        }
        $configSelection[$i][0] = '-- User_ID --';
        $configSelection[$i][1] = '-- User_ID --';
        $configSelection[$i][2] = $gL10n->get('PLG_MEMBERSHIPFEE_ADDITIONAL_FIELDS');
        $formMandateManagement->addSelectBox('data_field', $gL10n->get('PLG_MEMBERSHIPFEE_DATA_FIELD_SERIAL_NUMBER'), $configSelection, array(
            'defaultValue' => $pPreferences->config['Mandatsreferenz']['data_field'],
            'showContextDependentFirstEntry' => false
        ));
        $formMandateManagement->addDescription('mandate_management_desc', $gL10n->get('PLG_MEMBERSHIPFEE_MANDATE_MANAGEMENT_DESC'));

        $formMandateManagement->addSubmitButton('adm_button_save_mandatemanagement', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formMandateManagement->addToSmarty($smarty);
        $gCurrentSession->addFormObject($formMandateManagement);
        return $smarty->fetch('../templates/preferences.mandatemanagement.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the view definitions preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the view definitions preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createViewDefinitionsForm(): string
    {
        $this->assignSmartyVariable('open_viewdefinition_payments', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/viewdefinitions.php', array(
            'view_definition' => 'payments_fields'
        )));
        $this->assignSmartyVariable('open_viewdefinition_mandates', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/viewdefinitions.php', array(
            'view_definition' => 'mandates_fields'
        )));
        $this->assignSmartyVariable('open_viewdefinition_duedates', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/viewdefinitions.php', array(
            'view_definition' => 'duedates_fields'
        )));
        $this->assignSmartyVariable('open_viewdefinition_bill', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/viewdefinitions.php', array(
            'view_definition' => 'bill_fields'
        )));
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.viewdefinitions.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the deinstallation preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the deinstallation preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createUninstallationForm(): string
    {
        $this->assignSmartyVariable('open_uninstall', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/uninstall.php'));
        $smarty = $this->getSmartyTemplate();
        return $smarty->fetch('../templates/preferences.uninstall.plugin.membershipfee.tpl');
    }

    /**
     * Generates the html of the form from the access preferences and will return the complete html.
     *
     * @return string Returns the complete html of the form from the access preferences.
     * @throws Exception
     * @throws \Smarty\Exception
     */
    public function createAccessForm(): string
    {
        global $gL10n, $gCurrentSession, $gCurrentOrgId, $gDb;

        $pPreferences = new ConfigTable();
        $pPreferences->read();

        $formAccess = new FormPresenter('adm_preferences_form_access', '../templates/preferences.access.plugin.membershipfee.tpl', SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php', array(
            'mode' => 'save',
            'panel' => 'Access'
        )), null, array(
            'class' => 'form-preferences'
        ));

        $sql = 'SELECT rol_id, rol_name, cat_name
                  FROM ' . TBL_CATEGORIES . ' , ' . TBL_ROLES . '
                 WHERE cat_id = rol_cat_id
                   AND ( cat_org_id = ' . $gCurrentOrgId . '
                    OR cat_org_id IS NULL )
              ORDER BY cat_sequence, rol_name ASC';

        $formAccess->addSelectBoxFromSql('access_preferences', '', $gDb, $sql, array(
            'defaultValue' => $pPreferences->config['access']['preferences'],
            'helpTextId' => 'PLG_MEMBERSHIPFEE_ACCESS_PREFERENCES_DESC',
            'multiselect' => true
        ));

        $formAccess->addSubmitButton('adm_button_save_access', $gL10n->get('SYS_SAVE'), array(
            'icon' => 'bi-check-lg',
            'class' => 'offset-sm-3'
        ));

        $smarty = $this->getSmartyTemplate();
        $formAccess->addToSmarty($smarty);
        return $smarty->fetch('../templates/preferences.access.plugin.membershipfee.tpl');
    }

    /**
     * Set a panel name that should be opened at a page load.
     *
     * @param string $panelName
     *            Name of the panel that should be opened at a page load.
     * @return void
     */
    public function setPanelToShow(string $panelName): void
    {
        $this->preferencesPanelToShow = $panelName;
    }

    /**
     * Read all available registrations from the database and create the HTML content of this
     * page with the Smarty template engine and write the HTML output to the internal
     * parameter **$pageContent**.
     * If no registration is found, then show a message to the user.
     */
    public function show(): void
    {
        global $gL10n;

        if ($this->preferencesPanelToShow !== '') {
            // open the selected panel
            if ($this->preferencesPanelToShow !== '') {
                $this->addJavascript('
                    // --- Reset Tab active states for large screens
                    $("#adm_preferences_tabs .nav-link").removeClass("active");
                    $("#adm_preferences_tab_content .tab-pane").removeClass("active show");

                    // --- Reset Accordion active states for small screens
                    $("#adm_preferences_accordion [aria-expanded=\'true\']").attr("aria-expanded", "false");
                    $("#adm_preferences_accordion .accordion-button").addClass("collapsed");
                    $("#adm_preferences_accordion .accordion-item").removeClass("show");
                    $("#adm_preferences_accordion .accordion-collapse").removeClass("show");
                    
                    // --- Activate the selected Tab and its content
                    $("#adm_tab_' . $this->preferencesPanelToShow . '").addClass("active");
                    $("#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    
                    // --- For Mobile Accordion: open the desired accordion panel
                    $("#collapse_' . $this->preferencesPanelToShow . '").addClass("show");
                                        
                    // --- Desktop vs. Mobile via jQuery visibility
                    if ($(".d-none.d-md-block").is(":visible")) {
                        // Desktop mode
                        $("#adm_preferences_tabs .nav-link[data-bs-target=\'#adm_tab_' . $this->preferencesPanelToShow . '_content\']").addClass("active");
                        $("#adm_preferences_tab_content .tab-pane#adm_tab_' . $this->preferencesPanelToShow . '_content").addClass("active show");
                    } else {
                        // Mobile mode
                        $("#collapse_' . $this->preferencesPanelToShow . '") . addClass("show") . attr("aria-expanded", "true");
                        $("#heading_' . $this->preferencesPanelToShow . ' .accordion-button") . removeClass("collapsed") . attr("aria-expanded", "true");
                        // --- Hash setzen, damit Bookmark/Scroll stimmt und zum Element scrollen
                        location.hash = "#heading_' . $this->preferencesPanelToShow . '";
                    }
                ', true);
            }
        }

        $this->addJavascript('
            // === 1) Panel laden und Events binden ===
            function loadPreferencesPanel(panelId) {
                var panelContainers = $("[data-preferences-panel=\"" + panelId + "\"]");
                // only load the panel to the container that is currently visible
                var panelContainer = panelContainers.filter(":visible").first();

                if (!panelContainer.length) return;

                // Schritt 1: Spinner einfügen
                panelContainer.html("<div class=\"d-flex justify-content-center align-items-center\" style=\"height: 200px;\"><div class=\"spinner-border text-primary\" role=\"status\"><span class=\"visually-hidden\">Lade...</span></div></div>");

                $.get("' . ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/preferences.php", {
               mode: "html_form",
                    panel: panelId
                }, function(htmlContent) {
                    panelContainer.html(htmlContent);
                    initializePanelInteractions(panelId);
                }).fail(function() {
                    panelContainer.html("<div class=\"text-danger\">Fehler beim Laden</div>");
                });
            }

            // === 2) Innerhalb eines Panels die Klick-Handler anmelden ===
            function initializePanelInteractions(panelId) {
                var panelContainer = $("[data-preferences-panel=\"" + panelId + "\"]");

                // Module Settings visibility
                // Universal handling for module enabled toggle within the current panel container

                // define additional ids that should also be considered for visibility toggling
                var additionalIds = [\'#system_notifications_enabled\'];
                // Look for any input whose id ends with "_module_enabled"
                var selectors = ["[id$=\'_module_enabled\']"].concat(additionalIds);

                var moduleEnabledField = panelContainer.find(selectors.join(", ")).filter(":visible");
                if (moduleEnabledField.length > 0) {
                    // Get all row elements inside the form, excluding the row containing the module enabled field
                    var formElementGroups = panelContainer.find("form div.row")
                        .not(moduleEnabledField.closest("div.row"));

                    // Function to update visibility based on the fields type and state
                    var updateVisibility = function(initialCall) {
                        var isEnabled;
                        if (moduleEnabledField.attr("type") === "checkbox") {
                            isEnabled = moduleEnabledField.is(":checked");
                        } else {
                            isEnabled = moduleEnabledField.val() != 0;
                        }

                        if (initialCall === true) {
                            if (isEnabled) {
                                formElementGroups.show();
                            } else {
                                formElementGroups.hide();
                            }
                        } else {
                            if (isEnabled) {
                                formElementGroups.slideDown("slow");
                            } else {
                                formElementGroups.slideUp("slow");
                            }
                        }
                    };

                    // Set initial state without animation
                    updateVisibility(true);

                    // Update visibility on change
                    moduleEnabledField.on("change", updateVisibility);
                }
            }

            // === 3) Hooks für Desktop-Tabs ===
            $(document).on("shown.bs.tab", "ul#adm_preferences_tabs button.nav-link", function(e) {
                var target = e.target.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
                // scroll to the top of the page
                $("html, body").animate({
                    scrollTop: 0
                }, 500);
            });
            // initial: load the active tab panel
            $("ul#adm_preferences_tabs button.nav-link.active").each(function() {
                var target = this.getAttribute("data-bs-target");
                var match = target && target.match(/^#adm_tab_(.+)_content$/);
                if (match) {
                    loadPreferencesPanel(match[1]);
                }
            });

            // === 4) Hooks für Mobile-Accordion ===
            $(document).on("shown.bs.collapse", "#adm_preferences_accordion .accordion-collapse", function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);

                // scroll to the top of the accordion panel header
                var checkLoaded = setInterval(function(){
                    if ($("#collapse_" + panelId).find(".spinner-border").length === 0) {
                        clearInterval(checkLoaded);
                        $("html, body").animate({
                            scrollTop: $("#heading_" + panelId).offset().top
                        }, 500);
                    }
                }, 100);
            });
            // initial: geöffnetes Accordion-Panel laden
            $("#adm_preferences_accordion .accordion-collapse.show").each(function() {
                var panelId = this.id.replace(/^collapse_/, "");
                loadPreferencesPanel(panelId);
            });

            // === 5) Formular-Submit per AJAX ===
            $(document).on("submit", "form[id^=\"adm_preferences_form_\"]", formSubmit);
      ', true);

        $this->addJavascript('
            function cischieben(){
                var ci = $("input[type=text]#ci").val();
                var origci = $("input[type=text]#origci").val(ci);
                $("input[type=text]#ci").val("");
            };
            function creditorschieben(){
                var creditor = $("input[type=text]#creditor").val();
                var origcreditor = $("input[type=text]#origcreditor").val(creditor);
                $("input[type=text]#creditor").val("");
            };
        ');

        // Load the select2 in case any of the form uses a select box. Unfortunately, each section
        // is loaded on-demand, when there is no HTML page anymore to insert the css/JS file loading,
        // so we need to do it here, even when no selectbox will be used...
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/css/select2.css');
        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/select2-bootstrap-theme/select2-bootstrap-5-theme.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/select2.js');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/select2/js/i18n/' . $gL10n->getLanguageLibs() . '.js');

        $this->addCssFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/css/bootstrap-tabs-x-admidio.css');
        $this->addJavascriptFile(ADMIDIO_URL . FOLDER_LIBS . '/bootstrap-tabs-x/js/bootstrap-tabs-x-admidio.js');

        $this->assignSmartyVariable('preferenceTabs', $this->preferenceTabs);
        $this->addTemplateFile('preferences/preferences.tpl');

        parent::show();
    }
}
