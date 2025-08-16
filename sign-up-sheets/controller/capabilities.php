<?php
/**
 * Roles and Capabilities Controller
 */

namespace FDSUS\Controller;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use FDSUS\Model\Capabilities as CapabilitiesModel;
use FDSUS\Model\Roles;
use FDSUS\Model\Sheet as SheetModel;
use FDSUS\Model\Task as TaskModel;
use FDSUS\Model\Signup as SignupModel;
use WP_Roles;

class Capabilities extends Base
{
    /**
     * Construct
     */
    public function __construct()
    {
        add_action('fdsus_activate', array(&$this, 'addAllRolesAndCaps'), 10, 0);
        add_action('fdsus_deactivate', array(&$this, 'removeAllRolesAndCaps'), 10, 0);
        add_action('fdsus_activate_pro', array(&$this, 'resetAllRolesAndCaps'), 10, 0);
        add_action('fdsus_update_db', array(&$this, 'addAllRolesAndCaps'), 20, 0);
        add_action('fdsus_remove_capabilities', array(&$this, 'removeAllRolesAndCaps'), 10, 0);
        add_action('fdsus_set_capabilities', array(&$this, 'addAllRolesAndCaps'), 10, 0);
        add_action('fdsus_settings_page_after_save', array(&$this, 'updateAfterSettingsSave'), 10, 4);
        add_action('fdsus_settings_page_after_reset', array(&$this, 'updateAfterSettingsReset'), 10, 3);

        parent::__construct();
    }

    /**
     * Add custom roles and capabilities
     *
     * @return void
     */
    public function addAllRolesAndCaps()
    {
        $rolesModel = new Roles();
        $disabledRoles = get_option('fdsus_disabled_roles', array());
        if (empty($disabledRoles)) {
            $disabledRoles = array();
        }

        foreach ($rolesModel->getCustomRoles() as $roleKey => $roleLabel) {
            if (in_array($roleKey, $disabledRoles)) {
                continue;
            }
            add_role($roleKey, $roleLabel);
        }
        $this->setCapabilities();
    }

    /**
     * Remove custom roles and capabilities
     *
     * @return void
     */
    public function removeAllRolesAndCaps()
    {
        $rolesKeys = array(
            'signup_sheet_manager',
            'signup_sheet_viewer'
        );
        foreach ($rolesKeys as $roleKey) {
            $role = get_role($roleKey);
            if (is_object($role)) {
                remove_role($roleKey);
            }
        }

        $this->removeCapabilities();
    }

    /**
     * Reset all roles and capabilities
     *
     * @return void
     */
    public function resetAllRolesAndCaps()
    {
        $this->removeAllRolesAndCaps();
        $this->addAllRolesAndCaps();
    }

    /**
     * Add plugin-specific capabilities to all roles that need them
     */
    public function setCapabilities()
    {
        $roles = new Roles();

        foreach ($roles->getRolesAndCaps() as $roleKey => $allowedCaps) {
            $role = get_role($roleKey);
            if (!$role) {
                continue;
            }

            foreach ($allowedCaps as $allowedCap) {
                $role->add_cap($allowedCap);
            }
            reset($allowedCaps);
        }
    }

    /**
     * Remove plugin-specific capabilities from all roles that are managed by SUS
     * (the custom roles created by the plugin and the roles that were configured in Settings)
     */
    public function removeCapabilities()
    {
        // Get all possible caps
        // (prior to v2.3.2 task caps were assigned along with meta caps, so remove all instead of just the allowed ones)
        $sheetCaps = new CapabilitiesModel(SheetModel::POST_TYPE);
        $taskCaps = new CapabilitiesModel(TaskModel::POST_TYPE);
        $signupCaps = new CapabilitiesModel(SignupModel::POST_TYPE);
        $allCaps = array_merge(
            array_values($sheetCaps->getAll()),
            array_values($taskCaps->getAll()),
            array_values($signupCaps->getAll())
        );

        $roles = new Roles();
        foreach ($roles->getRolesAndCaps() as $roleKey => $allowedCaps) {
            $role = get_role($roleKey);
            if (!$role) {
                continue;
            }

            foreach ($allCaps as $cap) {
                $role->remove_cap($cap);
            }
            reset($allCaps);
        }
    }

    /**
     * Update after settings save
     *
     * @param string $optionName
     * @param array $optionValue
     * @param int $numberSaved
     * @param bool $updated
     */
    public function updateAfterSettingsSave($optionName, $optionValue, $numberSaved, $updated)
    {
        if (!$updated || ($optionName !== 'fdsus_disabled_roles' && $optionName !== 'dls_sus_roles')) {
            return;
        }

        $this->resetAllRolesAndCaps();
    }

    /**
     * Update after settings are reset
     *
     * @param string $optionName
     * @param array $optionValue
     * @param int $numberSaved
     */
    public function updateAfterSettingsReset($optionName, $optionValue, $numberSaved)
    {
        if ($optionName !== 'fdsus_disabled_roles' && $optionName !== 'dls_sus_roles') {
            return;
        }

        $this->resetAllRolesAndCaps();
    }
}
