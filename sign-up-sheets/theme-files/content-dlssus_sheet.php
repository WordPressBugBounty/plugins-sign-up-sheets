<?php
/**
 * The default template for displaying content
 *
 * Used for both single and index/archive/search.
 *
 * This template can be overridden by copying it to yourtheme/content-dlssus_sheet.php.
 *
 * @package     FetchDesigns
 * @subpackage  Sign_Up_Sheets
 * @see         https://www.fetchdesigns.com/sign-up-sheets-pro-overriding-templates-in-your-theme/
 * @since       2.1 (plugin version)
 * @version     1.1.1 (template file version)
 */
?>

<div class="<?php echo esc_attr(fdsus_template_classes_sign_up_sheet()) ?>" id="<?php echo get_the_ID() ? 'dls-sus-sheet-' . get_the_ID() : '' ?>">

    <h3><?php esc_attr_e('Sign up below...', 'fdsus'); ?></h3>

    <?php dlssus_the_tasks_table(); ?>

</div><!-- .dls-sus-sheet -->
