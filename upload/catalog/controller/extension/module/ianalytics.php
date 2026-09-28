<?php
class ControllerExtensionModuleIanalytics extends Controller
{
    private $moduleName;
    private $modulePath;
    private $moduleSettings;
    private $moduleIsEnabled;
    private $moduleTrackAfterSale;
    private $data = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        $this->load->model('setting/setting');
        $this->config->load('isenselabs/ianalytics');

        $this->moduleName = $this->config->get('ianalytics_name');
        $this->modulePath = $this->config->get('ianalytics_path');

        $setting = array_merge(
            array('module_ianalytics_status' => false),
            $this->model_setting_setting->getSetting('module_' . $this->moduleName, $this->config->get('config_store_id')),
            $this->model_setting_setting->getSetting('module_' . $this->moduleName . '_state', $this->config->get('config_store_id'))
        );

        $this->moduleTrackAfterSale = false;
        $this->moduleIsEnabled = false;
        $this->moduleSettings = array(
            'Enabled' => 'no',
            'AfterSaleData' => 'no',
            'GoogleAnalytics' => 'no'
        );

        if (!empty($setting) && $setting['module_ianalytics_status'] && $setting['module_ianalytics_setting']['Enabled'] == 'yes') {
            $this->moduleSettings = $setting['module_ianalytics_setting'];
            $this->moduleIsEnabled = true;

            if (empty($setting['module_ianalytics_state_isrun'])) {
                $this->moduleIsEnabled = false;
            }
            if ($this->moduleSettings['AfterSaleData'] == 'yes') {
                $this->moduleTrackAfterSale = true;
            }
        }

