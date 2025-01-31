<?php

/**
 *
 * Plugin Admin View Class.
 *
 * @package SPTP
 * @since   0.1.0
 */

namespace SPTP\Module;

class Admin extends Module {


	public function add_hooks() {
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ) );
		add_action( 'admin_init', array( $this, 'admin_fields' ) );
	}


	public function admin_fields() {

		add_settings_section( 'sptp_setting_section',
			__( 'Custom Post Type Permalink Settings', SPTP_TEXT_DOMAIN ),
			array( $this, 'setting_section' ),
			'permalink'
		);

		$post_types = get_post_types(
			array(
				'publicly_queryable' => true,
				'_builtin'           => false,
			)
		);

		array_walk( $post_types, array( $this, 'add_settings_field' ) );
	}


	public function setting_section() {
		?>
		<p><?php esc_html_e( 'Select permalink setting.', 'simple-post-type-permalinks' ); ?>
			<?php echo wp_kses_post(__( 'Available tags are <code>%post_id%</code>, <code>%postname%</code>, <code>%year%</code>, <code>%monthnum%</code>, <code>%day%</code>, <code>%hour%</code>, <code>%minute%</code>, <code>%second%</code>, <code>%author%</code>.', 'simple-post-type-permalinks' )); ?></p>

		<?php

	}

	/**
	 *
	 * register setting field row.
	 *
	 * @param string $post_type
	 */
	public function add_settings_field( $post_type ) {

		add_settings_field( "sptp_{$post_type}_structure",
			$post_type,
			array( $this, 'setting_field' ),
			'permalink',
			'sptp_setting_section',
			"sptp_{$post_type}_structure"
		);
		register_setting( 'permalink', "sptp_{$post_type}_structure" );
	}

	private function get_example_settings( $slug ) {
		return array(
			false,
			"/{$slug}/%post_id%",
			"/{$slug}/%postname%.html",
			"/{$slug}/%author%/%postname%",
			"/{$slug}/%year%/%monthnum%/%day%/%postname%/",
		);
	}


	/**
	 *
	 * setting field row.
	 *
	 * @param $args
	 */
	public function setting_field( $args ) {

		global /** @var \WP_Rewrite $wp_rewrite */
		$wp_rewrite;
		$slash = '';

		if ( $wp_rewrite->use_trailing_slashes ) {
			$slash = '/';
		}

		preg_match( '/sptp_(.+)_structure/', $args, $matches );
		$post_type        = $matches[1];
		$post_type_object = get_post_type_object( $post_type );

		$with_front = $post_type_object->rewrite['with_front'];
		$slug       = trim( $post_type_object->rewrite['slug'], '/' );
		if ( ! empty( $post_type_object->rewrite['original_slug'] ) ) {
			$slug = trim( $post_type_object->rewrite['original_slug'], '/' );
		}

		$sample_settings = $this->get_example_settings( $slug );

		$permastruct = $this->option->get_structure( $post_type );

		$disabled = $this->option->is_defined_structure( $post_type );
		?>
		<fieldset class="sptp-fieldset <?php echo ( $with_front ) ? 'with-front' : ''; ?>">
			<?php
			$checked = false;
			foreach ( $sample_settings as $sample_setting ) :
				if ( ! $checked ) {
					$checked = ( $permastruct == $sample_setting );
				}

				$dummy_tags = array(
					'%postname%' => 'sample-post',
					'%post_id%'  => '123',
					'%year%'     => date_i18n( 'Y' ),
					'%monthnum%' => date_i18n( 'm' ),
					'%day%'      => date_i18n( 'd' ),
					'%hour%'     => date_i18n( 'H' ),
					'%minute%'   => date_i18n( 'i' ),
					'%second%'   => date_i18n( 's' ),
					'%author%'   => 'jhon-doe',
				);

				$permalink = str_replace( array_keys( $dummy_tags ), array_values( $dummy_tags ), $sample_setting );
				?>
				<label>
					<input type="radio" name="<?php echo esc_attr( $args ); ?>_select"
						   value="<?php echo esc_attr( $sample_setting ) ?>" <?php
							if ( ! $disabled ) {
								checked( $permastruct, $sample_setting );
							}
							disabled( $disabled );?> />
					<?php
					if ( $sample_setting ) :?>
						<code><?php echo esc_url( home_url() );
							$front = substr( $wp_rewrite->front, 0, - 1 );
							if ( $with_front && $front ) :
								?><span class="front"><?php echo esc_html( $front ); ?></span><?php
							endif;
							echo esc_html( untrailingslashit( $permalink ) );
						?><span class="slash"><?php echo esc_attr( $slash ); ?></span></code>
						<?php
					else : ?>
						<?php esc_html_e( 'Default.', 'simple-post-type-permalinks' ); ?>
						<?php
					endif; ?>

				</label>
				<br/>
				<?php
			endforeach;
			?>
			<label>
				<input type="radio" name="<?php echo esc_attr( $args ); ?>_select" value="custom"
					<?php checked( $checked, false ); ?>
					<?php disabled( $disabled ); ?> />
				<code><?php echo esc_url( home_url() );
					$front = substr( $wp_rewrite->front, 0, - 1 );
					if ( $with_front && $front ) :
						?><span class="front"><?php echo esc_html( untrailingslashit( $front ) ); ?></span><?php
						?><span class="slash"><?php echo esc_attr( $slash ); ?></span><?php
					endif;
				?></code>

				<input class="regular-text code"
					   name="<?php echo esc_attr( "sptp_{$post_type}_structure" ); ?>"
					   id="<?php echo esc_attr( "sptp_{$post_type}_structure" ); ?>"
					   type="text" value="/<?php echo esc_attr( $permastruct ) ?>"
					<?php disabled( $disabled ); ?>
				/><span class="slash"><?php echo esc_html( $slash ); ?></span>
			</label>

		</fieldset>
		<?php
	}

	public function admin_enqueue_scripts( $hook ) {
		if ( 'options-permalink.php' === $hook ) {
			wp_enqueue_script(
				'admin-sptp-script',
				plugins_url( 'js/admin-simple-post-type-permalinks.js', SPTP_FILE ),
				array( 'jquery' ),
				SPTP_VER,
				true
			);
		}
	}
}
