<?php

/**
 * A wrapper class for the hooks of this extension.
 */
class NetworkAuthHooks {

	/**
	 * @param User $user
	 * @return void
	 */
	public static function onUserLoadAfterLoadFromSession( $user ) {
		self::getNetworkAuth()->onUserLoadAfterLoadFromSession( $user );
	}

	/**
	 * @param array &$personal_urls
	 * @param Title &$title
	 * @return void
	 */
	public static function onPersonalUrls( &$personal_urls, &$title ) {
		self::getNetworkAuth()->onPersonalUrls( $personal_urls, $title );
	}

	/**
	 * Creates, if necessary, a instance of the NetworkAuth and returns it.
	 *
	 * @return NetworkAuth
	 */
	private static function getNetworkAuth() {
		global $wgNetworkAuthUsers, $wgNetworkAuthSpecialUsers;
		static $networkAuth = null;

		if ( !$networkAuth ) {
			$networkAuth = new NetworkAuth( $wgNetworkAuthUsers, $wgNetworkAuthSpecialUsers );
		}

		return $networkAuth;
	}

}
