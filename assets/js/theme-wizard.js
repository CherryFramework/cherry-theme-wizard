( function( $, settings ) {

	'use strict';

	var tmThemeWizard = {
		css: {
			start: '[data-theme-wizard="start-install"]',
			getChild: '[data-theme-wizard="get-child"]',
			form: '.theme-wizard-form',
			input: '.wizard-input'
		},

		init: function() {

			$( document )
				.on( 'click.tmThemeWizard', tmThemeWizard.css.start, tmThemeWizard.startInstall )
				.on( 'click.tmThemeWizard', tmThemeWizard.css.getChild, tmThemeWizard.maybeGetChild )
				.on( 'focus.tmThemeWizard', tmThemeWizard.css.input, tmThemeWizard.clearErrorsOnFocus );
		},

		startInstall: function( event ) {

			var $this  = $( this ),
				$form  = $this.closest( tmThemeWizard.css.form ),
				$input = $( tmThemeWizard.css.input, $form ),
				errors = false,
				data   = {
					action: 'tm_theme_wizard_verify_data'
				};

			event.preventDefault();

			$input.each( function( index, el ) {
				var $this = $( this ),
					name  = $this.attr( 'name' ),
					val   = $this.val();

				if ( '' === val ) {
					tmThemeWizard.addError( $( this ), settings.errors.empty );
					errors = true;
				}

				data[ name ] = val;
			});

			if ( true === errors || $this.hasClass( 'in-progress' ) ) {
				return;
			}

			$this.addClass( 'in-progress' );

			tmThemeWizard.clearErrors( $input );
			tmThemeWizard.clearLog( $this );
			tmThemeWizard.doRecursiveAjax( $this, data );

		},

		maybeGetChild: function( event ) {

			var $this  = $( this ),
				$form  = $this.closest( tmThemeWizard.css.form ),
				$input = $( 'input[type="radio"]:checked', $form ),
				action = $input.val(),
				data   = {
					action: 'tm_theme_wizard_' + action
				};

			event.preventDefault();

			if ( $this.hasClass( 'in-progress' ) ) {
				return;
			}

			$this.addClass( 'in-progress' );

			tmThemeWizard.clearLog( $this );
			tmThemeWizard.doRecursiveAjax( $this, data );

		},

		doRecursiveAjax: function( $this, data ) {

			data.nonce = settings.nonce;

			$.ajax({
				url: ajaxurl,
				type: 'get',
				dataType: 'json',
				data: data
			}).done( function( response ) {

				if ( true === response.success ) {
					tmThemeWizard.addLog( $this, response.data.message, 'success' );

					if ( true === response.data.doNext ) {
						tmThemeWizard.doRecursiveAjax( $this, response.data.nextRequest );
					}

					if ( undefined !== response.data.redirect ) {
						window.location = response.data.redirect;
					}

					return;
				}

				tmThemeWizard.addLog( $this, response.data.message, 'error' );
				$this.removeClass( 'in-progress' );
			});

		},

		addLog: function ( $target, log, type ) {

			if ( ! $target.next( '.wizard-log' ).length ) {
				$target.after( '<div class="wizard-log"></div>' );
			}

			$target.next( '.wizard-log' ).append(
				'<div class="wizard-log__item type-' + type + '">' + log + '</div>'
			);
		},

		addError: function( $target, error ) {

			if ( $target.hasClass( 'wizard-error' ) ) {
				return;
			}

			$target.addClass( 'wizard-error' ).after('<div class="wizard-error-message">' + error + '</div>');
		},

		clearLog: function( $target ) {
			if ( $target.next( '.wizard-log' ).length > 0 ) {
				$target.next( '.wizard-log' ).remove();
			}
		},

		clearErrors: function( $target ) {

			if ( $target.hasClass( 'wizard-error' ) ) {
				$target.removeClass( 'wizard-error' ).next( '.wizard-error-message' ).remove();
			}

		},

		clearErrorsOnFocus: function( event ) {
			var $this = $( this );
			tmThemeWizard.clearErrors( $this );
		}
	};

	tmThemeWizard.init();

}( jQuery, window.tmThemeWizardSettings ) );