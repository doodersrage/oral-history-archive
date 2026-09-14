<?php
/**
 * Finding aid / collection archive.
 *
 * @package OralHistoryArchive
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$intro       = OHA_Plugin::setting( 'intro' );
$filters     = OHA_Frontend::current_filters();
$aid         = new WP_Query( OHA_Frontend::interview_query_args() );
$collections = get_terms(
	array(
		'taxonomy'   => OHA_Interview::COLLECTION,
		'hide_empty' => false,
	)
);
if ( is_wp_error( $collections ) ) {
	$collections = array();
}

$heading = __( 'Finding aid', 'oral-history-archive' );
if ( is_tax( OHA_Interview::COLLECTION ) ) {
	$heading = single_term_title( '', false );
}

require OHA_DIR . 'templates/header.php';
?>

<section class="oha-hero">
	<p class="oha-kicker"><?php esc_html_e( 'Special collections', 'oral-history-archive' ); ?></p>
	<h1><?php echo esc_html( $heading ); ?></h1>
	<p class="oha-lede"><?php echo esc_html( $intro ); ?></p>
</section>

<form class="oha-filters" method="get" action="<?php echo esc_url( get_post_type_archive_link( OHA_Interview::POST_TYPE ) ); ?>">
	<label class="oha-search">
		<span><?php esc_html_e( 'Search narrators and abstracts', 'oral-history-archive' ); ?></span>
		<input type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="<?php esc_attr_e( 'Produce market, ferry, mill…', 'oral-history-archive' ); ?>" />
	</label>
	<label>
		<span><?php esc_html_e( 'Collection', 'oral-history-archive' ); ?></span>
		<select name="collection">
			<option value=""><?php esc_html_e( 'All collections', 'oral-history-archive' ); ?></option>
			<?php foreach ( $collections as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filters['collection'], $term->slug ); ?>>
					<?php echo esc_html( $term->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
	<label>
		<span><?php esc_html_e( 'Rights', 'oral-history-archive' ); ?></span>
		<select name="rights">
			<option value=""><?php esc_html_e( 'Any status', 'oral-history-archive' ); ?></option>
			<option value="open" <?php selected( $filters['rights'], 'open' ); ?>><?php esc_html_e( 'Open for listening', 'oral-history-archive' ); ?></option>
			<option value="restricted" <?php selected( $filters['rights'], 'restricted' ); ?>><?php esc_html_e( 'Restricted', 'oral-history-archive' ); ?></option>
			<option value="embargoed" <?php selected( $filters['rights'], 'embargoed' ); ?>><?php esc_html_e( 'Embargoed', 'oral-history-archive' ); ?></option>
		</select>
	</label>
	<button type="submit"><?php esc_html_e( 'Filter catalog', 'oral-history-archive' ); ?></button>
</form>

<?php if ( ! $aid->have_posts() ) : ?>
	<div class="oha-empty">
		<h2><?php esc_html_e( 'No interviews match this search', 'oral-history-archive' ); ?></h2>
		<p><?php esc_html_e( 'Try clearing the filters, or browse the full finding aid. Restricted tapes are listed even when you cannot play them.', 'oral-history-archive' ); ?></p>
		<p><a class="oha-text-link" href="<?php echo esc_url( get_post_type_archive_link( OHA_Interview::POST_TYPE ) ); ?>"><?php esc_html_e( 'Reset catalog', 'oral-history-archive' ); ?></a></p>
	</div>
<?php else : ?>
	<p class="oha-count">
		<?php
		/* translators: %s: number of interviews */
		echo esc_html( sprintf( _n( '%s interview', '%s interviews', $aid->found_posts, 'oral-history-archive' ), number_format_i18n( $aid->found_posts ) ) );
		?>
	</p>
	<div class="oha-table-wrap">
		<table class="oha-aid">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Accession', 'oral-history-archive' ); ?></th>
					<th><?php esc_html_e( 'Interview', 'oral-history-archive' ); ?></th>
					<th><?php esc_html_e( 'Date', 'oral-history-archive' ); ?></th>
					<th><?php esc_html_e( 'Rights', 'oral-history-archive' ); ?></th>
					<th><?php esc_html_e( 'Duration', 'oral-history-archive' ); ?></th>
				</tr>
			</thead>
			<tbody>
			<?php
			while ( $aid->have_posts() ) :
				$aid->the_post();
				$post_id = get_the_ID();
				$meta    = OHA_Interview::get_meta( $post_id );
				$code    = OHA_Interview::rights_code( $post_id );
				$seconds = OHA_Interview::duration_seconds( $post_id );
				$coll    = get_the_terms( $post_id, OHA_Interview::COLLECTION );
				?>
				<tr class="oha-row-<?php echo esc_attr( $code ); ?>">
					<td class="oha-mono"><a href="<?php the_permalink(); ?>"><?php echo esc_html( $meta['accession'] ?: '—' ); ?></a></td>
					<td>
						<a class="oha-title-link" href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
						<?php if ( $meta['narrator'] ) : ?>
							<div class="oha-sub"><?php echo esc_html( $meta['narrator'] ); ?>
								<?php if ( $meta['interviewer'] ) : ?>
									<span>
										<?php
										/* translators: %s: interviewer name */
										echo esc_html( sprintf( __( ' · interviewed by %s', 'oral-history-archive' ), $meta['interviewer'] ) );
										?>
									</span>
								<?php endif; ?>
							</div>
						<?php endif; ?>
						<?php if ( $coll && ! is_wp_error( $coll ) ) : ?>
							<div class="oha-sub"><?php echo esc_html( implode( ', ', wp_list_pluck( $coll, 'name' ) ) ); ?></div>
						<?php endif; ?>
					</td>
					<td><?php echo esc_html( $meta['interview_date'] ? OHA_Interview::format_date( $meta['interview_date'] ) : '—' ); ?></td>
					<td><span class="oha-stamp oha-stamp-<?php echo esc_attr( $code ); ?>"><?php echo esc_html( OHA_Interview::rights_label( $post_id ) ); ?></span></td>
					<td><?php echo $seconds ? esc_html( OHA_Transcript::human_duration( $seconds ) ) : '—'; ?></td>
				</tr>
			<?php endwhile; ?>
			</tbody>
		</table>
	</div>
	<?php wp_reset_postdata(); ?>
<?php endif; ?>

<?php
require OHA_DIR . 'templates/footer.php';
