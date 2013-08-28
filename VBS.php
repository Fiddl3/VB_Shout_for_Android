<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fiddle
 * Date: 27.08.13
 * Time: 16:39
 * To change this template use File | Settings | File Templates.
 *
 * TODO: delete info handler in apk
 */

if(!defined( 'DIR' )) define('DIR', (($cwd = getcwd()) ? $cwd : '.'));


/**
 * Class output
 */
class Output
{
   private static $_charset;
   private static $_output = array();

   public static function error( $code )
   {
      $error = array('code' => $code /*, 'msg' => self::encode($text)*/);
      self::put( 'error', $error );
      self::put( 'return', false );
      self::render();
   }

   public static function put( $key, $text )
   {
      self::$_output[$key] = $text;
   }

   public static function render()
   {
      if ( !isset(self::$_output['return']) )
         self::$_output['return'] = true;

      ob_end_clean();
      ob_start();

      header( 'Content-Type: application/json; charset=utf-8' );
      echo json_encode( self::$_output );

      // header( 'Content-Type: text/html; charset=utf-8' );
      // echo '<pre>';
      // print_r( self::$_output );

      exit;
   }

   public static function info( $code )
   {
      $info = array('code' => $code/*, 'msg' => self::encode( $text )*/);
      if ( !empty(self::$_output['info']) )
         self::$_output['info'][] = $info;
      else
         self::$_output['info'][0] = $info;
   }

   public static function encode( $val, $out = true )
   {
      if ( !class_exists( 'vB_Template_Runtime' ) || empty($val) || is_bool( $val ) || is_numeric( $val ) || is_array( $val ) )
         return $val;


      if ( !isset(self::$_charset) ) {
         self::$_charset = trim( vB_Template_Runtime::fetchStyleVar( 'charset' ) );

         if ( preg_match( '/iso-?8859-?1/i', self::$_charset ) )
            self::$_charset = 'Windows-1252';
         elseif ( preg_match( '/iso-?8859-?(\d+)/i', self::$_charset, $match_iso ) )
            self::$_charset = 'ISO-8859-' . $match_iso[1];
         elseif ( preg_match( '/windows-?125(\d)/i', self::$_charset, $match_win ) )
            self::$_charset = 'Windows-125' . $match_win[1];
         else
            self::$_charset = preg_replace( '/^x-/i', '', self::$_charset );
      }


      if ( function_exists( 'mb_convert_encoding' ) && @mb_convert_encoding( 'string', self::$_charset, 'UTF-8' ) )
         $val = $out ? @mb_convert_encoding( $val, 'UTF-8', self::$_charset ) : @mb_convert_encoding( $val, self::$_charset, 'UTF-8' );
      elseif ( function_exists( 'iconv' ) && @iconv( self::$_charset, 'UTF-8', 'test-str' ) )
         $val = $out ? @iconv( self::$_charset, 'UTF-8//IGNORE', $val ) : @iconv( 'UTF-8', self::$_charset . '//IGNORE', $val );

      return $val;
   }

   public static function encodeArray( array &$source, array $keys )
   {
      foreach ( $keys as $key )
         if ( isset($source[$key]) )
            $source[$key] = self::encode( $source[$key], false );
   }

}

class Router
{
   private $_routes = array();

   public function __construct()
   {


   }

   public function addRoute( $name, $method, array $params = array() )
   {
      $route           = array();
      $route['method'] = $method;
      $route['params'] = $params;

      if ( !isset($this->_routes[$name]) )
         $this->_routes[$name] = array();

      $this->_routes[$name][] = array('method' => $method, 'params' => $params);

   }

   public function execRoute( $name )
   {
      if ( !isset($this->_routes[$name]) )
         return;

      foreach ( $this->_routes[$name] as $route )
         call_user_func_array( $route['method'], $route['params'] );
   }

}

class VBS
{
   const VERSION                   = '1.1.0';

