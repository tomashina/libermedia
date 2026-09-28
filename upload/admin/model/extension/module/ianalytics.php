<?php
class ModelExtensionModuleIanalytics extends Model
{
    public $data;

    public function getAnalyticsData($mydata, $store_id = 0)
    {
        $this->data = $mydata;
        $defRange   = date('Y-m-d', strtotime('-29 days'));
        $minDate    = $this->findMinDate($defRange, $store_id);
        $fromDate   = !empty($_GET['fromDate']) && $this->validateDate($_GET['fromDate']) ? $_GET['fromDate'] : $minDate;
        $toDate     = !empty($_GET['toDate']) && $this->validateDate($_GET['toDate']) ? $_GET['toDate'] : date('Y-m-d');

        $this->data['iAnalyticsMinDate']    = $minDate;
        $this->data['iAnalyticsFromDate']   = $fromDate;
        $this->data['iAnalyticsToDate']     = $toDate;
        $this->data['iAnalyticsSelectData'] = $this->getFilterState(time(), $fromDate, $toDate, $minDate);

        $this->data['tab'] = array();
        $this->data['tab']['presale']   = $this->tabPresale($store_id);
        $this->data['tab']['aftersale'] = $this->tabAftersale($store_id);
        $this->data['tab']['visitors']  = $this->tabVisitors($store_id);

        return $this->data;
    }

    public function tabPresale($store_id)
    {
        $response = array();

        $monthlySearchesTable = $this->getMonthlySearchesTable($store_id);
        $response['monthly_search'] = array(
            'labels' => $this->chartData($monthlySearchesTable['chart'], 0),
            'data1'  => $this->chartData($monthlySearchesTable['chart'], 2),
            'data2'  => $this->chartData($monthlySearchesTable['chart'], 3),
            'table'  => $monthlySearchesTable['table']
        );
        // Manipulate to fix single data issue on chart
        if (!isset($monthlySearchesTable['chart'][2])) {
            $response['monthly_search']['labels'] = str_replace('["', '["#", "', $response['monthly_search']['labels']);
            $response['monthly_search']['data1'] = str_replace('["', '["0", "', $response['monthly_search']['data1']);
            $response['monthly_search']['data2'] = str_replace('["', '["0", "', $response['monthly_search']['data1']);
        }

        $keywordSearchHistory = $this->getKeywordSearchHistory(80, $store_id);
        $response['keyword_search'] = array(
            'table'  => $keywordSearchHistory
        );

        $mostSearchedKeywords    = $this->getMostSearchedKeywords($store_id);
        $mostSearchedKeywordsPie = $this->getMostSearchedKeywordsPie($store_id);
        $response['most_search'] = array(
            // dashboard chart pie
            'chart_status' => !isset($mostSearchedKeywordsPie['No Data Gathered Yet']),
            'chart'  => $mostSearchedKeywordsPie,
            'table'  => $mostSearchedKeywords
        );

        $mostOpenedProducts = $this->getMostOpenedProducts($store_id);
        $response['product_open'] = array(
            'table'  => $mostOpenedProducts
        );

        $mostAddedtoCartProducts = $this->getMostAddedtoCartProducts($store_id);
        $response['product_cart'] = array(
            'table'  => $mostAddedtoCartProducts
        );

        $mostAddedtoWishlistProducts = $this->getMostAddedtoWishlistProducts($store_id);
        $response['product_wishlist'] = array(
            'table'  => $mostAddedtoWishlistProducts
        );

        $mostComparedProducts = $this->getMostComparedProducts($store_id);
        $response['product_compare'] = array(
            'status' => is_array($mostComparedProducts[0]),
            'table'  => $mostComparedProducts
        );

        return $response;
    }

    public function tabAftersale($store_id)
    {
        $response = array();

        $funnelData = $this->getFunnelData($store_id);
        $response['funnel'] = array(
            'status' => isset($funnelData[2][0]),
            'rate'   => 'N/A',
            'table'  => $funnelData
        );

        if ($response['funnel']['status']) {
            $min = $funnelData[1][1];
            $max = isset($funnelData[7][1]) ? $funnelData[7][1] : 0;
            if ($min > 0 && $max > 0) {
                $response['funnel']['rate'] = number_format((($max / $min) * 100), 2) . '%';
            }
        }

        $salesReportsData = $this->getSalesReportData($store_id);
        $response['sales_report'] = array(
            'status'      => !empty($salesReportsData['orders']),
            'orders'      => $salesReportsData['orders'],
            'dataTotal'   => $this->chartData($salesReportsData['orders'], 'total_nocurrency', false),
            'dataRevenue' => $this->chartData($salesReportsData['orders'], 'revenue_nocurrency', false),
            'dataTaxes'   => $this->chartData($salesReportsData['orders'], 'tax_nocurrency', false),
            'table'       => array_reverse($salesReportsData['orders'], true),
            'pagination'  => $salesReportsData['pagination']
        );
        // Manipulate to fix single data issue on chart
        if ($response['sales_report']['status'] && !isset($salesReportsData['orders'][1])) {
            array_unshift($response['sales_report']['orders'], array('date_start' => '#', 'date_end' => '#'));
            $response['sales_report']['dataTotal'] = str_replace('["', '["0", "', $response['sales_report']['dataTotal']);
            $response['sales_report']['dataRevenue'] = str_replace('["', '["0", "', $response['sales_report']['dataRevenue']);
            $response['sales_report']['dataTaxes'] = str_replace('["', '["0", "', $response['sales_report']['dataTaxes']);
        }

        $mostOrderProductData = $this->getMostOrderProductData($store_id);
        $response['most_product_order'] = array(
            'status'      => !empty($mostOrderProductData['products']),
            'labels'      => $this->chartData($mostOrderProductData['products'], 'name', false),
            'data'        => $this->chartData($mostOrderProductData['products'], 'quantity', false),
            'table'       => $mostOrderProductData['products'],
            'pagination'  => $mostOrderProductData['pagination']
        );

        $customerOrdersData = $this->getCustomerOrdersData($store_id);
        $response['customer_orders'] = array(
            'status'      => !empty($customerOrdersData['customers']),
            'labels'      => $this->chartData($customerOrdersData['customers'], 'customer', false),
            'data'        => $this->chartData($customerOrdersData['customers'], 'orders', false),
            'table'       => $customerOrdersData['customers'],
            'pagination'  => $customerOrdersData['pagination']
        );

        return $response;
    }

