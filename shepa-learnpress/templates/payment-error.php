<?php
/**
 * Template for displaying Shepa.com payment error message.
 *
 * This template can be overridden by copying it to yourtheme/learnpress/addons/shepa-payment/payment-error.php.
 *
 * @author   Nasrin Rezaei <nasrinrezaei45@gmail.com>
 * @package  LearnPress/Shepa/templates
 * @version  1.0.0
 */

/**
 * Prevent loading this file directly
 */
defined( 'ABSPATH' ) || exit();
?>

<?php $settings = LP()->settings; ?>

<div class="learn-press-message error ">
	<div><?php echo __( 'Transation failed', 'learnpress-shepa' ); ?></div>
</div>