   const ERR_VBS_NOT_INITIALIZED    = 0;
   const ERR_SB_NOT_EXIST           = 1;
   const ERR_SB_NOT_ACTIVE          = 2;
   const ERR_SB_USER_BANNED         = 3;
   const ERR_OUTPUT_IS_EMPTY        = 4;
   const ERR_NO_ROOM_AVAILABLE      = 5;
   const ERR_ERROR_WHILE_FETCHING   = 6;
   const ERR_SHOUT_NOT_EXIST        = 7;
   const ERR_ERROR_WHILE_SAVING     = 8;
   const ERR_INVALID_INSTANCE       = 9;
   const ERR_NO_ACCESS_TO_INSTANCE  = 10;
   const ERR_NO_INSTANCE_AVAILABLE  = 11;
   const ERR_NO_ACCESS_TO_ROOM      = 12;
   const ERR_LOGIN_ERROR_TRY_LATER  = 13;
   const ERR_LOGIN_ERROR_WRONG_DATA = 14;
   const ERR_LOGOUT_ERROR_BAD_TOKEN = 15;
   const ERR_VB_USER_BANNED         = 16;
   const ERR_VB_USER_NOT_EXIST      = 17;


   private static $_gpc;
   private static $_user;
   private static $_vb;

   private static $_chatroom;
   private static $_instance;

   private static $_chatrooms;
   private static $_instances;


   public static function init( vB_Registry $vb )
   {
      self::$_vb   = $vb;
      self::$_user = & $vb->userinfo;
      self::$_gpc  = & $vb->GPC;

      if ( !class_exists( 'VBSHOUT' ) )
         Output::error( self::ERR_SB_NOT_EXIST );

      if ( !self::$_vb->options['dbtech_vbshout_active'] )
         Output::error( self::ERR_SB_NOT_ACTIVE );

      if ( self::$_user['dbtech_vbshout_banned'] )
         Output::error( self::ERR_SB_USER_BANNED );

      if ( !(self::$_user['permissions']['forumpermissions'] & self::$_vb->bf_ugp_forumpermissions['canview']) )
         Output::error( self::ERR_VB_USER_BANNED );


      self::$_chatrooms = & VBSHOUT::$cache['chatroom'];
      self::$_instances = & VBSHOUT::$cache['instance'];
      self::$_instance  = & VBSHOUT::$instance;

      /* TODO: Delete this
      self::$_chatroom    = & VBSHOUT::$chatroom;
      self::$_activeusers = & VBSHOUT::$activeusers;
      self::$_permissions = & VBSHOUT::$permissions;
      */
   }

   public static function postInit( $instanceId, $roomId = 0 )
   {
      if ( empty(self::$_instances) )
         Output::error( self::ERR_NO_INSTANCE_AVAILABLE );

      $flag = false;
      if ( $instanceId != 0 ) {
         if ( !isset(self::$_instances[$instanceId]) )
            Output::error( self::ERR_INVALID_INSTANCE );

         self::$_instance = self::$_instances[$instanceId];

         if ( !self::$_instance['permissions_parsed']['canviewshoutbox'] )
            Output::error( self::ERR_NO_ACCESS_TO_INSTANCE );

         $flag = true;
      }
      else { // We don't know instanceId so we select first instance that user can view
         foreach ( self::$_instances as $instance ) {
            if ( $instance['permissions_parsed']['canviewshoutbox'] ) {
               self::$_instance = $instance;

               $flag = true;
            }
         }
      }

      if ( $roomId ) {
         self::$_chatroom = isset(self::$_chatrooms[$roomId]) ? self::$_chatrooms[$roomId] : null;

         if ( is_null( self::$_chatroom ) || !self::isAccessible( self::$_chatroom ) || !self::$_chatroom['active'] )
            Output::error( self::ERR_NO_ACCESS_TO_ROOM );
      }

      // If user can't view any instance, we throw error :P
      if ( !$flag )
       Output::error( self::ERR_NO_INSTANCE_AVAILABLE );



      /*// select first instance
            foreach ( self::$_instances as $instance ) {
               self::$_instance   = $instance;
               self::$_instanceid = $instance['instanceid'];

               return true;
            }
      */
   }

   public static function isAccessible( array $what, array $user = array() )
   {
      if ( empty($user) )
         $user = & self::$_user;

      if ( !isset($user['membergroupids']) )
         return false;

      if ( !isset($what['membergroupids']) )
         return isset($what['members'][$user['userid']]);

      return (bool)array_intersect( explode( ',', $user['usergroupid'] . ',' . $user['membergroupids'] ), explode( ',', $what['membergroupids'] ) );
   }

