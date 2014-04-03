<?php
/*
Copyright (C) 2012,2013 Olaf Lenz <http://www.mediawiki.org/wiki/User:Olenz>
Copyright (C) 2007,2008,2009,2010,2011 Tim Laqua

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License along
with this program; if not, write to the Free Software Foundation, Inc.,
51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
http://www.gnu.org/copyleft/gpl.html
*/

if ( !defined( 'MEDIAWIKI' ) ) die();

$wgExtensionCredits['other'][] =
  array(
        'path'           => __FILE__,
	'name'           => 'NetworkAuth',
	'version'        => '2.1.1',
	'author'         => 'Tim Laqua, Olaf Lenz',
	'descriptionmsg' => 'networkauth-desc',
	'url'            => 'https://www.mediawiki.org/wiki/Extension:NetworkAuth',
        );

// directly load ExternBib.class.php, as an instance will be created
// anyway
require_once __DIR__ . '/NetworkAuth.class.php';

$wgMessagesDirs['NetworkAuth'] = __DIR__ . '/i18n';
$wgExtensionMessagesFiles['NetworkAuth'] = __DIR__ . '/NetworkAuth.i18n.php';
$wgExtensionFunctions[] = 'efNetworkAuthSetup';

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
