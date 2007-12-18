<?php
/** \file
* \brief Contains code for the NetworkAuth Extension.
*/

# Not a valid entry point, skip unless MEDIAWIKI is defined
if (!defined('MEDIAWIKI')) {
	echo "NetworkAuth extension";
	exit(1);
}

$wgNetworkAuthUsers[] = array();

$wgExtensionCredits['other'][] = array(
	'name'        => 'NetworkAuth',
	'version'     => '1.0',
	'author'      => 'Tim Laqua',
	'description' => 'Allows you to authenticate users based on network information',
	'url'         => 'http://www.mediawiki.org/wiki/Extension:NetworkAuth',
);

$wgExtensionFunctions[] = 'efNetworkAuth_Setup';

function efNetworkAuth_Setup() {
        global $wgRequest;

		# $wgTitle isn't initalized yet - but we need to know where we are
		$pageTitle = Title::newFromURL( $wgRequest->getVal('title') );
		
		if (is_object($pageTitle)) {
			# Doesn't apply to Userlogin and Userlogout pages - that breaks stuff
			if (!$pageTitle->isSpecial('Userlogin') && !$pageTitle->isSpecial('Userlogout')) {
				#Add Messages
				global $wgMessageCache;
				require( dirname( __FILE__ ) . '/NetworkAuth.i18n.php' );
				foreach( $messages as $key => $value ) {
					  $wgMessageCache->addMessages( $messages[$key], $key );
				}
				
				efNetworkAuth_Authenticate();
			}
		}
		return true;
}

function efNetworkAuth_checkForNetworkAuthUser() {
	global $wgNetworkAuthUsers;
	
	$ip = wfGetIP();
	
	foreach ($wgNetworkAuthUsers as $networkAuthUser) {
		if ( isset( $networkAuthUser['user'] ) ) {
			if ( isset( $networkAuthUser['iprange'] ) ) {
				$hex = IP::toHex( $ip );
				$range = IP::parseRange( $networkAuthUser['iprange'] );
				if ( $hex >= $range[0] && $hex <= $range[1] ) {
					global $wgNetworkAuthHost;
					$wgNetworkAuthHost = $ip;
					return $networkAuthUser['user'];
				}
			} 
			
			if ( isset( $networkAuthUser['ippattern'] ) ) {
				if ( preg_match( $networkAuthUser['ippattern'],  $ip) ) {
					global $wgNetworkAuthHost;
					$wgNetworkAuthHost = $ip;
					return $networkAuthUser['user'];
				}
			}

			if ( isset( $networkAuthUser['hostpattern'] ) ) {
				$host = ar_gethostbyaddr( $ip );
				if ( preg_match( $networkAuthUser['hostpattern'],  $host) ) {
					global $wgNetworkAuthHost;
					$wgNetworkAuthHost = $host;
					return $networkAuthUser['user'];
				}
			}
		} else {
			# No user for range - useless.
		}
	}
	
	return '';
}

function efNetworkAuth_Authenticate() {
	global $wgUser;
	
	$wgNetworkAuthUser = 'HelpdeskComputer';
	
	if (!$wgUser->isLoggedIn()) {
		//echo 'Logged out: ' . $wgUser->getName();
		
		$networkAuthUser = efNetworkAuth_checkForNetworkAuthUser();
		if ( $networkAuthUser != '' ) {
			global $wgNetworkAuthUser;
			$wgNetworkAuthUser = $networkAuthUser;

			$u = User::newFromName( $wgNetworkAuthUser );			
		}
		
		if( is_null( $u ) || !User::isUsableName( $u->getName() ) ) {
			# Not cool.  Bad config
		} else {
			if ( 0 == $u->getID() ) {
				# Not cool.  Bad username
			} else {
				# Finally.
				$u->load();
				$wgUser = $u;
				
				# Since we're not really logged in, just pretending - force a logout
				# before the page gets displayed.
				global $wgHooks;
				$wgHooks['BeforePageDisplay'][] = 'efNetworkAuth_ForceLogout';
				
				# Add a display message to the personal URLs
				$wgHooks['PersonalUrls'][] = 'efNetworkAuth_PersonalUrls';
			}
		}
	} else {
		# Already logged in, do nothing.
	}
	return true;
}

function ar_gethostbyaddr($ip) {
  $output = `host -W 1 $ip`;
  if (ereg('.*pointer ([A-Za-z0-9.-]+)\..*',$output,$regs)) {
    return $regs[1]; 
  }
  return $ip;
} 

function efNetworkAuth_PersonalUrls($personal_urls, $title) {
	global $wgNetworkAuthUser, $wgNetworkAuthHost;
	if (isset($personal_urls['anonuserpage'])) {
		$personal_urls['anonuserpage']['text'] = 
			wfMsg('networkauth-purltext', $wgNetworkAuthUser, $wgNetworkAuthHost);
	} else {
		global $wgUser;
		$newUrls['anonuserpage'] = array(
			'text' => wfMsg('networkauth-purltext', $wgNetworkAuthUser, $wgNetworkAuthHost),
			'href' => null,
			'active' => true
		);
		
		foreach($personal_urls as $key => $value) {
			if ( $key == 'login' )
				$newUrls['anonlogin'] = $value;
			else
				$newUrls[$key] = $value;
		}
		$personal_urls = $newUrls;
	}
	return true;
}

function efNetworkAuth_ForceLogout($out) {
	# Force logout after most of the permission checks
	global $wgUser;
	$wgUser->logout();
	
	return true;
}