   public static function isInSync( $lastUpdate = null, $instanceId = null, $chatroomId = null, $exit = false )
   {
      if ( is_null( $lastUpdate ) ) {
         $lastUpdate = (int)self::$_gpc['lastupdate'];
         $instanceId = self::getInstanceId();
         $chatroomId = self::getChatRoomId();
      }

      if ( isset($lastUpdate) ) {
         !$instanceId && $instanceId = 1;
         !$chatroomId && $chatroomId = 0;

         if ( $lastUpdate && (self::getAop( $instanceId, $chatroomId ) <= $lastUpdate + 5) ) {
            if ( $exit )
               Output::render();

            return true;
         }
      }

      return false;
   }

   public static function getAop( $instanceId = null, $chatroomId = null, $marked = true )
   {
      if ( is_null( $instanceId ) ) {
         $instanceId = self::getInstanceId();
         $chatroomId = self::getChatRoomId();
      }

      if ( strlen( $instanceId ) )
         $instanceId = (int)$instanceId;
      $chatroomId = (int)$chatroomId;

      $path = DIR . '/dbtech/vbshout/aop/';
      $marked && $path .= 'markread-';

      $tab = ((!$chatroomId) ? 'shouts' . $instanceId : "chatroom_{$chatroomId}_{$instanceId}") . '.txt';

      $t = intval( @file_get_contents( $path . $tab ) );

      if ( strlen( $instanceId ) )
         return $t;

      // if it is main chatroom filename can be with or without "0", so second check fo filename with 0
      $t2 = self::getAop( 0, $chatroomId, $marked );

      return max( $t, $t2 );
   }

   public static function getInstanceId( )
   {
      if( !self::$_instance )
         return null;

      return self::$_instance['instanceid'];
   }

   public static function getChatRoomId( )
   {
      if( !self::$_chatroom )
         return 0;

      return self::$_chatroom['chatroomid'];
   }

   public static function getInstanceInfo()
   {
      // $tmp['description'] = strip_tags( self::$_instance['description'] );

      if ( is_null( self::getInstanceId() ) )
         return null;

      //TODO: optimize array keys length
      $info                   = array();
      $info['id']             = (int)self::$_instance['instanceid'];
      $info['active']         = (bool)self::$_instance['active'];
      $info['name']           = Output::encode( strip_tags( self::$_instance['name'] ) );
      $info['maxshouts']      = (int)self::$_instance['options']['maxshouts'];
      $info['floodchecktime'] = (int)self::$_instance['options']['floodchecktime'];
      $info['maxchars']       = (int)self::$_instance['options']['maxchars'];
      $info['maximages']      = (int)self::$_instance['options']['maximages'];
      $info['idletimeout']    = (int)self::$_instance['options']['idletimeout'];
      $info['refresh']        = (int)self::$_instance['options']['refresh'];
      $info['enablepms']      = (bool)self::$_instance['options']['enablepms'];
      $info['enablepmnotifs'] = (bool)self::$_instance['options']['enablepmnotifs'];
      $info['enablesysmsg']   = (bool)self::$_instance['options']['enable_sysmsg'];

      return $info;
   }

   public static function getUserInfo()
   {
      if( !self::$_user )
         return null;

      //$user['lang'] = self::$_user['lang_code']; TODO: Delete this in apk
      $user                 = array();
      $user['userid']       = (int)self::$_user['userid'];
      $user['usergroupid']  = (int)self::$_user['usergroupid'];
      $user['lastactivity'] = (int)self::$_user['lastactivity'];
      $user['username']     = Output::encode( self::$_user['username'] );
      $user['usergroup']    = Output::encode( self::$_user['permissions']['title'] );
      $user['usertitle']    = Output::encode( self::$_user['permissions']['usertitle'] );
      $user['token']        = self::$_user['securitytoken'];

      $user['canviewshoutbox'] = (bool)self::$_instance['permissions_parsed']['canviewshoutbox'];
      $user['canviewarchive']  = (bool)self::$_instance['permissions_parsed']['canviewarchive'];
      $user['canshout']        = (bool)self::$_instance['permissions_parsed']['canshout'];
      $user['canprune']        = (bool)self::$_instance['permissions_parsed']['canprune'];
      $user['canban']          = (bool)self::$_instance['permissions_parsed']['canban'];
      $user['isprotected']     = (bool)self::$_instance['permissions_parsed']['isprotected'];

      return $user;
   }

