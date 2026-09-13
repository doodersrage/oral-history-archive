<?php
/**
 * Admin: interview record, rights, tape log, settings.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class OHA_Admin {

	public static function init() {
		add_action( 'add_meta_boxes', array( __CLASS__, 'meta_boxes' ) );
		add_action( 'save_post_' . OHA_Interview::POST_TYPE, array( __CLASS__, 'save' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_menu', array( __CLASS__, 'settings_page' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_filter( 'manage_' . OHA_Interview::POST_TYPE . '_posts_columns', array( __CLASS__, 'columns' ) );
		add_action( 'manage_' . OHA_Interview::POST_TYPE . '_posts_custom_column', array( __CLASS__, 'column_content' ), 10, 2 );
		add_filter( 'enter_title_here', array( __CLASS__, 'title_placeholder' ), 10, 2 );
	}

	public static function title_placeholder( $text, $post ) {
		if ( $post && OHA_Interview::POST_TYPE === $post->post_type ) {
			return 'Narrator name, short occupation — e.g. Maria Chen, produce wholesaler';
		}
		return $text;
	}

	public static function assets( $hook ) {
		$screen = get_current_screen();
		$on_interview = $screen && OHA_Interview::POST_TYPE === $screen->post_type;
		$on_settings  = ( 'oha_interview_page_oha-settings' === $hook );

		if ( ! $on_interview && ! $on_settings ) {
			return;
		}

		if ( $on_interview && 'post' === $screen->base ) {
			wp_enqueue_media();
		}

		wp_enqueue_style(
			'oha-admin',
			OHA_URL . 'assets/css/admin.css',
			array(),
			OHA_VERSION
		);
		wp_enqueue_script(
			'oha-admin',
			OHA_URL . 'assets/js/admin.js',
			array( 'jquery' ),
			OHA_VERSION,
			true
		);
	}

	public static function meta_boxes() {
		add_meta_box(
			'oha_record',
			'Interview record',
			array( __CLASS__, 'box_record' ),
			OHA_Interview::POST_TYPE,
			'normal',
			'high'
		);
		add_meta_box(
			'oha_rights',
			'Rights and consent',
			array( __CLASS__, 'box_rights' ),
			OHA_Interview::POST_TYPE,
			'side',
			'high'
		);
		add_meta_box(
			'oha_tape',
			'Tape and transcript',
			array( __CLASS__, 'box_tape' ),
			OHA_Interview::POST_TYPE,
			'normal',
			'high'
		);
	}

	public static function box_record( $post ) {
		$meta = OHA_Interview::get_meta( $post->ID );
		wp_nonce_field( 'oha_save_interview', 'oha_nonce' );
		?>
		<p class="oha-help">The object is the interview, not a blog post. Fill the finding-aid fields the way a reading room would cite them.</p>
		<div class="oha-grid">
			<label>
				<span>Accession number</span>
				<input type="text" name="oha_accession" value="<?php echo esc_attr( $meta['accession'] ); ?>" placeholder="OH-2019-014" />
			</label>
			<label>
				<span>Narrator</span>
				<input type="text" name="oha_narrator" value="<?php echo esc_attr( $meta['narrator'] ); ?>" />
			</label>
			<label>
				<span>Interviewer</span>
				<input type="text" name="oha_interviewer" value="<?php echo esc_attr( $meta['interviewer'] ); ?>" />
			</label>
			<label>
				<span>Interview date</span>
				<input type="date" name="oha_interview_date" value="<?php echo esc_attr( $meta['interview_date'] ); ?>" />
			</label>
			<label>
				<span>Place of interview</span>
				<input type="text" name="oha_location" value="<?php echo esc_attr( $meta['location'] ); ?>" />
			</label>
			<label>
				<span>Language</span>
				<input type="text" name="oha_language" value="<?php echo esc_attr( $meta['language'] ); ?>" />
			</label>
		</div>
		<p class="oha-help">Use the main editor above for the abstract shown on the catalog record. Do not put the transcript there.</p>
		<?php
	}

	public static function box_rights( $post ) {
		$meta   = OHA_Interview::get_meta( $post->ID );
		$status = $meta['consent'];
		?>
		<p class="oha-help">Consent decides whether the public gets the tape. The catalog can still list a sealed interview.</p>
		<fieldset class="oha-consent">
			<label><input type="radio" name="oha_consent" value="public" <?php checked( $status, 'public' ); ?> /> Open for listening</label>
			<label><input type="radio" name="oha_consent" value="restricted" <?php checked( $status, 'restricted' ); ?> /> Restricted — catalog only</label>
			<label><input type="radio" name="oha_consent" value="embargoed" <?php checked( $status, 'embargoed' ); ?> /> Embargoed until a date</label>
		</fieldset>
		<p>
			<label>
				<span>Embargo lifts</span>
				<input type="date" name="oha_embargo_until" value="<?php echo esc_attr( $meta['embargo_until'] ); ?>" />
			</label>
		</p>
		<p>
			<label>
				<span>Release signed</span>
				<input type="date" name="oha_release_date" value="<?php echo esc_attr( $meta['release_date'] ); ?>" />
			</label>
		</p>
		<p>
			<label>
				<span>Public restriction statement</span>
				<textarea name="oha_restriction" rows="4" placeholder="Narrator requested that the recording remain sealed during their lifetime."><?php echo esc_textarea( $meta['restriction'] ); ?></textarea>
			</label>
		</p>
		<?php
	}

	public static function box_tape( $post ) {
		$meta     = OHA_Interview::get_meta( $post->ID );
		$audio_id = $meta['audio_id'];
		$url      = $audio_id ? wp_get_attachment_url( $audio_id ) : '';
		$filename = $audio_id ? basename( get_attached_file( $audio_id ) ) : '';
		$cue_count = is_array( $meta['cues'] ) ? count( $meta['cues'] ) : 0;
		?>
		<div class="oha-audio-picker">
			<input type="hidden" name="oha_audio_id" id="oha_audio_id" value="<?php echo esc_attr( (string) $audio_id ); ?>" />
			<p>
				<strong>Recording:</strong>
				<span id="oha-audio-label"><?php echo $filename ? esc_html( $filename ) : 'No file attached'; ?></span>
			</p>
			<p>
				<button type="button" class="button" id="oha-select-audio">Choose audio</button>
				<button type="button" class="button-link" id="oha-clear-audio">Remove</button>
			</p>
			<?php if ( $url ) : ?>
				<audio controls preload="metadata" src="<?php echo esc_url( $url ); ?>"></audio>
			<?php endif; ?>
		</div>
		<p>
			<label>
				<span>Duration in seconds (optional; otherwise taken from the last cue)</span>
				<input type="number" step="0.01" min="0" name="oha_duration" value="<?php echo esc_attr( (string) $meta['duration'] ); ?>" />
			</label>
		</p>
		<p class="oha-help">
			Paste a tape log, one cue per line:
			<code>00:00:12 Narrator: I was born upstairs from the stall.</code>
			Parsed cues: <strong id="oha-cue-count"><?php echo esc_html( (string) $cue_count ); ?></strong>
		</p>
		<textarea name="oha_tape_log" id="oha_tape_log" rows="16" class="large-text code"><?php echo esc_textarea( $meta['tape_log'] ); ?></textarea>
		<pre class="oha-parse-preview" id="oha-parse-preview" hidden></pre>
		<?php
	}

	public static function save( $post_id ) {
		if ( ! isset( $_POST['oha_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['oha_nonce'] ) ), 'oha_save_interview' ) ) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		$fields = array(
			'oha_accession'      => '_oha_accession',
			'oha_narrator'       => '_oha_narrator',
			'oha_interviewer'    => '_oha_interviewer',
			'oha_interview_date' => '_oha_interview_date',
			'oha_location'       => '_oha_location',
			'oha_language'       => '_oha_language',
			'oha_embargo_until'  => '_oha_embargo_until',
			'oha_release_date'   => '_oha_release_date',
		);

		foreach ( $fields as $posted => $meta_key ) {
			$value = isset( $_POST[ $posted ] ) ? sanitize_text_field( wp_unslash( $_POST[ $posted ] ) ) : '';
			update_post_meta( $post_id, $meta_key, $value );
		}

		$consent = isset( $_POST['oha_consent'] ) ? sanitize_key( wp_unslash( $_POST['oha_consent'] ) ) : 'public';
		if ( ! in_array( $consent, array( 'public', 'restricted', 'embargoed' ), true ) ) {
			$consent = 'public';
		}
		update_post_meta( $post_id, '_oha_consent', $consent );

		$restriction = isset( $_POST['oha_restriction'] ) ? sanitize_textarea_field( wp_unslash( $_POST['oha_restriction'] ) ) : '';
		update_post_meta( $post_id, '_oha_restriction', $restriction );

		$audio_id = isset( $_POST['oha_audio_id'] ) ? (int) $_POST['oha_audio_id'] : 0;
		update_post_meta( $post_id, '_oha_audio_id', $audio_id );

		$duration = isset( $_POST['oha_duration'] ) ? (float) $_POST['oha_duration'] : 0;
		update_post_meta( $post_id, '_oha_duration', $duration );

		$tape_log = isset( $_POST['oha_tape_log'] ) ? sanitize_textarea_field( wp_unslash( $_POST['oha_tape_log'] ) ) : '';
		$cues = OHA_Transcript::parse( $tape_log );
		update_post_meta( $post_id, '_oha_tape_log', $tape_log );
		update_post_meta( $post_id, '_oha_cues', $cues );
	}

	public static function columns( $columns ) {
		$new = array();
		foreach ( $columns as $key => $label ) {
			$new[ $key ] = $label;
			if ( 'title' === $key ) {
				$new['oha_accession'] = 'Accession';
				$new['oha_narrator']   = 'Narrator';
				$new['oha_rights']     = 'Rights';
				$new['oha_duration']   = 'Duration';
			}
		}
		return $new;
	}

	public static function column_content( $column, $post_id ) {
		$meta = OHA_Interview::get_meta( $post_id );
		switch ( $column ) {
			case 'oha_accession':
				echo esc_html( $meta['accession'] ?: '—' );
				break;
			case 'oha_narrator':
				echo esc_html( $meta['narrator'] ?: '—' );
				break;
			case 'oha_rights':
				echo esc_html( OHA_Interview::rights_label( $post_id ) );
				break;
			case 'oha_duration':
				$seconds = OHA_Interview::duration_seconds( $post_id );
				echo $seconds ? esc_html( OHA_Transcript::human_duration( $seconds ) ) : '—';
				break;
		}
	}

	public static function settings_page() {
		add_submenu_page(
			'edit.php?post_type=' . OHA_Interview::POST_TYPE,
			'Archive settings',
			'Settings',
			'manage_options',
			'oha-settings',
			array( __CLASS__, 'render_settings' )
		);
	}

	public static function register_settings() {
		register_setting(
			'oha_settings_group',
			OHA_Plugin::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
			)
		);
	}

	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		return array(
			'institution'     => sanitize_text_field( $input['institution'] ?? '' ),
			'rights_contact'  => sanitize_email( $input['rights_contact'] ?? '' ),
			'show_restricted' => empty( $input['show_restricted'] ) ? '0' : '1',
			'intro'           => sanitize_textarea_field( $input['intro'] ?? '' ),
		);
	}

	public static function render_settings() {
		$s = OHA_Plugin::settings();
		?>
		<div class="wrap oha-settings">
			<h1>Archive settings</h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'oha_settings_group' ); ?>
				<table class="form-table">
					<tr>
						<th><label for="oha_institution">Institution name</label></th>
						<td><input type="text" class="regular-text" id="oha_institution" name="<?php echo esc_attr( OHA_Plugin::OPTION ); ?>[institution]" value="<?php echo esc_attr( $s['institution'] ); ?>" /></td>
					</tr>
					<tr>
						<th><label for="oha_rights_contact">Rights contact</label></th>
						<td><input type="email" class="regular-text" id="oha_rights_contact" name="<?php echo esc_attr( OHA_Plugin::OPTION ); ?>[rights_contact]" value="<?php echo esc_attr( $s['rights_contact'] ); ?>" /></td>
					</tr>
					<tr>
						<th>Restricted records</th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( OHA_Plugin::OPTION ); ?>[show_restricted]" value="1" <?php checked( $s['show_restricted'], '1' ); ?> />
								List restricted and embargoed interviews in the public catalog (without the tape).
							</label>
						</td>
					</tr>
					<tr>
						<th><label for="oha_intro">Reading room introduction</label></th>
						<td><textarea id="oha_intro" class="large-text" rows="5" name="<?php echo esc_attr( OHA_Plugin::OPTION ); ?>[intro]"><?php echo esc_textarea( $s['intro'] ); ?></textarea></td>
					</tr>
				</table>
				<?php submit_button( 'Save settings' ); ?>
			</form>
		</div>
		<?php
	}
}
