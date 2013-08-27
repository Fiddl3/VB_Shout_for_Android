<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Fiddle
 * Date: 27.08.13
 * Time: 16:39
 * To change this template use File | Settings | File Templates.
 */

//TODO: delete info handler in apk
(!defined( 'DIR' )) && define('DIR', (($cwd = getcwd()) ? $cwd : '.'));


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
      self::output( 'error', $error );
      self::output( 'return', false );
      self::render();
   }

   public static function output( $key, $text )
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

   public static function rturn( $bool )
   {

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
   const VERSION            = '1.1.0';
   const ERR_SB_NOT_EXIST   = 1;
   const ERR_SB_NOT_ACTIVE  = 2;
   const ERR_SB_USER_BANNED = 3;
   const ERR_VB_USER_BANNED = 16;
   const ERR_NO_ACCESS_TO_INSTANCE = 1;


   private static $_vb;
   private static $_gpc;
   private static $_user;

   private static $_instance;

   private static $_chatrooms;
   private static $_instances;

   private static $_actual = false;

   /*
    TODO: Move code out
   // preInit
   // includes
   // GPC
   // init
   // router
   // render

   public static function preInit( $iId, $rId, $lastUpdate, $exit = false )
   {
       if(isset( $lastUpdate )){
           (!$iId) && $iId = 1;
           (!$rId) && $rId = 0;

           if($lastUpdate && (self::getAop( $iId, $rId ) <= $lastUpdate+5 ) ){
               if( $exit  )
                   Output::render(  );

               self::$_actual = true;
           }
       }
   }
   */

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

   public static function getInstanceInfo()
   {
      // $tmp['description'] = strip_tags( self::$_instance['description'] );
      //TODO: optimize array keys length
      $tmp                   = array();
      $tmp['id']             = (int)self::$_instance['instanceid'];
      $tmp['active']         = (bool)self::$_instance['active'];
      $tmp['name']           = Output::encode( strip_tags( self::$_instance['name'] ) );
      $tmp['maxshouts']      = (int)self::$_instance['options']['maxshouts'];
      $tmp['floodchecktime'] = (int)self::$_instance['options']['floodchecktime'];
      $tmp['maxchars']       = (int)self::$_instance['options']['maxchars'];
      $tmp['maximages']      = (int)self::$_instance['options']['maximages'];
      $tmp['idletimeout']    = (int)self::$_instance['options']['idletimeout'];
      $tmp['refresh']        = (int)self::$_instance['options']['refresh'];
      $tmp['enablepms']      = (bool)self::$_instance['options']['enablepms'];
      $tmp['enablepmnotifs'] = (bool)self::$_instance['options']['enablepmnotifs'];
      $tmp['enablesysmsg']   = (bool)self::$_instance['options']['enable_sysmsg'];

      return $tmp;
   }

   public static function getUserInfo()
   {
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
      require(DIR . '/includes/html_color_names.php');
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
            if ( $room['active'] && self::canAccess( $room ) )
               $rList[] = array(
                  'rid'    => (int)$room['chatroomid'],
                  'iid'    => (int)$iId = ($room['instanceid'] ? : self::$_instance['instanceid']),
                  'color'  => preg_match( '/[=\'"; ]color[=:"\']+([ a-zA-Z0-9\#]+)[;\'"\]]+/is', ($room['title']), $m ) ? ((substr( $color = trim( $m[1] ), 0, 1 ) != '#') ? (isset($html_color_names[strtolower( $color )]) ? '#' . $html_color_names[strtolower( $color )] : '') : $color) : '',
                  'title'  => Output::encode( strip_tags( $room['title'] ) ),
                  'marked' => VBS::getAop( $iId, $room['chatroomid'] ),
               );

      }

      return $rList;
   }

   public static function getAop( $iId, $rId, $marked = true )
   {
      if ( strlen( $iId ) )
         $iId = (int)$iId;
      $rId = (int)$rId;

      $path = DIR . '/dbtech/vbshout/aop/';
      $marked && $path .= 'markread-';

      $tab = ((!$rId) ? 'shouts' . $iId : "chatroom_{$rId}_{$iId}") . '.txt';

      $t = intval( @file_get_contents( $path . $tab ) );

      if ( strlen( $iId ) )
         return $t;

      // if it is main chatroom filename can be with or without "0", so second check fo filename with 0
      $t2 = self::getAop( 0, $rId, $marked );

      return max( $t, $t2 );
   }

   public static function canAccess( array $where, array $user = array() )
   {
      if ( empty($user) )
         $user = & self::$_user;

      if ( !isset($user['membergroupids']) )
         return false;

      if ( !isset($where['membergroupids']) )
         return isset($where['members'][$user['userid']]);

      return (bool)array_intersect( explode( ',', $user['usergroupid'] . ',' . $user['membergroupids'] ), explode( ',', $where['membergroupids'] ) );
   }

   public static function getShoutList( $num = null )
   {
      if ( !self::$_instance && (!self::$_instance = self::_getInstance()) )
         return false;
      if ( !self::$_instance['permissions_parsed']['canviewshoutbox'] )
         Output::error( self::ERR_NO_ACCESS_TO_INSTANCE );

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
        vbshout.instanceid IN(-1, 0, " . intval( self::$_instance['instanceid'] ) . ")
        AND vbshout.userid NOT IN(
          SELECT ignoreuserid
          FROM " . TABLE_PREFIX . "dbtech_vbshout_ignorelist AS ignorelist
          WHERE userid = " . self::$_user['userid'] . "
        )
        AND vbshout.chatroomid = " . intval( self::$_chatroomid )

                                                  . (is_null( $num ) ? " AND vbshout.dateline > " . (self::$_vb->GPC['lastupdate']) : '') .

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
      LIMIT ' . ($num ? $num : 100)
      );

      if ( !self::$_vb->db->num_rows( $fetchQ ) )
         return null;



      $list = array();
      require(DIR . '/includes/html_color_names.php');

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
         $nShout['msg']      = trim( Output::encode( $shout['message_raw'] ) );
         $nShout['userid']   = (int)$shout['userid'];
         $nShout['username'] = Output::encode( $shout['username'] );
         $nShout['uban']     = (bool)$shout['banned'];
         $nShout['ucolor']   = preg_match( '/[=\'"; ]color[=:"\']+([ a-zA-Z0-9\#]+)[;\'"\]]+/is', $shout['opentag'], $m ) ? ((substr( $color = trim( $m[1] ), 0, 1 ) != '#') ? (isset($html_color_names[strtolower( $color )]) ? '#' . $html_color_names[strtolower( $color )] : '') : $color) : '';
         $nShout['ubold']    = (bool)((stripos( $shout['opentag'], '<b>' ) !== false) || (stripos( $shout['opentag'], 'strong' ) !== false) || (stripos( $shout['opentag'], 'bold' ) !== false));
         $nShout['uitalic']  = (bool)((stripos( $shout['opentag'], '<i>' ) !== false) || (stripos( $shout['opentag'], 'italic' ) !== false));

         $nShout['pm_uid']   = (int)$shout['id'];
         $nShout['pm_uname'] = Output::encode( $shout['pmusername'] );

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

               $nShout['msg'] = Output::encode( strip_tags( $shout['message'] ) );
               break;

            default:
               $nShout['type'] = 'shout';
         }

         if ( $shout['userid'] == -1 )
            $nShout['type'] = 'system';

         ///////////////////////////////////////
         // $nShout['message'] = $nShout['msg'];
         ///////////////////////////////////////


         $list[] = $nShout;
         unset($nShout);

      }

      return array_reverse( $list );
   }

   public static function devInfo()
   {
      Output::output( 'VBVersion', self::$_vb->versionnumber );
      Output::output( 'VBSVersion', self::VERSION );
      Output::output( 'version', VBSHOUT::$version );
      Output::output( 'versionNo', VBSHOUT::$versionnumber );
      Output::output( 'isPro', VBSHOUT::$isPro );

      Output::render();
   }
}