   public static function getRoomList()
   {
      require_once(DIR . '/includes/html_color_names.php');
      $rList = array();

      //Main instance rooms
      if ( !empty(self::$_instances) ) {
         foreach ( self::$_instances as $room )
            if ( $room['active'] && $room['permissions_parsed']['canviewshoutbox'] )
               $rList[] = array(
                  'rid'    => 0,
                  'iid'    => (int)$room['instanceid'],
                  'color'  => '',
                  'title'  => Output::encode( strip_tags( $room['name'] ) ),
                  'marked' => VBS::getAop( $room['instanceid'], 0 ),
               );
      }

      if ( !empty(self::$_chatrooms) ) {
         foreach ( self::$_chatrooms as $room )
            if ( $room['active'] && self::isAccessible( $room ) )
               $rList[] = array(
                  'rid'    => (int)$room['chatroomid'],
                  'iid'    => (int)$iId = ($room['instanceid'] ? : self::$_instance['instanceid']),
                  'color'  => preg_match( '/[=\'"; ]color[=:"\']+([ a-zA-Z0-9\#]+)[;\'"\]]+/is', ($room['title']), $m ) ? ((substr( $color = trim( $m[1] ), 0, 1 ) != '#') ? (isset($html_color_names[strtolower( $color )]) ? '#' . $html_color_names[strtolower( $color )] : '') : $color) : '',
                  'title'  => Output::encode( strip_tags( $room['title'] ) ),
                  'marked' => VBS::getAop( $iId, $room['chatroomid'] ),
               );

      }

      return $rList? $rList : null;
   }

   public static function getShoutList( $limit = null )
   {
      if ( is_null( self::getInstanceId() ) )
         return false;

//      if ( !self::$_instance['permissions_parsed']['canviewshoutbox'] )
//         Output::error( self::ERR_NO_ACCESS_TO_INSTANCE );

      $fetchQ = self::$_vb->db->query_read_slave( "
      SELECT
        user.avatarid,
        user.avatarrevision,
        user.username,
        user.usergroupid,
        user.membergroupids,
        user.infractiongroupid,
        user.dbtech_vbshout_settings AS shoutsettings,
        user.dbtech_vbshout_shoutstyle AS shoutstyle,
        user.dbtech_vbshout_banned AS banned,
        usergroup.opentag,
        vbshout.*
        " . (self::$_vb->options['avatarenabled'] ? ', avatar.avatarpath, NOT ISNULL(customavatar.userid) AS hascustomavatar, customavatar.dateline AS avatardateline, customavatar.width AS avwidth, customavatar.height AS avheight, customavatar.height_thumb AS avheight_thumb, customavatar.width_thumb AS avwidth_thumb, customavatar.filedata_thumb' : '') . ",
        pmuser.username AS pmusername

      FROM " . TABLE_PREFIX . "dbtech_vbshout_shout AS vbshout
      LEFT JOIN " . TABLE_PREFIX . "user AS user ON(user.userid = vbshout.userid)
      LEFT JOIN " . TABLE_PREFIX . "usergroup AS usergroup ON(usergroup.usergroupid = IF( user.displaygroupid =0, user.usergroupid, user.displaygroupid ))" .
                                                  (self::$_vb->options['avatarenabled'] ? "
      LEFT JOIN " . TABLE_PREFIX . "avatar AS avatar ON (avatar.avatarid = user.avatarid)
      LEFT JOIN " . TABLE_PREFIX . "customavatar AS customavatar ON (customavatar.userid = user.userid)" : ''
                                                  ) . "
      LEFT JOIN " . TABLE_PREFIX . "user AS pmuser ON(pmuser.userid = vbshout.id)
      WHERE
        vbshout.instanceid IN(-1, 0, " . intval( self::getInstanceId() ) . ")
        AND vbshout.userid NOT IN(
          SELECT ignoreuserid
          FROM " . TABLE_PREFIX . "dbtech_vbshout_ignorelist AS ignorelist
          WHERE userid = " . self::$_user['userid'] . "
        )
        AND vbshout.chatroomid = " . intval( self::getChatRoomId() )

                                                  . (is_null($limit) ? " AND vbshout.dateline > " . ((int)self::$_gpc['lastupdate']) : '') .

                                                  ' AND (
                                                     vbshout.userid IN(-1, ' . self::$_user['userid'] . ') OR
          vbshout.id IN(0, ' . self::$_user['userid'] . ')
        )
         AND vbshout.userid NOT IN(
	SELECT userid
	FROM ' . TABLE_PREFIX . 'user AS user
	WHERE dbtech_vbshout_silenced = 1
		AND userid != ' . self::$_user['userid'] . '
)
      ORDER BY shoutid DESC
      LIMIT ' . ($limit ? $limit : 100)
      );