        $this->ipAddress    = !empty($this->request->server['REMOTE_ADDR']) ? $this->request->server['REMOTE_ADDR'] : '';
        $this->userLanguage = !empty($this->request->server['HTTP_ACCEPT_LANGUAGE']) ? $this->request->server['HTTP_ACCEPT_LANGUAGE'] : '';
    }

    public function index()
    {
        //  Required to avoid error incase module assigned to layout
    }

    protected function trackVisits()
    {
        $unique      = 0;
        $referer     = -1;
        $impressions = 1;
        $date        = date('Y-m-d');
        $store_id    = $this->config->get('config_store_id');
        $stage       = $this->getDailyTimeStage(strtotime(date('H:i:s')));
        $refsource   = !empty($this->request->server['HTTP_REFERER']) ? $this->request->server['HTTP_REFERER'] : '';

        $exists = $this->db->query("SELECT * FROM `" . DB_PREFIX . "ianalytics_visits_data` WHERE `date` = '$date' AND `stage` = '$stage' AND `store_id` = '$store_id' ");

        // Initial visit check
        if (!isset($this->session->data['iAnalyticsVisitID'])) {
            $unique  = 1;
            $referer = $this->getReferer($refsource);
        }

        // Daily time stage
        if (empty($exists->row)) {
            $this->dbAddData(array(
                'ianalytics_visits_data' => array (
                    'date'            => $date,
                    'stage'           => $stage,
                    'unique_visits'   => $unique,
                    'impressions'     => 1,
                    'referers_direct' => ($referer == 0 ? 1 : 0),
                    'referers_social' => ($referer == 1 ? 1 : 0),
                    'referers_search' => ($referer == 2 ? 1 : 0),
                    'referers_other'  => ($referer == 3 ? 1 : 0),
                    'store_id'        => $store_id,
                )
            ));

        // On Page reload
        } else {
            $db_referer = '';
            if (!isset($this->session->data['iAnalyticsVisitID'])) {
                switch ($referer) {
                    case 0: $db_referer = ", `referers_direct`=referers_direct+1"; break;
                    case 1: $db_referer = ", `referers_social`=referers_social+1"; break;
                    case 2: $db_referer = ", `referers_search`=referers_search+1"; break;
                    case 3: $db_referer = ", `referers_other`=referers_other+1"; break;
                    default: $db_referer = ''; break;
                }
            }

            $this->db->query("UPDATE `" . DB_PREFIX . "ianalytics_visits_data` SET `unique_visits`=unique_visits+$unique, `impressions`=impressions+$impressions $db_referer WHERE `date`='$date' AND `stage` = '$stage'  AND `store_id` = '$store_id' ");
        }

        if (!isset($this->session->data['iAnalyticsVisitID'])) {
            $this->session->data['iAnalyticsVisitID'] = $this->session->getId();
        }

        // Init funnel stage
        if (!isset($this->session->data['iAnalyticsFunnelStage'])) {
            $this->trackFunnel(0);
        }
        // Reset funnel stage
        if (isset($this->session->data['iAnalyticsFunnelStage']) && !$this->moduleTrackAfterSale) {
            unset($this->session->data['iAnalyticsFunnelStage']);
        }
    }

    protected function trackSearch()
    {
        $valid = false;
        if (!empty($this->request->get['route'])) {
            if (strpos($this->request->get['route'], 'product/search') !== false || strpos($this->request->get['route'], 'product/isearch') !== false) {
                if (empty($this->request->get['search'])) {
                    return;
                }
                $valid = true;
            }
        }

        if ($valid) {
            $this->dbAddData(array(
                'ianalytics_search_data' => array (
                    'date'             => date('Y-m-d'),
                    'time'             => date('H:i:s'),
                    'from_ip'          => $this->ipAddress,
                    'spoken_languages' => $this->userLanguage,
                    'search_value'     => $this->request->get['search'],
                    'search_results'   => $this->getSearchTotalResults($this->request->get['search']),
                    'store_id'         => $this->config->get('config_store_id'),
                )
            ));
        }
    }

    protected function trackOpenProducts()
    {
        if (empty($this->request->get['route']) || stripos($this->request->get['route'], 'product/product') !== 0 || empty($this->request->get['product_id'])) {
            return;
        }

        $product_id   = $this->request->get['product_id'];
        $product_info = $this->getProductInfo($product_id);

        $this->dbAddData(array(
            'ianalytics_product_opens' => array (
                'date'                 => date('Y-m-d'),
                'time'                 => date('H:i:s'),
                'from_ip'              => $this->ipAddress,
                'spoken_languages'     => $this->userLanguage,
                'product_id'           => $product_id,
                'product_name'         => $product_info['name'],
                'product_model'        => $product_info['model'],
                'product_price'        => $product_info['price'],
                'product_quantity'     => $product_info['quantity'],
                'product_stock_status' => $product_info['stock_status'],
                'store_id'             => $this->config->get('config_store_id'),
            )
        ));
    }

    protected function trackComparedProducts()
    {
        if (empty($this->request->get['route']) || $this->request->get['route'] != 'product/compare') {
            return;
        }

        $productsCompared = !empty($this->session->data['compare']) ? $this->session->data['compare'] : array();
        if (count($productsCompared) < 2) {
            return;
        }

        $namedProductsCompared = array();
        $idsProductsCompared   = array();
        foreach ($productsCompared as $key => $value) {
            $product_info             = $this->getProductInfo($value);
            $namedProductsCompared[] = $product_info['name'];
            $idsProductsCompared[]   = $value;
        }
        sort($idsProductsCompared);


        $this->dbAddData(array(
            'ianalytics_product_comparisons' => array (
                'date'             => date('Y-m-d'),
                'time'             => date('H:i:s'),
                'from_ip'          => $this->ipAddress,
                'spoken_languages' => $this->userLanguage,
                'product_ids'      => implode(',', $idsProductsCompared),
                'product_names'    => implode(' vs. ', $namedProductsCompared),
                'store_id'         => $this->config->get('config_store_id'),
            )
        ));
    }

    private function getDailyTimeStage($time)
    {
        $stage00 = strtotime('00:00:00');
        $stage0  = strtotime('06:00:00');
        $stage1  = strtotime('12:00:00');
        $stage2  = strtotime('18:00:00');
        $stage3  = strtotime('24:00:00');
        $result  = 0;

        if ($time > $stage00 && $time < $stage0) {
            $result = '0';
        } elseif ($time > $stage0 && $time < $stage1) {
            $result = '1';
        } elseif ($time > $stage1 && $time < $stage2) {
            $result = '2';
        } elseif ($time > $stage2 && $time < $stage3) {
            $result = '3';
        }

        return $result;
    }

    private function getReferer($ref)
    {
        $referers = array();
        $referers['social'] = array(
            "facebook.com",
            "t.co",
            "twitter.com",
            "plus.url.googl",
            "instagram"
        );
        $referers['search_engines'] = array(
            "google.",
            "bing.com",
            "yandex.com",
            "baidu.com"
        );
        $origin = 0; // 0 - Direct, 1 - Social, 2 - Search, 3 - Other

        if (!empty($ref)) {
            foreach ($referers['social'] as $letter) {
                if (strpos($ref, $letter) !== false) {
                    $origin = 1;
                }
            }
            if ($origin!=1) {
                foreach ($referers['search_engines'] as $letter) {
                    if (strpos($ref, $letter) !== false) {
                        $origin = 2;
                    }
                }
            }
            if (($origin!=1) && ($origin!=2) && (strpos($ref, HTTP_SERVER)!==false)) {
                $origin = 0;
            } elseif (($origin!=1) && ($origin!=2)) {
                $origin = 3;
            }
        }

        return $origin;
    }

    private function dbAddData($data)
    {
        foreach ($data as $table => $tableData) {
            $insertFields = array();
            $insertData   = array();

            foreach ($tableData as $fieldName => $fieldValue) {
                $insertFields[] = $fieldName;
                $insertData[]   = '"' . $this->db->escape($fieldValue) . '"';
            }

            $this->db->query('INSERT INTO ' . DB_PREFIX . $table . ' (' . implode(',', $insertFields) . ') VALUES (' . implode(',', $insertData) . ')');
        }
    }

    private function getSearchTotalResults($filter_name)
    {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getTotalProducts(array('filter_name' => $filter_name));
    }

    private function getProductInfo($product_id)
    {
        $this->load->model('catalog/product');
        return $this->model_catalog_product->getProduct($product_id);
    }


    //====================================================================================

    protected function trackProductToCart()
    {
        $this->load->model('catalog/product');

        $product_id   = $this->request->post['product_id'];
        $product_info = $this->model_catalog_product->getProduct($product_id);

        $this->dbAddData(array(
            'ianalytics_product_add_to_cart' => array (
                'date'                 => date('Y-m-d'),
                'time'                 => date('H:i:s'),
                'from_ip'              => $this->ipAddress,
                'spoken_languages'     => $this->userLanguage,
                'product_id'           => $product_id,
                'product_name'         => $product_info['name'],
                'product_model'        => $product_info['model'],
                'product_price'        => $product_info['price'],
                'product_quantity'     => $product_info['quantity'],
                'product_stock_status' => $product_info['stock_status'],
                'store_id'             => $this->config->get('config_store_id'),
            )
        ));

        if ($this->moduleTrackAfterSale) {
            $this->trackFunnel(1);
        }
    }

    /**
     * Funnel Stage
     *
     * - 0  trackVisits
     * - 1  trackProductToCart
     * - 2  addFunnelLoginRegister
            addFunnelLoginRegisterCheckout
     * - 3  addFunnelCheckoutShipping
     * - 4  addFunnelCheckoutPayment
     * - 5  addFunnelCheckoutConfirm
     * - 6  addFunnelCheckoutSuccess    
     */
    private function trackFunnel($stage)
    {
        if ($stage == 0 && !isset($this->session->data['iAnalyticsFunnelStage'])
            || isset($this->session->data['iAnalyticsFunnelStage']) && (int)$this->session->data['iAnalyticsFunnelStage'] < $stage
        ) {
            $this->dbAddData(array(
                'ianalytics_funnel_data' => array (
                    'stage'            => $stage,
                    'date'             => date('Y-m-d'),
                    'time'             => date('H:i:s'),
                    'from_ip'          => $this->ipAddress ? $this->ipAddress : '*HiddenIP*',
                    'spoken_languages' => $this->userLanguage,
                    'store_id'         => $this->config->get('config_store_id'),
                )
            ));

            if (in_array($stage, array(0, 6))) {
                $this->session->data['iAnalyticsFunnelStage'] = 0;
            } else {
                $this->session->data['iAnalyticsFunnelStage'] = $stage;
            }
        }
    }


    //====================================================================================
    // Events
    //====================================================================================

    /**
     * Presale track: search, open and compare product
     *
     * - catalog/controller/common/header/before
     */
    public function addPreSaleTracker(&$route, &$data)
    {
        if (!$this->moduleIsEnabled) { return; }

        $this->trackVisits();
        $this->trackSearch();
        $this->trackOpenProducts();
        $this->trackComparedProducts();
    }

    /**
     * Track product to cart
     *
     * - catalog/controller/checkout/cart/add/after
     */
    public function addProductToCart(&$route, &$data, &$output)
    {
        if (!$this->moduleIsEnabled) { return; }

        // Important to check if product is valid, because $data and $output is empty
        $product_id = 0;
        if (isset($this->request->post['product_id'])) {
            $product_id = (int)$this->request->post['product_id'];
        }

        $this->load->model('catalog/product');
        $product_info = $this->model_catalog_product->getProduct($product_id);

        if ($product_info) {
            $error    = false;
            $quantity = isset($this->request->post['quantity']) ? (int)$this->request->post['quantity'] : 1;
            $option   = isset($this->request->post['option']) ? array_filter($this->request->post['option']) : array();
            $recurring_id = isset($this->request->post['recurring_id']) ? $this->request->post['recurring_id'] : 0;

            $product_options = $this->model_catalog_product->getProductOptions($this->request->post['product_id']);
            foreach ($product_options as $product_option) {
                if ($product_option['required'] && empty($option[$product_option['product_option_id']])) {
                    $error = true;
                }
            }

            $recurrings = $this->model_catalog_product->getProfiles($product_info['product_id']);
            if ($recurrings) {
                $recurring_ids = array();
                foreach ($recurrings as $recurring) {
                    $recurring_ids[] = $recurring['recurring_id'];
                }

                if (!in_array($recurring_id, $recurring_ids)) {
                    $error = true;
                }
            }

            if (!$error) {
                $this->trackProductToCart();
            }
        }
    }

    /**
     * Track product to wishlist
     *
     * - catalog/controller/account/wishlist/add/after
     */
    public function addProductToWishlist(&$route, &$data, &$output)
    {
        if (!$this->moduleIsEnabled) { return; }

        $product_id = isset($this->request->post['product_id']) ? $this->request->post['product_id'] : 0;

        if ($product_id) {
            $product_info = $this->model_catalog_product->getProduct($product_id);

            $this->dbAddData(array(
                'ianalytics_product_add_to_wishlist' => array (
                    'date'                 => date('Y-m-d'),
                    'time'                 => date('H:i:s'),
                    'from_ip'              => $this->ipAddress,
                    'spoken_languages'     => $this->userLanguage,
                    'product_id'           => $product_id,
                    'product_name'         => $product_info['name'],
                    'product_model'        => $product_info['model'],
                    'product_price'        => $product_info['price'],
                    'product_quantity'     => $product_info['quantity'],
                    'product_stock_status' => $product_info['stock_status'],
                    'store_id'             => $this->config->get('config_store_id'),
                )
            ));
        }
    }

    /**
     * Funnel login register
     *
     * - catalog/controller/account/login/before
     */
    public function addFunnelLoginRegister(&$route, &$data)
    {
        if (!$this->moduleIsEnabled) { return; }

        if ($this->request->server['REQUEST_METHOD'] == 'POST') {
            $this->load->model('account/customer');

            // Replicate validate but do NOT login
            $error = false;
            $login_info = $this->model_account_customer->getLoginAttempts($this->request->post['email']);
            if ($login_info && ($login_info['total'] >= $this->config->get('config_login_attempts')) && strtotime('-1 hour') < strtotime($login_info['date_modified'])) {
                $error = true;
            }

            // Check if customer has been approved.
            $customer_info = $this->model_account_customer->getCustomerByEmail($this->request->post['email']);
            if ($customer_info && !$customer_info['status']) {
                $error = true;
            }

            if (!$error && $this->moduleTrackAfterSale) {
                $this->trackFunnel(2);
            }
        }
    }

    /**
     * Funnel login register for checkout
     *
     * - catalog/controller/checkout/guest/save/after
     * - catalog/controller/checkout/login/save/after
     * - catalog/controller/checkout/register/save/after
     */
    public function addFunnelLoginRegisterCheckout(&$route, &$data, &$output)
    {
        if (!$this->moduleIsEnabled) { return; }

        if ($this->moduleTrackAfterSale) {
            $this->trackFunnel(2);
        }
    }

    /**
     * Funnel shipping method for checkout
     *
     * - catalog/controller/checkout/shipping_method/save/after
     */
    public function addFunnelCheckoutShipping(&$route, &$data, &$output)
    {
        if (!$this->moduleIsEnabled) { return; }

        if ($this->moduleTrackAfterSale) {
            $this->trackFunnel(3);
        }
    }

    /**
     * Funnel payment method for checkout
     *
     * - catalog/controller/checkout/payment_method/save/after
     */
    public function addFunnelCheckoutPayment(&$route, &$data, &$output)
    {
        if (!$this->moduleIsEnabled) { return; }

        if ($this->moduleTrackAfterSale) {
            $this->trackFunnel(4);
        }
    }

    /**
     * Funnel checkout confirm
     *
     * - catalog/controller/checkout/confirm/after
     */
    public function addFunnelCheckoutConfirm(&$route, &$data, &$output)
    {
        if (!$this->moduleIsEnabled) { return; }

        if ($this->moduleTrackAfterSale) {
            if ($route == 'journal3/checkout/save') {
                if (!empty($this->request->get['confirm']) && $this->request->get['confirm'] == 'true') {
                    $this->trackFunnel(5);
                }
            } else {
                $this->trackFunnel(5);
            }
        }
    }

    /**
     * Funnel checkout Success
     *
     * - catalog/controller/checkout/success/before
     */
    public function addFunnelCheckoutSuccess(&$route, &$data)
    {
        if (!$this->moduleIsEnabled) { return; }

        if (isset($this->session->data['order_id']) && !isset($this->session->data['ianalytic_order_id'])) {
            $this->session->data['ianalytic_order_id'] = $this->session->data['order_id'];
            if ($this->moduleTrackAfterSale) {
                $this->trackFunnel(6);
            }
        }
    }

    /**
     * GA Commerce script
     *
     * - catalog/view/common/success/after
     * - catalog/view/common/ordersuccesspage/after
     * - catalog/view/common/ordersuccesspage_journal/after
     */
    public function addGAScript(&$route, &$data, &$output)
    {
        if (!$this->moduleIsEnabled) { return; }

        $settings = $this->moduleSettings;
        $order_id = isset($this->session->data['ianalytic_order_id']) ? $this->session->data['ianalytic_order_id'] : 0;
        $chunk = '';

        unset($this->session->data['ianalytic_order_id']);

        $valid = false;
        if ($order_id && $settings['GoogleAnalytics'] == 'yes' && isset($settings['GoogleAnalyticsIDNumber'])) {
            $valid = true;
        }

        // Proceed if valid
        if (!$valid) { return; }

        // Preparation
        $this->load->model('account/order');

        $data['order_id']       = $order_id;
        $data['store_name']     = $this->config->get('config_name');
        $data['order_info']     = $this->model_account_order->getOrder($order_id);
        $data['order_products'] = $this->model_account_order->getOrderProducts($order_id);

        if (!$data['order_info']) { return; }

        $tax = 0;
        foreach ($data['order_products'] as $row) {
            $tax = $tax + $row['tax'];
        }
        $data['tax'] = $tax;

        // Output script
        if ($settings['GoogleAnalyticsTracking'] == 'ga4_tracking') {
            $chunk .= '<script async src="https://www.googletagmanager.com/gtag/js?id=' . $settings['GoogleAnalyticsIDNumber'] . '"></script>' . PHP_EOL;
            $chunk .= "<script>" . PHP_EOL;
            $chunk .= "window.dataLayer = window.dataLayer || [];" . PHP_EOL;
            $chunk .= "function gtag(){dataLayer.push(arguments);}" . PHP_EOL;
            $chunk .= "gtag('js', new Date());" . PHP_EOL;
            $chunk .= "gtag('config', '" . $settings['GoogleAnalyticsIDNumber'] . "');" . PHP_EOL;
            $chunk .= "" . PHP_EOL;
            
            $chunk .= 'gtag("event", "purchase", {' . PHP_EOL;
            $chunk .= '  transaction_id: "' . $order_id . '",' . PHP_EOL;
            $chunk .= '  value: ' . $data['order_info']['total'] . ',' . PHP_EOL;
            $chunk .= '  tax: ' . $data['tax'] . ',' . PHP_EOL;
            $chunk .= '  currency: "' . $data['order_info']['currency_code'] . '",' . PHP_EOL;
            $chunk .= '  items: [' . PHP_EOL;

            foreach ($data['order_products'] as $key => $item) {
                $chunk .= '  {' . PHP_EOL;
                $chunk .= '    index: ' . $key . ',' . PHP_EOL;
                $chunk .= '    item_id: "' . $item['product_id'] . '",' . PHP_EOL;
                $chunk .= '    item_name: "' . $item['name'] . '",' . PHP_EOL;
                $chunk .= '    price: ' . $item['price'] . ',' . PHP_EOL;
                $chunk .= '    quantity: ' . $item['quantity'] . ',' . PHP_EOL;

                if (!empty($data['order_products'][$key+1])) {
                    $chunk .= '  },' . PHP_EOL;
                } else {
                    $chunk .= '  }' . PHP_EOL;
                }
            }

            $chunk .= '  ]' . PHP_EOL;
            $chunk .= '});' . PHP_EOL;
            $chunk .= "</script>" . PHP_EOL;

        } elseif ($settings['GoogleAnalyticsTracking'] == 'ec_tracking') {

            $chunk  = "<script>" . PHP_EOL;
            $chunk .= "if (typeof ga === 'undefined') {" . PHP_EOL;
            $chunk .= "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){" . PHP_EOL;
            $chunk .= "(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o)," . PHP_EOL;
            $chunk .= "m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)" . PHP_EOL;
            $chunk .= "})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');" . PHP_EOL;
            $chunk .= "ga('create', '". $settings['GoogleAnalyticsIDNumber'] . "', 'auto');" . PHP_EOL;
            $chunk .= "}" . PHP_EOL;

            $chunk .= "ga('require', 'ec');" . PHP_EOL;
            foreach ($data['order_products'] as $row) {
                $chunk .= "ga('ec:addProduct', {" . PHP_EOL;
                $chunk .= "  'id': '" . $row['product_id'] . "'," . PHP_EOL;        // Product ID (string).
                $chunk .= "  'name': '" . $row['name'] . "'," . PHP_EOL;            // Product name (string).
                $chunk .= "  'category':  ''," . PHP_EOL;                           // Product category (string).
                $chunk .= "  'brand':  ''," . PHP_EOL;                              // Product brand (string).
                $chunk .= "  'variant': '" . $row['model'] . "'," . PHP_EOL;        // Product variant (string).
                $chunk .= "  'price': '" . $row['price'] . "'," . PHP_EOL;          // Product price (currency).
                $chunk .= "  'coupon':  ''," . PHP_EOL;                             // Product coupon (string).
                $chunk .= "  'quantity': '" . $row['quantity'] . "'" . PHP_EOL;     // Product quantity (number).
                $chunk .= "});" . PHP_EOL;
            }
            $chunk .= "ga('ec:setAction', 'purchase', {" . PHP_EOL;
            $chunk .= "  'id': '" . $order_id . "'," . PHP_EOL;                         // (Required) Transaction id (string).
            $chunk .= "  'affiliation': ''," . PHP_EOL;                                 // Affiliation (string).
            $chunk .= "  'revenue': '" . $data['order_info']['total'] . "'," . PHP_EOL; // Revenue (currency).
            $chunk .= "  'tax': '" . $data['tax'] . "'," . PHP_EOL;                     // Tax (currency).
            $chunk .= "  'shipping': ''," . PHP_EOL;                                    // Shipping (currency).
            $chunk .= "  'coupon': ''," . PHP_EOL;                                      // Transaction coupon (string).
            $chunk .= "});" . PHP_EOL;
            $chunk .= "ga('send', 'event', 'placed a new order','" . $order_id . "', '');" . PHP_EOL;
            $chunk .= "</script>" . PHP_EOL;

        } elseif ($settings['GoogleAnalyticsTracking'] == 'regular_tracking') {

            $chunk  = "<script>" . PHP_EOL;
            $chunk  .= "var _gaq = _gaq || [];" . PHP_EOL;
            $chunk  .= "_gaq.push(['_setAccount', '" . $settings['GoogleAnalyticsIDNumber'] . "']);" . PHP_EOL;
            $chunk  .= "_gaq.push(['_set', 'currencyCode', '" . $data['order_info']['currency_code'] . "']);" . PHP_EOL;
            $chunk  .= "_gaq.push(['_trackPageview']);" . PHP_EOL;
            $chunk  .= "_gaq.push(['_addTrans'," . PHP_EOL;
            $chunk  .= "  '" . $order_id . "'," . PHP_EOL;                               // Transaction ID *
            $chunk  .= "  '" . $data['store_name'] . "'," . PHP_EOL;                     // Store Name
            $chunk  .= "  '" . $data['order_info']['total'] . "'," . PHP_EOL;            // Cart Total
            $chunk  .= "  '" . $data['tax'] . "'," . PHP_EOL;                            // Tax
            $chunk  .= "  '" . $data['order_info']['shipping_city'] . "'," . PHP_EOL;    // City
            $chunk  .= "  '" . $data['order_info']['shipping_zone'] . "'," . PHP_EOL;    // State/Province
            $chunk  .= "  '" . $data['order_info']['shipping_country'] . "'" . PHP_EOL;  // Country
            $chunk  .= "]);" . PHP_EOL;

            foreach ($data['order_products'] as $row) {
                $chunk .= "_gaq.push(['_addItem'," . PHP_EOL;
                $chunk .= "  '" . $order_id . "'," . PHP_EOL;           // Transaction ID *
                $chunk .= "  '" . $row['model'] . "'," . PHP_EOL;       // SKU/Code *
                $chunk .= "  '" . $row['name'] . "'," . PHP_EOL;        // Product Name
                $chunk .= "  ''," . PHP_EOL;                            // Category
                $chunk .= "  '" . $row['price'] . "'," . PHP_EOL;       // Price *
                $chunk .= "  '" . $row['quantity'] . "'," . PHP_EOL;    // Quantity *
                $chunk .= "]);" . PHP_EOL;
            }

            $chunk .= "_gaq.push(['_trackTrans']);" . PHP_EOL;
            $chunk .= "(function() { var ga = document.createElement('script'); ga.type = 'text/javascript'; ga.async = true; ga.src = ('https:' == document.location.protocol ? 'https://ssl' : 'http://www') + '.google-analytics.com/ga.js'; var s = document.getElementsByTagName('script')[0]; s.parentNode.insertBefore(ga, s); })();" . PHP_EOL;
            $chunk .= "</script>" . PHP_EOL;
        }

        $output = str_replace('</body>', $chunk . '</body>', $output);
    }
}
