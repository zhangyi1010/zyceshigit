<?php


namespace app;


class bighg extends \privateuse
{
    public $d,$layout, $db, $fw;

    //restful
    public function get()
    {
        $nav = $this->nav();
        $coin_type = $this->fw->get('PARAMS.nav');
        $coin_type = ($coin_type === null) ? '0' : $coin_type;
        if ($coin_type) {
            $coin_big = $this->bigdata($coin_type);
        } else {
            $coin_type = 'BTC';
            $coin_big = $this->bigdata($coin_type);
        }
        if ($coin_big) {
            $status = 200;
            $success = true;
            $message = '';
            $dataset = $coin_big;
        } else {
            $status = 500;
            $success = false;
            $message = '';
            $dataset = array(
                $status,
                $success,
                $message
            );
        }
        $datas = $this->jsondata($status, $success, $message, $dataset);
        $navs = array_column($nav, 'coin_type');
        $f3 = $this->fw;
        $f3->set('navs', $navs);
        $f3->set('coinbig', $datas);
        echo \Template::instance()->render('testz.html');
    }

    function post()
    {

    }

    function put()
    {

    }

    function delete()
    {

    }

    public function nav()
    {
        $dbs = $this->db;
        //big表
        $reb = $dbs->exec("SELECT distinct coin_type FROM v2_imp_cash_tran_big_dtl");

        return $reb;
    }
    //使用并行查询v3
    public function bigdata($coin)
    {
        $type = addslashes($coin);
        $dbs = $this->db;
        //近30天的数据
        //$data=$dbs->exec("SELECT trans_cnt,trans_date FROM v2_imp_cash_tran_cal_dtl  WHERE coin_type = ? AND  date_format(trans_date,'%Y-%m-%d') >= date_format(DATE_SUB(curdate(), INTERVAL 30 DAY),'%Y-%m-%d')",array($type));
        //big表24小时内数据
        // $data.=$dbs->exec("SELECT trans_amt,trans_time FROM v2_imp_cash_tran_big_dtl WHERE coin_type = ? AND date_format(trans_time,'%m-%d-%H') >= date_format(DATE_SUB(curdate(), INTERVAL 24 HOUR),'%m-%d-%H')",array($type));
        //详细
        // $data.=$dbs->exec("SELECT order_id,from_to_addr,trans_amt,trans_time FROM v2_imp_cash_tran_big_dtl WHERE coin_type = ? AND date_format(trans_time,'%Y-%m-%d-%H') >= date_format(DATE_SUB(curdate(), INTERVAL 24 HOUR),'%Y-%m-%d-%H')",array($type));
        try {
            $dbs->begin();
            $data1['v2_imp_cash_tran_cal_dtl'] = $dbs->exec("SELECT trans_cnt,trans_date FROM v2_imp_cash_tran_cal_dtl  WHERE coin_type = ? AND  date_format(trans_date,'%Y-%m-%d') >= date_format(DATE_SUB(curdate(), INTERVAL 30 DAY),'%Y-%m-%d')", array($type));
            $data2['v2_imp_cash_tran_big_dtl'] = $dbs->exec("SELECT order_id,from_to_addr,trans_amt,trans_time FROM v2_imp_cash_tran_big_dtl WHERE coin_type = ? AND date_format(trans_time,'%Y-%m-%d-%H') >= date_format(DATE_SUB(curdate(), INTERVAL 24 HOUR),'%Y-%m-%d-%H')", array($type));
            $dbs->commit();
        } catch (Exception $e) {
            $dbs->rollback();
            echo $e->getMessage(), "\n";
        }

        foreach ($data1['v2_imp_cash_tran_cal_dtl'] as $keys => $var) {
            $title = array_keys($data1['v2_imp_cash_tran_cal_dtl'][$keys]);
        }
        if ($title) {
            foreach ($title as $key => $value) {
                $datas['v2_imp_cash_tran_cal_dtl'][$key]['title'] = $value;
                $datas['v2_imp_cash_tran_cal_dtl'][$key]['data'] = $this->inArrayKey($data1['v2_imp_cash_tran_cal_dtl'], $value);
            }

        }
        foreach($data2['v2_imp_cash_tran_big_dtl'] as $keys => $var){
            $titler = array_keys($data2['v2_imp_cash_tran_big_dtl'][$keys]);
        }
        if($titler){
            foreach($titler as $key =>$value){
                $datas['v2_imp_cash_tran_big_dtl'][$key]['title']=$value;
                $datas['v2_imp_cash_tran_big_dtl'][$key]['data']=$this->inArrayKey($data2['v2_imp_cash_tran_big_dtl'],$value);
            }
        }
        return $datas;
    }

    //处理数组转化
    function inArrayKey($array, $field)
    {
        $arr = [];
        foreach ($array as $key => $value) {
            $arr[] = $value[$field];
        }

        return $arr;
    }

    //json数据处理
    public function jsondata($status, $success, $message = '', $data = array())
    {
        if (!is_bool($success)) {
            $dataset = array(
                'status' => 500,
                '$success' => false
            );
            return $dataset;
        }

        $datas = array(
            'status' => $status,
            'success' => $success,
            'message' => '',
            'data' => $data
        );
        header('Content-type: application/json;charset=utf-8');
        $dataset = json_encode($datas);
        return $dataset;

    }

    function __construct()
    {
        require_once("lib/dbconfig.php");
        require_once("lib/privateuse.php");
        $this->d= new \privateuse();

        $this->fw = \Base::instance();
        $this->db = new \DB\SQL(
            dsn, user, pw
        );
        $this->layout = ($this->fw->get('AJAX')) ? 'layouts/blank.html' : 'layouts/common.html';
        $this->mimetype = 'text/html';
    }
}