      if ( !self::$_vb->db->num_rows( $fetchQ ) )
         return null;

      require_once(DIR . '/includes/html_color_names.php');

      $list = array();
      while ( $shout = self::$_vb->db->fetch_array( $fetchQ ) ) {
         VBSHOUT::parse_action_codes( $shout['message_raw'], $shout['type'] );

         // $nShout['usergroupid']  = (int) $shout['usergroupid'];
         // $nShout['instanceid']   = (int) $shout['instanceid'];
         // $nShout['chatroomid']   = (int) $shout['chatroomid'];
         // $nShout['notification'] = $shout['notification']; //TODO Delete from apk
         // $nShout['avatar']       = 'default';

         $nShout             = array();
         $nShout['shoutid']  = (int)$shout['shoutid'];
         $nShout['time']     = (int)$shout['dateline'];
         $nShout['msg']      = trim( $shout['message_raw'] );
         $nShout['userid']   = (int)$shout['userid'];
         $nShout['username'] = $shout['username'];
         $nShout['uban']     = (bool)$shout['banned'];
         $nShout['ucolor']   = preg_match( '/[=\'"; ]color[=:"\']+([ a-zA-Z0-9\#]+)[;\'"\]]+/is', $shout['opentag'], $m ) ? ((substr( $color = trim( $m[1] ), 0, 1 ) != '#') ? (isset($html_color_names[strtolower( $color )]) ? '#' . $html_color_names[strtolower( $color )] : '') : $color) : '';
         $nShout['ubold']    = (bool)((stripos( $shout['opentag'], '<b>' ) !== false) || (stripos( $shout['opentag'], 'strong' ) !== false) || (stripos( $shout['opentag'], 'bold' ) !== false));
         $nShout['uitalic']  = (bool)((stripos( $shout['opentag'], '<i>' ) !== false) || (stripos( $shout['opentag'], 'italic' ) !== false));

         $nShout['pm_uid']   = (int)$shout['id'];
         $nShout['pm_uname'] = $shout['pmusername'];

         $nShout['canban']  = (bool)(($shout['userid'] != self::$_user['userid']) && (self::$_instance['permissions_parsed']['canban']) && (!VBSHOUT::check_protected_usergroup( $shout, true )));
         $nShout['canpm']   = (bool)((self::$_instance['permissions_parsed']['canpm']) && ($shout['userid'] > -1) && ($shout['userid'] != self::$_user['userid']));
         $nShout['canedit'] = (bool)((($shout['userid'] == self::$_user['userid']) && (self::$_instance['permissions_parsed']['caneditown'])) || (self::$_instance['permissions_parsed']['caneditothers']));

         if ( !$shout['shoutstyle'] = unserialize( $shout['shoutstyle'] ) )
            $shout['shoutstyle'] = array();

         $iid = self::$_instance['instanceid'];

         $style = isset($shout['shoutstyle'][$iid]) ? $shout['shoutstyle'][$iid] : array('color' => '', 'bold' => '', 'italic' => '');

         $nShout['scolor']  = (substr( $style['color'], 0, 1 ) != '#') ? (isset($html_color_names[strtolower( $style['color'] )]) ? '#' . $html_color_names[strtolower( $style['color'] )] : ((substr( $style['color'], 0, 3 ) == 'rgb') ? (preg_match( '#rgb\( ?([0-9]+), ?([0-9]+), ?([0-9]+) ?\)#', $style['color'], $m ) ? ('#' . str_pad( dechex( $m[1] ), 2, "0", STR_PAD_LEFT ) . str_pad( dechex( $m[2] ), 2, "0", STR_PAD_LEFT ) . str_pad( dechex( $m[3] ), 2, "0", STR_PAD_LEFT )) : '') : '')) : $style['color'];
         $nShout['sbold']   = (bool)$style['bold'];
         $nShout['sitalic'] = (bool)$style['italic'];

         unset($style);

         switch ( $shout['type'] ) {
            case VBSHOUT::$shouttypes['shout']:
               $nShout['type'] = 'shout';
               break;

            case VBSHOUT::$shouttypes['pm']:
               $nShout['type'] = 'pm';
               if ( strpos( $nShout['msg'], '/pm' ) === 0 )
                  $nShout['msg'] = trim( substr( $nShout['msg'], strpos( $nShout['msg'], ';' ) + 1 ) );
               break;

            case VBSHOUT::$shouttypes['me']:
            case VBSHOUT::$shouttypes['notif']:
               $nShout['type'] = 'me';
               break;

            case VBSHOUT::$shouttypes['system']:
               if ( strpos( $shout['message_raw'], '/prune' ) !== false || strpos( $shout['message_raw'], '/unsilence' ) !== false || strpos( $shout['message_raw'], '/silence' ) !== false )
                  $nShout['type'] = 'reload';
               else
                  $nShout['type'] = 'system';

               $nShout['msg'] = strip_tags( $shout['message'] );
               break;

            default:
               $nShout['type'] = 'shout';
         }

         if ( $shout['userid'] == -1 )
            $nShout['type'] = 'system';


         $nShout['msg']      = Output::encode( $shout['msg'] );
         $nShout['username'] = Output::encode( $shout['username'] );
         $nShout['pm_uname'] = Output::encode( $shout['pm_uname'] );

         $list[] = $nShout;
         unset($nShout);

      }

