<?php
/**
 * Locale Auto Switch
 *
 * @package    Locale Auto Switch
 * @subpackage Locale Auto Switch Main Functions
/*
	Copyright (c) 2018- Katsushi Kawamori (email : dodesyoswift312@gmail.com)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; version 2 of the License.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.

	You should have received a copy of the GNU General Public License
	along with this program; if not, write to the Free Software
	Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
 */

$localeautoswitch = new LocaleAutoSwitch();

/** ==================================================
 * Class Main function
 *
 * @since 1.00
 */
class LocaleAutoSwitch {

	/** ==================================================
	 * Languages
	 *
	 * @var $languages  languages.
	 */
	private $languages;

	/** ==================================================
	 * Locales
	 *
	 * @var $locale_wp_br_s  locale_wp_br_s.
	 */
	private $locale_wp_br_s;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		$this->languages      = get_available_languages();
		$this->languages[]    = 'en_US';
		$this->locale_wp_br_s = json_decode( get_option( 'locale_auto_switch' ), true );

		add_filter( 'locale', array( $this, 'locale' ) );
		add_action( 'init', array( $this, 'php_block_init' ) );
		add_shortcode( 'laslinks', array( $this, 'laslinks_func' ) );
	}

	/** ==================================================
	 * Swicth Locale
	 *
	 * @param  string $locale  locale.
	 * @return string $locale  locale.
	 * @since 1.00
	 */
	public function locale( $locale ) {
		return $this->switch_locale( $locale );
	}

	/** ==================================================
	 * Swicth Locale Main
	 *
	 * @param  string $locale  locale.
	 * @return string $locale  locale.
	 * @since 1.00
	 */
	private function switch_locale( $locale ) {

		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$lang = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );
			foreach ( (array) $this->locale_wp_br_s as $locale_wp_br ) {
				if ( ! empty( $locale_wp_br['locale_br'] ) ) {
					foreach ( $locale_wp_br['locale_br'] as $locale_br ) {
						$locale_brs = explode( '|', $locale_br );
						foreach ( $locale_brs as $locale_b ) {
							if ( $locale_b === $lang[0] ) {
								if ( in_array( $locale_wp_br['locale_wp'], $this->languages, true ) ) {
									$locale = $locale_wp_br['locale_wp'];
									$this->admin_switch_locale( $locale );
									return $locale;
								}
							}
						}
					}
				}
			}
			$locale = 'en_US';
			$this->admin_switch_locale( $locale );
		}

		return $locale;
	}

	/** ==================================================
	 * Swicth Locale Admin
	 *
	 * @param  string $locale  locale.
	 * @since 1.21
	 */
	private function admin_switch_locale( $locale ) {

		if ( is_admin() && ! get_option( 'locale_auto_switch_noadmin' ) ) {
			update_user_meta( get_current_user_id(), 'locale', $locale );
		}
	}

	/** ==================================================
	 * Locale links block & short code
	 *
	 * @since 1.00
	 */
	public function php_block_init() {

		register_block_type(
			plugin_dir_path( __DIR__ ) . 'block/build',
			array(
				'render_callback' => array( $this, 'laslinks_func' ),
				'title' => _x( 'Locale Auto Switch', 'block title', 'locale-auto-switch' ),
				'description' => _x( 'Automatically switch the locale according to the locale of the browser.', 'block description', 'locale-auto-switch' ),
				'keywords' => array(
					_x( 'browser', 'block keyword', 'locale-auto-switch' ),
					_x( 'languages', 'block keyword', 'locale-auto-switch' ),
					_x( 'locale', 'block keyword', 'locale-auto-switch' ),
				),
			)
		);

		$script_handle = generate_block_asset_handle( 'locale-auto-switch/las-links-block', 'editorScript' );
		wp_set_script_translations( $script_handle, 'locale-auto-switch' );
	}

	/** ==================================================
	 * Locale links short code
	 *
	 * @since 1.00
	 */
	public function laslinks_func() {

		$laslinks = '<div>' . esc_html__( 'Please set your browser&#39;s language setting to your native language.', 'locale-auto-switch' ) . '</div><div>' . esc_html__( 'It automatically switches to the following languages.', 'locale-auto-switch' ) . '</div>';
		$localeautoswitch_options = json_decode( get_option( 'locale_auto_switch' ), true );
		$languages                = get_available_languages();
		$languages[]              = 'en_US';
		$locale_languages         = array();
		if ( ! empty( $localeautoswitch_options ) ) {
			foreach ( (array) $localeautoswitch_options as $localeautoswitch_option ) {
				if ( in_array( $localeautoswitch_option['locale_wp'], $languages, true ) ) {
					$locale_languages[] = $localeautoswitch_option['locale_na'];
				}
			}
			$locale_languages_str = implode( ', ', $locale_languages );
			$laslinks .= '<div><i>' . esc_html( $locale_languages_str ) . '</i></div>';
		}

		return $laslinks;
	}
}
