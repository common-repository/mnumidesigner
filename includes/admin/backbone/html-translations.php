<?php
/**
 * Admin backbone template: Translations
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script type="text/html" id="tmpl-mnumidesigner-new-translation">
	<div class="errors"></div>
	<form onsubmit="event.preventDefault();">
		<input name="domain" type="hidden" value="editor">
		<table class="widefat">
			<tr>
				<th><label><?php esc_html_e( 'Name', 'mnumidesigner' ); ?></label></th>
				<td><input id="translation_name" name="name" type="text" class="medium-text"></input></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Base locale', 'mnumidesigner' ); ?></label></th>
				<td>
					<select id="translation_locale" name="locale" type="text" class="medium-text">
						<option value="en"><?php esc_html_e( 'English', 'mnumidesigner' ); ?></option>
						<option value="pl"><?php esc_html_e( 'Polish', 'mnumidesigner' ); ?></option>
					</select>
				</td>
			</tr>
		</table>
	</form>
</script>
<script type="text/html" id="tmpl-mnumidesigner-edit-translations">
	<table class="widefat">
		<thead>
			<tr>
				<td><?php esc_html_e( 'Id', 'mnumidesigner' ); ?></td>
				<td><?php esc_html_e( 'Original', 'mnumidesigner' ); ?></td>
				<td><?php esc_html_e( 'Translation', 'mnumidesigner' ); ?></td>
			</tr>
		</thead>
		<tbody>
			<# _.each(data.translations, function(entry) { #>
			<tr>
				<td>{{ entry.id }}</td>
				<td>{{ entry.original }}</td>
				<td>
					<input class="mnumidesigner-translation-entry" type="text" value="{{ entry.translation }}" data-entry-id="{{ entry.id }}">
				</td>
			</tr>
			<# }); #>
		</tbody>
	</table>
</script>
