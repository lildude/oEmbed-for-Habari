<?php
/**
 * oEmbed for Habari
 * 
 * A Habari plugin that allows you to embed content from certain providers using 
 * their oEmbed implementation and Habari's new support for shortcodes.
 *
 */
namespace Habari;

class oEmbed extends Plugin
{
	var $providers = array();

	/**
	 * Function that filters our post content and locates the embeded shortcode.
	 * Supported shortcode formats:
	 *
	 * @TODO: [embed]https://twitter.com/lildude/status/295511299243266048[/embed]
	 * @TODO: [embed width="200" height="200"]https://twitter.com/lildude/status/295511299243266048[/embed]
	 * @TODO: [embed width="200" height="200" url="https://twitter.com/lildude/status/295511299243266048"/]
	 */
	public function filter_shortcode_embed( $code_to_replace, $code_name, $attr_array, $code_contents, $post )
	{
		$this->providers = array(
	        '#https?://(www\.)?youtube.com/watch.*#i'            => array( 'http://www.youtube.com/oembed',                     true  ),
	        'http://youtu.be/*'                                  => array( 'http://www.youtube.com/oembed',                     false ),
	        'http://blip.tv/*'                                   => array( 'http://blip.tv/oembed/',                            false ),
	        '#https?://(www\.)?vimeo\.com/.*#i'                  => array( 'http://vimeo.com/api/oembed.{format}',              true  ),
	        '#https?://(www\.)?dailymotion\.com/.*#i'            => array( 'http://www.dailymotion.com/services/oembed',        true  ),
	        '#https?://(www\.)?flickr\.com/.*#i'                 => array( 'http://www.flickr.com/services/oembed/',            true  ),
	        '#https?://(.+\.)?smugmug\.com/.*#i'                 => array( 'http://api.smugmug.com/services/oembed/',           true  ),
	        '#https?://(www\.)?hulu\.com/watch/.*#i'             => array( 'http://www.hulu.com/api/oembed.{format}',           true  ),
	        '#https?://(www\.)?viddler\.com/.*#i'                => array( 'http://lab.viddler.com/services/oembed/',           true  ),
	        'http://qik.com/*'                                   => array( 'http://qik.com/api/oembed.{format}',                false ),
	        'http://revision3.com/*'                             => array( 'http://revision3.com/api/oembed/',                  false ),
	        'http://i*.photobucket.com/albums/*'                 => array( 'http://photobucket.com/oembed',                     false ),
	        'http://gi*.photobucket.com/groups/*'                => array( 'http://photobucket.com/oembed',                     false ),
	        '#https?://(www\.)?scribd\.com/.*#i'                 => array( 'http://www.scribd.com/services/oembed',             true  ),
	        'http://wordpress.tv/*'                              => array( 'http://wordpress.tv/oembed/',                       false ),
	        '#https?://(.+\.)?polldaddy\.com/.*#i'               => array( 'http://polldaddy.com/oembed/',                      true  ),
	        '#https?://(www\.)?funnyordie\.com/videos/.*#i'      => array( 'http://www.funnyordie.com/oembed',                  true  ),
	        '#https?://(www\.)?twitter.com/.+?/status(es)?/.*#i' => array( 'http://api.twitter.com/1/statuses/oembed.{format}', true  ),
	        '#https?://(www\.)?soundcloud\.com/.*#i'             => array( 'http://soundcloud.com/oembed',                      true  ),
	        '#https?://(www\.)?slideshare.net/*#'                => array( 'http://www.slideshare.net/api/oembed/2',            true  ),
	        '#http://instagr(\.am|am\.com)/p/.*#i'               => array( 'http://api.instagram.com/oembed',                   true  ),
    	);
		// Grab the url from our shortcode
		$url = ( isset( $attr_array['url'] ) ) ? $attr_array['url'] : $code_contents;

		//Utils::debug($code_to_replace);
		//Utils::debug($attr_array);
		$out = ( $url ) ? $this->get_html( $url ) : 'No url';
		Utils::debug($out);
		
	}

