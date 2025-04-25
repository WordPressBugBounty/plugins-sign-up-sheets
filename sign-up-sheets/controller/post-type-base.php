<?php
/**
 * Base class for post type controllers
 */

namespace FDSUS\Controller;

use FDSUS\Model\Capabilities;

class PostTypeBase extends Base
{
    /** @var */
    protected $baseSlug;

    /** @var bool */
    protected $removeBaseSlug = false;

    /** @var string[]  */
    private $bodyClasses = array();

    /** @var string  */
    public $postType = '';

    /**
     * PostTypeBase constructor
     */
    public function __construct()
    {
        parent::__construct();

        if ($this->removeBaseSlug) {
            add_filter('post_type_link', array(&$this, 'removeBaseSlug'), 10, 2);
            add_action('pre_get_posts', array(&$this, 'addPostNamesToMainQuery'));
        }
        if (!empty($this->postType)) {
            add_filter('the_title', array(&$this, 'modifyTheTitle'), 10, 2);
        }
        add_filter('body_class', array(&$this, 'modifyBodyClasses'));
    }

    /**
     * Modify body classes
     * Update the $bodyClasses array within any action prior to body_class and this will include it
     *
     * @param $classes
     *
     * @return string[]
     */
    public function modifyBodyClasses($classes)
    {
        $classes = array_merge($classes, $this->bodyClasses);
        return $classes;
    }

    /**
     * Add body class(es)
     *
     * @param string $newClasses space separated list of classes (ex: "fancy-post something-else-fancy")
     */
    protected function addBodyClasses($newClasses)
    {
        $this->bodyClasses = explode(' ', $newClasses);
    }

    /**
     * Modify the title
     *
     * @param string $title The post title.
     * @param int    $id    The post ID - set as optional to prevent issues with plugins that don't include this normally required paramater
     *
     * @return string
     */
    public function modifyTheTitle($title, $id = 0)
    {
        if (get_post_type($id) !== $this->postType) {
            return $title;
        }

        // Sanitize title
        return wp_kses_post($title);
    }

    /**
     * Get post type labels
     *
     * @param string $singular
     * @param string $plural
     *
     * @return array
     */
    protected function getPostTypeLabels($singular, $plural)
    {
        return array(
            'name'               => $plural,
            'singular_name'      => $singular,
            'all_items'          => __('All', 'sign-up-sheets') . ' '  . $plural,
            'add_new'            => __('Add New', 'sign-up-sheets'),
            'add_new_item'       => __('Add New', 'sign-up-sheets') . ' ' . $singular,
            'edit_item'          => __('Edit', 'sign-up-sheets') . ' ' . $singular,
            'new_item'           => __('New', 'sign-up-sheets') . ' ' . $singular,
            'view_item'          => __('View', 'sign-up-sheets') . ' ' . $singular,
            'view_items'         => __('View', 'sign-up-sheets') . ' ' . $plural,
            'search_items'       => __('Search', 'sign-up-sheets') . ' ' . $plural,
            /* translators: %s is replaced with the plural post type name */
            'not_found'          => sprintf(__('No %s found', 'sign-up-sheets'), $plural),
            /* translators: %s is replaced with the plural post type name */
            'not_found_in_trash' => sprintf(__('No %s found in Trash', 'sign-up-sheets'), $plural),
            /* translators: %s is replaced with the singluar post type name */
            'parent_item_colon'  => sprintf(__('Parent %s Record:', 'sign-up-sheets'), $singular),
            'menu_name'          => $plural,
        );
    }

    /**
     * Get add caps array
     *
     * @param string $singular
     * @param string $plural
     *
     * @return array
     */
    protected function getAddCapsArray($singular = '', $plural = '')
    {
        if (empty($singular)) {
            $singular = $this->postType;
        }
        $caps = new Capabilities($singular, $plural);

        return $caps->getAll() + array('read' => 'read');
    }

    /**
     * Remove custom post type base slug from URL
     *
     * @param string   $postLink
     * @param \WP_Post $post
     *
     * @return mixed
     */
    public function removeBaseSlug($postLink, $post)
    {
        if ($this->baseSlug === $post->post_type && 'publish' === $post->post_status) {
            $postLink = str_replace('/' . $post->post_type . '/', '/', $postLink);
        }
        return $postLink;
    }

    /**
     * Add custom post type names to Main Query (for removing base slug in URL)
     *
     * @param \WP_Query $query
     */
    public function addPostNamesToMainQuery(\WP_Query $query)
    {
        // Bail if this is not the main query.
        if (!$query->is_main_query()) {
            return;
        }
        // Bail if this query doesn't match our very specific rewrite rule.
        if (!isset($query->query['page']) || 2 !== count($query->query)) {
            return;
        }
        // Bail if we're not querying based on the post name.
        if (empty($query->query['name'])) {
            return;
        }

        // Add CPT to the list of post types WP will include when it queries based on the post name.
        $query->set('post_type', array('post', 'page', $this->baseSlug));
    }

}
