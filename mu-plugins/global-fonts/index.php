<?php
/**
 * Plugin Name: WordPress.org Fonts
 * Description: Registers a stylesheet to load Inter and EB Garamond for use in other themes/plugins.
 */

namespace WordPressdotorg\MU_Plugins\Global_Fonts;

// Include helper functions that exist in global-scope.
include __DIR__ . '/helper-functions.php';

add_filter( 'init', __NAMESPACE__ . '\register_style', 1 );
add_filter( 'block_editor_settings_all', __NAMESPACE__ . '\relative_to_absolute_urls' );
add_filter( 'wp_preload_resources', __NAMESPACE__ . '\maybe_preload_font' );

/**
 * Register stylesheet with font-family declarations.
 */
function register_style() {
	$version = filemtime( __DIR__ . '/style.css' );
	wp_register_style( 'wporg-global-fonts', get_font_stylesheet_url(), array(), $version );
}

/**
 * Get a stylesheet for the fonts for enqueuing manually as needed, for ex, in `add_editor_style`.
 */
function get_font_stylesheet_url() {
	return plugins_url( 'style.css', __FILE__ );
}

/**
 * Helper function to return valid subsets.
 *
 * Not all fonts have all subsets, but this list helps safelist the values.
 */
function get_valid_subsets() {
	return array(
		'arabic',
		'arrows',
		'cjk',
		'cyrillic-ext',
		'cyrillic',
		'greek-ext',
		'greek',
		'latin-ext',
		'latin',
		'vietnamese',
	);
}

/**
 * Filter the styles added by editor settings to inject the full URL to the font files.
 *
 * Once inlined in wp-admin, the relative URLs don't match the correct file paths.
 *
 * @param array $editor_settings Default editor settings.
 */
function relative_to_absolute_urls( $editor_settings ) {
	if ( ! isset( $editor_settings['styles'] ) ) {
		return $editor_settings;
	}

	foreach ( $editor_settings['styles'] as $i => $style ) {
		if ( ! isset( $style['css'] ) ) {
			continue;
		}
		if ( str_contains( $style['css'], './Inter' ) || str_contains( $style['css'], './EB-Garamond' ) ) {
			$url = plugins_url( '', __FILE__ );
			$style['css'] = str_replace( 'url(./', "url($url/", $style['css'] );
			$editor_settings['styles'][ $i ] = $style;
		}
	}

	return $editor_settings;
}

/**
 * Specify a font to be preloaded.
 *
 * This adds the font name (optionally with style and weight) and subset to the
 * preload list. No validation is done at this point, so this won't tell you if
 * the font or subset is invalid. That check is done in `maybe_preload_font` by
 * the `get_font_url` call.
 *
 * @param string $fonts   The font(s) to preload, comma-separated.
 * @param string $subsets The subset(s) to preload, comma-separated.
 *
 * @return bool If the font has been added to the preload list.
 */
function preload_font( $fonts, $subsets ) {
	$style = wp_styles()->query( 'wporg-global-fonts' );
	if ( ! $style || empty( $fonts ) || empty( $subsets ) ) {
		return false;
	}

	$fonts = explode( ',', $fonts );
	$subsets = explode( ',', $subsets );

	$valid_subsets = get_valid_subsets();
	$subsets = array_intersect( $subsets, $valid_subsets );
	if ( empty( $subsets ) ) {
		// No valid subsets, don't preload anything.
		return;
	}

	// something-ext is found, check that the corresponding base subset exists.
	// The -ext subset only has the extra characters, so the base font still needs to be loaded.
	if ( false !== strpos( implode( ', ', $subsets ), '-ext' ) ) {
		if ( in_array( 'latin-ext', $subsets ) && ! in_array( 'latin', $subsets ) ) {
			$subsets[] = 'latin';
		}
		if ( in_array( 'greek-ext', $subsets ) && ! in_array( 'greek', $subsets ) ) {
			$subsets[] = 'greek';
		}
		if ( in_array( 'cyrillic-ext', $subsets ) && ! in_array( 'cyrillic', $subsets ) ) {
			$subsets[] = 'cyrillic';
		}
	}

	$preload = $style->extra['preload'] ?? [];

	foreach ( $fonts as $font ) {
		$new_preload = [
			$font => $subsets,
		];
		$preload = array_merge_recursive( $preload, $new_preload );
	}

	wp_style_add_data( 'wporg-global-fonts', 'preload', $preload );

	return true;
}

