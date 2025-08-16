<?php
/**
 * Page Base class
 */

namespace FDSUS\Controller\Admin;

if (!defined('ABSPATH')) exit; // Exit if accessed directly

use FDSUS\Model\Data;
use FDSUS\Model\Sheet as SheetModel;

class PageBase
{
    /** @var Data */
    protected $data;

    /** @var string */
    protected $menuSlug = '';

    /** @var  */
    protected $parentMenuSlug;

    /** @var bool */
    protected $hideInParentMenu = false;

    /** @var string */
    protected $currentScreen;

    /** @var string */
    protected $hiddenFieldName;

    /** @var string */
    protected $hiddenFieldValue;

    public function __construct()
    {
        $this->data = new Data();
        $this->currentScreen = SheetModel::POST_TYPE . '_page_' . $this->menuSlug;
        $this->hiddenFieldName = 'fdsus_submit_screen';
        $this->hiddenFieldValue = $this->currentScreen;

        add_filter('submenu_file', array(&$this, 'maybeRemoveFromMenu'));
    }

    /**
     * Remove from parent menu item, when configured.
     * Workaround for the bug https://core.trac.wordpress.org/ticket/57579
     *
     * @param $submenuFile
     *
     * @return mixed
     *
     * @see https://stackoverflow.com/a/47577455/1197807
     */
    function maybeRemoveFromMenu($submenuFile)
    {
        if (!$this->hideInParentMenu) {
            return $submenuFile;
        }

        remove_submenu_page($this->parentMenuSlug, $this->menuSlug);

        return $submenuFile;
    }

    /**
     * Is this the current screen for the page
     *
     * @param $currentScreen
     *
     * @return bool
     */
    protected function isCurrentScreen($currentScreen = false)
    {
        if (!$currentScreen) {
            $currentScreen = get_current_screen();
        }

        return $currentScreen->id === $this->currentScreen;
    }
}
