<?php
/**
 * AppShell file
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
 * @since         CakePHP(tm) v 2.0
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

namespace Cake\Console\Command;

use Cake\Console\Shell;

/**
 * Application Shell
 *
 * Add your application-wide methods in the class below, your shells
 * will inherit them.
 *
 * This is the AppShell for an application that has none. It declares no global name itself:
 * src/aliases.php declares AppShell for it only when the application has no AppShell, which
 * may be loaded after this class.
 *
 * @package       app.Console.Command
 */
class AppShell extends Shell
{
}
