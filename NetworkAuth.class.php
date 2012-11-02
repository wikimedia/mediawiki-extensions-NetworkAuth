<?php
if (!defined('MEDIAWIKI')) die();

class NetworkAuth {
  function NetworkAuth($authrecords, $networkauthusers) {
    if (is_array($authrecords))
      $this->authrecords = $authrecords;
    else
      $this->authrecords = array($authrecords);

    if (is_array($networkauthusers))
      $this->networkauthusers = $networkauthusers;
    else
      $this->networkauthusers = array($networkauthusers);
  } 

  // if no user is logged in after the MW tried to load the session,
  // test whether the user can be logged in due to its source address
  function onUserLoadAfterLoadFromSession( $user ) {
    // If we are logged in at this point, there is no need to network
    // authenticate
    if ( $user->isLoggedIn() ) {
      return true;
    }

    // fetch the IP address
    $ip = wfGetIP();

    // loop over NetworkAuth records and see if any of it matches
    $matched = false;
    foreach ($this->authrecords as $authrecord) {
      if ( !isset( $authrecord['user'] ) ) {
        // no 'user' is specified for record, so don't do anything
        $record = print_r($authrecord, true);
        wfDebug( "NetworkAuth: Record $record does not contain 'user' field!\n" );
      } else {
        $username = $authrecord['user'];

        // test IP range
        if ( isset( $authrecord['iprange'] ) ) {
          $ranges = $authrecord['iprange'];
          $record = print_r($ranges, true);
          wfDebug( "NetworkAuth: Testing iprange record: $record" );
          if ( ! is_array( $ranges ) ) $ranges = explode("\n", $ranges);
        
          $hex = hexdec(IP::toHex( $ip ));
          foreach ( $ranges as $range ) {
            $parsedRange = IP::parseRange( $range );
            $lower = hexdec($parsedRange[0]);
            $upper = hexdec($parsedRange[1]);
            if ( $hex >= $lower && $hex <= $upper ) {
              wfDebug( "NetworkAuth: IP $ip is in range!\n" );
              $matched = true;
              break 2;
            }
            wfDebug( "NetworkAuth: IP $ip is not in range!\n" );
          }
        }
      
        // test IP pattern
        if ( isset( $authrecord['ippattern'] ) ) {
          $patterns = $authrecord['ippattern'];
          wfDebug( "NetworkAuth: Testing ippattern record: $patterns\n" );
          if ( ! is_array( $patterns ) )
            $patterns = explode("\n", $patterns);
        
          foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern,  $ip) ) {
              $matched = true;
              break 2;
            }
          }
        }
      
        // test host pattern
        if ( isset( $authrecord['hostpattern'] ) ) {
          $patterns = $authrecord['hostpattern'];
          if ( ! is_array( $patterns ) )
            $patterns = explode("\n", $patterns);
        
          $host = gethostbyaddr( $ip );
          foreach ( $patterns as $pattern ) {
            if ( preg_match( $pattern,  $host) ) {
              $matched = true;
              break 2;
            }
          }
        }

      }
    }

    if ( $matched ) {
      wfDebug( "NetworkAuth: Logging in IP $ip, User $username!\n" );

      // log in user
      $mid = User::idFromName( $username );
      $user->setId($mid);
      $user->loadFromId();
      // do *not* set cookie
      //$user->setCookies();
      //$user->saveSettings();
      wfRunHooks('UserLoginComplete', array(&$user, ""));

    }

    return true;
  }

  // for network authenticated users in $wgNetworkAuthSpecialUsers,
  // generate special links in the login bar:
  // Login, Logout, and hide preferences, talk page, contributions, etc.
  function onPersonalUrls(&$personal_urls, &$title) {
    global $wgUser, $wgUseCombinedLoginLink;
    $name = $wgUser->getName();
    if (! in_array($name, $this->networkauthusers)) {
      return true;
    }
    
    $ip = wfGetIP();
  
    wfDebug("NetworkAuth: modifying personal URLs for NetworkAuth special user $name from $ip.\n");

    // generate login link

    $newurls = array();
    // Username
    $newurls['userpage'] = 
      array('text' => wfMsg('networkauth-purltext', $name, $ip),
            'href' => null, 'active' => true);

    // copy default logout url
    $newurls['logout'] = $personal_urls['logout'];

    // Login link (needs to be called 'login2', otherwise CSS changes the layout
    $newurls['login2'] = 
      array('text' => wfMsg( 'login' ),
            'href' => SkinTemplate::makeSpecialUrl( 'Userlogin' ),
            'active' => $title->isSpecial( 'Userlogin' ));

    $personal_urls = $newurls;

    return true;
  }


}