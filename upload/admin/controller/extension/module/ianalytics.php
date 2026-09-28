<?php
class ControllerExtensionModuleIanalytics extends Controller
{
    private $moduleName;
    private $modulePath;
    private $moduleModel;
    private $moduleVersion;
    private $extensionsLink;
    private $callModel;
    private $error = array();
    private $data = array();

    public function __construct($registry)
    {
        parent::__construct($registry);

        // Config Loader
        $this->config->load('isenselabs/ianalytics');

        // Module Constants
        $this->moduleName     = $this->config->get('ianalytics_name');
        $this->callModel      = $this->config->get('ianalytics_model');
        $this->modulePath     = $this->config->get('ianalytics_path');
        $this->moduleVersion  = $this->config->get('ianalytics_version');
        $this->extensionsLink = $this->url->link('marketplace/extension', 'type=module&user_token=' . $this->session->data['user_token'], true);

        // Load Language
        $this->load->language($this->modulePath);

        // Load Model
        $this->load->model($this->modulePath);

        // Model Instance
        $this->moduleModel        = $this->{$this->callModel};

        // Global Variables
        $this->data['moduleName'] = $this->moduleName;
        $this->data['modulePath'] = $this->modulePath;

        $this->data['limit']      = 15;
    }

    public function index()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/store');
        $this->load->model('localisation/order_status');

