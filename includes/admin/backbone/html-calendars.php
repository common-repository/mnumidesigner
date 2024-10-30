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
<script type="text/html" id="tmpl-mnumidesigner-new-calendar">
	<div class="errors"></div>
	<form onsubmit="event.preventDefault();">
		<input name="domain" type="hidden" value="editor">
		<table class="widefat">
			<tr>
				<th><label><?php esc_html_e( 'Name', 'mnumidesigner' ); ?></label></th>
				<td><input id="calendar_name" name="name" type="text" class="medium-text"></input></td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Type', 'mnumidesigner' ); ?></label></th>
				<td>
					<select id="calendar_type" name="type" type="text" class="medium-text">
						<option value="name-day"><?php esc_html_e( 'Name days', 'mnumidesigner' ); ?></option>
						<option value="national-day"><?php esc_html_e( 'National days', 'mnumidesigner' ); ?></option>
					</select>
				</td>
			</tr>
			<tr>
				<th><label><?php esc_html_e( 'Locale', 'mnumidesigner' ); ?></label></th>
				<td>
				<?php
				wp_dropdown_languages(
					array(
						'id'                       => 'calendar_locale',
						'name'                     => 'locale',
						// returns 'site-default' value for locale, so we turn it off.
						'show_option_site_default' => false,
						// returns empty value for locale, so we turn it off.
						'show_option_en_us'        => false,
					)
				)
				?>
				</td>
			</tr>
		</table>
	</form>
</script>
<script type="text/html" id="tmpl-mnumidesigner-edit-calendars">
	<table class="widefat">
		<thead>
			<tr>
				<td><?php esc_html_e( 'Date', 'mnumidesigner' ); ?></td>
				<td><?php esc_html_e( 'Is cyclic?', 'mnumidesigner' ); ?></td>
				<td><?php esc_html_e( 'Event name', 'mnumidesigner' ); ?></td>
				<# if (data.type === 'national-day') { #>
				<td><?php esc_html_e( 'Event type', 'mnumidesigner' ); ?></td>
				<# } #>
				<td><?php esc_html_e( 'Actions', 'mnumidesigner' ); ?></td>
			</tr>
		</thead>
		<thead>
			<tr>
				<td><input type="date" id="event_date" required></td>
				<td><input type="checkbox" id="event_cyclic"></td>
				<td><input type="text"  id="event_name" class="medium-text" required></input></td>
				<# if (data.type === 'national-day') { #>
				<td>
					<select id="event_type" name="type">
						<option value=""></option>
						<option value="holiday"><?php esc_html_e( 'Holiday', 'mnumidesigner' ); ?></option>
					</select>
				</td>
				<# } #>
				<td><button id="event_add"><?php esc_html_e( 'Add', 'mnumidesigner' ); ?></button></td>
			</tr>
		</thead>
		<tbody>
			<# _.each(data.events, function(event, i) { #>
			<tr>
				<td>
					<input type="date" name="date" data-entry-id="{{ i }}" class="mnumidesigner-calendar-event <# if (event.cyclic) { #>cyclic<# } #>" value="{{ event.date }}">
				</td>
				<td>
					<input type="checkbox" name="cyclic" data-entry-id="{{ i }}" class="mnumidesigner-calendar-event" value="{{ event.cyclic }}" <# if (event.cyclic) { #>checked<# } #>>
				</td>
				<td>
					<input name="name" class="mnumidesigner-calendar-event" data-entry-id="{{ i }}" type="text" value="{{ event.name }}">
				</td>
				<# if (data.type === 'national-day') { #>
				<td>
					<select name="type" data-entry-id="{{ i }}" class="mnumidesigner-calendar-event">
						<option value="" <# if (event.type === '') { #>selected<# } #>></option>
						<option value="holiday" <# if (event.type === 'holiday') { #>selected<# } #>><?php esc_html_e( 'Holiday', 'mnumidesigner' ); ?></option>
					</select>
				</td>
				<# } #>
				<td>
					<button id="event_del" data-entry-id="{{ i }}"><?php esc_html_e( 'Delete', 'mnumidesigner' ); ?></button>
				</td>
			</tr>
			<# }); #>
		</tbody>
	</table>
</script>
