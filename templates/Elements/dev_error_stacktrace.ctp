<?php
/**
 * Prints the stack trace of an exception and its previous exceptions for the dev_error layout.
 *
 * Ported from the CakePHP 5 `templates/element/dev_error_stacktrace.php` element.
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
 * @package       Cake.View.Elements
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 * @var View $this
 * @var Throwable $error
 */

$exceptions = array();
$exc = $error;
while ($exc instanceof Throwable && count($exceptions) < 20) {
	$exceptions[] = $exc;
	$exc = $exc->getPrevious();
}

$vendorPaths = array(CAKE_CORE_INCLUDE_PATH . DS);
if (defined('ROOT')) {
	$vendorPaths[] = ROOT . DS . 'vendor' . DS;
}
$isVendorFile = function ($file) use ($vendorPaths) {
	foreach ($vendorPaths as $path) {
		if (strpos($file, $path) === 0) {
			return true;
		}
	}

	return strpos($file, DS . 'vendor' . DS) !== false;
};

// Debugger::excerpt() takes a zero based line number and returns up to 5 lines
// before and 3 lines after the highlighted line.
$excerpt = function ($file, $line) {
	return array(
		max(1, $line - 5),
		Debugger::excerpt($file, $line - 1, 4),
	);
};

// Link that opens the file in the editor set with the `Debugger.editor` configure value.
$editLink = function ($file, $line) {
	try {
		$url = Debugger::editorUrl($file, $line);
	} catch (InvalidArgumentException $e) {
		return '';
	}

	return '<a href="' . h($url) . '" class="stack-frame-edit">(edit)</a>';
};

foreach ($exceptions as $level => $exc) :
	$frames = $exc->getTrace();
	if ($level > 0) :
		// Only show the frames that are not part of the trace of the exception that wraps this one.
		$parentFrames = $exceptions[$level - 1]->getTrace();
		for ($i = count($frames) - 1, $p = count($parentFrames) - 1; $i >= 0 && $p >= 0; $p--) :
			if (
				isset($frames[$i]['file'], $frames[$i]['line'], $parentFrames[$p]['file'], $parentFrames[$p]['line']) &&
				$frames[$i]['file'] === $parentFrames[$p]['file'] &&
				$frames[$i]['line'] === $parentFrames[$p]['line']
			) :
				unset($frames[$i]);
				$i--;
			endif;
		endfor;
	?>
		<div class="stack-exception-header">
			<span class="stack-exception-caused"><?php echo __d('cake_dev', 'Caused by'); ?></span>
			<span class="stack-exception-message"><?php echo h($exc->getMessage()); ?></span>
			<span class="stack-exception-type"><?php echo h(get_class($exc)); ?></span>
		</div>
	<?php endif; ?>

	<div class="stack-frame">
		<?php
		$file = $exc->getFile();
		$line = $exc->getLine();
		list($lineno, $lines) = $excerpt($file, $line);
		?>
		<span class="stack-frame-file">
			<?php echo h(Debugger::trimPath($file)); ?> <?php echo __d('cake_dev', 'at line'); ?> <?php echo h($line); ?>
		</span>
		<?php if ($line) : ?>
			<?php echo $editLink($file, $line); ?>
		<?php endif; ?>

		<table class="code-excerpt" cellspacing="0" cellpadding="0">
		<?php foreach ($lines as $l => $code) : ?>
			<tr>
				<td class="excerpt-number" data-number="<?php echo $lineno + $l; ?>"></td>
				<td class="excerpt-line"><?php echo $code; ?></td>
			</tr>
		<?php endforeach; ?>
		</table>
	</div>

	<ul class="stack-frames">
	<?php
	foreach (array_values($frames) as $i => $stack) :
		$lines = array();
		$lineno = 0;
		$params = array();
		$line = null;
		if (isset($stack['file'], $stack['line']) && is_numeric($stack['line'])) :
			$line = (int)$stack['line'];
			list($lineno, $lines) = $excerpt($stack['file'], $line);
		endif;

		$file = isset($stack['file']) ? $stack['file'] : '[internal function]';

		if (isset($stack['function'])) :
			if (!empty($stack['args'])) :
				foreach ((array)$stack['args'] as $arg) :
					$params[] = Debugger::exportVar($arg, 4);
				endforeach;
			else :
				$params[] = __d('cake_dev', 'No arguments');
			endif;
		endif;

		$frameId = $level . '-' . $i;
		$vendorFrame = isset($stack['file']) && $isVendorFile($stack['file']) ? ' vendor-frame' : '';
	?>
		<li id="stack-frame-<?php echo $frameId; ?>" class="stack-frame<?php echo $vendorFrame; ?>">
			<div class="stack-frame-header">
				<button type="button" data-frame-id="<?php echo h($frameId); ?>" class="stack-frame-toggle">
					&#x25BC;
				</button>

				<div class="stack-frame-header-content">
					<span class="stack-frame-file">
						<?php echo h(Debugger::trimPath($file)); ?>
					</span>

					<?php if ($line !== null) : ?>
						<span class="stack-frame-line">
							<span class="stack-frame-label"><?php echo __d('cake_dev', 'at line'); ?></span><?php echo h($line); ?>
						</span>
					<?php endif; ?>

					<span class="stack-function">
						<?php if (isset($stack['class']) || isset($stack['function'])) : ?>
							<span class="stack-frame-label"><?php echo __d('cake_dev', 'in'); ?></span>
						<?php endif; ?>
						<?php if (isset($stack['class'])) : ?>
							<?php echo h($stack['class'] . (isset($stack['type']) ? $stack['type'] : '::') . $stack['function']); ?>
						<?php elseif (isset($stack['function'])) : ?>
							<?php echo h($stack['function']); ?>
						<?php endif; ?>
					</span>

					<?php if ($line !== null) : ?>
						<?php echo $editLink($file, $line); ?>
					<?php endif; ?>
				</div>
			</div>
			<div
				class="stack-frame-contents"
				id="stack-frame-details-<?php echo $frameId; ?>"
				style="display: none"
			>
				<table class="code-excerpt" cellspacing="0" cellpadding="0">
				<?php foreach ($lines as $l => $code) : ?>
					<tr>
						<td class="excerpt-number" data-number="<?php echo $lineno + $l; ?>"></td>
						<td class="excerpt-line"><?php echo $code; ?></td>
					</tr>
				<?php endforeach; ?>
				</table>

				<a href="#" class="stack-frame-args" data-target="stack-args-<?php echo $frameId; ?>"><?php echo __d('cake_dev', 'Toggle Arguments'); ?></a>
				<div id="stack-args-<?php echo $frameId; ?>" class="stack-args" style="display: none;">
					<?php foreach ($params as $param) : ?>
						<pre class="cake-debug"><?php echo h($param); ?></pre>
					<?php endforeach; ?>
				</div>
			</div>
		</li>
	<?php endforeach; ?>
	</ul>
<?php endforeach; ?>