      return array_reverse( $list );
   }


   public static function devInfo()
   {
      Output::put( 'VBVersion',  self::$_vb->versionnumber );
      Output::put( 'VBSVersion', self::VERSION );
      Output::put( 'version',    VBSHOUT::$version );
      Output::put( 'versionNo',  VBSHOUT::$versionnumber );
      Output::put( 'isPro',      VBSHOUT::$isPro );

      Output::render();
   }

   public static function instanceInfo()
   {
      if ( is_null( self::getInstanceId() ) )
         Output::error( self::ERR_VBS_NOT_INITIALIZED );

      Output::put( 'instance', self::getInstanceInfo() );

      self::userInfo();
      self::roomSync();

      Output::put( 'return', (bool)self::$_instance['permissions_parsed']['canviewshoutbox'] );
   }

   public static function userInfo()
   {
      if ( is_null( self::getInstanceId() ) )
         Output::error( self::ERR_VBS_NOT_INITIALIZED );

      $info = self::getUserInfo();
      if ( is_null( $info ) )
         Output::error( self::ERR_VB_USER_NOT_EXIST );

      Output::put( 'user', $info );

      Output::put( 'return', (bool)$info['userid'] );
   }


   public static function roomSync()
   {
      if ( is_null( self::getInstanceId() ) )
         Output::error( self::ERR_VBS_NOT_INITIALIZED );

      $list = self::getRoomList();

      if ( is_null( $list ) )
         Output::error( self::ERR_NO_ROOM_AVAILABLE );

      Output::put( 'rooms', $list );
   }

   public static function shoutSync()
   {
      if ( is_null( self::getInstanceId() ) )
         Output::error( self::ERR_VBS_NOT_INITIALIZED );

      Output::put( 'lastSync', self::getAop() ); //TODO: change "lastup" to lastSync in apk

      $tmp = self::getShoutList();

      if ( is_null( $tmp ) )
         return;
      elseif ( !$tmp )
         Output::error( self::ERR_ERROR_WHILE_FETCHING );
      else
         Output::put( 'shouts', $tmp );
   }

   public static function shoutReSync()
   {
      if ( is_null( self::getInstanceId() ) )
         Output::error( self::ERR_VBS_NOT_INITIALIZED );

      Output::put( 'lastReSync', self::getAop() ); //TODO: change "lastbu" to lastReSync in apk

      self::instanceInfo();

      if ( self::isInSync() )
         return;

      $limit = self::$_gpc['fetchcount'];
      $list  = self::getShoutList( $limit ? $limit : 10 );

      if ( is_null( $list ) )
         return;

      if ( empty($list) )
         Output::error( self::ERR_ERROR_WHILE_FETCHING );
      else
         Output::put( 'shouts', $list );
   }


   public static function shoutSave()
   {
      if ( is_null( self::getInstanceId() ) )
         Output::error( self::ERR_VBS_NOT_INITIALIZED );

      $type = (VBSHOUT::$shouttypes[self::$_gpc['type']] ? self::$_gpc['type'] : 'shout');

      $nShout = VBSHOUT::datamanager_init( 'Shout', self::$_vb, ERRTYPE_ARRAY );

      $nShout->set_info( 'instance', self::$_instance );

      if ( self::$_gpc['shoutid'] ) {
         if ( !$shout = self::$_vb->db->query_first_slave( 'SELECT * FROM ' . TABLE_PREFIX . 'dbtech_vbshout_shout WHERE shoutid = ' . self::$_gpc['shoutid'] ) )
            Output::error( self::ERR_SHOUT_NOT_EXIST );

         $nShout->set_existing( $shout );
         $nShout->set( 'message', Output::encode( self::$_gpc['message'], false ) );
      }
      else {
         $nShout->set( 'id', self::$_gpc['pmuserid'] );
         $nShout->set( 'message', Output::encode( self::$_gpc['message'], false ) );
         $nShout->set( 'type', VBSHOUT::$shouttypes[$type] );
         $nShout->set( 'instanceid', (!is_null( self::getChatRoomId() ) ? self::$_chatroom['instanceid'] : self::getInstanceId()) );
         $nShout->set( 'chatroomid', self::getChatRoomId() );
      }

      require_once(DIR . '/includes/class_bbcode.php');

      VBSHOUT::$tag_list = fetch_tag_list( '', true );

      $nShout->save();

      if ( !empty($nShout->errors) )
         Output::error( self::ERR_ERROR_WHILE_SAVING );
      // foreach ( $nShout->errors as $e ) self::info( $e );


      if ( $type == 'pm' )
         VBSHOUT::set_aop( 'pm_' . self::$_gpc['pmuserid'] . '_', self::getInstanceId(), false );

      if ( self::getChatRoomId() )
         VBSHOUT::set_aop( 'chatroom_' . self::getChatRoomId() . '_', self::getInstanceId() );

      VBSHOUT::set_aop( 'shouts', self::getInstanceId() );

      Output::put( 'return', true );
   }

   public static function shoutDelete()
   {
      if ( is_null( self::getInstanceId() ) )
         Output::error( self::ERR_VBS_NOT_INITIALIZED );

      $nShout = VBSHOUT::datamanager_init( 'Shout', self::$_vb, ERRTYPE_ARRAY );
      $nShout->set_info( 'instance', self::$_instance );

      if ( !self::$_gpc['shoutid'] )
         Output::error( self::ERR_SHOUT_NOT_EXIST );

      if ( !$shout = self::$_vb->db->query_first_slave( 'SELECT * FROM ' . TABLE_PREFIX . 'dbtech_vbshout_shout WHERE shoutid = ' . self::$_gpc['shoutid'] ) )
         Output::error( self::ERR_SHOUT_NOT_EXIST );

      $nShout->set_existing( $shout );

      if ( $flag = $nShout->delete() ) {
         if ( self::getChatRoomId() )
            VBSHOUT::set_aop( 'chatroom_' . self::getChatRoomId() . '_', self::getInstanceId() );
         VBSHOUT::set_aop( 'shouts', self::getInstanceId() );
      }

      Output::put( 'return', (bool)$flag );

   }

}


