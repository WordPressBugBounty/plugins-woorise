<?php

/**
 * Class Woorise_Embed
 */
class Woorise_Embed {

  /**
   * Shortcode tag name.
   * @var string
   */
  private $shortcode_tag = 'woorise';

  /**
   * iframe id tag.
   * @var string
   */
  private $iframe_class = 'e-woorise';

  /**
   * oembed handler url regex.
   * @var string
   */
  private $regex = '#https://(www\.)?woorise\.com/([^/]+)/((?:c/([0-9]+))|([^/]+))#i';

  /**
   * Woorise_Embed constructor.
   */
  function __construct() {
    add_action( 'init', [ $this, 'init' ] );
    add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
    add_shortcode( $this->get_shortcode_tag(), [ $this, 'shortcode' ] );
  }

  /**
   * Init function.
   */
  public function init() {
    wp_embed_register_handler( 'woorise', $this->regex, [ &$this, 'oembed_handler' ] );
  }

  /**
   * Enqueue scripts.
   */
  public function enqueue_scripts() {
    wp_register_script( 'woorise-embed', plugins_url( 'assets/js/iframe-resizer.parent.js', dirname( __FILE__ ) ), [], '5.2.1', true );
    wp_add_inline_script( 'woorise-embed', 'document.addEventListener("DOMContentLoaded",function(){iFrameResize({checkOrigin: false, license: "GPLv3"},"'. esc_attr( '.' . $this->iframe_class ) .'");});' );
  }

  /**
   * Oembed.
   *
   * @param object $match url.
   *
   * @return string
   */
  public function oembed_handler( $matches, $attr, $url, $rawattr ) {

    if ( wp_parse_url( $url, PHP_URL_HOST ) === 'woorise.com' ) {
      $embed = $this->get_embed( esc_attr( $matches[0] ) );
    }
    else {
      $embed = '';
    }

    return apply_filters( 'woorise_embed', $embed, $matches, $attr, $url, $rawattr );
  }

  /**
   * Output tags at footer.
   * @param array  $atts.
   * @return string
   */
  public function shortcode( $atts ) {

    $atts = shortcode_atts( [
  		'url' => '',
  	], $atts, $this->shortcode_tag );

    $embed = $this->get_embed( $atts['url'] );

    return $embed;
  }

  /**
   * Get shortcode tag.
   *
   * @return mixed|void
   */
  private function get_shortcode_tag() {
    return apply_filters( 'woorise_shortcode_tag', $this->shortcode_tag );
  }

  /**
   * Get embed.
   *
   * @return string
   */
  public function get_embed( $url ) {

    $current_url = set_url_scheme( 'http://' . wp_unslash( $_SERVER['HTTP_HOST'] ) . wp_unslash( $_SERVER['REQUEST_URI'] ) );

    $args = [
      'u' => strtok( $current_url, '?' ),
    ];

    $current_query = wp_parse_url( $current_url, PHP_URL_QUERY );

    if ( $current_query ) {
      parse_str( $current_query, $current_pieces );
      unset( $current_pieces['preview_nonce'] );
      $args = array_merge( $args, $current_pieces );
    }

    $src = add_query_arg( $args, $url );

    $output = sprintf(
      '<iframe class="%1$s" src="%2$s" style="border:none;width:1px;min-width:100%%;" scrolling="no"></iframe>',
      esc_attr( $this->iframe_class ), esc_url( $src )
    );

    wp_enqueue_script( 'woorise-embed' );

    return $output;
  }

  /**
   * Block render callback.
   *
   * @return string
   */
  public function woorise_embed_block_handler( $atts ) {

    $wrapper_attributes = get_block_wrapper_attributes();

    return sprintf(
      '<div %1$s>%2$s</div>',
      $wrapper_attributes,
      $this->get_embed( $atts['url'] )
    );
  }

} // end class
