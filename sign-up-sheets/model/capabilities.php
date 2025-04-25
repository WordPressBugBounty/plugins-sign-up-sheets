<?php
/**
 * Capabilities Model
 */

namespace FDSUS\Model;

class Capabilities
{
    /** @var array  */
    protected $metaCaps = array();

    /** @var array  */
    protected $primitiveCaps = array();

    /** @var array  */
    protected $termsCaps = array();

    /** @var string  */
    protected $singular = '';

    /** @var string  */
    protected $plural = '';

    public function __construct($singular, $plural = '')
    {
        if ($plural === '') {
            $plural = $singular . 's';
        }
        $this->singular = $singular;
        $this->plural = $plural;
        $this->set();
    }

    /**
     * Set capabilities
     *
     * @return void
     */
    protected function set()
    {
        $this->metaCaps = array(
            // Meta capabilities - not to be assigned directly to users or roles
            'edit_post'   => "edit_{$this->singular}",
            'read_post'   => "read_{$this->singular}",
            'delete_post' => "delete_{$this->singular}",
        );

        $this->primitiveCaps = array(
            // Primitive capabilities
            'edit_posts'             => "edit_{$this->plural}",
            'edit_others_posts'      => "edit_others_{$this->plural}",
            'delete_posts'           => "delete_{$this->plural}",
            'publish_posts'          => "publish_{$this->plural}",
            'read_private_posts'     => "read_private_{$this->plural}",

            // Primitive capabilities used within the map_meta_cap()
            'delete_private_posts'   => "delete_private_{$this->plural}",
            'delete_published_posts' => "delete_published_{$this->plural}",
            'delete_others_posts'    => "delete_others_{$this->plural}",
            'edit_private_posts'     => "edit_private_{$this->plural}",
            'edit_published_posts'   => "edit_published_{$this->plural}",

            'create_posts'           => "create_{$this->plural}",
        );

        $this->termsCaps = array(
            'manage_terms' => "manage_{$this->plural}",
            'edit_terms'   => "edit_{$this->plural}",
            'delete_terms' => "delete_{$this->plural}",
            //'assign_terms' => '', // Set separately - typically associated with edit_posts of related post type
        );
    }

    /**
     * Filter a list of capabilities by an array of capability keys
     *
     * @param array $caps
     * @param array $requestedCaps Ex: array('edit_posts', 'delete_posts')
     *
     * @return array|mixed
     */
    protected function filterByRequestedCaps($caps, $requestedCaps)
    {
        if (empty($requestedCaps)){
            return $caps;
        }
        return array_intersect_key($caps, array_fill_keys($requestedCaps, null));
    }

    /**
     * Get a single capability by key (i.e. "read_post")
     *
     * @param string $capKey
     *
     * @return string
     */
    public function get($capKey)
    {
        $caps = array_merge($this->getAll(), $this->getTerms());
        return $caps[$capKey];
    }

    /**
     * Get array of all primitive capabilities or filter by requested ones
     *
     * @param array $requestedCaps
     *
     * @return array
     */
    public function getPrimitive($requestedCaps = array())
    {
        return $this->filterByRequestedCaps($this->primitiveCaps, $requestedCaps);
    }

    public function getTerms($requestedCaps = array())
    {
        return $this->filterByRequestedCaps($this->termsCaps, $requestedCaps);
    }

    /**
     * Get array of all capabilities or filter by requested ones (doesn't include terms)
     *
     * @return array
     */
    public function getAll($requestedCaps = array())
    {
        return $this->filterByRequestedCaps($this->metaCaps + $this->primitiveCaps, $requestedCaps);
    }
}
