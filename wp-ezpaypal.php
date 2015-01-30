<?php

$plgSlug = basename(dirname(__FILE__));
include_once('ezKillLite.php');

if (!class_exists("EzPayPal6")) {

  class EzPayPal6 {

    var $isPro, $strPro, $plgDir, $plgURL;
    var $ezTran, $ezAdmin, $slug, $domain, $myPlugins;

    function EzPayPal6() { //constructor
      $this->plgDir = dirname(__FILE__);
      $this->plgURL = plugin_dir_url(__FILE__);
      $this->isPro = file_exists("{$this->plgDir}/admin/options-advanced.php");
      if ($this->isPro) {
        $this->strPro = ' Pro';
      }
      else {
        $this->strPro = ' Lite';
      }
      if (is_admin()) {
        require_once($this->plgDir . '/EzTran.php');
        $this->domain = $this->slug = 'easy-paypal';
        $this->ezTran = new EzTran(__FILE__, "EZ PayPal{$this->strPro}", $this->domain);
        $this->ezTran->setLang();
      }
    }

    static function createShop() {
      global $user_ID;
      $page = get_page_by_path('ez-shop');
      if ($page == null) {
        $src = plugins_url("shop.php", __FILE__);
        $content = "<iframe src='$src?wp' frameborder='0' style='overflow:hidden;overflow-x:hidden;overflow-y:hidden;width:100%;'  scrolling='no' id='the_iframe' onLoad='calcHeight();'></iframe>"
                . '<script type="text/javascript">
function calcHeight() {
  var the_iframe = document.getElementById("the_iframe");
  var the_height = the_iframe.contentWindow.document.body.scrollHeight;
  the_iframe.height = the_height;
}
</script>' .
                "<p>" . __("This is an auto-generated page by EZ PayPal Plugin. It displays the products you have defined in a neat table format, which allows your potential buyers to purchase them.", 'easy-paypal') . "</p>
<p>" . __("Note that you can create your own shop pages using the shortcodes. For example, each product can be displayed as a <strong>Buy Now</strong> using the shortcode format <code>[[ezshop buy=3 qty=2]Buy Now[/ezshop]]</code>. This will insert a link, which when clicked, will take your reader to a PayPal page to buy two licences of the product with id 3.", 'easy-paypal') . "</p>
<p>" . __("This E-Shop page shows you the product listing with the ids and names to help you select products and generate shortcodes or links. Click on the Id to view short code or link for the product with the quantity as specified. You can use the shortcode [[ezshop]] or [[ezshop]]Link Text[[/ezshop]] to display a link to your e-shop.", 'easy-paypal') . "</p>";

        $page['post_type'] = 'page';
        $page['post_content'] = $content;
        $page['post_parent'] = 0;
        $page['post_author'] = $user_ID;
        $page['post_status'] = 'publish';
        $page['post_title'] = 'EZ PayPal Shop';
        $page['post_name'] = 'ez-shop';
        $page['comment_status'] = 'closed';
        $pageid = wp_insert_post($page);
      }
      else {
        $pageid = $page->ID;
      }
      return $pageid;
    }

    function getQuery($atts) {
      $query = "";
      $vars = array("id" => "", "code" => "", "key" => "");
      $vars = shortcode_atts($vars, $atts);
      foreach ($vars as $k => $v) {
        if (!empty($v)) {
          $query = "&$k=$v";
          return $query;
        }
      }
    }

    function displayShop($atts, $content = '') {
      extract(shortcode_atts(array("qty" => "1"), $atts));
      $getParam = "?wp";
      $query = $this->getQuery($atts);
      if (!empty($query)) {
        $getParam .= "$query&qty=$qty";
        if (empty($content)) {
          $content = "Buy Now!";
        }
        $buyLink = "<a href='$this->plgURL/buy.php$getParam'>$content</a>";
      }
      else {
        if (empty($content)) {
          $content = "Visit Shop";
        }
        $buyLink = "<a href='$this->plgURL/shop.php$getParam'>$content</a>";
      }
      return $buyLink;
    }

    static function install() {
      $ezppOptions = array(); // not sure if I need this initialization
      $mOptions = "ezPayPal-V6";
      $ezppOptions = get_option($mOptions);
      if (empty($ezppOptions)) {
        // create the necessary tables
        $isInstallingWP = true;
        chdir(dirname(__FILE__) . '/admin');
        require_once('dbSetup.php');
        $ezppOptions['isSetup'] = true;
        $shopPage = EzPayPal6::createShop();
        $ezppOptions['shopPage'] = $shopPage;
      }
      $shopPage = $ezppOptions['shopPage'];
      if (!empty($shopPage)) {
        $shopObj = get_post($shopPage);
      }
      else {
        $shopObj = false;
      }
      if (empty($shopPage) || empty($shopObj) || $shopObj->post_status == 'trash') {
        $shopPage = EzPayPal6::createShop();
        $ezppOptions['shopPage'] = $shopPage;
      }
      else {
        $shopPage = $ezppOptions['shopPage'];
      }
      update_option($mOptions, $ezppOptions);
    }

    static function uninstall() {
      $mOptions = "ezPayPal-V6";
      $ezppOptions = get_option($mOptions);
      $shopPage = $ezppOptions['shopPage'];
      if (!empty($shopPage)) {
        wp_delete_post($shopPage, true);
      }
      delete_option($mOptions);
    }

    function printAdminPage() {
      $src = plugins_url("admin/index.php", __FILE__);
      ?>
      <script type="text/javascript">
        function calcHeight() {
          var w = window,
                  d = document,
                  e = d.documentElement,
                  g = d.getElementsByTagName('body')[0],
                  y = w.innerHeight || e.clientHeight || g.clientHeight;
          document.getElementById('the_iframe').height = y - 70;
        }
        if (window.addEventListener) {
          window.addEventListener('resize', calcHeight, false);
        }
        else if (window.attachEvent) {
          window.attachEvent('onresize', calcHeight)
        }
      </script>
      <?php

      echo "<iframe src='$src' frameborder='0' style='overflow:hidden;overflow-x:hidden;overflow-y:hidden;width:100%;position:absolute;top:5px;left:-10px;right:0px;bottom:0px' width='100%' height='900px' id='the_iframe' onLoad='calcHeight();'></iframe>";
    }

  }

} //End Class EzPayPal

if (class_exists("EzPayPal6")) {
  $ezPayPal = new EzPayPal6();
  if (isset($ezPayPal)) {
    add_action('admin_menu', 'ezPayPal_admin_menu');
    add_shortcode('ezshop', array($ezPayPal, 'displayShop'));

    if (!function_exists('ezPayPal_admin_menu')) {

      function ezPayPal_admin_menu() {
        global $ezPayPal;
        $mName = 'EZ PayPal ' . $ezPayPal->strPro;
        add_options_page($mName, $mName, 'activate_plugins', basename(__FILE__), array($ezPayPal, 'printAdminPage'));
      }

    }

    $file = dirname(__FILE__) . '/easy-paypal.php';
    register_activation_hook($file, array("EzPayPal6", 'install'));
    register_deactivation_hook($file, array("EzPayPal6", 'uninstall'));
  }
}