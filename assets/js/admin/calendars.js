/**
 * Backbone powered calendars table.
 *
 * @author Mnumi
 * @package MnumiDesigner
 */

( function( $ ) {
	$(
		function() {
			'use strict';
			var NewCalendarView = wp.Backbone.View.extend(
				{
					className: 'new-calendar',
					template: wp.template( 'mnumidesigner-new-calendar' ),
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
								name: this.$el.find( '#calendar_name' ).val(),
								type: this.$el.find( '#calendar_type' ).val(),
								locale: this.$el.find( '#calendar_locale' ).val(),
							},
							options
						);
					}
				}
			);

			var EditCalendarView = wp.Backbone.View.extend(
				{
					className: 'edit-calendars-list',
					template: wp.template( 'mnumidesigner-edit-calendars' ),
					events : {
						'change .mnumidesigner-calendar-event': 'update',
						'click #event_add': 'addEvent',
						'click #event_del': 'delEvent'
					},
					initialize: function( options ) {
						_.bindAll( this, 'render' );

						this.listenTo( this.model, 'sync', this.render );

						this.on( 'open', this.load );
					},
					load: function() {
						this.model.fetch( { data: { _embed: true } } );
					},
					addEvent: function() {
						var date   = this.$el.find( '#event_date' );
						var cyclic = this.$el.find( '#event_cyclic' );
						var name   = this.$el.find( '#event_name' );
						var type   = this.$el.find( '#event_type' );
						if ( ! date[0].checkValidity() ||
							! name[0].checkValidity()
						) {
							return;
						}

						this.model.get( 'events' ).push(
							{
								date: date.val(),
								cyclic: cyclic.prop( 'checked' ),
								name: name.val(),
								type: type.val()
							}
						);
						date.val( '' );
						name.val( '' );
						type.val( '' );
						this.render();
					},
					delEvent: function(e) {
						var id = $( e.target ).data( 'entry-id' );
						this.model.get( 'events' ).splice( id, 1 );
						this.render();
					},
					update: function(e) {
						var id    = $( e.target ).data( 'entry-id' );
						var value = e.target.value;
						var name  = e.target.name;
						if (name == 'cyclic') {
							value = $( e.target ).prop( 'checked' );
						}
						this.updateEntry( id, name, value );
						this.render();
					},
					updateEntry: function(entryId, name, value) {
						var object = this.model.get( 'events' )[entryId];

						if (object) {
							object[name] = value;
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

							var Calendar  = wp.api.models.Calendars;
							var Calendars = wp.api.collections.Calendars;
							var TableRow  = MnumiDesigner.view.TableRow;
							var Table     = MnumiDesigner.view.Table;

							var calendars = new Calendars();

							var CalendarRowView = TableRow.extend(
								{
									events : {
										'click .edit' : 'onEditClick',
										'click .delete' : 'onDeleteClick',
									},
									onEditClick: function(e) {
										if ( ! e.isDefaultPrevented() ) {
											e.preventDefault();

											var editView = new EditCalendarView(
												{
													model: this.model
												}
											);
											var modalEl  = editView.render().$el;
											modalEl.dialog(
												{
													show: "fadeIn",
													hide: "fadeOut",
													dialogClass: "mnumidesigner-dialog",
													modal: true,
													title: MnumiDesigner.api.edit.title,
													closeOnEscape: true,
													autoOpen: false,
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
															modalEl.dialog( 'destroy' );
														}
													}
												}
											);
											modalEl.dialog( 'open' );
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

							var CalendarsView = Table.extend(
								{
									tagName:  "",
									events : {
										'click .open-new-calendar-dialog' : 'onAddClick',
									},
									onAddClick: function(e) {
										e.preventDefault();
										var collection = this.collection;
										var newView    = new NewCalendarView(
											{
												model: new Calendar()
											}
										);
										var modalEl    = newView.render().$el;
										modalEl.dialog(
											{
												show: "fadeIn",
												hide: "fadeOut",
												dialogClass: "mnumidesigner-dialog",
												modal: true,
												title: MnumiDesigner.api.add_new.title,
												closeOnEscape: true,
												autoOpen: false,
												width: 'auto',
												position: {
													my: "center",
													at: "center",
													of: window
												},
												close: function(event, ui) {
													newView.remove();
												},
												buttons: {
													"OK" : function () {
														newView.save(
															{
																error : function(model, response) {
																	newView.setErrors( response.responseJSON.message );
																},
																success: function(model, response) {
																	collection.add( model );
																	newView.setErrors( '' );
																	modalEl.dialog( 'destroy' );
																}
															}
														);
													},
													"Cancel" : function () {
														newView.setErrors( '' );
														modalEl.dialog( "destroy" );
													}
												}
											}
										);
										modalEl.dialog( 'open' );
									}
								}
							);
							var App           = new CalendarsView(
								{
									el: $( '#calendars-list' ),
									collection: calendars,
									rowView: CalendarRowView
								}
							);

							calendars.fetch(
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
