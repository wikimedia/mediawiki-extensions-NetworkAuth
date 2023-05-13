<?php

// phpcs:disable MediaWiki.NamingConventions.LowerCamelFunctionsName.FunctionName

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
	 * @param SkinTemplate $sktemplate
	 * @param array &$links
	 * @return void
	 */
	public static function onSkinTemplateNavigation__Universal( $sktemplate, &$links ) {
		self::getNetworkAuth()->onSkinTemplateNavigation__Universal( $sktemplate, $links );
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
