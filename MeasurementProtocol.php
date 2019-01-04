<?php

/**
 * Class MeasurementProtocol
 */
class MeasurementProtocol {

    private $endpoint;

    /**
     * Флаг для очистки кеша запроса
     * @var bool
     */
    private $clearCacheFlag;

    /**
     * Settings
     * @var MeasurementProtocolSettings
     */
    private $settings;

    /**
     * Document settings
     * @var MeasurementProtocolDocument
     */
    private $document;

    /**
     * Event settings
     * @var MeasurementProtocolEvent|null
     */
    private $event;

    /**
     * Данные транзакции
     * @var MeasurementProtocolEnhancedEcommerceTransaction|null
     */
    private $transaction;

    public function __construct() {
        $this->endpoint = 'https://www.google-analytics.com/collect?';
        $this->transaction = null;
        $this->event = null;
    }

    public function setSettings(MeasurementProtocolSettings $settings) {
        $this->settings = $settings;
        return $this;
    }

    public function setDocument(MeasurementProtocolDocument $document) {
        $this->document = $document;
        return $this;
    }

    public function setEvent(MeasurementProtocolEvent $event) {
        $this->event = $event;
        return $this;
    }

    public function addTransaction(MeasurementProtocolEnhancedEcommerceTransaction $transaction) {
        $this->transaction = $transaction;
        return $this;
    }
    /**
     * Переключатель режимов отправки обращений
     * true - отправка на сервер аналитики
     * false - отправка обращений на проверку
     * @param bool $mode
     * @return $this
     */
    public function setDebug( $mode = false ) {
        if ($mode) {
            $this->endpoint = 'https://www.google-analytics.com/debug/collect?';
        } else {
            $this->endpoint = 'https://www.google-analytics.com/collect?';
        }
        return $this;
    }

    /**
     * Отправляет данные в аналитику
     * @return mixed
     */
    public function send() {
        $getString = $this->endpoint;
        $getString .= $this->getQueryParams();

        var_dump($getString);
        $ch = curl_init($getString); // such as http://example.com/example.xml
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, false);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $data = curl_exec($ch);
        curl_close($ch);

        var_dump($data);
        return $data;
    }

    public function clearCache($bool) {
        $this->clearCacheFlag = $bool;
        return $this;
    }
    /**
     * Формирует строку запроса
     * который будет отправлен
     * @return string
     */
    private function getQueryParams() {
        $params = array(
            'v' => $this->settings->getVersion(),
            't' => $this->settings->getHitType(),
            'tid' => $this->settings->getTid(),
            'cid' => $this->settings->getCid(),
            'uid' => $this->settings->getUserID(),
            'dl' => $this->document->getDocumentLocation(),
            'dt' => $this->document->getDocumentTitle(),
        );

        if ($this->event) {
            $params['ec'] = $this->event->getEventCategory();
            $params['ea'] = $this->event->getEventAction();
            $params['ev'] = $this->event->getEventValue();
        }

        if ($this->transaction) {
            $transactionData = $this->transaction->toArray();
            $params = array_merge($params, $transactionData);
        }

        if ($this->clearCacheFlag) {
            $params['z'] = time() . '-' . rand(2, 100);
        }
        print_r($params);
        return http_build_query($params);
        //&ti=T12345&ta=Google%20Store%20-%20Online&tr=37.39&tt=2.85&ts=5.34&tcc=SUMMER2013&pa=purchase&pr1id=P12345&pr1nm=Android%20Warhol%20T-Shirt&pr1ca=Apparel&pr1br=Google&pr1va=Black&pr1ps=1
        return "cid=c419a05d-0e07-4502-ae26-2f269abb1a4c&dh=mydemo.com&dp=%2Fhome2&dt=homepage&il1nm=Search%20Results&il1pi1id=P12345&il1pi1nm=Android%20Warhol%20T-Shirt&il1pi1ca=Apparel%2FT-Shirts&il1pi1br=Google&il1pi1va=Black&il1pi1ps=1&il1pi1cd1=Member&il2nm=Recommended%20Products&il2pi1nm=Yellow%20T-Shirt&il2pi2nm=Red%20T-Shirt&hl=ru";
    }
}

/**
 * Основные настройки для реализации Measurement Protocol
 * Class MeasurementProtocolSettings
 */
class MeasurementProtocolSettings {

    /**
     * Версия протокола
     * по умолчанию 1
     * @var int
     */
    private $v;

    /**
     * тип обращения
     * @var string
     */
    private $t;

    /**
     * идентификатор Universal Analytics
     * @var string
     */
    private $tid;

    /**
     * идентификатор клиента
     * @var string
     */
    private $cid;

    /**
     * Идентификатор клиента
     * @var
     */
    private $uid;


    public function setUserID($uid) {
        $this->uid = $uid;
        return $this;
    }

