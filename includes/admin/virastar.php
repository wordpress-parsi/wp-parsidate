<?php

namespace Alirezasedghi\Virastar;

use Exception;

class Virastar {
	private $charsPersian = 'ءاآأإئؤبپتثجچحخدذرزژسشصضطظعغفقکگلمنوهیةيك';

	// @REF: https://en.wikipedia.org/wiki/Persian_alphabet#Diacritics
	// `\u064e\u0650\u064f\u064b\u064d\u064c\u0651\u06c0`
	private $charsDiacritic = 'ًٌٍَُِّْ';

	private $patternURI = '#[-a-zA-Z0-9@:%_\+.~\#?&//=]{2,256}\.[a-z]{2,4}\b(\/[-a-zA-Z0-9@:%_\+.~\#?&//=]*)?#sim';
	private $patternAfter = '\\s.,;،؛!؟?"\'()[\\]{}“”«»';

	private $defaults = [
		"cleanup_begin_and_end"                          => true,
		"cleanup_extra_marks"                            => true,
		"cleanup_kashidas"                               => true,
		"cleanup_line_breaks"                            => true,
		"cleanup_rlm"                                    => true,
		"cleanup_spacing"                                => true,
		"cleanup_zwnj"                                   => true,
		"decode_html_entities"                           => true,
		"fix_arabic_numbers"                             => true,
		"fix_dashes"                                     => true,
		"fix_diacritics"                                 => true,
		"fix_english_numbers"                            => true,
		"fix_english_quotes_pairs"                       => true,
		"fix_english_quotes"                             => true,
		"fix_hamzeh"                                     => true,
		"fix_hamzeh_arabic"                              => false,
		"fix_misc_non_persian_chars"                     => true,
		"fix_misc_spacing"                               => true,
		"fix_numeral_symbols"                            => true,
		"fix_prefix_spacing"                             => true,
		"fix_persian_glyphs"                             => true,
		"fix_punctuations"                               => true,
		"fix_question_mark"                              => true,
		"fix_spacing_for_braces_and_quotes"              => true,
		"fix_spacing_for_punctuations"                   => true,
		"fix_suffix_misc"                                => true,
		"fix_suffix_spacing"                             => true,
		"fix_three_dots"                                 => true,
		"kashidas_as_parenthetic"                        => true,
		"markdown_normalize_braces"                      => true,
		"markdown_normalize_lists"                       => true,
		"normalize_dates"                                => true,
		"normalize_ellipsis"                             => true,
		"normalize_eol"                                  => true,
		"preserve_braces"                                => false,
		"preserve_brackets"                              => false,
		"preserve_comments"                              => true,
		"preserve_entities"                              => true,
		"preserve_front_matter"                          => true,
		"preserve_HTML"                                  => true,
		"preserve_nbsp"                                  => true,
		"preserve_URIs"                                  => true,
		"remove_diacritics"                              => false,
		"skip_markdown_ordered_lists_numbers_conversion" => false
	];

	private $digits = '۱۲۳۴۵۶۷۸۹۰';

	private $entities = [
		'sbquo;'  => '\x{201a}',
		'lsquo;'  => '\x{2018}',
		'lsquor;' => '\x{201a}',
		'ldquo;'  => '\x{201c}',
		'ldquor;' => '\x{201e}',
		'rdquo;'  => '\x{201d}',
		'rdquor;' => '\x{201d}',
		'rsquo;'  => '\x{2019}',
		'rsquor;' => '\x{2019}',
		'apos;'   => '\'',
		'QUOT;'   => '"',
		'QUOT'    => '"',
		'quot;'   => '"',
		'quot'    => '"',
		'zwj;'    => '\x{200d}',
		'ZWNJ;'   => '\x{200c}',
		'zwnj;'   => '\x{200c}',
		'shy;'    => '\x{00ad}' // wrongly used as zwnj
	];

	private $glyphs = [
		'\x{200c}ه' => 'ﻫ',
		'ی\x{200c}' => 'ﻰﻲ',
		'ﺃ'         => 'ﺄﺃ',
		'ﺁ'         => 'ﺁﺂ',
		'ﺇ'         => 'ﺇﺈ',
		'ا'         => 'ﺎا',
		'ب'         => 'ﺏﺐﺑﺒ',
		'پ'         => 'ﭖﭗﭘﭙ',
		'ت'         => 'ﺕﺖﺗﺘ',
		'ث'         => 'ﺙﺚﺛﺜ',
		'ج'         => 'ﺝﺞﺟﺠ',
		'چ'         => 'ﭺﭻﭼﭽ',
		'ح'         => 'ﺡﺢﺣﺤ',
		'خ'         => 'ﺥﺦﺧﺨ',
		'د'         => 'ﺩﺪ',
		'ذ'         => 'ﺫﺬ',
		'ر'         => 'ﺭﺮ',
		'ز'         => 'ﺯﺰ',
		'ژ'         => 'ﮊﮋ',
		'س'         => 'ﺱﺲﺳﺴ',
		'ش'         => 'ﺵﺶﺷﺸ',
		'ص'         => 'ﺹﺺﺻﺼ',
		'ض'         => 'ﺽﺾﺿﻀ',
		'ط'         => 'ﻁﻂﻃﻄ',
		'ظ'         => 'ﻅﻆﻇﻈ',
		'ع'         => 'ﻉﻊﻋﻌ',
		'غ'         => 'ﻍﻎﻏﻐ',
		'ف'         => 'ﻑﻒﻓﻔ',
		'ق'         => 'ﻕﻖﻗﻘ',
		'ک'         => 'ﮎﮏﮐﮑﻙﻚﻛﻜ',
		'گ'         => 'ﮒﮓﮔﮕ',
		'ل'         => 'ﻝﻞﻟﻠ',
		'م'         => 'ﻡﻢﻣﻤ',
		'ن'         => 'ﻥﻦﻧﻨ',
		'ه'         => 'ﻩﻪﻫﻬ',
		'هٔ'        => 'ﮤﮥ',
		'و'         => 'ﻭﻮ',
		'ﺅ'         => 'ﺅﺆ',
		'ی'         => 'ﯼﯽﯾﯿﻯﻰﻱﻲﻳﻴ',
		'ئ'         => 'ﺉﺊﺋﺌ',
		'لا'        => 'ﻼ',
		'ﻹ'         => 'ﻺ',
		'ﻷ'         => 'ﻸ',
		'ﻵ'         => 'ﻶ'
	];

