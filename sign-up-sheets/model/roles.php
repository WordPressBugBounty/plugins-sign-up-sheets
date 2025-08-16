<?php
/**
 * Roles Model
 */

namespace FDSUS\Model;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use FDSUS\Model\Sheet as SheetModel;
use FDSUS\Model\Signup as SignupModel;
use FDSUS\Model\Capabilities as CapabilitiesModel;

class Roles
{
    protected $rolesAndCaps;

    public function __construct()
    {
    }

    /**
     * Get custom SUS roles
     *
     * @return array
     */
    public function getCustomRoles()
    {
        return array(
            'signup_sheet_manager' => esc_html__('Sign-up Sheet Manager', 'sign-up-sheets'),
            'signup_sheet_viewer' => esc_html__('Sign-up Sheet Viewer', 'sign-up-sheets'),
        );
    }

    /**
     * Get array of roles and their associated SUS capabilities
     *
     * @return array
     */
    public function getRolesAndCaps()
    {
        if (isset($this->rolesAndCaps)) {
            return $this->rolesAndCaps;
        }

        $sheetCaps = new CapabilitiesModel(SheetModel::POST_TYPE);
        $signupCaps = new CapabilitiesModel(SignupModel::POST_TYPE);
        $allCaps = array_merge(array_values($sheetCaps->getPrimitive()), array_values($signupCaps->getPrimitive()));

        $susRoles = get_option('dls_sus_roles');
        if (!is_array($susRoles)) {
            $susRoles = array();
        }

        // Get user-selected roles to have all SUS capabilities
        $this->rolesAndCaps = array_fill_keys($susRoles, null);
        foreach ($this->rolesAndCaps as $role => $caps) {
            $this->rolesAndCaps[$role] = $allCaps;
        }

        // Remaining roles that need SUS capabilities
        $this->rolesAndCaps['administrator'] = $allCaps;
        $this->rolesAndCaps['signup_sheet_manager'] = array_merge(array('read'), $allCaps);
        $this->rolesAndCaps['signup_sheet_viewer'] = array(
            'read',
            $sheetCaps->get('edit_posts'),
            $sheetCaps->get('delete_posts'),
            $signupCaps->get('edit_posts'),
            $signupCaps->get('create_posts'),
        );

        /**
         * Filter for the array of SUS-managed roles and which capabilities should be assigned
         *
         * @param array $rolesAndCaps
         * @param array $susRoles SUS-managed roles
         *
         * @return array
         */
        $this->rolesAndCaps = apply_filters('fdsus_roles_and_caps', $this->rolesAndCaps, $susRoles);

        return $this->rolesAndCaps;
    }

}
