/**
 * Backbone powered templates listing in WooCommerce product view.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

(function( $ ) {
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

					var Project             = wp.api.models.Projects;
					var ProductProject      = wp.api.models.ProductsProjects;
					var ProductProjectsList = wp.api.collections.ProductsProjects;
					var ProjectsList        = wp.api.collections.Projects;

					var TemplateDetailView = wp.Backbone.View.extend(
						{
							tagName:  "div",
							template: wp.template( 'mnumidesigner-product-template-view' ),
							events : {
								'click .edit' : 'onEditClick',
								'click .duplicate' : 'onDuplicateClick',
								'click .delete' : 'onDeleteClick',
							},
							initialize: function( options ) {
								this.options = options;
								this.listenTo( this.model, 'change', this.render );
								this.listenTo( this.model, 'destroy', this.remove );
								this.render();
							},
							render: function() {
								this.$el.html( this.template( this.model.toJSON() ) );
								return this;
							},
							onEditClick: function(e) {
								e.preventDefault();
								if ( ! this.model.attributes._links.edit ) {
									return;
								}

								wp.apiRequest(
									{
										url: this.model.attributes._links.edit[0].href,
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
							},
							onDuplicateClick: function(e) {
								e.preventDefault();
								if ( ! this.model.attributes._links.duplicate ) {
									return;
								}

								var model      = this.model;
								var collection = this.model.collection;

								wp.apiRequest(
									{
										method: 'POST',
										dataType: 'json',
										url: this.model.attributes._links.duplicate[0].href,
										success: function(response) {
											console.log( JSON.parse( response.body ) );
										},
										error: function(response) {
											console.log( response );
										}
									}
								);
							},
							onDeleteClick: function(e) {
								e.preventDefault();
								if ( ! this.model.attributes._links.delete ) {
									return;
								}

								var variation_id = this.options.variation_id;
								e.preventDefault();
								if (variation_id) {
									this.model.destroy( { data: { variation_id: variation_id }, processData: true } );
								} else {
									this.model.destroy();
								}
							}
						}
					);

					var ProductProjectsView = wp.Backbone.View.extend(
						{
							events : {
								'click .open-new-designer-project-dialog' : 'onAddNewClick',
								'click .open-add-existing-designer-project-dialog' : 'onAddExistingClick',
							},
							initialize: function(options) {
								this.options = options;
								_.bindAll( this, 'render' );

								this.list = this.$el.find( "#templates-list" );

								this.listenTo( this.collection, 'add', this.addOne );
								this.listenTo( this.collection, 'all', this.render );
								this.listenTo( this.collection, 'change', this.render );
								this.listenTo( this.collection, 'remove', this.render );
								this.listenTo( this.collection, 'sync', this.render );

								if (this.options.variation_id) {
									this.collection.fetch(
										{ data: {
											variation_id: this.options.variation_id
											} }
									);
								} else {
									this.collection.fetch();
								}
							},

							addOne: function(project) {

								var params = { model: project };

								if (this.options.variation_id) {
									params.variation_id = this.options.variation_id;
								}

								var view = new TemplateDetailView( params );
								this.list.append( view.render().el );
							},

							addAll: function() {
								this.collection.each( this.addOne, this );
							},
							onAddNewClick: function(e) {
								e.preventDefault();

								var NewTemplateView = wp.Backbone.View.extend(
									{
										className: 'new-template',
										template: wp.template( 'mnumidesigner-new-template' ),
										initialize: function( options ) {
											this.options = options;
											this.model   = new Project();

											_.bindAll( this, 'render' );
											this.render();
										},
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
								var back_url        = MnumiDesigner.api.add_new.back_url + '?_wpnonce=' + wpApiSettings.nonce;

								if (this.options.variation_id) {
									back_url = back_url + '&variation_id=' + this.options.variation_id;
								}
								var view = new NewTemplateView(
									{
										back_url: back_url
									}
								);
								$( view.el ).dialog(
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
											at: "center",
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
																btn.removeClass( 'loading' );
																view.setErrors( response.responseJSON.message )
															},
															success: function(model, response) {
																window.location.href = response.redirect;
																view.setErrors( '' );
																view.el.dialog( 'close' );
															}
														}
													);
												}
										},
											{
												text: 'Cancel',
												'class': 'button btn-cancel',
												click: function() {
													$( this ).dialog( 'close' );
												}
										}
										]
									}
								);
							},
							onAddExistingClick: function(e) {
								e.preventDefault();
								var projects = new ProjectsList( {} );

								var params = {
									collection: projects,
									attachedCollection: this.collection
								};

								if (this.options.variation_id) {
									params.variation_id = this.options.variation_id;
								}

								var existingView = new AddExistingTemplatesView( params );

								$( existingView.el ).dialog(
									{
										dialogClass: "mnumidesigner-dialog",
										modal: true,
										title: MnumiDesigner.api.add_existing.title,
										closeOnEscape: true,
										maxWidth: 600,
										maxHeight: 500,
										width: 600,
										height: 500,
										position: {
											my: "center",
											at: "center",
											of: window
										},
										open: function() {
											existingView.trigger( 'open' );
										}
									}
								);
							}
						}
					);

					var AddExistingTemplatesView = wp.Backbone.View.extend(
						{
							className: 'existing-templates-list',
							template: wp.template( 'mnumidesigner-add-existing-templates' ),
							events : {
								'click .existing-template .attach' : 'attach',
								'click .ownership-types a' : 'filterOwnership',
								'click .load-more' : 'loadMore',
								'change .types' : 'filterType',
							},
							initialize: function(options) {
								this.options            = options;
								this.attachedCollection = options.attachedCollection;
								_.bindAll( this, 'render' );

								this.listenTo( this.collection, 'sync', this.render );

								this.listenTo( this.attachedCollection, 'sync', this.render );
								this.listenTo( this.attachedCollection, 'change', this.render );

								this.on( 'open', this.load );

								this.filters                = MnumiDesigner.api.add_existing.filters;
								this.currentOwnershipFilter = '';

								return this;
							},
							getFiltersQuery: function() {
								var query         = {};
								query.template_id = null;
								if (this.currentOwnershipFilter) {
									query.ownershipType = this.currentOwnershipFilter;
								}
								if (this.currentTypeFilter) {
									query.type = this.currentTypeFilter;
								}
								return query;
							},
							load: function() {
								this.collection.fetch(
									{
										data: this.getFiltersQuery()
									}
								);
							},
							loadMore: function(e) {
								e.preventDefault();
								// do not remove already loaded entries when fetching another part.
								this.collection.more( { remove: false } );
							},
							attach: function(e) {
								var collection         = this.collection;
								var attachedCollection = this.attachedCollection;
								var projectId          = $( e.currentTarget ).addClass( 'loading' ).data( 'project-id' );
								var project            = collection.where( { id: projectId } )[0];
								var existingProject    = new ProductProject( { id: projectId, parent: MnumiDesigner.product_id } );
								var variation_id       = this.options.variation_id;

								var data = {
									id: projectId
								};
								if (variation_id) {
									data.variation_id = variation_id;
								}

								wp.apiRequest(
									{
										method: 'POST',
										dataType: 'json',
										url: MnumiDesigner.api.add_existing.attach_url,
										data: data,
										success: function(response) {
											$( e.currentTarget ).hide();

											if (variation_id) {
												attachedCollection.fetch(
													{ data: {
														variation_id: variation_id
														} }
												);
											} else {
												attachedCollection.fetch();
											}
										},
										error: function(response) {
											console.log( response );
										}
									}
								);
							},

							filterOwnership: function(e) {
								e.preventDefault();
								this.currentOwnershipFilter = $( e.currentTarget ).attr( 'href' ).substring( 1 );
								this.load();
							},
							filterType: function(e) {
								e.preventDefault();
								this.currentTypeFilter = $( e.currentTarget ).val();
								this.load();
							},
							render: function() {
								this.$el.html(
									this.template(
										{
											collection: this.collection.toJSON(),
											attachedCollection: this.attachedCollection.toJSON(),

											currentOwnershipFilter: this.currentOwnershipFilter,
											currentTypeFilter: this.currentTypeFilter,
											filters: MnumiDesigner.api.add_existing.filters,

											hasMore: this.collection.hasMore()
										}
									)
								);

								return this;
							}
						}
					);

					$( "#woocommerce-product-data .mnumidesigner-simple-product-fields" )
					.each(
						function() {
							var el         = $( this );
							var collection = new ProductProjectsList(
								{},
								{
									parent: MnumiDesigner.product_id
								}
							);

							var App = new ProductProjectsView(
								{
									el: el,
									collection: collection
								}
							);
						}
					);
					$( '#woocommerce-product-data' ).on(
						'woocommerce_variations_loaded',
						function() {
							$( "#woocommerce-product-data .mnumidesigner-variadic-product-fields" )
							.each(
								function() {
									var el         = $( this );
									var collection = new ProductProjectsList(
										{},
										{
											parent: MnumiDesigner.product_id,
											variation_id: el.data( 'variationId' )
										}
									);

									var App = new ProductProjectsView(
										{
											el: el,
											collection: collection,
											variation_id: el.data( 'variationId' )
										}
									);
								}
							);
						}
					);

				}
			);
		}
	);
})( jQuery );
