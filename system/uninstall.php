<?php
/**
 ***********************************************************************************************
 * Uninstallation of the Admidio plugin BirthdayList
 *
 * @copyright The Admidio Team
 * @see https://www.admidio.org/
 * @license https://www.gnu.org/licenses/gpl-2.0.html GNU General Public License v2.0 only
 *
 * Parameters: 
 * 
 * mode     : security_check - security check
 *            uninst - uninstallation procedure
 *
 ***********************************************************************************************
 */
use Admidio\Categories\Entity\Category;
use Admidio\Infrastructure\Utils\SecurityUtils;
use Admidio\Infrastructure\Exception;
use Admidio\Menu\Entity\MenuEntry;
use Admidio\ProfileFields\Entity\ProfileField;
use Admidio\Roles\Entity\Role;
use Admidio\UI\Presenter\FormPresenter;
use Admidio\UI\Presenter\PagePresenter;
use Plugins\MembershipFee\classes\Config\ConfigTable;

try {
    require_once (__DIR__ . '/../../../system/common.php');
    require_once (__DIR__ . '/common_function.php');

    // only authorized user are allowed to start this module
    if (! isUserAuthorizedForPreferences()) {
        throw new Exception('SYS_NO_RIGHTS');
    }

    $pPreferences = new ConfigTable();
    $pPreferences->read();

    $result = $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_STARTMESSAGE');

    // Initialize and check the parameters
    $getMode = admFuncVariableIsValid($_GET, 'mode', 'string', array(
        'defaultValue' => 'security_check',
        'validValues' => array(
            'security_check',
            'uninst'
        )
    ));

    switch ($getMode) {
        case 'security_check':
            // Sicherheitsabfrage, ob wirklich alles gelöscht werden soll

            global $gL10n;

            $title = $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINSTALLATION');
            $headline = $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINSTALLATION');

            $gNavigation->addUrl(CURRENT_URL, $headline);

            $page = PagePresenter::withHtmlIDAndHeadline('plg-membershipfee-uninstall-html');
            $page->setTitle($title);
            $page->setHeadline($headline);

            $form = new FormPresenter('membershipfee_uninstall_form', '../templates/uninstall.plugin.membershipfee.tpl', '', $page, array(
                'type' => 'default',
                'method' => 'post',
                'setFocus' => false
            ));

            $form->addButton('btn_exit', $gL10n->get('SYS_YES'), array(
                'icon' => 'bi-check-square',
                'link' => SecurityUtils::encodeUrl(ADMIDIO_URL . FOLDER_PLUGINS . PLUGIN_FOLDER . '/system/uninstall.php', array(
                    'mode' => 'uninst'
                )),
                'class' => 'btn-primary'
            ));

            $form->addButton('btn_continue', $gL10n->get('SYS_BACK'), array(
                'icon' => 'bi-backspace',
                'link' => $gNavigation->getPreviousUrl(),
                'class' => 'btn-primary'
            ));

            $form->addToHtmlPage(false);

            $page->show();
            break;

        case 'uninst':
            // Sicherheitsabfrage wurde bestätigt, es darf alles gelöscht werden

            // Zugriffsrolle und Menüpunkt löschen
            if ($pPreferences->config['install']['access_role_id'] == 0 || $pPreferences->config['install']['menu_item_id'] == 0) {
                // nur zur Sicherheit; dass 'access_role_id' und/oder 'menu_item_id'== 0 ist, dürfte eigentlich nicht vorkommen
                $result .= $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_NO_INST_IDS_FOUND');
            } else {
                $result_role = true;

                $role = new Role($gDb, (int) $pPreferences->config['install']['access_role_id']);

                $result_role = $role->delete();
                $result .= ($result_role ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_ACCESS_ROLE_SUCCESS') : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_ACCESS_ROLE_ERROR'));

                $result_menu = false;

                // Alle in der Konfigurationstabelle gespeicherten Zugriffsrollen (org-übergreifend) einlesen
                $access_roles_prefs = $pPreferences->getAllAccessRoles();

                // der Menüpunkt wird nur entfernt, wenn die bei der Installation erzeugte Zugriffsrolle
                // die einzige Rolle ist, die noch in der Konfigurationstabelle gespeichert ist
                if (count($access_roles_prefs) === 1 && $access_roles_prefs[0] == $pPreferences->config['install']['access_role_id']) {
                    $menu = new MenuEntry($gDb, (int) $pPreferences->config['install']['menu_item_id']);
                    $result_menu = $menu->delete();
                    $result .= ($result_menu ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_MENU_ITEM_SUCCESS') : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_MENU_ITEM_ERROR'));
                } else {
                    // Menüpunkt nicht entfernen, wenn er noch für eine andere Organisation verwendet wird
                    $result .= $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_MENU_ITEM_NOT_DELETED');
                }
            }

            // Konfigurationsdaten löschen (nur in aktueller Organisation)
            $result_data = false;
            $result_db = false;

            $sql = 'DELETE FROM ' . $pPreferences->getTableName() . '
        		          WHERE plp_name LIKE ?
        			        AND plp_org_id = ? ';

            $result_data = $gDb->queryPrepared($sql, array(
                $pPreferences->getShortcut() . '__%',
                $gCurrentOrgId
            ));

            // wenn die Tabelle nur Einträge dieses Plugins hatte, sollte sie jetzt leer sein und kann gelöscht werden
            $sql = 'SELECT * FROM ' . $pPreferences->getTableName() . ' ';

            $statement = $gDb->queryPrepared($sql);

            if ($statement->rowCount() == 0) {
                $sql = 'DROP TABLE ' . $pPreferences->getTableName() . ' ';
                $result_db = $gDb->queryPrepared($sql);
            }

            $result .= ($result_data ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_DATA_DELETE_SUCCESS') : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_DATA_DELETE_ERROR'));
            $result .= ($result_db ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_TABLE_DELETE_SUCCESS') : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_TABLE_DELETE_ERROR'));

            // Profilfelder löschen

            // alle von Mitgliedsbeitrag angelegten Profilfelder
            $fieldArray = array();
            $fieldArray[] = 'ACCESSION' . $gCurrentOrgId;
            $fieldArray[] = 'BANK';
            $fieldArray[] = 'BIC';
            $fieldArray[] = 'CONTRIBUTORY_TEXT' . $gCurrentOrgId;
            $fieldArray[] = 'DEBTOR';
            $fieldArray[] = 'DEBTOR_CITY';
            $fieldArray[] = 'DEBTOR_EMAIL';
            $fieldArray[] = 'DEBTOR_POSTCODE';
            $fieldArray[] = 'DEBTOR_STREET';
            $fieldArray[] = 'DUEDATE' . $gCurrentOrgId;
            $fieldArray[] = 'FEE' . $gCurrentOrgId;
            $fieldArray[] = 'IBAN';
            $fieldArray[] = 'MANDATEDATE' . $gCurrentOrgId;
            $fieldArray[] = 'MANDATEID' . $gCurrentOrgId;
            $fieldArray[] = 'MEMBERNUMBER' . $gCurrentOrgId;
            $fieldArray[] = 'ORIG_DEBTOR_AGENT';
            $fieldArray[] = 'ORIG_IBAN';
            $fieldArray[] = 'ORIG_MANDATEID' . $gCurrentOrgId;
            $fieldArray[] = 'PAID' . $gCurrentOrgId;
            $fieldArray[] = 'SEQUENCETYPE' . $gCurrentOrgId;

            $profileField = new ProfileField($gDb);
            $category = new Category($gDb);

            foreach ($fieldArray as $fieldName) {
                $result_profileField = false;
                $catId = '';

                $profileField->readDataByColumns(array(
                    'usf_name_intern' => $fieldName
                ));

                $catId = $profileField->getValue('usf_cat_id');
                $category->readDataById((int) $catId);

                if ($category->getValue('cat_org_id') === $gCurrentOrgId) {
                    // das Profilfeld ist organisationsbezogen und kann deshalb gelöscht werden
                    $result_profileField = $profileField->delete();

                    $result .= ($result_profileField ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_PROFILE_FIELD_SUCCESS', array(
                        $fieldName
                    )) : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_PROFILE_FIELD_ERROR', array(
                        $fieldName
                    )));
                } else {
                    // das Profilfeld ist für alle Orgas, deshalb vor Löschung prüfen, ob es evtl. noch woanders verwendet wird

                    // die aktuellen Konfigurationsdaten wurden bereits gelöscht, wenn jetzt noch eine weitere Installation vorhanden ist, dann nicht löschen
                    if ($pPreferences->getAllPluginInstallations() > 0) {
                        $result .= $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_PROFILE_FIELD_NOT_DELETED', array(
                            $fieldName
                        ));
                    } else {
                        $result_profileField = $profileField->delete();

                        $result .= ($result_profileField ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_PROFILE_FIELD_SUCCESS', array(
                            $fieldName
                        )) : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_PROFILE_FIELD_ERROR', array(
                            $fieldName
                        )));
                    }
                }
            }

            // Maildaten löschen
            $result_mail = false;

            $sql = 'DELETE FROM ' . TBL_TEXTS . '
                          WHERE txt_name LIKE ?
                            AND txt_org_id = ? ';
            $result_mail = $gDb->queryPrepared($sql, array(
                'PMBMAIL_%',
                $gCurrentOrgId
            ));

            $result .= ($result_mail ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_MAIL_TEXTS_SUCCESS') : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_MAIL_TEXTS_ERROR'));

            // alle von Mitgliedsbeitrag angelegten Profilfelder
            $catArray = array();
            $catArray[] = 'ACCOUNT_DATA';
            $catArray[] = 'MANDATE' . $gCurrentOrgId;
            $catArray[] = 'MEMBERSHIP' . $gCurrentOrgId;
            $catArray[] = 'MEMBERSHIP_FEE' . $gCurrentOrgId;

            $catId = $profileField->getValue('usf_cat_id');
            $category->readDataById((int) $catId);

            foreach ($catArray as $cat) {
                $result_category = false;
                $category->readDataByColumns(array(
                    'cat_name_intern' => $cat
                ));

                try {
                    if ($category->getValue('cat_org_id') === $gCurrentOrgId) {
                        // das Profilfeld ist organisationsbezogen und kann deshalb gelöscht werden
                        $result_category = $category->delete();

                        $result .= ($result_category ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_CATEGORY_SUCCESS', array(
                            $cat
                        )) : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_CATEGORY_ERROR', array(
                            $cat
                        )));
                    } else {
                        // das Profilfeld ist für alle Orgas, deshalb vor Löschung prüfen, ob es evtl. noch woanders verwendet wird

                        // die aktuellen Konfigurationsdaten wurden bereits gelöscht, wenn jetzt noch eine weitere Installation vorhanden ist, dann nicht löschen
                        if ($pPreferences->getAllPluginInstallations() > 0) {
                            $result .= $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_CATEGORY_NOT_DELETED', array(
                                $cat
                            ));
                        } else {
                            $result_category = $category->delete();

                            $result .= ($result_category ? $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_CATEGORY_SUCCESS', array(
                                $cat
                            )) : $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_CATEGORY_ERROR', array(
                                $cat
                            )));
                        }
                    }
                } catch (Exception $e) {
                    $result .= $gL10n->get('PLG_MITGLIEDSBEITRAG_UNINST_CATEGORY_NOT_DELETED', array(
                        $cat
                    ));
                }
            }

            // Abschlussmeldung ausgeben
            $gNavigation->clear();
            $gMessage->setForwardUrl($gHomepage);
            $gMessage->show($result);
            break;
    }
} catch (Exception $e) {
    $gMessage->show($e->getMessage());
}