function userLogIn( array $data )
{
   global $vbulletin;
   require_once(DIR . '/includes/functions_login.php');

   Output::encodeArray( $data, array('username') );

   if ( verify_strike_status( $data['username'], true ) === false )
      Output::error( VBS::ERR_LOGIN_ERROR_TRY_LATER );

   if ( !verify_authentication( $data['username'], null, $data['md5password'], $data['md5password'], $data['cookieuser'], true ) ) {
      exec_strike_user( $data['username'] );

      Output::error( VBS::ERR_LOGIN_ERROR_WRONG_DATA );
   }

   $vbulletin->session->created = false;

   exec_unstrike_user( $data['username'] );
   process_new_login( $data['logintype'], $data['cookieuser'], null );

   $user             = array();
   $user['userid']   = $vbulletin->userinfo['userid'];
   $user['username'] = $vbulletin->userinfo['username'];
   $user['token']    = $vbulletin->userinfo['securitytoken'];

   Output::put( 'return', true );
   Output::put( 'user', $user );
}

function userLogOut( $token )
{
   global $vbulletin;
   require_once(DIR . '/includes/functions_login.php');


   if ( $vbulletin->userinfo['userid'] != 0 && !verify_security_token( $token, $vbulletin->userinfo['securitytoken_raw'] ) )
      Output::error( VBS::ERR_LOGOUT_ERROR_BAD_TOKEN );

   process_logout();

   Output::put( 'return', true );
}