	public function get_html( $url, $args = '' )
	{
		$provider = false;

        if ( !isset( $args['discover'] ) ) {
            $args['discover'] = true;
		}

        foreach ( $this->providers as $matchmask => $data ) {
            list( $providerurl, $regex ) = $data;

            // Turn the asterisk-type provider URLs into regex
            if ( !$regex ) {
                $matchmask = '#' . str_replace( '___wildcard___', '(.+)', preg_quote( str_replace( '*', '___wildcard___', $matchmask ), '#' ) ) . '#i';
                $matchmask = preg_replace( '|^#http\\\://|', '#https?\://', $matchmask );
            }

            if ( preg_match( $matchmask, $url ) ) {
                $provider = str_replace( '{format}', 'json', $providerurl ); // JSON is easier to deal with than XML
                break;
            }
        }

        //if ( !$provider && $args['discover'] )
            $provider = $this->discover( $url );

        //if ( !$provider || false === $data = $this->fetch( $provider, $url, $args ) )
        	// return false;
/*
        return apply_filters( 'oembed_result', $this->data2html( $data, $url ), $url, $args );
        */
	}


	public function discover( $url )
	{
		$providers = array();

        // Fetch URL content
        if ( $html = RemoteRequest::get_contents( $url ) ) {
        		
            // <link> types that contain oEmbed provider URLs
            $linktypes = array(
                'application/json+oembed' => 'json',
                'text/xml+oembed' => 'xml',
                'application/xml+oembed' => 'xml', // Incorrect, but used by at least Vimeo
            );

            // Strip <body>
            $html = substr( $html, 0, stripos( $html, '</head>' ) );

            // Do a quick check
            $tagfound = false;
            foreach ( $linktypes as $linktype => $format ) {
                if ( stripos($html, $linktype) ) {
                    $tagfound = true;
                    break;
                }
            }

            if ( $tagfound && preg_match_all( '/<link([^<>]+)>/i', $html, $links ) ) {
                foreach ( $links[1] as $link ) {
                    $atts = $this->shortcode_parse_atts( $link );

                    if ( !empty($atts['type']) && !empty($linktypes[$atts['type']]) && !empty($atts['href']) ) {
                        $providers[$linktypes[$atts['type']]] = $atts['href'];
                        // Stop here if it's JSON (that's all we need)
                        if ( 'json' == $linktypes[$atts['type']] ) {
                            break;
                        }
                    }
                }
            }
        }

        // JSON is preferred to XML
        if ( !empty($providers['json']) ) {
            return $providers['json'];
        }
        elseif ( !empty($providers['xml']) ) {
            return $providers['xml'];
        }
        else {
            return false;
        }
	}

	public function fetch()
	{

	}

	public function shortcode_parse_atts($text) {
        $atts = array();
        $pattern = '/(\w+)\s*=\s*"([^"]*)"(?:\s|$)|(\w+)\s*=\s*\'([^\']*)\'(?:\s|$)|(\w+)\s*=\s*([^\s\'"]+)(?:\s|$)|"([^"]*)"(?:\s|$)|(\S+)(?:\s|$)/';
        $text = preg_replace("/[\x{00a0}\x{200b}]+/u", " ", $text);
        if ( preg_match_all($pattern, $text, $match, PREG_SET_ORDER) ) {
                foreach ($match as $m) {
                        if (!empty($m[1]))
                                $atts[strtolower($m[1])] = stripcslashes($m[2]);
                        elseif (!empty($m[3]))
                                $atts[strtolower($m[3])] = stripcslashes($m[4]);
                        elseif (!empty($m[5]))
                                $atts[strtolower($m[5])] = stripcslashes($m[6]);
                        elseif (isset($m[7]) and strlen($m[7]))
                                $atts[] = stripcslashes($m[7]);
                        elseif (isset($m[8]))
                                $atts[] = stripcslashes($m[8]);
                }
        } else {
                $atts = ltrim($text);
        }
        return $atts;
	}
	
}
?>