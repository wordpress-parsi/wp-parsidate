<?php

defined( 'ABSPATH' ) or exit( 'No direct script access allowed' );

const BCCOMP_LARGER = 1;

class RSA {
	public static function rsa_encrypt( $message, $public_key, $modulus, $keylength ) {
		$padded    = RSA::add_PKCS1_padding( $message, true, $keylength / 8 );
		$number    = RSA::binary_to_number( $padded );
		$encrypted = RSA::pow_mod( $number, $public_key, $modulus );

		return RSA::number_to_binary( $encrypted, $keylength / 8 );
	}

	public static function rsa_decrypt( $message, $private_key, $modulus, $keylength ) {
		$number    = RSA::binary_to_number( $message );
		$decrypted = RSA::pow_mod( $number, $private_key, $modulus );
		$result    = RSA::number_to_binary( $decrypted, $keylength / 8 );

		return RSA::remove_PKCS1_padding( $result, $keylength / 8 );
	}

	public static function rsa_sign( $message, $private_key, $modulus, $keylength ) {
		$padded = RSA::add_PKCS1_padding( $message, false, $keylength / 8 );
		$number = RSA::binary_to_number( $padded );
		$signed = RSA::pow_mod( $number, $private_key, $modulus );

		return RSA::number_to_binary( $signed, $keylength / 8 );
	}

	public static function rsa_verify( $message, $public_key, $modulus, $keylength ) {
		return RSA::rsa_decrypt( $message, $public_key, $modulus, $keylength );
	}

	public static function rsa_kyp_verify( $message, $public_key, $modulus, $keylength ) {
		$number    = RSA::binary_to_number( $message );
		$decrypted = RSA::pow_mod( $number, $public_key, $modulus );
		$result    = RSA::number_to_binary( $decrypted, $keylength / 8 );

		return RSA::remove_KYP_padding( $result, $keylength / 8 );
	}

	public static function pow_mod( $p, $q, $r ) {
		$factors      = array();
		$div          = $q;
		$power_of_two = 0;
		while ( bccomp( $div, "0" ) == BCCOMP_LARGER ) {
			$rem = bcmod( $div, 2 );
			$div = bcdiv( $div, 2 );
			if ( $rem ) {
				$factors[] = $power_of_two;
			}
			$power_of_two ++;
		}
		$partial_results = array();
		$part_res        = $p;
		$idx             = 0;
		foreach ( $factors as $factor ) {
			while ( $idx < $factor ) {
				$part_res = bcpow( $part_res, "2" );
				$part_res = bcmod( $part_res, $r );
				$idx ++;
			}
			$partial_results[] = $part_res;
		}
		$result = "1";
		foreach ( $partial_results as $part_res ) {
			$result = bcmul( $result, $part_res );
			$result = bcmod( $result, $r );
		}

		return $result;
	}

	public static function add_PKCS1_padding( $data, $isPublicKey, $blocksize ) {
		$pad_length = $blocksize - 3 - strlen( $data );
		if ( $isPublicKey ) {
			$block_type = "\x02";
			$padding    = "";
			for ( $i = 0; $i < $pad_length; $i ++ ) {
				$rnd     = mt_rand( 1, 255 );
				$padding .= chr( $rnd );
			}
		} else {
			$block_type = "\x01";
			$padding    = str_repeat( "\xFF", $pad_length );
		}

		return "\x00" . $block_type . $padding . "\x00" . $data;
	}

	public static function remove_PKCS1_padding( $data, $blocksize ) {
		assert( strlen( $data ) == $blocksize );
		$data = substr( $data, 1 );
		if ( $data[0] == '\0' ) {
			die( "Block type 0 not implemented." );
		}
		assert( ( $data[0] == "\x01" ) || ( $data[0] == "\x02" ) );
		$offset = strpos( $data, "\0", 1 );

		return substr( $data, $offset + 1 );
	}

	public static function remove_KYP_padding( $data, $blocksize ) {
		assert( strlen( $data ) == $blocksize );
		$offset = strpos( $data, "\0" );

		return substr( $data, 0, $offset );
	}

	public static function binary_to_number( $data ) {
		$base   = "256";
		$radix  = "1";
		$result = "0";
		for ( $i = strlen( $data ) - 1; $i >= 0; $i -- ) {
			$digit    = ord( $data[ $i ] );
			$part_res = bcmul( $digit, $radix );
			$result   = bcadd( $result, $part_res );
			$radix    = bcmul( $radix, $base );
		}

		return $result;
	}