        $store_id = $this->getStoreId();

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && $this->validateForm()) {
            if (!empty($_POST['OaXRyb1BhY2sgLSBDb21'])) {
                $this->request->post[$this->moduleName]['LicensedOn'] = $_POST['OaXRyb1BhY2sgLSBDb21'];
            }
            if (!empty($_POST['cHRpbWl6YXRpb24ef4fe'])) {
                $this->request->post[$this->moduleName]['License'] = json_decode(base64_decode($_POST['cHRpbWl6YXRpb24ef4fe']), true);
            }

            $post = $this->request->post;
            $post['module_' . $this->moduleName . '_status'] = 0;
            $post['module_ianalytics_setting'] = $post['ianalytics'];

            $this->moduleModel->removeEvents();
            if ($post[$this->moduleName]['Enabled'] == 'yes') {
                $this->moduleModel->addEvents();
                $post['module_' . $this->moduleName . '_status'] = 1;
            }

            $this->model_setting_setting->editSetting('module_' . $this->moduleName, $post, $store_id);

            $this->session->data['success'] = $this->language->get('text_success');
            //$this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true));
        }

        //=== Documents
        $this->document->addStyle('view/stylesheet/' . $this->moduleName . '/' . $this->moduleName . '.css');
        $this->document->addStyle('view/javascript/' . $this->moduleName . '/jquery/css/ui-lightness/jquery-ui-1.9.2.custom.min.css');
        $this->document->addScript('view/javascript/' . $this->moduleName . '/jquery/js/jquery-ui-1.9.2.custom.min.js');
        $this->document->addScript('view/javascript/' . $this->moduleName . '/charts/Chart.js');
        $this->document->addScript('view/javascript/' . $this->moduleName . '/d3.v2.min.js');
        $this->document->addScript('view/javascript/' . $this->moduleName . '/d3-funnel-charts.min.js');
        $this->document->addScript('view/javascript/' . $this->moduleName . '/' . $this->moduleName . '.js');

        $this->document->setTitle($this->language->get('heading_title'));

        // Breadcrumbs
        $this->data['breadcrumbs']    = array();
        $this->data['breadcrumbs'][]  = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'user_token=' . $this->session->data['user_token'], true)
        );
        $this->data['breadcrumbs'][]  = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->extensionsLink
        );
        $this->data['breadcrumbs'][]  = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true)
        );

        // Notification
        $this->data['success'] = '';
        if (isset($this->session->data['success'])) {
            $this->data['success'] = $this->session->data['success'];
            unset($this->session->data['success']);
        }
        $this->data['error_warning'] = '';
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        }

        //=== Contents
        $this->language->load($this->modulePath);
        $language_strings = $this->language->load($this->modulePath);
        foreach ($language_strings as $code => $languageVariable) {
            $this->data[$code] = $languageVariable;
        }
        $this->data['heading_title']        .= ' ';

        $this->data['cancel']               = $this->extensionsLink;
        $this->data['action']               = $this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true);
        $this->data['user_token']           = $this->session->data['user_token'];

        $moduleSetting = array_merge(
            $this->model_setting_setting->getSetting('module_' . $this->moduleName, $store_id),
            $this->model_setting_setting->getSetting('module_' . $this->moduleName . '_state', $store_id)
        );
        $this->data['moduleData']           = isset($moduleSetting['module_ianalytics_setting']) ? $moduleSetting['module_ianalytics_setting'] : array();
        $this->data['ianalytics_isrun']     = isset($moduleSetting['module_ianalytics_state_isrun']) ? (bool)$moduleSetting['module_ianalytics_state_isrun'] : false;

        $this->data['store_id']             = (int)$store_id;
        $this->data['store']                = $this->getCurrentStore($store_id);
        $this->data['stores']               = array_merge(array(0 => array('store_id' => '0', 'name' => $this->config->get('config_name') . ' ' . $this->data['text_default'].' ', 'url' => HTTP_SERVER, 'ssl' => HTTPS_SERVER)), $this->model_setting_store->getStores());
        $this->data['order_statuses']       = $this->model_localisation_order_status->getOrderStatuses();

        $this->data['unlicensedHtml']       = empty($this->data['moduleData']['LicensedOn']) ? base64_decode('ICAgIDxkaXYgY2xhc3M9ImFsZXJ0IGFsZXJ0LWRhbmdlciBmYWRlIGluIj4NCiAgICAgICAgPGJ1dHRvbiB0eXBlPSJidXR0b24iIGNsYXNzPSJjbG9zZSIgZGF0YS1kaXNtaXNzPSJhbGVydCIgYXJpYS1oaWRkZW49InRydWUiPsOXPC9idXR0b24+DQogICAgICAgIDxoND5XYXJuaW5nISBVbmxpY2Vuc2VkIHZlcnNpb24gb2YgdGhlIG1vZHVsZSE8L2g0Pg0KICAgICAgICA8cD5Zb3UgYXJlIHJ1bm5pbmcgYW4gdW5saWNlbnNlZCB2ZXJzaW9uIG9mIHRoaXMgbW9kdWxlISBZb3UgbmVlZCB0byBlbnRlciB5b3VyIGxpY2Vuc2UgY29kZSB0byBlbnN1cmUgcHJvcGVyIGZ1bmN0aW9uaW5nLCBhY2Nlc3MgdG8gc3VwcG9ydCBhbmQgdXBkYXRlcy48L3A+PGRpdiBzdHlsZT0iaGVpZ2h0OjVweDsiPjwvZGl2Pg0KICAgICAgICA8YSBjbGFzcz0iYnRuIGJ0bi1kYW5nZXIiIGhyZWY9ImphdmFzY3JpcHQ6dm9pZCgwKSIgb25jbGljaz0iJCgnYVtocmVmPSNpc2Vuc2Vfc3VwcG9ydF0nKS50cmlnZ2VyKCdjbGljaycpIj5FbnRlciB5b3VyIGxpY2Vuc2UgY29kZTwvYT4NCiAgICA8L2Rpdj4=') : '';
        $this->data['licenseDataBase64']    = !empty($this->data['moduleData']['License']) ? base64_encode(json_encode($this->data['moduleData']['License'])) : '';
        $this->data['supportTicketLink']    = 'http://isenselabs.com/tickets/open/' . base64_encode('Support Request').'/'.base64_encode('31').'/'. base64_encode($_SERVER['SERVER_NAME']);

        // Tab data
        $this->data = $this->moduleModel->getAnalyticsData($this->data, $store_id);

        $this->data['reqGet']               = array_merge(array('filterOrders' => 0, 'filterGroup' => 'day'), $_GET);
        $this->data['element_filter']       = $this->load->view($this->modulePath . '/element_filter', $this->data);
        $this->data['report_sales_filter']  = $this->load->view($this->modulePath . '/report_sales_filter', $this->data);
        $this->data['report_sales']         = $this->load->view($this->modulePath . '/report_sales', $this->data);
        $this->data['report_customer_orders']          = $this->load->view($this->modulePath . '/report_customer_orders', $this->data);
        $this->data['report_product_purchased']        = $this->load->view($this->modulePath . '/report_product_purchased', $this->data);
        $this->data['report_product_purchased_filter'] = $this->load->view($this->modulePath . '/report_product_purchased_filter', $this->data);

        $this->data['tab_dashboard']        = $this->load->view($this->modulePath . '/tab_dashboard', $this->data);
        $this->data['tab_aftersale']        = $this->load->view($this->modulePath . '/tab_aftersale', $this->data);
        $this->data['tab_presale']          = $this->load->view($this->modulePath . '/tab_presale', $this->data);
        $this->data['tab_visitors']         = $this->load->view($this->modulePath . '/tab_visitors', $this->data);
        $this->data['tab_controlpanel']     = $this->load->view($this->modulePath . '/tab_controlpanel', $this->data);
        $this->data['tab_support']          = $this->load->view($this->modulePath . '/tab_support', $this->data);

        $this->data['column_left']          = $this->load->controller('common/column_left');
        $this->data['footer']               = $this->load->controller('common/footer');
        $this->data['header']               = $this->load->controller('common/header');

        $this->response->setOutput($this->load->view($this->modulePath . '/'.$this->moduleName, $this->data));
    }

    protected function validateForm()
    {
        if (!$this->user->hasPermission('modify', $this->modulePath)) {
            $this->error['warning'] = $this->language->get('error_permission');
        }
        return !$this->error;
    }

    public function pausegatheringdata()
    {
        $this->load->model('setting/setting');

        $store_id = $this->getStoreId();

        $this->model_setting_setting->editSetting('module_' . $this->moduleName . '_state', array('module_' . $this->moduleName . '_state_isrun' => '0'), $store_id);

        $this->session->data['success'] = 'iAnalytics data gathering is now <strong>paused</strong>.';

        $this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true));
    }

    public function resumegatheringdata()
    {
        $this->load->model('setting/setting');

        $store_id = $this->getStoreId();

        $this->model_setting_setting->editSetting('module_' . $this->moduleName . '_state', array('module_' . $this->moduleName . '_state_isrun' => '1'), $store_id);
        $this->session->data['success'] = 'iAnalytics data gathering is now <strong>resumed</strong>.';
        $this->response->redirect($this->url->link($this->modulePath, 'store_id=' .$store_id . '&user_token=' . $this->session->data['user_token'], true));
    }

    public function deletesearchkeyword()
    {
        $store_id = $this->getStoreId();

        if (!$this->validateForm()) {
            $this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true));
        }

        if (!empty($_GET['searchValue'])) {
            $this->moduleModel->deleteSearchKeyword($_GET['searchValue'], $store_id);

            $this->session->data['success'] = $this->language->get('deleted_keyword');
        }

        $this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'] . '&tab=1&searchTab=1', true));
    }

    public function deleteallsearchkeyword()
    {
        $store_id = $this->getStoreId();

        if (!$this->validateForm()) {
            $this->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true));
        }

        if (!empty($_GET['searchValue'])) {
            $this->moduleModel->deleteAllSearchKeyword($_GET['searchValue'], $store_id);

            $this->session->data['success'] = $this->language->get('deleted_keyword');
        }

        $this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true));
    }

    public function deleteanalyticsdata()
    {
        $store_id = $this->getStoreId();

        if (!$this->validateForm()) {
            $this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true));
        }

        $this->moduleModel->deleteAnalyticsData($store_id);

        $this->session->data['success'] = $this->language->get('deleted_analytics_data');

        $this->response->redirect($this->url->link($this->modulePath, 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'], true));
    }


    //====================================================================================
    // Tools
    //====================================================================================

    private function getStoreId()
    {
        $store_id = 0;

        if (isset($this->request->get['store_id'])) {
            $store_id = $this->request->get['store_id'];
        } elseif (isset($this->request->post['store_id'])) {
            $store_id = $this->request->post['store_id'];
        } else {
            $store_id = 0;
        }

        return $store_id;
    }

    private function getCurrentStore($store_id)
    {
        if ($store_id && $store_id != 0) {
            $store = $this->model_setting_store->getStore($store_id);
        } else {
            $store['store_id'] = 0;
            $store['name'] = $this->config->get('config_name');
            $store['url'] = $this->getCatalogURL();
        }
        return $store;
    }

    private function getCatalogURL()
    {
        if (isset($_SERVER['HTTPS']) && (($_SERVER['HTTPS'] == 'on') || ($_SERVER['HTTPS'] == '1'))) {
            $storeURL = HTTPS_CATALOG;
        } else {
            $storeURL = HTTP_CATALOG;
        }
        return $storeURL;
    }


    //====================================================================================
    // Internals
    //====================================================================================

    public function install()
    {
        $this->load->model('setting/setting');
        $this->load->model('setting/store');

        $this->data['stores'] = array_merge(array(0 => array('store_id' => '0', 'name' => $this->config->get('config_name') . '(Default)', 'url' => HTTP_SERVER, 'ssl' => HTTPS_SERVER)), $this->model_setting_store->getStores());

        foreach ($this->data['stores'] as $store) {
            $this->model_setting_setting->editSetting('module_' . $this->moduleName . '_state', array('module_' . $this->moduleName . '_state_isrun' => '1'), $store['store_id']);
        }

        $this->moduleModel->install();
    }

    public function uninstall()
    {
        $this->load->model('setting/setting');

        $store_id = $this->getStoreId();

        $this->model_setting_setting->deleteSetting('module_' . $this->moduleName, $store_id);
        $this->model_setting_setting->deleteSetting('module_' . $this->moduleName . '_state', $store_id);

        $this->moduleModel->uninstall();
    }

    //====================================================================================
    // Events
    //====================================================================================

    /**
     * Add iAnalytics menu to column left
     *
     * - admin/view/common/column_left/before
     */
    public function addMenuColumnLeft(&$route, &$data)
    {
        $data['menus'][] = array(
            'id'       => 'menu-ianalytics',
            'icon'     => 'fa fa-pie-chart fa-fw',
            'name'     => 'iAnalytics',
            'href'     => $this->url->link('extension/module/ianalytics', 'user_token=' . $this->session->data['user_token'], true),
            'children' => array()
        );
    }
}
