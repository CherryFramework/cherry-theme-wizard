<?php
/**
 * Install child theme template
 */
$theme_data = get_option( ttw()->settings['options']['parent_data'] );

if ( ! $theme_data ) {
	echo '<div class="theme-wizard-error">' . esc_html__( 'We can\'t find any inforamtion about installed theme. Plaese, return to previous', 'cherry-theme-wizard' ) . '</div>';
	return;
}

?>
<h2><?php esc_html_e( 'Use child theme?', 'cherry-theme-wizard' ); ?></h2>
<div class="desc"><?php
	printf( esc_html__( 'We recommend you to use our child themes generator to get child theme for %s', 'cherry-theme-wizard' ), $theme_data['ThemeName'] );
?></div>
<div class="theme-wizard-form">
	<div class="theme-wizard-radio-wrap"><?php
		ttw_interface()->add_form_radio( array(
			'label'   => esc_html__( 'Continue with parent theme', 'cherry-theme-wizard' ),
			'desc'    => esc_html__( 'Skip child theme installation and continute with parent theme.', 'cherry-theme-wizard' ),
			'field'   => 'use_child',
			'value'   => 'skip_child',
			'checked' => true,
		) );
		ttw_interface()->add_form_radio( array(
			'label'   => esc_html__( 'Use child theme', 'cherry-theme-wizard' ),
			'desc'    => esc_html__( 'Download and install child theme. Note: we recommend doing this, because it is the most safe way to make future modifictaions.', 'cherry-theme-wizard' ),
			'field'   => 'use_child',
			'value'   => 'get_child',
		) );
	?></div>
	<?php
		ttw_interface()->button( array(
			'action' => 'get-child',
			'text'   => esc_html__( 'Continue', 'cherry-theme-wizard' ),
		) );
	?>
</div>