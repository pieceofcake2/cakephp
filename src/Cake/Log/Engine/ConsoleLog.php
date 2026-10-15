<?php
/**
 * Console Logging
 *
 * CakePHP(tm) :  Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          http://www.cakefoundation.org/projects/info/cakephp CakePHP(tm) Project
 * @package       Cake.Log.Engine
 * @since         CakePHP(tm) v 2.2
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

/**
 * Console logging. Writes logs to console output.
 *
 * @package       Cake.Log.Engine
 */
class ConsoleLog extends BaseLog
{
    /**
     * Output stream
     *
     * @var ConsoleOutput
     */
    protected $_output = null;

    /**
     * Constructs a new Console Logger.
     *
     * Config
     *
     * - `types` string or array, levels the engine is interested in
     * - `scopes` string or array, scopes the engine is interested in
     * - `stream` the path to save logs on.
     * - `outputAs` integer or ConsoleOutput::[RAW|PLAIN|COLOR]. Defaults to PLAIN when
     *   the stream is not a terminal (or is a Windows console without ANSI support),
     *   COLOR otherwise.
     *
     * @param array $config Options for the FileLog, see above.
     * @throws CakeLogException
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $config = Hash::merge([
            'stream' => 'php://stderr',
            'types' => null,
            'scopes' => [],
            'outputAs' => null,
            ], $this->_config);
        if ($config['stream'] instanceof ConsoleOutput) {
            $this->_output = $config['stream'];
        } elseif (is_string($config['stream'])) {
            $this->_output = new ConsoleOutput($config['stream']);
        } else {
            throw new CakeLogException('`stream` not a ConsoleOutput nor string');
        }
        if ($config['outputAs'] === null) {
            // ConsoleOutput decides between color and plain output by checking its own stream.
            $config['outputAs'] = $this->_output->outputAs();
            if (DS === '\\' && !(bool)env('ANSICON') && env('ConEmuANSI') !== 'ON') {
                $config['outputAs'] = ConsoleOutput::PLAIN;
            }
        }
        $config = $this->config($config);
        $this->_output->outputAs($config['outputAs']);
    }

    /**
     * Implements writing to console.
     *
     * @param string $type The type of log you are making.
     * @param string $message The message you want to log.
     * @return bool success of write.
     */
    public function write($type, $message)
    {
        $output = date('Y-m-d H:i:s') . ' ' . ucfirst($type) . ': ' . $message . "\n";

        return $this->_output->write(sprintf('<%s>%s</%s>', $type, $output, $type), 0);
    }
}
