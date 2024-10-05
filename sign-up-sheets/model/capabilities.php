<?php
/**
 * Capabilities Model
 */

namespace FDSUS\Model;

class Capabilities
{
    protected $caps = array();

    public function __construct($capType)
    {
        $this->setArray($capType);
    }

    /**
     * Get add caps array
     *
     * @param string $capType
     *
     * @return array
     */
    protected function setArray($capType)
    {
        return $this->caps = array(
            'edit_post'              => "edit_{$capType}",
            'read_post'              => "read_{$capType}",
            'delete_post'            => "delete_{$capType}",
            'edit_posts'             => "edit_{$capType}s",
            'edit_others_posts'      => "edit_others_{$capType}s",
            'publish_posts'          => "publish_{$capType}s",
            'read_private_posts'     => "read_private_{$capType}s",
            'delete_posts'           => "delete_{$capType}s",
            'delete_private_posts'   => "delete_private_{$capType}s",
            'delete_published_posts' => "delete_published_{$capType}s",
            'delete_others_posts'    => "delete_others_{$capType}s",
            'edit_private_posts'     => "edit_private_{$capType}s",
            'edit_published_posts'   => "edit_published_{$capType}s",
        );
    }

    /**
     * Get capability by key (i.e. "read_post")
     *
     * @param string $capKey
     *
     * @return string
     */
    public function get($capKey)
    {
        return $this->caps[$capKey];
    }

    /**
     * Get array of all capabilities
     *
     * @return array
     */
    public function getAll()
    {
        return $this->caps;
    }
}
