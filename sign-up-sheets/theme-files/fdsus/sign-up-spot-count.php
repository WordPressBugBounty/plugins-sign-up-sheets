<?php
/**
 * Template for display the sign-up spot count
 *
 * This template can be overridden by copying it to yourtheme/fdsus/sign-up-spot-count.php
 *
 * @package     FetchDesigns
 * @subpackage  Sign_Up_Sheets
 * @see         https://www.fetchdesigns.com/sign-up-sheets-pro-overriding-templates-in-your-theme/
 * @since       2.2.14 (plugin version)
 * @version     1.0.0 (template file version)
 */

/** @var array $args */

/** @var string $spotCount */
$spotCount = $args['spot_count'];
?>

<span class="fdsus-spot-count"><?php echo esc_attr($spotCount) ?></span>
