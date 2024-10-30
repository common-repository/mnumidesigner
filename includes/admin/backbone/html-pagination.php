<?php
/**
 * Admin backbone template: Pagination
 *
 * @package MnumiDesigner
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tablenav-pages
	<# if ( data.total_pages == 1) { #> one-page
	<# } else if (data.total_pages == 0) { #> no-pages<# }#>
	<?php if ( ! empty( $infinite_scroll ) ) { ?>
	hide-if-js
	<?php } ?>
	">
	<span class="displaying-num">
		<# if ( data.total_objects > 1) { #>
		{{{ data.total_objects }}} items
		<# } else { #>
		{{{ data.total_objects }}} item
		<# } #>
	</span>
	<span class="pagination-links">
		<# var page_links = []; #>

		<# var total_pages_before = '<span class="paging-input">'; #>
		<# var total_pages_after = '</span></span>'; #>

		<# if ( data.disable_first ) { #>
			<# page_links.push('<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&laquo;</span>'); #>
		<# } else { #>
			<# page_links.push('<a class="first-page button" href><span class="screen-reader-text"><?php esc_html_e( 'First page', 'mnumidesigner' ); ?></span><span aria-hidden="true">&laquo;</span></a>'); #>
		<# } #>

		<# if ( data.disable_prev ) { #>
			<# page_links.push('<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&lsaquo;</span>'); #>
		<# } else { #>
			<# page_links.push('<a class="prev-page button" href><span class="screen-reader-text"><?php esc_html_e( 'Previous page', 'mnumidesigner' ); ?></span><span aria-hidden="true">&lsaquo;</span></a>'); #>
		<# } #>

	<?php if ( 'bottom' === $which ) : ?>
		<#
		var html_current_page = data.current;
		total_pages_before = '<span class="screen-reader-text"><?php esc_html_e( 'Current Page', 'mnumidesigner' ); ?></span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">';
		#>
	<?php else : ?>
		<# var html_current_page = '<label for="current-page-selector" class="screen-reader-text"><input class="current-page" id="current-page-selector" type="text" name="paged" value="' + data.current + '" size="' + data.current.toString().length + '" aria-describedby="table-paging" /><span class="tablenav-paging-text">'; #>
	<?php endif; ?>

		<# page_links.push([
			total_pages_before,
			html_current_page,
			' of ',
			'<span class="total-pages">' + data.total_pages + '</span>',
			total_pages_after
		].join('')); #>

		<# if ( data.disable_next ) { #>
			<# page_links.push('<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&rsaquo;</span>'); #>
		<# } else { #>
			<# page_links.push('<a class="next-page button" href><span class="screen-reader-text"><?php esc_html_e( 'Next page', 'mnumidesigner' ); ?></span><span aria-hidden="true">&rsaquo;</span></a>'); #>
		<# } #>

		<# if ( data.disable_last ) { #>
			<# page_links.push('<span class="tablenav-pages-navspan button disabled" aria-hidden="true">&raquo;</span>'); #>
		<# } else { #>
			<# page_links.push('<a class="last-page button" href><span class="screen-reader-text"><?php esc_html_e( 'Last page', 'mnumidesigner' ); ?></span><span aria-hidden="true">&raquo;</span></a>'); #>
		<# } #>
		{{{ page_links.join("") }}}
	</span>
</div>
