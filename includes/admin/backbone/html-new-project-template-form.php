<?php
/**
 * Admin field template: Project type
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script type="text/html" id="tmpl-mnumidesigner-new-template">
<div class="errors"></div>
<form onsubmit="event.preventDefault();">
	<table class="widefat">
		<tr>
			<th><label><?php esc_html_e( 'Type', 'mnumidesigner' ); ?></label></th>
			<td>
				<select id="project_type" name="type">
				<?php foreach ( $available_project_types as $project_type ) : ?>
					<option value="<?php echo esc_attr( $project_type ); ?>">
						<?php if ( 'custom' === $project_type ) : ?>
							<?php esc_html_e( 'Custom', 'mnumidesigner' ); ?>
						<?php elseif ( 'album-2pages' === $project_type ) : ?>
							<?php esc_html_e( 'Album', 'mnumidesigner' ); ?>
						<?php elseif ( 'calendar-12m' === $project_type ) : ?>
							<?php esc_html_e( 'Calendar', 'mnumidesigner' ); ?>
						<?php elseif ( 'business-card' === $project_type ) : ?>
							<?php esc_html_e( 'Business card', 'mnumidesigner' ); ?>
						<?php endif; ?>
					</option>
				<?php endforeach; ?>
				</select>
			</td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Width', 'mnumidesigner' ); ?></label></th>
			<td><input id="project_width" name="width" type="number" min="1" class="small-text"></input> mm</td>
		</tr>
		<tr>
			<th><label><?php esc_html_e( 'Height', 'mnumidesigner' ); ?></label></th>
			<td><input id="project_height" name="height" type="number" min="1" class="small-text"></input> mm</td>
		</tr>
	</table>
</form>
</script>
