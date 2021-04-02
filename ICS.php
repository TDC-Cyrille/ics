<?php

/**
 * ICS.php
 * =======
 * Use this class to create an .ics file.
 *
 * Usage
 * -----
 * Basic usage - generate ics file contents (see below for available properties):
 *   $ics = new ICS($props);
 *   $ics_file_contents = $ics->to_string();
 *
 * Setting properties after instantiation
 *   $ics = new ICS();
 *   $ics->set('summary', 'My awesome event');
 *
 * You can also set multiple properties at the same time by using an array:
 *   $ics->set(array(
 *     'dtstart' => 'now + 30 minutes',
 *     'dtend' => 'now + 1 hour'
 *   ));
 *
 * Available properties
 * --------------------
 * description
 *   String description of the event.
 * dtend
 *   A date/time stamp designating the end of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * dtstart
 *   A date/time stamp designating the start of the event. You can use either a
 *   DateTime object or a PHP datetime format string (e.g. "now + 1 hour").
 * location
 *   String address or description of the location of the event.
 * summary
 *   String short summary of the event - usually used as the title.
 * url
 *   A url to attach to the the event. Make sure to add the protocol (http://
 *   or https://).
 */

	class ICS {

		const DT_FORMAT = 'Ymd\THis';

		protected $properties = array();
		private $available_properties = array(
			'description',
			'dtend',
			'dtstart',
			'location',
			'summary',
			'url'
		);

		public function __construct( $props ) {
			
			$this->set( $props );
			
		}

		public function set( $key, $val = false ) {
			
			if( is_array( $key ) )
				foreach( $key as $k => $v )
					$this->set( $k, $v );
			else
				if( in_array( $key, $this->available_properties ) )
					$this->properties[$key] = $this->sanitize_val( $val, $key );
			
		}

		public function to_string() {
			
			$rows = $this->build_props();
			
			return implode( PHP_EOL, $rows );
		
		}

		private function build_props() {

			$ics_props = array(
				'BEGIN:VCALENDAR',
				'VERSION:2.0',
				'PRODID:-//hacksw/handcal//NONSGML v1.0//EN',
				'CALSCALE:GREGORIAN',
				'BEGIN:VTIMEZONE',
				'TZID:Asia/Singapore',
				'TZURL:https://tzurl.org/zoneinfo-outlook/Asia/Singapore',
				'X-LIC-LOCATION:Asia/Singapore',
				'BEGIN:STANDARD',
				'TZOFFSETFROM:+0800',
				'TZOFFSETTO:+0800',
				'TZNAME:CST',
				'DTSTART:19700101T000000',
				'END:STANDARD',
				'END:VTIMEZONE',
				'BEGIN:VEVENT'
			);

			$props = array();
			foreach( $this->properties as $k => $v ) {
				
				switch( $k ) {
					
					case 'url':
					
						$props[strtoupper( $k.';VALUE=URI' )] = $v;
						
						break;
					
					case 'dtstart':
					case 'dtend':
					
						$props[strtoupper( $k ).';TZID=Asia/Singapore'] = $v;
						
						break;
					
					default:
					
						$props[strtoupper( $k )] = $v;
					
						break;
					
				}
				
			}

			$props['DTSTAMP'] = $this->format_timestamp( 'now' );
			$props['UID'] = uniqid();

			foreach( $props as $k => $v )
				$ics_props[] = "$k:$v";
				
			$ics_props[] = 'BEGIN:VALARM';
			$ics_props[] = 'ACTION:DISPLAY';
			$ics_props[] = 'DESCRIPTION:'.$this->properties['summary'];
			$ics_props[] = 'TRIGGER:-PT15M';
			$ics_props[] = 'END:VALARM';
			$ics_props[] = 'END:VEVENT';
			$ics_props[] = 'END:VCALENDAR';

			return $ics_props;
			
		}

		private function sanitize_val( $val, $key = false ) {
			
			switch( $key ) {
				
				case 'dtend':
				case 'dtstamp':
				case 'dtstart':
				
					$val = $this->format_timestamp($val);
					
					break;
					
				default:
				
					$val = $this->escape_string($val);
					
					break;
				
			}

			return $val;
			
		}

		private function format_timestamp( $timestamp ) {

			$dt = new DateTime( $timestamp );

			return $dt->format( self::DT_FORMAT );

		}

		private function escape_string( $str ) {

			$str = str_replace( '&nbsp;', ' ', $str );
			$str = str_replace( array( '<br>', '<br />', '<br/>' ), '\n', $str );
			
			return preg_replace( '/([\,;])/','\\\$1', $str );

		}
	  
	}

?>
