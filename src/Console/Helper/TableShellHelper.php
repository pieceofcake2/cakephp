<?php
/**
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         2.8
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Console\Helper;

/**
 * Create a visually pleasing ASCII art table
 * from 2 dimensional array data.
 */
class TableShellHelper extends BaseShellHelper
{
    /**
     * Default config for this helper.
     *
     * @var array
     */
    protected array $_defaultConfig = [
        'headers' => true,
        'rowSeparator' => false,
        'headerStyle' => 'info',
    ];

    /**
     * Calculate the column widths
     *
     * @param array $rows The rows on which the columns width will be calculated on.
     * @return array
     */
    protected function _calculateWidths(array $rows): array
    {
        $widths = [];
        foreach ($rows as $line) {
            for ($i = 0, $len = count($line); $i < $len; $i++) {
                $columnLength = mb_strlen($line[$i]);
                if ($columnLength > ($widths[$i] ?? 0)) {
                    $widths[$i] = $columnLength;
                }
            }
        }

        return $widths;
    }

    /**
     * Output a row separator.
     *
     * @param array $widths The widths of each column to output.
     * @return void
     */
    protected function _rowSeparator(array $widths): void
    {
        $out = '';
        foreach ($widths as $column) {
            $out .= '+' . str_repeat('-', $column + 2);
        }
        $out .= '+';
        $this->_consoleOutput->write($out);
    }

    /**
     * Output a row.
     *
     * @param array $row The row to output.
     * @param array $widths The widths of each column to output.
     * @param array $options Options to be passed.
     * @return void
     */
    protected function _render(array $row, array $widths, array $options = []): void
    {
        $out = '';
        foreach ($row as $i => $column) {
            $pad = $widths[$i] - mb_strlen($column);
            if (!empty($options['style'])) {
                $column = $this->_addStyle($column, $options['style']);
            }
            $out .= '| ' . $column . str_repeat(' ', $pad) . ' ';
        }
        $out .= '|';
        $this->_consoleOutput->write($out);
    }

    /**
     * Output a table.
     *
     * @param array $args The data to render out.
     * @return void
     */
    public function output(array $args): void
    {
        $config = $this->config();
        $widths = $this->_calculateWidths($args);
        $this->_rowSeparator($widths);
        if ($config['headers'] === true) {
            $this->_render(array_shift($args), $widths, ['style' => $config['headerStyle']]);
            $this->_rowSeparator($widths);
        }
        foreach ($args as $line) {
            $this->_render($line, $widths);
            if ($config['rowSeparator'] === true) {
                $this->_rowSeparator($widths);
            }
        }
        if ($config['rowSeparator'] !== true) {
            $this->_rowSeparator($widths);
        }
    }

    /**
     * Add style tags
     *
     * @param string $text The text to be surrounded
     * @param string $style The style to be applied
     * @return string
     */
    protected function _addStyle(string $text, string $style): string
    {
        return '<' . $style . '>' . $text . '</' . $style . '>';
    }
}
