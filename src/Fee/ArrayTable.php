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
     * @param array $dados
     * @param array $nameCols
     * @param array $options
     * @return string
     */
    private static $funcsNumArgs = array();

    public static function render(array $dados, $nameCols = array(), $options = array(), $exportToExcel = false)
    {
        $classes                           = 'table table-condensed table-bordered table-hover table-striped table-highlight';
        $attributes                        = array(
            'class' => $classes,
        );
        if ($exportToExcel)
            $attributes['data-p-tabletoexcel'] = true;
        $optionsDefault                    = array(
            'table'     => array(
                'attributes' => $attributes
            ),
            'countCol'  => true,
            'actions'   => array(),
            'callbacks' => array(
                'fields' => array(
                )
            )
        );

        $options = array_merge_recursive($options, $optionsDefault);
        $html    = '<table ' . self::arrayToAttributes($options['table']['attributes']) . '>';
        $html .= '<thead>';

        if (!$nameCols) {
            $keys     = array_keys(end($dados));
            $nameCols = array_combine($keys, $keys);
        }
        $html .= '<tr>';

        foreach ($nameCols as $name => $colName) {
            $html .= "<th>{$colName}</th>";
        }

        if ($options['actions'])
            $html .= '<th>Ações</th>';

        $html .= '</tr>';
        $html .= '</thead>';

        $html .= '<tbody>';
        reset($dados);
        foreach ($dados as $rowNum => $row):
            $currentConfigs = array(
                'tr' => array(
                    'attributes' => ''
                )
            );
            $contentTr      = '';

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
                    $arguments   = array(
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
                if ($currentConfigs['td']['attributes'])
                    $attributesTD = self::arrayToAttributes($currentConfigs['td']['attributes']);
                $contentTr .= "<td{$attributesTD}>{$content}</td>";
            endforeach;

            if ($options['actions']) :
                $contentTr .= '<td>';
                foreach ($options['actions'] as $action):
                    $attrs = isset($action['attrs']) ? $action['attrs'] : '';
                    $url   = str_replace('??', $row[$options['primary']], $action['url']);
                    $contentTr .= "<a href=\"{$url}\" {$attrs}>";

                    if (isset($action['icon']))
                        $contentTr .= "<i class=\"{$action['icon']}\"></i>";

                    if (isset($action['html']))
                        $contentTr .= $action['html'];
                    $contentTr .= '</a> ';
                endforeach;
                $contentTr .= '</td>';
            endif;
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
        if ($returnString)
            return ' ' . implode(' ', $attributes);
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
