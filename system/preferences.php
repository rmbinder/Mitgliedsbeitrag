<?php
/**
 ***********************************************************************************************
 * MembershipFee preferences
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters:
 *
 * mode     : html           - (default) Show page with all preferences panels
 *            html_form      - Returns the html of the requested form
 *            save           - Save organization preferences
 *            set            - Set preferences
 * panel    : The name of the preferences panel that should be shown or saved.
 ***********************************************************************************************
 */
use Admidio\Infrastructure\Exception;
use Plugins\MembershipFee\classes\Config\ConfigTable;
use Plugins\MembershipFee\classes\Presenter\MembershipfeePreferencesPresenter;
use Plugins\MembershipFee\classes\Service\MembershipfeePreferencesService;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    // only authorized user are allowed to start this module
    if (! isUserAuthorizedForPreferences()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'html',
        'validValues' => array(
            'html',
            'html_form',
            'set',
            'save'
        )
    ));
    $getPanel = admFuncVariableIsValid($_GET, 'panel', 'string');
    $getConf = admFuncVariableIsValid($_GET, 'conf', 'numeric');

    if ($getMode === 'set') {
        if ($getPanel === 'agestaggered_roles') {
            if ($getConf == - 1) {
                $pPreferences->config['Altersrollen']['altersrollen_token'][] = $pPreferences->config_default['Altersrollen']['altersrollen_token'][0];
            } else {
                array_splice($pPreferences->config['Altersrollen']['altersrollen_token'], $getConf, 1);
            }
        } elseif ($getPanel === 'family_roles') {
            if ($getConf == - 1) {
                $pPreferences->config['Familienrollen']['familienrollen_beitrag'][] = $pPreferences->config_default['Familienrollen']['familienrollen_beitrag'][0];
                $pPreferences->config['Familienrollen']['familienrollen_zeitraum'][] = $pPreferences->config_default['Familienrollen']['familienrollen_zeitraum'][0];
                $pPreferences->config['Familienrollen']['familienrollen_beschreibung'][] = $pPreferences->config_default['Familienrollen']['familienrollen_beschreibung'][0];
                $pPreferences->config['Familienrollen']['familienrollen_prefix'][] = $pPreferences->config_default['Familienrollen']['familienrollen_prefix'][0];
                $pPreferences->config['Familienrollen']['familienrollen_pruefung'][] = $pPreferences->config_default['Familienrollen']['familienrollen_pruefung'][0];
            } else {
                array_splice($pPreferences->config['Familienrollen']['familienrollen_beitrag'], $getConf, 1);
                array_splice($pPreferences->config['Familienrollen']['familienrollen_zeitraum'], $getConf, 1);
                array_splice($pPreferences->config['Familienrollen']['familienrollen_beschreibung'], $getConf, 1);
                array_splice($pPreferences->config['Familienrollen']['familienrollen_prefix'], $getConf, 1);
                array_splice($pPreferences->config['Familienrollen']['familienrollen_pruefung'], $getConf, 1);
            }
        } elseif ($getPanel === 'individual_contributions') {
            if ($getConf == - 1) {
                $pPreferences->config['individual_contributions']['desc'][] = $pPreferences->config_default['individual_contributions']['desc'][0];
                $pPreferences->config['individual_contributions']['short_desc'][] = $pPreferences->config_default['individual_contributions']['short_desc'][0];
                $pPreferences->config['individual_contributions']['role'][] = $pPreferences->config_default['individual_contributions']['role'][0];
                $pPreferences->config['individual_contributions']['amount'][] = $pPreferences->config_default['individual_contributions']['amount'][0];
                $pPreferences->config['individual_contributions']['profilefield'][] = $pPreferences->config_default['individual_contributions']['profilefield'][0];
            } else {
                array_splice($pPreferences->config['individual_contributions']['desc'], $getConf, 1);
                array_splice($pPreferences->config['individual_contributions']['short_desc'], $getConf, 1);
                array_splice($pPreferences->config['individual_contributions']['role'], $getConf, 1);
                array_splice($pPreferences->config['individual_contributions']['amount'], $getConf, 1);
                array_splice($pPreferences->config['individual_contributions']['profilefield'], $getConf, 1);
            }
        } elseif ($getPanel === 'account_data') {
            $_SESSION['pMembershipFee']['account_data_mandate_change'] = TRUE;
        }
        $pPreferences->save();
        $getMode = 'html';
    }

    switch ($getMode) {
        case 'html':
            // create html page object
            $page = new MembershipfeePreferencesPresenter($getPanel);

            $gNavigation->addUrl(CURRENT_URL, $page->getHeadline());

            $page->show();
            break;

        case 'save':
            $preferences = new MembershipfeePreferencesService();
            $result = $preferences->save($getPanel, $_POST);

            echo json_encode(array(
                'status' => 'success',
                'message' => $result
            ));
            break;

        // Returns the html of the requested form
        case 'html_form':
            $preferencesUI = new MembershipfeePreferencesPresenter('adm_preferences_form');
            $methodName = 'create' . str_replace('_', '', ucwords($getPanel, '_')) . 'Form';
            echo $preferencesUI->{$methodName}();
            break;
    }
} catch (Throwable $exception) {
    if (in_array($getMode, array(
        'save'
    ))) {
        echo json_encode(array(
            'status' => 'error',
            'message' => $exception->getMessage()
        ));
    } elseif ($getMode === 'html_form') {
        echo $exception->getMessage();
    } else {
        $gMessage->show($exception->getMessage());
    }
}
