<?php
/**
 * Reading room footer.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$contact = OHA_Plugin::setting( 'rights_contact' );
?>
</main>
<footer class="oha-colophon">
	<p>
		<?php esc_html_e( 'This is a reading room, not a podcast feed. Restricted interviews stay in the catalog so the record of the conversation is not erased.', 'oral-history-archive' ); ?>
		<?php if ( $contact ) : ?>
			<?php esc_html_e( 'Rights questions:', 'oral-history-archive' ); ?> <a href="mailto:<?php echo esc_attr( $contact ); ?>"><?php echo esc_html( $contact ); ?></a>
		<?php endif; ?>
	</p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