	// Options
	private $options = array();

	/**
	 * Construction
	 *
	 * @throws Exception
	 */
	public function __construct( $options = array() ) {
		if ( ! empty( $options ) ) {
			if ( is_array( $options ) ) {
				$this->setOptions( $options );
			} else {
				throw new Exception( 'Options should be an array.' );
			}
		}
	}

	/**
	 * Set options or update it
	 *
	 * @param array $options
	 */
	public function setOptions( array $options ) {
		$this->options = $this->parseOptions( $options );
	}

	/**
	 * Get options
	 *
	 * @return array
	 */
	public function getOptions() {
		return ! empty( $this->options ) ? $this->options : $this->defaults;
	}

	/**
	 * Parse options
	 *
	 * @param $options
	 *
	 * @return array
	 */
	public function parseOptions( $options ) {
		if ( is_object( $options ) ) {
			$parsed_args = get_object_vars( $options );
		} elseif ( is_array( $options ) ) {
			$parsed_args =& $options;
		} else {
			parse_str( (string) $options, $parsed_args );
		}

		$defaults = $this->defaults;

		if ( ! empty( $parsed_args ) ) {
			return array_merge( $defaults, $parsed_args );
		}

		return $defaults;
	}

	/**
	 * Converts numeral and selected html character-sets into original characters
	 *
	 * @param $text
	 *
	 * @return string
	 */
	public function decodeHTMLEntities( $text ) {
		return preg_replace_callback( '/&(#?[^;\W]+;?)/', function ( $matched ) {
			$match = isset( $matched[1] ) ? $matched[1] : '';

			// $htmlarray() = $matched;
			return ' __HTML__PRESERVER__ ';
		}, $text );
	}

