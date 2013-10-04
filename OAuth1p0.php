<?php
class OAuth1p0 {

	const OAUTH_PREFIX = "oauth_";
	const CONSUMER_KEY = "oauth_consumer_key";
	const SIGNATURE_METHOD = "oauth_signature_method";
	const TIMESTAMP = "oauth_timestamp";
	const NONCE = "oauth_nonce";
	const VERSION = "oauth_version";
	const SIGNATURE = "oauth_signature";
	const TOKEN = "oauth_token";
	const CALLBACK = "oauth_callback";
	const BODY_HASH = "oauth_body_hash";
	const VERSION_1_0 = "1.0";
	const SIGNATURE_METHOD_HMAC_SHA1 = "HMAC-SHA1";
	const BLANK_CALLBACK = "about:blank";
	const HTTP_DEFAULT_PORT = 80;
	const HTTPS_DEFAULT_PORT = 443;
	
	
	/**
	 * Create new OAuth parameters for x-www-form-urlencoded message.
	 * 
	 * @param string $url Target url
	 * @param string $httpMethod HTTP method
	 * @param array $parameters Key/value pair of parameters
	 * @param string $key OAuth key name
	 * @param string $secret Secret used for OAuth signature
	 * @return array of OAuth parameters
	 */
	public static function CreateOAuthParametersForFormUrlEncoded($url, $httpMethod, $parameters, $key, $secret) {
		$timeStamp = time();

		$mt = microtime();
		$rand = mt_rand();
		
		$nonce = hash('sha512', $mt . $rand);
		$oauthParameters = array();

		// add OAuth parameters
		$oauthParameters[ self::VERSION ] = self::VERSION_1_0;
		$oauthParameters[ self::NONCE ] = $nonce;
		$oauthParameters[ self::TIMESTAMP ] = $timeStamp;
		$oauthParameters[ self::SIGNATURE_METHOD ] = self::SIGNATURE_METHOD_HMAC_SHA1;
		$oauthParameters[ self::CONSUMER_KEY ] = $key;
		$oauthParameters[ self::CALLBACK ] = self::BLANK_CALLBACK;

		$signature = self::CalculateSignatureForFormUrlEncoded( $url, $httpMethod, $secret, $parameters, $oauthParameters );

		$oauthParameters[ self::SIGNATURE ] = $signature;

		return $oauthParameters;
	}

	/**
	 * Generates OAuth signature for passed in parameters and checks against passed in signature
	 * @param string $url URL that request was sent to
	 * @param string $httpMethod HTTP method used
	 * @param array $parameters parameters that were used in OAuth signature and OAuth parameters
	 * @param string $secret OAuth secret that will be used to generated the signature
	 * @return boolean true if generated signature matches passed in OAuth signature, false if it doesn't
	 */
	public static function CheckSignatureForFormUrlEncoded( $url, $httpMethod, $parameters, $secret) {
	    $oauthParameters = array();
	    $lmsParameters = array();

	    // Separate LMS and OAuth parameters
	    foreach( $parameters as $key => $value ) {
	        if( strpos( $key, self::OAUTH_PREFIX ) === 0 ) {
	            $oauthParameters[urldecode( $key )] = urldecode( $value );
	            continue;
	        }
	        $lmsParameters[urldecode( $key )] = urldecode( $value );
	    }
	    
	    $signature = self::CalculateSignatureForFormUrlEncoded( $url, $httpMethod, $secret, $lmsParameters, $oauthParameters );
	    
	    return $parameters[ self::SIGNATURE ] === $signature;   
	    
	}


	/**
	 * Calculates OAuth signature for x-www-form-urlencoded message
	 * @param string $url URL that request will be sent to
	 * @param string $httpMethod HTTP method used
	 * @param string $secret secret used for OAuth signing
	 * @param array $formData key/value pairs from the form to sign
	 * @param array $oauthParameters OAuth parameters used in the request
	 * @throws Exception if there are duplicate parameters
	 * @return string OAuth signature
	 */
	public static function CalculateSignatureForFormUrlEncoded( $url, $httpMethod, $secret, $formData, $oauthParameters ) {
		$newOAuthParameters = self::RemoveUnwantedOAuthParameters( $oauthParameters );

		// url parameters
		$urlQuery = parse_url( $url, PHP_URL_QUERY );
		parse_str($urlQuery, $queryParameters);
		$urlParameters = array();

		foreach($queryParameters as $key => $value) {
			//Ignore OAuth parameters
			if( strpos( $key, self::OAUTH_PREFIX ) === 0 ) {
				continue;
			}
			$urlParameters[urldecode( $key )] = urldecode( $value );
		}

		// sort parameters (LMS parameters, Url parameters and OAuth parameters)
		$parameters = array();

		foreach( $formData as $key => $value ) {
			if( array_key_exists( $key, $parameters ) ) {
				throw new Exception( "$key already exists." );
			} else {
				$parameters[ $key ] = $value;
			}
		}

		foreach( $urlParameters as $key => $value ) {
			if( array_key_exists( $key, $parameters ) ) {
				throw new Exception( "$key already exists." );
			} else {
				$parameters[ $key ] = $value;
			}
		}

		foreach( $newOAuthParameters as $key => $value ) {
			if( array_key_exists( $key, $parameters ) ) {
				throw new Exception( "$key already exists." );
			} else {
				$parameters[ $key ] = $value;
			}
		}

		return self::CalculateSignature( $httpMethod, $url, $parameters, $secret );
	}

	
	/**
	 * Calculate signature for application/xml message. Can also be used by any type of message other than x-www-form-urlencoded.
	 * @param string $url Request URL
	 * @param string $httpMethod HTTP method used in the request
	 * @param string $secret OAuth secret
	 * @param string $xmlBody Request body
	 * @param string $oauthParameters OAuth parameters
	 * @return string OAuth signature
	 */
	public function CalculateSignatureForXml( $url, $httpMethod, $secret, $xmlBody, $oauthParameters ) {
		$parameters = self::RemoveUnwantedOAuthParameters( $oauthParameters );

		// body hash
		$bodyHash = CalculateSHA1Hash( $xmlBody );
		$parameters[ self::BODY_HASH ] = $bodyHash;

		return self::CalculateSignature( $httpMethod, $url, $parameters, $secret );
	}


