<?php
/**
 * Plugin Name: Kask Preloader
 * Description: Lightweight page preloader with a custom icon and background color.
 * Version:     1.1.0
 * Author:      Kask Creativity
 * License:     GPL-2.0-or-later
 */

if ( ! defined( 'ABSPATH' ) ) exit;

final class Kask_Preloader {

	const OPT  = 'kask_preloader';
	const SLUG = 'kask-preloader';
	private $printed = false;

	public function __construct() {
		add_action( 'admin_menu', [ $this, 'menu' ] );
		add_action( 'admin_init', [ $this, 'register' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'admin_assets' ] );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), [ $this, 'action_link' ] );

		add_action( 'wp_head', [ $this, 'styles' ], 1 );
		add_action( 'wp_body_open', [ $this, 'markup' ], 1 );
		add_action( 'wp_footer', [ $this, 'markup' ], 1 ); // Fallback for themes without wp_body_open.
	}

	private function opts() {
		return wp_parse_args( get_option( self::OPT, [] ), [
			'enabled' => 0,
			'icon_id' => 0,
			'bg'      => '#ffffff',
			'size'    => 96,
		] );
	}

	private function active() {
		if ( ! $this->opts()['enabled'] || is_admin() || is_customize_preview() || wp_doing_ajax() ) return false;
		// Bricks builder + preview iframe.
		if ( function_exists( 'bricks_is_builder' ) && bricks_is_builder() ) return false;
		// Elementor editor preview.
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$el = \Elementor\Plugin::$instance;
			if ( isset( $el->preview ) && $el->preview->is_preview_mode() ) return false;
		}
		// Query-param fallback (Etch builder, plus anything the checks above miss).
		foreach ( [ 'etch', 'bricks', 'elementor-preview' ] as $q ) {
			if ( isset( $_GET[ $q ] ) ) return false;
		}
		return (bool) apply_filters( 'kask_preloader_active', true );
	}

	/* ---------- Front end ---------- */

	public function styles() {
		if ( ! $this->active() ) return;
		$o = $this->opts();
		?>
<style id="kask-preloader-css">
#kask-preloader{position:fixed;inset:0;z-index:999999;display:grid;place-items:center;background:<?php echo esc_attr( $o['bg'] ); ?>;transition:opacity .4s ease,visibility .4s ease;animation:kask-preloader-failsafe .4s ease 5s forwards}
#kask-preloader img{width:<?php echo (int) $o['size']; ?>px;max-width:40vw;height:auto;animation:kask-preloader-pulse 1.6s ease-in-out infinite}
.kask-preloader-done #kask-preloader{opacity:0;visibility:hidden;pointer-events:none}
html:has(e-page-transition) #kask-preloader{display:none}
@keyframes kask-preloader-pulse{50%{opacity:.55;transform:scale(.94)}}
@keyframes kask-preloader-failsafe{to{opacity:0;visibility:hidden}}
@media (prefers-reduced-motion:reduce){#kask-preloader{transition:none;animation-duration:1ms}#kask-preloader img{animation:none}}
</style>
<noscript><style>#kask-preloader{display:none}</style></noscript>
		<?php
	}

	public function markup() {
		if ( $this->printed || ! $this->active() ) return;
		$this->printed = true;

		$o   = $this->opts();
		$src = $o['icon_id'] ? wp_get_attachment_image_url( (int) $o['icon_id'], 'full' ) : '';

		echo '<div id="kask-preloader" aria-hidden="true">';
		if ( $src ) {
			printf( '<img src="%s" alt="" width="%d" decoding="async" fetchpriority="high">', esc_url( $src ), (int) $o['size'] );
		}
		echo '</div>';
		?>
<script data-cfasync="false" data-no-optimize="1" data-no-defer="1" data-no-minify="1" nowprocket>(function(d,w){var h=d.documentElement,done=function(){h.classList.add('kask-preloader-done')};if(d.readyState==='complete'){done()}else{w.addEventListener('load',done)}w.addEventListener('pageshow',function(e){if(e.persisted)done()})})(document,window);</script>
		<?php
	}

	/* ---------- Admin ---------- */

	public function menu() {
		add_options_page( 'Preloader', 'Preloader', 'manage_options', self::SLUG, [ $this, 'page' ] );
	}

	public function action_link( $links ) {
		array_unshift( $links, '<a href="' . esc_url( admin_url( 'options-general.php?page=' . self::SLUG ) ) . '">Settings</a>' );
		return $links;
	}

	public function register() {
		register_setting( self::SLUG, self::OPT, [ 'sanitize_callback' => [ $this, 'sanitize' ] ] );
	}

	public function sanitize( $in ) {
		return [
			'enabled' => empty( $in['enabled'] ) ? 0 : 1,
			'icon_id' => absint( $in['icon_id'] ?? 0 ),
			'bg'      => sanitize_hex_color( $in['bg'] ?? '' ) ?: '#ffffff',
			'size'    => min( 400, max( 16, absint( $in['size'] ?? 96 ) ) ),
		];
	}

	public function admin_assets( $hook ) {
		if ( 'settings_page_' . self::SLUG !== $hook ) return;

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_register_script( 'kask-preloader-admin', false, [ 'jquery', 'wp-color-picker' ], '1.0.0', true );
		wp_enqueue_script( 'kask-preloader-admin' );
		wp_add_inline_script( 'kask-preloader-admin', <<<JS
jQuery(function($){
	$('.kask-color').wpColorPicker();
	var frame;
	$('#kask-icon-select').on('click', function(e){
		e.preventDefault();
		if (frame) { frame.open(); return; }
		frame = wp.media({ title: 'Choose preloader icon', button: { text: 'Use this icon' }, library: { type: 'image' }, multiple: false });
		frame.on('select', function(){
			var a = frame.state().get('selection').first().toJSON();
			$('#kask-icon-id').val(a.id);
			$('#kask-icon-preview').html('<img src="' + a.url + '" alt="" style="max-width:120px;height:auto">');
			$('#kask-icon-remove').show();
		});
		frame.open();
	});
	$('#kask-icon-remove').on('click', function(e){
		e.preventDefault();
		$('#kask-icon-id').val('');
		$('#kask-icon-preview').empty();
		$(this).hide();
	});
});
JS
		);
	}

	public function page() {
		if ( ! current_user_can( 'manage_options' ) ) return;
		$o   = $this->opts();
		$n   = self::OPT;
		$src = $o['icon_id'] ? wp_get_attachment_image_url( (int) $o['icon_id'], 'medium' ) : '';
		?>
<div class="wrap">
	<h1>Preloader</h1>
	<form method="post" action="options.php">
		<?php settings_fields( self::SLUG ); ?>
		<table class="form-table" role="presentation">
			<tr>
				<th scope="row">Status</th>
				<td><label><input type="checkbox" name="<?php echo $n; ?>[enabled]" value="1" <?php checked( $o['enabled'], 1 ); ?>> Show preloader on the front end</label></td>
			</tr>
			<tr>
				<th scope="row">Icon</th>
				<td>
					<div id="kask-icon-preview" style="margin-bottom:8px"><?php if ( $src ) : ?><img src="<?php echo esc_url( $src ); ?>" alt="" style="max-width:120px;height:auto"><?php endif; ?></div>
					<input type="hidden" id="kask-icon-id" name="<?php echo $n; ?>[icon_id]" value="<?php echo (int) $o['icon_id']; ?>">
					<button type="button" class="button" id="kask-icon-select">Choose icon</button>
					<button type="button" class="button-link-delete" id="kask-icon-remove" style="margin-left:8px;<?php echo $src ? '' : 'display:none'; ?>">Remove</button>
				</td>
			</tr>
			<tr>
				<th scope="row"><label for="kask-size">Icon width</label></th>
				<td><input type="number" id="kask-size" name="<?php echo $n; ?>[size]" value="<?php echo (int) $o['size']; ?>" min="16" max="400" class="small-text"> px</td>
			</tr>
			<tr>
				<th scope="row">Background color</th>
				<td><input type="text" class="kask-color" name="<?php echo $n; ?>[bg]" value="<?php echo esc_attr( $o['bg'] ); ?>" data-default-color="#ffffff"></td>
			</tr>
		</table>
		<?php submit_button( 'Save changes' ); ?>
	</form>
</div>
		<?php
	}
}

new Kask_Preloader();
