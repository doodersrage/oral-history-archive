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
		This is a reading room, not a podcast feed. Restricted interviews stay in the catalog so the record of the conversation is not erased.
		<?php if ( $contact ) : ?>
			Rights questions: <a href="mailto:<?php echo esc_attr( $contact ); ?>"><?php echo esc_html( $contact ); ?></a>
		<?php endif; ?>
	</p>
</footer>
<?php wp_footer(); ?>
</body>
</html>
