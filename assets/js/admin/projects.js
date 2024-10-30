/**
 * Backbone powered customer projects table.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

( function( $ ) {
	$(
		function() {
			'use strict';

			wp.api.init(
				{
					'versionString': MnumiDesigner.namespace,
					'apiRoot': MnumiDesigner.apiRoot,
				}
			).done(
				function() {
					wp.api.loadPromise.done(
						function() {

							Backbone.emulateHTTP = MnumiDesigner.emulateHTTP;

							var Project  = wp.api.models.Projects;
							var Projects = wp.api.collections.Projects;
							var TableRow = MnumiDesigner.view.TableRow;
							var Table    = MnumiDesigner.view.Table;

							var projects = new Projects();

							var ProjectRowView = TableRow.extend(
								{
									events : {
										'click .edit' : 'onEditClick',
										'click .duplicate' : 'onDuplicateClick',
										'click .delete' : 'onDeleteClick',
									},
									onEditClick: function(e) {
										if ( ! e.isDefaultPrevented() ) {
											e.preventDefault();
											if ( ! this.model.get( '_links' ).edit ) {
												return;
											}

											wp.apiRequest(
												{
													url: this.model.get( '_links' ).edit[0].href,
													data: {
														back_url: window.location.href
													},
													success: function(response) {
														window.location.href = response.redirect;
													},
													error: function(response) {
														console.log( response );
													}
												}
											);
										}
									},
									onDuplicateClick: function(e) {
										if ( ! e.isDefaultPrevented() ) {
											e.preventDefault();
											if ( ! this.model.get( '_links' ).duplicate ) {
												return;
											}
											var model      = this.model;
											var collection = this.model.collection;

											wp.apiRequest(
												{
													method: 'POST',
													dataType: 'json',
													url: this.model.get( '_links' ).duplicate[0].href,
													success: function(response) {
														console.log( JSON.parse( response.body ) );
														collection.add(
															{
																type: model.get( 'type' ),
																type_label: model.get( 'type_label' ),
																number_of_pages: model.get( 'number_of_pages' ),
																_links: {}
															},
															{ at: 0 }
														);
													},
													error: function(response) {
														console.log( response );
													}
												}
											);
										}
									},
									onDeleteClick: function(e) {
										if ( ! e.isDefaultPrevented() ) {
											e.preventDefault();
											if ( ! this.model.get( '_links' ).delete ) {
												return;
											}
											var collection = this.model.collection;
											this.model.destroy(
												{
													wait: true,
													success:  function(model, response) {
														collection.fetch(
															{
																data: collection.state.data
																}
														);
													},
													error: function(model, response) {
														collection.fetch(
															{
																data: collection.state.data
																}
														);
													}
												}
											);
										}
									}
								}
							);

							var ProjectsView = Table.extend(
								{
									tagName:  "",
									events : function() {
										return _.extend(
											{},
											Table.prototype.events,
											{
												'click .ownership-types a' : 'filterOwnership',
												'change .project-types' : 'filterType',
												'click .open-new-designer-project-dialog' : 'onAddClick',
											}
										);
									},
									filterOwnership: function(e) {
										e.preventDefault();
										var target = $( e.currentTarget );
										target.parents( 'ul' ).find( 'a' ).removeClass( 'current' );
										var type = target.addClass( 'current' ).data( 'type' );
										if ( type ) {
											if (type == 'trash') {
												this.collection.state.data.is_pending_removal = true;
												delete this.collection.state.data.ownershipType;
											} else {
												this.collection.state.data.ownershipType      = type;
												this.collection.state.data.is_pending_removal = false;
											}
										} else {
											delete this.collection.state.data.ownershipType;
											this.collection.state.data.is_pending_removal = false;
										}
										this.collection.fetch(
											{
												data: this.collection.state.data
											}
										);
									},
									filterType: function(e) {
										e.preventDefault();
										var target = $( e.currentTarget );
										var type   = target.val();
										if ( type ) {
											this.collection.state.data.type = type;
										} else {
											delete this.collection.state.data.type;
										}
										this.collection.fetch(
											{
												data: this.collection.state.data
											}
										);
									}
								}
							);
								var App      = new ProjectsView(
									{
										el: $( '#projects-list' ),
										collection: projects,
										rowView: ProjectRowView
									}
								);

								projects.fetch(
									{
										data: {
											per_page: MnumiDesigner.table.per_page,
											is_pending_removal: false,
											is_derived: true
										}
									}
								);
						}
					);
				}
			);
		}
	);
})( jQuery );