    public function getUserID() {
        return $this->uid;
    }

    /**
     * MeasurementProtocolSettings constructor.
     * @param $UA
     * @param $v
     */

    public function __construct($UA, $v = 1) {
        $this->tid = $UA;
        $this->v = 1;

        if (isset($_COOKIE['_ga'])) {
            $cid = $_COOKIE['_ga'];
        } else {
            $cid = $this->gaGenUUID();
        }

        $this->cid = $cid;
    }

    /**
     * Возвращает версию протокола
     * @return int
     */
    public function getVersion() {
        return $this->v;
    }

    /**
     * Возвращает UAID куда будет отправлено событие
     * @return mixed
     */
    public function getTid() {
        return $this->tid;
    }

    /**
     * Получает идентификатор клиента
     * @return string
     */
    public function getCid() {
        return $this->cid;
    }

    /**
     * Устанавливает тип обращения
     * @param $type
     * @return $this
     */
    public function setHitType($type) {
        $this->t = $type;
        return $this;
    }

    /**
     * Получает тип обращения
     * @return mixed
     */
    public function getHitType() {
        return $this->t;
    }

    /**
     * Геренирует Client ID
     * @return string
     */
    private function gaGenUUID() {
        return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            // 32 bits for "time_low"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
            // 16 bits for "time_mid"
            mt_rand( 0, 0xffff ),
            // 16 bits for "time_hi_and_version",
            // four most significant bits holds version number 4
            mt_rand( 0, 0x0fff ) | 0x4000,
            // 16 bits, 8 bits for "clk_seq_hi_res",
            // 8 bits for "clk_seq_low",
            // two most significant bits holds zero and one for variant DCE1.1
            mt_rand( 0, 0x3fff ) | 0x8000,
            // 48 bits for "node"
            mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
        );
    }
}

/**
 * Настройки документа для реализации Measurement Protocol
 * Class MeasurementProtocolDocument
 */
class MeasurementProtocolDocument {

    /**
     * Ссылка на страницу отправки события
     * @var string
     */
    private $dl;

    /**
     * Заголовок страницы, отправляемой событие
     * @var string
     */
    private $dt;


    /**
     * Устанавливает url страницы отправки события
     * @param $dl
     * @return $this
     */
    public function setDocumentLocation($dl) {
        $this->dl = $dl;
        return $this;
    }

    /**
     * Возвращает значение url страницы
     * с которой будет отправлено событие
     * @return mixed
     */
    public function getDocumentLocation() {
        return $this->dl;
    }

    /**
     * Устанавливает заголовок страницы
     * с которой отправялется событие
     * @param $dt
     * @return $this
     */
    public function setDocumentTitle($dt) {
        $this->dt = $dt;
        return $this;
    }

    /**
     * Возвращает значение заголовка страницы
     * с которой будет отправлено событие
     * @return mixed
     */
    public function getDocumentTitle() {
        return $this->dt;
    }
}

class MeasurementProtocolEvent {

    /**
     * Категория для события
     * @var string
     */
    private $ec;

    /**
     * Действие для события
     * @var string
     */
    private $ea;

    /**
     * Значение события
     * @var string
     */
    private $ev;

    /**
     * Non-interaction
     * @var int
     */
    private $ni;


    /**
     * Устанавливает категорию события
     * @param $event
     * @return $this
     */
    public function setEventCategory($event) {
        $this->ec = $event;
        return $this;
    }

    /**
     * Устанавливает действие события
     * @param $action
     * @return $this
     */
    public function setEventAction($action) {
        $this->ea = $action;
        return $this;
    }

    /**
     * Устанавливает значение события
     * @param $value
     * @return $this
     */
    public function setEventValue($value) {
        $this->ev = $value;
        return $this;
    }

    /**
     * Возвращает категорию события
     * @param $event
     * @return string
     */
    public function getEventCategory($event) {
        return $this->ec;
    }

    /**
     * Возвращает действие события
     * @param $action
     * @return string
     */
    public function getEventAction($action) {
        return $this->ea;
    }

    /**
     * Возвращает значение события
     * @param $value
     * @return string
     */
    public function getEventValue($value) {
        return $this->ev;
    }

    public function setNonInteraction($ni) {
        $this->ni = $ni;
        return $this;
    }
    public function getNonInteraction() {
        return $this->ni;
    }


}

class MeasurementProtocolEnhancedEcommerceTransaction {
    /**
     * Идентификатор транзакции
     * @var int
     */
    private $ti;

    /**
     * Источник транзации
     * @var string
     */
    private $ta;

    /**
     * Сумма транзации
     * @var double
     */
    private $tr;

    /**
     * Сумма налогов
     * @var double
     */
    private $tt;

    /**
     * Стоимость доставки
     * @var double
     */
    private $ts;

