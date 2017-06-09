<?php
/*
Plugin Name: Simple 404 Logger
Plugin URI: https://geek.hellyer.kiwi/plugins/simple-404-logger/
Description: Simple 404 Logger. Logs 404 error URLs and nothing else. Designed to be as basic and light on resources as possible.
Version: 1.0
Author: Ryan Hellyer
Author URI: https://geek.hellyer.kiwi/
License: GPL2

------------------------------------------------------------------------
Copyright Ryan Hellyer

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA

*/


/**
 * Do not continue processing since file was called directly
 */
if ( ! defined( 'ABSPATH' ) ) {
	die( 'Eh! What you doin in here?' );
}

/**
 * Logs 404 errors.
 *
 * @copyright Copyright (c), Ryan Hellyer
 * @license http://www.gnu.org/licenses/gpl.html GPL
 * @author Ryan Hellyer <ryanhellyer@gmail.com>
 * @package Hellish Simplicity
 * @since Hellish Simplicity 1.5v
 */
class Simple_404_Logger {

	const OPTION = 'simple-404-log';

	/**
	 * Constructor.
	 */
	public function __construct() {
		register_activation_hook( __FILE__, array( $this, 'plugin_activate' ) );
		add_action( '404_template', array($this, 'log' ), 10, 1 );
		add_action( 'admin_menu', array( $this, 'create_admin_page' ) );
	}

	/**
	 * Plugin activation.
	 */
	public function plugin_activate() {

		add_option(
			self::OPTION, // Key
			null,         // Value
			null,         // deprecated arg
			false         // set to not-autoload
		);

	}

	/**
	 * Create the admin page and add it to the menu.
	 */
	public function create_admin_page() {

		add_management_page(
			__ ( '404 Log', 'plugin-slug' ), // Page title
			__ ( '404 Log', 'plugin-slug' ), // Menu title
			'manage_options',                // Capability required
			'simple-404-logger',             // The URL slug
			array( $this, 'admin_page' )     // Displays the admin page
		);
	}

	/**
	 * Output the admin page.
	 */
	public function admin_page() {

		?>
		<div class="wrap">
			<h1><?php esc_html_e( '404 Log', 'simple-404-logger' ); ?></h1>
			<p><?php esc_html_e( 'For performance reasons, only the latest 404 error is logged for each URL.', 'simple-404-logger' ); ?></p>

			<table class="wp-list-table widefat fixed striped posts">
				<tr>
					<th><?php esc_html_e( 'URL', 'simple-404-logger' ); ?></th>
					<th><?php esc_html_e( 'IP Address', 'simple-404-logger' ); ?></th>
					<th><?php esc_html_e( 'Most recent access time', 'simple-404-logger' ); ?></th>
				</tr><?php

				$log = get_option( self::OPTION );
				foreach ( $log as $url => $data ) {

					$date = '';
					if ( isset( $data[1] ) ) {
						$date = date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $data[1] );
					}

					$ip_address = '';
					if ( isset( $data[0] ) ) {
						$ip_address = $data[0];
					}

					echo '<tr>';
					echo '<td>' . esc_url( $url ) . '</td>';

					echo '<td>' . esc_html( $ip_address ) . '</td>';
					echo '<td>' . esc_html( $date ) . '</td>';

					echo '</tr>';
				}

				?>
			<table>

		</div><?php
	}

	/**
	 * Log the request.
	 */
	public function log() {

		$log = get_option( self::OPTION );
		//$log = array();

		// Sanitize the URL
		$url = esc_url( $this->full_url( $_SERVER ) );

		// Bail out if IP is invalid
		$ip_address = $_SERVER['REMOTE_ADDR'];
		if ( ! filter_var( $ip_address, FILTER_VALIDATE_IP ) ) {
			return;
		}

		// Sanitize the request time
		$request_time = absint( $_SERVER['REQUEST_TIME'] );

		// Add data to array
		$log[$url] = array(
			$ip_address,
			$request_time,
		);

		// Store the data
		update_option( self::OPTION, $log );

	}

	/**
	 * Adapted from code found at https://stackoverflow.com/questions/6768793/get-the-full-url-in-php
	 *
	 * @todo  Add proper inline docs
	 */
	public function url_origin( $s, $use_forwarded_host = false ) {
		$ssl      = ( ! empty( $s['HTTPS'] ) && $s['HTTPS'] == 'on' );
		$sp       = strtolower( $s['SERVER_PROTOCOL'] );
		$protocol = substr( $sp, 0, strpos( $sp, '/' ) ) . ( ( $ssl ) ? 's' : '' );
		$port     = $s['SERVER_PORT'];
		$port     = ( ( ! $ssl && $port=='80' ) || ( $ssl && $port=='443' ) ) ? '' : ':'.$port;
		$host     = ( $use_forwarded_host && isset( $s['HTTP_X_FORWARDED_HOST'] ) ) ? $s['HTTP_X_FORWARDED_HOST'] : ( isset( $s['HTTP_HOST'] ) ? $s['HTTP_HOST'] : null );
		$host     = isset( $host ) ? $host : $s['SERVER_NAME'] . $port;
		return $protocol . '://' . $host;
	}

	/**
	 * Adapted from code found at https://stackoverflow.com/questions/6768793/get-the-full-url-in-php
	 *
	 * @todo  Add proper inline docs
	 */
	public function full_url( $s, $use_forwarded_host = false ) {
		return $this->url_origin( $s, $use_forwarded_host ) . $s['REQUEST_URI'];
	}

}
new Simple_404_Logger;
