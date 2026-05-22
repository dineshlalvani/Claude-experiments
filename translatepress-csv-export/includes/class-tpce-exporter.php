<?php
/**
 * Streams TranslatePress strings into a CSV file.
 *
 * The exporter walks the dictionary table for each requested
 * language pair in batches, resolves each row to one or more pages
 * via wp_trp_original_meta, and writes one CSV row per
 * (string, page) pair. Strings that are not associated with any
 * page are written once with an empty page column.
 *
 * @package TPCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TPCE_Exporter {

	const BATCH_SIZE = 500;

	/** @var array */
	private $args;

	/** @var resource */
	private $stream;

	/** @var array Cache of post_id => array( title, url, type ) */
	private $post_cache = array();

	/**
	 * @param array $args {
	 *     @type string[] $languages     Target language slugs to include.
	 *                                   Defaults to every non-default TP language.
	 *     @type int[]    $statuses      Status flags to include. Defaults to all.
	 *     @type int[]    $block_types   Block types to include. Defaults to all.
	 *     @type bool     $include_empty Whether to include rows whose translation is empty. Default true.
	 *     @type bool     $include_gettext Include gettext (theme/plugin) strings. Default true.
	 * }
	 */
	public function __construct( array $args = array() ) {
		$this->args = wp_parse_args( $args, array(
			'languages'       => array(),
			'statuses'        => array(),
			'block_types'     => array(),
			'include_empty'   => true,
			'include_gettext' => true,
		) );
	}

	/**
	 * Emit the CSV to the given stream (defaults to php://output).
	 * The caller is responsible for sending HTTP headers.
	 *
	 * @param resource|null $stream
	 * @return int Number of data rows written.
	 */
	public function stream( $stream = null ) {
		$this->stream = $stream ?: fopen( 'php://output', 'w' );

		// UTF-8 BOM so Excel detects encoding correctly.
		fwrite( $this->stream, "\xEF\xBB\xBF" );

		fputcsv( $this->stream, array(
			'Page',
			'Page URL',
			'Post type',
			'Section',
			'Language',
			'Original',
			'Translation',
			'Status',
			'Source',
		) );

		$rows_written = 0;
		$default      = TPCE_TP_Data::default_language();
		$languages    = $this->resolve_languages();

		foreach ( $languages as $language ) {
			$rows_written += $this->stream_dictionary( $default, $language );
			if ( $this->args['include_gettext'] ) {
				$rows_written += $this->stream_gettext( $language );
			}
		}

		return $rows_written;
	}

	/**
	 * @return string[]
	 */
	private function resolve_languages() {
		$all     = TPCE_TP_Data::languages();
		$default = TPCE_TP_Data::default_language();
		$all     = array_values( array_diff( $all, array( $default ) ) );

		if ( empty( $this->args['languages'] ) ) {
			return $all;
		}
		return array_values( array_intersect( $all, $this->args['languages'] ) );
	}

	/**
	 * Stream the dictionary table for a given language pair.
	 *
	 * @param string $from
	 * @param string $to
	 * @return int Number of rows written.
	 */
	private function stream_dictionary( $from, $to ) {
		global $wpdb;

		$table = TPCE_TP_Data::dictionary_table( $from, $to );
		if ( ! TPCE_TP_Data::table_exists( $table ) ) {
			return 0;
		}

		$where  = $this->build_dictionary_where();
		$offset = 0;
		$rows   = 0;
		$lang   = TPCE_TP_Data::language_name( $to );

		do {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- $table and $where are built from trusted constants/whitelists.
			$sql = $wpdb->prepare(
				"SELECT id, original, translated, status, block_type, original_id
				 FROM `{$table}`
				 {$where}
				 ORDER BY id ASC
				 LIMIT %d OFFSET %d",
				self::BATCH_SIZE,
				$offset
			);
			$batch = $wpdb->get_results( $sql );
			if ( empty( $batch ) ) {
				break;
			}

			$original_ids = array_filter( array_map( static function ( $r ) {
				return (int) $r->original_id;
			}, $batch ) );

			$post_map = $this->build_post_map( $original_ids );

			foreach ( $batch as $row ) {
				$pages = isset( $post_map[ (int) $row->original_id ] )
					? $post_map[ (int) $row->original_id ]
					: array( null );

				foreach ( $pages as $post_id ) {
					$rows++;
					$this->write_row( $row, $post_id, $lang, 'Dictionary' );
				}
			}

			$offset += self::BATCH_SIZE;
		} while ( count( $batch ) === self::BATCH_SIZE );

		return $rows;
	}

	/**
	 * Stream the gettext table (theme/plugin strings) for a language.
	 *
	 * @param string $language
	 * @return int Number of rows written.
	 */
	private function stream_gettext( $language ) {
		global $wpdb;

		$table = TPCE_TP_Data::gettext_table( $language );
		if ( ! TPCE_TP_Data::table_exists( $table ) ) {
			return 0;
		}

		$where_parts = array();
		if ( ! $this->args['include_empty'] ) {
			$where_parts[] = "translated <> ''";
		}
		$where = $where_parts ? 'WHERE ' . implode( ' AND ', $where_parts ) : '';

		$offset = 0;
		$rows   = 0;
		$lang   = TPCE_TP_Data::language_name( $language );

		do {
			// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$sql = $wpdb->prepare(
				"SELECT id, original, translated, status, domain
				 FROM `{$table}`
				 {$where}
				 ORDER BY id ASC
				 LIMIT %d OFFSET %d",
				self::BATCH_SIZE,
				$offset
			);
			$batch = $wpdb->get_results( $sql );
			if ( empty( $batch ) ) {
				break;
			}

			foreach ( $batch as $row ) {
				$rows++;
				fputcsv( $this->stream, array(
					'(theme/plugin strings)',
					'',
					'',
					'Gettext: ' . ( $row->domain !== '' ? $row->domain : 'default' ),
					$lang,
					(string) $row->original,
					(string) $row->translated,
					TPCE_TP_Data::status_label( $row->status ),
					'Gettext',
				) );
			}

			$offset += self::BATCH_SIZE;
		} while ( count( $batch ) === self::BATCH_SIZE );

		return $rows;
	}

	/**
	 * @return string SQL WHERE clause (including the WHERE keyword), or ''.
	 */
	private function build_dictionary_where() {
		global $wpdb;
		$clauses = array();

		if ( ! empty( $this->args['statuses'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $this->args['statuses'] ), '%d' ) );
			$clauses[]    = $wpdb->prepare( "status IN ($placeholders)", $this->args['statuses'] );
		}

		if ( ! empty( $this->args['block_types'] ) ) {
			$placeholders = implode( ',', array_fill( 0, count( $this->args['block_types'] ), '%d' ) );
			$clauses[]    = $wpdb->prepare( "block_type IN ($placeholders)", $this->args['block_types'] );
		}

		if ( ! $this->args['include_empty'] ) {
			$clauses[] = "translated <> ''";
		}

		if ( empty( $clauses ) ) {
			return '';
		}
		return 'WHERE ' . implode( ' AND ', $clauses );
	}

	/**
	 * Look up the posts that each original_id is associated with.
	 *
	 * @param int[] $original_ids
	 * @return array<int, int[]> Map of original_id => array of post IDs.
	 */
	private function build_post_map( array $original_ids ) {
		global $wpdb;

		if ( empty( $original_ids ) ) {
			return array();
		}

		$meta_table = TPCE_TP_Data::original_meta_table();
		if ( ! TPCE_TP_Data::table_exists( $meta_table ) ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $original_ids ), '%d' ) );
		// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$sql = $wpdb->prepare(
			"SELECT original_id, meta_value FROM `{$meta_table}`
			 WHERE meta_key = 'post_parent_id' AND original_id IN ($placeholders)",
			$original_ids
		);
		$rows = $wpdb->get_results( $sql );

		$map = array();
		foreach ( $rows as $row ) {
			$oid = (int) $row->original_id;
			$pid = (int) $row->meta_value;
			if ( $pid <= 0 ) {
				continue;
			}
			if ( ! isset( $map[ $oid ] ) ) {
				$map[ $oid ] = array();
			}
			$map[ $oid ][] = $pid;
		}
		return $map;
	}

	/**
	 * Write a single CSV row for a dictionary entry.
	 *
	 * @param object   $row     Dictionary row.
	 * @param int|null $post_id Associated post or null when unknown.
	 * @param string   $lang    Display name of the target language.
	 * @param string   $source  Source table label.
	 */
	private function write_row( $row, $post_id, $lang, $source ) {
		$page  = $this->resolve_post( $post_id );
		$title = $page ? $page['title'] : '(no page)';
		$url   = $page ? $page['url'] : '';
		$type  = $page ? $page['type'] : '';

		fputcsv( $this->stream, array(
			$title,
			$url,
			$type,
			TPCE_TP_Data::block_type_label( $row->block_type ),
			$lang,
			(string) $row->original,
			(string) $row->translated,
			TPCE_TP_Data::status_label( $row->status ),
			$source,
		) );
	}

	/**
	 * Resolve a post ID into the columns the CSV needs, with caching.
	 *
	 * @param int|null $post_id
	 * @return array{title:string,url:string,type:string}|null
	 */
	private function resolve_post( $post_id ) {
		if ( ! $post_id ) {
			return null;
		}
		if ( isset( $this->post_cache[ $post_id ] ) ) {
			return $this->post_cache[ $post_id ];
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			return $this->post_cache[ $post_id ] = array(
				'title' => sprintf( '(deleted #%d)', $post_id ),
				'url'   => '',
				'type'  => '',
			);
		}

		return $this->post_cache[ $post_id ] = array(
			'title' => $post->post_title !== '' ? $post->post_title : sprintf( '(untitled #%d)', $post_id ),
			'url'   => (string) get_permalink( $post ),
			'type'  => $post->post_type,
		);
	}
}
