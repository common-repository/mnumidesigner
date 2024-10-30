<?php
/**
 * Admin backbone template: Product
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script type="text/html" id="tmpl-mnumidesigner-product-template-view">
	<div class="mnumidesigner_project_ids_field mnumidesigner-project">
		<h3 class="">
			<div class="preview-wrapper">
				<img src="{{ data.preview }}" class="mnumidesigner-project-preview">
			</div>

			<div class="info">
				<span>{{ data.project_label }}</span><br />
				<span style="color:silver">{{ data.id }} ({{ data.type_label }})</span>
				<div><?php esc_html_e( 'ID', 'mnumidesigner' ); ?>: {{ data.id }}</div>
				<div><?php esc_html_e( 'Pages count', 'mnumidesigner' ); ?>: {{ data.number_of_pages }}</div>
			</div>

			<div class="actions">
				<?php if ( current_user_can( 'attach_mnumidesigner_template' ) ) : ?>
				<button class="remove_row delete"><?php esc_html_e( 'Detach', 'mnumidesigner' ); ?></button>
				<?php endif; ?>
				<?php if ( current_user_can( 'edit_mnumidesigner_template' ) ) : ?>
				<# if (!data.is_global) { #>
				<button class="edit"><?php esc_html_e( 'Edit', 'mnumidesigner' ); ?></button>
				<# } #>
				<?php endif; ?>
			</div>
		</h3>
	</div>
</script>

<script type="text/html" id="tmpl-mnumidesigner-add-existing-templates">
	<div class="filters">
		<form id="filters">
		<ul class="subsubsub ownership-types">
		<# _.mapObject(data.filters.ownership_types, function(label, value) { #>
			<# if ( value != 'trash' ) { #>
				<li>
					<a href="#{{ value }}" <# if (data.currentOwnershipFilter == value) { #> class="current" <# } #>>{{ label }}</a></li>
			<# } #>
		<# }); #>
		</ul>
		<div class="tablenav"><div class="actions">
			<select name="type" class="types">
			<# _.mapObject(data.filters.types, function(label, value) { #>
				<option value="{{ value }}" <# if (data.currentTypeFilter == value) { #> selected <# } #>>{{ label }}</option>
			<# }); #>
			</select>
		</div></div>
		</form>
	</div>
	<table>
		<tbody>
		<# _.each(data.collection, function(project) { #>
			<tr class="existing-template">
				<td class="project-preview">
					<img src="{{ project.preview }}" class="mnumidesigner-project-preview">
				</td>

				<td class="info">
					<span>{{ project.project_label }}</span><br />
					<span style="color:silver">{{ project.id }} ({{ project.type_label }})</span>
				</td>

				<td class="actions">
					<?php if ( current_user_can( 'attach_mnumidesigner_template' ) ) : ?>
					<# if ( ! _.findWhere(data.attachedCollection, { id: project.id }) ) { #>
					<button class="attach button" data-project-id="{{ project.id }}"><?php esc_html_e( 'Attach', 'mnumidesigner' ); ?></button>
					<# }; #>
					<?php endif; ?>
				</td>
			</tr>
		<# }); #>
		</tbody>
	</table>
	<# if ( data.hasMore ) { #>
		<a href="#" class="load-more"><?php esc_html_e( 'Load more', 'mnumidesigner' ); ?></a>
	<# }; #>
</script>
