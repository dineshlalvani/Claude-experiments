<?php
/**
 * Admin UI: a page under Tools with a form that posts to admin-post.php
 * and streams the resulting CSV back to the browser.
 *
 * @package TPCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TPCE_Admin {

	const PAGE_SLUG    = 'tp-csv-export';
	const EXPORT_ACTION = 'tpce_export';
	const NONCE_ACTION  = 'tpce_export_nonce';

	public function register() {
		add_action( 'admin_menu', array( $this, 'add_menu' ) );
		add_action( 'admin_post_' . self::EXPORT_ACTION, array( $this, 'handle_export' ) );
	}

	public function add_menu() {
		add_management_page(
			__( 'TranslatePress CSV Export', 'tp-csv-export' ),
			__( 'TP CSV Export', 'tp-csv-export' ),
			TPCE_CAPABILITY,
			self::PAGE_SLUG,
			array( $this, 'render_page' )
		);
	}

	public function render_page() {
		if ( ! current_user_can( TPCE_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'tp-csv-export' ) );
		}

		$available = TPCE_TP_Data::is_available();
		$languages = TPCE_TP_Data::languages();
		$default   = TPCE_TP_Data::default_language();
		$targets   = array_values( array_diff( $languages, array( $default ) ) );
		?>
		<div class="wrap">
			<h1><?php echo esc_html__( 'TranslatePress CSV Export', 'tp-csv-export' ); ?></h1>

			<?php if ( ! $available ) : ?>
				<div class="notice notice-error">
					<p><?php echo esc_html__( 'TranslatePress does not appear to be installed, or no translation tables have been created yet. Activate TranslatePress, add at least one secondary language, then return here.', 'tp-csv-export' ); ?></p>
				</div>
				<?php return; ?>
			<?php endif; ?>

			<p>
				<?php echo esc_html__( 'Export every translatable string TranslatePress has discovered on this site. Each row is one string in one language, grouped by the page where it appears and by section (block type).', 'tp-csv-export' ); ?>
			</p>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<?php wp_nonce_field( self::NONCE_ACTION ); ?>
				<input type="hidden" name="action" value="<?php echo esc_attr( self::EXPORT_ACTION ); ?>" />

				<table class="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row"><label><?php echo esc_html__( 'Languages', 'tp-csv-export' ); ?></label></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php echo esc_html__( 'Languages', 'tp-csv-export' ); ?></legend>
									<?php if ( empty( $targets ) ) : ?>
										<p><em><?php echo esc_html__( 'No secondary languages are configured.', 'tp-csv-export' ); ?></em></p>
									<?php else : ?>
										<?php foreach ( $targets as $slug ) : ?>
											<label style="display:inline-block; margin-right:1.5em;">
												<input type="checkbox" name="languages[]" value="<?php echo esc_attr( $slug ); ?>" checked="checked" />
												<?php echo esc_html( TPCE_TP_Data::language_name( $slug ) ); ?>
												<code><?php echo esc_html( $slug ); ?></code>
											</label>
										<?php endforeach; ?>
									<?php endif; ?>
									<p class="description"><?php echo esc_html__( 'Uncheck a language to exclude it from the export.', 'tp-csv-export' ); ?></p>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label><?php echo esc_html__( 'Sections', 'tp-csv-export' ); ?></label></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php echo esc_html__( 'Sections', 'tp-csv-export' ); ?></legend>
									<?php foreach ( TPCE_TP_Data::BLOCK_TYPE_LABELS as $value => $label ) : ?>
										<label style="display:inline-block; margin-right:1.5em;">
											<input type="checkbox" name="block_types[]" value="<?php echo esc_attr( $value ); ?>" checked="checked" />
											<?php echo esc_html( $label ); ?>
										</label>
									<?php endforeach; ?>
									<p class="description"><?php echo esc_html__( 'Block types map onto sections of a page (body text, slugs, meta, images, etc.). Uncheck a row to exclude it.', 'tp-csv-export' ); ?></p>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label><?php echo esc_html__( 'Statuses', 'tp-csv-export' ); ?></label></th>
							<td>
								<fieldset>
									<legend class="screen-reader-text"><?php echo esc_html__( 'Statuses', 'tp-csv-export' ); ?></legend>
									<?php
									$statuses = array(
										TPCE_TP_Data::STATUS_NOT_TRANSLATED => __( 'Not translated', 'tp-csv-export' ),
										TPCE_TP_Data::STATUS_MACHINE        => __( 'Machine translated', 'tp-csv-export' ),
										TPCE_TP_Data::STATUS_HUMAN_REVIEWED => __( 'Human reviewed', 'tp-csv-export' ),
										TPCE_TP_Data::STATUS_DEPRECATED     => __( 'Deprecated', 'tp-csv-export' ),
									);
									foreach ( $statuses as $value => $label ) :
										$checked = $value === TPCE_TP_Data::STATUS_DEPRECATED ? '' : 'checked="checked"';
									?>
										<label style="display:inline-block; margin-right:1.5em;">
											<input type="checkbox" name="statuses[]" value="<?php echo esc_attr( $value ); ?>" <?php echo $checked; ?> />
											<?php echo esc_html( $label ); ?>
										</label>
									<?php endforeach; ?>
								</fieldset>
							</td>
						</tr>

						<tr>
							<th scope="row"><label><?php echo esc_html__( 'Options', 'tp-csv-export' ); ?></label></th>
							<td>
								<fieldset>
									<label>
										<input type="checkbox" name="include_empty" value="1" checked="checked" />
										<?php echo esc_html__( 'Include strings that have no translation yet', 'tp-csv-export' ); ?>
									</label>
									<br />
									<label>
										<input type="checkbox" name="include_gettext" value="1" checked="checked" />
										<?php echo esc_html__( 'Include gettext (theme & plugin) strings', 'tp-csv-export' ); ?>
									</label>
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>

				<?php submit_button( __( 'Download CSV', 'tp-csv-export' ) ); ?>
			</form>
		</div>
		<?php
	}

	public function handle_export() {
		if ( ! current_user_can( TPCE_CAPABILITY ) ) {
			wp_die( esc_html__( 'You do not have permission to perform this export.', 'tp-csv-export' ), 403 );
		}
		check_admin_referer( self::NONCE_ACTION );

		$args = array(
			'languages'       => $this->sanitize_string_list( $_POST['languages'] ?? array() ),
			'statuses'        => $this->sanitize_int_list( $_POST['statuses'] ?? array() ),
			'block_types'     => $this->sanitize_int_list( $_POST['block_types'] ?? array() ),
			'include_empty'   => ! empty( $_POST['include_empty'] ),
			'include_gettext' => ! empty( $_POST['include_gettext'] ),
		);

		// Disable any output buffering so we can stream a large file.
		while ( ob_get_level() > 0 ) {
			ob_end_clean();
		}
		nocache_headers();

		$filename = sprintf(
			'translatepress-export-%s.csv',
			gmdate( 'Y-m-d-His' )
		);

		header( 'Content-Type: text/csv; charset=UTF-8' );
		header( 'Content-Disposition: attachment; filename="' . $filename . '"' );

		( new TPCE_Exporter( $args ) )->stream();
		exit;
	}

	/**
	 * @param mixed $value
	 * @return string[]
	 */
	private function sanitize_string_list( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		$out = array();
		foreach ( $value as $item ) {
			$item = sanitize_text_field( wp_unslash( $item ) );
			if ( $item !== '' ) {
				$out[] = $item;
			}
		}
		return $out;
	}

	/**
	 * @param mixed $value
	 * @return int[]
	 */
	private function sanitize_int_list( $value ) {
		if ( ! is_array( $value ) ) {
			return array();
		}
		return array_values( array_map( 'intval', $value ) );
	}
}