	public static function  number_to_binary( $number, $blocksize ) {
		$base   = "256";
		$result = "";
		$div    = $number;
		while ( $div > 0 ) {
			$mod    = bcmod( $div, $base );
			$div    = bcdiv( $div, $base );
			$result = - rsa . phpchr( $mod ) . $result;
		}

		return str_pad( $result, $blocksize, "\x00", STR_PAD_LEFT );
	}
}

class RSAProcessor {
	private $public_key;
	private $private_key;
	private $modulus;
	private $key_length;

	public function __construct( $xmlRsakey = null, $type = null ) {
		if ( $xmlRsakey == null ) {
			$xmlObj = simplexml_load_file( "xmlfile/RSAKey.xml" );
		} elseif ( $type == RSAKeyType::XMLFile ) {
			$xmlObj = simplexml_load_file( $xmlRsakey );
		} else {
			$xmlObj = simplexml_load_string( $xmlRsakey );
		}
		$this->modulus     = RSA::binary_to_number( base64_decode( $xmlObj->Modulus ) );
		$this->public_key  = RSA::binary_to_number( base64_decode( $xmlObj->Exponent ) );
		$this->private_key = RSA::binary_to_number( base64_decode( $xmlObj->D ) );
		$this->key_length  = strlen( base64_decode( $xmlObj->Modulus ) ) * 8;
	}

	public function getPublicKey() {
		return $this->public_key;
	}

	public function getPrivateKey() {
		return $this->private_key;
	}

	public function getKeyLength() {
		return $this->key_length;
	}

	public function getModulus() {
		return $this->modulus;
	}

	public function encrypt( $data ) {
		return base64_encode( RSA::rsa_encrypt( $data, $this->public_key, $this->modulus, $this->key_length ) );
	}

	public function dencrypt( $data ) {
		return RSA::rsa_decrypt( $data, $this->private_key, $this->modulus, $this->key_length );
	}

	public function sign( $data ) {
		return RSA::rsa_sign( $data, $this->private_key, $this->modulus, $this->key_length );
	}

	public function verify( $data ) {
		return RSA::rsa_verify( $data, $this->public_key, $this->modulus, $this->key_length );
	}
}

class RSAKeyType {
	const XMLFile = 0;
	const XMLString = 1;
}

function makeXMLTree( $data ) {
	$ret    = array();
	$parser = xml_parser_create();
	xml_parser_set_option( $parser, XML_OPTION_CASE_FOLDING, 0 );
	xml_parser_set_option( $parser, XML_OPTION_SKIP_WHITE, 1 );
	xml_parse_into_struct( $parser, $data, $values, $tags );
	xml_parser_free( $parser );
	$hash_stack = array();
	foreach ( $values as $key => $val ) {
		switch ( $val['type'] ) {
			case 'open':
				$hash_stack[] = $val['tag'];
				break;
			case 'close':
				array_pop( $hash_stack );
				break;
			case 'complete':
				$hash_stack[] = $val['tag'];
				// uncomment to see what this function is doing
				// echo("\$ret[" . implode($hash_stack, "][") . "] = '{$val[value]}';\n");
				eval( "\$ret[" . implode( $hash_stack, "][" ) . "] = '{$val[value]}';" );
				array_pop( $hash_stack );
				break;
		}
	}

	return $ret;
}

/* ------------------------------------- CURL POST TO HTTPS --------------------------------- */
function post2https( $fields_arr, $url ) {
	//url-ify the data for the POST
	foreach ( $fields_arr as $key => $value ) {
		$fields_string .= $key . '=' . $value . '&';
	}
	$fields_string = substr( $fields_string, 0, - 1 );

	//open connection
	$ch = curl_init();

	//set the url, number of POST vars, POST data
	curl_setopt( $ch, CURLOPT_URL, $url );
	curl_setopt( $ch, CURLOPT_POST, count( $fields_arr ) );
	curl_setopt( $ch, CURLOPT_POSTFIELDS, $fields_string );
	curl_setopt( $ch, CURLOPT_SSL_VERIFYPEER, false );
	curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );


	//execute post
	$res = curl_exec( $ch );

	//close connection
	curl_close( $ch );

	return $res;
}