<?php

namespace App\Swirf;

class Swirf
{
    private $request_raw = [];
    private $input = null;
    private $isEncrypted = false;

    public function getRequestRaw()
    {
        return $this->request_raw;
    }

    public function setInput($input)
    {
        $this->input = $input;
    }

    public function getInput($array = false)
    {
        return ($this->input == null) ? null : (is_array($this->input) ? (($array) ? $this->input : array2object($this->input)) : $this->input);
    }

    public function input($key = null, $array = false)
    {
        if (!($this->input == null)) {
            if ($key == null) {
                return is_array($this->input) ? (($array) ? $this->input : array2object($this->input)) : $this->input;
            } else {
                if (isset($this->input[$key])) {
                    return is_array($this->input[$key]) ? (($array) ? $this->input[$key] : array2object($this->input[$key])) : $this->input[$key];
                }
            }
        } else {
            if ($key == null) {
                return ($array) ? array() : (object) array();
            }
        }

        return;
    }

    public function processInput($input)
    {
        $this->request_raw = $input;
            $data = $input;
            if (beginWith(trim($data), '{')) {
                $clean = prepare_json_decode($input);
                $json = json_decode($clean, true, 512 , JSON_BIGINT_AS_STRING);
                if (!(is_null($json))) {
                    $this->input = valueArrayToValidType($json);
                }
            }
            else
            {
                $decrypted = base64_decode($data);
                $clean = prepare_json_decode($decrypted);
                $json = json_decode($clean, true);
                if (!(is_null($json))) {
                    $this->input = valueArrayToValidType($json);
                    $this->isEncrypted = true;
                }
            }

            if (!is_null($this->input)) {
                $results = array();
                foreach ($this->input as $key => $value) {
                    $results[$key] = $value;
                }
                $this->input = $results;
            }
    }




}