	/**
	 * Cleanup Text
	 *
	 * @param $text
	 *
	 * @return string
	 * @throws Exception
	 */
	public function cleanup( $text ) {
		if ( ! is_string( $text ) ) {
			throw new Exception( 'Expected a String, but received ' . gettype( $text ) );
		}

		// trim text
		$text = trim( $text );

		// don't bother if its empty or whitespace
		if ( empty( $text ) ) {
			return $text;
		}

		$options = $this->getOptions();

		// preserves front matter data in the text
		if ( $options["preserve_front_matter"] ) {
			$front_matter = array();
			$text         = preg_replace_callback( '/^ ---[\S\s]*?---\n/', function ( $matched ) use ( $front_matter ) {
				$front_matter[] = $matched;

				return ' __FRONT__MATTER__PRESERVER__ ';
			}, $text );
		}

		// preserves all html tags in the text
		// @props: @wordpress/wordcount
		if ( $options["preserve_HTML"] ) {
			$html = array();
			$text = preg_replace_callback( '/<\/?[a-z][^>]*?>/i', function ( $matched ) use ( $html ) {
				$html[] = $matched;

				return ' __HTML__PRESERVER__ ';
			}, $text );
		}

		// preserves all html comments in the text
		// @props: @wordpress/wordcount
		if ( $options["preserve_comments"] ) {
			$comments = array();
			$text     = preg_replace_callback( '/<!--[\s\S]*?-->/', function ( $matched ) use ( $comments ) {
				$comments[] = $matched;

				return ' __COMMENT__PRESERVER__ ';
			}, $text );
		}

		// preserves strings inside square brackets (`[]`)
		if ( $options["preserve_brackets"] ) {
			$brackets = array();
			$text     = preg_replace_callback( '/(\[.*?\])/', function ( $matched ) use ( $brackets ) {
				$brackets[] = $matched;

				return ' __BRACKETS__PRESERVER__ ';
			}, $text );
		}

		// preserve strings inside curly braces (`{}`)
		if ( $options["preserve_braces"] ) {
			$braces = array();
			$text   = preg_replace_callback( '/(\{.*?\})/', function ( $matched ) use ( $braces ) {
				$braces[] = $matched;

				return ' __BRACES__PRESERVER__ ';
			}, $text );
		}

		// preserves all uri strings in the text
		if ( $options["preserve_URIs"] ) {
			$md_links = array();
			$uris     = array();

			// stores Markdown links separately
			$text = preg_replace_callback( '/]\((.*?)\)/', function ( $matched ) use ( $md_links ) {
				if ( is_string( $matched ) ) {
					$matched = [ $matched ];
				}
				if ( isset( $matched[1] ) ) {
					$md_links[] = trim( $matched[1] );

					return '](__MD_LINK__PRESERVER__)'; // no padding!
				}

				return $matched[0];
			}, $text );

			$text = preg_replace_callback( $this->patternURI, function ( $matched ) use ( $uris ) {
				$uris[] = $matched;

				return ' __URI__PRESERVER__ ';
			}, $text );
		}

		// preserves all no-break space entities in the text
		if ( $options["preserve_nbsp"] ) {
			$nbsps = array();
			$text  = preg_replace_callback( '/&nbsp;|&#160;/iu', function ( $matched ) use ( $nbsps ) {
				$nbsps[] = $matched;

				return ' __NBSPS__PRESERVER__ ';
			}, $text );
		}

		if ( $options["decode_html_entities"] ) {
			$text = $this->decodeHTMLEntities( $text );
		}

		// preserves all html entities in the text
		// @props: @substack/node-ent
		if ( $options["preserve_entities"] ) {
			$entities = array();
			$text     = preg_replace_callback( '/&(#?[^;\W]+;?)/', function ( $matched ) use ( $entities ) {
				$entities[] = $matched;

				return ' __ENTITIES__PRESERVER__ ';
			}, $text );
		}

		if ( $options["normalize_eol"] ) {
			$text = $this->normalizeEOL( $text );
		}

		if ( $options["fix_persian_glyphs"] ) {
			$text = $this->fixPersianGlyphs( $text );
		}

		if ( $options["fix_dashes"] ) {
			$text = $this->fixDashes( $text );
		}

		if ( $options["fix_three_dots"] ) {
			$text = $this->fixThreeDots( $text );
		}

		if ( $options["normalize_ellipsis"] ) {
			$text = $this->normalizeEllipsis( $text );
		}

		if ( $options["fix_english_quotes_pairs"] ) {
			$text = $this->fixEnglishQuotesPairs( $text );
		}

		if ( $options["fix_english_quotes"] ) {
			$text = $this->fixEnglishQuotes( $text );
		}

		if ( $options["fix_hamzeh"] ) {
			if ( $options["fix_hamzeh_arabic"] ) {
				$text = $this->fixHamzehArabic( $text );
			}
			$text = $this->fixHamzeh( $text );
		} else if ( $options["fix_suffix_spacing"] ) {
			if ( $options["fix_hamzeh_arabic"] ) {
				$text = $this->fixHamzehArabicAlt( $text );
			}
			$text = $this->fixSuffixSpacingHamzeh( $text );
		}

		if ( $options["cleanup_rlm"] ) {
			$text = $this->cleanupRLM( $text );
		}

		if ( $options["cleanup_zwnj"] ) {
			$text = $this->cleanupZWNJ( $text );
		}

		if ( $options["fix_arabic_numbers"] ) {
			$text = $this->fixArabicNumbers( $text );
		}

		// word tokenizer
		$text = preg_replace_callback( '/(^|\s+)([[({"\'“«]?)(\S+)([\])}"\'”»]?)(?=($|\s+))/', function ( $matches ) use ( $options ) {
			$matched = isset( $matches[0] ) ? $matches[0] : '';
			// $before = $matches[1] ?? '';
			// $leading = $matches[2] ?? '';
			$word     = isset( $matches[3] ) ? $matches[3] : '';
			$trailing = isset( $matches[4] ) ? $matches[4] : '';
			$after    = isset( $matches[5] ) ? $matches[5] : '';

			// should not replace to persian chars in english phrases
			preg_match( '/[a-zA-Z\-_]{2,}/u', $word, $word_match );
			if ( $word_match ) {
				return $matched;
			}

			// should not touch sprintf directives
			unset( $word_match );
			preg_match_all( "/%(?:\d+\$)?[+-]?(?:[ 0]|'.{1})?-?\d*(?:\.\d+)?[bcdeEufFgGosxX]/u", $word, $word_match );
			if ( $word_match && ( isset( $word_match[0] ) && ! empty( $word_match[0] ) ) ) {
				return $matched;
			}

			// should not touch numbers in html entities
			unset( $word_match );
			preg_match( '/&#\d+;/u', $word, $word_match );
			if ( $word_match ) {
				return $matched;
			}

			// skips converting english numbers of ordered lists in markdown
			unset( $word_match );
			preg_match( '/(?:(?:\r?\n)|(?:\r\n?)|(?:^|\n))\d+\.\s/', $matched . $trailing . $after, $word_match );
			if ( $options["skip_markdown_ordered_lists_numbers_conversion"] && $word_match ) {
				return $matched;
			}

			if ( $options["fix_english_numbers"] ) {
				$matched = $this->fixEnglishNumbers( $matched );
			}

			if ( $options["fix_numeral_symbols"] ) {
				$matched = $this->fixNumeralSymbols( $matched );
			}

			if ( $options["fix_punctuations"] ) {
				$matched = $this->fixPunctuations( $matched );
			}

			if ( $options["fix_misc_non_persian_chars"] ) {
				$matched = $this->fixMiscNonPersianChars( $matched );
			}

			if ( $options["fix_question_mark"] ) {
				$matched = $this->fixQuestionMark( $matched );
			}

			return $matched;
		}, $text );

		if ( $options["normalize_dates"] ) {
			$text = $this->normalizeDates( $text );
		}

		if ( $options["fix_prefix_spacing"] ) {
			$text = $this->fixPrefixSpacing( $text );
		}

		if ( $options["fix_suffix_spacing"] ) {
			$text = $this->fixSuffixSpacing( $text );
		}

		if ( $options["fix_suffix_misc"] ) {
			$text = $this->fixSuffixMisc( $text );
		}

		if ( $options["fix_spacing_for_braces_and_quotes"] ) {
			$text = $this->fixBracesSpacing( $text );
		}

		if ( $options["cleanup_extra_marks"] ) {
			$text = $this->cleanupExtraMarks( $text );
		}

		if ( $options["fix_spacing_for_punctuations"] ) {
			$text = $this->fixPunctuationSpacing( $text );
		}

		if ( $options["kashidas_as_parenthetic"] ) {
			$text = $this->kashidasAsParenthetic( $text );
		}

		if ( $options["cleanup_kashidas"] ) {
			$text = $this->cleanupKashidas( $text );
		}

		if ( $options["markdown_normalize_braces"] ) {
			$text = $this->markdownNormalizeBraces( $text );
		}

		if ( $options["markdown_normalize_lists"] ) {
			$text = $this->markdownNormalizeLists( $text );
		}

		// doing it again after `fixPunctuationSpacing()`
		if ( $options["fix_spacing_for_braces_and_quotes"] ) {
			$text = $this->fixBracesSpacingInside( $text );
		}

		if ( $options["fix_misc_spacing"] ) {
			$text = $this->fixMiscSpacing( $text );
		}

		if ( $options["remove_diacritics"] ) {
			$text = $this->removeDiacritics( $text );
		} else if ( $options["fix_diacritics"] ) {
			$text = $this->fixDiacritics( $text );
		}

		if ( $options["cleanup_spacing"] ) {
			$text = $this->cleanupSpacing( $text );
		}

		if ( $options["cleanup_zwnj"] ) {
			$text = $this->cleanupZWNJLate( $text );
		}

		if ( $options["cleanup_line_breaks"] ) {
			$text = $this->cleanupLineBreaks( $text );
		}

		// bringing back entities
		if ( $options["preserve_entities"] ) {
			$entities_array = $this->entities;
			$text           = preg_replace_callback( '/[ ]?__ENTITIES__PRESERVER__[ ]?/', function () use ( $entities_array ) {
				return array_shift( $entities_array );
			}, $text );
		}

		// bringing back nbsp
		if ( $options["preserve_nbsp"] ) {
			$nbsps = isset( $nbsps ) ? $nbsps : array();
			$text  = preg_replace_callback( '/[ ]?__NBSPS__PRESERVER__[ ]?/', function () use ( $nbsps ) {
				return array_shift( $nbsps );
			}, $text );
		}

		// bringing back URIs
		if ( $options["preserve_URIs"] ) {
			$md_links = isset( $md_links ) ? $md_links : array();
			// no padding!
			$text = preg_replace_callback( '/__MD_LINK__PRESERVER__/', function () use ( $md_links ) {
				return array_shift( $md_links );
			}, $text );

			$uris = isset( $uris ) ? $uris : array();
			$text = preg_replace_callback( '/[ ]?__URI__PRESERVER__[ ]?/', function () use ( $uris ) {
				return array_shift( $uris );
			}, $text );
		}

		// bringing back braces
		if ( $options["preserve_braces"] ) {
			$braces = isset( $braces ) ? $braces : array();
			$text   = preg_replace_callback( '/[ ]?__BRACES__PRESERVER__[ ]?/', function () use ( $braces ) {
				return array_shift( $braces );
			}, $text );
		}

		// bringing back brackets
		if ( $options["preserve_brackets"] ) {
			$brackets = isset( $brackets ) ? $brackets : array();
			$text     = preg_replace_callback( '/[ ]?__BRACKETS__PRESERVER__[ ]?/', function () use ( $brackets ) {
				return array_shift( $brackets );
			}, $text );
		}

		// bringing back HTML comments
		if ( $options["preserve_comments"] ) {
			$comments = isset( $comments ) ? $comments : array();
			$text     = preg_replace_callback( '/[ ]?__COMMENT__PRESERVER__[ ]?/', function () use ( $comments ) {
				return array_shift( $comments );
			}, $text );
		}

		// bringing back HTML tags
		if ( $options["preserve_HTML"] ) {
			$html = isset( $html ) ? $html : array();
			$text = preg_replace_callback( '/[ ]?__HTML__PRESERVER__[ ]?/', function () use ( $html ) {
				return array_shift( $html );
			}, $text );
		}

		// bringing back front matter
		if ( $options["preserve_front_matter"] ) {
			$front_matter = isset( $front_matter ) ? $front_matter : array();
			$text         = preg_replace_callback( '/[ ]?__FRONT__MATTER__PRESERVER__[ ]?/', function () use ( $front_matter ) {
				return array_shift( $front_matter );
			}, $text );
		}

		if ( $options["cleanup_begin_and_end"] ) {
			$text = $this->cleanupBeginAndEnd( $text );
		} else {
			// removes single space paddings around the string
			$text = preg_replace( '/^[ ]/', '', preg_replace( '/[ ]$/', '', $text ) );
		}

		return $text;
	}

