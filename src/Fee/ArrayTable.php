<?php

namespace Fee;

class ArrayTable
{

    /**
     * $options = array(
     *   'callbacks' => array(
     *       'fields' => array(
     *       'telefone' => function($filedValue, $row) {
     *           //code
     *              return $filedValue;
     *           }
     *       )
     *   )
     * );
     *
     *
     * @param array $data
     * @param array $nameCols
     * @param array $options
     * @return string
     */
    private static $funcsNumArgs = array();

    public static function render($data, $nameCols = array(), $options = array())
    {
        if ($data instanceof \Traversable) {
            $data = iterator_to_array($data);
        }
        if (!is_array($data)) {
            throw new \InvalidArgumentException('The argument "$data" is not a "array" or instance of "\Traversable". "$data" is: ' . gettype($data));
        }
        $classes = 'table table-condensed table-bordered table-hover table-striped table-highlight';
        $attributes = array(
            'class' => $classes,
        );

        $optionsDefault = array(
            'table' => array(
                'attributes' => $attributes
            ),
            'countCol' => true,
            'actions' => array(),
            'callbacks' => array(
                'fields' => array(
                )
            )
        );

        $options = array_replace_recursive($optionsDefault, $options);
        $html = '<table ' . self::arrayToAttributes($options['table']['attributes']) . '>';
        $html .= '<thead>';

        $data = array_map(function($value) {
            if ($value instanceof \Traversable) {
                $value = iterator_to_array($value);
            }
            return $value;
        }, $data);

        if (!$nameCols) {
            $keys     = array_keys(end($data));
            $nameCols = array_combine($keys, $keys);
        }
        $html .= '<tr>';

        foreach ($nameCols as $name => $colName) {
            $html .= "<th>{$colName}</th>";
        }

        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        reset($data);
        foreach ($data as $rowNum => $row):
            $currentConfigs = array(
                'tr' => array(
                    'attributes' => ''
                )
            );
            $contentTr = '';

            foreach ($nameCols as $name => $colName):
                if ($colName === '#') {
                    if (!$options['countCol']) {
                        continue;
                    }
                    $content = ++$rowNum;
                } else {
                    $content = isset($row[$name]) ? $row[$name] : '';
                    if (isset($options['values']['fields'][$name])) {
                        $content = $options['values']['fields'][$name];
                    }
                }
                $currentConfigs['td'] = array(
                    'attributes' => ''
                );
                if (isset($options['callbacks']['fields'][$name]) && is_callable($options['callbacks']['fields'][$name])) {
                    $arguments = array(
                        &$content, &$row, &$currentConfigs
                    );
                    $numArgument = self::getNumArgs($options['callbacks']['fields'][$name]);

                    if ($numArgument !== false && is_int($numArgument)) {
                        array_splice($arguments, $numArgument);
                        $result = call_user_func_array($options['callbacks']['fields'][$name], $arguments);
                    } else {
                        $result = $options['callbacks']['fields'][$name]($content, $row, $currentConfigs);
                    }

                    if ($result !== null) {
                        $content = $result;
                    }
                }
                $attributesTD = '';
                if ($currentConfigs['td']['attributes']){
                    $attributesTD = self::arrayToAttributes($currentConfigs['td']['attributes']);
                }
                $contentTr .= "<td{$attributesTD}>{$content}</td>";
            endforeach;

            $html .= '<tr' . self::arrayToAttributes($currentConfigs['tr']['attributes']) . ">{$contentTr}</tr>";
        endforeach;

        $html .= '</tbody>';

        $html .= '</table>';

        return $html;
    }

    public static function arrayToAttributes($array, $returnString = true)
    {
        if (!is_array($array)) {
            return $array ? ' ' . $array : '';
        }
        $attributes = array();
        foreach ($array as $name => $value) {
            $attributes [] = "$name=\"$value\"";
        }
        if ($returnString){
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
        if (!class_exists('ReflectionFunction'))
            return false;

        $ref = new \ReflectionFunction($func);
        if (is_string($func)) {
            return (self::$funcsNumArgs[$func] = $ref->getNumberOfParameters());
        }
        return $ref->getNumberOfParameters();
    }

}
