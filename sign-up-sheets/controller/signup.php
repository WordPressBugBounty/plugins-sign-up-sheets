<?php
/**
 * Sign-up Controller
 */

namespace FDSUS\Controller;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use FDSUS\Model\Signup as SignupModel;

class Signup extends PostTypeBase
{
    public function __construct()
    {
        $this->postType = SignupModel::POST_TYPE;

        add_action('init', array(&$this, 'addPostType'), 0);

        parent::__construct();
    }

    /**
     * Add custom post type
     */
    public function addPostType()
    {
        $args = array(
            'labels' => $this->getPostTypeLabels(SignupModel::getName(true), SignupModel::getName()),
            'hierarchical'        => false,
            'supports'            => array(
                'title',
                'editor',
                'author',
            ),
            'public'              => true,
            'show_ui'             => false,
            'show_in_menu'        => false,
            'show_in_nav_menus'   => false,
            'publicly_queryable'  => false,
            'exclude_from_search' => true,
            'has_archive'         => false,
            'query_var'           => true,
            'can_export'          => true,
            'rewrite'             => array('slug' => SignupModel::getBaseSlug()),
            'capability_type'     => SignupModel::POST_TYPE,
            'map_meta_cap'        => true,
            'capabilities'        => $this->getAddCapsArray(SignupModel::POST_TYPE),
        );
        register_post_type(SignupModel::POST_TYPE, $args);
    }
}
