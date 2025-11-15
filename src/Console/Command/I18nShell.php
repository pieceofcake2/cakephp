<?php
/**
 * Internationalization Management Shell
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @since         CakePHP(tm) v 1.2.0.5669
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Console\Command;

use AppShell;
use Cake\Console\Command\Task\ExtractTask;
use Cake\Console\ConsoleOptionParser;
use Cake\Core\App;

App::uses('AppShell', 'Console/Command');

/**
 * Shell for I18N management.
 *
 * @package       Cake.Console.Command
 * @property ExtractTask $Extract
 */
class I18nShell extends AppShell
{
    /**
     * Contains database source to use
     *
     * @var string
     */
    public string $dataSource = 'default';

    /**
     * Contains tasks to load and instantiate
     *
     * @var array|bool|null
     */
    public array|bool|null $tasks = ['Extract'];

    /**
     * Override startup of the Shell
     *
     * @return void
     */
    public function startup(): void
    {
        $this->_welcome();
        if (isset($this->params['datasource'])) {
            $this->dataSource = $this->params['datasource'];
        }

        if ($this->command && $this->command !== 'help') {
            if (!config('database')) {
                $this->err(__d('cake_console', 'Your database configuration was not found.'));
                $this->err(__d('cake_console', 'Please create app/Config/database.php manually.'));
                $this->err(__d('cake_console', 'You can use app/Config/database.php.default as a template.'));
                $this->_stop(1);
            }
        }
    }

    /**
     * Override main() for help message hook
     *
     * @return void
     */
    public function main(): void
    {
        $this->out(__d('cake_console', '<info>I18n Shell</info>'));
        $this->hr();
        $this->out(__d('cake_console', '[E]xtract POT file from sources'));
        $this->out(__d('cake_console', '[I]nitialize i18n database table'));
        $this->out(__d('cake_console', '[H]elp'));
        $this->out(__d('cake_console', '[Q]uit'));

        $choice = strtolower($this->in(__d('cake_console', 'What would you like to do?'), ['E', 'I', 'H', 'Q']));
        switch ($choice) {
            case 'e':
                $this->Extract->execute();
                break;
            case 'i':
                $this->initdb();
                break;
            case 'h':
                $this->out($this->OptionParser->help());
                break;
            case 'q':
                $this->_stop();

                return;
            default:
                $this->out(__d('cake_console', 'You have made an invalid selection. Please choose a command to execute by entering E, I, H, or Q.'));
        }
        $this->hr();
        $this->main();
    }

    /**
     * Initialize I18N database.
     *
     * @return void
     */
    public function initdb(): void
    {
        $this->dispatchShell('schema create i18n');
    }

    /**
     * Gets the option parser instance and configures it.
     *
     * @return ConsoleOptionParser
     */
    public function getOptionParser(): ConsoleOptionParser
    {
        $parser = parent::getOptionParser();

        $parser->description(
            __d('cake_console', 'I18n Shell initializes i18n database table for your application and generates .pot files(s) with translations.'),
        )->addSubcommand('initdb', [
            'help' => __d('cake_console', 'Initialize the i18n table.'),
        ])->addSubcommand('extract', [
            'help' => __d('cake_console', 'Extract the po translations from your application'),
            'parser' => $this->Extract->getOptionParser(),
        ]);

        return $parser;
    }
}
