<?php
/**
 * Verification Step template
 */
?>
<h2><?php esc_html_e( 'Install Theme', 'cherry-theme-wizard' ); ?></h2>
<div class="desc"><?php
	esc_html_e( 'Please, enter your Template ID and Order ID to start installation:', 'cherry-theme-wizard' );
?></div>
<div class="theme-wizard-form">
	<?php
		ttw_interface()->add_form_row( array(
			'label'       => esc_html__( 'Template ID:', 'cherry-theme-wizard' ),
			'field'       => 'template_id',
			'placeholder' => esc_html__( 'Enter your template ID here...', 'cherry-theme-wizard' ),
		) );
		ttw_interface()->add_form_row( array(
			'label'       => esc_html__( 'Order ID:', 'cherry-theme-wizard' ),
			'field'       => 'order_id',
			'placeholder' => esc_html__( 'Enter your order ID here...', 'cherry-theme-wizard' ),
		) );
		ttw_interface()->button( array(
			'action' => 'start-install',
			'text'   => esc_html__( 'Start Install', 'cherry-theme-wizard' ),
		) );
	?>
</div>