    /**
     * Код купона
     * @var string
     */
    private $tcc;

    /**
     * Действие над товарами
     * @var string
     */
    private $pa;

    /**
     * массив товаров транзакции
     * @var MeasurementProtocolProduct[]
     */
    private $products;

    public function __construct() {
        $this->ti = '';
        $this->ta = 'Online Store';
        $this->tr = 0;
        $this->tt = 0;
        $this->ts = 0;
        $this->tcc = '';
        $this->pa = '';

    }

    /**
     * Устанавливает значение идентификатора транзакции
     * @param $ti
     * @return $this
     */
    public function setId($ti) {
        $this->ti = $ti;
        return $this;
    }

    /**
     * Получает значение идентификатора транзации
     * @return int
     */
    public function getId() {
        return $this->ti;
    }

    /**
     * Устанавливает значение источника транзакции
     * @param $ta
     * @return $this
     */
    public function setAffiliation($ta) {
        $this->ta = $ta;
        return $this;
    }

    /**
     * Возвращает значение источника транзакции
     * @return string
     */
    public function getAffiliation() {
        return $this->ta;
    }

    /**
     * Устанавливает значение суммы транзакции
     * @param $tr
     * @return $this
     */
    public function setRevenue($tr) {
        $this->tr = $tr;
        return $this;
    }

    /**
     * Возвращает значение суммы транзакции
     * @return float
     */
    public function getRevenue() {
        return $this->tr;
    }

    /**
     * Устанавливает значение суммы налогов
     * @param $tt
     * @return $this
     */
    public function setTax($tt) {
        $this->tt = $tt;
        return $this;
    }

    /**
     * Возвращает значение суммы налогов
     * @return float
     */
    public function getTax() {
        return $this->tt;
    }

    /**
     * Устанавливает значение стоимости доставки
     * @param $ts
     * @return $this
     */
    public function setShipping($ts) {
        $this->ts = $ts;
        return $this;
    }

    /**
     * Возвращает значение стоимости доставки
     * @return float
     */
    public function getShipping() {
        return $this->ts;
    }

    /**
     * Устанавливает значение кода купона
     * @return $this
     */
    public function setCouponCode($tcc) {
        $this->tcc = $tcc;
        return $this;
    }

    /**
     * Возвращает значение кода купона
     * @return string
     */
    public function getShippingCode() {
        return $this->tcc;
    }

    /**
     * Устанавливает значение типа действия
     * @param $pa
     * @return $this
     */
    public function setProductAction($pa) {
        $this->pa = $pa;
        return $this;
    }

    /**
     * Возвращает тип действия
     * @return string
     */
    public function getProductAction() {
        return $this->pa;
    }

    /**
     * Добавляет товары
     * в массив товаров
     * @param MeasurementProtocolProduct $product
     */
    public function addProduct(MeasurementProtocolProduct $product) {
        $this->products[] = $product;
    }

    /**
     * Преобразовывает обьект в массив
     * с необходимыми ключами для Measurement Protocol
     * @return array
     */
    public function toArray() {
        $array = array();

        $array['ti'] = $this->ti;
        $array['ta'] = $this->ta;
        $array['tr'] = $this->tr;
        $array['tt'] = $this->tt;
        $array['ts'] = $this->ts;
        $array['tcc'] = $this->tcc;

        foreach ($this->products as $key => $product) {
            $arrayKey =  $key + 1;
            $array['pr' . $arrayKey . 'id'] = $product->getId();
            $array['pr' . $arrayKey . 'nm'] = $product->getName();
            $array['pr' . $arrayKey . 'ca'] = $product->getCategory();
            $array['pr' . $arrayKey . 'br'] = $product->getBrand();
            $array['pr' . $arrayKey . 'va'] = $product->getVariant();
            $array['pr' . $arrayKey . 'ps'] = $product->getPosition();
        }
        return $array;
    }

}

class MeasurementProtocolProduct {
    private $id;
    private $nm;
    private $ca;
    private $br;
    private $va;
    private $ps;

    public function setId($id) {
        $this->id = $id;
        return $this;
    }

    public function getId() {
        return $this->id;
    }

    public function setName($nm) {
        $this->nm = $nm;
        return $this;
    }

    public function getName() {
        return $this->nm;
    }

    public function setCategory($ca) {
        $this->ca = $ca;
        return $this;
    }

    public function getCategory() {
        return $this->ca;
    }

    public function setBrand($br) {
        $this->br = $br;
        return $this;
    }

    public function getBrand() {
        return $this->br;
    }

    public function setVariant($va) {
        $this->va = $va;
        return $this;
    }

    public function getVariant() {
        return $this->va;
    }

    public function setPosition($ps) {
        $this->ps = $ps;
        return $this;
    }

    public function getPosition() {
        return $this->ps;
    }
}