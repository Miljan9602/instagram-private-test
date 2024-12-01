<?php

namespace InstagramAPI;

class Bloks
{
    public function parseResponse(
        $array,
        $str
    ) {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $results = array_merge($results, $this->parseResponse($value, $str));
            } elseif (is_string($value)) {
                switch ($str) {
                    case 'bk.action.map.Make':
                        $re = '/(\s?\(bk\.action\.map\.Make.*)/m';
                        break;
                    default:
                        $re = '/(\s?\(bk\.action\.core\.TakeLast.*)/m';
                }
                preg_match_all($re, $value, $matches, PREG_SET_ORDER, 0);
                if (!empty($matches[0])) {
                    $results = array_merge($results, $matches[0]);
                }
            }
        }

        return array_unique($results);
    }

    public function getBloks(
        $str
    ) {
        $matches = [];
        $open = 0;
        $start = false;
        for ($i = 0; $i < strlen($str); $i++) {
            if ($str[$i] == '(') {
                if (!$open) {
                    $start = $i;
                }
                $open++;
            } elseif ($str[$i] == ')') {
                $open--;
                if (!$open) {
                    $matches[] = [substr($str, $start, $i - $start + 1), 'childs' => []];
                    $start = false;
                }
            }
        }

        foreach ($matches as $idx => $match) {
            $matches[$idx]['childs'][] = $this->getBloks(substr($match[0], 1, -1));
        }

        return $matches;
    }

    public function searchArray(
        $arr,
        $value
    ) {
        $results = [];
        foreach ($arr as $key => $element) {
            if (is_array($element)) {
                $subResults = $this->searchArray($element, $value);
                $results = array_merge($results, $subResults);
            } elseif ($key === 0 && substr(substr($element, 1), 0, strlen($value)) === $value) {
                $results[] = isset($arr['childs']) ? $arr[0] : $arr;
            }
        }

        return array_unique($results, SORT_REGULAR);
    }

    public function findOffsets(
        $arr,
        $searchValue,
        $offsets = []
    ) {
        foreach ($arr as $key => $value) {
            if ($value === $searchValue) {
                array_unshift($offsets, $key);

                return $offsets;
            } elseif (is_array($value)) {
                $result = $this->findOffsets($value, $searchValue, $offsets);
                if (!empty($result)) {
                    array_unshift($result, $key);

                    return $result;
                }
            }
        }

        return [];
    }

    protected function _fixJsonString(
        $string
    ) {
        $search = ['\\\\', '\\"', "\n", '\\r', '\\t', '\\b', '\\f', "\x0d"];
        $replace = ['\\', '"', '\\n', "\r", "\t", "\b", "\f", ''];
        $json_string = str_replace($search, $replace, $string);
        $search = ['\\\\\\', '\\\\\\\\\\\\', '\"login_response\":\\', 'Secure\"', '\",\"headers\":\"', '\",\"cookies\":\"', '\",\"cookies\"', 'challenge_context\":\"', 'false}\"}}}', 'null}"}}}', '",\"exact_profile_identified\":null}', '{\"registration_response\":\\'];
        $replace = ['\\', '\\\\\\', '"login_response":', 'Secure"', '","headers":"', '","cookies":"', '","cookies"', 'challenge_context\":\\', 'false}\}}}', 'null}}}}', '"}', '{"registration_response":'];
        $json_string = str_replace($search, $replace, $json_string);

        return $json_string;
    }

    protected function _extractJsonString(
        $string
    ) {
        $jsonStart = strpos($string, '{');
        $jsonEnd = strrpos($string, '}');
        if ($jsonStart !== false && $jsonEnd !== false && $jsonEnd > $jsonStart) {
            $json = substr($string, $jsonStart, $jsonEnd - $jsonStart + 1);
            $json = $this->_fixJsonString($json);

            return $json;
        }

        return null;
    }

    protected function _getMatchingKeyValue(
        $array,
        $searchString
    ) {
        foreach ($array as $value) {
            if (is_array($value)) {
                $result = $this->_getMatchingKeyValue($value, $searchString);
                if ($result !== null) {
                    return $result;
                }
            } elseif (is_string($value)) {
                if (strpos($value, $searchString) !== false) {
                    return $value;
                }
            }
        }

        return null;
    }

    public function extractJsonFromBlockContainingKey(
        $response,
        $key
    ) {
        $stringBlok = $this->_getMatchingKeyValue($response, $key);

        return $this->_extractJsonString($stringBlok);
    }

    public function getRegAndInfoFlow(
        $response,
        $key
    ) {
        $jsons = $this->extractJsonFromBlockContainingKey($response, $key);
        $jsons = explode('||', str_replace('}", "{"', '}||{"', $jsons));

        return ['reg_info'  =>  $jsons[0], 'flow_info'  =>  $jsons[1]];
    }

    public function parseMap(
        $input
    ) {
        $output = [];

        $stack = [];
        $token = '';
        $mode = '';

        for ($i = 0; $i < strlen($input); $i++) {
            $char = $input[$i];

            switch ($char) {
                case '(':
                    array_push($stack, $mode);
                    $mode = '';
                    array_push($stack, $output);
                    $output = [];
                    break;
                case ')':
                    if ($mode === 'string') {
                        $output[] = $token;
                    }
                    $token = trim($token);
                    if (!empty($token)) {
                        $output[] = $token;
                    }
                    $token = '';
                    $tmp = array_pop($stack);
                    $prev_mode = array_pop($stack);
                    if ($prev_mode === 'map') {
                        $map = [];
                        for ($j = count($output) - 1; $j > 0; $j -= 2) {
                            $key = $output[$j - 1];
                            $value = $output[$j];
                            $map[$key] = $value;
                        }
                        $output = $map;
                    }
                    array_push($tmp, $output);
                    $output = $tmp;
                    $mode = $prev_mode;
                    break;
                case ',':
                    if ($mode === 'string') {
                        $token .= $char;
                    } else {
                        $token = trim($token);
                        if (!empty($token) && $token !== 'bk.action.map.Make' && $token !== 'bk.action.array.Make') {
                            $output[] = $token;
                        }
                        $token = '';
                    }
                    break;
                case ' ':
                    if ($mode === 'string') {
                        $token .= $char;
                    }
                    break;
                case '"':
                    if ($mode === 'string') {
                        $output[] = $token;
                        $token = '';
                        $mode = '';
                    } else {
                        $mode = 'string';
                    }
                    break;
                default:
                    $token .= $char;
                    break;
            }
        }

        return $output[0];
    }

    public function map_arrays(
        $keys,
        $values
    ) {
        $result = [];
        for ($i = 0; $i < count($keys); $i++) {
            if (array_key_exists($i, $values)) {
                $result[$keys[$i]] = $values[$i];
            } else {
                $result[$keys[$i]] = null;
            }
        }

        return $result;
    }

    public function recursiveArrayMerge(
        array $array1,
        array $array2
    ) {
        $merged = $array1;

        foreach ($array2 as $key => $value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = $this->recursiveArrayMerge($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    public function findValueWithSubstringRecursive(
        $array,
        $substring
    ) {
        $results = [];

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                // Recursive call for sub-array
                $subResults = $this->findValueWithSubstringRecursive($value, $substring);
                if (!empty($subResults)) {
                    $results = array_merge($results, $subResults);
                }
            } elseif (strpos($value, $substring) !== false) {
                $results[] = [$value];
            }
        }

        return $results;
    }

    public function cleanData(
        $array
    ) {
        $result = [];
        foreach ($array as $k=>$v) {
            if (!is_array($v)) {
                $result[$k] = $v;
            } else {
                switch ($v[0]) {
                    case 'bk.action.i64.Const':
                    case 'bk.action.i32.Const':
                    case 'bk.action.bool.Const':
                        $result[$k] = $v[1];
                        break;
                    case 'bk.action.array.Get':
                    case 'bk.action.bloks.GetState':
                        $result[$k] = $this->cleanData(array_slice($v, 1));
                        // no break
                    default:
                        break;
                }
            }
        }

        return $result;
    }

    public function parseBlok(
        $bloksResponse,
        $blok
    ) {
        $bloks = $this->getBloks($bloksResponse);
        $res = $this->searchArray($bloks, $blok);

        switch ($blok) {
            case 'bk.action.caa.HandleLoginResponse':
            case 'bk.action.caa.PresentTwoFactorAuthFlow':
            case 'bk.action.caa.PresentCheckpointsFlow':
                if (count($res) > 0) {
                    return $this->_extractJsonString($res[0]);
                }
                $blok = 'bk.action.map.Make';
                $bloks = $this->getBloks($bloksResponse);
                $res = $this->searchArray($bloks, $blok);
                // no break
            case 'assistive_login_info':
                foreach ($res as $arr) {
                    if (str_contains($arr, 'assistive_login_info')) {
                        return stripcslashes($this->_extractJsonString($arr));
                    }
                }
                // no break
            case 'bk.action.map.Make':
                $mapped = [];
                foreach ($res as $map) {
                    $mapped[] = $this->parseMap($map);
                }

                return $mapped;
            default:
                return null;
        }
    }
}