	protected function cleanupZWNJ( $text ) {
		// converts all soft hyphens (&shy;) into zwnj
		return preg_replace( '/\x{00ad}/u', '\x{200c}',
			// removes more than one zwnj
			preg_replace( '/\x{200c}{2,}/u', '\x{200c}',
				// cleans zwnj before and after numbers, english words, spaces and punctuations
				// preg_replace('~\x{200c}([\w\s0-9۰-۹[\](){}«»“”.…,:;?!$%@#*=+\-/\\،؛٫٬×٪؟ـ])~u', '$1', // \w is for any english word character in javascript, but it supports words in any language in php
				preg_replace( '~\x{200c}([\s0-9۰-۹[\](){}«»“”.…,:;?!$%@#*=+\-/\\،؛٫٬×٪؟ـ])~u', '$1',
					// preg_replace('~([\w\s0-9۰-۹[\](){}«»“”.…,:;?!$%@#*=+\-/\\،؛٫٬×٪؟ـ])\x{200c}~u', '$1', // \w is for any english word character in javascript, but it supports words in any language in php
					preg_replace( '~([\s0-9۰-۹[\](){}«»“”.…,:;?!$%@#*=+\-/\\،؛٫٬×٪؟ـ])\x{200c}~u', '$1',
						// removes unnecessary zwnj on start/end of each line
						preg_replace( '/(^\x{200c}|\x{200c}$)/um', '', $text )
					)
				)
			)
		);
	}

