<?php
namespace B2TBadges\utilities;

function human_filesize( $bytes, $decimals = 2 ) {
  $size   = [ 'B', 'KB', 'MB', 'GB', 'TB' ];
  $factor = floor( ( strlen( $bytes ) - 1 ) / 3 );
  return sprintf( "%.{$decimals}f", $bytes / pow( 1024, $factor ) ) . ' ' . $size[ $factor ];
}