/**
 * Add any fonts specified for preloading to the WordPress preload stack.
 */
function maybe_preload_font( $preload ) {
	$style = wp_styles()->query( 'wporg-global-fonts' );

	if ( ! $style || empty( $style->extra['preload'] ) ) {
		return $preload;
	}

	foreach ( (array) $style->extra['preload'] as $font => $subsets ) {
		if ( empty( $font ) || empty( $subsets ) ) {
			continue;
		}

		$subsets = array_unique( $subsets );
		foreach ( $subsets as $subset ) {
			if ( empty( $subset ) ) {
				continue;
			}

			$font_url = get_font_url( $font, $subset );
			if ( ! $font_url ) {
				continue;
			}

			$preload[] = [
				'href'        => $font_url,
				'as'          => 'font',
				'crossorigin' => 'crossorigin',
				'type'        => 'font/woff2',
			];
		}
	}

	return $preload;
}

/**
 * Return the details about a specific font face.
 */
function get_font_url( $font, $subset ) {
	$lower_font   = strtolower( trim( $font ) );
	$lower_subset = strtolower( trim( $subset ) );

	switch ( $lower_font ) {
		case 'newsreader':
			$font_folder    = 'Newsreader/';
			$font_file_name = 'Newsreader-';
			break;
		case 'newsreader italic':
			$font_folder    = 'Newsreader/';
			$font_file_name = 'Newsreader-Italic-';
			break;
		case 'anton':
			$font_folder    = 'Anton/';
			$font_file_name = 'Anton-Regular-';
			break;
		case 'courier prime':
			$font_folder    = 'CourierPrime/';
			$font_file_name = 'CourierPrime-Regular-';
			break;
		case 'courier prime bold':
			$font_folder    = 'CourierPrime/';
			$font_file_name = 'CourierPrime-Bold-';
			break;
		case 'inter':
			$font_folder    = 'Inter/';
			$font_file_name = 'Inter-';
			break;
		case 'eb garamond':
			$font_folder    = 'EB-Garamond/';
			$font_file_name = 'EBGaramond-';
			break;
		case 'eb garamond italic':
			$font_folder    = 'EB-Garamond/';
			$font_file_name = 'EBGaramond-Italic-';
			break;
		case 'ibm plex mono extralight':
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-ExtraLight-';
			break;
		case 'ibm plex mono extralight italic':
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-ExtraLightItalic-';
			break;
		case 'ibm plex mono':
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Regular-';
			break;
		case 'ibm plex mono italic':
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Italic-';
			break;
		case 'ibm plex mono medium':
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Medium-';
			break;
		case 'ibm plex mono bold':
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-Bold-';
			break;
		case 'ibm plex mono bold italic':
			$font_folder    = 'IBMPlexMono/';
			$font_file_name = 'IBMPlexMono-BoldItalic-';
			break;
		case 'ibm plex sans':
			$font_folder    = 'IBMPlexSans/';
			$font_file_name = 'IBMPlexSans-Regular-';
			break;
		case 'ibm plex sans semibold':
			$font_folder    = 'IBMPlexSans/';
			$font_file_name = 'IBMPlexSans-SemiBold-';
			break;
		case 'noto serif jp':
			$font_folder    = 'NotoSerif/';
			$font_file_name = 'NotoSerifJP-';
			break;
		case 'noto kufi':
			$font_folder    = 'Noto/';
			$font_file_name = 'NotoKufi-';
			break;
	}

	$filepath = $font_folder . $font_file_name . $lower_subset . '.woff2';
	if ( ! file_exists( __DIR__ . '/' . $filepath ) ) {
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			trigger_error( sprintf( 'Requested font file %s does not exist.', esc_html( $filepath ) ), E_USER_WARNING );
		}
		return false;
	}

	return plugins_url( $filepath, __FILE__ );
}
