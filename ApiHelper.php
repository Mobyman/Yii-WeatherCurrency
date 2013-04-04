<?php

abstract class AbstractApi {

    public $name;

	abstract function getUpdate();
    abstract function getData();

    protected function getJson() {
        $model = Api::model()->uptodate()->findByAttributes(array('name' => $this->name));
        if($model && $model->value) {
            return $model->value;
        } else {
            $model = Api::model()->findByAttributes(array('name' => $this->name));
            $updatedValue = $this->getUpdate();
            if(!empty($updatedValue)) {
                if(!$model) {
                    $model = new Api();
                    $model->name = $this->name;
                }
                $model->value = $updatedValue;
                $model->timestamp = new CDbExpression('NOW()');
                $model->save();
            } else {
                $model = Api::model()->findByPk((string)$this->name);
            }
            if(isset($model->value))
                return $model->value;
            else
                return null;
        }
    }
}

class Weather extends AbstractApi {
	
	public $name;
	private $key;
	private $format;
	private $city;
	private $url;

    public function __construct() {
        $this->key = ""; // key form http://api.worldweatheronline.com

        $this->name = "weather";
        $this->format = "json";
        $this->city = "Novosibirsk";
        $this->url = sprintf(
            "http://api.worldweatheronline.com/free/v1/weather.ashx?q=%s&format=%s&num_of_days=2&key=%s",
            $this->city, $this->format, $this->key
        );
    }

	public function getUpdate() {
        if (!HttpHelper::checkUrl($this->url))
            return false;
		return file_get_contents($this->url);
	}

    public function getData(){
        if($this->format == "json") {
            $json = $this->getJson();
            if ($json) {
                $result = CJSON::decode($json, true);
                if(isset($result['data']['current_condition'][0]['temp_C'])) {
                    return $result['data']['current_condition'][0]['temp_C'];
                } else {
                    return 0xFF;
                }
            } else {
                return 0xFF;
            }
        } else {
            if(defined(YII_DEBUG) && YII_DEBUG)
                throw new Exception("API type not supported", 400);
        }
    }
}

class Currency extends AbstractApi {

    public $name;
	private $url;

    public function __construct() {
        $this->name = "currency";
        $this->url = 'http://www.cbr.ru/scripts/XML_daily.asp';
    }


	public function getUpdate() {
        if (!HttpHelper::checkUrl($this->url))
            return false;

		$content = file_get_contents($this->url);
		$xml = simplexml_load_string($content);

		$currency['USD'] = round((float)str_replace(",", ".", $xml->Valute[9]->Value), 2);
		$currency['EUR'] = round((float) str_replace(",", ".", $xml->Valute[10]->Value), 2);
		
		return json_encode($currency);
	}

    public function getData(){
        if (!empty($this)) {
            $json = $this->getJson();
            if($json) {
                $result = json_decode($json, true);
                return $result;
            }
        } else {
            throw new Exception("Empty response", 400);
        }
    }
}