    public function tabVisitors($store_id)
    {
        $response = array();

        $visitorsDataByDay = $this->getVisitorsDataByDay('date', $store_id);
        $response['daily_unique'] = array(
            'status' => is_array($visitorsDataByDay[0]),
            'labels' => $this->chartData($visitorsDataByDay, 0),
            'data'   => $this->chartData($visitorsDataByDay, 1),
            'table'  => array_reverse($visitorsDataByDay),
            'count'  => count($visitorsDataByDay)
        );
        // Manipulate to fix single data issue on chart
        if ($response['daily_unique']['status'] && !isset($visitorsDataByDay[2])) {
            $response['daily_unique']['labels'] = str_replace('["', '["#", "', $response['daily_unique']['labels']);
            $response['daily_unique']['data'] = str_replace('["', '["0", "', $response['daily_unique']['data']);
        }

        $visitorsData = $this->getVisitorsData('stage', $store_id);
        $response['daily_parts'] = array(
            'status' => is_array($visitorsData[0]),
            'labels' => $this->chartData($visitorsData, 0),
            'data'   => $this->chartData($visitorsData, 1),
            'table'  => $visitorsData
        );
        // Manipulate to fix single data issue on chart
        if ($response['daily_parts']['status'] && !isset($visitorsData[2])) {
            $response['daily_parts']['labels'] = str_replace('["', '["#", "', $response['daily_parts']['labels']);
            $response['daily_parts']['data'] = str_replace('["', '["0", "', $response['daily_parts']['data']);
        }

        $visitorsDataReferers = $this->getVisitorsDataReferers('date', $store_id);
        $visitorsDataReferersPie = $this->getVisitorsDataReferersPie('', $store_id);
        $response['traffic_sources'] = array(
            'status' => is_array($visitorsDataReferers[0]),
            'data'   => is_array($visitorsDataReferers[0]) ? $visitorsDataReferersPie[1] : array('0', '0', '0', '0'),
            'table'  => $visitorsDataReferers
        );

        return $response;
    }

    public function deleteSearchKeyword($id, $store_id = 0)
    {
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_search_data WHERE id = "' . $id . '" AND store_id="' . $store_id . '"');
    }

    public function deleteAllSearchKeyword($value, $store_id = 0)
    {
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_search_data WHERE search_value = "' . $value . '" AND store_id="' . $store_id . '"');
    }

    public function deleteAnalyticsData($store_id = 0)
    {
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_product_comparisons WHERE store_id="' . $store_id . '"');
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_product_opens WHERE store_id="' . $store_id . '"');
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_search_data WHERE store_id="' . $store_id . '"');
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_product_add_to_cart WHERE store_id="' . $store_id . '"');
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_product_add_to_wishlist WHERE store_id="' . $store_id . '"');
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_funnel_data WHERE store_id="' . $store_id . '"');
        $this->db->query('DELETE FROM ' . DB_PREFIX . 'ianalytics_visits_data WHERE store_id="' . $store_id . '"');
    }


    // =================================================================================
    // Data Queries
    // =================================================================================

    // Presale
    // ==========================================

    private function getMonthlySearchesTable($store_id = 0)
    {
        $columns = array(array('Day','Total Search Queries','Successful Search Queries','Zero-Results Search Queries'));
        $items   = array();

        for ($i=$this->data['iAnalyticsToDate']; strcmp($i, $this->data['iAnalyticsFromDate']) >= 0; $i = date('Y-m-d', strtotime($i) - 43201)) {
            $succeeded = $this->getNumberSearchesByDay($i, 'success', $store_id);
            $failed = $this->getNumberSearchesByDay($i, 'fail', $store_id);
            $items[] = array(date("j-n-Y", strtotime($i)), (string)($succeeded+$failed), (string)$succeeded, (string)$failed);
        }

        $result = array(
            'table' => array_merge($columns, $items),
            'chart' => array_merge($columns, array_reverse($items))
        );

        return $result;
    }

