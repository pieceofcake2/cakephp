<?php

namespace Cake\Controller\Component\Acl;

/**
 * Access Control Object
 */
class PhpAco
{
    /**
     * holds internal ACO representation
     *
     * @var array
     */
    protected array $_tree = [];

    /**
     * map modifiers for ACO paths to their respective PCRE pattern
     *
     * @var array
     */
    public static array $modifiers = [
        '*' => '.*',
    ];

    /**
     * Constructor
     *
     * @param array $rules Rules array
     */
    public function __construct(array $rules = [])
    {
        foreach (['allow', 'deny'] as $type) {
            if (empty($rules[$type])) {
                $rules[$type] = [];
            }
        }

        $this->build($rules['allow'], $rules['deny']);
    }

    /**
     * return path to the requested ACO with allow and deny rules attached on each level
     *
     * @param array|string $aco ACO string
     * @return array
     */
    public function path(array|string $aco): array
    {
        $aco = $this->resolve($aco);
        $path = [];
        $root = $this->_tree;
        $stack = [[$root, 0]];

        while (!empty($stack)) {
            [$root, $level] = array_pop($stack);

            if (empty($path[$level])) {
                $path[$level] = [];
            }

            foreach ($root as $node => $elements) {
                $pattern = '/^' . str_replace(array_keys(static::$modifiers), array_values(static::$modifiers), $node) . '$/';

                if ($node == $aco[$level] || preg_match($pattern, $aco[$level])) {
                    // merge allow/denies with $path of current level
                    foreach (['allow', 'deny'] as $policy) {
                        if (!empty($elements[$policy])) {
                            if (empty($path[$level][$policy])) {
                                $path[$level][$policy] = [];
                            }
                            $path[$level][$policy] = array_merge($path[$level][$policy], $elements[$policy]);
                        }
                    }

                    // traverse
                    if (!empty($elements['children']) && isset($aco[$level + 1])) {
                        $stack[] = [$elements['children'], $level + 1];
                    }
                }
            }
        }

        return $path;
    }

    /**
     * allow/deny ARO access to ARO
     *
     * @param array|string $aro ARO string
     * @param array|string  $aco ACO string
     * @param string|null $action Action string
     * @param string $type access type
     * @return void
     */
    public function access(array|string $aro, array|string $aco, ?string $action, string $type = 'deny'): void
    {
        $aco = $this->resolve($aco);
        $depth = count($aco);
        $root = $this->_tree;
        $tree = &$root;

        foreach ($aco as $i => $node) {
            if (!isset($tree[$node])) {
                $tree[$node] = [
                    'children' => [],
                ];
            }

            if ($i < $depth - 1) {
                $tree = &$tree[$node]['children'];
            } else {
                if (empty($tree[$node][$type])) {
                    $tree[$node][$type] = [];
                }

                $tree[$node][$type] = array_merge(is_array($aro) ? $aro : [$aro], $tree[$node][$type]);
            }
        }

        $this->_tree = &$root;
    }

    /**
     * resolve given ACO string to a path
     *
     * @param array|string $aco ACO string
     * @return array path
     */
    public function resolve(array|string $aco): array
    {
        if (is_array($aco)) {
            return array_map('strtolower', $aco);
        }

        // strip multiple occurrences of '/'
        $aco = preg_replace('#/+#', '/', $aco);
        // make case insensitive
        $aco = ltrim(strtolower($aco), '/');

        return array_filter(array_map('trim', explode('/', $aco)));
    }

    /**
     * build a tree representation from the given allow/deny informations for ACO paths
     *
     * @param array $allow ACO allow rules
     * @param array $deny ACO deny rules
     * @return void
     */
    public function build(array $allow, array $deny = []): void
    {
        $this->_tree = [];

        foreach ($allow as $dotPath => $aros) {
            if (is_string($aros)) {
                $aros = array_map('trim', explode(',', $aros));
            }

            $this->access($aros, $dotPath, null, 'allow');
        }

        foreach ($deny as $dotPath => $aros) {
            if (is_string($aros)) {
                $aros = array_map('trim', explode(',', $aros));
            }

            $this->access($aros, $dotPath, null, 'deny');
        }
    }
}
