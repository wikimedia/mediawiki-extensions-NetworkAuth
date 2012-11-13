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
    // If we are on the login or logout page, we should also not be
    // logged in automatically
    $context = RequestContext::getMain();
    if ( $user->isLoggedIn() ) {
      return true;
    } else if ( $context->getTitle()->isSpecial('Userlogin') ) {
      wfDebug( "NetworkAuth: Login Special page detected" );
      $user->mId = 0;
      // taken from User::doLogout()
      // remove session cookie
      $user->getRequest()->setSessionData( 'wsUserID', 0 );
      $user->getRequest()->setSessionData( 'wsUserName', null );
      return true;
    }

    // fetch the IP address
    $ip = $user->getRequest()->getIP();

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
      // do *not* set cookie and save settings
      wfRunHooks('UserLoginComplete', array(&$user, ""));
    }

    return true;
  }

  // for network authenticated users in $this->networkauthusers,
  // generate login and logout links in the personal urls, and hide
  // preferences, talk page, contributions, etc.
  function onPersonalUrls(&$personal_urls, &$title) {
    global $wgUseCombinedLoginLink, $wgSecureLogin;

    $context = RequestContext::getMain();
    $request = $context->getRequest();

    $name = $context->getUser()->getName();
    if (! in_array($name, $this->networkauthusers)) {
      return true;
    }
    
    $ip = $context->getRequest()->getIP();
  
    wfDebug("NetworkAuth: modifying personal URLs for NetworkAuth special user $name from $ip.\n");

    // generate personal urls
    $newurls = array();
    // generate username
    $newurls['userpage'] = 
      array('text' => wfMsg('networkauth-purltext', $name, $ip),
            'href' => null, 'active' => true);

    // copy default logout url
    $newurls['logout'] = $personal_urls['logout'];

    // GENERATE LOGIN LINK

    $query = array();
    if ( !$request->wasPosted() ) {
      $query = $request->getValues();
      unset( $query['title'] );
      unset( $query['returnto'] );
      unset( $query['returntoquery'] );
    }
    $thisquery = wfArrayToCGI( $query );

    // The following is copied from SkinTemplate::buildPersonalUrls

    // Due to bug 32276, if a user does not have read permissions,
    // $this->getTitle() will just give Special:Badtitle, which is
    // not especially useful as a returnto parameter. Use the title
    // from the request instead, if there was one.
    $page = Title::newFromURL( $request->getVal( 'title', '' ) );
    $page = $request->getVal( 'returnto', $page );
    $a = array();
    if ( strval( $page ) !== '' ) {
      $a['returnto'] = $page;
      $query = $request->getVal( 'returntoquery', $thisquery );
      if( $query != '' ) {
        $a['returntoquery'] = $query;
      }
    }
    
    if ( $wgSecureLogin && $request->detectProtocol() === 'https' ) {
      $a['wpStickHTTPS'] = true;
    }

    $returnto = wfArrayToCGI( $a );

    $loginlink = 
      $context->getUser()->isAllowed( 'createaccount' ) && $wgUseCombinedLoginLink
      ? 'nav-login-createaccount'
      : 'login';
    $is_signup = $request->getText( 'type' ) == 'signup';

    // anonlogin & login are the same
    $proto = $wgSecureLogin ? PROTO_HTTPS : null;

    $login_url = 
      array( 'text' => $context->msg( $loginlink )->text(),
             'href' => Skin::makeSpecialUrl( 'Userlogin', $returnto, $proto ),
             'active' => $title->isSpecial( 'Userlogin' ) && ( $loginlink == 'nav-login-createaccount' || !$is_signup ),
             'class' => $wgSecureLogin ? 'link-https' : ''
             );
    $createaccount_url = 
      array(
            'text' => $context->msg( 'createaccount' )->text(),
            'href' => Skin::makeSpecialUrl( 'Userlogin', "$returnto&type=signup", $proto ),
            'active' => $title->isSpecial( 'Userlogin' ) && $is_signup,
            'class' => $wgSecureLogin ? 'link-https' : ''
            );

    if ( $context->getUser()->isAllowed( 'createaccount' ) && !$wgUseCombinedLoginLink ) {
      $newurls['createaccount'] = $createaccount_url;
    }

    $newurls['networkauth-login'] = $login_url;
                        
    $personal_urls = $newurls;

    return true;
  }


}