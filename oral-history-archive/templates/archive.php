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

$heading = 'Finding aid';
if ( is_tax( OHA_Interview::COLLECTION ) ) {
	$heading = single_term_title( '', false );
}

require OHA_DIR . 'templates/header.php';
?>

<section class="oha-hero">
	<p class="oha-kicker">Special collections</p>
	<h1><?php echo esc_html( $heading ); ?></h1>
	<p class="oha-lede"><?php echo esc_html( $intro ); ?></p>
</section>

<form class="oha-filters" method="get" action="<?php echo esc_url( home_url( '/' ) ); ?>">
	<label class="oha-search">
		<span>Search narrators and abstracts</span>
		<input type="search" name="q" value="<?php echo esc_attr( $filters['q'] ); ?>" placeholder="Produce market, ferry, mill…" />
	</label>
	<label>
		<span>Collection</span>
		<select name="collection">
			<option value="">All collections</option>
			<?php foreach ( $collections as $term ) : ?>
				<option value="<?php echo esc_attr( $term->slug ); ?>" <?php selected( $filters['collection'], $term->slug ); ?>>
					<?php echo esc_html( $term->name ); ?>
				</option>
			<?php endforeach; ?>
		</select>
	</label>
	<label>
		<span>Rights</span>
		<select name="rights">
			<option value="">Any status</option>
			<option value="open" <?php selected( $filters['rights'], 'open' ); ?>>Open for listening</option>
			<option value="restricted" <?php selected( $filters['rights'], 'restricted' ); ?>>Restricted</option>
			<option value="embargoed" <?php selected( $filters['rights'], 'embargoed' ); ?>>Embargoed</option>
		</select>
	</label>
	<button type="submit">Filter catalog</button>
</form>

<?php if ( ! $aid->have_posts() ) : ?>
	<div class="oha-empty">
		<h2>No interviews match this search</h2>
		<p>Try clearing the filters, or browse the full finding aid. Restricted tapes are listed even when you cannot play them.</p>
		<p><a class="oha-text-link" href="<?php echo esc_url( home_url( '/' ) ); ?>">Reset catalog</a></p>
	</div>
<?php else : ?>
	<p class="oha-count"><?php echo esc_html( sprintf( _n( '%s interview', '%s interviews', $aid->found_posts, 'oral-history-archive' ), number_format_i18n( $aid->found_posts ) ) ); ?></p>
	<div class="oha-table-wrap">
		<table class="oha-aid">
			<thead>
				<tr>
					<th>Accession</th>
					<th>Interview</th>
					<th>Date</th>
					<th>Rights</th>
					<th>Duration</th>
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
									<span> · interviewed by <?php echo esc_html( $meta['interviewer'] ); ?></span>
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
