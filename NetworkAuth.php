<?php
if ( !defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['other'][] = 
  array(
        'path'           => __FILE__,
	'name'           => 'NetworkAuth',
	'version'        => '2.0',
	'author'         => 'Tim Laqua, Olaf Lenz',
	'descriptionmsg' => 'networkauth-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:NetworkAuth',
        );

$dir = dirname(__FILE__);
$dir .= '/';
                         
// directly load ExternBib.class.php, as an instance will be created
// anyway
require_once($dir . 'NetworkAuth.class.php');
  
$wgExtensionMessagesFiles['NetworkAuth'] = $dir . 'NetworkAuth.i18n.php';
$wgExtensionFunctions[] = 'efNetworkAuthSetup';
$wgExtensionMessagesFiles['NetworkAuth'] = $dir . '/NetworkAuth.i18n.php';

// defaults
if (!isset($wgNetworkAuthUsers))                                        
  $wgNetworkAuthUsers = array();
if (!isset($wgNetworkAuthSpecialUsers))
  $wgNetworkAuthSpecialUsers = array();

function efNetworkAuthSetup() {
  global 
    $wgHooks,
    $wgNetworkAuth,
    $wgNetworkAuthUsers, 
    $wgNetworkAuthSpecialUsers;

  $wgNetworkAuth = new NetworkAuth($wgNetworkAuthUsers, $wgNetworkAuthSpecialUsers);

  $wgHooks['UserLoadAfterLoadFromSession'][] = 
    array($wgNetworkAuth, 'onUserLoadAfterLoadFromSession');
  $wgHooks['PersonalUrls'][] = 
    array($wgNetworkAuth, 'onPersonalUrls');

  return true;
}





