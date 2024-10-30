/**
 * Backbone powered tables.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

( function( $ ) {
	$(
		function() {
			'use strict';
			var mnumi                        = window.MnumiDesigner = window.MnumiDesigner || {};
			mnumi.table                      = mnumi.table || {};
			mnumi.view                       = mnumi.view || {};
			mnumi.view.TablePagination       = wp.Backbone.View.extend(
				{
					events: {
						'click .pagination-links a.first-page': 'onFirstPageClick',
						'click .pagination-links a.prev-page': 'onPrevPageClick',
						'click .pagination-links a.next-page': 'onNextPageClick',
						'click .pagination-links a.last-page': 'onLastPageClick',
					},
					initialize: function( options ) {
						this.options = options;
					},
					render: function() {
						var state         = this.collection.state;
						var disable_first = false;
						var disable_last  = false;
						var disable_prev  = false;
						var disable_next  = false;

						this.$el.html(
							this.template(
								{
									current: state.currentPage || 1,
									total_pages: state.totalPages,
									total_objects: state.totalObjects,
									disable_first: state.currentPage <= 1,
									disable_last: state.currentPage > state.totalPages - 1,
									disable_prev: state.currentPage == 1,
									disable_next: state.currentPage == state.totalPages
								}
							)
						);
						return this;
					},
					onFirstPageClick: function(e) {
						e.preventDefault();
						this.collection.state.data.page = 1;
						this.collection.fetch(
							{
								data: this.collection.state.data
							}
						);
					},
					onPrevPageClick: function(e) {
						e.preventDefault();
						this.collection.state.data.page = this.collection.state.currentPage > 2 ?
						this.collection.state.currentPage - 1 :
						1;
						this.collection.fetch(
							{
								data: this.collection.state.data
							}
						);
					},
					onNextPageClick: function(e) {
						e.preventDefault();
						this.collection.more();
					},
					onLastPageClick: function(e) {
						e.preventDefault();
						this.collection.state.data.page = this.collection.state.totalPages;
						this.collection.fetch(
							{
								data: this.collection.state.data
							}
						);
					}
				}
			);
			mnumi.view.TablePaginationTop    = mnumi.view.TablePagination.extend(
				{
					template: wp.template( 'mnumidesigner-table-pagination-top-view' ),
				}
			);
			mnumi.view.TablePaginationBottom = mnumi.view.TablePagination.extend(
				{
					template: wp.template( 'mnumidesigner-table-pagination-bottom-view' ),
				}
			);
			mnumi.view.TableRow              = wp.Backbone.View.extend(
				{
					tagName:  "tr",
					template: wp.template( 'mnumidesigner-table-row-view' ),
					render: function() {
						this.$el.html( this.template( this.model.toJSON() ) );
						return this;
					}
				}
			);

			mnumi.view.Table = wp.Backbone.View.extend(
				{
					template: wp.template( 'mnumidesigner-table-view' ),
					headerTemplate: wp.template( 'mnumidesigner-table-header-view' ),
					footerTemplate: wp.template( 'mnumidesigner-table-footer-view' ),
					emptyTemplate: wp.template( 'mnumidesigner-table-empty-view' ),
					errorTemplate: wp.template( 'mnumidesigner-table-error-view' ),
					loadingTemplate: wp.template( 'mnumidesigner-table-loading-view' ),

					events : {
						'click .sortable a': 'onSort',
						'click .sorted a': 'onSort',
					},
					initialize: function( options ) {
						this.options = options;
						_.bindAll( this, 'render' );
						this.head = this.$el.find( '#mnumidesigner-list-head' );
						this.list = this.$el.find( '#mnumidesigner-list' );
						this.foot = this.$el.find( '#mnumidesigner-list-foot' );

						this.paginationTop    = this.$el.find( '#mnumidesigner-table-nav-top .pagination-container' );
						this.paginationBottom = this.$el.find( '#mnumidesigner-table-nav-bottom .pagination-container' );

						this.loading = false;
						this.error   = false;
						this.listenTo( this.collection, 'add', this.addOne );
						this.listenTo( this.collection, 'reset', this.addAll );
						this.listenTo( this.collection, 'change', this.addAll );
						this.listenTo( this.collection, 'sync', this.onSync );
						this.listenTo( this.collection, 'request', this.onRequest );
						this.listenTo( this.collection, 'error', this.onError );
						this.listenTo( this.collection, 'remove', this.render );
						this.listenTo( this.collection, 'all', this.render );

						this.render();
					},
					onRequest: function(collection, xhr, options) {
						this.loading = true;
						this.error   = false;
					},
					onSync: function(collection, xhr, options) {
						this.loading = false;
					},
					onError: function(collection, xhr, options) {
						this.loading = false;
						this.error   = true;
					},
					onSort: function(e) {
						if ( ! e.isDefaultPrevented() ) {
							e.preventDefault();

							var currentColumn = $( e.target ).parents( 'th' );
							var orderby       = currentColumn.attr( 'id' );
							var order         = currentColumn.hasClass( 'asc' ) ? 'desc' : 'asc';

							this.collection.state.data.orderby = orderby;
							this.collection.state.data.order   = order;
							this.collection.fetch(
								{
									data: this.collection.state.data
								}
							);
						}
					},
					render: function() {
						var collection = this.collection;
						this.head.html( this.headerTemplate( collection.state.data ) );
						this.foot.html( this.footerTemplate( collection.state.data ) );

						if (collection.length > 0) {
							this.paginationTop.html(
								(new mnumi.view.TablePaginationTop(
									{
										collection: collection
									}
								)).render().el
							);
							this.paginationBottom.html(
								(new mnumi.view.TablePaginationBottom(
									{
										collection: collection
									}
								)).render().el
							);
						}
						if (this.loading) {
							this.list.html( this.loadingTemplate() );
						} else if (this.error) {
							this.list.html( this.errorTemplate() );
						} else if (collection.length < 1) {
							this.list.html( this.emptyTemplate() );
						} else {
							this.addAll();
						}

						return this;
					},
					addOne: function(model) {
						var viewClass = this.options.rowView ? this.options.rowView : TableRowView;
						var view      = new viewClass(
							{
								model: model
							}
						);
						this.list.append( view.render().el );
					},
					addAll: function() {
						this.list.html( '' );
						this.collection.each( this.addOne, this );
					},
				}
			);
		}
	);
})( jQuery );
