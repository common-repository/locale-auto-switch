<?php
/**
 * Locale Auto Switch
 *
 * @package    LocaleAutoSwitch
 * @subpackage LocaleAutoSwitch Widget
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

add_action(
	'widgets_init',
	function () {
		register_widget( 'LocaleAutoSwitchWidgetItem' );
	}
);

/** ==================================================
 * Widget
 *
 * @since 1.01
 */
class LocaleAutoSwitchWidgetItem extends WP_Widget {

	/** ==================================================
	 * Construct
	 *
	 * @since 1.00
	 */
	public function __construct() {

		parent::__construct(
			'LocaleAutoSwitchWidgetItem', /* Base ID */
			'Locale Auto Switch', /* Name */
			array( 'description' => __( 'Language switching message from Locale Auto Switch.', 'locale-auto-switch' ) ) /* Args */
		);
	}

	/** ==================================================
	 * Widget
	 *
	 * @param array $args args.
	 * @param array $instance instance.
	 * @since 1.00
	 */
	public function widget( $args, $instance ) {

		$title = __( 'Language' );

		echo wp_kses_post( $args['before_widget'] );
		echo wp_kses_post( $args['before_title'] . $title . $args['after_title'] );
		echo do_shortcode( '[laslinks]' );
		echo wp_kses_post( $args['after_widget'] );
	}
}