    private function getKeywordSearchHistory($limit = 80, $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();
        $result = $this->db->query('SELECT * FROM ' . DB_PREFIX . 'ianalytics_search_data
                        WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . '
                            `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        ORDER BY `date` DESC, `time` DESC
                        LIMIT 0, '.$limit);

        if ($result->num_rows == 0) {
            return array(0 => 'No Data Gathered Yet');
        } else {
            $k = array(array('Keyword','Date','Time','Results Found','User Language','IP address','ID'));
            foreach ($result->rows as $i => $search) {
                array_push($k, array($search['search_value'],$search['date'],$search['time'],$search['search_results'],$search['spoken_languages'],$search['from_ip'],$search['id']));
            }
        }

        return $k;
    }

    private function getMostSearchedKeywords($store_id = 0)
    {
        $k = array(array('Keyword','Searches'));
        $temp = $this->getMostSearchedKeywordsRaw(80, true, $store_id);

        foreach ($temp as $key => $value) {
            array_push($k, array($key,$value));
        }

        return $k;
    }

    private function getMostSearchedKeywordsPie($store_id = 0) {
        $keywords = $this->getMostSearchedKeywordsRaw(7,true,$store_id);
        $keys = array_keys($keywords);
        $values = array_values($keywords);
        $pattern = array();
        $i = 0;
        foreach (array_combine($keys, $values) as $key => $val) {
            $pattern[$key] = array($val, $i);
            $i++;
        }
        return $pattern;
    }

    private function getMostOpenedProducts($store_id = 0)
    {
        $k = array(array('Product','Opens'));
        $temp = $this->getMostOpenedProductsRaw('product_name', $store_id);
        foreach ($temp as $key => $value) {
            array_push($k, array($key,$value));
        }

        return $k;
    }

    private function getMostAddedtoCartProducts($store_id = 0)
    {
        $k = array(array('Product','Added to Cart'));
        $temp = $this->getMostAddedtoCartProductsRaw('product_name', $store_id);
        foreach ($temp as $key => $value) {
            array_push($k, array($key,$value));
        }

        return $k;
    }

    private function getMostAddedtoWishlistProducts($store_id = 0)
    {
        $k = array(array('Product','Added to Wishlist'));
        $temp = $this->getMostAddedtoWishlistProductsRaw('product_name', $store_id);
        foreach ($temp as $key => $value) {
            array_push($k, array($key,$value));
        }

        return $k;
    }

    private function getMostComparedProducts($store_id = 0)
    {
        $k = array(array('Products','Comparisons'));
        $temp = $this->getMostComparedProductsRaw($store_id);

        if ($temp === array('No Data Gathered Yet' => 0)) {
            return array(0 => 'No Data Gathered Yet');
        }

        foreach ($temp as $key => $value) {
            array_push($k, array($key,$value));
        }

        return $k;
    }

    //===========

    private function getNumberSearchesByDay(&$day, $type = 'success', $store_id = 0)
    {
        $fail      = 0;
        $success   = 0;
        $condition = 'search_results = "0"';
        $excludedIPs = $this->excludedIPs();

        if ($type == 'success') {
            $condition = 'search_results != "0"';
        }

        $result = $this->db->query('SELECT COUNT(*) as count FROM ' . DB_PREFIX . 'ianalytics_search_data
                        WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . '
                            `date` >= "'.$this->data['iAnalyticsFromDate'].'" AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND `date`="'.$day.'"
                            AND '.$condition.'
                            AND store_id="'.$store_id.'"
                        GROUP BY `date`');

        return empty($result->row['count']) ? 0 : (int)$result->row['count'];
    }

    private function getMostSearchedKeywordsRaw($limit = 80, $returnZeroResultsToo = true, $store_id = 0)
    {
        $temp      = array();
        $condition = '';
        $excludedIPs = $this->excludedIPs();

        if ($returnZeroResultsToo == false) {
            $condition = ' AND search_results != "0"';
        }

        $result = $this->db->query('SELECT `search_value`, COUNT(*) as count FROM ' . DB_PREFIX . 'ianalytics_search_data WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . ' `date` >= "'.$this->data['iAnalyticsFromDate'].'" AND `date` <= "'.$this->data['iAnalyticsToDate'].'"'.$condition.' AND store_id="'.$store_id.'" GROUP BY `search_value` ORDER BY count DESC LIMIT 0, '.$limit);

        if ($result->num_rows == 0) {
            return array('No Data Gathered Yet' => 'No Data Gathered Yet');
        } else {
            $res = array();
            foreach ($result->rows as $row) {
                $res[$row['search_value']] = $row['count'];
            }
            arsort($res);
            return $res;
        }

        return $results;
    }

    private function getMostOpenedProductsRaw($param = 'product_id', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.', COUNT(*) as count FROM ' . DB_PREFIX . 'ianalytics_product_opens
                        WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . '
                            `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        GROUP BY `product_id`
                        ORDER BY count DESC, `date` DESC, `time` DESC');

        if ($result->num_rows == 0) {
            return array('No Data Gathered Yet' => 0);
        } else {
            $k = array();
            foreach ($result->rows as $i => $search) {
                $k[$search[$param]] = $search['count'];
            }
            arsort($k);
        }

        return $k;
    }

    private function getMostAddedtoCartProductsRaw($param = 'product_id', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.', COUNT(*) as count FROM ' . DB_PREFIX . 'ianalytics_product_add_to_cart
                        WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . '
                            `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        GROUP BY `product_id`
                        ORDER BY count DESC, `date` DESC, `time` DESC');

        if ($result->num_rows == 0) {
            return array('No Data Gathered Yet' => 0);
        } else {
            $k = array();
            foreach ($result->rows as $i => $search) {
                $k[$search[$param]] = $search['count'];
            }
            arsort($k);
        }

        return $k;
    }

    private function getMostAddedtoWishlistProductsRaw($param = 'product_id', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.', COUNT(*) as count FROM ' . DB_PREFIX . 'ianalytics_product_add_to_wishlist
                        WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . '
                            `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        GROUP BY `product_id`
                        ORDER BY count DESC, `date` DESC, `time` DESC');

        if ($result->num_rows == 0) {
            return array('No Data Gathered Yet' => 0);
        } else {
            $k = array();
            foreach ($result->rows as $i => $search) {
                $k[$search[$param]] = $search['count'];
            }
            arsort($k);
        }

        return $k;
    }

    private function getMostComparedProductsRaw($store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT product_ids as pids, (SELECT product_names FROM ' . DB_PREFIX . 'ianalytics_product_comparisons WHERE product_ids = pids AND store_id="'.$store_id.'" ORDER BY `date` DESC, `time` DESC LIMIT 0,1) as comparison, COUNT(*) as count FROM ' . DB_PREFIX . 'ianalytics_product_comparisons WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . ' `date` >= "'.$this->data['iAnalyticsFromDate'].'" AND `date` <= "'.$this->data['iAnalyticsToDate'].'" AND store_id="'.$store_id.'" GROUP BY pids ORDER BY count DESC');

        if ($result->num_rows == 0) {
            return array('No Data Gathered Yet' => 0);
        } else {
            $k = array();
            foreach ($result->rows as $i => $search) {
                $k[$search['comparison']] = $search['count'];
            }
            arsort($k);
        }

        return $k;
    }


