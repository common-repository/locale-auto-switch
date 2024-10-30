<?php
/**
 * Locale Auto Switch
 *
 * @package LocaleAutoSwitch
 * @subpackage LocaleAutoSwitch Management screen
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

$localeautoswitchadmin = new LocaleAutoSwitchAdmin();

/** ==================================================
 * Management screen
 */
class LocaleAutoSwitchAdmin {

	/** ==================================================
	 * Csv file name
	 *
	 * @var $csv_file_name  csv_file_name.
	 */
	private $csv_file_name;
	/** ==================================================
	 * Csv url name
	 *
	 * @var $csv_url_name  csv_url_name.
	 */
	private $csv_url_name;
	/** ==================================================
	 * Upload directory
	 *
	 * @var $upload_dir  upload_dir.
	 */
	private $upload_dir;
	/** ==================================================
	 * Languages
	 *
	 * @var $languages  languages.
	 */
	private $languages;

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		$wp_uploads         = wp_upload_dir();
		$relation_path_true = strpos( $wp_uploads['baseurl'], '../' );
		if ( $relation_path_true > 0 ) {
			$basepath         = substr( $wp_uploads['baseurl'], 0, $relation_path_true );
			$upload_url       = $this->realurl( $basepath, $relationalpath );
			$this->upload_dir = wp_normalize_path( realpath( $wp_uploads['basedir'] ) );
		} else {
			$upload_url       = $wp_uploads['baseurl'];
			$this->upload_dir = wp_normalize_path( $wp_uploads['basedir'] );
		}
		if ( is_ssl() ) {
			$upload_url = str_replace( 'http:', 'https:', $upload_url );
		}
		$this->upload_dir    = untrailingslashit( $this->upload_dir );
		$upload_url          = untrailingslashit( $upload_url );
		$this->csv_file_name = $this->upload_dir . '/locale-wp-br-' . wp_date( 'Y-m-d' ) . '.csv';
		$this->csv_url_name  = $upload_url . '/locale-wp-br-' . wp_date( 'Y-m-d' ) . '.csv';
		$this->languages     = get_available_languages();
		$this->languages[]   = 'en_US';

		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_menu', array( $this, 'plugin_menu' ) );
		add_filter( 'plugin_action_links', array( $this, 'settings_link' ), 10, 2 );
	}

	/** ==================================================
	 * Add a "Settings" link to the plugins page
	 *
	 * @param  array  $links  links array.
	 * @param  string $file   file.
	 * @return array  $links  links array.
	 * @since 1.00
	 */
	public function settings_link( $links, $file ) {
		static $this_plugin;
		if ( empty( $this_plugin ) ) {
			$this_plugin = 'locale-auto-switch/localeautoswitch.php';
		}
		if ( $file === $this_plugin ) {
			$links[] = '<a href="' . admin_url( 'options-general.php?page=LocaleAutoSwitch' ) . '">' . __( 'Settings' ) . '</a>';
		}
			return $links;
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_menu() {
		add_options_page( 'Locale Auto Switch Options', 'Locale Auto Switch', 'manage_options', 'LocaleAutoSwitch', array( $this, 'plugin_options' ) );
	}

	/** ==================================================
	 * Settings page
	 *
	 * @since 1.00
	 */
	public function plugin_options() {

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.' ) );
		}

		if ( file_exists( $this->csv_file_name ) ) {
			wp_delete_file( $this->csv_file_name );
		}

		if ( isset( $_POST['SettingsSave'] ) && ! empty( $_POST['SettingsSave'] ) ||
				isset( $_POST['Default'] ) && ! empty( $_POST['Default'] ) ||
				isset( $_POST['Export'] ) && ! empty( $_POST['Export'] ) ||
				isset( $_POST['NoAdminChange'] ) && ! empty( $_POST['NoAdminChange'] ) ) {
			if ( check_admin_referer( 'las_settings', 'las_set' ) ) {
				$this->options_updated();
			}
		}

		$scriptname = admin_url( 'options-general.php?page=LocaleAutoSwitch' );

		$import_html = null;
		if ( isset( $_POST['Import'] ) && ! empty( $_POST['Import'] ) ) {
			if ( isset( $_POST['locale_auto_switch_file_load'] ) && ! empty( $_POST['locale_auto_switch_file_load'] ) ) {
				if ( check_admin_referer( 'las_file_load', 'locale_auto_switch_file_load' ) ) {
					if ( isset( $_FILES['filename']['name'] ) && ! empty( $_FILES['filename']['name'] ) ) {
						if ( isset( $_FILES['filename']['tmp_name'] ) && ! empty( $_FILES['filename']['tmp_name'] ) &&
								isset( $_FILES['filename']['name'] ) && ! empty( $_FILES['filename']['name'] ) &&
								isset( $_FILES['filename']['type'] ) && ! empty( $_FILES['filename']['type'] ) &&
								isset( $_FILES['filename']['error'] ) ) {
							if ( 0 === intval( wp_unslash( $_FILES['filename']['error'] ) ) ) {
								$filename = sanitize_text_field( wp_unslash( $_FILES['filename']['tmp_name'] ) );
								$org_name = sanitize_file_name( wp_unslash( $_FILES['filename']['name'] ) );
								$mimetype = sanitize_text_field( wp_unslash( $_FILES['filename']['type'] ) );
								$filetype = wp_check_filetype( $org_name, array( 'csv' => $mimetype ) );
								if ( ! $filetype['ext'] && ! current_user_can( 'unfiltered_upload' ) ) {
									echo '<div class="notice notice-error is-dismissible"><ul><li>' . esc_html__( 'Sorry, this file type is not permitted for security reasons.' ) . '</li></ul></div>';
								} else {
									update_option( 'locale_auto_switch', wp_json_encode( $this->import( $filename ) ) );
									wp_delete_file( $filename );
									echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Settings' ) . ' --> ' . esc_html__( 'Imported from CSV file.', 'locale-auto-switch' ) . '</li></ul></div>';
								}
							}
						}
					}
				}
			} else {
				$import_html  = '<hr>' . "\n";
				$import_html .= '<form method="post" action="' . $scriptname . '" enctype="multipart/form-data">' . "\n";
				$import_html .= wp_nonce_field( 'las_file_load', 'locale_auto_switch_file_load' ) . "\n";
				$import_html .= '<input name="filename" type="file" accept="text/csv" size="80" />' . "\n";
				$import_html .= get_submit_button( __( 'Import' ), 'large', 'Import', false );
				$import_html .= '</form>' . "\n";
			}
		}

		$current_browser_lang = __( 'Could not retrieve browser\'s language settings.', 'locale-auto-switch' );
		if ( isset( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) {
			$lang = explode( ',', sanitize_text_field( wp_unslash( $_SERVER['HTTP_ACCEPT_LANGUAGE'] ) ) );
			$current_browser_lang = $lang[0];
		}
		?>

		<div class="wrap">
		<h2>Locale Auto Switch</h2>
			<details>
			<summary><strong><?php esc_html_e( 'Various links of this plugin', 'locale-auto-switch' ); ?></strong></summary>
			<?php $this->credit(); ?>
			</details>

			<div class="wrap">
				<h2><?php esc_html_e( 'Settings' ); ?></h2>
				<hr />
				<form method="post" id="localeautoswitch_settings_form" action="<?php echo esc_url( $scriptname ); ?>">
				<?php wp_nonce_field( 'las_settings', 'las_set' ); ?>
				<h3><?php esc_html_e( 'Locale in admin screen', 'locale-auto-switch' ); ?></h3>
				<div style="display: block;padding:5px 5px">
				<input type="checkbox" name="no_admin_change" value="1" <?php checked( '1', get_option( 'locale_auto_switch_noadmin' ) ); ?> /><?php esc_html_e( 'Do not change', 'locale-auto-switch' ); ?>
				</div>
				<?php submit_button( __( 'Save Changes' ), 'large', 'NoAdminChange', true ); ?>
				<hr />
				<h3><?php esc_html_e( 'Locale of browser', 'locale-auto-switch' ); ?></h3>
				<div style="display: block;padding:5px 5px">
				<strong><?php esc_html_e( 'Languages that are not installed will be forced to switch to English.', 'locale-auto-switch' ); ?></strong>
				</div>
				<div style="display: block;padding:5px 5px">
				<?php /* translators: installed language icon */ ?>
				<strong><?php printf( esc_html__( 'The installed language is marked with %1$s.', 'locale-auto-switch' ), '<span class="dashicons dashicons-translation"></span>' ); ?></strong>
				</div>
				<div style="display: block;padding:5px 5px">
				<strong><?php esc_html_e( 'Browser can specify multiple items separated by "|".', 'locale-auto-switch' ); ?></strong>
				</div>
				<div style="display: block;padding:5px 5px">
				<strong><?php esc_html_e( 'Current browser\'s language settings', 'locale-auto-switch' ); ?> : <code><?php echo esc_html( $current_browser_lang ); ?></code></strong>
				</div>
				<div class="submit">
					<?php submit_button( __( 'Save Changes' ), 'large', 'SettingsSave', false ); ?>
					<?php submit_button( __( 'Default' ), 'large', 'Default', false ); ?>
					<?php submit_button( __( 'Export to CSV', 'locale-auto-switch' ), 'large', 'Export', false ); ?>
					<?php
					submit_button( __( 'Import from CSV', 'locale-auto-switch' ), 'large', 'Import', false );
					?>
				</div>
				</form>
				<?php
				$allowed_html = array(
					'form'  => array(
						'method'  => array(),
						'action'  => array(),
						'enctype' => array(),
					),
					'input' => array(
						'type'   => array(),
						'accept' => array(),
						'name'   => array(),
						'id'     => array(),
						'value'  => array(),
						'size'   => array(),
						'class'  => array(),
					),
					'hr' => array(),
				);
				echo wp_kses( $import_html, $allowed_html );

				if ( file_exists( $this->csv_file_name ) ) {
					?>
					<hr>
					<button type="button" class="button button-large" onclick="location.href='<?php echo esc_attr( $this->csv_url_name ); ?>'"><?php esc_html_e( 'Download Export File' ); ?></button>
					<?php
				}
				?>
				<hr>
				<table>
				<?php
				$localeautoswitch_options = json_decode( get_option( 'locale_auto_switch' ), true );
				$count                    = 0;
				foreach ( (array) $localeautoswitch_options as $localeautoswitch_option ) {
					if ( 0 === $count % 2 ) {
						$trstyle = 'background: #fff;';
					} else {
						$trstyle = 'background: #eee;';
					}
					?>
					<tr style="<?php echo esc_attr( $trstyle ); ?>">
					<?php
					if ( 0 === $count ) {
						?>
						<td><strong><?php esc_html_e( 'English' ); ?></strong>
						<input type="hidden" name="localeautoswitch_locale_en[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $localeautoswitch_option['locale_en'] ); ?>" form="localeautoswitch_settings_form">
						</td>
						<td><strong><?php esc_html_e( 'Native', 'locale-auto-switch' ); ?></strong>
						<input type="hidden" name="localeautoswitch_locale_na[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $localeautoswitch_option['locale_na'] ); ?>" form="localeautoswitch_settings_form">
						</td>
						<td><strong>WordPress</strong>
						<input type="hidden" name="localeautoswitch_locale_wp[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $localeautoswitch_option['locale_wp'] ); ?>" form="localeautoswitch_settings_form">
						</td>
						<td align="center"><strong><?php esc_html_e( 'Browser', 'locale-auto-switch' ); ?></strong>
						<?php
						$locale_br = null;
						if ( ! empty( $localeautoswitch_option['locale_br'] ) ) {
							$locale_br = wp_unslash( implode( '|', $localeautoswitch_option['locale_br'] ) );
						}
						?>
						<input type="hidden" name="localeautoswitch_locale_br[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $locale_br ); ?>" form="localeautoswitch_settings_form">
						</td>
						<?php
					} else {
						?>
						<td><?php echo esc_html( $localeautoswitch_option['locale_en'] ); ?>
						<input type="hidden" name="localeautoswitch_locale_en[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $localeautoswitch_option['locale_en'] ); ?>" form="localeautoswitch_settings_form">
						</td>
						<td><?php echo esc_html( $localeautoswitch_option['locale_na'] ); ?>
						<input type="hidden" name="localeautoswitch_locale_na[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $localeautoswitch_option['locale_na'] ); ?>" form="localeautoswitch_settings_form">
						</td>
						<td>
						<?php
						if ( in_array( $localeautoswitch_option['locale_wp'], $this->languages, true ) ) {
							?>
							<span class="dashicons dashicons-translation"></span>
							<?php
						}
						echo esc_html( $localeautoswitch_option['locale_wp'] );
						?>
						<input type="hidden" name="localeautoswitch_locale_wp[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $localeautoswitch_option['locale_wp'] ); ?>" form="localeautoswitch_settings_form">
						</td>
						<td>
						<?php
						$locale_br = null;
						if ( ! empty( $localeautoswitch_option['locale_br'] ) ) {
							$locale_br = wp_unslash( implode( '|', $localeautoswitch_option['locale_br'] ) );
						}
						?>
						<input type="text" name="localeautoswitch_locale_br[<?php echo esc_attr( $count ); ?>]" value="<?php echo esc_attr( $locale_br ); ?>" style="width: 160px;" form="localeautoswitch_settings_form">
						</td>
						<?php
					}
					?>
					</tr>

					<?php
					++$count;
				}
				?>
				</table>
			</div>
		</div>
		<?php
	}

	/** ==================================================
	 * Credit
	 *
	 * @since 1.00
	 */
	private function credit() {

		$plugin_name    = null;
		$plugin_ver_num = null;
		$plugin_path    = plugin_dir_path( __DIR__ );
		$plugin_dir     = untrailingslashit( wp_normalize_path( $plugin_path ) );
		$slugs          = explode( '/', $plugin_dir );
		$slug           = end( $slugs );
		$files          = scandir( $plugin_dir );
		foreach ( $files as $file ) {
			if ( '.' === $file || '..' === $file || is_dir( $plugin_path . $file ) ) {
				continue;
			} else {
				$exts = explode( '.', $file );
				$ext  = strtolower( end( $exts ) );
				if ( 'php' === $ext ) {
					$plugin_datas = get_file_data(
						$plugin_path . $file,
						array(
							'name'    => 'Plugin Name',
							'version' => 'Version',
						)
					);
					if ( array_key_exists( 'name', $plugin_datas ) && ! empty( $plugin_datas['name'] ) && array_key_exists( 'version', $plugin_datas ) && ! empty( $plugin_datas['version'] ) ) {
						$plugin_name    = $plugin_datas['name'];
						$plugin_ver_num = $plugin_datas['version'];
						break;
					}
				}
			}
		}
		$plugin_version = __( 'Version:' ) . ' ' . $plugin_ver_num;
		/* translators: FAQ Link & Slug */
		$faq       = sprintf( __( 'https://wordpress.org/plugins/%s/faq', 'locale-auto-switch' ), $slug );
		$support   = 'https://wordpress.org/support/plugin/' . $slug;
		$review    = 'https://wordpress.org/support/view/plugin-reviews/' . $slug;
		$translate = 'https://translate.wordpress.org/projects/wp-plugins/' . $slug;
		$facebook  = 'https://www.facebook.com/katsushikawamori/';
		$twitter   = 'https://twitter.com/dodesyo312';
		$youtube   = 'https://www.youtube.com/channel/UC5zTLeyROkvZm86OgNRcb_w';
		$donate    = __( 'https://shop.riverforest-wp.info/donate/', 'locale-auto-switch' );

		?>
		<span style="font-weight: bold;">
		<div>
		<?php echo esc_html( $plugin_version ); ?> | 
		<a style="text-decoration: none;" href="<?php echo esc_url( $faq ); ?>" target="_blank" rel="noopener noreferrer">FAQ</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $support ); ?>" target="_blank" rel="noopener noreferrer">Support Forums</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $review ); ?>" target="_blank" rel="noopener noreferrer">Reviews</a>
		</div>
		<div>
		<a style="text-decoration: none;" href="<?php echo esc_url( $translate ); ?>" target="_blank" rel="noopener noreferrer">
		<?php
		/* translators: Plugin translation link */
		echo esc_html( sprintf( __( 'Translations for %s' ), $plugin_name ) );
		?>
		</a> | <a style="text-decoration: none;" href="<?php echo esc_url( $facebook ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-facebook"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $twitter ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-twitter"></span></a> | <a style="text-decoration: none;" href="<?php echo esc_url( $youtube ); ?>" target="_blank" rel="noopener noreferrer"><span class="dashicons dashicons-video-alt3"></span></a>
		</div>
		</span>

		<div style="width: 250px; height: 180px; margin: 5px; padding: 5px; border: #CCC 2px solid;">
		<h3><?php esc_html_e( 'Please make a donation if you like my work or would like to further the development of this plugin.', 'locale-auto-switch' ); ?></h3>
		<div style="text-align: right; margin: 5px; padding: 5px;"><span style="padding: 3px; color: #ffffff; background-color: #008000">Plugin Author</span> <span style="font-weight: bold;">Katsushi Kawamori</span></div>
		<button type="button" style="margin: 5px; padding: 5px;" onclick="window.open('<?php echo esc_url( $donate ); ?>')"><?php esc_html_e( 'Donate to this plugin &#187;' ); ?></button>
		</div>

		<?php
	}

	/** ==================================================
	 * Update wp_options table.
	 *
	 * @since 1.00
	 */
	private function options_updated() {

		if ( check_admin_referer( 'las_settings', 'las_set' ) ) {
			if ( ! empty( $_POST['Default'] ) ) {
				$csv_file = plugin_dir_path( __DIR__ ) . 'data/locale-wp-br.csv';
				update_option( 'locale_auto_switch', wp_json_encode( $this->import( $csv_file ) ) );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Locale of browser', 'locale-auto-switch' ) . ' --> ' . esc_html__( 'Default' ) . ' --> ' . esc_html__( 'Changes saved.' ) . '</li></ul></div>';
			} elseif ( ! empty( $_POST['Export'] ) ) {
				$this->export( $this->csv_file_name, json_decode( get_option( 'locale_auto_switch' ), true ) );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Locale of browser', 'locale-auto-switch' ) . ' --> ' . esc_html__( 'Exported a CSV file. Please download it.', 'locale-auto-switch' ) . '</li></ul></div>';
			} elseif ( ! empty( $_POST['NoAdminChange'] ) ) {
				if ( isset( $_POST['no_admin_change'] ) && ! empty( $_POST['no_admin_change'] ) ) {
					update_option( 'locale_auto_switch_noadmin', 1 );
				} else {
					delete_option( 'locale_auto_switch_noadmin' );
				}
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Locale in admin screen', 'locale-auto-switch' ) . ' --> ' . esc_html__( 'Changes saved.' ) . '</li></ul></div>';
			} elseif ( isset( $_POST['localeautoswitch_locale_br'] ) && ! empty( $_POST['localeautoswitch_locale_br'] ) ) {
				if ( isset( $_POST['localeautoswitch_locale_en'] ) && ! empty( $_POST['localeautoswitch_locale_en'] ) ) {
					$locale_en = array_map( 'sanitize_text_field', wp_unslash( $_POST['localeautoswitch_locale_en'] ) );
				}
				if ( isset( $_POST['localeautoswitch_locale_na'] ) && ! empty( $_POST['localeautoswitch_locale_na'] ) ) {
					$locale_na = array_map( 'sanitize_text_field', wp_unslash( $_POST['localeautoswitch_locale_na'] ) );
				}
				if ( isset( $_POST['localeautoswitch_locale_wp'] ) && ! empty( $_POST['localeautoswitch_locale_wp'] ) ) {
					$locale_wp = array_map( 'sanitize_text_field', wp_unslash( $_POST['localeautoswitch_locale_wp'] ) );
				}
				if ( isset( $_POST['localeautoswitch_locale_br'] ) && ! empty( $_POST['localeautoswitch_locale_br'] ) ) {
					$locale_brs = array_map( 'sanitize_text_field', wp_unslash( $_POST['localeautoswitch_locale_br'] ) );
				}
				$count = 0;
				$maxcount = count( $locale_wp );
				for ( $i = 0; $i <= $maxcount - 1; $i++ ) {
					if ( ! empty( $locale_brs[ $i ] ) ) {
						$locale_br = explode( '|', sanitize_text_field( $locale_brs[ $i ] ) );
					} else {
						$locale_br = null;
					}
					$locale_auto_switch_tbl[ $i ] = array(
						'locale_en' => stripslashes( $locale_en[ $i ] ),
						'locale_na' => stripslashes( $locale_na[ $i ] ),
						'locale_wp' => $locale_wp[ $i ],
						'locale_br' => $locale_br,
					);
				}
				update_option( 'locale_auto_switch', wp_json_encode( $locale_auto_switch_tbl ) );
				echo '<div class="notice notice-success is-dismissible"><ul><li>' . esc_html__( 'Locale of browser', 'locale-auto-switch' ) . ' --> ' . esc_html__( 'Changes saved.' ) . '</li></ul></div>';
			}
		}
	}

	/** ==================================================
	 * Import
	 *
	 * @param  string $csv_file  file.
	 * @return array $locale_auto_switch_tbl  table.
	 * @since 1.18
	 */
	private function import( $csv_file ) {

		$file = new SplFileObject( $csv_file );

		$file->setFlags(
			SplFileObject::READ_CSV |
			SplFileObject::READ_AHEAD |
			SplFileObject::SKIP_EMPTY |
			SplFileObject::DROP_NEW_LINE
		);

		$locale_auto_switch_tbl = array();
		foreach ( $file as $line ) {
			$locale_br                = explode( '|', $line[3] );
			$locale_auto_switch_tbl[] = array(
				'locale_en' => $line[0],
				'locale_na' => $line[1],
				'locale_wp' => $line[2],
				'locale_br' => $locale_br,
			);
		}

		$file = null;

		return $locale_auto_switch_tbl;
	}

	/** ==================================================
	 * Export
	 *
	 * @param  string $export_file  export file.
	 * @param  array  $options_tbl  options table.
	 * @since 1.18
	 */
	private function export( $export_file, $options_tbl ) {

		$locale_auto_switch_tbl = array();
		foreach ( $options_tbl as $line ) {
			if ( ! empty( $line['locale_br'] ) ) {
				$locale_br = stripslashes( implode( '|', $line['locale_br'] ) );
			} else {
				$locale_br = null;
			}
			$locale_auto_switch_tbl[] = array(
				'locale_en' => stripslashes( $line['locale_en'] ),
				'locale_na' => stripslashes( $line['locale_na'] ),
				'locale_wp' => $line['locale_wp'],
				'locale_br' => $locale_br,
			);
		}

		$file = new SplFileObject( $export_file, 'a' );

		foreach ( $locale_auto_switch_tbl as $value ) {
			$file->fputcsv( $value );
		}

		$file = null;
	}

	/** ==================================================
	 * Settings register
	 *
	 * @since 1.00
	 */
	public function register_settings() {

		if ( ! get_option( 'locale_auto_switch' ) ) {
			$csv_file               = plugin_dir_path( __DIR__ ) . 'data/locale-wp-br.csv';
			update_option( 'locale_auto_switch', wp_json_encode( $this->import( $csv_file ) ) );
		}
	}

	/** ==================================================
	 * Real Url
	 *
	 * @param  string $base  base.
	 * @param  string $relationalpath relationalpath.
	 * @return string $realurl realurl.
	 * @since  1.03
	 */
	private function realurl( $base, $relationalpath ) {

		$parse = array(
			'scheme'   => null,
			'user'     => null,
			'pass'     => null,
			'host'     => null,
			'port'     => null,
			'query'    => null,
			'fragment' => null,
		);
		$parse = wp_parse_url( $base );

		if ( strpos( $parse['path'], '/', ( strlen( $parse['path'] ) - 1 ) ) !== false ) {
			$parse['path'] .= '.';
		}

		if ( preg_match( '#^https?://#', $relationalpath ) ) {
			return $relationalpath;
		} elseif ( preg_match( '#^/.*$#', $relationalpath ) ) {
			return $parse['scheme'] . '://' . $parse['host'] . $relationalpath;
		} else {
			$base_path = explode( '/', dirname( $parse['path'] ) );
			$rel_path  = explode( '/', $relationalpath );
			foreach ( $rel_path as $rel_dir_name ) {
				if ( '.' === $rel_dir_name ) {
					array_shift( $base_path );
					array_unshift( $base_path, '' );
				} elseif ( '..' === $rel_dir_name ) {
					array_pop( $base_path );
					if ( count( $base_path ) === 0 ) {
						$base_path = array( '' );
					}
				} else {
					array_push( $base_path, $rel_dir_name );
				}
			}
			$path = implode( '/', $base_path );
			return $parse['scheme'] . '://' . $parse['host'] . $path;
		}
	}
}