	/**
	 * Remove OAuth signature and body hash from the list of parameters
	 * @param array $oauthParameters OAuth parameters to check
	 * @return array OAuth parameters without signature and body hash 
	 */
	private static function RemoveUnwantedOAuthParameters( $oauthParameters ) {
		$newOAuthParameters = array();

		foreach( $oauthParameters as $key => $value ) {
			if( $key != self::SIGNATURE && $key != self::BODY_HASH ) {
				$newOAuthParameters[ $key ] = $value;
			}
		}

		return $newOAuthParameters;
	}


	/**
	 * Calculate OAuth signature
	 * @param string $httpMethod HTTP method used in the request
	 * @param string $url URL used in the request
	 * @param array $parameters request parameters as key/value pairs
	 * @param string $secret OAuth secret
	 * @return string OAuth signature
	 */
	private static function CalculateSignature( $httpMethod, $url, $parameters, $secret ) {
		ksort($parameters);

		$normalizedParameters = "";

		foreach( $parameters as $key => $value ) {

			if( strlen($normalizedParameters) != 0 ) {
				$normalizedParameters .= '&';
			}

			$normalizedParameters .= rawurlencode( $key );
			$normalizedParameters .=  "=";
			$normalizedParameters .= rawurlencode( $value );
		}

		// normalize url
		$normalizedUrl = self::NormalizeUrl( $url );

		//  build signature base string
		$signatureBase = strtoupper( $httpMethod );
		$signatureBase .= '&';
		$signatureBase .= rawurlencode( $normalizedUrl );
		$signatureBase .= '&';
		$signatureBase .= rawurlencode( $normalizedParameters );

		// sign
		return self::CalculateSHA1Hash( $signatureBase, $secret );
	}


	/**
	 * Calculates SHA1 has
	 * @param string $s string to encode
	 * @param string $secret (optional) secret used in the signature
	 * @return string SHA1 hash
	 */
	private static function CalculateSHA1Hash( $s, $secret = null ) {
	    if ( $secret != null ) {
	        $key = rawurlencode( $secret ) . '&';
	        $signature = base64_encode(hash_hmac("sha1", $s, $key, true));
	    } else {
	        $signature = base64_encode( sha1( $s, true ) );
	    }

		return $signature;
	}

	
	/**
	 * Normalizes URL
	 * @param string $url URL to normalize
	 * @return string normalized URL
	 */
	public static function NormalizeUrl( $url ) {
		$parsed = parse_url( $url );
		$scheme = 'https';
		$port = self::HTTPS_DEFAULT_PORT;

		// Figure out scheme and port
		if ( empty( $parsed['scheme'] ) || $parsed['scheme'] == 'http' ) {
			$scheme = 'http';
			if( !empty( $parsed['port'] ) ) {
				$port =  $parsed['port'];
			} else {
				$port = self::HTTP_DEFAULT_PORT;
			}
		} else {
			if( !empty( $parsed['port'] ) ) {
				$port =  $parsed['port'];
			}
		}
		
		$normalizedUrl = $scheme . "://" . $parsed['host'];
		

		if( ( strcasecmp( $scheme, 'http' ) === 0 && $port != self::HTTP_DEFAULT_PORT ) ||
				( strcasecmp( $scheme, 'https' ) === 0 && $port != self::HTTPS_DEFAULT_PORT ) ) {
			$normalizedUrl .= ":" . $port;
		}

		$normalizedUrl = strtolower( $normalizedUrl );

		$normalizedUrl .= $parsed['path'];

		return $normalizedUrl;
	}
}
?>