    // After Sale
    // ==========================================

    private function getFunnelData($store_id = 0)
    {
        $k = array(array('Stage','Actions'));
        $temp = $this->getFunnelDataRaw('stage', $store_id);
        foreach ($temp as $key => $value) {
            array_push($k, array($key,$value));
        }

        return $k;
    }

    private function getSalesReportData($store_id = 0)
    {
        $this->load->model('extension/report/sale');

        $filter_date_start      = $this->data['iAnalyticsFromDate'];
        $filter_date_end        = $this->data['iAnalyticsToDate'];
        $filter_group           = isset($_GET['filterGroup']) ? $_GET['filterGroup'] : 'day';
        $filter_order_status_id = isset($_GET['filterOrders']) ? $_GET['filterOrders']  : 0;
        $page   = isset($_GET['page']) ? $_GET['page'] : 1;
        $limit  = $this->data['limit'];

        $param = array(
            'filter_date_start'      => $filter_date_start,
            'filter_date_end'        => $filter_date_end,
            'filter_group'           => $filter_group,
            'filter_order_status_id' => $filter_order_status_id,
            'start'                  => ($page - 1) * $limit,
            'limit'                  => $limit
        );

        $order_results = $this->getOrders($param, $store_id);
        $order_total = $this->model_extension_report_sale->getTotalOrders($param);

        $orders = array();
        $i = 0;
        foreach ($order_results as $order_result) {
            $orders[$i] = array(
                'date_start'       => date($this->language->get('date_format_short'), strtotime($order_result['date_start'])),
                'date_end'         => date($this->language->get('date_format_short'), strtotime($order_result['date_end'])),
                'orders'           => $order_result['orders'],
                'tax'              => $this->currency->format($order_result['tax'], $this->config->get('config_currency')),
                'tax_nocurrency'   => isset($order_result['tax']) ? $order_result['tax'] : '0',
                'total'            => $this->currency->format($order_result['total'], $this->config->get('config_currency')),
                'total_nocurrency' => $order_result['total']
            );
            $orders[$i]['revenue_nocurrency'] = (string)($orders[$i]['total_nocurrency'] - $orders[$i]['tax_nocurrency']);
            $orders[$i]['revenue'] = $this->currency->format($orders[$i]['revenue_nocurrency'], $this->config->get('config_currency'));
            $i++;
        }

        $url = '';
        if (isset($_GET['fromDate'])) { $url .= '&fromDate=' . $_GET['fromDate']; }
        if (isset($_GET['toDate'])) {   $url .= '&toDate=' . $_GET['toDate']; }
        if (isset($_GET['filterGroup'])) { $url .= '&filterGroup=' . $_GET['filterGroup']; }
        if (isset($_GET['filterOrders'])) { $url .= '&filterOrders=' . $_GET['filterOrders']; }

        $pagination        = new Pagination();
        $pagination->total = $order_total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link($this->data['modulePath'], 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'] . $url . '&page={page}');
        $pagination_data   = array(
            'render'    => $pagination->render(),
            'results'   => sprintf($this->language->get('text_pagination'), ($pagination->total) ? (($page - 1) * $pagination->limit) + 1 : 0, ((($page - 1) * $pagination->limit) > ($pagination->total - $pagination->limit)) ? $pagination->total : ((($page - 1) * $pagination->limit) + $pagination->limit), $pagination->total, ceil($pagination->total / $pagination->limit))
        );

        return array(
            'orders'     => array_reverse($orders, true),
            'pagination' => $pagination_data
        );
    }

    private function getMostOrderProductData($store_id = 0)
    {
        $filter_date_start      = $this->data['iAnalyticsFromDate'];
        $filter_date_end        = $this->data['iAnalyticsToDate'];
        $filter_order_status_id = isset($_GET['filterOrders']) ? $_GET['filterOrders']  : 0;
        $page   = isset($_GET['page']) ? $_GET['page'] : 1;
        $limit  = $this->data['limit'];

        $param = array(
            'store_id'               => $store_id,
            'filter_date_start'      => $filter_date_start,
            'filter_date_end'        => $filter_date_end,
            'filter_order_status_id' => $filter_order_status_id,
            'start'                  => ($page - 1) * $limit,
            'limit'                  => $limit
        );

        $product_results = $this->getPurchased($param);
        $product_total = $this->getTotalPurchased($param);

        $products = array();
        foreach ($product_results as $product) {
            $products[] = array(
                'name'       => $product['name'],
                'model'      => $product['model'],
                'quantity'   => $product['quantity'],
                'total'      => $this->currency->format($product['total'], $this->config->get('config_currency'))
            );
        }

        $url = '';
        if (isset($_GET['fromDate'])) { $url .= '&fromDate=' . $_GET['fromDate']; }
        if (isset($_GET['toDate'])) {   $url .= '&toDate=' . $_GET['toDate']; }
        if (isset($_GET['filterOrders'])) { $url .= '&filterOrders=' . $_GET['filterOrders']; }

        $pagination        = new Pagination();
        $pagination->total = $product_total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link($this->data['modulePath'], 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'] . $url . '&page={page}');
        $pagination_data   = array(
            'render'    => $pagination->render(),
            'results'   => sprintf($this->language->get('text_pagination'), ($pagination->total) ? (($page - 1) * $pagination->limit) + 1 : 0, ((($page - 1) * $pagination->limit) > ($pagination->total - $pagination->limit)) ? $pagination->total : ((($page - 1) * $pagination->limit) + $pagination->limit), $pagination->total, ceil($pagination->total / $pagination->limit))
        );

        return array(
            'products'   => $products,
            'pagination' => $pagination_data
        );
    }

    private function getCustomerOrdersData($store_id = 0)
    {
        $this->load->model('extension/report/customer');

        $filter_date_start      = $this->data['iAnalyticsFromDate'];
        $filter_date_end        = $this->data['iAnalyticsToDate'];
        $filter_order_status_id = isset($_GET['filterOrders']) ? $_GET['filterOrders']  : 0;
        $page   = isset($_GET['page']) ? $_GET['page'] : 1;
        $limit  = $this->data['limit'];

        $param = array(
            'filter_date_start'      => $filter_date_start,
            'filter_date_end'        => $filter_date_end,
            'filter_order_status_id' => $filter_order_status_id,
            'start'                  => ($page - 1) * $limit,
            'limit'                  => $limit
        );

        $customer_results = $this->getOrdersCustomer($param, $store_id);
        $customer_total = $this->model_extension_report_customer->getTotalOrders($param);

        $customers = array();
        foreach ($customer_results as $customer) {
            $customers[] = array(
                'customer'       => $customer['customer'],
                'email'          => $customer['email'],
                'customer_group' => $customer['customer_group'],
                'status'         => ($customer['status'] ? $this->language->get('text_enabled') : $this->language->get('text_disabled')),
                'orders'         => $customer['orders'],
                'total'          => $this->currency->format($customer['total'], $this->config->get('config_currency')),
                'action'         => array(
                    'text' => $this->language->get('text_edit'),
                    'href' => $this->url->link('customer/customer/edit', 'user_token=' . $this->session->data['user_token'] . '&customer_id=' . $customer['customer_id'], true),
                )
            );
        }

        $url = '';
        if (isset($_GET['fromDate'])) { $url .= '&fromDate=' . $_GET['fromDate']; }
        if (isset($_GET['toDate'])) {   $url .= '&toDate=' . $_GET['toDate']; }
        if (isset($_GET['filterOrders'])) { $url .= '&filterOrders=' . $_GET['filterOrders']; }

        $pagination        = new Pagination();
        $pagination->total = $customer_total;
        $pagination->page  = $page;
        $pagination->limit = $limit;
        $pagination->url   = $this->url->link($this->data['modulePath'], 'store_id=' . $store_id . '&user_token=' . $this->session->data['user_token'] . $url . '&page={page}');
        $pagination_data   = array(
            'render'    => $pagination->render(),
            'results'   => sprintf($this->language->get('text_pagination'), ($pagination->total) ? (($page - 1) * $pagination->limit) + 1 : 0, ((($page - 1) * $pagination->limit) > ($pagination->total - $pagination->limit)) ? $pagination->total : ((($page - 1) * $pagination->limit) + $pagination->limit), $pagination->total, ceil($pagination->total / $pagination->limit))
        );

        return array(
            'customers'  => $customers,
            'pagination' => $pagination_data
        );
    }

    //===========

    private function getOrders($data = array(), $store_id = 0)
    {
        $sql = "SELECT MIN(o.date_added) AS date_start, MAX(o.date_added) AS date_end, COUNT(*) AS `orders`, SUM((SELECT SUM(op.quantity) FROM `" . DB_PREFIX . "order_product` op WHERE op.order_id = o.order_id GROUP BY op.order_id)) AS products, SUM((SELECT SUM(ot.value) FROM `" . DB_PREFIX . "order_total` ot WHERE ot.order_id = o.order_id AND ot.code = 'tax' GROUP BY ot.order_id)) AS tax, SUM(o.total) AS `total` FROM `" . DB_PREFIX . "order` o";

        if (!empty($data['filter_order_status_id'])) {
            $sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
        } else {
            $sql .= " WHERE o.order_status_id > '0'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " AND o.store_id='" . $store_id . "'";

        $group = 'week';
        if (!empty($data['filter_group'])) {
            $group = $data['filter_group'];
        }

        switch ($group) {
            case 'day':
                $sql .= " GROUP BY YEAR(o.date_added), MONTH(o.date_added), DAY(o.date_added)";
                break;
            default:
            case 'week':
                $sql .= " GROUP BY YEAR(o.date_added), WEEK(o.date_added)";
                break;
            case 'month':
                $sql .= " GROUP BY YEAR(o.date_added), MONTH(o.date_added)";
                break;
            case 'year':
                $sql .= " GROUP BY YEAR(o.date_added)";
                break;
        }

        $sql .= " ORDER BY o.date_added DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    private function getPurchased($data = array())
    {
        $sql = "SELECT op.name, op.model, SUM(op.quantity) AS quantity, SUM((op.price + op.tax) * op.quantity) AS total FROM " . DB_PREFIX . "order_product op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id)";

        if (!empty($data['filter_order_status_id'])) {
            $sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
        } else {
            $sql .= " WHERE o.order_status_id > '0'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " AND o.store_id= '" . $this->db->escape($data['store_id']) . "'";

        $sql .= " GROUP BY op.product_id ORDER BY total DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }

    private function getTotalPurchased($data)
    {
        $sql = "SELECT COUNT(DISTINCT op.product_id) AS total FROM `" . DB_PREFIX . "order_product` op LEFT JOIN `" . DB_PREFIX . "order` o ON (op.order_id = o.order_id)";

        if (!empty($data['filter_order_status_id'])) {
            $sql .= " WHERE o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
        } else {
            $sql .= " WHERE o.order_status_id > '0'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " AND o.store_id= '" . $this->db->escape($data['store_id']) . "'";

        $query = $this->db->query($sql);

        return $query->row['total'];
    }

    private function getFunnelDataRaw($param = 'stage', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.', COUNT(*) as count FROM ' . DB_PREFIX . 'ianalytics_funnel_data
                        WHERE' . (!empty($excludedIPs) ? ' `from_ip` NOT IN (' . implode(',', $excludedIPs) . ') AND' : '') . '
                            `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        GROUP BY `stage`
                        ORDER BY `stage` ASC, `date` DESC, `time` DESC');

        if ($result->num_rows == 0) {
            return array('No Data Gathered Yet' => 0);
        } else {
            $k = array();
            foreach ($result->rows as $i => $search) {
                $k[$search[$param]] = $search['count'];
            }
        }
        return array_replace(array(0,0,0,0,0,0,0), $k);
    }

    private function getOrdersCustomer($data = array(), $store_id = 0)
    {
        $sql = "SELECT c.customer_id, CONCAT(c.firstname, ' ', c.lastname) AS customer, c.email, cgd.name AS customer_group, c.status, COUNT(o.order_id) AS orders, SUM(o.total) AS `total` FROM `" . DB_PREFIX . "order` o
            LEFT JOIN `" . DB_PREFIX . "customer` c ON (o.customer_id = c.customer_id)
            LEFT JOIN `" . DB_PREFIX . "customer_group_description` cgd ON (c.customer_group_id = cgd.customer_group_id AND cgd.language_id = '" . (int)$this->config->get('config_language_id') . "')
            WHERE o.customer_id > 0 AND o.store_id = '".$store_id."'";

        if (!empty($data['filter_order_status_id'])) {
            $sql .= " AND o.order_status_id = '" . (int)$data['filter_order_status_id'] . "'";
        } else {
            $sql .= " AND o.order_status_id > '0'";
        }

        if (!empty($data['filter_date_start'])) {
            $sql .= " AND DATE(o.date_added) >= '" . $this->db->escape($data['filter_date_start']) . "'";
        }

        if (!empty($data['filter_date_end'])) {
            $sql .= " AND DATE(o.date_added) <= '" . $this->db->escape($data['filter_date_end']) . "'";
        }

        $sql .= " GROUP BY o.customer_id ORDER BY total DESC";

        if (isset($data['start']) || isset($data['limit'])) {
            if ($data['start'] < 0) {
                $data['start'] = 0;
            }

            if ($data['limit'] < 1) {
                $data['limit'] = 20;
            }

            $sql .= " LIMIT " . (int)$data['start'] . "," . (int)$data['limit'];
        }

        $query = $this->db->query($sql);

        return $query->rows;
    }


    // Visitor
    // ==========================================

    private function getVisitorsData($param = 'stage', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.', sum(`unique_visits`) as visits, sum(`impressions`) as page_impressions, sum(referers_social+referers_other+referers_search+referers_direct) as referers
                        FROM ' . DB_PREFIX . 'ianalytics_visits_data
                        WHERE `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        GROUP BY `stage`
                        ORDER BY `stage` ASC, `date` DESC');

        if ($result->num_rows == 0) {
            return array(0 => 'No Data Gathered Yet');
        } else {
            $label = array('Midnight', 'Morning', 'Noon', 'Evening');
            $k = array(array('Part of the Day','Unique Visits','Page Impressions','Referers'));
            foreach ($result->rows as $i => $search) {
                array_push($k, array($label[$search['stage']],$search['visits'],$search['page_impressions'],$search['referers']));
            }
        }
        return $k;
    }

    private function getVisitorsDataByDay($param = 'date', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.', sum(`unique_visits`) as visits, sum(`impressions`) as page_impressions, sum(referers_social+referers_other+referers_search+referers_direct) as referers
                        FROM ' . DB_PREFIX . 'ianalytics_visits_data
                        WHERE `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        GROUP BY `date`
                        ORDER BY `date` ASC');

        if ($result->num_rows == 0) {
            return array(0 => 'No Data Gathered Yet');
        } else {
            $k = array(array('Date','Unique Visits','Page Impressions','Referers'));
            foreach ($result->rows as $i => $search) {
                array_push($k, array($search['date'],$search['visits'],$search['page_impressions'],$search['referers']));
            }
        }
        return $k;
    }

    private function getVisitorsDataReferers($param = 'date', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.', sum(`referers_direct`) as direct, sum(`referers_social`) as social, sum(`referers_other`) as other, sum(`referers_search`) as search
                        FROM ' . DB_PREFIX . 'ianalytics_visits_data
                        WHERE `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        GROUP BY `date`
                        ORDER BY `date` DESC');

        if ($result->num_rows == 0) {
            return array(0 => 'No Data Gathered Yet');
        } else {
            $k = array(array('Date','Direct','Social','Search','Other'));
            foreach ($result->rows as $i => $search) {
                array_push($k, array($search['date'],$search['direct'],$search['social'],$search['search'],$search['other']));
            }
        }
        return $k;
    }

    private function getVisitorsDataReferersPie($param = '', $store_id = 0)
    {
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT '.$param.' sum(`referers_direct`) as direct, sum(`referers_social`) as social, sum(`referers_other`) as other, sum(`referers_search`) as search
                        FROM ' . DB_PREFIX . 'ianalytics_visits_data
                        WHERE `date` >= "'.$this->data['iAnalyticsFromDate'].'"
                            AND `date` <= "'.$this->data['iAnalyticsToDate'].'"
                            AND store_id="'.$store_id.'"
                        ORDER BY `date` DESC');

        if ($result->num_rows == 0 || isset($result->row['direct']) && $result->row['direct'] == null) {
            return array(0 => 'No Data Gathered Yet');
        } else {
            $k = array(array('Direct','Social','Search','Other'));
            foreach ($result->rows as $i => $search) {
                array_push($k, array($search['direct'],$search['social'],$search['search'],$search['other']));
            }
            return $k;
        }
    }


    // =================================================================================
    // Tools
    // =================================================================================

    private function excludedIPs()
    {
        $configValue = $this->config->get('module_ianalytics_setting');
        $ips = array();
        if (!empty($configValue['BlacklistedIPs'])) {
            $ips = $configValue['BlacklistedIPs'];
            $ips = str_replace("\n\r", "\n", $ips);
            $ips = explode("\n", $ips);
            foreach ($ips as $i => $val) {
                $ips[$i] = '"'.trim($val).'"';
            }
        }

        return $ips;
    }

    private function chartData($data, $column, $unset = 0)
    {
        $result = array();

        if (isset($data[0]) && is_array($data[0])) {
            if (is_numeric($unset)) {
                unset($data[$unset]);
            }

            foreach ($data as $k => $v) {
                $result[] = $v[$column];
            }
        }

        return json_encode($result);
    }

    private function findMinDate($defRange, $store_id = 0)
    {
        $output = $defRange; // make sure the minDate is a month ago
        $excludedIPs = $this->excludedIPs();

        $result = $this->db->query('SELECT LEAST(
            "' . $this->db->escape($defRange) . '",
            (SELECT MIN(`date`) FROM ' . DB_PREFIX . 'ianalytics_product_comparisons WHERE store_id="'. $store_id .'"' . (!empty($excludedIPs) ? ' AND `from_ip` NOT IN (' . implode(',', $excludedIPs) . ')' : '') . '),
            (SELECT MIN(`date`) FROM ' . DB_PREFIX . 'ianalytics_product_opens WHERE store_id="'. $store_id .'"' .(!empty($excludedIPs) ? ' AND `from_ip` NOT IN (' . implode(',', $excludedIPs) . ')' : '') . '),
            (SELECT MIN(`date`) FROM ' . DB_PREFIX . 'ianalytics_search_data WHERE store_id="'. $store_id .'"' . (!empty($excludedIPs) ? ' AND `from_ip` NOT IN (' . implode(',', $excludedIPs) . ')' : '') .')
        ) as min');

        if ($result->num_rows > 0 && $result->row['min'] != null) {
            $output = $result->row['min'];
        }

        return $output;
    }

    private function getFilterState($now, $fromDate, $toDate, $minDate)
    {
        // Date dropdown state
        $enable   = array();
        $interval = abs(ceil((($now - strtotime($minDate))/86400))) + 1;
        switch ($interval) {
            case ($interval < 7) :                      { $enable = array(1,0,0,0); } break;
            case ($interval >= 7 && $interval < 30) :   { $enable = array(1,1,0,0); } break;
            case ($interval >= 30 && $interval < 365) : { $enable = array(1,1,1,0); } break;
            case ($interval >= 365) :                   { $enable = array(1,1,1,1); } break;
        }

        // Date dropdown auto select
        $select = array(1,0,0,0);
        if ($toDate == date('Y-m-d')) {
            $interval = abs(ceil(((strtotime($toDate) - strtotime($fromDate))/86400)));
            switch ($interval) {
                case 6   : { $select = array(0,1,0,0); } break;
                case 29  : { $select = array(0,0,1,0); } break;
                case 364 : { $select = array(0,0,0,1); } break;
                default  : { $select = array(1,0,0,0); } break;
            }
        }

        return array('enable' => $enable, 'select' => $select);
    }

    private function validateDate($date, $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) == $date;
    }


    // =================================================================================
    // Internals
    // =================================================================================

    public function install()
    {
        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ianalytics_visits_data` (
            `id` int(11) NOT NULL auto_increment COMMENT 'Primary index',
            `date` date NOT NULL COMMENT 'Date when data is added',
            `stage` enum('0','1','2','3') not null default '0' COMMENT 'Stage of the cliend that generated the data',
            `unique_visits` int(11) NOT NULL default '0' COMMENT 'Unique visits',
            `impressions` int(11) NOT NULL default '0' COMMENT 'Visited Pages',
            `referers_direct` int(11) NOT NULL default '0' COMMENT 'Direct hits',
            `referers_social` int(11) NOT NULL default '0' COMMENT 'Referers from social networks',
            `referers_search` int(11) NOT NULL default '0' COMMENT 'Referers from search engines',
            `referers_other` int(11) NOT NULL default '0' COMMENT 'Referers from other websites',
            `store_id` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ianalytics_search_data` (
            `id` int(11) NOT NULL auto_increment COMMENT 'Primary index',
            `date` date NOT NULL COMMENT 'Date when data is added',
            `time` time NOT NULL COMMENT 'Time of day when data is added',
            `from_ip` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'IP of client that generated the data',
            `spoken_languages` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'Language of the client that generated the data',
            `search_value` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'The searched text',
            `search_results` int(11) NOT NULL default '0' COMMENT 'The number of found search results',
            `store_id` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ianalytics_funnel_data` (
            `id` int(11) NOT NULL auto_increment COMMENT 'Primary index',
            `date` date NOT NULL COMMENT 'Date when data is added',
            `time` time NOT NULL COMMENT 'Time of day when data is added',
            `from_ip` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'IP of client that generated the data',
            `spoken_languages` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'Language of the client that generated the data',
            `stage` enum('0','1','2','3','4','5','6') not null default '0' COMMENT 'Stage of the cliend that generated the data',
            `store_id` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ianalytics_product_opens` (
            `id` int(11) NOT NULL auto_increment COMMENT 'Primary index',
            `date` date NOT NULL COMMENT 'Date when data is added',
            `time` time NOT NULL COMMENT 'Time of day when data is added',
            `from_ip` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'IP of client that generated the data',
            `spoken_languages` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'Language of the client that generated the data',
            `product_id` int(11) NOT NULL COMMENT 'The id of the opened product',
            `product_name` text collate utf8_unicode_ci NOT NULL COMMENT 'The name of the opened product',
            `product_model` text collate utf8_unicode_ci NOT NULL COMMENT 'The model of the opened product',
            `product_price` decimal(15,4) NOT NULL default '0.0000' COMMENT 'The price of the opened product',
            `product_quantity` int(11) NOT NULL default '0' COMMENT 'The quantity of the opened product',
            `product_stock_status` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'The stock status of the opened product',
            `store_id` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ianalytics_product_comparisons` (
            `id` int(11) NOT NULL auto_increment COMMENT 'Primary index',
            `date` date NOT NULL COMMENT 'Date when data is added',
            `time` time NOT NULL COMMENT 'Time of day when data is added',
            `from_ip` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'IP of client that generated the data',
            `spoken_languages` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'Language of the client that generated the data',
            `product_ids` varchar(255) collate utf8_unicode_ci NOT NULL COMMENT 'The ids of the compared products, ordered ascending. Used to determine the count of the comparison',
            `product_names` text collate utf8_unicode_ci NOT NULL COMMENT 'Product names according to the ids',
            `store_id` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ianalytics_product_add_to_cart` (
            `id` int(11) NOT NULL auto_increment COMMENT 'Primary index',
            `date` date NOT NULL COMMENT 'Date when data is added',
            `time` time NOT NULL COMMENT 'Time of day when data is added',
            `from_ip` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'IP of client that generated the data',
            `spoken_languages` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'Language of the client that generated the data',
            `product_id` int(11) NOT NULL COMMENT 'The id of the opened product',
            `product_name` text collate utf8_unicode_ci NOT NULL COMMENT 'The name of the added to cart product',
            `product_model` text collate utf8_unicode_ci NOT NULL COMMENT 'The model of the added to cart product',
            `product_price` decimal(15,4) NOT NULL default '0.0000' COMMENT 'The price of the added to cart product',
            `product_quantity` int(11) NOT NULL default '0' COMMENT 'The quantity of the the added to cart product',
            `product_stock_status` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'The stock status of the added to cart product',
            `store_id` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");

        $this->db->query("CREATE TABLE IF NOT EXISTS `" . DB_PREFIX . "ianalytics_product_add_to_wishlist` (
            `id` int(11) NOT NULL auto_increment COMMENT 'Primary index',
            `date` date NOT NULL COMMENT 'Date when data is added',
            `time` time NOT NULL COMMENT 'Time of day when data is added',
            `from_ip` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'IP of client that generated the data',
            `spoken_languages` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'Language of the client that generated the data',
            `product_id` int(11) NOT NULL COMMENT 'The id of the opened product',
            `product_name` text collate utf8_unicode_ci NOT NULL COMMENT 'The name of the added to cart product',
            `product_model` text collate utf8_unicode_ci NOT NULL COMMENT 'The model of the added to cart product',
            `product_price` decimal(15,4) NOT NULL default '0.0000' COMMENT 'The price of the added to cart product',
            `product_quantity` int(11) NOT NULL default '0' COMMENT 'The quantity of the the added to cart product',
            `product_stock_status` tinytext collate utf8_unicode_ci NOT NULL COMMENT 'The stock status of the added to wishlist product',
            `store_id` int(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`id`)
        ) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;");
    }

    public function uninstall()
    {
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ianalytics_visits_data`");
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ianalytics_search_data`");
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ianalytics_funnel_data`");
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ianalytics_product_opens`");
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ianalytics_product_comparisons`");
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ianalytics_product_add_to_cart`");
        // $this->db->query("DROP TABLE IF EXISTS `" . DB_PREFIX . "ianalytics_product_add_to_wishlist`");
    }

    private $eventGroup = 'isenselabs_ianalytics';

    public function addEvents()
    {
        $this->load->model('setting/event');
        $this->config->load('isenselabs/ianalytics');

        $modulePath = $this->config->get('ianalytics_path');

        $this->model_setting_event->deleteEventByCode($this->eventGroup);

        //=== Admin Events
        $this->model_setting_event->addEvent($this->eventGroup, "admin/view/common/column_left/before", $modulePath . "/addMenuColumnLeft", 1, 0);

        //==== Catalog Events
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/common/header/before', $modulePath . '/addPreSaleTracker', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/extension/basel/basel_features/add_to_cart/after', $modulePath . '/addProductToCart', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/account/wishlist/add/after', $modulePath . '/addProductToWishlist', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/account/login/before', $modulePath . '/addFunnelLoginRegister', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/checkout/guest/save/after', $modulePath . '/addFunnelLoginRegisterCheckout', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/checkout/login/save/after', $modulePath . '/addFunnelLoginRegisterCheckout', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/checkout/register/save/after', $modulePath . '/addFunnelLoginRegisterCheckout', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/checkout/shipping_method/save/after', $modulePath . '/addFunnelCheckoutShipping', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/extension/quickcheckout/payment_method/set/after', $modulePath . '/addFunnelCheckoutPayment', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/extension/quickcheckout/confirm/after', $modulePath . '/addFunnelCheckoutConfirm', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/checkout/success/before', $modulePath . '/addFunnelCheckoutSuccess', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/view/common/success/after', $modulePath . '/addGAScript', 1, 0);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/view/common/ordersuccesspage/after', $modulePath . '/addGAScript', 1, 0);

        // Compatibility Codes
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/view/common/ordersuccesspage_journal/after', $modulePath . '/addGAScript', 1, 0);
        
        //=== Journal 3
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/journal3/checkout/save/after', $modulePath . '/addFunnelCheckoutShipping', 1, 11);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/journal3/checkout/save/after', $modulePath . '/addFunnelCheckoutPayment', 1, 12);
        $this->model_setting_event->addEvent($this->eventGroup, 'catalog/controller/journal3/checkout/save/after', $modulePath . '/addFunnelCheckoutConfirm', 1, 13);
    }

    public function removeEvents()
    {
        $this->load->model('setting/event');
        $this->model_setting_event->deleteEventByCode($this->eventGroup);
    }
}
