<?php
if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'NetworkAuth' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['NetworkAuth'] = __DIR__ . '/i18n';
	wfWarn(
		'Deprecated PHP entry point used for the NetworkAuth extension. ' .
		'Please use wfLoadExtension instead, ' .
		'see https://www.mediawiki.org/wiki/Extension_registration for more details.'
	);
	return;
} else {
	die( 'This version of the NetworkAuth extension requires MediaWiki 1.34+' );
}
