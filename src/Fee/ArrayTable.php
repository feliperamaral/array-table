<?php

namespace Fee;

class ArrayTable
{

    /**
     * @param array $data
     * @param array $nameCols
     * @param array $options
     * @return string
     */
    private static $funcsNumArgs = array();
    private static $commomDefault = array(
        'attributes' => array(),
        'rowAttributes' => array(),
        'cellAttributes' => array(),
        'callbacks' => array(
            '*' => null,
            'index' => array()
        ),
        'values' => array(
            '*' => null,
            'index' => array()
        )
    );
    public static $optionsDefault = array(
        'table' => array(
            'attributes' => array()
        ),
        'thead' => array(
            'typeOfCell' => 'th'
        ),
        'tbody' => array(
            'typeOfCell' => 'td'
        ),
        'tfoot' => array(
            'typeOfCell' => 'td'
        ),
    );

    public static function render($data, $nameCols = array(), $options = array())
    {
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('The argument "$data" is not a "array" or instance of "\Traversable". "$data" is: ' . gettype($data));
        }

        $options = self::normalizeOptions($options);

        if (!$nameCols) {
            $firstKeys = array_keys(current($data));
            $nameCols = array_combine($firstKeys, $firstKeys);
        }
        $nameCols = array_keys($nameCols);
        reset($data);

        $html = '<table' . self::arrayToAttr($options['table']['attributes']) . '>';

        if (sizeof($data) <= 3 && (isset($data['thead']) || isset($data['tbody']) || isset($data['tfoot']))) {
            foreach ($data as $type => $dataTable) {
                $html .= self::renderGroup($dataTable, $nameCols, $options[$type], $type);
            }
        } else {
            $html .= self::renderGroup(array($nameCols), $nameCols, $options['thead'], 'thead');
            $html .= self::renderGroup($data, $nameCols, $options['tbody'], 'tbody');
        }
        return $html . '</table>';
    }

    /**
     * 
     * @param array|\Traversable $data
     * @param array $indices
     * @param array $options
     * @param string $type
     * @return string
     */
    private static function renderGroup($data, $indices, $options, $type)
    {

        $html = "<$type>";

        foreach ($data as $row):
            if ($row instanceof \Traversable) {
                $row = iterator_to_array($row);
            }
            $currentConfigs = array(
                'tr' => array(
                    'attributes' => ''
                )
            );
            $contentTr = '';

            foreach ($indices as $index) {
                $contentTr .= self::renderContent($row, $index, $options);
            }

            $html .= '<tr' . self::arrayToAttr($currentConfigs['tr']['attributes']) . ">{$contentTr}</tr>";
        endforeach;

        return $html . "</$type>";
    }

    private static function renderContent($row, $i, $options)
    {
        $content = isset($row[$i]) ? $row[$i] : '';

        if (isset($options['values']['fields'][$i])) {
            $content = $options['values']['fields'][$i];
        }

        $currentConfigs = $options;
        $callbacks = array();

        if(isset($options['callbacks']['*'])){
            $callbacks = (array) $options['callbacks']['*'];
        }

        if (isset($options['callbacks']['fields'][$i])) {
            $callbacks = array_merge($callbacks, (array) $options['callbacks']['fields'][$i]);
        }
        foreach ($callbacks as $callback) {
            if (!is_callable($callback)) {
                continue;
            }

            $arguments = array(&$content, &$row, &$currentConfigs);
            $numArgument = self::getNumArgs($callback);

            array_splice($arguments, is_int($numArgument) ? $numArgument : 3);

            $result = call_user_func_array($callback, $arguments);

            if ($result !== null) {
                $content = $result;
            }
        }
        
//        var_dump($currentConfigs);

        return "<{$currentConfigs['typeOfCell']}" . self::arrayToAttr($currentConfigs['cellAttributes']) . '>'
                . $content
                . "</{$currentConfigs['typeOfCell']}>";
    }

    private static function normalizeOptions($options)
    {
        self::$optionsDefault['thead'] += self::$commomDefault;
        self::$optionsDefault['tbody'] += self::$commomDefault;
        self::$optionsDefault['tfoot'] += self::$commomDefault;

        foreach (array_keys(self::$optionsDefault['tbody']) as $ktb) {
            if (isset($options[$ktb])) {
                if (!isset($options['tbody']) || !is_array($options['tbody'])) {
                    $options['tbody'] = array();
                }
                $options['tbody'][$ktb] = $options[$ktb];
                unset($options[$ktb]);
            }
        }

        foreach (self::$optionsDefault as $key => $optsDefault) {
            if (isset($options[$key])) {
                $options[$key] = array_replace_recursive($optsDefault, $options[$key]);
            } else {
                $options[$key] = $optsDefault;
            }
        }

        return $options;
    }

    public static function arrayToAttr($array, $returnString = true)
    {
        if (!is_array($array)) {
            return $array ? ' ' . $array : '';
        }
        $attributes = array();
        foreach ($array as $name => $value) {
            $attributes [] = "$name=\"$value\"";
        }
        if ($returnString) {
            return ' ' . implode(' ', $attributes);
        }
        return $attributes;
    }

    public function __invoke()
    {
        return call_user_func_array(__CLASS__ . '::render', func_get_args());
    }

    private static function getNumArgs($func)
    {
        if (is_string($func) && isset(self::$funcsNumArgs[$func])) {
            return self::$funcsNumArgs[$func];
        }
        if (!class_exists('ReflectionFunction')) {
            return false;
        }

        $ref = new \ReflectionFunction($func);

        if (is_string($func)) {
            return (self::$funcsNumArgs[$func] = $ref->getNumberOfRequiredParameters());
        }
        return $ref->getNumberOfRequiredParameters();
    }
}
