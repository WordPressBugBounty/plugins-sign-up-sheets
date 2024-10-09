<?php
/**
 * [sign_up_spot_count] Shortcode Controller
 *
 * Examples:
 * [sign_up_spot_count] <-- outputs count for all active sheets
 * [sign_up_spot_count category_slug="foo,bar"] <-- outputs count for the categories with slugs "foo" and "bar"
 *
 * @since 2.2.14
 */

namespace FDSUS\controller\scode;

use FDSUS\Controller\Base;
use FDSUS\Controller\Pro\Scode\TaskModel;
use FDSUS\Id;
use FDSUS\Model\SheetCollection;

class SignUpSpotCount extends Base
{
    public function __construct()
    {
        parent::__construct();
        add_shortcode('sign_up_spot_count', array(&$this, 'shortcode'));
    }

    /**
     * Enqueue plugin css and js files
     */
    function addCssAndJsToSignUp()
    {
        wp_enqueue_script('jquery');
        wp_enqueue_style(Id::PREFIX . '-style');
        wp_enqueue_script('dlssus-js');
    }

    /**
     * Main shortcode
     *
     * @param array $atts attributes from shortcode call
     *
     * @return string shortcode output
     */
    public function shortcode($atts)
    {
        /**
         * Filter shortcode attributes
         *
         * @param array $atts
         *
         * @return array
         * @since 2.2.14
         */
        $atts = apply_filters('fdsus_scode_sign_up_spot_count_attributes', $atts);

        /**
         * @var string $category_slug
         * @var string $empty_spot_text
         */
        extract(
            shortcode_atts(
                array(
                    'category_slug' => '', // single slug or comma-separated for multiples
                    'empty_spot_text' => '0'
                ), $atts
            )
        );

        $this->addCssAndJsToSignUp();

        ob_start();

        $spotCount = $this->getOpenSpotCount($atts);

        $args = array(
            'spot_count'  => $spotCount > 0 ? (string)$spotCount : $empty_spot_text,
        );
        $this->locateTemplate('fdsus/sign-up-spot-count.php', true, false, $args);

        return ob_get_clean();
    }

    protected function getOpenSpotCount($atts)
    {
        $count = 0;
        $args = array();

        /**
         * Filter for sheet collection arguments
         *
         * @param array $args arguments for sheet collection query
         * @param array $atts shortcode attributes
         *
         * @return array
         * @since 2.2.14
         */
        $args = apply_filters('fdsus_scode_sign_up_sheet_collection_args', $args, $atts);

        $sheetCollection = new SheetCollection();
        $sheets = $sheetCollection->get($args);

        foreach ($sheets as $sheet) {
            // Exclude expired sheets
            if (!empty($sheet->dlssus_end_date)) {
                $endDate = strtotime($sheet->dlssus_end_date) + 86400;
                if ($endDate < time()) {
                    continue;
                }
            }

            $count += $sheet->getOpenSpots();
        }

        return $count;
    }
}