/**************************************************
 *
 *                   TEST
 *
 **************************************************/

Output::put( 'id', (int)$_REQUEST['id'] );
VBS::isInSync( $_REQUEST['lastupdate'], $_REQUEST['instance'], $_REQUEST['chatroom'], $_REQUEST['do'] == 'sync' );

require_once('./global.php');
global $vbulletin;

$vbulletin->input->clean_array_gpc( 'r', array(
                                              'token'       => TYPE_STR,
                                              'id'          => TYPE_INT,
                                              'do'          => TYPE_STR,
                                              'action'      => TYPE_STR,
                                              'order'       => TYPE_STR,
                                              'lastupdate'  => TYPE_INT,
                                              'fetchcount'  => TYPE_INT,
                                              'chatroom'    => TYPE_STR,
                                              'instance'    => TYPE_INT,

                                              'username'    => TYPE_STR,
                                              'md5password' => TYPE_STR,
                                              'cookieuser'  => TYPE_BOOL,
                                              'logintype'   => TYPE_STR,

                                              'shoutid'     => TYPE_INT,
                                              'message'     => TYPE_NOHTML,
                                              'type'        => TYPE_STR,
                                              'pmuserid'    => TYPE_UINT,
                                              'chatroomid'  => TYPE_UINT,

                                         ) );

VBS::init( $vbulletin );

$router = new Router();

$router->addRoute( 'login', 'userLogIn', array($vbulletin->GPC) );

$router->addRoute( 'logout', 'userLogOut', array($vbulletin->GPC['token']) );

$router->addRoute( 'sync', 'VBS::postInit', array($vbulletin->GPC['instance'], $vbulletin->GPC['chatroom']) );
$router->addRoute( 'sync', 'VBS::shoutSync' );

$router->addRoute( 'reSync', 'VBS::postInit', array($vbulletin->GPC['instance'], $vbulletin->GPC['chatroom']) );
$router->addRoute( 'reSync', 'VBS::shoutReSync' );

$router->addRoute( 'save', 'VBS::postInit', array($vbulletin->GPC['instance'], $vbulletin->GPC['chatroom']) );
$router->addRoute( 'save', 'VBS::shoutSave' );

$router->addRoute( 'delete', 'VBS::postInit', array($vbulletin->GPC['instance'], $vbulletin->GPC['chatroom']) );
$router->addRoute( 'delete', 'VBS::shoutDelete' );


$router->execRoute($vbulletin->GPC['do']);

Output::render();


// preInit
// includes
// GPC
// init
// router
// render