/**
 * Backbone powered translations table.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

( function( $ ) {
	$(
		function() {
			'use strict';
			var NewTranslationView = wp.Backbone.View.extend(
				{
					className: 'new-translation',
					template: wp.template( 'mnumidesigner-new-translation' ),
					initialize: function( options ) {
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
								name: this.$el.find( '#translation_name' ).val(),
								locale: this.$el.find( '#translation_locale' ).val(),
							},
							options
						);
					}
				}
			);

			var EditTranslationView = wp.Backbone.View.extend(
				{
					className: 'edit-translations-list',
					template: wp.template( 'mnumidesigner-edit-translations' ),
					events : {
						'change .mnumidesigner-translation-entry': 'update'
					},
					initialize: function( options ) {
						_.bindAll( this, 'render' );

						this.listenTo( this.model, 'sync', this.render );

						this.on( 'open', this.load );
					},
					load: function() {
						this.model.fetch( { data: { _embed: true } } );
					},
					update: function(e) {
						this.updateEntry(
							$( e.target ).data( 'entry-id' ),
							e.target.value
						);
					},
					updateEntry: function(entryId, trans) {
						var object = this.model.get( 'translations' )
						.find(
							function(entry) {
								return entry.id == entryId
							}
						);
						if (object) {
							object.translation = trans;
						}
					},
					save: function() {
						this.model.save();
					},
					render: function() {
						this.$el.html( this.template( this.model.toJSON() ) );
						return this;
					},
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

							var Translation  = wp.api.models.Translations;
							var Translations = wp.api.collections.Translations;
							var TableRow     = MnumiDesigner.view.TableRow;
							var Table        = MnumiDesigner.view.Table;

							var translations = new Translations();

							var TranslationRowView = TableRow.extend(
								{
									events : {
										'click .edit' : 'onEditClick',
										'click .delete' : 'onDeleteClick',
									},
									onEditClick: function(e) {
										if ( ! e.isDefaultPrevented() ) {
											e.preventDefault();

											var editView = new EditTranslationView(
												{
													model: this.model
												}
											);
											$( editView.el ).dialog(
												{
													show: "fadeIn",
													hide: "fadeOut",
													dialogClass: "mnumidesigner-dialog",
													modal: true,
													title: MnumiDesigner.api.edit.title,
													closeOnEscape: true,
													width: 800,
													height: 'auto',
													position: {
														my: "center",
														at: "top",
														of: window
													},
													open: function() {
														editView.trigger( 'open' );
													},
													buttons: {
														"OK" : function () {
															editView.save();
															$( this ).dialog( 'destroy' );
														}
													}
												}
											);
										}
									},
									onDeleteClick: function(e) {
										if ( ! e.isDefaultPrevented() ) {
											e.preventDefault();
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
									},
								}
							);

							var TranslationsView = Table.extend(
								{
									tagName:  "",
									events : {
										'click .open-new-language-dialog' : 'onAddClick',
									},
									onAddClick: function(e) {
										e.preventDefault();
										var collection = this.collection;
										var newView    = new NewTranslationView(
											{
												model: new Translation()
											}
										);
										$( newView.el ).dialog(
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
												buttons: {
													"OK" : function () {
														var self = $( this );
														newView.save(
															{
																error : function(model, response) {
																	newView.setErrors( response.responseJSON.message );
																},
																success: function(model, response) {
																	collection.add( model );
																	newView.setErrors( '' );
																	self.dialog( 'destroy' );
																}
															}
														);
													},
													"Cancel" : function () {
														newView.setErrors( '' );
														$( this ).dialog( "destroy" );
													}
												}
											}
										);
									}
								}
							);
							var App              = new TranslationsView(
								{
									el: $( '#translations-list' ),
									collection: translations,
									rowView: TranslationRowView
								}
							);

							translations.fetch(
								{
									data: {
										per_page: MnumiDesigner.table.per_page
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
