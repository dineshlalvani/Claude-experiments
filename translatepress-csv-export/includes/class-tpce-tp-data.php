<?php
/**
 * Thin wrapper around TranslatePress's database schema.
 *
 * TranslatePress stores data in several custom tables, one set per
 * language. This class hides the table naming and exposes helpers
 * for the rest of the plugin to consume.
 *
 * @package TPCE
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TPCE_TP_Data {

	/**
	 * Status flags TranslatePress uses on rows in the dictionary table.
	 * Source: TRP_Query class.
	 */
	const STATUS_NOT_TRANSLATED = 0;
	const STATUS_MACHINE        = 1;
	const STATUS_HUMAN_REVIEWED = 2;
	const STATUS_DEPRECATED     = 3;

	/**
	 * Block type values used by TranslatePress on the dictionary table.
	 * Values are taken from TRP constants. Anything unknown is left
	 * as its raw integer with a generic "Other" label.
	 */
	const BLOCK_TYPE_LABELS = array(
		0 => 'Regular text',
		1 => 'Post slug',
		2 => 'Meta information',
		3 => 'Images',
		4 => 'Dynamic / Navigation',
		5 => 'Block HTML',
		6 => 'Link',
	);

	/**
	 * @return array TP settings array (or empty array if TP is not installed).
	 */
	public static function settings() {
		$settings = get_option( 'trp_settings', array() );
		return is_array( $settings ) ? $settings : array();
	}

	/**
	 * @return string Default site language slug (e.g. en_US). Falls back to
	 *                WP locale if TP is not installed.
	 */
	public static function default_language() {
		$settings = self::settings();
		if ( ! empty( $settings['default-language'] ) ) {
			return $settings['default-language'];
		}
		return get_locale();
	}

	/**
	 * @return string[] Slugs of every TP translation language, including
	 *                  the default one. Falls back to an empty array.
	 */
	public static function languages() {
		$settings = self::settings();
		if ( ! empty( $settings['translation-languages'] ) && is_array( $settings['translation-languages'] ) ) {
			return array_values( $settings['translation-languages'] );
		}
		return array();
	}

	/**
	 * Map a language slug to a human readable name using TP's own list
	 * when available, otherwise fall back to the slug.
	 *
	 * @param string $slug
	 * @return string
	 */
	public static function language_name( $slug ) {
		$settings = self::settings();
		if ( ! empty( $settings['publish-languages-names'][ $slug ] ) ) {
			return $settings['publish-languages-names'][ $slug ];
		}
		if ( function_exists( 'locale_get_display_name' ) ) {
			$name = locale_get_display_name( $slug, get_locale() );
			if ( $name ) {
				return $name;
			}
		}
		return $slug;
	}

	/**
	 * Dictionary table holds translations between two languages.
	 *
	 * @param string $from Source language slug.
	 * @param string $to   Target language slug.
	 * @return string Fully prefixed table name.
	 */
	public static function dictionary_table( $from, $to ) {
		global $wpdb;
		return $wpdb->prefix . 'trp_dictionary_' . strtolower( $from ) . '_' . strtolower( $to );
	}

	/**
	 * Gettext table holds theme/plugin translations for a single target
	 * language.
	 *
	 * @param string $language Target language slug.
	 * @return string Fully prefixed table name.
	 */
	public static function gettext_table( $language ) {
		global $wpdb;
		return $wpdb->prefix . 'trp_gettext_' . strtolower( $language );
	}

	/**
	 * @return string Table that stores the canonical original strings
	 *                (referenced from the dictionary by original_id).
	 */
	public static function original_strings_table() {
		global $wpdb;
		return $wpdb->prefix . 'trp_original_strings';
	}

	/**
	 * @return string Table that stores metadata about original strings,
	 *                including the post IDs each string appears on.
	 */
	public static function original_meta_table() {
		global $wpdb;
		return $wpdb->prefix . 'trp_original_meta';
	}

	/**
	 * Test whether a table physically exists. Used to fail gracefully
	 * when TranslatePress is not active or a particular language pair
	 * has not been populated yet.
	 *
	 * @param string $table
	 * @return bool
	 */
	public static function table_exists( $table ) {
		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $table ) );
		return $found === $table;
	}

	/**
	 * @return bool True when TP appears to be installed (settings + at
	 *              least one dictionary table exist).
	 */
	public static function is_available() {
		$languages = self::languages();
		if ( empty( $languages ) ) {
			return false;
		}
		$default = self::default_language();
		foreach ( $languages as $slug ) {
			if ( $slug === $default ) {
				continue;
			}
			if ( self::table_exists( self::dictionary_table( $default, $slug ) ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Human readable label for a numeric block_type.
	 *
	 * @param int|string $block_type
	 * @return string
	 */
	public static function block_type_label( $block_type ) {
		$key = (int) $block_type;
		if ( isset( self::BLOCK_TYPE_LABELS[ $key ] ) ) {
			return self::BLOCK_TYPE_LABELS[ $key ];
		}
		return sprintf( 'Other (%d)', $key );
	}

	/**
	 * Human readable label for a status flag.
	 *
	 * @param int|string $status
	 * @return string
	 */
	public static function status_label( $status ) {
		switch ( (int) $status ) {
			case self::STATUS_NOT_TRANSLATED:
				return 'Not translated';
			case self::STATUS_MACHINE:
				return 'Machine translated';
			case self::STATUS_HUMAN_REVIEWED:
				return 'Human reviewed';
			case self::STATUS_DEPRECATED:
				return 'Deprecated';
			default:
				return (string) $status;
		}
	}
}
