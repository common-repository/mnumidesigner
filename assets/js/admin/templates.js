/**
 * Backbone powered templates table.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

( function( $ ) {
	$(
		function() {
			'use strict';
			var NewTemplateView = wp.Backbone.View.extend(
				{
					className: 'new-template',
					template: wp.template( 'mnumidesigner-new-template' ),
					render: function() {
						this.$el.html( this.template() );
						return this;
					},
					setErrors: function(text) {
						this.$el.find( '.errors' ).text( text );
					},
					save: function(options) {
						this.model.save(
							{
								type: this.$el.find( '#project_type' ).val(),
								width: this.$el.find( '#project_width' ).val(),
								height: this.$el.find( '#project_height' ).val(),
								back_url: this.options.back_url
							},
							options
						);
					}
				}
			);

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

							var templates = new Projects();

							var TemplateRowView = TableRow.extend(
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

							var TemplatesView = Table.extend(
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
												'click #search-submit' : 'onSearchClick',
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
										this.onSearchClick( e );
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
										this.onSearchClick( e );
									},
									onSearchClick: function(e) {
										e.preventDefault();
										var s = $( '#mnumidesigner-template-search-input' ).val();
										if ( s.length > 0 ) {
											this.collection.state.data.search = s;
										} else {
											delete this.collection.state.data.search;
										}
										this.collection.fetch(
											{
												data: this.collection.state.data
											}
										);
									},
									onAddClick: function(e) {
										e.preventDefault();
										var view = new NewTemplateView(
											{
												model: new Project(),
												back_url: window.location.href
											}
										);
										$( view.render().el ).dialog(
											{
												show: "fadeIn",
												hide: "fadeOut",
												dialogClass: "mnumidesigner-dialog",
												modal: true,
												title: MnumiDesigner.api.add_new.title,
												closeOnEscape: true,
												width: 'auto',
												position: {
													my: "center",
													at: "top",
													of: window
												},
												buttons: [
													{
														text: 'Ok',
														'class': 'button btn-save',
														click: function(e) {
															e.preventDefault();
															var btn = $( e.currentTarget );
															btn.addClass( 'loading' );
															view.save(
																{
																	error : function(model, response) {
																		console.log( model, response );
																		btn.removeClass( 'loading' );
																		view.setErrors( response.responseJSON.message )
																	},
																	success: function(model, response) {
																		window.location.href = response.redirect;
																		view.setErrors( '' );
																		view.dialog( 'destroy' );
																	}
																}
															);
														}
												},
													{
														text: 'Cancel',
														'class': 'button btn-cancel',
														click: function () {
															$( this ).dialog( 'destroy' );
														}
												}
												]
											}
										);
									}
								}
							);
								var App       = new TemplatesView(
									{
										el: $( '#templates-list' ),
										collection: templates,
										rowView: TemplateRowView
									}
								);

								templates.fetch(
									{
										data: {
											per_page: MnumiDesigner.table.per_page,
											is_pending_removal: false,
											template_id: 'NULL'
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