	// late checks for zwnj
	protected function cleanupZWNJLate( $text ) {
		// cleans zwnj after characters that don't connect to the next
		return preg_replace( '/([إأةؤورزژاآدذ،؛,:«»\\/@#$٪×*()ـ\-=|])\x{200c}/u', '$1', $text );
	}

	protected function charReplace( $text, $fromBatch, $toBatch ) {
		$fromChars = mb_str_split( $fromBatch );
		$toChars   = mb_str_split( $toBatch );
		foreach ( $fromChars as $key => $value ) {
			$text = preg_replace( "~" . $value . "~u", isset( $toChars[ $key ] ) ? $toChars[ $key ] : '', $text );
		}

		return $text;
	}

	protected function arrReplace( $text, $array ) {
		foreach ( $array as $key => $item ) {
			$text = preg_replace( '/[' . $item . ']/u', $key, $text );
		}

		return $text;
	}

	protected function normalizeEOL( $text ) {
		// replace windows end of lines with unix eol (`\n`)
		return preg_replace( '/(\r?\n)|(\r\n?)/u', "\n", $text ); // Replace windows end of lines with unix eol (`\n`)
	}

	protected function fixDashes( $text ) {
		// replaces triple dash to mdash
		// replaces double dash to ndash
		return preg_replace( '/-{2}/', '–', preg_replace( '/-{3}/', '—', $text ) );
	}

	protected function fixThreeDots( $text ) {
		// remove spaces between dots
		// replaces three dots with ellipsis character
		return preg_replace( '/\.([ ]+)(?=[.])/', '.', preg_replace( '/[ \t]*\.{3,}/', '…', $text ) );
	}

	protected function normalizeEllipsis( $text ) {
		// replaces more than one ellipsis with one
		// replaces (space|tab|zwnj) after ellipsis with one space
		// NOTE: allows for space before ellipsis
		return preg_replace( '/(…){2,}/', '…',
			preg_replace( '/([ ]{1,})*…[ \t\x{200c}]*/u', '$1… ', $text )
		);
	}

	protected function fixEnglishQuotesPairs( $text ) {
		// replaces english quote pairs with their persian equivalent
		return preg_replace( '/(“)(.+?)(”)/', '«$2»', $text );
	}

	// replaces english quote marks with their persian equivalent
	protected function fixEnglishQuotes( $text ) {
		return preg_replace( '/(["\'`]+)(.+?)(\1)/', '«$2»', $text );
	}

	protected function fixHamzeh( $text ) {
		$replacement = '$1هٔ$3';

		// replaces ه followed by (space|ZWNJ|lrm) follow by ی with هٔ
		return preg_replace( '/(\S)(ه[\s\x{200c}\x{200e}]+[یي])([\s\x{200c}\x{200e}])/u', $replacement,  // heh + ye
			// replaces ه followed by (space|ZWNJ|lrm|nothing) follow by ء with هٔ
			preg_replace( '/(\S)(ه[\s\x{200c}\x{200e}]?\x{0621})([\s\x{200c}\x{200e}])/u', $replacement, // heh + standalone hamza
				// replaces هٓ or single-character ۀ with the standard هٔ
				preg_replace( '/(ۀ|هٓ)/u', 'هٔ', $text )
			)
		);
	}

	protected function fixHamzehArabic( $text ) {
		// converts arabic hamzeh ة to هٔ
		return preg_replace( '/(\S)ة([\s\x{200c}\x{200e}])/u', '$1هٔ$2', $text );
	}

	protected function fixHamzehArabicAlt( $text ) {
		// converts arabic hamzeh ة to ه‌ی
		return preg_replace( '/(\S)ة([\s\x{200c}\x{200e}])/u', '$1ه‌ی$2', $text );
	}

	protected function cleanupRLM( $text ) {
		// converts Right-to-left marks followed by persian characters to
		// zero-width non-joiners (ZWNJ)
		return preg_replace( '/([^a-zA-Z\-_])(\x{200f})/u', '$1\x{200c}', $text );
	}

	// converts incorrect persian glyphs to standard characters
	protected function fixPersianGlyphs( $text ) {
		return $this->arrReplace( $text, $this->glyphs );
	}

	protected function fixMiscNonPersianChars( $text ) {
		return $this->charReplace( $text, 'كڪيىۍېہە', 'ککییییههه' );
	}

	// replaces english numbers with their persian equivalent
	protected function fixEnglishNumbers( $text ) {
		return $this->charReplace( $text, '1234567890', $this->digits );
	}

	// replaces arabic numbers with their persian equivalent
	protected function fixArabicNumbers( $text ) {
		return $this->charReplace( $text, '١٢٣٤٥٦٧٨٩٠', $this->digits );
	}

	// @REF: https://github.com/shkarimpour/pholiday/pull/5/files
	protected function convertPersianNumbers( $text ) {
		return preg_replace_callback( '/[\x{0660}-\x{0669}\x{06f0}-\x{06f9}]/', function ( $matched ) {
			return ord( isset( $matched[0][0] ) ? $matched[0][0] : '' ) & 0xf;
		}, $text );
	}

	protected function fixNumeralSymbols( $text ) {
		// replaces english percent signs (U+066A)
		return preg_replace( '/([۰-۹]) ?%/', '$1٪',
			// replaces dots between numbers into decimal separator (U+066B)
			preg_replace( '/([۰-۹])\.(?=[۰-۹])/', '$1٫',
				// replaces commas between numbers into thousands separator (U+066C)
				preg_replace( '/([۰-۹]),(?=[۰-۹])/', '$1٬', $text )
			)
		);
	}

	protected function normalizeDates( $text ) {
		// re-orders date parts with slash as delimiter
		return preg_replace_callback( '#([0-9۰-۹]{1,2})([/-])([0-9۰-۹]{1,2})\2([0-9۰-۹]{4})#', function ( $matched ) {
			$day = isset( $matched[1] ) ? $matched[1] : '';
			// $delimiter = $matched[2] ?? '';
			$month = isset( $matched[3] ) ? $matched[3] : '';
			$year  = isset( $matched[4] ) ? $matched[4] : '';

			return $year . '/' . $month . '/' . $day;
		}, $text );
	}

	protected function fixPunctuations( $text ) {
		return $this->charReplace( $text, '٬,;', '،،؛' );
	}

	// replaces question marks with its persian equivalent
	protected function fixQuestionMark( $text ) {
		return preg_replace( '/(\?)/', '\x{061F}', $text ); // \x{061F} = ؟
	}

	// puts zwnj between the word and the prefix:
	// - mi* nemi* bi*
	// NOTE: there's a possible bug here: prefixes could be separate nouns
	protected function fixPrefixSpacing( $text ) {
		$replacement = "$1\u{200c}$3";

		return preg_replace( '/((\s|^)ن?می) ([^ ])/u', $replacement, preg_replace( '/((\s|^)بی) ([^ ])/u', $replacement, $text ) );
	}

	// puts zwnj between the word and the suffix
	// NOTE: possible bug: suffixes could be nouns
	protected function fixSuffixSpacing( $text ) {
		$replacement = "$1\u{200c}$2";
		// must be done before others
		// *ha *haye
		return preg_replace( '#([' . $this->charsPersian . $this->charsDiacritic . ']) (ها(ی)?[' . $this->patternAfter . '])#u', $replacement,
			// *am *at *ash *ei *eid *eem *and *man *tan *shan
			preg_replace( '#([' . $this->charsPersian . $this->charsDiacritic . ']) ((ام|ات|اش|ای|اید|ایم|اند|مان|تان|شان)[' . $this->patternAfter . '])#u', $replacement,
				// *tar *tari *tarin
				preg_replace( '#([' . $this->charsPersian . $this->charsDiacritic . ']) (تر((ی)|(ین))?[' . $this->patternAfter . '])#u', $replacement,
					// *hayee *hayam *hayat *hayash *hayetan *hayeman *hayeshan
					preg_replace( '#([' . $this->charsPersian . $this->charsDiacritic . ']) ((هایی|هایم|هایت|هایش|هایمان|هایتان|هایشان)[' . $this->patternAfter . '])#u', $replacement, $text )
				)
			)
		);
	}

	protected function fixSuffixSpacingHamzeh( $text ) {
		$replacement = '$1\x{0647}\x{200c}\x{06cc}$3';

		// heh + ye
		return preg_replace( '/(\S)(ه[\s\x{200c}]+[یي])([\s\x{200c}])/u', $replacement,
			// heh + standalone hamza
			preg_replace( '/(\S)(ه[\s\x{200c}]?\x{0621})([\s\x{200c}])/u', $replacement,
				// heh + hamza above
				preg_replace( '/(\S)(ه[\s\x{200c}]?\x{0654})([\s\x{200c}])/u', $replacement, $text )
			)
		);
	}

	protected function fixSuffixMisc( $text ) {
		// replaces ه followed by ئ or ی, and then by ی, with ه\x{200c}ای,
		// EXAMPLE: خانه‌ئی becomes خانه‌ای
		// preg_replace('/(\S)ه[\x{200c}\x{200e}][ئی]ی([\s\x{200c}\x{200e}])/u', "$1ه\u{200c}ای$2", $text);
		return preg_replace( '/(\S)ه[\x{200c}\x{200e}][ئی]ی/u', "$1ه\u{200c}ای$2", $text );
	}

	protected function cleanupExtraMarks( $text ) {
		// removes space between different/same marks (combining for cleanup)
		return preg_replace( '#([؟?!])([ ]+)(?=[؟?!])#', '$1',
			// replaces more than one exclamation mark with just one
			preg_replace( '/(!){2,}/u', '$1',
				// replaces more than one english or persian question mark with just one
				preg_replace( '/(\x{061F}|\?){2,}/u', '$1', // \x{061F} = `؟`
					// re-orders consecutive marks
					preg_replace( '/(!)([ \t]*)([\x{061F}?])/u', '$3$1', $text ) // `?!` --> `!?`
				)
			)
		);
	}

	// replaces kashidas to ndash in parenthetic
	protected function kashidasAsParenthetic( $text ) {
		return preg_replace( '/(\s)\x{0640}+/u', '$1–', preg_replace( '/\x{0640}+(\s)/u', '–$1', $text ) );
	}

	protected function cleanupKashidas( $text ) {
		// converts kashida between numbers to ndash
		return preg_replace( '/([0-9۰-۹]+)ـ+([0-9۰-۹]+)/u', '$1–$2',
			// removes all kashidas between non-whitespace characters
			// MAYBE: more punctuations
			preg_replace( '/([^\s.])\x{0640}+(?![\s.])/u', '$1', $text )
		);
	}

	protected function fixPunctuationSpacing( $text ) {
		// removes space before punctuations
		return preg_replace( '/[ \t\x{200c}]*([:;,؛،.؟?!]{1})/u', '$1',
			// removes more than one space after punctuations
			// except followed by new-lines (or preservers)
			preg_replace( '/([:;,؛،.؟?!]{1})[ \t\x{200c}]*(?!\n|_{2})/u', '$1 ',
				// removes space after colon that separates time parts
				preg_replace( '/([0-9۰-۹]+):\s+([0-9۰-۹]+)/', '$1:$2',
					// removes space after dots in numbers
					preg_replace( '/([0-9۰-۹]+)\. ([0-9۰-۹]+)/', '$1.$2',
						// removes space before common domain tlds
						preg_replace( '~([\w\-_]+)\. (ir|com|org|net|info|edu|me)([\s/\])»:;.])~', '$1.$2$3',
							// removes space between different/same marks (double-check)
							preg_replace( '/([؟?!])([ ]+)(?=[؟?!])/', '$1', $text )
						)
					)
				)
			)
		);
	}

	protected function fixBracesSpacing( $text ) {
		$replacement = ' $1$2$3 ';
		// removes inside spaces and more than one outside
		// for `()`, `[]`, `{}`, `“”` and `«»`
		return preg_replace( "/[ \t\x{200c}]*(\()\s*([^)]+?)\s*?(\))[ \t\x{200c}]*/u", $replacement,
			preg_replace( "/[ \t\x{200c}]*(\[)\s*([^\]]+?)\s*?(\])[ \t\x{200c}]*/u", $replacement,
				preg_replace( "/[ \t\x{200c}]*(\{)\s*([^}]+?)\s*?(\})[ \t\x{200c}]*/u", $replacement,
					preg_replace( "/[ \t\x{200c}]*(“)\s*([^”]+?)\s*?(”)[ \t\x{200c}]*/u", $replacement,
						preg_replace( "/[ \t\x{200c}]*(«)\s*([^»]+?)\s*?(»)[ \t\x{200c}]*/u", $replacement, $text )
					)
				)
			)
		);
	}

	protected function fixBracesSpacingInside( $text ) {
		$replacement = '$1$2$3';

		// removes inside spaces for `()`, `[]`, `{}`, `“”` and `«»`
		return preg_replace( "/(\()\s*([^)]+?)\s*?(\))/u", $replacement,
			preg_replace( "/(\[)\s*([^\]]+?)\s*?(\])/u", $replacement,
				preg_replace( "/(\{)\s*([^}]+?)\s*?(\})/u", $replacement,
					preg_replace( "/(“)\s*([^”]+?)\s*?(”)/u", $replacement,
						preg_replace( "/(«)\s*([^»]+?)\s*?(»)/u", $replacement,
							// NOTE: must be here, weird not working if on `markdownNormalizeBraces()`
							// removes Markdown link spaces inside normal ()
							preg_replace( "/(\(\[.*?\]\(.*?\))\s+(\))/u", '$1$2', $text )
						)
					)
				)
			)
		);
	}

	protected function markdownNormalizeBraces( $text ) {
		// removes space between ! and opening brace on markdown images
		// EXAMPLE: `! [alt] (src)` --> `![alt](src)`
		return preg_replace( "/! (\[.*?\])[ ]?(\(.*?\))[ ]?/", '!$1$2',
			// remove spaces between [] and ()
			// EXAMPLE: `[text] (link)` --> `[text](link)`
			preg_replace( "/(\[.*?\])[ \t]+(\(.*?\))/", '$1$2',
				// removes spaces inside double () [] {}
				// EXAMPLE: `[[ text ]]` --> `[[text]]`
				preg_replace( "/\(\([ \t]*(.*?)[ \t]*\)\)/", '(($1))',
					preg_replace( "/\[\[[ \t]*(.*?)[ \t]*\]\]/", '[[$1]]',
						preg_replace( "/\{\{[ \t]*(.*?)[ \t]*\}\}/", '{{$1}}',
							preg_replace( "/\{\{\{[ \t]*(.*?)[ \t]*\}\}\}/", '{{{$1}}}', // mustache escape
								// removes spaces between double () [] {}
								// EXAMPLE: `[[text] ]` --> `[[text]]`
								preg_replace( "/(\(\(.*\))[ \t]+(\))/", '$1$2',
									preg_replace( "/(\[\[.*\])[ \t]+(\])/", '$1$2',
										preg_replace( "/(\{\{.*\})[ \t]+(\})/", '$1$2', $text )
									)
								)
							)
						)
					)
				)
			)
		);
	}

	protected function markdownNormalizeLists( $text ) {
		// removes extra line between two items list
		return preg_replace( '/((\n|^)\*.*?)\n+(?=\n\*)/', '$1',
			preg_replace( '/((\n|^)-.*?)\n+(?=\n-)/', '$1',
				preg_replace( '/((\n|^)#.*?)\n+(?=\n#)/', '$1', $text )
			)
		);
	}

	protected function fixMiscSpacing( $text ) {
		// removes space before parentheses on misc cases
		return preg_replace( '/ \((ص|عج|س|ع|ره)\)/u', '($1)',
			// removes space before braces containing numbers
			preg_replace( '/ \[([0-9۰-۹]+)\]/u', '[$1]', $text )
		);
	}

	protected function fixDiacritics( $text ) {
		// cleans zwnj before diacritic characters
		return preg_replace( '#\x{200c}([' . $this->charsDiacritic . '])#u', '$1',
			// cleans more than one diacritic characters
			// props @languagetool-org
			preg_replace( '#(.*)([' . $this->charsDiacritic . ']){2,}(.*)#u', '$1$2$3',
				// clean spaces before diacritic characters
				preg_replace( '#(\\S)[ ]+([' . $this->charsDiacritic . '])#u', '$1$2', $text )
			)
		);
	}

	protected function removeDiacritics( $text ) {
		// removes all diacritic characters
		return preg_replace( '/[' . $this->charsDiacritic . ']+/u', '', $text );
	}

	protected function cleanupSpacing( $text ) {
		// replaces more than one space with just a single one
		// except before/after preservers and before new-lines
		// .replace(/(?<![_]{2})([ ]{2,})(?![_]{2}|\n)/g, ' ') // WORKS: using lookbehind
		return preg_replace( '/([^_])([ ]{2,})(?![_]{2}|\n)/u', '$1 ',
			// cleans whitespace/zwnj between new-lines
			// @REF: https://stackoverflow.com/a/10965543/
			preg_replace( '/\n[\s\x{200c}]*\n/u', "\n\n", $text )
		);
	}

	protected function cleanupLineBreaks( $text ) {
		// cleans more than two contiguous line-breaks
		return preg_replace( '/\n{2,}/u', "\n\n", $text );
	}

	protected function cleanupBeginAndEnd( $text ) {
		// removes space/tab/zwnj/nbsp from the beginning of the new-lines
		return preg_replace( '/([\n]+)[ \t\x{200c}\x{00a0}]*/u', '$1',
			// remove spaces, tabs, zwnj, direction marks and new lines from
			// the beginning and end of text
			// @REF: http://stackoverflow.com/a/38490203
			preg_replace( '/^[\s\x{200c}\x{200e}\x{200f}]+|[\s\x{200c}\x{200e}\x{200f}]+$/u', '', $text )
		);
	}

	protected function flipPunctuations( $text ) {
		$end    = [ '-' ];
		$start  = [ '!', '.', '،', '…', '"' ];
		$before = [];
		$after  = [];

		$text    = $this->fixThreeDots( $text );
		$trimmed = trim( $text );

		$countOfStart = count( $start );
		for ( $iStart = 0; $iStart < $countOfStart; $iStart ++ ) {
			$sElement = $start[ $iStart ];
			$sReg     = '^\\' . $sElement . '/i';
			if ( preg_match( $sReg, $text ) ) {
				$text    = preg_replace( $sReg, '', $trimmed );
				$after[] = $sElement;
			}
		}

		$countOfEnd = count( $end );
		for ( $iEnd = 0; $iEnd < $countOfEnd; $iEnd ++ ) {
			$eElement = $end[ $iEnd ];
			$eReg     = '\\' . $eElement . '$/i';
			if ( preg_match( $eReg, $text ) ) {
				$text     = preg_replace( $eReg, '', $trimmed );
				$before[] = $eElement;
			}
		}

		$countOfBefore = count( $before );
		for ( $iBefore = 0; $iBefore < $countOfBefore; $iBefore ++ ) {
			$text = $before[ $iBefore ] . ' ' . $text;
		}

		$countOfAfter = count( $after );
		for ( $iAfter = 0; $iAfter < $countOfAfter; $iAfter ++ ) {
			$text += $after[ $iAfter ];
		}

		return $this->normalizeEllipsis( $text );
	}

	// swap incorrect quotes pairs `»«` to `«»` and `”“` to `“”`
	protected function swapQuotes( $text ) {
		return preg_replace( '/(»)(.+?)(«)/', '«$2»', preg_replace( '/(”)(.+?)(“)/', '“$2”', $text ) );
	}
}